# YogaMart - Premium Yoga E-Commerce & Learning Platform

YogaMart is a modern, full-stack web application designed for yoga enthusiasts. It combines a premium e-commerce experience with a comprehensive learning management system (LMS) where users can browse products, enroll in courses, and watch tutorial videos.

## 🌟 Key Features

### 1. User Experience & Design
- **SPA-like Transitions:** Smooth fade-in/fade-out transitions between all internal pages for a seamless feel.
- **Asynchronous Interactions:** Login, registration, profile updates, and "Add to Cart" actions are handled via AJAX/Fetch API to prevent page reloads.
- **Responsive Design:** Fully optimized for mobile, tablet, and desktop views.
- **Dynamic Feedback:** Instant toast notifications and loading indicators for all background actions.

### 2. E-Commerce Module
- **Product Catalog:** Grid-based shop with category filtering and real-time search.
- **Advanced Cart System:** Asynchronous cart management with dynamic header count updates.
- **Product Gallery:** Multi-image support for products with an interactive thumbnail switcher.
- **Checkout & Payment:** Secure checkout process with integrated **Razorpay Payment Gateway**.
- **Order Tracking:** Detailed order history for users and a management interface for admins.

### 3. Learning Management
- **Video Library:** Categorized tutorial videos (Free vs. Premium).
- **Course System:** Structured courses with multiple video lessons and difficulty levels (Beginner, Intermediate, Advanced).
- **Tutor Ecosystem:** Dedicated registration and dashboard for tutors to upload and manage their own content.

### 4. Security & Authentication
- **Multi-Role System:** Distinct permissions for Learners, Tutors, and Admins.
- **Two-Factor Authentication (2FA):** OTP-based verification for both login and registration via email.
- **Secure Data Handling:** Prepared SQL statements to prevent SQL injection and hashed passwords (bcrypt).
- **Session Management:** Enhanced secure session handling with activity tracking and role-based redirects.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.x
- **Database:** MySQL / MariaDB
- **Frontend:** Vanilla JavaScript (ES6+), HTML5, CSS3 (Custom Variables)
- **Integrations:** Razorpay API (Payments), PHPMailer (Email/OTP)
- **Environment:** Compatible with XAMPP / WAMP or Docker environments.

---

## 📂 Project Structure

```text
/php
├── admin/                  # Admin Dashboard & Management logic
├── assets/                 # CSS, Frontend JS, and static images
├── content/                # Uploaded assets (Product images, Profile pics, Videos)
├── includes/               # Core backend logic (DB connect, Session check, functions)
├── shop/                   # E-commerce logic (Cart, Checkout, Payment verification)
├── tutor/                  # Tutor-specific dashboards and content upload
├── .env                    # Environment variables (DB credentials, SMTP, API keys)
├── home.php                # Landing page
├── login-registration.php  # Unified AJAX login/reg for learners
└── Profile.php             # User profile management
```

---

## 🚀 Installation & Setup

### 1. Prerequisites
- XAMPP or any local PHP server.
- Composer (for PHPMailer dependencies).

### 2. Database Setup
1. Create a database named `yogamart_db`.
2. Import the latest schema from `db-image/yogamart_db.sql`.
3. (Optional) Run `admin/execute_shop_fix.php` via browser to ensure all e-commerce columns are present.

### 3. Environment Configuration
Create or edit the `.env` file in the root directory:
```ini
DB_HOST=localhost
DB_NAME=yogamart_db
DB_USER=root
DB_PASS=

# SMTP Config for OTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# Razorpay Config
RAZORPAY_KEY_ID=rzp_test_xxxx
RAZORPAY_KEY_SECRET=xxxx
```

---

## 🔧 Maintenance & Debugging

- **Error Logs:** Centralized logging can be found in `includes/error.log`.
- **Database Fixes:** If new columns are missing, scripts like `admin/execute_shop_fix.php` are available to synchronize the schema.
- **Uploads:** Ensure `content/products/` and `content/users/profile_pictures/` directories have write permissions (777 on Linux).

---

## 📝 Admin Credentials
*Please check your `users_tbl` for the admin user (typically the user with `role = 'admin'`).*

---
*Documentation generated on March 9, 2026.*
