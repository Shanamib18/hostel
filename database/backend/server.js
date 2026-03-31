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

// Global middleware for headers including a robust CSP to permit local connections
app.use((req, res, next) => {
  res.setHeader("Content-Security-Policy", "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' http://localhost:3000 http://127.0.0.1:3000 ws://localhost:3000 http://localhost:3000/.well-known/appspecific/com.chrome.devtools.json;");
  next();
});

// Handle Chrome DevTools internal requests with 200 OK and valid JSON to clear 404 noise
app.get('/.well-known/appspecific/com.chrome.devtools.json', (req, res) => res.status(200).json({}));

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
