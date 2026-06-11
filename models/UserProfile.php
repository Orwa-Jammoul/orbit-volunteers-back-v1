<?php
require_once 'config/database.php';

class UserProfile {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getCompleteProfile($userId) {
        $profile = [];
        
        // Get user basic info
        $userModel = new User();
        $profile['user'] = $userModel->getById($userId);
        
        if (!$profile['user']) {
            return null;
        }
        
        // Get experience
        $experienceModel = new Experience();
        $profile['experience'] = $experienceModel->getByUserId($userId);
        
        // Get education
        $educationModel = new Education();
        $profile['education'] = $educationModel->getByUserId($userId);
        
        // Get skills
        $skillModel = new Skill();
        $profile['skills'] = $skillModel->getByUserId($userId);
        
        // Get teams
        $teamModel = new Team();
        $profile['teams'] = $teamModel->getUserTeams($userId);
        
        // Get blog posts (as author)
        $profile['blogs'] = $this->getUserBlogs($userId);
        
        // Get comments
        $profile['comments'] = $this->getUserComments($userId);
        
        return $profile;
    }
    
    private function getUserBlogs($userId) {
        $sql = "SELECT b.id, b.title, b.published_at, b.view_count
                FROM blog b
                JOIN blog_author ba ON b.id = ba.blog_id
                WHERE ba.user_id = :user_id
                ORDER BY b.published_at DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    private function getUserComments($userId) {
        $sql = "SELECT c.*, 
                       CASE 
                           WHEN c.target_type = 'blog' THEN (SELECT title FROM blog WHERE id = c.target_id)
                           WHEN c.target_type = 'project' THEN (SELECT title FROM project WHERE id = c.target_id)
                       END as target_title
                FROM comment c
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    public function updateProfile($userId, $data) {
        $userModel = new User();
        return $userModel->update($userId, $data);
    }
    
    public function addExperience($userId, $data) {
        $data['user_id'] = $userId;
        $experienceModel = new Experience();
        return $experienceModel->create($data);
    }
    
    public function addEducation($userId, $data) {
        $data['user_id'] = $userId;
        $educationModel = new Education();
        return $educationModel->create($data);
    }
    
    public function addSkill($userId, $data) {
        $data['user_id'] = $userId;
        $skillModel = new Skill();
        return $skillModel->create($data);
    }
}
?>