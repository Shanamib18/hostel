# LBSCEK Hostel Management System

Comprehensive hostel management system for **LBS College of Engineering, Kasaragod** — Ladies' Hostel (LH LBSCEK). Manages 278+ students across 141 rooms with integrated mess management, entry/exit monitoring, fee processing, and face recognition technology.

## Key Features

- **Face Recognition Attendance** — Secure meal tracking (breakfast/lunch/dinner) with face verification
- **Entry/Exit Monitoring** — Digital door access logs with face-based verification
- **Comprehensive Billing System** — Monthly bills, hostel fees, establishment fees, mess fees, and fine tracking
- **Mess Management** — Purchase tracking, expense categorization, mess cut requests, and secretary workflows
- **Fee Payment Processing** — Online payment integration with transaction tracking and payment status monitoring
- **Room Management** — 141 rooms across 3 categories (G, F, S) with occupancy tracking and status management
- **Multi-role Access** — Admin, Warden, Mess Manager, Security, Mess Secretary, and Student roles
- **Staff Dashboard** — Comprehensive statistics on rooms, attendance, payments, and operations
- **Student Portal** — View personal attendance, billing, payment history, and manage account settings

## Tech Stack

- **Database**: MySQL (MariaDB 10.4+)
- **Backend API**: Node.js with Express.js
- **Face Recognition**: Python (Flask, face_recognition library)
- **Admin/Staff Dashboard**: PHP with secure session management
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)

## Project Structure

```
hostel/
├── backend/              # Node.js + Express API
│   ├── config/
│   │   └── db.js         # MySQL connection pool
│   ├── middleware/
│   │   └── auth.js       # JWT authentication
│   ├── routes/
│   │   ├── attendance.js # Mess attendance endpoints
│   │   ├── auth.js       # Login/logout endpoints
│   │   ├── payments.js   # Payment processing
│   │   ├── rooms.js      # Room management
│   │   └── students.js   # Student management
│   ├── uploads/faces/    # Stored face encodings
│   ├── package.json
│   ├── server.js
│   └── setup-users.js    # Initial user setup
├── face-service/         # Python face recognition service
│   ├── app.py            # Flask API for face encoding/matching
│   └── requirements.txt
├── php/                  # Staff/Admin dashboard
│  Installation & Setup

### Prerequisites
- MySQL 5.7+ (MariaDB 10.4+)
- Node.js 14+
- Python 3.8+
- PHP 7.4+
- XAMPP, WAMP, or similar local server stack

### 1. Database Setup (MySQL/MariaDB)

```bash
# Create database and load schema
mysql -u root -p hostel_lbscek < database/schema.sql

# Load initial data (staff, hostels, fee structure)
mysql -u root -p hostel_lbscek < database/seed.sql

# Load all 100+ actual students from CSV
mysql -u root -p hostel_lbscek < database/seed_all_students.sql

# Optionally load room and fee data
mysql -u root -p hostel_lbscek < database/rooms.sql
```

### 2. Node.js Backend API

```bash
cd backend
npm install

# Create .env file with configuration
cat > .env << EOF
PORT=3000
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=hostel_lbscek
JWT_SECRET=your_secret_key_here
EOF

# Set up admin user
node setup-users.js

# Start the server
npm start
```

API will be available at `http://localhost:3000`

### 3. Python Face Recognition Service

```bash
cd face-service

# Create virtual environment (Windows)
python -m venv venv
venv\Scripts\activate

# Or on macOS/Linux
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Start the service
python app.py
```

Face recognition service will run at `http://localhost:5000`

### 4. PHP Dashboard (Staff/Admin/Mess Secretary)

Using XAMPP:
```bash
# Copy project to htdocs
cp -r hostel C:\xampp\htdocs\

# Start Apache and MySQL from XAMPP Control Panel
# ADatabase Schema Overview

### Core Tables
- **hostels** — Hostel information (name, capacity, type)
- **staff** — Staff members with roles (admin, warden, mess_manager, security, caretaker)
- **students** — Student records with contact, parent info, academic details
- **rooms** — 141 rooms organized by category (G/F/S) with occupancy tracking
- **face_encodings** — Secure storage of face recognition data for students and staff

### Attendance & Access
- **mess_attendance** — Meal tracking with timestamp, meal type, verification method
- **entry_exit_logs** — Hostel entry/exit records with face verification

### Billing & Payments
- **monthly_bills** — Student billing with hostel fee, establishment fee, mess fee, fine tracking
- **fee_structure** — Configurable fee amounts and periods
- **mess_cut_requests** — Student requests for mess exemptions with approval workflow

### Operations
- **mess_purchases** — Expense tracking for provisions, vegetables, meat, fuel, etc.
- **mess_secretaries** — Mess staff members for secondary access management

## API Endpoints (Node.js Backend)

### Authentication
- `POST /api/auth/login` — User login (staff, student, mess secretary)
- `POST /api/auth/logout` — User logout

### Students
- `GET /api/students` — List all students
- `POST /api/students` — Add new student
- `GET /api/students/:id` — Get student details
- `PUT /api/students/:id` — Update student

### Attendance  
- `POST /api/attendance/mark` — Mark meal attendance with face/manual
- `GET /api/attendance/:student_id` — Get attendance records
- `GET /api/attendance/stats/:period` — Attendance statistics

### Payments
- `GET /api/payments/bills/:student_id` — Student bills
- `POST /api/payments/process` — Process payment
- `GET /api/payments/history` — Payment history

### Rooms
- `GET /api/rooms` — List all rooms
- `PUT /api/rooms/:id/assign` — Assign student to room
- `GET /api/rooms/occupancy` — Room occupancy status

## Security Features

- **Password Hashing** — bcrypt with salt (cost factor 10)
- **Session Management** — PHP sessions for staff/admin, JWT tokens for API
- **Face Recognition** — Secure encoding storage, verification-based access
- **Role-Based Access Control** — Different dashboards for admin, warden, mess manager, staff, student
- **Input Validation** — Server-side validation for all user inputs
- **Database** — Foreign key constraints, data integrity checks

## Troubleshooting

### Common Issues

**"Table 'hostel_lbscek.staff' doesn't exist"**
- Solution: Run `mysql -u root -p hostel_lbscek < database/schema.sql` to create all tables

**Database connection errors**
- Check MySQL is running: `mysql -u root -p -e "SELECT 1"`
- Verify credentials in `.env` and `php/config/db.php`
- Ensure database `hostel_lbscek` exists

**Face recognition service not responding**
- Verify Python service is running on port 5000
- Check dependencies: `pip install -r face-service/requirements.txt`
- Ensure face encodings are uploaded to backend/uploads/faces/

**Login page shows blank or 404**
- Check XAMPP Apache is running
- Verify hostel project is in htdocs: `C:\xampp\htdocs\hostel\`
- Try accessing directly: `http://localhost/hostel/php/login.php`

## Production Deployment Checklist

- [ ] Change all default passwords
- [ ] Update `JWT_SECRET` to a strong random value
- [ ] Enable HTTPS/SSL certificates
- [ ] Configure database backups
- [ ] Set up proper error logging
- [ ] Configure mail service for password resets
- [ ] Review and update fee structure
- [ ] Test all payment gateway integrations
- [ ] Set up monitoring for face recognition service
- [ ] Document admin procedures for student management
php -S localhost:8000
# Access at http://localhost:8000/login.php
```

### 5. Student Frontend Portal

```bash
# Option 1: Using VS Code Live Server extension
# Right-click frontend/index.html → Open with Live Server

# Option 2: Using Python's built-in server
cd frontend
python -m http.server 8001

# Option 3: Any static file server
# Just ensure frontend/app.js API_BASE_URL points to http://localhost:3000
```

Access at `http://localhost:8001` (or configured port)

### Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@lbscek.ac.in` | `password123` |
| Warden | `warden@lbscek.ac.in` | `password123` |
| Mess Manager | `mess@lbscek.ac.in` | `password123` |
| Student (100+ available) | See `seed_all_students.sql` | `password123` |

**Note**: All seed passwords use bcrypt hash of `password123`. Change these credentials in production!

### 3. Python Face Service

```bash
cd face-service
python -m venv venv
venv\Scripts\activate   # Windows
pip install -r requirements.txt
python app.py
```

Face service runs at `http://localhost:5000`

### 4. PHP Dashboard

Configure PHP with MySQL (XAMPP, WAMP, or built-in server):

```bash
cd php
php -S localhost:8000
```

### 5. Frontend

Serve the `frontend` folder (e.g. with VS Code Live Server or any static server). Or open `frontend/index.html` and ensure the API URL in `app.js` points to your backend.

**Default credentials (from seed):**
- Staff: `admin@lbscek.ac.in` / `password123`
- Student: `student1@lbscek.ac.in` / `password123` (or student2, student3, etc.)

## Environment Variables

**Backend (.env):**
- `PORT` — API port (default 3000)
- `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` — MySQL connection
- `JWT_SECRET` — Secret for JWT tokens
- `FACE_SERVICE_URL` — Face recognition service URL (default http://localhost:5000)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/student/login | Student login |
| POST | /api/auth/staff/login | Staff login |
| GET | /api/attendance/mess | Get mess attendance (student) |
| POST | /api/attendance/mess | Mark mess attendance (staff) |
| GET | /api/attendance/entry-exit | Get entry/exit logs |
| POST | /api/attendance/entry-exit | Record entry/exit (staff) |
| GET | /api/attendance/dashboard | Dashboard stats (staff) |
| GET | /api/payments/fee-structure | Fee structure |
| GET | /api/payments/my-payments | Payment history |
| POST | /api/payments/pay | Create payment |

All protected routes require `Authorization: Bearer <token>` header.
