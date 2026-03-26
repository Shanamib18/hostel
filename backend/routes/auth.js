const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { body, validationResult } = require('express-validator');
const pool = require('../config/db');
const { auth } = require('../middleware/auth');

const router = express.Router();

// Student login
router.post('/student/login', [
  body('email').isEmail().normalizeEmail(),
  body('password').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const [rows] = await pool.execute(
      'SELECT id, student_id, name, email, password_hash, room_id, hostel_id FROM students WHERE email = ? AND is_active = 1',
      [req.body.email]
    );
    if (!rows.length) return res.status(401).json({ error: 'Invalid credentials' });

    const valid = await bcrypt.compare(req.body.password, rows[0].password_hash);
    if (!valid) return res.status(401).json({ error: 'Invalid credentials' });

    const token = jwt.sign(
      { id: rows[0].id, type: 'student', hostel_id: rows[0].hostel_id },
      process.env.JWT_SECRET || 'secret',
      { expiresIn: '7d' }
    );
    res.json({
      token,
      user: {
        id: rows[0].id,
        student_id: rows[0].student_id,
        name: rows[0].name,
        email: rows[0].email,
        room_id: rows[0].room_id,
        hostel_id: rows[0].hostel_id
      }
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Staff login
router.post('/staff/login', [
  body('email').isEmail().normalizeEmail(),
  body('password').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const [rows] = await pool.execute(
      'SELECT id, name, email, password_hash, role, hostel_id FROM staff WHERE email = ?',
      [req.body.email]
    );
    if (!rows.length) return res.status(401).json({ error: 'Invalid credentials' });

    const valid = await bcrypt.compare(req.body.password, rows[0].password_hash);
    if (!valid) return res.status(401).json({ error: 'Invalid credentials' });

    const token = jwt.sign(
      { id: rows[0].id, type: 'staff', role: rows[0].role, hostel_id: rows[0].hostel_id },
      process.env.JWT_SECRET || 'secret',
      { expiresIn: '7d' }
    );
    res.json({
      token,
      user: {
        id: rows[0].id,
        name: rows[0].name,
        email: rows[0].email,
        role: rows[0].role,
        hostel_id: rows[0].hostel_id
      }
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Forgot password request - STAFF
router.post('/staff/forgot-password', [
  body('email').isEmail().normalizeEmail(),
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const { email } = req.body;
    const [rows] = await pool.execute(
      'SELECT id FROM staff WHERE email = ?',
      [email]
    );

    if (!rows.length) {
      // To prevent email enumeration, we send a generic success response.
      // The message doesn't confirm if the user exists.
      return res.json({ message: 'If a staff account with that email exists, a reset link has been generated.' });
    }

    const token = require('crypto').randomBytes(32).toString('hex');
    const expires_at = new Date(Date.now() + 3600000); // Token expires in 1 hour

    // NOTE: This requires the `staff_password_reset_tokens` table created in Step 1.
    await pool.execute(
      'INSERT INTO staff_password_reset_tokens (staff_id, token, expires_at) VALUES (?, ?, ?)',
      [rows[0].id, token, expires_at]
    );

    // In a real app, you'd email this link. For this project, we log it.
    // The PHP frontend will direct the user to this link.
    const resetLink = `http://localhost/hostel/php/reset-password.php?token=${token}`; // Adjust URL to match your PHP server setup
    console.log('------------------------------------------------');
    console.log('STAFF PASSWORD RESET LINK:', resetLink);
    console.log('------------------------------------------------');

    res.json({ message: 'Password reset link generated (check server console for staff link).' });

  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Failed to request password reset.' });
  }
});

// Reset password with token - STAFF
router.post('/staff/reset-password', [
  body('token').notEmpty(),
  body('password').isLength({ min: 6 })
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const { token, password } = req.body;

    const [tokens] = await pool.execute('SELECT staff_id FROM staff_password_reset_tokens WHERE token = ? AND expires_at > NOW()', [token]);
    if (!tokens.length) return res.status(400).json({ error: 'Invalid or expired token' });

    const passwordHash = await bcrypt.hash(password, 10);
    await pool.execute('UPDATE staff SET password_hash = ? WHERE id = ?', [passwordHash, tokens[0].staff_id]);
    await pool.execute('DELETE FROM staff_password_reset_tokens WHERE token = ?', [token]);

    res.json({ message: 'Password updated successfully. Please login.' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get current user
router.get('/me', auth, async (req, res) => {
  try {
    const table = req.user.type === 'student' ? 'students' : 'staff';
    const [rows] = await pool.execute(
      `SELECT * FROM ${table} WHERE id = ?`,
      [req.user.id]
    );
    if (!rows.length) return res.status(404).json({ error: 'User not found' });
    const user = rows[0];
    delete user.password_hash;
    res.json(user);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Forgot password request
router.post('/forgot-password', [
  body('email').isEmail().normalizeEmail(),
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const { email } = req.body;
    const [rows] = await pool.execute(
      'SELECT id, name, email FROM students WHERE email = ? AND is_active = 1',
      [email]
    );

    if (!rows.length) {
      // To prevent email enumeration, send a generic success response.
      return res.json({ message: 'If a student account with that email exists, a reset link has been generated.' });
    }

    const token = require('crypto').randomBytes(32).toString('hex');
    const expires_at = new Date(Date.now() + 3600000); // Token expires in 1 hour

    await pool.execute(
      'INSERT INTO password_reset_tokens (student_id, token, expires_at) VALUES (?, ?, ?)',
      [rows[0].id, token, expires_at]
    );

    // For development: Log the link to console so you can click it
    const resetLink = `http://localhost:3000/?reset_token=${token}`;
    console.log('------------------------------------------------');
    console.log('PASSWORD RESET LINK:', resetLink);
    console.log('------------------------------------------------');

    res.json({ message: 'Password reset link generated (check server console).' });

  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Failed to request password reset.' });
  }
});

// Reset password with token
router.post('/reset-password', [
  body('token').notEmpty(),
  body('password').isLength({ min: 6 })
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ errors: errors.array() });

    const { token, password } = req.body;

    // Find valid token
    const [tokens] = await pool.execute(
      'SELECT student_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()',
      [token]
    );

    if (!tokens.length) return res.status(400).json({ error: 'Invalid or expired token' });

    const passwordHash = await bcrypt.hash(password, 10);
    await pool.execute('UPDATE students SET password_hash = ? WHERE id = ?', [passwordHash, tokens[0].student_id]);
    await pool.execute('DELETE FROM password_reset_tokens WHERE token = ?', [token]);

    res.json({ message: 'Password updated successfully. Please login.' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
