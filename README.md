# Payroll Management System

A comprehensive **web-based Payroll Management System** built using **PHP, MySQL/MariaDB, and Bootstrap**. This system streamlines employee management, attendance tracking, payroll processing, and payslip generation for organizations of all sizes.

---

## 🔧 Features

### 🔐 User Authentication
- Secure login system with **role-based access control**
- Separate roles for **Admin** and **Employees**

### 👤 Employee Management
- Add, edit, delete employee records
- Store complete employee details including salary structure

### 🕒 Attendance Management
- Record daily attendance: Present, Absent, Half-day, Leave
- Track **Time-in/Time-out**
- View attendance reports by employee or date range

### 💰 Payroll Processing
- Salary calculation based on attendance & defined structure
- Supports earnings: **Basic, HRA, DA, TA, Other Allowances**
- Deductions: **PF, Tax, Other Deductions**
- Monthly payroll generation for all employees

### 📄 Payslip Generation
- View/print detailed payslips with breakdown
- Access historical payslips

### 📊 Dashboard & Reports
- Admin dashboard with key payroll metrics
- Monthly summaries and employee-specific views

---

## 🛠️ Technology Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5  
- **Backend**: PHP  
- **Database**: MySQL/MariaDB  
- **Server**: Apache (XAMPP/WAMP/LAMP)

---

## 📦 Installation Guide

### 📋 Prerequisites
- XAMPP/WAMP/LAMP with **PHP 7.4+** and **MySQL/MariaDB**
- Web browser (Chrome, Firefox, etc.)

### 🧱 Database Setup
1. Create a new database named: `payroll_system`
2. Import `database/payroll_db.sql` using phpMyAdmin or CLI

### ⚙️ Application Setup
1. Clone or download this repository to your web server root (`htdocs/` for XAMPP)
2. Configure DB connection in:  
   `config/database.php`

```php
private $host = "localhost";
private $db_name = "payroll_system";
private $username = "root";
private $password = "";
private $port = "3307"; // Use your MySQL port if different
```
## Project Structure
payroll_system/
├── assets/             → CSS/JS Files
├── config/             → DB Configuration
├── includes/           → Header & Footer
├── employees/          → CRUD Operations
├── attendance/         → Attendance Management
├── payroll/            → Payroll Calculation & Payslip
├── admin/              → Admin Dashboard
├── auth/               → Login/Register/Logout
├── database/           → SQL File
└── index.php           → Landing Page
