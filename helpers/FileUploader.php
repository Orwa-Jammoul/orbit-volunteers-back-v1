<?php
class FileUploader {
    private $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/ogg',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    private $maxSize = 5242880; // 5MB
    private $uploadDir;
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?: UPLOAD_DIR;
    }
    
    public function upload($file, $subDir = null) {
        // Validate file
        $errors = $this->validate($file);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create target directory
        $targetDir = $this->uploadDir;
        if ($subDir) {
            $targetDir .= '/' . $subDir;
        }
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;
        
        // Move file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $targetPath,
                'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath),
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }
        
        return ['success' => false, 'errors' => ['Failed to move uploaded file']];
    }
    
    public function uploadMultiple($files, $subDir = null) {
        $results = [];
        
        foreach ($files['tmp_name'] as $key => $tmp_name) {
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $tmp_name,
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];
            
            $results[] = $this->upload($file, $subDir);
        }
        
        return $results;
    }
    
    private function validate($file) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $errors[] = "File size exceeds maximum allowed size of " . ($this->maxSize / 1024 / 1024) . "MB";
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowedTypes)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $this->allowedTypes);
        }
        
        // Check for malicious content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            $errors[] = "File content type does not match declared type";
        }
        
        return $errors;
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "File exceeds upload_max_filesize directive";
            case UPLOAD_ERR_FORM_SIZE:
                return "File exceeds MAX_FILE_SIZE directive";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error";
        }
    }
    
    public function setAllowedTypes($types) {
        $this->allowedTypes = $types;
    }
    
    public function setMaxSize($size) {
        $this->maxSize = $size;
    }
}
?>