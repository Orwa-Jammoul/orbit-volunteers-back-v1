<?php
class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::unauthorized("Authorization token required");
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $payload = JWT::decode($token);
        
        if (!$payload) {
            Response::unauthorized("Invalid or expired token");
        }
        
        return $payload;
    }
    
    public static function requireRole($allowedRoles) {
        $user = self::authenticate();
        
        if (!in_array($user['role'], $allowedRoles)) {
            Response::forbidden("Insufficient permissions");
        }
        
        return $user;
    }
}
?>