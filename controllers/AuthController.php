<?php
require_once 'models/User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'firstname', 'lastname'];
        $errors = Validator::validateRequired($data, $required);
        
        if (!empty($errors)) {
            Response::error("Validation failed", 422, $errors);
        }
        
        // Validate email
        if (!Validator::validateEmail($data['email'])) {
            Response::error("Invalid email format", 422);
        }
        
        // Check if user exists
        if ($this->userModel->getByEmail($data['email'])) {
            Response::error("Email already registered", 409);
        }
        
        // Create user
        $userId = $this->userModel->create($data);
        
        if ($userId) {
            Response::success(['user_id' => $userId], "Registration successful", 201);
        } else {
            Response::error("Registration failed", 500);
        }
    }
    
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error("Email and password required", 422);
        }
        
        $user = $this->userModel->getByEmail($data['email']);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::error("Invalid credentials", 401);
        }
        
        if ($user['status'] !== 'active') {
            Response::error("Account is " . $user['status'], 403);
        }
        
        // Generate JWT
        $token = JWT::encode([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'username' => $user['username']
        ]);
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'role' => $user['role']
            ]
        ], "Login successful");
    }
    
    public function me() {
        $user = AuthMiddleware::authenticate();
        $userData = $this->userModel->getById($user['id']);
        
        if (!$userData) {
            Response::notFound("User not found");
        }
        
        // Get additional data
        $userData['experience'] = $this->userModel->getExperience($user['id']);
        $userData['education'] = $this->userModel->getEducation($user['id']);
        $userData['skills'] = $this->userModel->getSkills($user['id']);
        
        Response::success($userData);
    }
    
    public function logout() {
        // JWT is stateless, but we can provide a logout endpoint
        Response::success(null, "Logout successful");
    }
}
?>