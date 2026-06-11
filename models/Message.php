<?php
require_once 'config/database.php';

class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 100, $offset = 0, $status = null) {
        $sql = "SELECT * FROM message WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM message WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function getByUser($userId) {
        $sql = "SELECT * FROM message WHERE to_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $sql = "INSERT INTO message (name, email, to_id, subject, message, status) 
                VALUES (:name, :email, :to_id, :subject, :message, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':to_id' => $data['to_id'] ?? null,
            ':subject' => $data['subject'] ?? null,
            ':message' => $data['message'],
            ':status' => 'unread'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateStatus($id, $status) {
        $allowed = ['unread', 'read', 'replied'];
        if (!in_array($status, $allowed)) {
            return false;
        }
        
        $sql = "UPDATE message SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM message WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getUnreadCount() {
        $sql = "SELECT COUNT(*) as count FROM message WHERE status = 'unread'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>