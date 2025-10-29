/**
 * Product Image AJAX Management
 * 
 * Handles real-time image uploads and deletions to Cloudinary via AJAX
 * Provides instant visual feedback and manages image metadata
 */

// Configuration
const MAX_ADDITIONAL_IMAGES = 3;
const UPLOAD_ENDPOINT = '/backend/api/upload-product-image.php';
const DELETE_ENDPOINT = '/backend/api/delete-product-image.php';

// State management
let uploadingCount = 0;
let additionalImagesCount = 0;

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
 * Upload image to Cloudinary via AJAX
 * 
 * @param {File} file - Image file to upload
 * @param {string} imageType - 'primary' or 'additional'
 * @returns {Promise<Object>} Upload result
 */
async function uploadImageToCloudinary(file, imageType) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('image_type', imageType);
    formData.append('csrf_token', getCsrfToken());
    
    try {
        showLoadingIndicator(imageType);
        uploadingCount++;
        
        const response = await fetch(UPLOAD_ENDPOINT, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            addImagePreview(result.url, result.public_id, imageType);
            storeImageMetadata(result.url, result.public_id, imageType);
            showSuccessIndicator(imageType);
            
            if (imageType === 'additional') {
                additionalImagesCount++;
                updateAdditionalImagesButton();
            }
            
            return result;
        } else {
            showErrorMessage(result.error || 'Upload failed', imageType);
            return null;
        }
    } catch (error) {
        console.error('Upload error:', error);
        showErrorMessage('Upload failed: ' + error.message, imageType);
        return null;
    } finally {
        hideLoadingIndicator(imageType);
        uploadingCount--;
    }
}

/**
 * Delete image from Cloudinary via AJAX
 * 
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 * @returns {Promise<boolean>} Success status
 */
async function deleteImageFromCloudinary(publicId, imageType) {
    const formData = new FormData();
    formData.append('public_id', publicId);
    formData.append('csrf_token', getCsrfToken());
    
    try {
        showLoadingIndicator(imageType);
        
        const response = await fetch(DELETE_ENDPOINT, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            removeImagePreview(publicId);
            removeImageMetadata(publicId, imageType);
            
            if (imageType === 'additional') {
                additionalImagesCount--;
                updateAdditionalImagesButton();
            }
            
            return true;
        } else {
            showErrorMessage(result.error || 'Delete failed', imageType);
            return false;
        }
    } catch (error) {
        console.error('Delete error:', error);
        showErrorMessage('Delete failed: ' + error.message, imageType);
        return false;
    } finally {
        hideLoadingIndicator(imageType);
    }
}

/**
 * Store image metadata in hidden form fields
 * 
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function storeImageMetadata(url, publicId, imageType) {
    if (imageType === 'primary') {
        const urlField = document.getElementById('primary_image_url');
        const idField = document.getElementById('primary_image_public_id');
        
        if (urlField) urlField.value = url;
        if (idField) idField.value = publicId;
    } else {
        const urlsField = document.getElementById('additional_image_urls');
        const idsField = document.getElementById('additional_image_public_ids');
        
        if (urlsField && idsField) {
            const urls = urlsField.value ? JSON.parse(urlsField.value) : [];
            const ids = idsField.value ? JSON.parse(idsField.value) : [];
            
            urls.push(url);
            ids.push(publicId);
            
            urlsField.value = JSON.stringify(urls);
            idsField.value = JSON.stringify(ids);
        }
    }
}

/**
 * Remove image metadata from hidden form fields
 * 
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function removeImageMetadata(publicId, imageType) {
    if (imageType === 'primary') {
        const urlField = document.getElementById('primary_image_url');
        const idField = document.getElementById('primary_image_public_id');
        
        if (urlField) urlField.value = '';
        if (idField) idField.value = '';
    } else {
        const urlsField = document.getElementById('additional_image_urls');
        const idsField = document.getElementById('additional_image_public_ids');
        
        if (urlsField && idsField) {
            const urls = urlsField.value ? JSON.parse(urlsField.value) : [];
            const ids = idsField.value ? JSON.parse(idsField.value) : [];
            
            const index = ids.indexOf(publicId);
            if (index > -1) {
                urls.splice(index, 1);
                ids.splice(index, 1);
            }
            
            urlsField.value = JSON.stringify(urls);
            idsField.value = JSON.stringify(ids);
        }
    }
}

/**
 * Add image preview to UI
 * 
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function addImagePreview(url, publicId, imageType) {
    const previewContainer = imageType === 'primary' 
        ? document.getElementById('primaryPreviewContainer')
        : document.getElementById('additionalPreviewContainer');
    
    if (!previewContainer) {
        console.error('Preview container not found for type:', imageType);
        return;
    }
    
    const previewDiv = document.createElement('div');
    previewDiv.className = 'image-preview';
    previewDiv.dataset.publicId = publicId;
    previewDiv.dataset.imageType = imageType;
    
    previewDiv.innerHTML = `
        <img src="${url}" alt="Product image" loading="lazy">
        <button type="button" class="remove-image-btn" onclick="handleRemoveImage('${publicId}', '${imageType}')" title="Remove image">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    if (imageType === 'primary') {
        // Replace existing primary image
        previewContainer.innerHTML = '';
    }
    
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
 * 
 * @param {string} imageType - 'primary' or 'additional'
 */
function showLoadingIndicator(imageType) {
    const indicator = document.getElementById(imageType + 'LoadingIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
    }
    
    // Disable upload buttons during upload
    disableUploadButtons(true);
}

/**
 * Hide loading indicator
 * 
 * @param {string} imageType - 'primary' or 'additional'
 */
function hideLoadingIndicator(imageType) {
    const indicator = document.getElementById(imageType + 'LoadingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    
    // Re-enable upload buttons if no uploads in progress
    if (uploadingCount === 0) {
        disableUploadButtons(false);
    }
}

/**
 * Show success indicator briefly
 * 
 * @param {string} imageType - 'primary' or 'additional'
 */
function showSuccessIndicator(imageType) {
    const indicator = document.getElementById(imageType + 'SuccessIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 2000);
    }
}

/**
 * Show error message
 * 
 * @param {string} message - Error message
 * @param {string} imageType - 'primary' or 'additional'
 */
function showErrorMessage(message, imageType) {
    // Create error notification
    const errorDiv = document.createElement('div');
    errorDiv.className = 'image-error-notification';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    const container = imageType === 'primary'
        ? document.getElementById('primaryPreviewContainer')
        : document.getElementById('additionalPreviewContainer');
    
    if (container) {
        container.appendChild(errorDiv);
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
    
    console.error('Image error:', message);
}

/**
 * Disable/enable upload buttons
 * 
 * @param {boolean} disabled - Whether to disable buttons
 */
function disableUploadButtons(disabled) {
    const primaryBtn = document.getElementById('primaryUploadBtn');
    const additionalBtn = document.getElementById('additionalUploadBtn');
    
    if (primaryBtn) {
        primaryBtn.style.pointerEvents = disabled ? 'none' : 'auto';
        primaryBtn.style.opacity = disabled ? '0.5' : '1';
    }
    
    if (additionalBtn) {
        additionalBtn.style.pointerEvents = disabled ? 'none' : 'auto';
        additionalBtn.style.opacity = disabled ? '0.5' : '1';
    }
}

/**
 * Update additional images button state
 */
function updateAdditionalImagesButton() {
    const btn = document.getElementById('additionalUploadBtn');
    const input = document.getElementById('additionalImagesInput');
    
    if (additionalImagesCount >= MAX_ADDITIONAL_IMAGES) {
        if (btn) {
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
            btn.textContent = `Maximum ${MAX_ADDITIONAL_IMAGES} images reached`;
        }
        if (input) {
            input.disabled = true;
        }
    } else {
        if (btn) {
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
            btn.textContent = `Click to Upload Additional Images (${additionalImagesCount}/${MAX_ADDITIONAL_IMAGES})`;
        }
        if (input) {
            input.disabled = false;
        }
    }
}

/**
 * Handle remove image button click
 * 
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
async function handleRemoveImage(publicId, imageType) {
    // Optional: Confirm deletion
    // if (!confirm('Are you sure you want to remove this image?')) {
    //     return;
    // }
    
    // Disable the remove button to prevent double-clicks
    const previewDiv = document.querySelector(`.image-preview[data-public-id="${publicId}"]`);
    if (previewDiv) {
        const removeBtn = previewDiv.querySelector('.remove-image-btn');
        if (removeBtn) {
            removeBtn.disabled = true;
            removeBtn.style.opacity = '0.5';
        }
    }
    
    const success = await deleteImageFromCloudinary(publicId, imageType);
    
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
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!allowedTypes.includes(file.type)) {
        return {
            valid: false,
            error: 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'
        };
    }
    
    if (file.size > maxSize) {
        return {
            valid: false,
            error: 'File size exceeds 10MB limit.'
        };
    }
    
    return { valid: true };
}

/**
 * Handle primary image file input change
 */
async function handlePrimaryImageChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file
    const validation = validateFile(file);
    if (!validation.valid) {
        showErrorMessage(validation.error, 'primary');
        event.target.value = ''; // Clear input
        return;
    }
    
    // If there's an existing primary image, delete it first
    const existingPublicId = document.getElementById('primary_image_public_id')?.value;
    if (existingPublicId) {
        await deleteImageFromCloudinary(existingPublicId, 'primary');
    }
    
    // Upload new image
    await uploadImageToCloudinary(file, 'primary');
    
    // Clear input so same file can be selected again if needed
    event.target.value = '';
}

/**
 * Handle additional images file input change
 */
async function handleAdditionalImagesChange(event) {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;
    
    // Check if we can add more images
    const remainingSlots = MAX_ADDITIONAL_IMAGES - additionalImagesCount;
    if (remainingSlots <= 0) {
        showErrorMessage(`Maximum ${MAX_ADDITIONAL_IMAGES} additional images allowed.`, 'additional');
        event.target.value = '';
        return;
    }
    
    // Limit files to remaining slots
    const filesToUpload = files.slice(0, remainingSlots);
    
    if (files.length > remainingSlots) {
        showErrorMessage(`Only ${remainingSlots} more image(s) can be added.`, 'additional');
    }
    
    // Upload files sequentially
    for (const file of filesToUpload) {
        // Validate file
        const validation = validateFile(file);
        if (!validation.valid) {
            showErrorMessage(validation.error, 'additional');
            continue;
        }
        
        // Upload image
        await uploadImageToCloudinary(file, 'additional');
    }
    
    // Clear input
    event.target.value = '';
}

/**
 * Initialize event listeners
 */
function initializeImageUpload() {
    // Primary image input
    const primaryInput = document.getElementById('primaryImageInput');
    if (primaryInput) {
        primaryInput.addEventListener('change', handlePrimaryImageChange);
    }
    
    // Additional images input
    const additionalInput = document.getElementById('additionalImagesInput');
    if (additionalInput) {
        additionalInput.addEventListener('change', handleAdditionalImagesChange);
    }
    
    // Count existing additional images on page load
    const existingAdditionalImages = document.querySelectorAll('#additionalPreviewContainer .image-preview');
    additionalImagesCount = existingAdditionalImages.length;
    updateAdditionalImagesButton();
    
    console.log('Product image AJAX initialized');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeImageUpload);
} else {
    initializeImageUpload();
}

// Make functions globally available
window.handleRemoveImage = handleRemoveImage;
window.uploadImageToCloudinary = uploadImageToCloudinary;
window.deleteImageFromCloudinary = deleteImageFromCloudinary;
