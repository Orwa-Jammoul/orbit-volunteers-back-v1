<?php
require_once 'config/database.php';

class Block {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 100, $offset = 0, $isActive = null) {
        $sql = "SELECT b.*, bc.name as category_name, bc.slug as category_slug
                FROM block b
                LEFT JOIN block_category bc ON b.category_id = bc.id
                WHERE 1=1";
        
        $params = [];
        
        if ($isActive !== null) {
            $sql .= " AND b.is_active = :is_active";
            $params[':is_active'] = $isActive;
        }
        
        $sql .= " ORDER BY b.display_order, b.created_at DESC LIMIT :limit OFFSET :offset";
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
        $sql = "SELECT b.*, bc.name as category_name, bc.slug as category_slug
                FROM block b
                LEFT JOIN block_category bc ON b.category_id = bc.id
                WHERE b.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    public function getByCategory($categorySlug, $isActive = true) {
        $sql = "SELECT b.* FROM block b
                JOIN block_category bc ON b.category_id = bc.id
                WHERE bc.slug = :category_slug";
        
        if ($isActive) {
            $sql .= " AND b.is_active = 1";
        }
        
        $sql .= " ORDER BY b.display_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_slug' => $categorySlug]);
        
        return $stmt->fetchAll();
    }
    
    public function getChildren($parentId, $isActive = true) {
        $sql = "SELECT * FROM block WHERE parent_id = :parent_id";
        
        if ($isActive) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY display_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $sql = "INSERT INTO block (category_id, parent_id, title, description1, description2, 
                                   description3, description4, image1_url, image2_url, 
                                   image3_url, image4_url, url, location_url, icon_text, 
                                   display_order, is_active) 
                VALUES (:category_id, :parent_id, :title, :description1, :description2, 
                        :description3, :description4, :image1_url, :image2_url, 
                        :image3_url, :image4_url, :url, :location_url, :icon_text, 
                        :display_order, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':title' => $data['title'],
            ':description1' => $data['description1'] ?? null,
            ':description2' => $data['description2'] ?? null,
            ':description3' => $data['description3'] ?? null,
            ':description4' => $data['description4'] ?? null,
            ':image1_url' => $data['image1_url'] ?? null,
            ':image2_url' => $data['image2_url'] ?? null,
            ':image3_url' => $data['image3_url'] ?? null,
            ':image4_url' => $data['image4_url'] ?? null,
            ':url' => $data['url'] ?? null,
            ':location_url' => $data['location_url'] ?? null,
            ':icon_text' => $data['icon_text'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['category_id', 'parent_id', 'title', 'description1', 'description2', 
                          'description3', 'description4', 'image1_url', 'image2_url', 
                          'image3_url', 'image4_url', 'url', 'location_url', 'icon_text', 
                          'display_order', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE block SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM block WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getCategories() {
        $sql = "SELECT * FROM block_category ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function createCategory($data) {
        $sql = "INSERT INTO block_category (name, slug, description) 
                VALUES (:name, :slug, :description)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
}
?>