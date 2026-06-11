<?php
require_once 'models/Media.php';

class MediaController {
    private $mediaModel;
    
    public function __construct() {
        $this->mediaModel = new Media();
    }
    
    public function upload() {
        AuthMiddleware::requireRole(['admin']);
        
        if (!isset($_FILES['file'])) {
            Response::error("No file uploaded", 422);
        }
        
        $file = $_FILES['file'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        if (!in_array($file['type'], $allowedTypes)) {
            Response::error("Invalid file type", 422);
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error("File too large. Max 5MB", 422);
        }
        
        $uploadDir = UPLOAD_DIR . date('Y/m/d');
        $fileName = $this->mediaModel->uploadFile($file, $uploadDir);
        
        if ($fileName) {
            $fileUrl = "/uploads/" . date('Y/m/d') . "/" . $fileName;
            Response::success(['url' => $fileUrl], "File uploaded successfully");
        } else {
            Response::error("Failed to upload file", 500);
        }
    }
    
    public function getAlbums() {
        $type = $_GET['type'] ?? 'image';
        
        if ($type === 'image') {
            $albums = $this->mediaModel->getAllImageAlbums();
        } else {
            $albums = $this->mediaModel->getAllVideoAlbums();
        }
        
        Response::success($albums);
    }
    
    public function getAlbum($id) {
        $type = $_GET['type'] ?? 'image';
        
        if ($type === 'image') {
            $album = $this->mediaModel->getImageAlbum($id);
        } else {
            $album = $this->mediaModel->getVideoAlbum($id);
        }
        
        if (!$album) {
            Response::notFound("Album not found");
        }
        
        Response::success($album);
    }
    
    public function createAlbum() {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['title'])) {
            Response::error("Title is required", 422);
        }
        
        $type = $data['type'] ?? 'image';
        
        if ($type === 'image') {
            $albumId = $this->mediaModel->createImageAlbum($data['title'], $data['description'] ?? null);
        } else {
            $albumId = $this->mediaModel->createVideoAlbum($data['title'], $data['description'] ?? null);
        }
        
        if ($albumId) {
            Response::success(['id' => $albumId], "Album created successfully", 201);
        } else {
            Response::error("Failed to create album", 500);
        }
    }
    
    public function addImage($albumId) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['image_url'])) {
            Response::error("Image URL is required", 422);
        }
        
        $imageId = $this->mediaModel->addImage(
            $albumId,
            $data['image_url'],
            $data['alt'] ?? null,
            $data['caption'] ?? null,
            $data['display_order'] ?? 0
        );
        
        if ($imageId) {
            Response::success(['id' => $imageId], "Image added successfully", 201);
        } else {
            Response::error("Failed to add image", 500);
        }
    }
    
    public function addVideo($albumId) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['video_url'])) {
            Response::error("Video URL is required", 422);
        }
        
        $videoId = $this->mediaModel->addVideo(
            $albumId,
            $data['video_url'],
            $data['title'] ?? null,
            $data['description'] ?? null,
            $data['duration'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['display_order'] ?? 0
        );
        
        if ($videoId) {
            Response::success(['id' => $videoId], "Video added successfully", 201);
        } else {
            Response::error("Failed to add video", 500);
        }
    }
    
    public function deleteMedia($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $type = $_GET['type'] ?? 'image';
        
        if ($type === 'image') {
            $result = $this->mediaModel->deleteImage($id);
        } else {
            $result = $this->mediaModel->deleteVideo($id);
        }
        
        if ($result) {
            Response::success(null, "Media deleted successfully");
        } else {
            Response::error("Failed to delete media", 500);
        }
    }
}
?>