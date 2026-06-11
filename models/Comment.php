<?php
require_once 'config/database.php';

class Comment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByTarget($targetType, $targetId, $status = 'approved') {
        $sql = "SELECT c.*, u.username, u.firstname, u.lastname, u.photo_url
                FROM comment c
                LEFT JOIN user u ON c.user_id = u.id
                WHERE c.target_type = :target_type AND c.target_id = :target_id";
        
        if ($status) {
            $sql .= " AND c.status = :status";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $params = [':target_type' => $targetType, ':target_id' => $targetId];
        
        if ($status) {
            $params[':status'] = $status;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT c.*, u.username, u.firstname, u.lastname, u.photo_url
                FROM comment c
                LEFT JOIN user u ON c.user_id = u.id
                WHERE c.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO comment (target_type, target_id, user_id, username, content, status) 
                VALUES (:target_type, :target_id, :user_id, :username, :content, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':target_type' => $data['target_type'],
            ':target_id' => $data['target_id'],
            ':user_id' => $data['user_id'] ?? null,
            ':username' => $data['username'] ?? null,
            ':content' => $data['content'],
            ':status' => $data['status'] ?? 'pending'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE comment SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM comment WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as count FROM comment WHERE status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function getAllPending($limit = 50, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.firstname, u.lastname, u.photo_url
                FROM comment c
                LEFT JOIN user u ON c.user_id = u.id
                WHERE c.status = 'pending'
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>