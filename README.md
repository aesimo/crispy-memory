# IdeaOne - Complete PHP Production System

## 📋 Overview

IdeaOne is a fully server-side PHP-based web platform designed for students to earn money by submitting innovative ideas. The platform follows a coin-based submission model where ideas are reviewed by moderators and managed centrally by admins.

## 🚀 Features

### User Dashboard
- Submit innovative ideas across 100+ categories
- Track idea status (pending, approved, rejected)
- View wallet balance and earnings
- Request withdrawals (minimum ₹500)
- Buy coins using Razorpay payment gateway
- Receive messages and notifications
- Manage profile settings

### Moderator Dashboard
- Review pending ideas
- Approve ideas with custom amounts
- Reject ideas with detailed reasons
- Send automated messages to users
- View review history

### Admin Dashboard
- Manage users (view, edit, delete)
- Add/remove moderators
- Manage categories
- View all ideas
- Approve/reject withdrawals
- Broadcast messages to users
- Platform settings

## 🛠️ Tech Stack

- **Frontend**: PHP embedded HTML, CSS, JavaScript
- **Backend**: Core PHP 8.0+
- **Database**: Supabase (PostgreSQL)
- **Storage**: Supabase Storage
- **Payments**: Razorpay
- **Authentication**: JWT tokens

## 📦 Project Structure

```
ideaone/
├── config/
│   └── config.php              # Configuration loader
├── classes/
│   ├── Auth.php                # Authentication & JWT handling
│   ├── Database.php            # Database connection
│   ├── FileUpload.php          # File upload handling
│   └── Payment.php             # Razorpay integration
├── auth/
│   ├── login.php               # User login
│   ├── register.php            # User registration (6 free coins)
│   └── logout.php              # Logout
├── user/
│   ├── dashboard.php           # User dashboard
│   ├── submit-idea.php         # Submit new idea
│   ├── ideas.php               # View all ideas
│   ├── wallet.php              # Wallet & transactions
│   ├── messages.php            # Inbox
│   ├── withdraw.php            # Request withdrawal
│   ├── profile.php             # Profile management
│   └── buy-coins.php           # Buy coins with Razorpay
├── moderator/
│   ├── dashboard.php           # Moderator dashboard
│   ├── review.php              # Review ideas
│   └── ideas.php               # View all ideas
├── admin/
│   ├── dashboard.php           # Admin dashboard
│   └── (more admin pages...)
├── api/
│   ├── create-order.php        # Create Razorpay order
│   └── verify-payment.php      # Verify payment
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet
│   └── js/
│       └── main.js             # Main JavaScript
├── database/
│   └── init.php                # Database initialization
├── uploads/
│   ├── ideas/                  # Idea documents
│   └── prototypes/             # Idea prototypes
├── .env                        # Environment configuration
├── index.php                   # Home page
└── README.md                   # This file
```

## 🔧 Installation

### Prerequisites

- PHP 8.0 or higher
- PostgreSQL database (Supabase)
- Razorpay account
- Composer (optional)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ideaone
   ```

2. **Configure environment variables**
   
   Copy `.env` file and update with your credentials:
   ```env
   DB_HOST=your-supabase-host.supabase.co
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASSWORD=your-database-password
   
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=your-anon-key
   SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
   
   RAZORPAY_KEY_ID=your-razorpay-key-id
   RAZORPAY_KEY_SECRET=your-razorpay-key-secret
   RAZORPAY_WEBHOOK_SECRET=your-webhook-secret
   
   JWT_SECRET=your-jwt-secret-key-change-this-in-production
   ```

3. **Initialize database**
   ```bash
   php database/init.php
   ```
   
   This will create all necessary tables and seed categories.

4. **Set up file permissions**
   ```bash
   chmod -R 755 uploads
   chmod -R 755 logs
   ```

5. **Configure web server**
   
   Point your web server to the project root directory.

   For Apache:
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /path/to/ideaone
       
       <Directory /path/to/ideaone>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

6. **Create admin user**
   
   You'll need to manually create the first admin user in the database:
   ```sql
   INSERT INTO users (name, email, mobile, dob, password_hash, coins, wallet_balance, role, created_at)
   VALUES (
       'Admin User',
       'admin@ideaone.com',
       '1234567890',
       '1990-01-01',
       '$2y$10$YourHashedPasswordHere',
       1000,
       0,
       'admin',
       NOW()
   );
   ```

## 🎯 Usage

### User Flow
1. Register account (get 6 free coins)
2. Submit idea (costs 2 coins)
3. Wait for moderator review
4. If approved, earnings credited to wallet
5. Request withdrawal when balance reaches ₹500

### Moderator Flow
1. Login to moderator dashboard
2. Review pending ideas
3. Approve (with amount) or reject (with reason)
4. User automatically notified

### Admin Flow
1. Login to admin dashboard
2. Manage users, moderators, categories
3. Approve withdrawals
4. Broadcast messages
5. Configure platform settings

## 🔐 Security Features

- JWT token-based authentication
- bcrypt password hashing
- CSRF protection
- SQL injection prevention (prepared statements)
- File upload validation
- Role-based access control
- Razorpay signature verification
- API rate limiting (recommended)

## 💰 Payment Integration

- **Razorpay** for coin purchases
- Server-side order creation
- Webhook signature verification
- Coins credited after successful payment

## 📝 Database Schema

### Tables
- `users` - User accounts
- `categories` - Idea categories
- `ideas` - Submitted ideas
- `wallet_transactions` - Transaction history
- `withdrawals` - Withdrawal requests
- `messages` - User messages
- `razorpay_orders` - Payment orders

## 🌐 API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `POST /auth/logout.php` - User logout

### Payments
- `POST /api/create-order.php` - Create Razorpay order
- `POST /api/verify-payment.php` - Verify payment

## 📧 Support

For support, contact: support@ideaone.com

## 📄 License

Copyright © 2024 IdeaOne. All rights reserved.

## 🔄 Updates

### Version 1.0.0
- Initial release
- Complete user, moderator, and admin dashboards
- Razorpay integration
- File upload system
- Message system
- Withdrawal system

---

**Note**: This is a production-ready system. Ensure you use strong passwords, enable HTTPS, and configure proper security settings before deploying to production.