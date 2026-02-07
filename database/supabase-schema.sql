-- Supabase Database Schema for IdeaOne
-- This file contains all tables, indexes, and RLS policies

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable Row Level Security
ALTER DATABASE postgres SET "app.settings.jwt_secret" TO 'your-jwt-secret';

-- ============================================================================
-- USERS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user' CHECK (role IN ('user', 'moderator', 'admin')),
    coins INTEGER DEFAULT 0,
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    is_verified BOOLEAN DEFAULT false,
    referral_code VARCHAR(50) UNIQUE,
    referred_by UUID REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_referral_code ON users(referral_code);

-- Enable RLS on users
ALTER TABLE users ENABLE ROW LEVEL SECURITY;

-- RLS Policies for users
CREATE POLICY "Users can view own profile" ON users
    FOR SELECT USING (auth.uid()::text = id::text);

CREATE POLICY "Moderators can view all users" ON users
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() AND role IN ('moderator', 'admin')
        )
    );

CREATE POLICY "Users can update own profile" ON users
    FOR UPDATE USING (auth.uid()::text = id::text);

CREATE POLICY "Admins can update any user" ON users
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- ============================================================================
-- CATEGORIES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS categories (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    estimated_earning VARCHAR(100),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_categories_name ON categories(name);
CREATE INDEX idx_categories_active ON categories(is_active);

-- Enable RLS on categories
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;

-- Public can view active categories
CREATE POLICY "Public can view active categories" ON categories
    FOR SELECT USING (is_active = true);

-- ============================================================================
-- IDEAS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS ideas (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id UUID REFERENCES categories(id),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    solution TEXT,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    estimated_earning DECIMAL(10, 2),
    moderator_id UUID REFERENCES users(id),
    moderation_notes TEXT,
    file_url VARCHAR(500),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_ideas_user_id ON ideas(user_id);
CREATE INDEX idx_ideas_category_id ON ideas(category_id);
CREATE INDEX idx_ideas_status ON ideas(status);
CREATE INDEX idx_ideas_created_at ON ideas(created_at);

-- Enable RLS on ideas
ALTER TABLE ideas ENABLE ROW LEVEL SECURITY;

-- Users can view own ideas
CREATE POLICY "Users can view own ideas" ON ideas
    FOR SELECT USING (user_id = auth.uid());

-- Moderators can view all ideas
CREATE POLICY "Moderators can view all ideas" ON ideas
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() AND role IN ('moderator', 'admin')
        )
    );

-- Users can create own ideas
CREATE POLICY "Users can create ideas" ON ideas
    FOR INSERT WITH CHECK (user_id = auth.uid());

-- ============================================================================
-- RAZORPAY ORDERS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS razorpay_orders (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_id VARCHAR(255) UNIQUE NOT NULL,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    coins INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'created' CHECK (status IN ('created', 'completed', 'failed', 'cancelled')),
    payment_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_razorpay_orders_user_id ON razorpay_orders(user_id);
CREATE INDEX idx_razorpay_orders_order_id ON razorpay_orders(order_id);
CREATE INDEX idx_razorpay_orders_status ON razorpay_orders(status);

-- Enable RLS on razorpay_orders
ALTER TABLE razorpay_orders ENABLE ROW LEVEL SECURITY;

-- Users can view own orders
CREATE POLICY "Users can view own orders" ON razorpay_orders
    FOR SELECT USING (user_id = auth.uid());

-- ============================================================================
-- WALLET TRANSACTIONS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('purchase', 'withdrawal', 'earning', 'refund', 'bonus', 'referral')),
    amount DECIMAL(10, 2) NOT NULL,
    coins INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'cancelled')),
    payment_id VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_wallet_transactions_user_id ON wallet_transactions(user_id);
CREATE INDEX idx_wallet_transactions_type ON wallet_transactions(type);
CREATE INDEX idx_wallet_transactions_status ON wallet_transactions(status);
CREATE INDEX idx_wallet_transactions_created_at ON wallet_transactions(created_at);

-- Enable RLS on wallet_transactions
ALTER TABLE wallet_transactions ENABLE ROW LEVEL SECURITY;

-- Users can view own transactions
CREATE POLICY "Users can view own transactions" ON wallet_transactions
    FOR SELECT USING (user_id = auth.uid());

-- ============================================================================
-- WITHDRAWALS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS withdrawals (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    fee DECIMAL(10, 2) DEFAULT 0.00,
    final_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'processed', 'cancelled')),
    payment_method VARCHAR(100),
    payment_details JSONB,
    admin_notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_withdrawals_user_id ON withdrawals(user_id);
CREATE INDEX idx_withdrawals_status ON withdrawals(status);
CREATE INDEX idx_withdrawals_created_at ON withdrawals(created_at);

-- Enable RLS on withdrawals
ALTER TABLE withdrawals ENABLE ROW LEVEL SECURITY;

-- Users can view own withdrawals
CREATE POLICY "Users can view own withdrawals" ON withdrawals
    FOR SELECT USING (user_id = auth.uid());

-- Moderators can view all withdrawals
CREATE POLICY "Moderators can view all withdrawals" ON withdrawals
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() AND role IN ('moderator', 'admin')
        )
    );

-- ============================================================================
-- MESSAGES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS messages (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    sender_id UUID REFERENCES users(id) ON DELETE SET NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_messages_user_id ON messages(user_id);
CREATE INDEX idx_messages_is_read ON messages(is_read);

-- Enable RLS on messages
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

-- Users can view own messages
CREATE POLICY "Users can view own messages" ON messages
    FOR SELECT USING (user_id = auth.uid());

-- ============================================================================
-- ACTIVITY LOG TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    details JSONB,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- ============================================================================
-- FUNCTIONS AND TRIGGERS
-- ============================================================================

-- Update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply trigger to tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_ideas_updated_at BEFORE UPDATE ON ideas
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_razorpay_orders_updated_at BEFORE UPDATE ON razorpay_orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_withdrawals_updated_at BEFORE UPDATE ON withdrawals
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- STORAGE BUCKETS (Create these in Supabase Dashboard)
-- ============================================================================

-- Idea attachments bucket
-- CREATE BUCKET idea_uploads WITH (public = false);

-- User avatars bucket
-- CREATE BUCKET avatars WITH (public = true);

-- ============================================================================
-- SAMPLE DATA
-- ============================================================================

-- Insert default categories
INSERT INTO categories (name, description, estimated_earning) VALUES
('Mobile Apps', 'Innovative mobile application ideas', '₹500 - ₹5,000'),
('Web Applications', 'Web-based solutions and platforms', '₹500 - ₹5,000'),
('AI & ML', 'Artificial Intelligence and Machine Learning solutions', '₹3,000 - ₹20,000'),
('Blockchain', 'Blockchain and cryptocurrency innovations', '₹5,000 - ₹25,000'),
('IoT Solutions', 'Internet of Things and smart devices', '₹2,000 - ₹15,000'),
('E-commerce', 'Online shopping and marketplace ideas', '₹500 - ₹10,000'),
('Social Media', 'Social networking and communication platforms', '₹1,000 - ₹15,000'),
('Healthcare', 'Medical and healthcare technology solutions', '₹2,000 - ₹20,000'),
('Education', 'E-learning and educational technology', '₹500 - ₹10,000'),
('Gaming', 'Game development and gaming concepts', '₹1,000 - ₹15,000')
ON CONFLICT DO NOTHING;

-- Create admin user (password: admin123 - change this!)
-- This should be done through the application, not hardcoded
-- INSERT INTO users (email, password_hash, name, role, coins, wallet_balance, is_verified) VALUES
-- ('admin@ideaone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin', 1000, 10000.00, true);