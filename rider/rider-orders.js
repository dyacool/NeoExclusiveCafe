/**
 * Rider Orders JavaScript
 * Handles order interactions and proof of delivery modal with camera integration
 */

let videoStream = null;
let capturedImageBlob = null;
let currentOrderId = null;

// DOM Elements
const modal = document.getElementById('proof-modal');
const modalOrderId = document.getElementById('modal-order-id');
const cameraPreview = document.getElementById('camera-preview');
const capturedPhoto = document.getElementById('captured-photo');
const cameraError = document.getElementById('camera-error');
const closeBtn = document.getElementById('close-modal-btn');
const captureBtn = document.getElementById('capture-btn');
const retakeBtn = document.getElementById('retake-btn');
const confirmBtn = document.getElementById('confirm-btn');
const uploadProgress = document.getElementById('upload-progress');

/**
 * Open proof of delivery modal
 */
async function openProofModal(orderId) {
    currentOrderId = orderId;
    modalOrderId.textContent = orderId;
    modal.classList.add('active');
    
    // Reset UI state
    cameraPreview.style.display = 'block';
    capturedPhoto.style.display = 'none';
    cameraError.style.display = 'none';
    captureBtn.style.display = 'inline-flex';
    retakeBtn.style.display = 'none';
    confirmBtn.style.display = 'none';
    uploadProgress.style.display = 'none';
    captureBtn.disabled = false;
    
    try {
        // Request camera access (prefer rear camera on mobile)
        const constraints = {
            video: {
                facingMode: 'environment', // Rear camera
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };
        
        videoStream = await navigator.mediaDevices.getUserMedia(constraints);
        cameraPreview.srcObject = videoStream;
        cameraError.style.display = 'none';
        captureBtn.disabled = false;
    } catch (error) {
        console.error('Camera access error:', error);
        cameraPreview.style.display = 'none';
        cameraError.style.display = 'block';
        captureBtn.disabled = true;
        
        // Show specific error message
        if (error.name === 'NotAllowedError') {
            cameraError.querySelector('p').textContent = 'Camera access denied';
            cameraError.querySelector('small').textContent = 'Please enable camera permissions in your browser settings';
        } else if (error.name === 'NotFoundError') {
            cameraError.querySelector('p').textContent = 'No camera detected';
            cameraError.querySelector('small').textContent = 'Please ensure your device has a camera';
        } else {
            cameraError.querySelector('p').textContent = 'Camera error';
            cameraError.querySelector('small').textContent = error.message || 'Unable to access camera';
        }
    }
}

/**
 * Close modal and stop camera
 */
function closeModal() {
    modal.classList.remove('active');
    stopCamera();
    capturedImageBlob = null;
    currentOrderId = null;
}

/**
 * Stop camera stream
 */
function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
}

/**
 * Capture photo from video stream
 */
function capturePhoto() {
    const canvas = document.getElementById('photo-canvas');
    const context = canvas.getContext('2d');
    
    // Set canvas size to video size
    canvas.width = cameraPreview.videoWidth;
    canvas.height = cameraPreview.videoHeight;
    
    // Draw video frame to canvas
    context.drawImage(cameraPreview, 0, 0);
    
    // Convert to blob with compression
    canvas.toBlob((blob) => {
        capturedImageBlob = blob;
        
        // Show captured photo
        capturedPhoto.src = URL.createObjectURL(blob);
        capturedPhoto.style.display = 'block';
        cameraPreview.style.display = 'none';
        
        // Update button visibility
        captureBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-flex';
        confirmBtn.style.display = 'inline-flex';
        
        // Stop camera to save battery
        stopCamera();
    }, 'image/jpeg', 0.85); // 85% quality for good balance
}

/**
 * Retake photo
 */
async function retakePhoto() {
    capturedImageBlob = null;
    capturedPhoto.style.display = 'none';
    cameraPreview.style.display = 'block';
    retakeBtn.style.display = 'none';
    confirmBtn.style.display = 'none';
    captureBtn.style.display = 'inline-flex';
    
    // Restart camera
    await openProofModal(currentOrderId);
}

/**
 * Confirm and upload proof
 */
async function confirmDelivery() {
    if (!capturedImageBlob || !currentOrderId) {
        alert('Please capture a photo first');
        return;
    }
    
    // Disable buttons
    confirmBtn.disabled = true;
    retakeBtn.disabled = true;
    closeBtn.disabled = true;
    
    // Show upload progress
    uploadProgress.style.display = 'block';
    
    // Create form data
    const formData = new FormData();
    formData.append('order_id', currentOrderId);
    formData.append('proof_image', capturedImageBlob, `order_${currentOrderId}_${Date.now()}.jpg`);
    
    try {
        const response = await fetch('submit-delivery-proof.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success! Show message and reload
            alert('✓ Delivery proof submitted successfully!');
            closeModal();
            location.reload();
        } else {
            throw new Error(result.error || 'Upload failed');
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('❌ Failed to upload proof: ' + error.message + '\n\nPlease try again.');
        
        // Re-enable buttons
        confirmBtn.disabled = false;
        retakeBtn.disabled = false;
        closeBtn.disabled = false;
        uploadProgress.style.display = 'none';
    }
}

// Event Listeners
closeBtn.addEventListener('click', closeModal);
captureBtn.addEventListener('click', capturePhoto);
retakeBtn.addEventListener('click', retakePhoto);
confirmBtn.addEventListener('click', confirmDelivery);

// Close modal on background click
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
    }
});

// Auto-refresh orders every 5 minutes
setInterval(() => {
    if (!modal.classList.contains('active')) {
        location.reload();
    }
}, 5 * 60 * 1000);

// Add visual feedback for touch interactions on mobile
document.addEventListener('DOMContentLoaded', function() {
    const orderRows = document.querySelectorAll('.order-row:not(.completed)');
    
    orderRows.forEach(row => {
        row.addEventListener('touchstart', function() {
            this.style.backgroundColor = 'var(--gray-50)';
        });
        
        row.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.backgroundColor = '';
            }, 200);
        });
    });
});

console.log('Rider Orders Interface with Camera Integration Loaded');
