<?php
require_once 'models/Team.php';

class TeamController {
    private $teamModel;
    
    public function __construct() {
        $this->teamModel = new Team();
    }
    
    public function index() {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $teams = $this->teamModel->getAll($limit, $offset);
        Response::success($teams);
    }
    
    public function show($id) {
        $team = $this->teamModel->getById($id);
        
        if (!$team) {
            Response::notFound("Team not found");
        }
        
        Response::success($team);
    }
    
    public function create() {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            Response::error("Name and slug are required", 422);
        }
        
        $teamId = $this->teamModel->create($data);
        
        if ($teamId) {
            Response::success(['id' => $teamId], "Team created successfully", 201);
        } else {
            Response::error("Failed to create team", 500);
        }
    }
    
    public function update($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if ($this->teamModel->update($id, $data)) {
            Response::success(null, "Team updated successfully");
        } else {
            Response::error("Failed to update team", 500);
        }
    }
    
    public function delete($id) {
        AuthMiddleware::requireRole(['admin']);
        
        if ($this->teamModel->delete($id)) {
            Response::success(null, "Team deleted successfully");
        } else {
            Response::error("Failed to delete team", 500);
        }
    }
    
    public function addMember($teamId) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['user_id'])) {
            Response::error("User ID is required", 422);
        }
        
        $role = $data['role'] ?? null;
        $displayOrder = $data['display_order'] ?? 0;
        
        if ($this->teamModel->addMember($teamId, $data['user_id'], $role, $displayOrder)) {
            Response::success(null, "Team member added successfully");
        } else {
            Response::error("Failed to add team member", 500);
        }
    }
    
    public function removeMember($teamId, $userId) {
        AuthMiddleware::requireRole(['admin']);
        
        if ($this->teamModel->removeMember($teamId, $userId)) {
            Response::success(null, "Team member removed successfully");
        } else {
            Response::error("Failed to remove team member", 500);
        }
    }
}
?>