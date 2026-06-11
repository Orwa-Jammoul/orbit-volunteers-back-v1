<?php
require_once 'models/Project.php';
require_once 'models/Media.php';

class ProjectController {
    private $projectModel;
    private $mediaModel;
    
    public function __construct() {
        $this->projectModel = new Project();
        $this->mediaModel = new Media();
    }
    
    public function index() {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $status = $_GET['status'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        
        $projects = $this->projectModel->getAll($limit, $offset, $status, $categoryId);
        
        // Add images, videos, and category info to each project
        foreach ($projects as &$project) {
            $project = $this->addMediaToProject($project);
            $project = $this->addCategoryToProject($project);
            $project = $this->addTeamToProject($project);
        }
        
        Response::success($projects);
    }
    
    public function show($id) {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            Response::notFound("Project not found");
        }
        
        // Add images, videos, category, and team info to the project
        $project = $this->addMediaToProject($project);
        $project = $this->addCategoryToProject($project);
        $project = $this->addTeamToProject($project);
        
        Response::success($project);
    }
    
    /**
     * Add category information to a project
     */
    private function addCategoryToProject($project) {
        if (!empty($project['category_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, slug, description, parent_id FROM project_category WHERE id = ?");
            $stmt->execute([$project['category_id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get parent category info if exists
            if ($category && !empty($category['parent_id'])) {
                $stmt = $db->prepare("SELECT id, name, slug FROM project_category WHERE id = ?");
                $stmt->execute([$category['parent_id']]);
                $parentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
                $category['parent'] = $parentCategory;
            }
            
            $project['category'] = $category;
        } else {
            $project['category'] = null;
        }
        
        return $project;
    }
    
    /**
     * Add team information to a project
     */
    private function addTeamToProject($project) {
        if (!empty($project['team_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, slug, description FROM team WHERE id = ?");
            $stmt->execute([$project['team_id']]);
            $team = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get team members
            if ($team) {
                $stmt = $db->prepare("
                    SELECT u.id, u.username, u.firstname, u.lastname, u.photo_url, tm.role
                    FROM team_member tm
                    JOIN user u ON tm.user_id = u.id
                    WHERE tm.team_id = ?
                    ORDER BY tm.display_order
                ");
                $stmt->execute([$project['team_id']]);
                $team['members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $project['team'] = $team;
        } else {
            $project['team'] = null;
        }
        
        return $project;
    }
    
    /**
     * Add images and videos to a project
     */
    private function addMediaToProject($project) {
        // Get images from image_album if exists
        if (!empty($project['image_album_id'])) {
            $project['images'] = $this->mediaModel->getImages($project['image_album_id']);
        } else {
            // Also check individual image URLs as fallback
            $project['images'] = [];
            for ($i = 1; $i <= 4; $i++) {
                $imageKey = "image{$i}_url";
                if (!empty($project[$imageKey])) {
                    $project['images'][] = [
                        'id' => null,
                        'image_url' => $project[$imageKey],
                        'alt' => $project['title'],
                        'caption' => null,
                        'display_order' => $i
                    ];
                }
            }
        }
        
        // Get videos from video_album if exists
        if (!empty($project['video_album_id'])) {
            $project['videos'] = $this->mediaModel->getVideos($project['video_album_id']);
        } else {
            $project['videos'] = [];
        }
        
        return $project;
    }
    
    /**
     * Get images for a specific project
     */
    public function getImages($id) {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            Response::notFound("Project not found");
        }
        
        if (!empty($project['image_album_id'])) {
            $images = $this->mediaModel->getImages($project['image_album_id']);
            Response::success([
                'project_id' => $id,
                'project_title' => $project['title'],
                'album_id' => $project['image_album_id'],
                'images' => $images
            ]);
        } else {
            // Return individual images as fallback
            $images = [];
            for ($i = 1; $i <= 4; $i++) {
                $imageKey = "image{$i}_url";
                if (!empty($project[$imageKey])) {
                    $images[] = [
                        'url' => $project[$imageKey],
                        'position' => $i
                    ];
                }
            }
            
            Response::success([
                'project_id' => $id,
                'project_title' => $project['title'],
                'album_id' => null,
                'images' => $images
            ]);
        }
    }
    
    /**
     * Get videos for a specific project
     */
    public function getVideos($id) {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            Response::notFound("Project not found");
        }
        
        if (!empty($project['video_album_id'])) {
            $videos = $this->mediaModel->getVideos($project['video_album_id']);
            Response::success([
                'project_id' => $id,
                'project_title' => $project['title'],
                'album_id' => $project['video_album_id'],
                'videos' => $videos
            ]);
        } else {
            Response::success([
                'project_id' => $id,
                'project_title' => $project['title'],
                'album_id' => null,
                'videos' => []
            ]);
        }
    }
    
    /**
     * Get all project categories
     */
    public function categories() {
        $categories = $this->projectModel->getCategories();
        
        // Add project count to each category
        foreach ($categories as &$category) {
            $projectCount = $this->projectModel->getProjectCountByCategory($category['id']);
            $category['project_count'] = $projectCount;
        }
        
        Response::success($categories);
    }
    
    /**
     * Get single category by ID with its projects
     */
    public function getCategoryById($id) {
        $category = $this->projectModel->getCategoryById($id);
        
        if (!$category) {
            Response::notFound("Category not found");
        }
        
        // Get projects in this category
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $projects = $this->projectModel->getAll($limit, $offset, null, $id);
        
        // Add media, category, and team info to projects
        foreach ($projects as &$project) {
            $project = $this->addMediaToProject($project);
            $project = $this->addCategoryToProject($project);
            $project = $this->addTeamToProject($project);
        }
        
        $category['projects'] = $projects;
        $category['total_projects'] = count($projects);
        
        Response::success($category);
    }
    
    /**
     * Get single category by slug
     */
    public function getCategoryBySlug($slug) {
        $category = $this->projectModel->getCategoryBySlug($slug);
        
        if (!$category) {
            Response::notFound("Category not found");
        }
        
        // Get projects in this category
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $projects = $this->projectModel->getAll($limit, $offset, null, $category['id']);
        
        // Add media, category, and team info to projects
        foreach ($projects as &$project) {
            $project = $this->addMediaToProject($project);
            $project = $this->addCategoryToProject($project);
            $project = $this->addTeamToProject($project);
        }
        
        $category['projects'] = $projects;
        $category['total_projects'] = $this->projectModel->getProjectCountByCategory($category['id']);
        
        Response::success($category);
    }
    
    /**
     * Create a new project category
     */
    public function createCategory() {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            Response::error("Name and slug are required", 422);
        }
        
        $categoryId = $this->projectModel->createCategory($data);
        
        if ($categoryId) {
            Response::success(['id' => $categoryId], "Category created successfully", 201);
        } else {
            Response::error("Failed to create category", 500);
        }
    }
    
    /**
     * Update a project category
     */
    public function updateCategory($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if ($this->projectModel->updateCategory($id, $data)) {
            Response::success(null, "Category updated successfully");
        } else {
            Response::error("Failed to update category", 500);
        }
    }
    
    /**
     * Delete a project category
     */
    public function deleteCategory($id) {
        AuthMiddleware::requireRole(['admin']);
        
        // Check if category has projects
        $projectCount = $this->projectModel->getProjectCountByCategory($id);
        if ($projectCount > 0) {
            Response::error("Cannot delete category with {$projectCount} project(s). Move or delete projects first.", 409);
        }
        
        if ($this->projectModel->deleteCategory($id)) {
            Response::success(null, "Category deleted successfully");
        } else {
            Response::error("Failed to delete category", 500);
        }
    }
    
    public function create() {
        // Check if admin
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if (empty($data['title'])) {
            Response::error("Title is required", 422);
        }
        
        // Handle album creation if images are provided
        if (!empty($data['images']) && is_array($data['images'])) {
            // Create image album
            $albumTitle = $data['title'] . ' - Images';
            $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for project: ' . $data['title']);
            
            if ($albumId) {
                $data['image_album_id'] = $albumId;
                
                // Add images to album
                foreach ($data['images'] as $index => $image) {
                    $this->mediaModel->addImage(
                        $albumId,
                        $image['url'],
                        $image['alt'] ?? $data['title'],
                        $image['caption'] ?? null,
                        $index
                    );
                }
            }
        }
        
        // Handle video album creation if videos are provided
        if (!empty($data['videos']) && is_array($data['videos'])) {
            // Create video album
            $albumTitle = $data['title'] . ' - Videos';
            $albumId = $this->mediaModel->createVideoAlbum($albumTitle, 'Videos for project: ' . $data['title']);
            
            if ($albumId) {
                $data['video_album_id'] = $albumId;
                
                // Add videos to album
                foreach ($data['videos'] as $index => $video) {
                    $this->mediaModel->addVideo(
                        $albumId,
                        $video['url'],
                        $video['title'] ?? $data['title'],
                        $video['description'] ?? null,
                        $video['duration'] ?? null,
                        $video['thumbnail_url'] ?? null,
                        $index
                    );
                }
            }
        }
        
        $projectId = $this->projectModel->create($data);
        
        if ($projectId) {
            Response::success(['id' => $projectId], "Project created successfully", 201);
        } else {
            Response::error("Failed to create project", 500);
        }
    }
    
    public function update($id) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Handle new images addition if provided
        if (!empty($data['new_images']) && is_array($data['new_images'])) {
            $project = $this->projectModel->getById($id);
            
            // Create image album if it doesn't exist
            if (empty($project['image_album_id'])) {
                $albumTitle = $project['title'] . ' - Images';
                $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for project: ' . $project['title']);
                
                if ($albumId) {
                    $this->projectModel->update($id, ['image_album_id' => $albumId]);
                    $data['image_album_id'] = $albumId;
                }
            } else {
                $albumId = $project['image_album_id'];
            }
            
            // Add new images to album
            if (isset($albumId)) {
                foreach ($data['new_images'] as $index => $image) {
                    $this->mediaModel->addImage(
                        $albumId,
                        $image['url'],
                        $image['alt'] ?? $project['title'],
                        $image['caption'] ?? null,
                        $image['display_order'] ?? $index
                    );
                }
                unset($data['new_images']);
            }
        }
        
        // Handle new videos addition if provided
        if (!empty($data['new_videos']) && is_array($data['new_videos'])) {
            $project = $this->projectModel->getById($id);
            
            // Create video album if it doesn't exist
            if (empty($project['video_album_id'])) {
                $albumTitle = $project['title'] . ' - Videos';
                $albumId = $this->mediaModel->createVideoAlbum($albumTitle, 'Videos for project: ' . $project['title']);
                
                if ($albumId) {
                    $this->projectModel->update($id, ['video_album_id' => $albumId]);
                    $data['video_album_id'] = $albumId;
                }
            } else {
                $albumId = $project['video_album_id'];
            }
            
            // Add new videos to album
            if (isset($albumId)) {
                foreach ($data['new_videos'] as $index => $video) {
                    $this->mediaModel->addVideo(
                        $albumId,
                        $video['url'],
                        $video['title'] ?? $project['title'],
                        $video['description'] ?? null,
                        $video['duration'] ?? null,
                        $video['thumbnail_url'] ?? null,
                        $video['display_order'] ?? $index
                    );
                }
                unset($data['new_videos']);
            }
        }
        
        if ($this->projectModel->update($id, $data)) {
            Response::success(null, "Project updated successfully");
        } else {
            Response::error("Failed to update project", 500);
        }
    }
    
    public function delete($id) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        // Get project info before deleting
        $project = $this->projectModel->getById($id);
        
        // Delete associated albums if they exist
        if ($project) {
            if (!empty($project['image_album_id'])) {
                // Delete image album and its images (cascade should handle this)
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM image_album WHERE id = ?");
                $stmt->execute([$project['image_album_id']]);
            }
            
            if (!empty($project['video_album_id'])) {
                // Delete video album and its videos
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM video_album WHERE id = ?");
                $stmt->execute([$project['video_album_id']]);
            }
        }
        
        if ($this->projectModel->delete($id)) {
            Response::success(null, "Project deleted successfully");
        } else {
            Response::error("Failed to delete project", 500);
        }
    }
    
    public function volunteerOpportunities() {
        $opportunities = $this->projectModel->getVolunteerOpportunities();
        
        // Add media, category, and team info to each opportunity
        foreach ($opportunities as &$opportunity) {
            $opportunity = $this->addMediaToProject($opportunity);
            $opportunity = $this->addCategoryToProject($opportunity);
            $opportunity = $this->addTeamToProject($opportunity);
        }
        
        Response::success($opportunities);
    }
    
    /**
     * Add image to existing project
     */
    public function addImage($id) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            Response::notFound("Project not found");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['image_url'])) {
            Response::error("Image URL is required", 422);
        }
        
        // Create image album if it doesn't exist
        if (empty($project['image_album_id'])) {
            $albumTitle = $project['title'] . ' - Images';
            $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for project: ' . $project['title']);
            
            if ($albumId) {
                // Update project with album ID
                $this->projectModel->update($id, ['image_album_id' => $albumId]);
                $project['image_album_id'] = $albumId;
            } else {
                Response::error("Failed to create image album", 500);
            }
        }
        
        // Add image to album
        $imageId = $this->mediaModel->addImage(
            $project['image_album_id'],
            $data['image_url'],
            $data['alt'] ?? $project['title'],
            $data['caption'] ?? null,
            $data['display_order'] ?? 0
        );
        
        if ($imageId) {
            Response::success(['image_id' => $imageId], "Image added successfully", 201);
        } else {
            Response::error("Failed to add image", 500);
        }
    }
    
    /**
     * Add video to existing project
     */
    public function addVideo($id) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            Response::notFound("Project not found");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['video_url'])) {
            Response::error("Video URL is required", 422);
        }
        
        // Create video album if it doesn't exist
        if (empty($project['video_album_id'])) {
            $albumTitle = $project['title'] . ' - Videos';
            $albumId = $this->mediaModel->createVideoAlbum($albumTitle, 'Videos for project: ' . $project['title']);
            
            if ($albumId) {
                // Update project with album ID
                $this->projectModel->update($id, ['video_album_id' => $albumId]);
                $project['video_album_id'] = $albumId;
            } else {
                Response::error("Failed to create video album", 500);
            }
        }
        
        // Add video to album
        $videoId = $this->mediaModel->addVideo(
            $project['video_album_id'],
            $data['video_url'],
            $data['title'] ?? $project['title'],
            $data['description'] ?? null,
            $data['duration'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['display_order'] ?? 0
        );
        
        if ($videoId) {
            Response::success(['video_id' => $videoId], "Video added successfully", 201);
        } else {
            Response::error("Failed to add video", 500);
        }
    }
    
    /**
     * Remove image from project
     */
    public function removeImage($projectId, $imageId) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        if ($this->mediaModel->deleteImage($imageId)) {
            Response::success(null, "Image removed successfully");
        } else {
            Response::error("Failed to remove image", 500);
        }
    }
    
    /**
     * Remove video from project
     */
    public function removeVideo($projectId, $videoId) {
        try {
            AuthMiddleware::requireRole(['admin']);
        } catch (Exception $e) {
            Response::unauthorized("Admin access required");
        }
        
        if ($this->mediaModel->deleteVideo($videoId)) {
            Response::success(null, "Video removed successfully");
        } else {
            Response::error("Failed to remove video", 500);
        }
    }
}
?>