/**
 * Admin Profile Picture AJAX Management
 * 
 * Handles real-time admin profile picture uploads and deletions to Cloudinary via AJAX
 * Provides instant visual feedback and updates avatar display
 */

// Configuration
const UPLOAD_ENDPOINT = '/backend/api/upload-profile-picture.php';
const DELETE_ENDPOINT = '/backend/api/delete-profile-picture.php';
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

// State management
let isUploading = false;

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
 * Upload profile picture to Cloudinary via AJAX
 * 
 * @param {File} file - Image file to upload
 * @returns {Promise<Object>} Upload result
 */
async function uploadProfilePictureToCloudinary(file) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', getCsrfToken());
    
    console.log('Uploading profile picture:', file.name);
    
    try {
        showLoadingIndicator();
        isUploading = true;
        
        const response = await fetch(UPLOAD_ENDPOINT, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateAvatarDisplay(result.url, result.public_id);
            showSuccessMessage('Profile picture updated successfully!');
            
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
        isUploading = false;
    }
}

/**
 * Delete profile picture from Cloudinary via AJAX
 * 
 * @param {string} publicId - Cloudinary public ID
 * @returns {Promise<boolean>} Success status
 */
async function deleteProfilePictureFromCloudinary(publicId) {
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
            revertToInitials();
            showSuccessMessage('Profile picture removed successfully!');
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
 * Update avatar display with new image
 * 
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 */
function updateAvatarDisplay(url, publicId) {
    const avatar = document.getElementById('avatar');
    if (!avatar) return;
    
    // Remove existing content
    avatar.innerHTML = '';
    
    // Create new image element
    const img = document.createElement('img');
    img.id = 'profile-image';
    img.src = url + '?t=' + Date.now(); // Cache bust
    img.alt = 'Profile picture';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.style.borderRadius = '50%';
    
    avatar.appendChild(img);
    
    // Update or add remove button
    updateRemoveButton(publicId);
    
    console.log('Avatar updated with new image:', url);
}

/**
 * Revert avatar to initials display
 */
function revertToInitials() {
    const avatar = document.getElementById('avatar');
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    
    if (!avatar || !firstnameInput || !lastnameInput) return;
    
    const firstname = firstnameInput.value || '';
    const lastname = lastnameInput.value || '';
    const initials = (firstname.charAt(0) + lastname.charAt(0)).toUpperCase();
    
    // Remove existing content
    avatar.innerHTML = '';
    
    // Create initials span
    const initialsSpan = document.createElement('span');
    initialsSpan.id = 'initials';
    initialsSpan.textContent = initials;
    
    avatar.appendChild(initialsSpan);
    
    // Remove the remove button
    const removeBtn = document.getElementById('remove-avatar-btn');
    if (removeBtn) {
        removeBtn.remove();
    }
    
    console.log('Avatar reverted to initials:', initials);
}

/**
 * Update or add remove button
 * 
 * @param {string} publicId - Cloudinary public ID
 */
function updateRemoveButton(publicId) {
    const container = document.getElementById('avatar-upload-container');
    if (!container) return;
    
    // Remove existing button if present
    let removeBtn = document.getElementById('remove-avatar-btn');
    if (removeBtn) {
        removeBtn.remove();
    }
    
    // Create new remove button
    removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.id = 'remove-avatar-btn';
    removeBtn.className = 'remove-avatar-btn';
    removeBtn.dataset.publicId = publicId;
    removeBtn.title = 'Remove profile picture';
    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
    removeBtn.onclick = () => handleRemoveProfilePicture(publicId);
    
    container.appendChild(removeBtn);
}

/**
 * Handle remove profile picture button click
 * 
 * @param {string} publicId - Cloudinary public ID
 */
async function handleRemoveProfilePicture(publicId) {
    if (!confirm('Are you sure you want to remove your profile picture?')) {
        return;
    }
    
    // Disable the remove button to prevent double-clicks
    const removeBtn = document.getElementById('remove-avatar-btn');
    if (removeBtn) {
        removeBtn.disabled = true;
        removeBtn.style.opacity = '0.5';
    }
    
    const success = await deleteProfilePictureFromCloudinary(publicId);
    
    if (!success && removeBtn) {
        // Re-enable button if deletion failed
        removeBtn.disabled = false;
        removeBtn.style.opacity = '1';
    }
}

/**
 * Show loading indicator
 */
function showLoadingIndicator() {
    const indicator = document.getElementById('profileLoadingIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
    }
    
    const avatar = document.getElementById('avatar');
    if (avatar) {
        avatar.style.opacity = '0.5';
    }
    
    const container = document.getElementById('avatar-upload-container');
    if (container) {
        container.style.cursor = 'wait';
        container.style.pointerEvents = 'none';
    }
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    const indicator = document.getElementById('profileLoadingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    
    const avatar = document.getElementById('avatar');
    if (avatar) {
        avatar.style.opacity = '1';
    }
    
    const container = document.getElementById('avatar-upload-container');
    if (container) {
        container.style.cursor = 'pointer';
        container.style.pointerEvents = 'auto';
    }
}

/**
 * Show success message
 * 
 * @param {string} message - Success message
 */
function showSuccessMessage(message) {
    const indicator = document.getElementById('profileSuccessIndicator');
    if (indicator) {
        indicator.textContent = message;
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
    alert(message);
    console.error('Profile picture error:', message);
}

/**
 * Compress image before upload
 * 
 * @param {File} file - Original image file
 * @param {number} maxSizeMB - Maximum size in MB (default 2MB)
 * @param {number} maxWidth - Maximum width in pixels (default 500)
 * @returns {Promise<File>} Compressed image file
 */
async function compressImage(file, maxSizeMB = 2, maxWidth = 500) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                
                // Resize if needed
                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
                
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Try different quality levels to get under maxSizeMB
                let quality = 0.9;
                const tryCompress = () => {
                    canvas.toBlob((blob) => {
                        if (blob.size <= maxSizeMB * 1024 * 1024 || quality <= 0.5) {
                            // Success or reached minimum quality
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            console.log(`Compressed from ${(file.size / 1024 / 1024).toFixed(2)}MB to ${(blob.size / 1024 / 1024).toFixed(2)}MB`);
                            resolve(compressedFile);
                        } else {
                            // Try lower quality
                            quality -= 0.1;
                            tryCompress();
                        }
                    }, 'image/jpeg', quality);
                };
                
                tryCompress();
            };
            img.onerror = reject;
        };
        reader.onerror = reject;
    });
}

/**
 * Handle profile picture file input change
 */
async function handleProfilePictureChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showErrorMessage('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        event.target.value = '';
        return;
    }
    
    // Compress image if it's too large
    let fileToUpload = file;
    if (file.size > 2 * 1024 * 1024) { // If larger than 2MB
        console.log('Compressing image...');
        try {
            fileToUpload = await compressImage(file, 2, 500);
        } catch (error) {
            console.error('Compression failed:', error);
            showErrorMessage('Failed to compress image. Please try a smaller file.');
            event.target.value = '';
            return;
        }
    }
    
    // Validate compressed file size
    if (fileToUpload.size > MAX_FILE_SIZE) {
        showErrorMessage('File size exceeds 10MB limit even after compression. Please use a smaller image.');
        event.target.value = '';
        return;
    }
    
    // Upload compressed image
    await uploadProfilePictureToCloudinary(fileToUpload);
    
    // Clear input so same file can be selected again if needed
    event.target.value = '';
}

/**
 * Update initials when name changes
 */
function updateInitials() {
    const initialsElement = document.getElementById('initials');
    if (!initialsElement) return;
    
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    
    if (!firstnameInput || !lastnameInput) return;
    
    const firstname = firstnameInput.value || '';
    const lastname = lastnameInput.value || '';
    const initials = (firstname.charAt(0) + lastname.charAt(0)).toUpperCase();
    
    initialsElement.textContent = initials;
}

/**
 * Initialize event listeners
 */
function initializeProfilePictureUpload() {
    // Avatar upload container click
    const avatarContainer = document.getElementById('avatar-upload-container');
    const fileInput = document.getElementById('file-input');
    
    if (avatarContainer && fileInput) {
        avatarContainer.addEventListener('click', (e) => {
            // Don't trigger file input if clicking remove button
            if (e.target.closest('.remove-avatar-btn')) {
                return;
            }
            fileInput.click();
        });
        
        fileInput.addEventListener('change', handleProfilePictureChange);
        console.log('Admin profile picture AJAX initialized');
    } else {
        console.warn('Avatar container or file input not found');
    }
    
    // Update initials when name changes
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    
    if (firstnameInput) {
        firstnameInput.addEventListener('input', updateInitials);
    }
    
    if (lastnameInput) {
        lastnameInput.addEventListener('input', updateInitials);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProfilePictureUpload);
} else {
    initializeProfilePictureUpload();
}

// Make functions globally available
window.handleRemoveProfilePicture = handleRemoveProfilePicture;
window.uploadProfilePictureToCloudinary = uploadProfilePictureToCloudinary;
window.deleteProfilePictureFromCloudinary = deleteProfilePictureFromCloudinary;
