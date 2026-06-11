<?php
require_once 'config/database.php';

class Media {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Image Album methods
    public function createImageAlbum($title, $description = null) {
        $sql = "INSERT INTO image_album (title, description) VALUES (:title, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':title' => $title, ':description' => $description]);
        return $this->db->lastInsertId();
    }
    
    public function getImageAlbum($id) {
        $sql = "SELECT * FROM image_album WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $album = $stmt->fetch();
        
        if ($album) {
            $album['images'] = $this->getImages($id);
        }
        
        return $album;
    }
    
    public function getAllImageAlbums() {
        $sql = "SELECT * FROM image_album ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $albums = $stmt->fetchAll();
        
        foreach ($albums as &$album) {
            $album['image_count'] = $this->getImageCount($album['id']);
        }
        
        return $albums;
    }
    
    public function addImage($albumId, $imageUrl, $alt = null, $caption = null, $displayOrder = 0) {
        $sql = "INSERT INTO image (image_album_id, image_url, alt, caption, display_order) 
                VALUES (:album_id, :image_url, :alt, :caption, :display_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':album_id' => $albumId,
            ':image_url' => $imageUrl,
            ':alt' => $alt,
            ':caption' => $caption,
            ':display_order' => $displayOrder
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getImages($albumId) {
        $sql = "SELECT * FROM image WHERE image_album_id = :album_id ORDER BY display_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':album_id' => $albumId]);
        return $stmt->fetchAll();
    }
    
    public function deleteImage($imageId) {
        $sql = "DELETE FROM image WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $imageId]);
    }
    
    private function getImageCount($albumId) {
        $sql = "SELECT COUNT(*) as count FROM image WHERE image_album_id = :album_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':album_id' => $albumId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    // Video Album methods
    public function createVideoAlbum($title, $description = null) {
        $sql = "INSERT INTO video_album (title, description) VALUES (:title, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':title' => $title, ':description' => $description]);
        return $this->db->lastInsertId();
    }
    
    public function getVideoAlbum($id) {
        $sql = "SELECT * FROM video_album WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $album = $stmt->fetch();
        
        if ($album) {
            $album['videos'] = $this->getVideos($id);
        }
        
        return $album;
    }
    
    public function getAllVideoAlbums() {
        $sql = "SELECT * FROM video_album ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $albums = $stmt->fetchAll();
        
        foreach ($albums as &$album) {
            $album['video_count'] = $this->getVideoCount($album['id']);
        }
        
        return $albums;
    }
    
    public function addVideo($albumId, $videoUrl, $title = null, $description = null, $duration = null, $thumbnailUrl = null, $displayOrder = 0) {
        $sql = "INSERT INTO video (video_album_id, video_url, title, description, duration, thumbnail_url, display_order) 
                VALUES (:album_id, :video_url, :title, :description, :duration, :thumbnail_url, :display_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':album_id' => $albumId,
            ':video_url' => $videoUrl,
            ':title' => $title,
            ':description' => $description,
            ':duration' => $duration,
            ':thumbnail_url' => $thumbnailUrl,
            ':display_order' => $displayOrder
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getVideos($albumId) {
        $sql = "SELECT * FROM video WHERE video_album_id = :album_id ORDER BY display_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':album_id' => $albumId]);
        return $stmt->fetchAll();
    }
    
    public function deleteVideo($videoId) {
        $sql = "DELETE FROM video WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $videoId]);
    }
    
    private function getVideoCount($albumId) {
        $sql = "SELECT COUNT(*) as count FROM video WHERE video_album_id = :album_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':album_id' => $albumId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    // File upload handling
    public function uploadFile($file, $targetDir) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $file['name']);
        $targetPath = $targetDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
        
        return false;
    }
}
?>