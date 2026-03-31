const jwt = require('jsonwebtoken');
const pool = require('../config/db');

const auth = async (req, res, next) => {
  try {
    const token = req.header('Authorization')?.replace('Bearer ', '');
    if (!token) {
      return res.status(401).json({ error: 'Access denied. No token provided.' });
    }
    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret');
    req.user = decoded;
    next();
  } catch (error) {
    res.status(401).json({ error: 'Invalid or expired token.' });
  }
};

const staffAuth = async (req, res, next) => {
  if (req.user?.type !== 'staff') {
    return res.status(403).json({ error: 'Staff access required.' });
  }
  next();
};

const adminAuth = async (req, res, next) => {
  if (req.user?.role !== 'admin' && req.user?.role !== 'warden') {
    return res.status(403).json({ error: 'Admin access required.' });
  }
  next();
};

module.exports = { auth, staffAuth, adminAuth };