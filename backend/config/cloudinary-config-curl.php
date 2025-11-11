<?php
/**
 * Cloudinary Configuration - cURL version (No Composer required)
 */

class CloudinaryConfig {
    private static $instance = null;
    private $cloudName;
    private $apiKey;
    private $apiSecret;
    private $moderationEnabled = false;
    
    private function __construct() {
        // Try to load from .env file if it exists
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $this->loadFromEnv($envFile);
        } else {
            // Use hardcoded credentials
            $this->cloudName = 'dvdccumbs';
            $this->apiKey = '952758222666671';
            $this->apiSecret = 'euo6cTupk7L8lxxV4cf6TWzdiBY';
        }
        
        // Check if moderation is enabled
        $moderationConfigFile = __DIR__ . '/cloudinary-moderation-config.php';
        if (file_exists($moderationConfigFile)) {
            include $moderationConfigFile;
            if (defined('CLOUDINARY_MODERATION_ENABLED')) {
                $this->moderationEnabled = CLOUDINARY_MODERATION_ENABLED;
            }
        }
        
        error_log("CloudinaryConfig initialized - Cloud: {$this->cloudName}, Moderation: " . ($this->moderationEnabled ? 'enabled' : 'disabled'));
    }
    
    private function loadFromEnv($envFile) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            switch ($name) {
                case 'CLOUDINARY_CLOUD_NAME':
                    $this->cloudName = $value;
                    break;
                case 'CLOUDINARY_API_KEY':
                    $this->apiKey = $value;
                    break;
                case 'CLOUDINARY_API_SECRET':
                    $this->apiSecret = $value;
                    break;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCloudName() {
        return $this->cloudName;
    }
    
    public function getApiKey() {
        return $this->apiKey;
    }
    
    public function getApiSecret() {
        return $this->apiSecret;
    }
    
    public function isModerationEnabled() {
        return $this->moderationEnabled;
    }
    
    /**
     * Test connection to Cloudinary
     */
    public function testConnection() {
        $ch = curl_init();
        $url = "https://api.cloudinary.com/v1_1/{$this->cloudName}/usage";
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->apiKey}:{$this->apiSecret}");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $error
            ];
        }
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'message' => 'Connected to Cloudinary successfully',
                'cloud_name' => $this->cloudName
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Authentication failed (HTTP ' . $httpCode . ')',
                'response' => $response
            ];
        }
    }
}
