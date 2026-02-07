<?php
/**
 * File Upload Class
 * Handles secure file uploads with validation
 */

class FileUpload {
    private $allowedTypes = ['pdf', 'docx', 'pptx', 'jpg', 'jpeg', 'png', 'mp4'];
    private $maxSize = 10485760; // 10MB
    private $uploadDir;
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../uploads';
        $this->maxSize = Config::get('MAX_FILE_SIZE', 10485760);
        $this->allowedTypes = explode(',', Config::get('ALLOWED_FILE_TYPES', 'pdf,docx,pptx,jpg,jpeg,png,mp4'));
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload file with validation
     */
    public function upload($file, $subfolder = '') {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'success' => false,
                'message' => 'No file uploaded'
            ];
        }
        
        // Validate file size
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'message' => 'File size exceeds maximum limit of ' . ($this->maxSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // Get file extension
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($fileExt, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes)
            ];
        }
        
        // Validate file content (MIME type)
        $allowedMimes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'mp4' => 'video/mp4'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, array_values($allowedMimes))) {
            return [
                'success' => false,
                'message' => 'Invalid file content type'
            ];
        }
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file['name']);
        
        // Create subfolder if specified
        $targetDir = $this->uploadDir;
        if ($subfolder) {
            $targetDir .= '/' . trim($subfolder, '/');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }
        
        $targetPath = $targetDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $targetPath,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $fileExt
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file'
        ];
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $subfolder = '') {
        $results = [];
        $successCount = 0;
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->upload($file, $subfolder);
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            }
        }
        
        return [
            'success' => $successCount > 0,
            'uploaded' => $successCount,
            'total' => count($files['name']),
            'results' => $results
        ];
    }
    
    /**
     * Delete file
     */
    public function delete($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = preg_replace('/[^a-zA-Z0-9-_]/', '-', $basename);
        $basename = trim($basename, '-');
        
        $randomString = bin2hex(random_bytes(8));
        $timestamp = time();
        
        return $timestamp . '_' . $randomString . '_' . $basename . '.' . $extension;
    }
    
    /**
     * Format file size
     */
    public static function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}