<?php
class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    public static function success($data = null, $message = "Success", $statusCode = 200) {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    public static function error($message, $statusCode = 400, $errors = null) {
        self::json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }
    
    public static function unauthorized($message = "Unauthorized") {
        self::error($message, 401);
    }
    
    public static function forbidden($message = "Forbidden") {
        self::error($message, 403);
    }
}
?>