const express = require('express');
const pool = require('../config/db');
const { auth, staffAuth } = require('../middleware/auth');
const axios = require('axios');

const router = express.Router();
const FACE_SERVICE = process.env.FACE_SERVICE_URL || 'http://localhost:5000';

// Get student's mess attendance
router.get('/mess', auth, async (req, res) => {
  try {
    if (req.user.type !== 'student') {
      return res.status(403).json({ error: 'Student access only' });
    }
    const [start, end] = [req.query.start || new Date().toISOString().slice(0, 10), req.query.end || new Date().toISOString().slice(0, 10)];

    const [rows] = await pool.execute(
      `SELECT id, meal_type, marked_at, method, verified 
       FROM mess_attendance 
       WHERE student_id = ? AND DATE(marked_at) BETWEEN ? AND ?
       ORDER BY marked_at DESC`,
      [req.user.id, start, end]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Mark mess attendance (face or manual)
router.post('/mess', auth, staffAuth, async (req, res) => {
  try {
    const { student_id, meal_type, image_base64, method = 'manual' } = req.body;

    if (!['breakfast', 'lunch', 'dinner'].includes(meal_type)) {
      return res.status(400).json({ error: 'Invalid meal type' });
    }

    let verifiedStudentId = student_id;
    if (method === 'face' && image_base64) {
      try {
        const { data } = await axios.post(`${FACE_SERVICE}/verify`, {
          image: image_base64,
          user_type: 'student'
        });
        verifiedStudentId = data.user_id;
      } catch (e) {
        return res.status(400).json({ error: 'Face verification failed' });
      }
    } else if (!student_id) {
      return res.status(400).json({ error: 'Student ID required for manual entry' });
    }

    await pool.execute(
      `INSERT INTO mess_attendance (student_id, meal_type, method, verified) 
       VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE marked_at = NOW(), method = VALUES(method), verified = VALUES(verified)`,
      [verifiedStudentId, meal_type, method, method === 'face' ? 1 : 0]
    );
    res.json({ message: 'Attendance marked successfully' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Entry/Exit logs - get student's logs
router.get('/entry-exit', auth, async (req, res) => {
  try {
    const studentId = req.user.type === 'student' ? req.user.id : req.query.student_id;
    if (!studentId && req.user.type !== 'staff') return res.status(400).json({ error: 'Student ID required' });

    const [start, end] = [req.query.start || new Date().toISOString().slice(0, 10), req.query.end || new Date().toISOString().slice(0, 10)];

    const [rows] = await pool.execute(
      `SELECT id, type, recorded_at, method, verified 
       FROM entry_exit_logs 
       WHERE student_id = ? AND DATE(recorded_at) BETWEEN ? AND ?
       ORDER BY recorded_at DESC`,
      [studentId || req.user.id, start, end]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Record entry/exit (face or manual)
router.post('/entry-exit', auth, staffAuth, async (req, res) => {
  try {
    const { student_id, type, image_base64, method = 'manual' } = req.body;

    if (!['entry', 'exit'].includes(type)) {
      return res.status(400).json({ error: 'Invalid type (entry/exit)' });
    }

    let verifiedStudentId = student_id;
    if (method === 'face' && image_base64) {
      try {
        const { data } = await axios.post(`${FACE_SERVICE}/verify`, {
          image: image_base64,
          user_type: 'student'
        });
        verifiedStudentId = data.user_id;
      } catch (e) {
        return res.status(400).json({ error: 'Face verification failed' });
      }
    } else if (!student_id) {
      return res.status(400).json({ error: 'Student ID required for manual entry' });
    }

    await pool.execute(
      'INSERT INTO entry_exit_logs (student_id, type, method, verified) VALUES (?, ?, ?, ?)',
      [verifiedStudentId, type, method, method === 'face' ? 1 : 0]
    );
    res.json({ message: `${type} recorded successfully` });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Dashboard stats (staff)
router.get('/dashboard', auth, staffAuth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || 1;

    const [roomStats] = await pool.execute(
      'SELECT COUNT(*) as total, SUM(current_occupancy) as occupied FROM rooms WHERE hostel_id = ?',
      [hostelId]
    );
    const [todayEntry] = await pool.execute(
      `SELECT COUNT(*) as count FROM entry_exit_logs e 
       JOIN students s ON e.student_id = s.id 
       WHERE s.hostel_id = ? AND e.type = 'entry' AND DATE(e.recorded_at) = CURDATE()`,
      [hostelId]
    );
    const [todayExit] = await pool.execute(
      `SELECT COUNT(*) as count FROM entry_exit_logs e 
       JOIN students s ON e.student_id = s.id 
       WHERE s.hostel_id = ? AND e.type = 'exit' AND DATE(e.recorded_at) = CURDATE()`,
      [hostelId]
    );
    const [messToday] = await pool.execute(
      `SELECT meal_type, COUNT(*) as count FROM mess_attendance m 
       JOIN students s ON m.student_id = s.id 
       WHERE s.hostel_id = ? AND DATE(m.marked_at) = CURDATE() GROUP BY meal_type`,
      [hostelId]
    );

    res.json({
      rooms: roomStats[0],
      today_entry: todayEntry[0].count,
      today_exit: todayExit[0].count,
      mess_today: messToday.reduce((a, r) => ({ ...a, [r.meal_type]: r.count }), {})
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
