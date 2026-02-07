<?php
/**
 * Email Service Class
 * Handles email sending using SendGrid API
 */

class EmailService {
    private $apiKey;
    private $fromEmail;
    private $fromName;
    private $isProduction;
    
    public function __construct() {
        $this->apiKey = Config::get('SENDGRID_API_KEY');
        $this->fromEmail = Config::get('SENDGRID_FROM_EMAIL', 'noreply@ideaone.com');
        $this->fromName = Config::get('SENDGRID_FROM_NAME', 'IdeaOne');
        $this->isProduction = Config::isProduction();
    }
    
    /**
     * Send email using SendGrid
     */
    public function send($to, $subject, $content, $isHtml = true) {
        if (empty($this->apiKey)) {
            error_log("SendGrid API key not configured");
            return false;
        }
        
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $emailContent = $isHtml ? 'text/html' : 'text/plain';
        
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'content' => [
                [
                    'type' => $emailContent,
                    'value' => $content
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 202) {
            return true;
        }
        
        error_log("SendGrid API Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($to, $name, $verificationToken) {
        $appUrl = Config::get('APP_URL');
        $verificationUrl = $appUrl . '/auth/verify-email.php?token=' . $verificationToken;
        
        $subject = 'Verify Your Email - IdeaOne';
        
        $content = $this->getTemplate('email_verification', [
            'name' => $name,
            'verification_url' => $verificationUrl,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($to, $name, $coins) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Welcome to IdeaOne! 🎉';
        
        $content = $this->getTemplate('welcome', [
            'name' => $name,
            'coins' => $coins,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($to, $name, $resetToken) {
        $appUrl = Config::get('APP_URL');
        $resetUrl = $appUrl . '/auth/reset-password.php?token=' . $resetToken;
        
        $subject = 'Password Reset Request - IdeaOne';
        
        $content = $this->getTemplate('password_reset', [
            'name' => $name,
            'reset_url' => $resetUrl,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl,
            'expiry_hours' => 1
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send idea approval email
     */
    public function sendIdeaApprovalEmail($to, $name, $ideaTitle, $amount, $note = '') {
        $appUrl = Config::get('APP_URL');
        $subject = 'Congratulations! Your Idea Was Approved 💰';
        
        $content = $this->getTemplate('idea_approved', [
            'name' => $name,
            'idea_title' => $ideaTitle,
            'amount' => $amount,
            'note' => $note,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send idea rejection email
     */
    public function sendIdeaRejectionEmail($to, $name, $ideaTitle, $reason) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Your Idea Review Results';
        
        $content = $this->getTemplate('idea_rejected', [
            'name' => $name,
            'idea_title' => $ideaTitle,
            'reason' => $reason,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send withdrawal confirmation email
     */
    public function sendWithdrawalConfirmationEmail($to, $name, $amount, $finalAmount) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Withdrawal Request Submitted';
        
        $content = $this->getTemplate('withdrawal_confirmation', [
            'name' => $name,
            'amount' => $amount,
            'final_amount' => $finalAmount,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send withdrawal processed email
     */
    public function sendWithdrawalProcessedEmail($to, $name, $amount, $status) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Your Withdrawal Has Been Processed';
        
        $content = $this->getTemplate('withdrawal_processed', [
            'name' => $name,
            'amount' => $amount,
            'status' => $status,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send coin purchase confirmation email
     */
    public function sendCoinPurchaseEmail($to, $name, $coins, $amount) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Coin Purchase Successful 🪙';
        
        $content = $this->getTemplate('coin_purchase', [
            'name' => $name,
            'coins' => $coins,
            'amount' => $amount,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Send referral bonus email
     */
    public function sendReferralBonusEmail($to, $name, $bonus, $referrerName) {
        $appUrl = Config::get('APP_URL');
        $subject = 'Referral Bonus Earned! 🎁';
        
        $content = $this->getTemplate('referral_bonus', [
            'name' => $name,
            'bonus' => $bonus,
            'referrer_name' => $referrerName,
            'app_name' => 'IdeaOne',
            'app_url' => $appUrl
        ]);
        
        return $this->send($to, $subject, $content, true);
    }
    
    /**
     * Get email template
     */
    private function getTemplate($templateName, $data) {
        $templates = [
            'email_verification' => $this->getEmailVerificationTemplate(),
            'welcome' => $this->getWelcomeTemplate(),
            'password_reset' => $this->getPasswordResetTemplate(),
            'idea_approved' => $this->getIdeaApprovedTemplate(),
            'idea_rejected' => $this->getIdeaRejectedTemplate(),
            'withdrawal_confirmation' => $this->getWithdrawalConfirmationTemplate(),
            'withdrawal_processed' => $this->getWithdrawalProcessedTemplate(),
            'coin_purchase' => $this->getCoinPurchaseTemplate(),
            'referral_bonus' => $this->getReferralBonusTemplate()
        ];
        
        $template = $templates[$templateName] ?? '';
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    private function getEmailVerificationTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .button { display: inline-block; padding: 15px 30px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to IdeaOne!</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Thank you for registering with IdeaOne! We\'re excited to have you on board.</p>
            <p>To complete your registration and start earning, please verify your email address by clicking the button below:</p>
            <div style="text-align: center;">
                <a href="{verification_url}" class="button">Verify Email Address</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #6366f1;">{verification_url}</p>
            <p><strong>This link will expire in 24 hours.</strong></p>
            <p>If you didn\'t create an account with IdeaOne, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
            <p>Start turning your ideas into earnings!</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getWelcomeTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .bonus { background: #d1fae5; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to IdeaOne!</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Welcome to IdeaOne! We\'re thrilled to have you join our community of innovators.</p>
            <div class="bonus">
                <h2 style="color: #065f46; margin: 0 0 10px 0;">🎁 Your Sign-up Bonus!</h2>
                <p style="font-size: 24px; font-weight: bold; color: #065f46; margin: 0;">{coins} FREE Coins</p>
            </div>
            <p>You can use these coins to submit your ideas and start earning money!</p>
            <div style="text-align: center;">
                <a href="{app_url}/user/submit-idea.php" class="button">Submit Your First Idea</a>
            </div>
            <p>Here\'s what you can do next:</p>
            <ul>
                <li>✨ Submit innovative ideas across 100+ categories</li>
                <li>💰 Earn money when your ideas get approved</li>
                <li>🪙 Buy more coins if needed</li>
                <li>💸 Withdraw your earnings</li>
            </ul>
            <p>If you have any questions, feel free to reach out to our support team.</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
            <p>Start turning your ideas into earnings!</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getPasswordResetTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .button { display: inline-block; padding: 15px 30px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .warning { background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>We received a request to reset your password for your IdeaOne account.</p>
            <div style="text-align: center;">
                <a href="{reset_url}" class="button">Reset Your Password</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #6366f1;">{reset_url}</p>
            <div class="warning">
                <p style="margin: 0;"><strong>⚠️ Important:</strong> This link will expire in {expiry_hours} hour(s).</p>
            </div>
            <p>If you didn\'t request a password reset, please ignore this email and your password will remain unchanged.</p>
            <p>For your security, please never share your password with anyone.</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getIdeaApprovedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .amount { background: #d1fae5; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Congratulations! Your Idea Was Approved!</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Great news! Your idea has been reviewed and approved by our moderators.</p>
            <h3>Your Idea:</h3>
            <p style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">{idea_title}</p>
            <div class="amount">
                <h2 style="color: #065f46; margin: 0 0 10px 0;">💰 Approved Amount</h2>
                <p style="font-size: 36px; font-weight: bold; color: #065f46; margin: 0;">₹{amount}</p>
            </div>
            {note}
            <p>The amount has been credited to your wallet and you can withdraw it once you reach the minimum withdrawal amount of ₹500.</p>
            <div style="text-align: center;">
                <a href="{app_url}/user/wallet.php" class="button">View Your Wallet</a>
            </div>
            <p>Keep submitting great ideas and continue earning!</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getIdeaRejectedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .reason { background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Your Idea Review Results</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Thank you for submitting your idea to IdeaOne. Our moderators have carefully reviewed it.</p>
            <h3>Your Idea:</h3>
            <p style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">{idea_title}</p>
            <div class="reason">
                <h3 style="color: #991b1b; margin: 0 0 10px 0;">❌ Not Approved</h3>
                <p><strong>Reason:</strong></p>
                <p>{reason}</p>
            </div>
            <p>Please don\'t be discouraged! Use this feedback to improve your next idea submission. We encourage you to:</p>
            <ul>
                <li>✨ Refine your idea based on the feedback</li>
                <li>💡 Add more details and clarification</li>
                <li>📎 Include supporting documents or prototypes</li>
                <li>🔄 Try a different category</li>
            </ul>
            <div style="text-align: center;">
                <a href="{app_url}/user/submit-idea.php" class="button">Submit Another Idea</a>
            </div>
            <p>Keep innovating and submitting great ideas!</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getWithdrawalConfirmationTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .details { background: #e0e7ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💸 Withdrawal Request Submitted</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Your withdrawal request has been successfully submitted and is now being processed.</p>
            <div class="details">
                <h3 style="margin: 0 0 15px 0;">Withdrawal Details:</h3>
                <p><strong>Requested Amount:</strong> ₹{amount}</p>
                <p><strong>Platform Fee (2%):</strong> ₹{fee}</p>
                <p><strong>Amount You\'ll Receive:</strong> <strong>₹{final_amount}</strong></p>
            </div>
            <p><strong>Processing Time:</strong> 1-3 business days</p>
            <p>You will receive another email notification once your withdrawal has been processed.</p>
            <p>If you have any questions or concerns, please contact our support team.</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getWithdrawalProcessedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .status.approved { background: #d1fae5; }
        .status.rejected { background: #fee2e2; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💸 Your Withdrawal Has Been Processed</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Your withdrawal request has been reviewed and processed.</p>
            <div class="status {status}">
                <h3 style="margin: 0 0 10px 0;">Status: {status_text}</h3>
                <p><strong>Amount:</strong> ₹{amount}</p>
            </div>
            {message}
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getCoinPurchaseTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .coins { background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🪙 Coin Purchase Successful!</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Your coin purchase has been completed successfully!</p>
            <div class="coins">
                <h2 style="color: #92400e; margin: 0 0 10px 0;">Coins Added</h2>
                <p style="font-size: 36px; font-weight: bold; color: #92400e; margin: 0;">{coins} Coins</p>
            </div>
            <p><strong>Amount Paid:</strong> ₹{amount}</p>
            <p>These coins have been added to your wallet and are ready to use for submitting ideas!</p>
            <div style="text-align: center;">
                <a href="{app_url}/user/submit-idea.php" class="button">Submit an Idea</a>
            </div>
            <p>Thank you for choosing IdeaOne!</p>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getReferralBonusTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9fafb; }
        .bonus { background: #ede9fe; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #8b5cf6; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Referral Bonus Earned!</h1>
        </div>
        <div class="content">
            <p>Hi {name},</p>
            <p>Great news! Someone you referred has joined IdeaOne, and you\'ve earned a referral bonus!</p>
            <div class="bonus">
                <h2 style="color: #5b21b6; margin: 0 0 10px 0;">Bonus Earned</h2>
                <p style="font-size: 36px; font-weight: bold; color: #5b21b6; margin: 0;">{bonus} Coins</p>
            </div>
            <p><strong>Referred by:</strong> {referrer_name}</p>
            <p>Keep sharing your referral link to earn more bonuses!</p>
            <div style="text-align: center;">
                <a href="{app_url}/user/referral.php" class="button">Get Your Referral Link</a>
            </div>
        </div>
        <div class="footer">
            <p>© 2024 IdeaOne. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
}