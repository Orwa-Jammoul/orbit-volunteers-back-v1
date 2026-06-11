<?php
require_once 'config/database.php';

class Education {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByUserId($userId) {
        $sql = "SELECT * FROM education WHERE user_id = :user_id ORDER BY display_order, start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM education WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO education (user_id, degree, institution, field_of_study, start_date, end_date, current, description, display_order) 
                VALUES (:user_id, :degree, :institution, :field_of_study, :start_date, :end_date, :current, :description, :display_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':degree' => $data['degree'],
            ':institution' => $data['institution'],
            ':field_of_study' => $data['field_of_study'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':current' => $data['current'] ?? false,
            ':description' => $data['description'] ?? null,
            ':display_order' => $data['display_order'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['degree', 'institution', 'field_of_study', 'start_date', 'end_date', 'current', 'description', 'display_order'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE education SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM education WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function deleteByUserId($userId) {
        $sql = "DELETE FROM education WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
}
?>