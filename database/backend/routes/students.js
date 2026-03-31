const express = require('express');
const pool = require('../config/db');
const { auth, staffAuth, adminAuth } = require('../middleware/auth');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const router = express.Router();
const uploadDir = path.join(__dirname, '../uploads/faces');
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, uploadDir),
  filename: (req, file, cb) => cb(null, `face_${Date.now()}_${file.originalname}`)
});
const upload = multer({ storage, limits: { fileSize: 5 * 1024 * 1024 } });

// List students (staff)
router.get('/', auth, staffAuth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || 1;
    const [rows] = await pool.execute(
      `SELECT s.id, s.student_id, s.name, s.email, s.phone, s.department, s.year, r.room_number, r.floor
       FROM students s 
       LEFT JOIN rooms r ON s.room_id = r.id 
       WHERE s.hostel_id = ? AND s.is_active = 1
       ORDER BY r.room_number, s.name`,
      [hostelId]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get student profile (self or by staff)
router.get('/:id', auth, async (req, res) => {
  try {
    const id = req.params.id;
    if (req.user.type === 'student' && parseInt(id) !== req.user.id) {
      return res.status(403).json({ error: 'Access denied' });
    }

    const [rows] = await pool.execute(
      `SELECT s.id, s.student_id, s.name, s.email, s.phone, s.department, s.year, s.room_id, r.room_number, r.floor
       FROM students s 
       LEFT JOIN rooms r ON s.room_id = r.id 
       WHERE s.id = ?`,
      [id]
    );
    if (!rows.length) return res.status(404).json({ error: 'Student not found' });
    res.json(rows[0]);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Register face (student upload, calls Python service)
router.post('/:id/register-face', auth, upload.single('face_image'), async (req, res) => {
  try {
    const studentId = parseInt(req.params.id);
    if (req.user.type === 'student' && studentId !== req.user.id) {
      return res.status(403).json({ error: 'Access denied' });
    }
    if (!req.file) return res.status(400).json({ error: 'No image uploaded' });

    const faceServiceUrl = process.env.FACE_SERVICE_URL || 'http://localhost:5000';
    const base64 = fs.readFileSync(req.file.path).toString('base64');

    const axios = require('axios');
    const { data } = await axios.post(`${faceServiceUrl}/register`, {
      user_type: 'student',
      user_id: studentId,
      image: base64
    });

    const encodingId = data.encoding_id;
    await pool.execute('UPDATE students SET face_encoding_id = ? WHERE id = ?', [encodingId, studentId]);
    fs.unlinkSync(req.file.path);

    res.json({ message: 'Face registered successfully', encoding_id: encodingId });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
