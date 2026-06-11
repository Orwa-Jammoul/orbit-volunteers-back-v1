<?php
require_once 'models/UserProfile.php';

class UserProfileController {
    private $profileModel;
    
    public function __construct() {
        $this->profileModel = new UserProfile();
    }
    
    public function getProfile($userId = null) {
        $authUser = AuthMiddleware::authenticate();
        
        // If no userId provided, get current user's profile
        if (!$userId) {
            $userId = $authUser['id'];
        }
        
        // Check authorization
        if ($authUser['id'] != $userId && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only view your own profile");
        }
        
        $profile = $this->profileModel->getCompleteProfile($userId);
        
        if (!$profile) {
            Response::notFound("Profile not found");
        }
        
        Response::success($profile);
    }
    
    public function updateProfile($userId) {
        $authUser = AuthMiddleware::authenticate();
        
        // Check authorization
        if ($authUser['id'] != $userId && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only update your own profile");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if ($this->profileModel->updateProfile($userId, $data)) {
            Response::success(null, "Profile updated successfully");
        } else {
            Response::error("Failed to update profile", 500);
        }
    }
    
    public function addExperience($userId) {
        $authUser = AuthMiddleware::authenticate();
        
        if ($authUser['id'] != $userId && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only add experience to your own profile");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if (empty($data['title'])) {
            Response::error("Title is required", 422);
        }
        
        $experienceId = $this->profileModel->addExperience($userId, $data);
        
        if ($experienceId) {
            Response::success(['id' => $experienceId], "Experience added successfully", 201);
        } else {
            Response::error("Failed to add experience", 500);
        }
    }
    
    public function addEducation($userId) {
        $authUser = AuthMiddleware::authenticate();
        
        if ($authUser['id'] != $userId && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only add education to your own profile");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if (empty($data['degree']) || empty($data['institution'])) {
            Response::error("Degree and institution are required", 422);
        }
        
        $educationId = $this->profileModel->addEducation($userId, $data);
        
        if ($educationId) {
            Response::success(['id' => $educationId], "Education added successfully", 201);
        } else {
            Response::error("Failed to add education", 500);
        }
    }
    
    public function addSkill($userId) {
        $authUser = AuthMiddleware::authenticate();
        
        if ($authUser['id'] != $userId && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only add skills to your own profile");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if (empty($data['skill_name'])) {
            Response::error("Skill name is required", 422);
        }
        
        $skillId = $this->profileModel->addSkill($userId, $data);
        
        if ($skillId) {
            Response::success(['id' => $skillId], "Skill added successfully", 201);
        } else {
            Response::error("Failed to add skill", 500);
        }
    }
}
?>