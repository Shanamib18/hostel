require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');

const authRoutes = require('./routes/auth');
const attendanceRoutes = require('./routes/attendance');
const paymentsRoutes = require('./routes/payments');
const studentsRoutes = require('./routes/students');
const roomsRoutes = require('./routes/rooms');
const { auth } = require('./middleware/auth');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Serve static frontend files
app.use(express.static(path.join(__dirname, '../frontend')));

// API routes
app.use('/api/auth', authRoutes);
app.use('/api/attendance', auth, attendanceRoutes);
app.use('/api/payments', auth, paymentsRoutes);
app.use('/api/students', auth, studentsRoutes);
app.use('/api/rooms', auth, roomsRoutes);

// Health check
app.get('/api/health', (req, res) => res.json({ status: 'ok', service: 'hostel-lbscek-api' }));

// Error handler
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ error: err.message || 'Internal server error' });
});

app.listen(PORT, () => {
  console.log(`LBSCEK Hostel Management API running on http://localhost:${PORT}`);
});
