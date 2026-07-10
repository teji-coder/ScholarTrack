# ScholarTrack

ScholarTrack is a Scholarship Eligibility and Application Portal developed using PHP, MySQL, HTML, CSS, and JavaScript. The system helps students discover scholarships, submit applications online, upload requirements, and monitor their application status. Administrators can manage scholarships, review applications, monitor student profiles, and generate reports through a centralized dashboard.

## Features

### Student
- User Registration and Login
- Profile Management
- Profile Picture Upload
- Scholarship Browsing
- Scholarship Application
- Document Upload
- Application Status Tracking
- Notifications
- Change Password
- Forgot Password

### Administrator
- Secure Admin Login
- Dashboard Overview
- Manage Scholarships (CRUD)
- Manage Student Accounts
- Review Scholarship Applications
- Approve, Reject, or Waitlist Applications
- Reports Generation
- Profile Management
- Change Password

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- XAMPP

## Project Structure

```
ScholarTrack/
│
├── assets/
├── profile/
├── uploads/
├── index.php
├── login.php
├── register.php
├── student_dashboard.php
├── admin_dashboard.php
├── config.php
└── scholartrack.sql
```

## Installation

1. Install XAMPP.
2. Copy the ScholarTrack folder into the `htdocs` directory.
3. Start Apache and MySQL.
4. Import `scholartrack.sql` into phpMyAdmin.
5. Open your browser and go to:

```
http://localhost/ScholarTrack/
```

## Default Accounts

### Administrator

Email:
```
admin@scholartrack.com
```

Password:
```
admin123
```

### Student

Register a new student account through the registration page.

## Developers

Developed as a course project for Applications Development.

## License

This project is intended for educational purposes only.