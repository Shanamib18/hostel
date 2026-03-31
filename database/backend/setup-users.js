/**
 * Fix login: Update all staff and students with bcrypt hash for password123
 * Run from backend:  node setup-users.js
 */
require('dotenv').config();
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');

const hash = bcrypt.hashSync('password123', 10);

async function main() {
  const conn = await mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'hostel_lbscek',
  });
  await conn.execute('UPDATE staff SET password_hash = ? WHERE hostel_id = 1', [hash]);
  await conn.execute('UPDATE students SET password_hash = ? WHERE hostel_id = 1', [hash]);
  const [s1] = await conn.execute('SELECT COUNT(*) c FROM staff WHERE hostel_id = 1');
  const [s2] = await conn.execute('SELECT COUNT(*) c FROM students WHERE hostel_id = 1');
  console.log('Passwords updated. Use password123 for all users.');
  console.log('Staff:', s1[0].c, '| Students:', s2[0].c);
  await conn.end();
}
main().catch(err => { console.error('Error:', err.message); process.exit(1); });
