<?php
require_once 'models/Blog.php';
require_once 'models/Media.php';

class BlogController {
    private $blogModel;
    private $mediaModel;
    
    public function __construct() {
        $this->blogModel = new Blog();
        $this->mediaModel = new Media();
    }
    
    public function index() {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $categoryId = $_GET['category_id'] ?? null;
        
        $blogs = $this->blogModel->getAll($limit, $offset, $categoryId);
        
        // Add images, videos, author, and category info to each blog
        foreach ($blogs as &$blog) {
            $blog = $this->addMediaToBlog($blog);
            $blog = $this->addAuthorToBlog($blog);
            $blog = $this->addCategoryToBlog($blog);
        }
        
        Response::success($blogs);
    }
    
    public function show($id) {
        $blog = $this->blogModel->getById($id);
        
        if (!$blog) {
            Response::notFound("Blog post not found");
        }
        
        // Add images, videos, author, and category info to the blog
        $blog = $this->addMediaToBlog($blog);
        $blog = $this->addAuthorToBlog($blog);
        $blog = $this->addCategoryToBlog($blog);
        
        Response::success($blog);
    }
    
    /**
     * Add category information to a blog post
     */
    private function addCategoryToBlog($blog) {
        if (!empty($blog['category_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, slug, description, parent_id FROM blog_category WHERE id = ?");
            $stmt->execute([$blog['category_id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get parent category info if exists
            if ($category && !empty($category['parent_id'])) {
                $stmt = $db->prepare("SELECT id, name, slug FROM blog_category WHERE id = ?");
                $stmt->execute([$category['parent_id']]);
                $parentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
                $category['parent'] = $parentCategory;
            }
            
            $blog['category'] = $category;
        } else {
            $blog['category'] = null;
        }
        
        return $blog;
    }
    
    /**
     * Add author information to a blog post
     */
    private function addAuthorToBlog($blog) {
        if (!empty($blog['author_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, photo_url, bio FROM user WHERE id = ?");
            $stmt->execute([$blog['author_id']]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);
            $blog['author'] = $author;
        } else {
            $blog['author'] = null;
        }
        
        return $blog;
    }
    
    /**
     * Add images and videos to a blog post
     */
    private function addMediaToBlog($blog) {
        // Get images from image_album if exists
        if (!empty($blog['image_album_id'])) {
            $blog['images'] = $this->mediaModel->getImages($blog['image_album_id']);
        } else {
            // Also check individual image URLs as fallback
            $blog['images'] = [];
            for ($i = 1; $i <= 4; $i++) {
                $imageKey = "image{$i}_url";
                if (!empty($blog[$imageKey])) {
                    $blog['images'][] = [
                        'id' => null,
                        'image_url' => $blog[$imageKey],
                        'alt' => $blog['title'],
                        'caption' => null,
                        'display_order' => $i
                    ];
                }
            }
        }
        
        // Get videos from video_album if exists
        if (!empty($blog['video_album_id'])) {
            $blog['videos'] = $this->mediaModel->getVideos($blog['video_album_id']);
        } else {
            $blog['videos'] = [];
        }
        
        return $blog;
    }
    
    /**
     * Get images for a specific blog post
     */
    public function getImages($id) {
        $blog = $this->blogModel->getById($id);
        
        if (!$blog) {
            Response::notFound("Blog post not found");
        }
        
        if (!empty($blog['image_album_id'])) {
            $images = $this->mediaModel->getImages($blog['image_album_id']);
            Response::success([
                'blog_id' => $id,
                'blog_title' => $blog['title'],
                'album_id' => $blog['image_album_id'],
                'images' => $images
            ]);
        } else {
            // Return individual images as fallback
            $images = [];
            for ($i = 1; $i <= 4; $i++) {
                $imageKey = "image{$i}_url";
                if (!empty($blog[$imageKey])) {
                    $images[] = [
                        'url' => $blog[$imageKey],
                        'position' => $i
                    ];
                }
            }
            
            Response::success([
                'blog_id' => $id,
                'blog_title' => $blog['title'],
                'album_id' => null,
                'images' => $images
            ]);
        }
    }
    
    /**
     * Get videos for a specific blog post
     */
    public function getVideos($id) {
        $blog = $this->blogModel->getById($id);
        
        if (!$blog) {
            Response::notFound("Blog post not found");
        }
        
        if (!empty($blog['video_album_id'])) {
            $videos = $this->mediaModel->getVideos($blog['video_album_id']);
            Response::success([
                'blog_id' => $id,
                'blog_title' => $blog['title'],
                'album_id' => $blog['video_album_id'],
                'videos' => $videos
            ]);
        } else {
            Response::success([
                'blog_id' => $id,
                'blog_title' => $blog['title'],
                'album_id' => null,
                'videos' => []
            ]);
        }
    }
    
    /**
     * Get all blog categories
     */
    public function categories() {
        $categories = $this->blogModel->getCategories();
        
        // Add blog count to each category
        foreach ($categories as &$category) {
            $blogCount = $this->blogModel->getBlogCountByCategory($category['id']);
            $category['blog_count'] = $blogCount;
        }
        
        Response::success($categories);
    }
    
    /**
     * Get single category by ID with its blogs
     */
    public function getCategoryById($id) {
        $category = $this->blogModel->getCategoryById($id);
        
        if (!$category) {
            Response::notFound("Category not found");
        }
        
        // Get blogs in this category
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $blogs = $this->blogModel->getAll($limit, $offset, $id);
        
        // Add media, author, and category info to blogs
        foreach ($blogs as &$blog) {
            $blog = $this->addMediaToBlog($blog);
            $blog = $this->addAuthorToBlog($blog);
            $blog = $this->addCategoryToBlog($blog);
        }
        
        $category['blogs'] = $blogs;
        $category['total_blogs'] = count($blogs);
        
        Response::success($category);
    }
    
    /**
     * Get single category by slug
     */
    public function getCategoryBySlug($slug) {
        $category = $this->blogModel->getCategoryBySlug($slug);
        
        if (!$category) {
            Response::notFound("Category not found");
        }
        
        // Get blogs in this category
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $blogs = $this->blogModel->getAll($limit, $offset, $category['id']);
        
        // Add media, author, and category info to blogs
        foreach ($blogs as &$blog) {
            $blog = $this->addMediaToBlog($blog);
            $blog = $this->addAuthorToBlog($blog);
            $blog = $this->addCategoryToBlog($blog);
        }
        
        $category['blogs'] = $blogs;
        $category['total_blogs'] = $this->blogModel->getBlogCountByCategory($category['id']);
        
        Response::success($category);
    }
    
    /**
     * Create a new blog category
     */
    public function createCategory() {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            Response::error("Name and slug are required", 422);
        }
        
        $categoryId = $this->blogModel->createCategory($data);
        
        if ($categoryId) {
            Response::success(['id' => $categoryId], "Category created successfully", 201);
        } else {
            Response::error("Failed to create category", 500);
        }
    }
    
    /**
     * Update a blog category
     */
    public function updateCategory($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if ($this->blogModel->updateCategory($id, $data)) {
            Response::success(null, "Category updated successfully");
        } else {
            Response::error("Failed to update category", 500);
        }
    }
    
    /**
     * Delete a blog category
     */
    public function deleteCategory($id) {
        AuthMiddleware::requireRole(['admin']);
        
        // Check if category has blogs
        $blogCount = $this->blogModel->getBlogCountByCategory($id);
        if ($blogCount > 0) {
            Response::error("Cannot delete category with {$blogCount} blog(s). Move or delete blogs first.", 409);
        }
        
        if ($this->blogModel->deleteCategory($id)) {
            Response::success(null, "Category deleted successfully");
        } else {
            Response::error("Failed to delete category", 500);
        }
    }
    
    public function create() {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        // Validate required fields
        if (empty($data['title'])) {
            Response::error("Title is required", 422);
        }
        
        // Get current user as author if author_id not provided
        if (empty($data['author_id'])) {
            $currentUser = AuthMiddleware::authenticate();
            $data['author_id'] = $currentUser['id'];
        }
        
        // Handle album creation if images are provided
        if (!empty($data['images']) && is_array($data['images'])) {
            // Create image album
            $albumTitle = $data['title'] . ' - Images';
            $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for blog: ' . $data['title']);
            
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
            $albumId = $this->mediaModel->createVideoAlbum($albumTitle, 'Videos for blog: ' . $data['title']);
            
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
        
        $blogId = $this->blogModel->create($data);
        
        if ($blogId) {
            Response::success(['id' => $blogId], "Blog post created successfully", 201);
        } else {
            Response::error("Failed to create blog post", 500);
        }
    }
    
    public function update($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);
        
        if ($this->blogModel->update($id, $data)) {
            Response::success(null, "Blog post updated successfully");
        } else {
            Response::error("Failed to update blog post", 500);
        }
    }
    
    public function delete($id) {
        AuthMiddleware::requireRole(['admin']);
        
        // Get blog info before deleting
        $blog = $this->blogModel->getById($id);
        
        // Delete associated albums if they exist
        if ($blog) {
            if (!empty($blog['image_album_id'])) {
                // Delete image album and its images (cascade should handle this)
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM image_album WHERE id = ?");
                $stmt->execute([$blog['image_album_id']]);
            }
            
            if (!empty($blog['video_album_id'])) {
                // Delete video album and its videos
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM video_album WHERE id = ?");
                $stmt->execute([$blog['video_album_id']]);
            }
        }
        
        if ($this->blogModel->delete($id)) {
            Response::success(null, "Blog post deleted successfully");
        } else {
            Response::error("Failed to delete blog post", 500);
        }
    }
    
    /**
     * Add image to existing blog post
     */
    public function addImage($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $blog = $this->blogModel->getById($id);
        
        if (!$blog) {
            Response::notFound("Blog post not found");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['image_url'])) {
            Response::error("Image URL is required", 422);
        }
        
        // Create image album if it doesn't exist
        if (empty($blog['image_album_id'])) {
            $albumTitle = $blog['title'] . ' - Images';
            $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for blog: ' . $blog['title']);
            
            if ($albumId) {
                // Update blog with album ID
                $this->blogModel->update($id, ['image_album_id' => $albumId]);
                $blog['image_album_id'] = $albumId;
            } else {
                Response::error("Failed to create image album", 500);
            }
        }
        
        // Add image to album
        $imageId = $this->mediaModel->addImage(
            $blog['image_album_id'],
            $data['image_url'],
            $data['alt'] ?? $blog['title'],
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
     * Add video to existing blog post
     */
    public function addVideo($id) {
        AuthMiddleware::requireRole(['admin']);
        
        $blog = $this->blogModel->getById($id);
        
        if (!$blog) {
            Response::notFound("Blog post not found");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['video_url'])) {
            Response::error("Video URL is required", 422);
        }
        
        // Create video album if it doesn't exist
        if (empty($blog['video_album_id'])) {
            $albumTitle = $blog['title'] . ' - Videos';
            $albumId = $this->mediaModel->createVideoAlbum($albumTitle, 'Videos for blog: ' . $blog['title']);
            
            if ($albumId) {
                // Update blog with album ID
                $this->blogModel->update($id, ['video_album_id' => $albumId]);
                $blog['video_album_id'] = $albumId;
            } else {
                Response::error("Failed to create video album", 500);
            }
        }
        
        // Add video to album
        $videoId = $this->mediaModel->addVideo(
            $blog['video_album_id'],
            $data['video_url'],
            $data['title'] ?? $blog['title'],
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
     * Get blogs by author
     */
    public function getByAuthor($authorId) {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $blogs = $this->blogModel->getByAuthor($authorId, $limit, $offset);
        
        // Add images, videos, author, and category info to each blog
        foreach ($blogs as &$blog) {
            $blog = $this->addMediaToBlog($blog);
            $blog = $this->addAuthorToBlog($blog);
            $blog = $this->addCategoryToBlog($blog);
        }
        
        Response::success($blogs);
    }
    
    /**
     * Get current user's blogs
     */
    public function getMyBlogs() {
        $currentUser = AuthMiddleware::authenticate();
        $this->getByAuthor($currentUser['id']);
    }
}
?>