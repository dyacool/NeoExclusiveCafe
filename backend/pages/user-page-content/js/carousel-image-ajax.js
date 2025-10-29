/**
 * Carousel Image AJAX Management
 * 
 * Handles real-time carousel image uploads and deletions to Cloudinary via AJAX
 * Provides instant visual feedback and manages image metadata
 */

// Configuration
const UPLOAD_ENDPOINT = '/backend/api/upload-carousel-image.php';
const DELETE_ENDPOINT = '/backend/api/delete-carousel-image.php';
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

// State management
let uploadingCount = 0;

/**
 * Get CSRF token from hidden field or meta tag
 */
function getCsrfToken() {
    const tokenField = document.getElementById('csrf_token');
    if (tokenField) {
        return tokenField.value;
    }
    
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) {
        return tokenMeta.getAttribute('content');
    }
    
    console.error('CSRF token not found');
    return '';
}

/**
 * Upload carousel image to Cloudinary via AJAX
 * 
 * @param {File} file - Image file to upload
 * @returns {Promise<Object>} Upload result
 */
async function uploadCarouselImageToCloudinary(file) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', getCsrfToken());
    
    // Get title from form if available
    const titleField = document.querySelector('input[name="title"]');
    const title = titleField ? titleField.value : 'Carousel Image';
    formData.append('title', title);
    
    console.log('Uploading carousel image:', file.name);
    
    try {
        showLoadingIndicator();
        uploadingCount++;
        
        const response = await fetch(UPLOAD_ENDPOINT, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            addImagePreview(result.url, result.public_id);
            storeImageMetadata(result.url, result.public_id);
            showSuccessIndicator();
            
            return result;
        } else {
            showErrorMessage(result.error || 'Upload failed');
            return null;
        }
    } catch (error) {
        console.error('Upload error:', error);
        showErrorMessage('Upload failed: ' + error.message);
        return null;
    } finally {
        hideLoadingIndicator();
        uploadingCount--;
    }
}

/**
 * Delete carousel image from Cloudinary via AJAX
 * 
 * @param {string} publicId - Cloudinary public ID
 * @returns {Promise<boolean>} Success status
 */
async function deleteCarouselImageFromCloudinary(publicId) {
    const formData = new FormData();
    formData.append('public_id', publicId);
    formData.append('csrf_token', getCsrfToken());
    
    try {
        showLoadingIndicator();
        
        const response = await fetch(DELETE_ENDPOINT, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            removeImagePreview(publicId);
            removeImageMetadata(publicId);
            return true;
        } else {
            showErrorMessage(result.error || 'Delete failed');
            return false;
        }
    } catch (error) {
        console.error('Delete error:', error);
        showErrorMessage('Delete failed: ' + error.message);
        return false;
    } finally {
        hideLoadingIndicator();
    }
}

/**
 * Store image metadata in hidden form fields
 * 
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 */
function storeImageMetadata(url, publicId) {
    const urlField = document.getElementById('carousel_image_url');
    const idField = document.getElementById('carousel_image_public_id');
    
    if (urlField) urlField.value = url;
    if (idField) idField.value = publicId;
    
    console.log('Stored metadata - URL:', url, 'Public ID:', publicId);
}

/**
 * Remove image metadata from hidden form fields
 * 
 * @param {string} publicId - Cloudinary public ID
 */
function removeImageMetadata(publicId) {
    const urlField = document.getElementById('carousel_image_url');
    const idField = document.getElementById('carousel_image_public_id');
    
    if (urlField) urlField.value = '';
    if (idField) idField.value = '';
    
    console.log('Removed metadata for:', publicId);
}

/**
 * Add image preview to UI
 * 
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 */
function addImagePreview(url, publicId) {
    const previewContainer = document.getElementById('carouselPreviewContainer');
    
    if (!previewContainer) {
        console.error('Preview container not found');
        return;
    }
    
    // Clear any existing preview (only one carousel image at a time)
    previewContainer.innerHTML = '';
    
    const previewDiv = document.createElement('div');
    previewDiv.className = 'image-preview';
    previewDiv.dataset.publicId = publicId;
    
    previewDiv.innerHTML = `
        <img src="${url}" alt="Carousel image" loading="lazy">
        <button type="button" class="remove-image-btn" onclick="handleRemoveCarouselImage('${publicId}')" title="Remove image">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    previewContainer.appendChild(previewDiv);
}

/**
 * Remove image preview from UI
 * 
 * @param {string} publicId - Cloudinary public ID
 */
function removeImagePreview(publicId) {
    const previewDiv = document.querySelector(`.image-preview[data-public-id="${publicId}"]`);
    if (previewDiv) {
        previewDiv.remove();
    }
}

/**
 * Show loading indicator
 */
function showLoadingIndicator() {
    const indicator = document.getElementById('carouselLoadingIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
    }
    
    // Disable upload button during upload
    disableUploadButton(true);
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    const indicator = document.getElementById('carouselLoadingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    
    // Re-enable upload button if no uploads in progress
    if (uploadingCount === 0) {
        disableUploadButton(false);
    }
}

/**
 * Show success indicator briefly
 */
function showSuccessIndicator() {
    const indicator = document.getElementById('carouselSuccessIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }
}

/**
 * Show error message
 * 
 * @param {string} message - Error message
 */
function showErrorMessage(message) {
    // Create error notification
    const errorDiv = document.createElement('div');
    errorDiv.className = 'image-error-notification';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    const container = document.getElementById('carouselPreviewContainer');
    
    if (container) {
        container.appendChild(errorDiv);
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
    
    console.error('Carousel image error:', message);
}

/**
 * Disable/enable upload button
 * 
 * @param {boolean} disabled - Whether to disable button
 */
function disableUploadButton(disabled) {
    const input = document.getElementById('carouselImageInput');
    
    if (input) {
        input.disabled = disabled;
        input.style.opacity = disabled ? '0.5' : '1';
    }
}

/**
 * Handle remove image button click
 * 
 * @param {string} publicId - Cloudinary public ID
 */
async function handleRemoveCarouselImage(publicId) {
    // Disable the remove button to prevent double-clicks
    const previewDiv = document.querySelector(`.image-preview[data-public-id="${publicId}"]`);
    if (previewDiv) {
        const removeBtn = previewDiv.querySelector('.remove-image-btn');
        if (removeBtn) {
            removeBtn.disabled = true;
            removeBtn.style.opacity = '0.5';
        }
    }
    
    const success = await deleteCarouselImageFromCloudinary(publicId);
    
    if (!success && previewDiv) {
        // Re-enable button if deletion failed
        const removeBtn = previewDiv.querySelector('.remove-image-btn');
        if (removeBtn) {
            removeBtn.disabled = false;
            removeBtn.style.opacity = '1';
        }
    }
}

/**
 * Validate file before upload
 * 
 * @param {File} file - File to validate
 * @returns {Object} Validation result
 */
function validateFile(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!allowedTypes.includes(file.type)) {
        return {
            valid: false,
            error: 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'
        };
    }
    
    if (file.size > MAX_FILE_SIZE) {
        return {
            valid: false,
            error: 'File size exceeds 5MB limit.'
        };
    }
    
    return { valid: true };
}

/**
 * Handle carousel image file input change
 */
async function handleCarouselImageChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file
    const validation = validateFile(file);
    if (!validation.valid) {
        showErrorMessage(validation.error);
        event.target.value = ''; // Clear input
        return;
    }
    
    // If there's an existing image, delete it first
    const existingPublicId = document.getElementById('carousel_image_public_id')?.value;
    if (existingPublicId) {
        await deleteCarouselImageFromCloudinary(existingPublicId);
    }
    
    // Upload new image
    await uploadCarouselImageToCloudinary(file);
    
    // Clear input so same file can be selected again if needed
    event.target.value = '';
}

/**
 * Initialize event listeners
 */
function initializeCarouselImageUpload() {
    // Carousel image input
    const carouselInput = document.getElementById('carouselImageInput');
    if (carouselInput) {
        carouselInput.addEventListener('change', handleCarouselImageChange);
        console.log('Carousel image AJAX initialized');
    } else {
        console.warn('Carousel image input not found');
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCarouselImageUpload);
} else {
    initializeCarouselImageUpload();
}

// Make functions globally available
window.handleRemoveCarouselImage = handleRemoveCarouselImage;
window.uploadCarouselImageToCloudinary = uploadCarouselImageToCloudinary;
window.deleteCarouselImageFromCloudinary = deleteCarouselImageFromCloudinary;
