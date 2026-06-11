<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbit Volunteers API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header .version {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        
        .header .description {
            color: #555;
            margin-bottom: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .badge-get { background: #61affe; color: white; }
        .badge-post { background: #49cc90; color: white; }
        .badge-put { background: #fca130; color: white; }
        .badge-delete { background: #f93e3e; color: white; }
        .badge-patch { background: #50e3c2; color: white; }
        
        .content {
            display: flex;
            gap: 30px;
        }
        
        .sidebar {
            width: 280px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .sidebar-nav {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav h3 {
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 8px;
        }
        
        .sidebar-nav a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .sidebar-nav a:hover {
            color: #667eea;
            background: #f0f0f0;
        }
        
        .main-content {
            flex: 1;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section h3 {
            margin: 20px 0 10px 0;
            color: #555;
        }
        
        .endpoint {
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
            padding-left: 20px;
        }
        
        .endpoint-title {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .endpoint-path {
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .endpoint-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .parameters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .parameters table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .parameters th, .parameters td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .parameters th {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .response-example {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #49cc90;
        }
        
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Orbit Volunteers API</h1>
            <div class="version">Version 1.0.0</div>
            <div class="description">
                A comprehensive REST API for managing volunteers, projects, blogs, teams, and more in the Orbit Volunteers platform.
            </div>
            <div>
                <span class="badge">Base URL: http://localhost/orbit_volunteers</span>
                <span class="badge">Authentication: JWT Bearer Token</span>
                <span class="badge">Format: JSON</span>
            </div>
        </div>
        
        <div class="content">
            <div class="sidebar">
                <div class="sidebar-nav">
                    <h3>📚 Documentation</h3>
                    <ul>
                        <li><a href="#authentication">🔐 Authentication</a></li>
                        <li><a href="#users">👥 Users</a></li>
                        <li><a href="#blogs">📝 Blogs</a></li>
                        <li><a href="#blog-categories">🏷️ Blog Categories</a></li>
                        <li><a href="#projects">🚀 Projects</a></li>
                        <li><a href="#project-categories">📁 Project Categories</a></li>
                        <li><a href="#teams">👥 Teams</a></li>
                        <li><a href="#comments">💬 Comments</a></li>
                        <li><a href="#messages">✉️ Messages</a></li>
                        <li><a href="#blocks">📦 Content Blocks</a></li>
                        <li><a href="#media">🖼️ Media</a></li>
                        <li><a href="#error-codes">⚠️ Error Codes</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="main-content">
                <!-- Authentication Section -->
                <div id="authentication" class="section">
                    <h2>🔐 Authentication</h2>
                    <p>The API uses JWT (JSON Web Token) for authentication. Include the token in the Authorization header of all authenticated requests.</p>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/auth/register</span>
                        </div>
                        <div class="endpoint-description">Register a new user account</div>
                        <div class="parameters">
                            <h4>Request Body:</h4>
                            <table>
                                <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                                <tr><td>username</td><td>string</td><td>Yes</td><td>Unique username</td></tr>
                                <tr><td>email</td><td>string</td><td>Yes</td><td>Valid email address</td></tr>
                                <tr><td>password</td><td>string</td><td>Yes</td><td>Minimum 6 characters</td></tr>
                                <tr><td>firstname</td><td>string</td><td>Yes</td><td>First name</td></tr>
                                <tr><td>lastname</td><td>string</td><td>Yes</td><td>Last name</td></tr>
                            </table>
                        </div>
                        <div class="code-block">
{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "firstname": "John",
    "lastname": "Doe"
}
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/auth/login</span>
                        </div>
                        <div class="endpoint-description">Login and get authentication token</div>
                        <div class="code-block">
{
    "email": "admin@orbit.com",
    "password": "admin123"
}
                        </div>
                        <div class="response-example">
                            <strong>Response:</strong>
                            <div class="code-block">
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@orbit.com",
            "role": "admin"
        }
    }
}
                            </div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/auth/me</span>
                        </div>
                        <div class="endpoint-description">Get current authenticated user information</div>
                        <div class="parameters">
                            <strong>Headers:</strong> Authorization: Bearer {token}
                        </div>
                    </div>
                </div>
                
                <!-- Users Section -->
                <div id="users" class="section">
                    <h2>👥 Users</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/users</span>
                        </div>
                        <div class="endpoint-description">Get list of users (Admin only)</div>
                        <div class="parameters">
                            <h4>Query Parameters:</h4>
                            <table>
                                <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                                <tr><td>limit</td><td>int</td><td>Items per page (default: 100)</td></tr>
                                <tr><td>offset</td><td>int</td><td>Pagination offset (default: 0)</td></tr>
                                <tr><td>status</td><td>string</td><td>Filter by status (active/inactive/suspended/pending)</td></tr>
                                <tr><td>role</td><td>string</td><td>Filter by role (admin/volunteer)</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/users/{id}</span>
                        </div>
                        <div class="endpoint-description">Get user by ID with full profile (experience, education, skills)</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-put">PUT</span>
                            <span class="endpoint-path">/users/{id}</span>
                        </div>
                        <div class="endpoint-description">Update user profile</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-delete">DELETE</span>
                            <span class="endpoint-path">/users/{id}</span>
                        </div>
                        <div class="endpoint-description">Delete user (Admin only)</div>
                    </div>
                </div>
                
                <!-- Blogs Section -->
                <div id="blogs" class="section">
                    <h2>📝 Blogs</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blogs</span>
                        </div>
                        <div class="endpoint-description">Get all blog posts</div>
                        <div class="parameters">
                            <h4>Query Parameters:</h4>
                            <table>
                                <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                                <tr><td>limit</td><td>int</td><td>Items per page (default: 50)</td></tr>
                                <tr><td>offset</td><td>int</td><td>Pagination offset (default: 0)</td></tr>
                                <tr><td>category_id</td><td>int</td><td>Filter by category</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blogs/{id}</span>
                        </div>
                        <div class="endpoint-description">Get blog post by ID with full content, author, images, and videos</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/blogs</span>
                        </div>
                        <div class="endpoint-description">Create new blog post (Admin only)</div>
                        <div class="code-block">
{
    "title": "My Blog Post",
    "category_id": 1,
    "description1": "Content here...",
    "images": [
        {"url": "https://example.com/image.jpg", "alt": "Description"}
    ]
}
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blogs/author/{authorId}</span>
                        </div>
                        <div class="endpoint-description">Get blogs by author ID</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blogs/my-blogs</span>
                        </div>
                        <div class="endpoint-description">Get current user's blogs (Requires authentication)</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blogs/images/{id}</span>
                        </div>
                        <div class="endpoint-description">Get images for a specific blog post</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/blogs/{id}/images</span>
                        </div>
                        <div class="endpoint-description">Add image to blog post (Admin only)</div>
                    </div>
                </div>
                
                <!-- Blog Categories Section -->
                <div id="blog-categories" class="section">
                    <h2>🏷️ Blog Categories</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blog-categories</span>
                        </div>
                        <div class="endpoint-description">Get all blog categories</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blog-categories/{id}</span>
                        </div>
                        <div class="endpoint-description">Get category by ID with its blogs</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blog-categories/{slug}</span>
                        </div>
                        <div class="endpoint-description">Get category by slug with its blogs</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/blog-categories</span>
                        </div>
                        <div class="endpoint-description">Create new blog category (Admin only)</div>
                        <div class="code-block">
{
    "name": "Technology",
    "slug": "technology",
    "description": "Tech-related posts"
}
                        </div>
                    </div>
                </div>
                
                <!-- Projects Section -->
                <div id="projects" class="section">
                    <h2>🚀 Projects</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/projects</span>
                        </div>
                        <div class="endpoint-description">Get all projects</div>
                        <div class="parameters">
                            <h4>Query Parameters:</h4>
                            <table>
                                <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                                <tr><td>status</td><td>string</td><td>need_volunteers/in_progress/completed/on_hold</td></tr>
                                <tr><td>category_id</td><td>int</td><td>Filter by category</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/projects/{id}</span>
                        </div>
                        <div class="endpoint-description">Get project by ID with full details, category, team, images, and videos</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/projects/volunteer-opportunities</span>
                        </div>
                        <div class="endpoint-description">Get projects needing volunteers</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/projects</span>
                        </div>
                        <div class="endpoint-description">Create new project (Admin only)</div>
                    </div>
                </div>
                
                <!-- Project Categories Section -->
                <div id="project-categories" class="section">
                    <h2>📁 Project Categories</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/project-categories</span>
                        </div>
                        <div class="endpoint-description">Get all project categories</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/project-categories/{id}</span>
                        </div>
                        <div class="endpoint-description">Get category by ID with its projects</div>
                    </div>
                </div>
                
                <!-- Teams Section -->
                <div id="teams" class="section">
                    <h2>👥 Teams</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/teams</span>
                        </div>
                        <div class="endpoint-description">Get all teams</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/teams/{id}</span>
                        </div>
                        <div class="endpoint-description">Get team by ID with members</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/teams/{id}/members</span>
                        </div>
                        <div class="endpoint-description">Add member to team (Admin only)</div>
                        <div class="code-block">
{
    "user_id": 2,
    "role": "Developer",
    "display_order": 1
}
                        </div>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div id="comments" class="section">
                    <h2>💬 Comments</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/comments/{type}/{id}</span>
                        </div>
                        <div class="endpoint-description">Get comments for blog or project</div>
                        <div class="parameters">
                            <strong>type:</strong> blog or project<br>
                            <strong>id:</strong> The ID of the blog or project
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/comments</span>
                        </div>
                        <div class="endpoint-description">Add a comment</div>
                        <div class="code-block">
{
    "target_type": "blog",
    "target_id": 1,
    "content": "Great article!",
    "username": "Guest"  // Optional if authenticated
}
                        </div>
                    </div>
                </div>
                
                <!-- Messages Section -->
                <div id="messages" class="section">
                    <h2>✉️ Messages</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/messages</span>
                        </div>
                        <div class="endpoint-description">Send a contact message</div>
                        <div class="code-block">
{
    "name": "John Doe",
    "email": "john@example.com",
    "subject": "Question about volunteering",
    "message": "I would like to volunteer..."
}
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/messages</span>
                        </div>
                        <div class="endpoint-description">Get all messages (Admin only)</div>
                    </div>
                </div>
                
                <!-- Blocks Section -->
                <div id="blocks" class="section">
                    <h2>📦 Content Blocks</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blocks</span>
                        </div>
                        <div class="endpoint-description">Get all content blocks</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blocks/category/{slug}</span>
                        </div>
                        <div class="endpoint-description">Get blocks by category slug</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/blocks/children/{id}</span>
                        </div>
                        <div class="endpoint-description">Get child blocks for a parent block</div>
                    </div>
                </div>
                
                <!-- Media Section -->
                <div id="media" class="section">
                    <h2>🖼️ Media</h2>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/upload</span>
                        </div>
                        <div class="endpoint-description">Upload a file (Admin only)</div>
                        <div class="parameters">
                            <strong>Content-Type:</strong> multipart/form-data<br>
                            <strong>Field:</strong> file
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-get">GET</span>
                            <span class="endpoint-path">/albums</span>
                        </div>
                        <div class="endpoint-description">Get all media albums</div>
                    </div>
                    
                    <div class="endpoint">
                        <div class="endpoint-title">
                            <span class="badge badge-post">POST</span>
                            <span class="endpoint-path">/albums</span>
                        </div>
                        <div class="endpoint-description">Create a new album (Admin only)</div>
                        <div class="code-block">
{
    "title": "My Album",
    "type": "image",
    "description": "Album description"
}
                        </div>
                    </div>
                </div>
                
                <!-- Error Codes Section -->
                <div id="error-codes" class="section">
                    <h2>⚠️ Error Codes</h2>
                    <div class="parameters">
                        <table>
                            <tr><th>Code</th><th>Description</th></tr>
                            <tr><td>200</td><td>Success</td></tr>
                            <tr><td>201</td><td>Created successfully</td></tr>
                            <tr><td>400</td><td>Bad request - Invalid parameters</td></tr>
                            <tr><td>401</td><td>Unauthorized - Missing or invalid token</td></tr>
                            <tr><td>403</td><td>Forbidden - Insufficient permissions</td></tr>
                            <tr><td>404</td><td>Not found - Resource doesn't exist</td></tr>
                            <tr><td>422</td><td>Validation error - Invalid data</td></tr>
                            <tr><td>429</td><td>Too many requests - Rate limit exceeded</td></tr>
                            <tr><td>500</td><td>Internal server error</td></tr>
                        </table>
                    </div>
                    
                    <div class="note">
                        <strong>📌 Note:</strong> All endpoints that modify data (POST, PUT, DELETE) require admin authentication except for:
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li><code>POST /auth/register</code> - Public access</li>
                            <li><code>POST /auth/login</code> - Public access</li>
                            <li><code>POST /comments</code> - Public access (with optional auth)</li>
                            <li><code>POST /messages</code> - Public access</li>
                            <li><code>GET /blogs</code> and <code>GET /blogs/{id}</code> - Public access</li>
                            <li><code>GET /projects</code> and <code>GET /projects/{id}</code> - Public access</li>
                        </ul>
                    </div>
                    
                    <div class="note">
                        <strong>🔑 Default Admin Login:</strong><br>
                        Email: admin@orbit.com<br>
                        Password: admin123
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>