<?php
require_once 'models/Comment.php';

class CommentController
{
    private $commentModel;

    public function __construct()
    {
        $this->commentModel = new Comment();
    }

    public function index($type, $id)
    {
        if (!in_array($type, ['blog', 'project'])) {
            Response::error("Invalid target type", 422);
        }

        $status = $_GET['status'] ?? 'approved';

        // Only admin can see pending comments
        if ($status === 'pending') {
            AuthMiddleware::requireRole(['admin']);
        }

        $existingComment = $this->commentModel->getByTarget($type, $id, $status);

        if (!$existingComment) {
            Response::notFound("No pending comments found for this target");
        }

        $comments = $this->commentModel->getByTarget($type, $id, $status);
        Response::success($comments);
    }

    public function create()
    {
        $authUser = AuthMiddleware::authenticate();

        $data['user_id'] = $authUser['id'];

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            Response::error("Invalid input", 400);
        }
        $data = Validator::sanitizeArray($data);

        // Validate required fields
        $required = ['target_type', 'target_id', 'content'];
        $errors = Validator::validateRequired($data, $required);

        if (!empty($errors)) {
            Response::error("Validation failed", 422, $errors);
        }

        if (!in_array($data['target_type'], ['blog', 'project'])) {
            Response::error("Invalid target type", 422);
        }

        // that will not appear like error but user can not send status if not admin
        if ($authUser['role'] !== 'admin') {
            unset($data['status']);
        }

        // Guest comment
        if (empty($data['username'])) {
            Response::error("Username is required for guest comments", 422);
        }


        $commentId = $this->commentModel->create($data);

        if ($commentId) {
            Response::success(['id' => $commentId], "Comment submitted for approval", 201);
        } else {
            Response::error("Failed to submit comment", 500);
        }
    }

    public function updateStatus($id)
    {
        AuthMiddleware::requireRole(['admin']);

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['status'])) {
            Response::error("Status is required", 422);
        }

        if (!in_array($data['status'], ['pending', 'approved'])) {
            Response::error("Invalid status", 422);
        }

        if ($this->commentModel->updateStatus($id, $data['status'])) {
            Response::success(null, "Comment status updated successfully");
        } else {
            Response::error("Failed to update comment status", 500);
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireRole(['admin']);

        if ($this->commentModel->delete($id)) {
            Response::success(null, "Comment deleted successfully");
        } else {
            Response::error("Failed to delete comment", 500);
        }
    }
}
