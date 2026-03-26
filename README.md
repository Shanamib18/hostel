# LBSCEK Hostel Management System

Hostel management system for **LBS College of Engineering, Kasaragod** — Ladies' Hostel (LH LBSCEK). Supports 278 students, 8 staff, and 141 rooms with mess attendance, entry/exit monitoring, and fee payment processing. Uses face recognition for secure attendance and entry/exit recording.

## Tech Stack

- **Database**: MySQL
- **Backend API**: Node.js, Express.js
- **Face Recognition**: Python (Flask, face_recognition)
- **Admin Dashboard**: PHP
- **Frontend**: HTML, CSS, JavaScript

## Features

- **Mess attendance** — Face-based or manual meal tracking (breakfast/lunch/dinner)
- **Entry/exit logs** — Digital monitoring with face verification
- **Fee payments** — View fee structure, payment history, pay online
- **Staff dashboard** — Room occupancy, attendance stats, payment status
- **Student portal** — View attendance records, make payments

## Project Structure

```
hostel/
├── backend/          # Node.js + Express API
│   ├── config/
│   ├── middleware/
│   ├── routes/
│   └── server.js
├── face-service/     # Python face recognition
│   ├── app.py
│   └── requirements.txt
├── php/              # Staff dashboard (PHP)
│   ├── config/
│   ├── assets/
│   └── *.php
├── frontend/         # HTML/JS student portal
├── database/
│   ├── schema.sql
│   └── seed.sql
└── README.md
```

## Setup

### 1. Database (MySQL)

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
cd backend && npm install && node setup-users.js   # Set password: password123
```

### 2. Node.js API

```bash
cd backend
cp .env.example .env
# Edit .env with DB credentials and JWT_SECRET
npm install
npm start
```

API runs at `http://localhost:3000`

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
