<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove the base path
$base_path = '/orbit_volunteers';
$path = str_replace($base_path, '', strtolower($request_uri));
$path = str_replace(strtolower($script_name), '', $path);
$path = strtok($path, '?');
$path = trim($path, '/');

// Load required files
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';
require_once __DIR__ . '/helpers/JWT.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';

// Handle CORS
CorsMiddleware::handle();

try {
    // Handle empty path
    if (empty($path)) {
        Response::success([
            'api' => 'Orbit Volunteers API',
            'version' => '1.0.0',
            'endpoints' => [
                'Authentication' => [
                    'POST /auth/register' => 'Register new user',
                    'POST /auth/login' => 'Login user',
                    'GET /auth/me' => 'Get current user',
                ],
                'Users' => [
                    'GET /users' => 'List all users',
                    'GET /users/{id}' => 'Get user by ID',
                    'PUT /users/{id}' => 'Update user',
                    'DELETE /users/{id}' => 'Delete user',
                ],
                'Projects' => [
                    'GET /projects' => 'List all projects',
                    'GET /projects/{id}' => 'Get project by ID',
                    'POST /projects' => 'Create project',
                    'PUT /projects/{id}' => 'Update project',
                    'DELETE /projects/{id}' => 'Delete project',
                    'GET /projects/categories' => 'Get project categories',
                    'GET /projects/volunteer-opportunities' => 'Get projects needing volunteers',
                ],
                'Blogs' => [
                    'GET /blogs' => 'List all blogs',
                    'GET /blogs/{id}' => 'Get blog by ID',
                    'POST /blogs' => 'Create blog',
                    'PUT /blogs/{id}' => 'Update blog',
                    'DELETE /blogs/{id}' => 'Delete blog',
                    'GET /blogs/categories' => 'Get blog categories',
                ],
                'Comments' => [
                    'GET /comments/{type}/{id}' => 'Get comments for blog/project',
                    'POST /comments' => 'Add comment',
                    'PUT /comments/{id}/status' => 'Update comment status',
                    'DELETE /comments/{id}' => 'Delete comment',
                ],
                'Messages' => [
                    'POST /messages' => 'Send message',
                    'GET /messages' => 'Get messages (admin)',
                    'GET /messages/{id}' => 'Get message by ID',
                ],
                'Teams' => [
                    'GET /teams' => 'List all teams',
                    'GET /teams/{id}' => 'Get team by ID',
                    'POST /teams' => 'Create team',
                    'PUT /teams/{id}' => 'Update team',
                    'DELETE /teams/{id}' => 'Delete team',
                    'POST /teams/{id}/members' => 'Add member to team',
                    'DELETE /teams/{id}/members/{userId}' => 'Remove member from team',
                ],
                'Blocks' => [
                    'GET /blocks' => 'List content blocks',
                    'GET /blocks/{id}' => 'Get block by ID',
                    'GET /blocks/category/{slug}' => 'Get blocks by category',
                ],
                'Media' => [
                    'POST /upload' => 'Upload file',
                    'GET /albums' => 'Get media albums',
                    'POST /albums' => 'Create album',
                ],
                'Other' => [
                    'GET /health' => 'Health check',
                    'GET /docs' => 'API Documentation',
                ]
            ]
        ]);
        exit;
    }

    // Split the path into segments
    // $path = str_replace($base_path, '', $request_uri);
    // print_r($path) ;
    // print_r($request_uri) ;
    // $path = str_replace($base_path, $path, '');
    $segments = explode('/', $path);
    $endpoint = strtolower($segments[0]);
    // print_r($segments) ;
    // print_r($endpoint) ;
    // print_r($script_name) ;
    // print("\n");
    // return;
    $id = $segments[1] ?? null;
    $sub_id = $segments[2] ?? null;

    // Load controllers as needed
    switch ($endpoint) {
        case 'health':
            Response::success(['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        case 'docs':
            header('Content-Type: text/html');
            if (file_exists(__DIR__ . '/docs.php')) {
                include __DIR__ . '/docs.php';
            } else {
                echo "<h1>API Documentation</h1>";
                echo "<p>Orbit Volunteers API is running.</p>";
                echo "<p>Available endpoints are listed at the root URL.</p>";
            }
            break;

        // ==================== AUTHENTICATION ====================
        case 'auth':
            require_once __DIR__ . '/controllers/AuthController.php';
            $auth = new AuthController();

            if ($id === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->register();
            } elseif ($id === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->login();
            } elseif ($id === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $auth->me();
            } else {
                Response::notFound("Auth endpoint '{$id}' not found");
            }
            break;

        // ==================== USERS ====================
        case 'users':
            require_once __DIR__ . '/controllers/UserController.php';
            $userController = new UserController();

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if ($id) {
                    $userController->show($id);
                } else {
                    $userController->index();
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
                $userController->update($id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
                $userController->delete($id);
            } else {
                Response::error("Method not allowed", 405);
            }
            break;

        // ==================== PROJECTS ====================
        case 'projects':
            require_once __DIR__ . '/controllers/ProjectController.php';
            $projectController = new ProjectController();

            // Handle special sub-endpoints
            if ($id === 'categories' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $projectController->categories();
            } elseif ($id === 'volunteer-opportunities' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $projectController->volunteerOpportunities();
            } elseif ($id && is_numeric($id)) {
                // Handle /projects/{id}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $projectController->show($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $projectController->update($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $projectController->delete($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                // Handle /projects
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $projectController->index();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $projectController->create();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;

        // ==================== PROJECTS categories ====================
        case 'project-categories':
            require_once __DIR__ . '/controllers/ProjectController.php';
            $projectController = new ProjectController();

            if ($id && is_numeric($id)) {
                // GET /project-categories/{id}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $projectController->getCategoryById($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $projectController->updateCategory($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $projectController->deleteCategory($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } elseif ($id && !is_numeric($id)) {
                // GET /project-categories/slug/{slug}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $projectController->getCategoryBySlug($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                // GET /project-categories
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $projectController->categories();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $projectController->createCategory();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;
        // ==================== BLOGS ====================
        case 'blogs':
            require_once __DIR__ . '/controllers/BlogController.php';
            $blogController = new BlogController();

            // Handle sub-endpoints
            if ($id === 'images' && $sub_id && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // GET /blogs/images/{id}
                $blogController->getImages($sub_id);
            } elseif ($id === 'videos' && $sub_id && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // GET /blogs/videos/{id}
                $blogController->getVideos($sub_id);
            } elseif ($id === 'author' && $sub_id && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // GET /blogs/author/{authorId}
                $blogController->getByAuthor($sub_id);
            } elseif ($id === 'my-blogs' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // GET /blogs/my-blogs
                $blogController->getMyBlogs();
            } elseif ($id && $sub_id === 'images' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // POST /blogs/{id}/images
                $blogController->addImage($id);
            } elseif ($id && $sub_id === 'videos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // POST /blogs/{id}/videos
                $blogController->addVideo($id);
            } elseif ($id && is_numeric($id)) {
                // Handle /blogs/{id}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blogController->show($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $blogController->update($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $blogController->delete($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                // Handle /blogs
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blogController->index();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $blogController->create();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;

        // ==================== BLOGS Categories ====================
        case 'blog-categories':
            require_once __DIR__ . '/controllers/BlogController.php';
            $blogController = new BlogController();

            if ($id && is_numeric($id)) {
                // GET /blog-categories/{id}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blogController->getCategoryById($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $blogController->updateCategory($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $blogController->deleteCategory($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } elseif ($id && !is_numeric($id)) {
                // GET /blog-categories/slug/{slug}
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blogController->getCategoryBySlug($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                // GET /blog-categories
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blogController->categories();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $blogController->createCategory();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;
        // ==================== COMMENTS ====================
        case 'comments':
            require_once __DIR__ . '/controllers/CommentController.php';
            $commentController = new CommentController();

            if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id && $sub_id) {
                // /comments/{type}/{id}
                $commentController->index($id, $sub_id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id && is_numeric($id) && $sub_id === 'status') {
                // /comments/{id}/status
                $commentController->updateStatus($id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id && is_numeric($id)) {
                // /comments/{id}
                $commentController->update($id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id && is_numeric($id)) {
                // /comments/{id}
                $commentController->delete($id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // POST /comments
                $commentController->create();
            } else {
                Response::notFound("Comment endpoint not found");
            }
            break;

        // ==================== MESSAGES ====================
        case 'messages':
            require_once __DIR__ . '/controllers/MessageController.php';
            $messageController = new MessageController();

            if ($id && is_numeric($id) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $messageController->show($id);
            } elseif ($id === 'status' && $sub_id && $_SERVER['REQUEST_METHOD'] === 'PUT') {
                $messageController->updateStatus($id);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $messageController->index();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $messageController->create();
            } else {
                Response::error("Method not allowed", 405);
            }
            break;

        // ==================== TEAMS ====================
        case 'teams':
            require_once __DIR__ . '/controllers/TeamController.php';
            $teamController = new TeamController();

            if ($id && is_numeric($id)) {
                if ($sub_id === 'members' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    // POST /teams/{id}/members
                    $teamController->addMember($id);
                } elseif ($sub_id === 'members' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    // DELETE /teams/{id}/members/{userId}
                    $teamController->removeMember($id, $sub_id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $teamController->show($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $teamController->update($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $teamController->delete($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $teamController->index();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $teamController->create();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;

        // ==================== BLOCKS ====================
        case 'blocks':
            require_once __DIR__ . '/controllers/BlockController.php';
            $blockController = new BlockController();

            if ($id === 'category' && $sub_id && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // /blocks/category/{slug}
                $blockController->getByCategory($sub_id);
            } elseif ($id && is_numeric($id)) {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blockController->show($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $blockController->update($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    $blockController->delete($id);
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $blockController->index();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $blockController->create();
                } else {
                    Response::error("Method not allowed", 405);
                }
            }
            break;

        // ==================== MEDIA ====================
        case 'media':
        case 'upload':
            require_once __DIR__ . '/controllers/MediaController.php';
            $mediaController = new MediaController();

            if ($endpoint === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $mediaController->upload();
            } elseif ($endpoint === 'albums') {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $mediaController->getAlbums();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $mediaController->createAlbum();
                } else {
                    Response::error("Method not allowed", 405);
                }
            } else {
                Response::notFound("Media endpoint not found");
            }
            break;

        default:
            print('not founddddddddd');
            Response::notFound("Endpoint '{$endpoint}' not found. Available endpoints: auth, users, projects, blogs, comments, messages, teams, blocks, media, health, docs");
            break;
    }
} catch (Exception $e) {
    print('errorrrrrrrr');
    Response::error($e->getMessage(), 500);
}
