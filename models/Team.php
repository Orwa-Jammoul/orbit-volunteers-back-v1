<?php
require_once 'config/database.php';

class Team {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT t.*, 
                       CONCAT(u.firstname, ' ', u.lastname) as team_leader_name
                FROM team t
                LEFT JOIN user u ON t.team_leader_id = u.id
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $teams = $stmt->fetchAll();
        
        foreach ($teams as &$team) {
            $team['members'] = $this->getMembers($team['id']);
        }
        
        return $teams;
    }
    
    public function getById($id) {
        $sql = "SELECT t.*, 
                       CONCAT(u.firstname, ' ', u.lastname) as team_leader_name
                FROM team t
                LEFT JOIN user u ON t.team_leader_id = u.id
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $team = $stmt->fetch();
        
        if ($team) {
            $team['members'] = $this->getMembers($id);
        }
        
        return $team;
    }
    
    public function getBySlug($slug) {
        $sql = "SELECT * FROM team WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        
        $team = $stmt->fetch();
        
        if ($team) {
            $team['members'] = $this->getMembers($team['id']);
        }
        
        return $team;
    }
    
    public function create($data) {
        $sql = "INSERT INTO team (name, slug, description, team_leader_id) 
                VALUES (:name, :slug, :description, :team_leader_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':team_leader_id' => $data['team_leader_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [':id' => $id];
        
        $allowedFields = ['name', 'slug', 'description', 'team_leader_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE team SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM team WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function addMember($teamId, $userId, $role = null, $displayOrder = 0) {
        $sql = "INSERT INTO team_member (team_id, user_id, role, display_order) 
                VALUES (:team_id, :user_id, :role, :display_order)
                ON DUPLICATE KEY UPDATE role = :role, display_order = :display_order";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId,
            ':role' => $role,
            ':display_order' => $displayOrder
        ]);
    }
    
    public function removeMember($teamId, $userId) {
        $sql = "DELETE FROM team_member WHERE team_id = :team_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':team_id' => $teamId, ':user_id' => $userId]);
    }
    
    private function getMembers($teamId) {
        $sql = "SELECT tm.*, u.username, u.firstname, u.lastname, u.email, u.photo_url, u.bio
                FROM team_member tm
                JOIN user u ON tm.user_id = u.id
                WHERE tm.team_id = :team_id
                ORDER BY tm.display_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':team_id' => $teamId]);
        
        return $stmt->fetchAll();
    }
    
    public function getUserTeams($userId) {
        $sql = "SELECT t.* FROM team t
                JOIN team_member tm ON t.id = tm.team_id
                WHERE tm.user_id = :user_id
                ORDER BY t.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
}
?>