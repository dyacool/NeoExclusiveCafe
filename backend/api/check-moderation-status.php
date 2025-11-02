<?php
/**
 * Check Moderation Status API
 * 
 * Returns the moderation status for a given Cloudinary public ID
 * Used by frontend to poll for moderation results
 * 
 * @package NeoCafe
 * @version 1.0.0
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type
header('Content-Type: application/json');

// Allow CORS for local development (remove in production if not needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../includes/cloudinary-moderation-helper.php';

/**
 * Validate public ID parameter
 */
function validatePublicId($publicId) {
    if (empty($publicId)) {
        return ['valid' => false, 'error' => 'Missing public_id parameter'];
    }
    
    if (strlen($publicId) > 255) {
        return ['valid' => false, 'error' => 'Invalid public_id length'];
    }
    
    // Basic sanitization
    $publicId = trim($publicId);
    
    return ['valid' => true, 'public_id' => $publicId];
}

/**
 * Get image URL from public ID
 */
function getImageUrl($publicId) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    if (empty($cloudName)) {
        return null;
    }
    
    return "https://res.cloudinary.com/{$cloudName}/image/upload/{$publicId}";
}

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Get public_id parameter
    $publicId = $_GET['public_id'] ?? '';
    
    // Validate public_id
    $validation = validatePublicId($publicId);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $validation['error']]);
        exit;
    }
    
    $publicId = $validation['public_id'];
    
    // Initialize moderation helper
    $moderationHelper = new CloudinaryModerationHelper($conn);
    
    // Check if moderation is enabled
    if (!$moderationHelper->isModerationEnabled()) {
        // If moderation is disabled, return approved status
        http_response_code(200);
        echo json_encode([
            'status' => 'approved',
            'message' => 'Moderation is disabled',
            'public_id' => $publicId,
            'url' => getImageUrl($publicId),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Get moderation status from database
    $moderationData = $moderationHelper->getModerationStatus($publicId);
    
    if ($moderationData) {
        // Moderation result found
        $status = $moderationData['status'];
        $kind = $moderationData['kind'];
        $responseData = $moderationData['response_data'];
        $createdAt = $moderationData['created_at'];
        
        // Extract confidence scores and labels if available
        $labels = [];
        $maxConfidence = 0;
        
        if (isset($responseData['moderation_labels']) && is_array($responseData['moderation_labels'])) {
            foreach ($responseData['moderation_labels'] as $label) {
                if (isset($label['Name']) && isset($label['Confidence'])) {
                    $confidence = floatval($label['Confidence']);
                    $labels[] = [
                        'name' => $label['Name'],
                        'confidence' => round($confidence, 2)
                    ];
                    $maxConfidence = max($maxConfidence, $confidence);
                }
            }
        }
        
        // Prepare response
        $response = [
            'status' => $status,
            'public_id' => $publicId,
            'provider' => $kind,
            'checked_at' => $createdAt,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add URL if approved
        if ($status === 'approved') {
            $response['url'] = getImageUrl($publicId);
        }
        
        // Add rejection details if rejected
        if ($status === 'rejected') {
            $response['rejection_reason'] = $moderationHelper->getErrorMessage('default');
            $response['labels'] = $labels;
            $response['max_confidence'] = round($maxConfidence, 2);
        }
        
        // Add labels for pending status
        if ($status === 'pending' && !empty($labels)) {
            $response['labels'] = $labels;
        }
        
        http_response_code(200);
        echo json_encode($response);
        
    } else {
        // No moderation result found yet - check temp_uploaded_images
        $stmt = $conn->prepare(
            "SELECT moderation_status, moderation_checked_at, cloud_url 
             FROM temp_uploaded_images 
             WHERE public_id = ? 
             LIMIT 1"
        );
        
        $stmt->bind_param("s", $publicId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Image found in temp table
            $tempStatus = $row['moderation_status'] ?? 'pending';
            $checkedAt = $row['moderation_checked_at'];
            $cloudUrl = $row['cloud_url'];
            
            http_response_code(200);
            echo json_encode([
                'status' => $tempStatus,
                'public_id' => $publicId,
                'url' => $cloudUrl,
                'checked_at' => $checkedAt,
                'message' => 'Moderation in progress',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Image not found in either table
            http_response_code(404);
            echo json_encode([
                'error' => 'Image not found',
                'public_id' => $publicId,
                'message' => 'No moderation data available for this image',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Moderation status check error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Failed to check moderation status',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
