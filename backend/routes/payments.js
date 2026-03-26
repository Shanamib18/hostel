const express = require('express');
const pool = require('../config/db');
const { auth, staffAuth, adminAuth } = require('../middleware/auth');

const router = express.Router();

// Get fee structure
router.get('/fee-structure', auth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || req.query.hostel_id || 1;
    const [rows] = await pool.execute(
      'SELECT * FROM fee_structure WHERE hostel_id = ? AND is_active = 1',
      [hostelId]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get current dues/fee status for student
router.get('/dues', auth, async (req, res) => {
  try {
    if (req.user.type !== 'student') {
      return res.status(403).json({ error: 'Student access only' });
    }

    const [pendingDues] = await pool.execute(
      `SELECT p.id, p.amount, p.payment_date, p.notes, f.fee_type, f.period
       FROM fee_payments p
       JOIN fee_structure f ON p.fee_structure_id = f.id
       WHERE p.student_id = ? AND p.status = 'pending'
       ORDER BY p.payment_date ASC`,
      [req.user.id]
    );

    res.json(pendingDues);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get student's payment history
router.get('/my-payments', auth, async (req, res) => {
  try {
    const studentId = req.user.type === 'student' ? req.user.id : req.query.student_id;
    if (!studentId && req.user.type !== 'staff') return res.status(400).json({ error: 'Student ID required' });

    const [rows] = await pool.execute(
      `SELECT p.*, f.fee_type, f.period 
       FROM fee_payments p 
       JOIN fee_structure f ON p.fee_structure_id = f.id 
       WHERE p.student_id = ? 
       ORDER BY p.created_at DESC`,
      [studentId || req.user.id]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Create payment (student or staff)
router.post('/pay', auth, async (req, res) => {
  try {
    const { fee_structure_id, amount, payment_method, transaction_id } = req.body;
    const studentId = req.user.type === 'student' ? req.user.id : req.body.student_id;

    if (!studentId || !fee_structure_id || !amount || !payment_method) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    if (req.user.type === 'student' && studentId !== req.user.id) {
      return res.status(403).json({ error: 'Cannot pay for another student' });
    }

    await pool.execute(
      `INSERT INTO fee_payments (student_id, fee_structure_id, amount, payment_date, payment_method, transaction_id, status, paid_at) 
       VALUES (?, ?, ?, CURDATE(), ?, ?, 'completed', NOW())`,
      [studentId, fee_structure_id, amount, payment_method, transaction_id || null]
    );
    res.json({ message: 'Payment recorded successfully' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Get payment summary (staff dashboard)
router.get('/summary', auth, staffAuth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || 1;

    const [pending] = await pool.execute(
      `SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
       FROM fee_payments p 
       JOIN students s ON p.student_id = s.id 
       WHERE s.hostel_id = ? AND p.status = 'pending'`,
      [hostelId]
    );
    const [completed] = await pool.execute(
      `SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
       FROM fee_payments p 
       JOIN students s ON p.student_id = s.id 
       WHERE s.hostel_id = ? AND p.status = 'completed' AND MONTH(p.payment_date) = MONTH(CURDATE())`,
      [hostelId]
    );

    res.json({
      pending: pending[0],
      completed_this_month: completed[0]
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// List students with payment status (staff)
router.get('/student-status', auth, staffAuth, async (req, res) => {
  try {
    const hostelId = req.user.hostel_id || 1;
    const month = req.query.month || new Date().toISOString().slice(0, 7);

    const [rows] = await pool.execute(
      `SELECT s.id, s.student_id, s.name, s.email, r.room_number,
         (SELECT COUNT(*) FROM fee_payments p2 
          WHERE p2.student_id = s.id AND p2.status = 'completed' 
          AND DATE_FORMAT(p2.payment_date, '%Y-%m') = ?) as payments_this_month
       FROM students s 
       LEFT JOIN rooms r ON s.room_id = r.id 
       WHERE s.hostel_id = ? AND s.is_active = 1`,
      [month, hostelId]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
