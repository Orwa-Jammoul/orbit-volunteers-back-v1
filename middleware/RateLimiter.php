<?php
class RateLimiter {
    private $maxRequests;
    private $timeWindow;
    private $storageDir;
    
    public function __construct($maxRequests = 100, $timeWindow = 3600) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->storageDir = __DIR__ . '/../storage/ratelimit/';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }
    
    public function check($identifier = null) {
        if (!$identifier) {
            $identifier = $_SERVER['REMOTE_ADDR'];
        }
        
        $filename = $this->storageDir . md5($identifier) . '.json';
        $currentTime = time();
        
        $data = [
            'count' => 1,
            'requests' => [$currentTime]
        ];
        
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            
            // Remove old requests
            $data['requests'] = array_filter($data['requests'], function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) <= $this->timeWindow;
            });
            
            $data['count'] = count($data['requests']);
            
            // Check if limit exceeded
            if ($data['count'] >= $this->maxRequests) {
                $oldestRequest = min($data['requests']);
                $resetTime = $oldestRequest + $this->timeWindow;
                $waitTime = $resetTime - $currentTime;
                
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset' => $resetTime,
                    'wait' => $waitTime,
                    'limit' => $this->maxRequests
                ];
            }
            
            $data['requests'][] = $currentTime;
            $data['count']++;
        }
        
        // Save data
        file_put_contents($filename, json_encode($data));
        
        return [
            'allowed' => true,
            'remaining' => $this->maxRequests - $data['count'],
            'reset' => $currentTime + $this->timeWindow,
            'wait' => 0,
            'limit' => $this->maxRequests
        ];
    }
    
    public static function apply($maxRequests = 100, $timeWindow = 3600) {
        $limiter = new self($maxRequests, $timeWindow);
        $result = $limiter->check();
        
        // Add rate limit headers
        header("X-RateLimit-Limit: " . $result['limit']);
        header("X-RateLimit-Remaining: " . $result['remaining']);
        header("X-RateLimit-Reset: " . $result['reset']);
        
        if (!$result['allowed']) {
            header("Retry-After: " . $result['wait']);
            Response::error("Too many requests. Please try again in " . ceil($result['wait'] / 60) . " minutes.", 429);
        }
        
        return $result['allowed'];
    }
}
?>