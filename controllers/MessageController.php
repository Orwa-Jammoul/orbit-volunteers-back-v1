<?php
require_once 'models/Message.php';

class MessageController {
    private $messageModel;
    
    public function __construct() {
        $this->messageModel = new Message();
    }
    
    public function index() {
        AuthMiddleware::requireRole(['admin']);
        
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        $status = $_GET['status'] ?? null;
        
        $messages = $this->messageModel->getAll($limit, $offset, $status);
        Response::success($messages);
    }
    
    public function show($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $message = $this->messageModel->getById($id);
        
        if (!$message) {
            Response::notFound("Message not found");
        }
        
        // Mark as read if it's unread
        if ($message['status'] === 'unread') {
            $this->messageModel->updateStatus($id, 'read');
            $message['status'] = 'read';
        }
        
        Response::success($message);
    }
    
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Validate required fields
        $required = ['name', 'email', 'message'];
        $errors = Validator::validateRequired($data, $required);
        
        if (!empty($errors)) {
            Response::error("Validation failed", 422, $errors);
        }
        
        // Validate email
        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format", 422);
        }
        
        $messageId = $this->messageModel->create($data);
        
        if ($messageId) {
            // Here you could send email notification
            Response::success(['id' => $messageId], "Message sent successfully", 201);
        } else {
            Response::error("Failed to send message", 500);
        }
    }
    
    public function updateStatus($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['status'])) {
            Response::error("Status is required", 422);
        }
        
        $allowed = ['unread', 'read', 'replied'];
        if (!in_array($data['status'], $allowed)) {
            Response::error("Invalid status", 422);
        }
        
        if ($this->messageModel->updateStatus($id, $data['status'])) {
            Response::success(null, "Message status updated successfully");
        } else {
            Response::error("Failed to update message status", 500);
        }
    }
    
    public function delete($id) {
        AuthMiddleware::requireRole(['admin']);
        
        if ($this->messageModel->delete($id)) {
            Response::success(null, "Message deleted successfully");
        } else {
            Response::error("Failed to delete message", 500);
        }
    }
}
?>