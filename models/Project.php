<?php
require_once 'config/database.php';

class Project {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 50, $offset = 0, $status = null, $categoryId = null) {
        $sql = "SELECT p.*, 
                       pc.id as category_id, pc.name as category_name, pc.slug as category_slug,
                       t.id as team_id, t.name as team_name, t.slug as team_slug
                FROM project p
                LEFT JOIN project_category pc ON p.category_id = pc.id
                LEFT JOIN team t ON p.team_id = t.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        
        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        
        $sql .= " ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";
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
        // Increment view count
        $this->incrementViewCount($id);
        
        $sql = "SELECT p.*, 
                       pc.id as category_id, pc.name as category_name, pc.slug as category_slug, 
                       pc.description as category_description,
                       t.id as team_id, t.name as team_name, t.slug as team_slug, t.description as team_description
                FROM project p
                LEFT JOIN project_category pc ON p.category_id = pc.id
                LEFT JOIN team t ON p.team_id = t.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Increment view count for a project
     */
    private function incrementViewCount($id) {
        $sql = "UPDATE project SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
    
    public function create($data) {
        $sql = "INSERT INTO project (category_id, team_id, title, status, description1, description2, 
                                      description3, description4, image1_url, image2_url, 
                                      image3_url, image4_url, project_url, image_album_id, video_album_id, published_at) 
                VALUES (:category_id, :team_id, :title, :status, :description1, :description2, 
                        :description3, :description4, :image1_url, :image2_url, 
                        :image3_url, :image4_url, :project_url, :image_album_id, :video_album_id, :published_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'] ?? null,
            ':team_id' => $data['team_id'] ?? null,
            ':title' => $data['title'],
            ':status' => $data['status'] ?? 'need_volunteers',
            ':description1' => $data['description1'] ?? null,
            ':description2' => $data['description2'] ?? null,
            ':description3' => $data['description3'] ?? null,
            ':description4' => $data['description4'] ?? null,
            ':image1_url' => $data['image1_url'] ?? null,
            ':image2_url' => $data['image2_url'] ?? null,
            ':image3_url' => $data['image3_url'] ?? null,
            ':image4_url' => $data['image4_url'] ?? null,
            ':project_url' => $data['project_url'] ?? null,
            ':image_album_id' => $data['image_album_id'] ?? null,
            ':video_album_id' => $data['video_album_id'] ?? null,
            ':published_at' => $data['published_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['category_id', 'team_id', 'title', 'status', 'description1', 'description2', 
                          'description3', 'description4', 'image1_url', 'image2_url', 
                          'image3_url', 'image4_url', 'project_url', 'image_album_id', 'video_album_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE project SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM project WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getCategories() {
        $sql = "SELECT * FROM project_category ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getVolunteerOpportunities() {
        $sql = "SELECT p.*, 
                       pc.id as category_id, pc.name as category_name, pc.slug as category_slug
                FROM project p
                LEFT JOIN project_category pc ON p.category_id = pc.id
                WHERE p.status = 'need_volunteers' 
                ORDER BY p.published_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get category by ID
     */
    public function getCategoryById($id) {
        $sql = "SELECT * FROM project_category WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get category by slug
     */
    public function getCategoryBySlug($slug) {
        $sql = "SELECT * FROM project_category WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get project count by category
     */
    public function getProjectCountByCategory($categoryId) {
        $sql = "SELECT COUNT(*) as count FROM project WHERE category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
    
    /**
     * Create a new category
     */
    public function createCategory($data) {
        $sql = "INSERT INTO project_category (name, slug, description, parent_id) 
                VALUES (:name, :slug, :description, :parent_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update a category
     */
    public function updateCategory($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['name', 'slug', 'description', 'parent_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE project_category SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Delete a category
     */
    public function deleteCategory($id) {
        // First, update projects in this category to set category_id to NULL
        $updateSql = "UPDATE project SET category_id = NULL WHERE category_id = :category_id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([':category_id' => $id]);
        
        // Then delete the category
        $sql = "DELETE FROM project_category WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
}
?>