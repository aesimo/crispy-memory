<?php
/**
 * SMS Service Class
 * Handles SMS sending using Twilio API
 */

class SmsService {
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $isProduction;
    
    public function __construct() {
        $this->accountSid = Config::get('TWILIO_ACCOUNT_SID');
        $this->authToken = Config::get('TWILIO_AUTH_TOKEN');
        $this->fromNumber = Config::get('TWILIO_PHONE_NUMBER');
        $this->isProduction = Config::isProduction();
    }
    
    /**
     * Send SMS using Twilio
     */
    public function send($to, $message) {
        if (empty($this->accountSid) || empty($this->authToken)) {
            error_log("Twilio credentials not configured");
            return false;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        
        $data = [
            'From' => $this->fromNumber,
            'To' => $to,
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            return true;
        }
        
        error_log("Twilio API Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Send verification code SMS
     */
    public function sendVerificationSms($to, $name, $verificationCode) {
        $message = $this->getSmsTemplate('verification', [
            'name' => $name,
            'code' => $verificationCode,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send OTP for login
     */
    public function sendLoginOtp($to, $otp) {
        $message = $this->getSmsTemplate('login_otp', [
            'otp' => $otp,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send password reset SMS
     */
    public function sendPasswordResetSms($to, $resetToken) {
        $message = $this->getSmsTemplate('password_reset', [
            'token' => $resetToken,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send idea approval SMS
     */
    public function sendIdeaApprovalSms($to, $ideaTitle, $amount) {
        $message = $this->getSmsTemplate('idea_approved', [
            'idea_title' => $ideaTitle,
            'amount' => $amount,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send withdrawal confirmation SMS
     */
    public function sendWithdrawalConfirmationSms($to, $amount, $finalAmount) {
        $message = $this->getSmsTemplate('withdrawal_confirmation', [
            'amount' => $amount,
            'final_amount' => $finalAmount,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send withdrawal processed SMS
     */
    public function sendWithdrawalProcessedSms($to, $amount, $status) {
        $message = $this->getSmsTemplate('withdrawal_processed', [
            'amount' => $amount,
            'status' => $status,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send coin purchase SMS
     */
    public function sendCoinPurchaseSms($to, $coins) {
        $message = $this->getSmsTemplate('coin_purchase', [
            'coins' => $coins,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Send referral bonus SMS
     */
    public function sendReferralBonusSms($to, $bonus) {
        $message = $this->getSmsTemplate('referral_bonus', [
            'bonus' => $bonus,
            'app_name' => 'IdeaOne'
        ]);
        
        return $this->send($to, $message);
    }
    
    /**
     * Get SMS template
     */
    private function getSmsTemplate($templateName, $data) {
        $templates = [
            'verification' => 'Hi {name}, Your {app_name} verification code is: {code}. Valid for 10 minutes. Do not share this code.',
            'login_otp' => 'Your {app_name} login OTP is: {otp}. Valid for 5 minutes. Do not share with anyone.',
            'password_reset' => 'Your {app_name} password reset code is: {token}. Valid for 1 hour. Do not share this code.',
            'idea_approved' => 'Great news! Your idea "{idea_title}" has been approved. Amount: ₹{amount}. Credited to wallet.',
            'withdrawal_confirmation' => 'Withdrawal of ₹{amount} submitted. You will receive ₹{final_amount} after processing. {app_name}',
            'withdrawal_processed' => 'Your withdrawal of ₹{amount} has been {status}. {app_name}',
            'coin_purchase' => 'Coin purchase successful! {coins} coins added to your wallet. {app_name}',
            'referral_bonus' => '🎁 Referral bonus! You earned {bonus} coins. {app_name}'
        ];
        
        $template = $templates[$templateName] ?? '';
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
}