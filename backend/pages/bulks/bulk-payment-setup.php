<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Include navbar (database already loaded by admin-auth)
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Include Cloudinary config
require_once __DIR__ . '/../../../config/cloudinary-config.php';

// Fetch all QR payment images (not deleted)
$stmt = $conn->prepare("SELECT id, qr_image, qr_name, is_active, created_at, updated_at FROM bulk_payment WHERE deleted_at IS NULL ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$payment_qrs = $result->fetch_all(MYSQLI_ASSOC);

// Get active QR
$active_qr = null;
foreach ($payment_qrs as $qr) {
    if ($qr['is_active']) {
        $active_qr = $qr;
        break;
    }
}

$success_message = '';
$error_message = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Payment Setup</title>
    <link rel="stylesheet" href="/backend/pages/account/admin-profile.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        .upload-section {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border: 2px dashed var(--gray-300);
            border-radius: 0.5rem;
            text-align: center;
            background-color: var(--gray-50);
        }

        .upload-section.dragover {
            border-color: var(--green-500);
            background-color: var(--green-50);
        }

        .qr-preview {
            max-width: 300px;
            margin: 1rem auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .qr-preview img {
            width: 100%;
            border-radius: 0.5rem;
        }

        .qr-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 0.5rem;
            border: 1px solid var(--gray-200);
        }

        .qr-table th {
            background-color: var(--green-600);
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .qr-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .qr-table tr:hover {
            background-color: var(--gray-50);
        }

        .qr-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .qr-thumbnail:hover {
            transform: scale(1.1);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-active {
            background-color: var(--green-100);
            color: var(--green-700);
        }

        .badge-inactive {
            background-color: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-activate {
            background-color: var(--green-600);
            color: white;
        }

        .btn-activate:hover {
            background-color: var(--green-700);
        }

        .btn-deactivate {
            background-color: var(--gray-400);
            color: white;
        }

        .btn-deactivate:hover {
            background-color: var(--gray-500);
        }

        .btn-remove {
            background-color: var(--red-600);
            color: white;
        }

        .btn-remove:hover {
            background-color: var(--red-700);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 0.5rem;
        }

        .upload-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            background-color: var(--green-600);
            color: white;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .upload-label:hover {
            background-color: var(--green-700);
        }

        .upload-label svg {
            margin-right: 0.5rem;
        }

        #qr-file-input {
            display: none;
        }

        .btn-delete {
            background-color: var(--red-600);
            margin-top: 1rem;
        }

        .btn-delete:hover {
            background-color: var(--red-500);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: var(--green-50);
            color: var(--green-800);
            border: 1px solid var(--green-200);
        }

        .alert-error {
            background-color: #fee;
            color: var(--red-600);
            border: 1px solid #fcc;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            border: 4px solid var(--gray-200);
            border-top: 4px solid var(--green-600);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="modal" id="imageModal" onclick="closeModal()">
        <img id="modalImage" src="" alt="QR Code">
    </div>

    <div class="admin-profile-container">
        <div class="main-container">
            <div class="profile-card">
                <div class="profile-header">
                    <h1 class="profile-title">Bulk Payment QR Code Setup</h1>
                    <p class="user-username">Upload QR code for bulk order payments</p>
                </div>
                
                <div class="profile-content">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <?php if ($active_qr): ?>
                        <div class="info-group">
                            <div class="info-label">Current Active QR Code</div>
                            <div class="qr-preview">
                                <img src="<?php echo htmlspecialchars($active_qr['qr_image']); ?>" alt="Active Payment QR Code" onclick="viewImage('<?php echo htmlspecialchars($active_qr['qr_image']); ?>')">
                            </div>
                            <?php if ($active_qr['qr_name']): ?>
                                <p style="text-align: center; color: var(--gray-800); font-weight: 600; margin-top: 0.5rem;">
                                    <?php echo htmlspecialchars($active_qr['qr_name']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($active_qr['updated_at']): ?>
                                <p style="text-align: center; color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem;">
                                    Last updated: <?php echo date('F j, Y g:i A', strtotime($active_qr['updated_at'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="upload-section" id="uploadSection">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--gray-400); margin: 0 auto 1rem;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p style="color: var(--gray-600); margin-bottom: 1rem;">
                                Add new QR code for bulk order payments
                            </p>
                            <input type="text" id="qr-name-input" name="qr_name" placeholder="QR NAME (e.g., GCash - Main Account)" 
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem;">
                            <label for="qr-file-input" class="upload-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Choose File
                            </label>
                            <input type="file" id="qr-file-input" name="qr_image" accept="image/jpeg,image/png,image/jpg,image/gif" required>
                            <p style="color: var(--gray-500); font-size: 0.75rem; margin-top: 0.5rem;">
                                Supported formats: JPG, PNG, GIF (Max 10MB)
                            </p>
                            <p id="fileName" style="color: var(--green-600); font-size: 0.875rem; margin-top: 0.5rem; display: none;"></p>
                            
                            <!-- Preview Section -->
                            <div id="previewSection" style="display: none; margin-top: 1.5rem;">
                                <div class="info-label" style="text-align: center; margin-bottom: 1rem;">Preview</div>
                                <div style="max-width: 300px; margin: 0 auto; border: 2px solid var(--green-500); border-radius: 0.5rem; padding: 1rem; background-color: white;">
                                    <img id="previewImage" src="" alt="QR Preview" style="width: 100%; border-radius: 0.5rem;">
                                    <p id="previewName" style="text-align: center; font-weight: 600; color: var(--gray-800); margin-top: 0.5rem;"></p>
                                </div>
                                <div style="text-align: center; margin-top: 1rem;">
                                    <button type="button" class="btn" onclick="uploadFile()" style="background-color: var(--green-600); margin-right: 0.5rem;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                            <polyline points="7 3 7 8 15 8"></polyline>
                                        </svg>
                                        Save QR Code
                                    </button>
                                    <button type="button" class="btn" onclick="cancelUpload()" style="background-color: var(--gray-500);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (count($payment_qrs) > 0): ?>
                        <div class="info-group">
                            <div class="info-label">All QR Codes (<?php echo count($payment_qrs); ?>)</div>
                            <div class="table-container">
                                <table class="qr-table">
                                    <thead>
                                        <tr>
                                            <th>QR Code</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_qrs as $qr): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars($qr['qr_image']); ?>" 
                                                         alt="QR Code" 
                                                         class="qr-thumbnail"
                                                         onclick="viewImage('<?php echo htmlspecialchars($qr['qr_image']); ?>')">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($qr['qr_name'] ?? 'Unnamed QR'); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($qr['is_active']): ?>
                                                        <span class="badge badge-active">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-inactive">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size: 0.875rem; color: var(--gray-600);">
                                                    <?php echo date('M j, Y', strtotime($qr['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if (!$qr['is_active']): ?>
                                                            <button class="btn-sm btn-activate" onclick="setActive(<?php echo $qr['id']; ?>)">
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                                </svg>
                                                                Set Active
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn-sm btn-deactivate" onclick="setInactive(<?php echo $qr['id']; ?>)">
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <circle cx="12" cy="12" r="10"></circle>
                                                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                                                </svg>
                                                                Deactivate
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn-sm btn-remove" onclick="deleteQRCode(<?php echo $qr['id']; ?>)">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                            Remove
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="../account/admin-profile.php" class="btn" style="background-color: var(--gray-600); margin-top: 1rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Back to Profile
                    </a>
                </div>
            </div>
            
            <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('qr-file-input');
        const qrNameInput = document.getElementById('qr-name-input');
        const fileName = document.getElementById('fileName');
        const uploadSection = document.getElementById('uploadSection');
        const previewSection = document.getElementById('previewSection');
        const previewImage = document.getElementById('previewImage');
        const previewName = document.getElementById('previewName');
        let selectedFile = null;
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileSelection(file);
            }
        });

        // Drag and drop
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection(file);
            }
        });

        function handleFileSelection(file) {
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                fileInput.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                fileInput.value = '';
                return;
            }
            
            selectedFile = file;
            fileName.textContent = '✓ Selected: ' + file.name;
            fileName.style.display = 'block';
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                const name = qrNameInput.value.trim() || 'QR Code - ' + new Date().toLocaleString();
                previewName.textContent = name;
                previewSection.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        // Update preview name when user types
        qrNameInput.addEventListener('input', function() {
            if (selectedFile) {
                const name = qrNameInput.value.trim() || 'QR Code - ' + new Date().toLocaleString();
                previewName.textContent = name;
            }
        });

        function cancelUpload() {
            selectedFile = null;
            fileInput.value = '';
            fileName.style.display = 'none';
            previewSection.style.display = 'none';
            previewImage.src = '';
            previewName.textContent = '';
        }

        function uploadFile() {
            if (!selectedFile) {
                alert('Please select a file first');
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            const formData = new FormData();
            formData.append('qr_image', selectedFile);
            formData.append('qr_name', qrNameInput.value.trim());

            fetch('upload-qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                overlay.classList.remove('active');
                if (data.success) {
                    alert('QR code uploaded successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to upload QR code'));
                }
            })
            .catch(error => {
                overlay.classList.remove('active');
                console.error('Upload error:', error);
                alert('Error uploading QR code. Please try again.');
            });
        }

        function setActive(id) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            fetch('set-active-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                overlay.classList.remove('active');
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to set active QR code'));
                }
            })
            .catch(error => {
                overlay.classList.remove('active');
                alert('Error: ' + error.message);
            });
        }

        function setInactive(id) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            fetch('set-inactive-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                overlay.classList.remove('active');
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to deactivate QR code'));
                }
            })
            .catch(error => {
                overlay.classList.remove('active');
                alert('Error: ' + error.message);
            });
        }

        function deleteQRCode(id) {
            if (!confirm('Are you sure you want to remove this QR code?')) {
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            fetch('delete-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                overlay.classList.remove('active');
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete QR code'));
                }
            })
            .catch(error => {
                overlay.classList.remove('active');
                alert('Error deleting QR code: ' + error.message);
            });
        }

        function viewImage(url) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = url;
            modal.classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
        }
    </script>
</body>
</html>
