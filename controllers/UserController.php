<?php
require_once 'models/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index()
    {
        // Check if admin (you can implement proper auth check)
        try {
            AuthMiddleware::authenticate();
        } catch (Exception $e) {
            // Allow access or restrict as needed
        }

        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        $filters = [];

        if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
        if (isset($_GET['role'])) $filters['role'] = $_GET['role'];
        if (isset($_GET['specialization'])) $filters['specialization'] = $_GET['specialization'];

        $users = $this->userModel->getAll($limit, $offset, $filters);

        // Remove passwords from response
        foreach ($users as &$user) {
            unset($user['password']);
        }

        Response::success($users);
    }

    public function show($id)
    {
        $user = $this->userModel->getById($id);

        if (!$user) {
            Response::notFound("User not found");
        }

        // Remove password from response
        unset($user['password']);

        // Get additional data
        $user['experience'] = $this->userModel->getExperience($id);
        $user['education'] = $this->userModel->getEducation($id);
        $user['skills'] = $this->userModel->getSkills($id);

        Response::success($user);
    }

    public function update($id)
    {
        // Check authentication
        $authUser = AuthMiddleware::authenticate();

        // Check from user existing
        $existingUser = $this->userModel->getById($id);

        if (!$existingUser) {
            Response::notFound("User not found");
        }

        // Check if user is updating their own profile or is admin
        if ($authUser['id'] != $id && $authUser['role'] !== 'admin') {
            Response::forbidden("You can only update your own profile");
        }

        // Get data and clean it
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            Response::error("Invalid input", 400);
        }

        $data = Validator::sanitizeArray($data);


        // Prevent update role (just for admin)
        if ($authUser['role'] !== 'admin') {
            unset($data['role']);
            unset($data['created_at']);
            unset($data['updated_at']);
        }

        // return errors
        if (!empty($errors)) {
            Response::error("Validation failed", 422, $errors);
        }

        if ($this->userModel->update($id, $data)) {
            Response::success(null, "User updated successfully");
        } else {
            Response::error("Update failed", 500);
        }
    }

    public function delete($id)
    {
        // Check if admin
        $authUser = AuthMiddleware::authenticate();
        if ($authUser['role'] !== 'admin') {
            Response::forbidden("Admin access required");
        }

        if ($this->userModel->delete($id)) {
            Response::success(null, "User deleted successfully");
        } else {
            Response::error("Delete failed", 500);
        }
    }

    public function updateStatus($id)
    {
        // Check if admin
        $authUser = AuthMiddleware::authenticate();
        if ($authUser['role'] !== 'admin') {
            Response::forbidden("Admin access required");
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['status'])) {
            Response::error("Status is required", 422);
        }

        $allowedStatuses = ['active', 'inactive', 'suspended', 'pending'];
        if (!in_array($data['status'], $allowedStatuses)) {
            Response::error("Invalid status", 422);
        }

        if ($this->userModel->updateStatus($id, $data['status'])) {
            Response::success(null, "User status updated successfully");
        } else {
            Response::error("Update failed", 500);
        }
    }
}
