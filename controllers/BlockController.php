<?php
require_once 'models/Block.php';
require_once 'models/Media.php';

class BlockController
{
    private $blockModel;
    private $mediaModel;

    public function __construct()
    {
        $this->blockModel = new Block();
        $this->mediaModel = new Media();
    }

    public function index()
    {
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        $isActive = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : null;

        $blocks = $this->blockModel->getAll($limit, $offset, $isActive);

        // Add images to each block
        foreach ($blocks as &$block) {
            $block = $this->addMediaToBlock($block);
        }

        Response::success($blocks);
    }

    public function show($id)
    {
        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Block not found");
        }

        // Add images to the block
        $block = $this->addMediaToBlock($block);

        Response::success($block);
    }

    public function getByCategory($slug)
    {
        $isActive = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : true;

        $blocks = $this->blockModel->getByCategory($slug, $isActive);

        // Add images to each block
        foreach ($blocks as &$block) {
            $block = $this->addMediaToBlock($block);
        }

        Response::success($blocks);
    }

    /**
     * Add images to a block
     * Note: Blocks table doesn't have image_album_id or video_album_id fields
     * based on your schema, so we use individual image URLs
     */
    private function addMediaToBlock($block)
    {
        // Blocks use individual image URLs (image1_url, image2_url, etc.)
        $block['images'] = [];
        for ($i = 1; $i <= 4; $i++) {
            $imageKey = "image{$i}_url";
            if (!empty($block[$imageKey])) {
                $block['images'][] = [
                    'id' => null,
                    'image_url' => $block[$imageKey],
                    'image_base64' => $this->mediaModel->getImageBase64FromUrl($block[$imageKey]),
                    'alt' => $block['title'],
                    'caption' => null,
                    'display_order' => $i,
                    'position' => $i
                ];
            }
        }

        // If you add image_album_id to blocks table in the future, use this:
        if (!empty($block['image_album_id'])) {
            $albumImages = $this->mediaModel->getImages($block['image_album_id']);
            if (!empty($albumImages)) {
                $block['images'] = array_merge($block['images'], $albumImages);
            }
        }

        // Blocks typically don't have videos, but if added in the future
        $block['videos'] = [];
        if (!empty($block['video_album_id'])) {
            $block['videos'] = $this->mediaModel->getVideos($block['video_album_id']);
        }

        return $block;
    }

    /**
     * Get images for a specific block
     */
    public function getImages($id)
    {
        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Block not found");
        }

        $images = [];
        for ($i = 1; $i <= 4; $i++) {
            $imageKey = "image{$i}_url";
            if (!empty($block[$imageKey])) {
                $images[] = [
                    'url' => $block[$imageKey],
                    'base64' => $this->mediaModel->getImageBase64FromUrl($block[$imageKey]),
                    'position' => $i,
                    'key' => $imageKey
                ];
            }
        }

        // Check for album images if image_album_id exists
        if (!empty($block['image_album_id'])) {
            $albumImages = $this->mediaModel->getImages($block['image_album_id']);
            $images = array_merge($images, $albumImages);
        }

        Response::success([
            'block_id' => $id,
            'block_title' => $block['title'],
            'category' => $block['category_name'] ?? null,
            'images' => $images
        ]);
    }

    /**
     * Update block image
     */
    public function updateImage($id)
    {
        AuthMiddleware::requireRole(['admin']);

        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Block not found");
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['image_url']) || empty($data['position'])) {
            Response::error("Image URL and position are required", 422);
        }

        $position = $data['position'];
        if ($position < 1 || $position > 4) {
            Response::error("Position must be between 1 and 4", 422);
        }

        $imageField = "image{$position}_url";
        $updateData = [$imageField => $data['image_url']];

        if ($this->blockModel->update($id, $updateData)) {
            Response::success(null, "Block image updated successfully");
        } else {
            Response::error("Failed to update block image", 500);
        }
    }

    public function create()
    {
        AuthMiddleware::requireRole(['admin']);

        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);

        // Validate required fields
        if (empty($data['title'])) {
            Response::error("Title is required", 422);
        }

        // Handle image album creation if multiple images are provided
        if (!empty($data['images']) && is_array($data['images'])) {
            // Create image album
            $albumTitle = $data['title'] . ' - Images';
            $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for block: ' . $data['title']);

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

        $blockId = $this->blockModel->create($data);

        if ($blockId) {
            Response::success(['id' => $blockId], "Block created successfully", 201);
        } else {
            Response::error("Failed to create block", 500);
        }
    }

    public function update($id)
    {
        AuthMiddleware::requireRole(['admin']);

        $data = json_decode(file_get_contents('php://input'), true);
        $data = Validator::sanitizeArray($data);

        // Handle new images addition if provided
        if (!empty($data['new_images']) && is_array($data['new_images'])) {
            $block = $this->blockModel->getById($id);

            // Create image album if it doesn't exist and we're adding multiple images
            if (empty($block['image_album_id']) && count($data['new_images']) > 1) {
                $albumTitle = $block['title'] . ' - Images';
                $albumId = $this->mediaModel->createImageAlbum($albumTitle, 'Images for block: ' . $block['title']);

                if ($albumId) {
                    // Migrate existing individual images to album
                    for ($i = 1; $i <= 4; $i++) {
                        $imageField = "image{$i}_url";
                        if (!empty($block[$imageField])) {
                            $this->mediaModel->addImage(
                                $albumId,
                                $block[$imageField],
                                $block['title'],
                                null,
                                $i
                            );
                        }
                    }

                    $this->blockModel->update($id, ['image_album_id' => $albumId]);
                    $data['image_album_id'] = $albumId;
                }
            }

            // Add new images to album
            if (!empty($block['image_album_id']) || !empty($data['image_album_id'])) {
                $albumId = $data['image_album_id'] ?? $block['image_album_id'];
                if ($albumId) {
                    foreach ($data['new_images'] as $index => $image) {
                        $this->mediaModel->addImage(
                            $albumId,
                            $image['url'],
                            $image['alt'] ?? $block['title'],
                            $image['caption'] ?? null,
                            $image['display_order'] ?? $index
                        );
                    }
                    unset($data['new_images']);
                }
            } else {
                // If no album, store as individual images
                foreach ($data['new_images'] as $index => $image) {
                    $position = $image['position'] ?? ($index + 1);
                    if ($position >= 1 && $position <= 4) {
                        $imageField = "image{$position}_url";
                        $data[$imageField] = $image['url'];
                    }
                }
                unset($data['new_images']);
            }
        }

        if ($this->blockModel->update($id, $data)) {
            Response::success(null, "Block updated successfully");
        } else {
            Response::error("Failed to update block", 500);
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireRole(['admin']);

        // Get block info before deleting
        $block = $this->blockModel->getById($id);

        // Delete associated album if it exists
        if ($block && !empty($block['image_album_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM image_album WHERE id = ?");
            $stmt->execute([$block['image_album_id']]);
        }

        // Delete video album if it exists (for future use)
        if ($block && !empty($block['video_album_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM video_album WHERE id = ?");
            $stmt->execute([$block['video_album_id']]);
        }

        if ($this->blockModel->delete($id)) {
            Response::success(null, "Block deleted successfully");
        } else {
            Response::error("Failed to delete block", 500);
        }
    }

    public function categories()
    {
        $categories = $this->blockModel->getCategories();

        // Add block count to each category
        foreach ($categories as &$category) {
            $blocks = $this->blockModel->getByCategory($category['slug'], true);
            $category['block_count'] = count($blocks);
        }

        Response::success($categories);
    }

    /**
     * Get children blocks for a parent block
     */
    public function getChildren($id)
    {
        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Parent block not found");
        }

        $isActive = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : true;
        $children = $this->blockModel->getChildren($id, $isActive);

        // Add images to children blocks
        foreach ($children as &$child) {
            $child = $this->addMediaToBlock($child);
        }

        Response::success([
            'parent' => $block,
            'children' => $children,
            'total_children' => count($children)
        ]);
    }

    /**
     * Get blocks by location URL
     */
    public function getByLocation($locationUrl)
    {
        $isActive = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : true;

        $db = Database::getInstance()->getConnection();
        $sql = "SELECT b.*, bc.name as category_name, bc.slug as category_slug
                FROM block b
                LEFT JOIN block_category bc ON b.category_id = bc.id
                WHERE b.location_url = :location_url";

        if ($isActive) {
            $sql .= " AND b.is_active = 1";
        }

        $sql .= " ORDER BY b.display_order";

        $stmt = $db->prepare($sql);
        $stmt->execute([':location_url' => $locationUrl]);
        $blocks = $stmt->fetchAll();

        // Add images to blocks
        foreach ($blocks as &$block) {
            $block = $this->addMediaToBlock($block);
        }

        Response::success($blocks);
    }

    /**
     * Toggle block active status
     */
    public function toggleStatus($id)
    {
        AuthMiddleware::requireRole(['admin']);

        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Block not found");
        }

        $newStatus = $block['is_active'] ? 0 : 1;

        if ($this->blockModel->update($id, ['is_active' => $newStatus])) {
            Response::success([
                'id' => $id,
                'is_active' => (bool)$newStatus
            ], "Block status updated successfully");
        } else {
            Response::error("Failed to update block status", 500);
        }
    }

    /**
     * Reorder blocks
     */
    public function reorder()
    {
        AuthMiddleware::requireRole(['admin']);

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['blocks']) || !is_array($data['blocks'])) {
            Response::error("Blocks array is required", 422);
        }

        $db = Database::getInstance()->getConnection();
        $success = true;

        foreach ($data['blocks'] as $blockData) {
            if (!isset($blockData['id']) || !isset($blockData['display_order'])) {
                continue;
            }

            $stmt = $db->prepare("UPDATE block SET display_order = :display_order WHERE id = :id");
            $result = $stmt->execute([
                ':display_order' => $blockData['display_order'],
                ':id' => $blockData['id']
            ]);

            if (!$result) {
                $success = false;
            }
        }

        if ($success) {
            Response::success(null, "Blocks reordered successfully");
        } else {
            Response::error("Failed to reorder blocks", 500);
        }
    }

    /**
     * Duplicate a block
     */
    public function duplicate($id)
    {
        AuthMiddleware::requireRole(['admin']);

        $block = $this->blockModel->getById($id);

        if (!$block) {
            Response::notFound("Block not found");
        }

        // Create duplicate data
        $duplicateData = [
            'category_id' => $block['category_id'],
            'parent_id' => $block['parent_id'],
            'title' => $block['title'] . ' (Copy)',
            'description1' => $block['description1'],
            'description2' => $block['description2'],
            'description3' => $block['description3'],
            'description4' => $block['description4'],
            'image1_url' => $block['image1_url'],
            'image2_url' => $block['image2_url'],
            'image3_url' => $block['image3_url'],
            'image4_url' => $block['image4_url'],
            'url' => $block['url'],
            'location_url' => $block['location_url'],
            'icon_text' => $block['icon_text'],
            'display_order' => $block['display_order'] + 1,
            'is_active' => 0 // Set as inactive by default
        ];

        $newBlockId = $this->blockModel->create($duplicateData);

        if ($newBlockId) {
            Response::success(['id' => $newBlockId], "Block duplicated successfully", 201);
        } else {
            Response::error("Failed to duplicate block", 500);
        }
    }
}
