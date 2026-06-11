<?php
require_once 'config/database.php';

class Blog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 50, $offset = 0, $categoryId = null) {
        $sql = "SELECT b.*, 
                    bc.id as category_id, bc.name as category_name, bc.slug as category_slug,
                    u.id as author_id, u.username as author_username, 
                    u.firstname as author_firstname, u.lastname as author_lastname,
                    u.photo_url as author_photo_url,
                    (SELECT COUNT(*) FROM comment WHERE target_type = 'blog' AND target_id = b.id AND status = 'approved') as comment_count
                FROM blog b
                LEFT JOIN blog_category bc ON b.category_id = bc.id
                LEFT JOIN user u ON b.author_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($categoryId) {
            $sql .= " AND b.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        
        $sql .= " ORDER BY b.published_at DESC LIMIT :limit OFFSET :offset";
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
        
        $sql = "SELECT b.*, 
                    bc.id as category_id, bc.name as category_name, bc.slug as category_slug, bc.description as category_description,
                    u.id as author_id, u.username as author_username, 
                    u.firstname as author_firstname, u.lastname as author_lastname,
                    u.email as author_email, u.photo_url as author_photo_url,
                    u.bio as author_bio
                FROM blog b
                LEFT JOIN blog_category bc ON b.category_id = bc.id
                LEFT JOIN user u ON b.author_id = u.id
                WHERE b.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    public function getByAuthor($authorId, $limit = 50, $offset = 0) {
        $sql = "SELECT b.*, bc.name as category_name,
                       u.id as author_id, u.username as author_username, 
                       u.firstname as author_firstname, u.lastname as author_lastname,
                       u.photo_url as author_photo_url,
                       (SELECT COUNT(*) FROM comment WHERE target_type = 'blog' AND target_id = b.id AND status = 'approved') as comment_count
                FROM blog b
                LEFT JOIN blog_category bc ON b.category_id = bc.id
                LEFT JOIN user u ON b.author_id = u.id
                WHERE b.author_id = :author_id
                ORDER BY b.published_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':author_id', $authorId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $sql = "INSERT INTO blog (category_id, author_id, title, tag, description1, description2, 
                                  description3, description4, image1_url, image2_url, 
                                  image3_url, image4_url, url, image_album_id, video_album_id, published_at) 
                VALUES (:category_id, :author_id, :title, :tag, :description1, :description2, 
                        :description3, :description4, :image1_url, :image2_url, 
                        :image3_url, :image4_url, :url, :image_album_id, :video_album_id, :published_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'] ?? null,
            ':author_id' => $data['author_id'] ?? null,
            ':title' => $data['title'],
            ':tag' => $data['tag'] ?? null,
            ':description1' => $data['description1'] ?? null,
            ':description2' => $data['description2'] ?? null,
            ':description3' => $data['description3'] ?? null,
            ':description4' => $data['description4'] ?? null,
            ':image1_url' => $data['image1_url'] ?? null,
            ':image2_url' => $data['image2_url'] ?? null,
            ':image3_url' => $data['image3_url'] ?? null,
            ':image4_url' => $data['image4_url'] ?? null,
            ':url' => $data['url'] ?? null,
            ':image_album_id' => $data['image_album_id'] ?? null,
            ':video_album_id' => $data['video_album_id'] ?? null,
            ':published_at' => $data['published_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['category_id', 'author_id', 'title', 'tag', 'description1', 'description2', 
                          'description3', 'description4', 'image1_url', 'image2_url', 
                          'image3_url', 'image4_url', 'url', 'image_album_id', 'video_album_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE blog SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM blog WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    private function incrementViewCount($id) {
        $sql = "UPDATE blog SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
    
    public function getCategories() {
        $sql = "SELECT * FROM blog_category ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($id) {
        $sql = "SELECT * FROM blog_category WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }

    /**
     * Get category by slug
     */
    public function getCategoryBySlug($slug) {
        $sql = "SELECT * FROM blog_category WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        
        return $stmt->fetch();
    }

    /**
     * Get blog count by category
     */
    public function getBlogCountByCategory($categoryId) {
        $sql = "SELECT COUNT(*) as count FROM blog WHERE category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $result = $stmt->fetch();
        
        return $result['count'];
    }

    /**
     * Create a new category
     */
    public function createCategory($data) {
        $sql = "INSERT INTO blog_category (name, slug, description, parent_id) 
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
        
        $sql = "UPDATE blog_category SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id) {
        $sql = "DELETE FROM blog_category WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
}
?>