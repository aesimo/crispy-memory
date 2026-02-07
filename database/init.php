<?php
/**
 * Database Initialization Script
 * Creates all necessary tables and seeds initial data
 */

require_once __DIR__ . '/../classes/Database.php';

class DatabaseInitializer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Initialize all tables
     */
    public function initialize() {
        try {
            $this->db->beginTransaction();
            
            $this->createUsersTable();
            $this->createCategoriesTable();
            $this->createIdeasTable();
            $this->createWalletTransactionsTable();
            $this->createWithdrawalsTable();
            $this->createMessagesTable();
            $this->createRazorpayOrdersTable();
            $this->createPasswordResetsTable();
            $this->createEmailVerificationsTable();
            $this->createSmsVerificationsTable();
            $this->createActivityLogsTable();
            $this->createReferralCodesTable();
            
            $this->seedCategories();
            
            $this->db->commit();
            
            echo "Database initialized successfully!\n";
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            echo "Database initialization failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create users table
     */
    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            mobile VARCHAR(20) UNIQUE NOT NULL,
            dob DATE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            coins INTEGER DEFAULT 0,
            wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
            role VARCHAR(20) DEFAULT 'user',
            email_verified BOOLEAN DEFAULT FALSE,
            sms_verified BOOLEAN DEFAULT FALSE,
            referral_code VARCHAR(20) UNIQUE,
            referred_by INTEGER REFERENCES users(id),
            total_earnings DECIMAL(10, 2) DEFAULT 0.00,
            total_coins_earned INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Users table created/verified.\n";
    }
    
    /**
     * Create categories table
     */
    private function createCategoriesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            estimated_earning VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Categories table created/verified.\n";
    }
    
    /**
     * Create ideas table
     */
    private function createIdeasTable() {
        $sql = "CREATE TABLE IF NOT EXISTS ideas (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            category_id INTEGER REFERENCES categories(id),
            title VARCHAR(255) NOT NULL,
            problem TEXT NOT NULL,
            solution TEXT NOT NULL,
            file_path VARCHAR(500),
            prototype_path VARCHAR(500),
            status VARCHAR(20) DEFAULT 'pending',
            approved_amount DECIMAL(10, 2),
            moderator_note TEXT,
            rejection_reason TEXT,
            share_count INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Ideas table created/verified.\n";
    }
    
    /**
     * Create wallet_transactions table
     */
    private function createWalletTransactionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            type VARCHAR(50) NOT NULL,
            amount DECIMAL(10, 2) DEFAULT 0.00,
            coins INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            payment_id VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Wallet transactions table created/verified.\n";
    }
    
    /**
     * Create withdrawals table
     */
    private function createWithdrawalsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            amount DECIMAL(10, 2) NOT NULL,
            fee DECIMAL(10, 2) NOT NULL,
            final_amount DECIMAL(10, 2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            admin_note TEXT,
            processed_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Withdrawals table created/verified.\n";
    }
    
    /**
     * Create messages table
     */
    private function createMessagesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id SERIAL PRIMARY KEY,
            sender_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            receiver_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            subject VARCHAR(255),
            content TEXT NOT NULL,
            read_status BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Messages table created/verified.\n";
    }
    
    /**
     * Create razorpay_orders table
     */
    private function createRazorpayOrdersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS razorpay_orders (
            id SERIAL PRIMARY KEY,
            order_id VARCHAR(255) UNIQUE NOT NULL,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            amount DECIMAL(10, 2) NOT NULL,
            coins INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'created',
            payment_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Razorpay orders table created/verified.\n";
    }
    
    /**
     * Create password_resets table
     */
    private function createPasswordResetsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS password_resets (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            token VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255),
            mobile VARCHAR(20),
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Password resets table created/verified.\n";
    }
    
    /**
     * Create email_verifications table
     */
    private function createEmailVerificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_verifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            token VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Email verifications table created/verified.\n";
    }
    
    /**
     * Create sms_verifications table
     */
    private function createSmsVerificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS sms_verifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            mobile VARCHAR(20) NOT NULL,
            code VARCHAR(10) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            verified BOOLEAN DEFAULT FALSE,
            attempts INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "SMS verifications table created/verified.\n";
    }
    
    /**
     * Create activity_logs table
     */
    private function createActivityLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            action VARCHAR(100) NOT NULL,
            details JSONB,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Activity logs table created/verified.\n";
    }
    
    /**
     * Create referral_codes table
     */
    private function createReferralCodesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS referral_codes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            referral_code VARCHAR(20) UNIQUE NOT NULL,
            total_referrals INTEGER DEFAULT 0,
            total_earned DECIMAL(10, 2) DEFAULT 0.00,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        echo "Referral codes table created/verified.\n";
    }
    
    /**
     * Seed categories
     */
    private function seedCategories() {
        // Check if categories already exist
        $existingCategories = $this->db->fetchOne("SELECT COUNT(*) as count FROM categories");
        
        if ($existingCategories['count'] > 0) {
            echo "Categories already seeded. Skipping...\n";
            return;
        }
        
        $categories = [
            ['name' => 'Mobile Apps', 'estimated_earning' => '₹500 - ₹5,000', 'description' => 'Innovative mobile application ideas'],
            ['name' => 'Web Applications', 'estimated_earning' => '₹1,000 - ₹10,000', 'description' => 'Web-based solutions and platforms'],
            ['name' => 'IoT Solutions', 'estimated_earning' => '₹2,000 - ₹15,000', 'description' => 'Internet of Things ideas'],
            ['name' => 'AI & Machine Learning', 'estimated_earning' => '₹3,000 - ₹20,000', 'description' => 'AI/ML based solutions'],
            ['name' => 'Blockchain', 'estimated_earning' => '₹5,000 - ₹25,000', 'description' => 'Blockchain and cryptocurrency ideas'],
            ['name' => 'E-commerce', 'estimated_earning' => '₹1,000 - ₹8,000', 'description' => 'Online business and shopping ideas'],
            ['name' => 'Education', 'estimated_earning' => '₹500 - ₹5,000', 'description' => 'Educational platforms and tools'],
            ['name' => 'Healthcare', 'estimated_earning' => '₹2,000 - ₹12,000', 'description' => 'Health and wellness solutions'],
            ['name' => 'Agriculture', 'estimated_earning' => '₹1,000 - ₹7,000', 'description' => 'Farming and agricultural innovations'],
            ['name' => 'Entertainment', 'estimated_earning' => '₹500 - ₹4,000', 'description' => 'Gaming, media and entertainment ideas'],
            ['name' => 'Finance', 'estimated_earning' => '₹2,000 - ₹15,000', 'description' => 'Financial services and fintech'],
            ['name' => 'Transportation', 'estimated_earning' => '₹1,000 - ₹8,000', 'description' => 'Transport and logistics solutions'],
            ['name' => 'Environment', 'estimated_earning' => '₹1,500 - ₹10,000', 'description' => 'Sustainability and eco-friendly ideas'],
            ['name' => 'Social Impact', 'estimated_earning' => '₹500 - ₹5,000', 'description' => 'Community and social welfare ideas'],
            ['name' => 'Security', 'estimated_earning' => '₹2,000 - ₹12,000', 'description' => 'Cybersecurity and safety solutions'],
            ['name' => 'Smart City', 'estimated_earning' => '₹3,000 - ₹15,000', 'description' => 'Urban development and smart solutions'],
            ['name' => 'Robotics', 'estimated_earning' => '₹5,000 - ₹25,000', 'description' => 'Robotics and automation ideas'],
            ['name' => 'VR/AR', 'estimated_earning' => '₹2,000 - ₹15,000', 'description' => 'Virtual and augmented reality solutions'],
            ['name' => 'Food & Beverage', 'estimated_earning' => '₹500 - ₹4,000', 'description' => 'Food industry innovations'],
            ['name' => 'Fashion', 'estimated_earning' => '₹1,000 - ₹6,000', 'description' => 'Fashion and lifestyle ideas']
        ];
        
        foreach ($categories as $category) {
            $this->db->execute(
                "INSERT INTO categories (name, estimated_earning, description) VALUES (?, ?, ?)",
                [$category['name'], $category['estimated_earning'], $category['description']]
            );
        }
        
        echo "Categories seeded successfully.\n";
    }
}

// Run initialization if executed directly
if (php_sapi_name() === 'cli') {
    $initializer = new DatabaseInitializer();
    $initializer->initialize();
}