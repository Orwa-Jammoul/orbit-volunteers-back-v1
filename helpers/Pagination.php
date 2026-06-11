<?php
class Pagination {
    public static function paginate($totalItems, $currentPage = 1, $itemsPerPage = 20) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        return [
            'current_page' => $currentPage,
            'items_per_page' => $itemsPerPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }
    
    public static function getLinks($baseUrl, $currentPage, $totalPages) {
        $links = [];
        
        // Previous link
        if ($currentPage > 1) {
            $links[] = [
                'url' => $baseUrl . '?page=' . ($currentPage - 1),
                'label' => 'Previous',
                'active' => false
            ];
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        if ($start > 1) {
            $links[] = [
                'url' => $baseUrl . '?page=1',
                'label' => '1',
                'active' => false
            ];
            if ($start > 2) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false
                ];
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $links[] = [
                'url' => $baseUrl . '?page=' . $i,
                'label' => (string)$i,
                'active' => $i === $currentPage
            ];
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false
                ];
            }
            $links[] = [
                'url' => $baseUrl . '?page=' . $totalPages,
                'label' => (string)$totalPages,
                'active' => false
            ];
        }
        
        // Next link
        if ($currentPage < $totalPages) {
            $links[] = [
                'url' => $baseUrl . '?page=' . ($currentPage + 1),
                'label' => 'Next',
                'active' => false
            ];
        }
        
        return $links;
    }
}
?>