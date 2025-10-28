<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryConfig {
    private static $instance = null;
    private $cloudinary;
    
    private function __construct() {
        // Load environment variables from .env file (for local development)
        $this->loadEnv();
        
        // Get credentials from environment (works for both Heroku and local)
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
        $apiKey = getenv('CLOUDINARY_API_KEY') ?: $_ENV['CLOUDINARY_API_KEY'] ?? null;
        $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: $_ENV['CLOUDINARY_API_SECRET'] ?? null;
        
        // Fallback to hardcoded values if environment variables not set (temporary for testing)
        if (empty($cloudName)) $cloudName = 'dvdccumbs';
        if (empty($apiKey)) $apiKey = '952758222666671';
        if (empty($apiSecret)) $apiSecret = 'euo6cTupk7L8lxxV4cf6TWzdiBY';
        
        // Initialize Cloudinary
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret
            ],
            'url' => [
                'secure' => true
            ]
        ]);
        
        // Validate credentials
        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            throw new Exception('Cloudinary credentials are not properly configured');
        }
    }
    
    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (!empty($key) && !array_key_exists($key, $_ENV)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCloudinary() {
        return $this->cloudinary;
    }
    
    public function testConnection() {
        try {
            // Try to get account details to verify connection
            $result = $this->cloudinary->adminApi()->ping();
            return [
                'success' => true,
                'message' => 'Successfully connected to Cloudinary',
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Cloudinary: ' . $e->getMessage()
            ];
        }
    }
}
?>
