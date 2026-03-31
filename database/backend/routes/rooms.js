const express = require('express');
const pool = require('../config/db');
const { auth, staffAuth, adminAuth } = require('../middleware/auth');

const router = express.Router();

// List rooms
router.get('/', auth, async (req, res) => {
  try {
    const hostelId = req.user?.hostel_id || req.query.hostel_id || 1;
    const [rows] = await pool.execute(
      `SELECT r.*, 
         (SELECT GROUP_CONCAT(CONCAT(s.name, ' (', s.student_id, ')') SEPARATOR ', ') 
          FROM students s WHERE s.room_id = r.id AND s.is_active = 1) as occupants
       FROM rooms r 
       WHERE r.hostel_id = ?
       ORDER BY r.room_number`,
      [hostelId]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get room occupancy stats
router.get('/occupancy', auth, staffAuth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || 1;
    const [rows] = await pool.execute(
      `SELECT status, COUNT(*) as count FROM rooms WHERE hostel_id = ? GROUP BY status`,
      [hostelId]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
