<?php
/**
 * Supabase Client Class
 * Handles Supabase PostgreSQL connection and operations with RLS
 */

require_once __DIR__ . '/Database.php';

class SupabaseClient {
    private $db;
    private $supabaseUrl;
    private $supabaseKey;
    private $serviceRoleKey;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->supabaseUrl = Config::get('SUPABASE_URL');
        $this->supabaseKey = Config::get('SUPABASE_ANON_KEY');
        $this->serviceRoleKey = Config::get('SUPABASE_SERVICE_ROLE_KEY');
    }
    
    /**
     * Execute query with Supabase RLS context
     * Sets the user context for Row Level Security
     */
    public function queryWithRLS($sql, $params = [], $userId = null) {
        try {
            // Set RLS user context if userId is provided
            if ($userId) {
                $this->db->execute("SET LOCAL request.jwt.claim.sub = ?", [$userId]);
            }
            
            // Execute the query
            $stmt = $this->db->query($sql, $params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Supabase Query Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Upload file to Supabase Storage
     */
    public function uploadFile($bucket, $filePath, $fileContent, $contentType) {
        if (!$this->supabaseUrl || !$this->serviceRoleKey) {
            throw new Exception('Supabase storage is not configured');
        }
        
        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $contentType,
            'Authorization: Bearer ' . $this->serviceRoleKey,
            'Content-Length: ' . strlen($fileContent)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        throw new Exception('Failed to upload file to Supabase Storage');
    }
    
    /**
     * Get public URL for a file
     */
    public function getPublicUrl($bucket, $filePath) {
        if (!$this->supabaseUrl) {
            throw new Exception('Supabase is not configured');
        }
        
        return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$filePath}";
    }
    
    /**
     * Delete file from Supabase Storage
     */
    public function deleteFile($bucket, $filePath) {
        if (!$this->supabaseUrl || !$this->serviceRoleKey) {
            throw new Exception('Supabase storage is not configured');
        }
        
        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->serviceRoleKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Create storage bucket if it doesn't exist
     */
    public function ensureBucketExists($bucketName, $isPublic = false) {
        try {
            // Check if bucket exists
            $result = $this->db->fetchOne(
                "SELECT * FROM storage.buckets WHERE name = ?",
                [$bucketName]
            );
            
            if (!$result) {
                // Create bucket (this would typically be done via Supabase dashboard or API)
                error_log("Bucket '{$bucketName}' does not exist. Please create it in Supabase dashboard.");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error checking bucket: " . $e->getMessage());
            return false;
        }
    }
}