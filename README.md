👟 E-commerce Shoes

E-commerce Shoes is a lightweight yet full-featured online shoe store built with plain PHP (PDO), MySQL, and Tailwind CSS.
It includes a public storefront and a powerful admin panel for managing products, orders, payments, and analytics.

This project is ideal for:

PHP learners who want a real-world e-commerce example

Small internal shops

University / internship projects

Admin dashboard & analytics practice

✨ Features Overview
🛍 Storefront

Product listing with categories

Featured products

Stock-aware product display

Clean, responsive UI using Tailwind CSS

🔐 Authentication & Roles

Secure admin login (session-based)

Role checks (admin, user)

CSRF protection

Session regeneration to prevent fixation

📦 Product & Inventory Management

Create, edit, delete products

Category management

Featured items system

Low-stock alerts

Automatic inventory updates after orders

🧾 Orders & Payments

Full order lifecycle management

Order status tracking (pending, paid, shipped, completed)

Payment recording per order

Multiple payment gateways support

Transaction logs for auditing

💳 Payment Gateways

Multiple gateways (configurable via DB)

Gateway-based transaction filtering

Accurate revenue calculation using payments table

Gateway enable/disable from admin UI

📊 Analytics Dashboard

Revenue tracking (daily / monthly)

Orders over time

Top products & customers

Orders by status

Payment methods usage

Hourly order distribution

Date range & custom date filters

Chart.js visualizations

🧰 Tech Stack
Layer	Technology
Backend	PHP 8+ (PDO)
Database	MySQL / MariaDB
Frontend	Tailwind CSS
Charts	Chart.js
Local Dev	Laragon (Windows)
Optional Auth	Google OAuth

📁 Project Structure

E-commerce-shoes/
│
├── admin/
│   ├── auth/
│   ├── process/
│   │   ├── analytics/
│   │   ├── orders/
│   │   ├── products/
│   │   └── notifications/
│   └── layout/
│
├── config/
│   ├── conn.php
│   ├── google.php
│   └── google.local.php
│
├── database/
│   └── table.sql
│
├── public/
│   ├── assets/
│   ├── css/
│   └── js/
│
├── helpers/
│   ├── auth.php
│   ├── csrf.php
│   └── response.php
│
├── index.php
└── README.md

🔧 Configuration Guide
📌 Database

Connection: config/conn.php

Uses PDO with prepared statements

Strict error handling recommended

🔑 Google OAuth (Optional)

Config file:

config/google.php (production)

config/google.local.php (local)

Requires Google Developer Console setup

💳 Payment Methods

Stored in payment_methods table

Managed via Admin UI

Fields include:

method_code

method_name

is_active

🔗 Important Endpoints
📊 Analytics
Endpoint	Description
admin/process/analytics/analytics.php	Dashboard UI
admin/process/analytics/analyties_api.php	JSON API
Example:
analyties_api.php?gateway=paypal&limit=200


Returns transaction data for a specific payment gateway.

📈 Analytics Logic (Important)

Revenue is calculated from payments table

Ensures only completed payments are counted

Orders are analyzed separately from payments

Supports:

Preset ranges (today, 7 days, 30 days)

Custom date ranges

Charts auto-update based on filters

🔐 Security Practices

Prepared SQL statements (PDO)

CSRF tokens for all POST requests

Session regeneration on login

HttpOnly cookies

Role-based access control

Input validation & sanitization

🧪 Development & Testing

Seed database using database/table.sql

Add sample:

Products

Orders

Payments

Use analytics dashboard to verify charts

Enable PHP error reporting during development

🌍 Deployment Notes

Set secure => true for cookies on HTTPS

Disable error display in production

Use .env-style config if deploying publicly

Backup database regularly

🤝 Contributing

Fork the repository

Create a feature branch

Commit clean, focused changes

Open a Pull Request

Please keep code:

PSR-12 compliant

Secure

Well-commented

📄 License

Add a LICENSE file
MIT License is recommended for open usage.

🧠 Future Improvements (Ideas)

REST API for mobile apps

Webhooks for payment gateways

Email notifications

Product reviews & ratings

Admin role permissions

Docker setup