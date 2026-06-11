<?php
require_once 'config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 100, $offset = 0, $filters = []) {
        $sql = "SELECT id, username, email, firstname, lastname, gender, mobile_phone, 
                       nationality, specialization, status, role, github_url, facebook_url, 
                       linkedin_url, photo_url, bio, created_at, updated_at 
                FROM user WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        
        if (isset($filters['specialization'])) {
            $sql .= " AND specialization = :specialization";
            $params[':specialization'] = $filters['specialization'];
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
        $sql = "SELECT id, username, email, firstname, lastname, gender, mobile_phone, 
                       address, nationality, specialization, status, role, github_url, 
                       facebook_url, linkedin_url, photo_url, bio, created_at, updated_at 
                FROM user WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    public function getByEmail($email) {
        $sql = "SELECT * FROM user WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO user (username, email, password, firstname, lastname, gender, 
                                  mobile_phone, address, nationality, specialization, 
                                  github_url, facebook_url, linkedin_url, photo_url, bio, role) 
                VALUES (:username, :email, :password, :firstname, :lastname, :gender, 
                        :mobile_phone, :address, :nationality, :specialization, 
                        :github_url, :facebook_url, :linkedin_url, :photo_url, :bio, :role)";
        
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':firstname' => $data['firstname'],
            ':lastname' => $data['lastname'],
            ':gender' => $data['gender'] ?? 'male',
            ':mobile_phone' => $data['mobile_phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':nationality' => $data['nationality'] ?? null,
            ':specialization' => $data['specialization'] ?? 'other',
            ':github_url' => $data['github_url'] ?? null,
            ':facebook_url' => $data['facebook_url'] ?? null,
            ':linkedin_url' => $data['linkedin_url'] ?? null,
            ':photo_url' => $data['photo_url'] ?? null,
            ':bio' => $data['bio'] ?? null,
            ':role' => $data['role'] ?? 'volunteer'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['username', 'email', 'firstname', 'lastname', 'gender', 
                          'mobile_phone', 'address', 'nationality', 'specialization', 
                          'status', 'github_url', 'facebook_url', 'linkedin_url', 
                          'photo_url', 'bio'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (isset($data['password'])) {
            $updates[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE user SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM user WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE user SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }
    
    public function getExperience($userId) {
        $sql = "SELECT * FROM experience WHERE user_id = :user_id ORDER BY display_order, start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    public function getEducation($userId) {
        $sql = "SELECT * FROM education WHERE user_id = :user_id ORDER BY display_order, start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    public function getSkills($userId) {
        $sql = "SELECT * FROM skills WHERE user_id = :user_id ORDER BY display_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
}
?>