<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../../login/admin/admin-auth.php';

// Include the navbar and database
require_once __DIR__ . "/../../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../../admin-includes/database.php";

// Check for valid access token
if (!isset($_SESSION['chatbot_db_access_token']) || empty($_SESSION['chatbot_db_access_token'])) {
    header("Location: cb-knowledge-settings.php?error=access_denied");
    exit;
}

// Verify token hasn't expired
$token = $_SESSION['chatbot_db_access_token'];
$stmt = $conn->prepare("SELECT expires_at FROM chatbot_access_tokens WHERE token = ? AND revoked = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    unset($_SESSION['chatbot_db_access_token']);
    header("Location: cb-knowledge-settings.php?error=token_invalid");
    exit;
}

$tokenData = $result->fetch_assoc();
if (strtotime($tokenData['expires_at']) < time()) {
    unset($_SESSION['chatbot_db_access_token']);
    header("Location: cb-knowledge-settings.php?error=token_expired");
    exit;
}

// Get all tables from database
$tables = [];
$tableResult = $conn->query("SHOW TABLES");
while ($row = $tableResult->fetch_array()) {
    $tables[] = $row[0];
}

// Get currently selected tables
$selectedTables = [];
$stmt = $conn->prepare("SELECT config_json FROM chatbot_database_settings ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    if (!empty($settings['config_json'])) {
        $config = json_decode($settings['config_json'], true);
        $selectedTables = $config['selected_tables'] ?? [];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $selected = $_POST['tables'] ?? [];
    $adminId = $_SESSION['admin_id'];
    
    $configJson = json_encode([
        'selected_tables' => $selected,
        'updated_by_admin' => $adminId,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // Check if settings exist
    $checkStmt = $conn->prepare("SELECT id FROM chatbot_database_settings LIMIT 1");
    $checkStmt->execute();
    $existingResult = $checkStmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        // Update existing
        $existing = $existingResult->fetch_assoc();
        $updateStmt = $conn->prepare("UPDATE chatbot_database_settings SET config_json = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("sii", $configJson, $adminId, $existing['id']);
        $updateStmt->execute();
    } else {
        // Insert new
        $insertStmt = $conn->prepare("INSERT INTO chatbot_database_settings (config_json, updated_by, updated_at, created_at) VALUES (?, ?, NOW(), NOW())");
        $insertStmt->bind_param("si", $configJson, $adminId);
        $insertStmt->execute();
    }
    
    $success_message = "Database settings saved successfully!";
    $selectedTables = $selected; // Update the selected tables array
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Database Settings - NeoCafe Admin</title>
    <link rel="icon" type="image/x-icon" href="../../../../assets/images/favicon.ico">
    
    <!-- CSS files -->
    <link rel="stylesheet" href="../../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="chatbot-knowledge.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include __DIR__ . "/../../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="main-container">
        <div class="kb-wrapper fade-in">
            <!-- Header -->
            <div class="db-settings-header">
                <div>
                    <h1><i class="fas fa-database"></i> Chatbot Database Settings</h1>
                    <p>Select which database tables the chatbot can use as its knowledge source.</p>
                </div>
                <a href="../cb-knowledge-settings.php" class="kb-btn kb-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Knowledge Base
                </a>
            </div>

            <?php if (isset($success_message)): ?>
            <div class="kb-message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <!-- Token Expiration Notice -->
            <div class="token-notice">
                <i class="fas fa-clock"></i>
                <span>Secure session active. This page access will expire in <strong id="token-timer"></strong></span>
            </div>

            <!-- Database Tables Selection -->
            <div class="db-tables-container">
                <form method="POST" id="settings-form">
                    <div class="db-tables-header">
                        <h2><i class="fas fa-table"></i> Available Database Tables</h2>
                        <div class="table-actions">
                            <button type="button" class="kb-btn kb-btn-secondary btn-sm" onclick="selectAll()">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="kb-btn kb-btn-secondary btn-sm" onclick="deselectAll()">
                                <i class="fas fa-times"></i> Deselect All
                            </button>
                        </div>
                    </div>

                    <div class="tables-grid">
                        <?php 
                        // Define default table information for common tables
                        $defaultTableInfo = [
                            'products' => [
                                'icon' => 'fa-box', 
                                'desc' => 'Product information, prices, descriptions, and inventory', 
                                'color' => '#4CAF50',
                                'related_tables' => ['categories', 'product_images']
                            ],
                            'orders' => [
                                'icon' => 'fa-shopping-cart', 
                                'desc' => 'Order history, transactions, and best sellers analysis', 
                                'color' => '#9C27B0',
                                'related_tables' => ['order_items', 'products', 'users']
                            ],
                            'categories' => [
                                'icon' => 'fa-tags', 
                                'desc' => 'Product categories and classifications', 
                                'color' => '#2196F3',
                                'related_tables' => ['products']
                            ],
                            'users' => [
                                'icon' => 'fa-users', 
                                'desc' => 'Customer accounts and profiles (name and basic info only)', 
                                'color' => '#FF9800',
                                'related_tables' => []
                            ],
                            'promotions' => [
                                'icon' => 'fa-percent', 
                                'desc' => 'Active promotions, discounts, and special offers', 
                                'color' => '#E91E63',
                                'related_tables' => ['products']
                            ],
                            'business_hours' => [
                                'icon' => 'fa-clock', 
                                'desc' => 'Operating hours and schedule information', 
                                'color' => '#00BCD4',
                                'related_tables' => []
                            ],
                            'bulk_orders' => [
                                'icon' => 'fa-boxes', 
                                'desc' => 'Bulk order information and wholesale pricing', 
                                'color' => '#607D8B',
                                'related_tables' => ['products']
                            ],
                        ];
                        
                        // Generate info for all tables from database
                        $tableInfo = [];
                        foreach ($tables as $table) {
                            if (isset($defaultTableInfo[$table])) {
                                // Use predefined info
                                $tableInfo[$table] = $defaultTableInfo[$table];
                            } else {
                                // Generate generic info
                                $tableInfo[$table] = [
                                    'icon' => 'fa-database',
                                    'desc' => 'Database table: ' . $table,
                                    'color' => '#607D8B',
                                    'related_tables' => []
                                ];
                            }
                        }
                        
                        // Show all tables from the database
                        foreach ($tables as $table):
                            
                            $isSelected = in_array($table, $selectedTables);
                            $info = $tableInfo[$table];
                        ?>
                        <div class="table-card <?php echo $isSelected ? 'selected' : ''; ?>">
                            <input 
                                type="checkbox" 
                                name="tables[]" 
                                value="<?php echo htmlspecialchars($table); ?>" 
                                id="table_<?php echo htmlspecialchars($table); ?>"
                                <?php echo $isSelected ? 'checked' : ''; ?>
                                onchange="toggleCard(this)"
                            >
                            <label for="table_<?php echo htmlspecialchars($table); ?>">
                                <div class="table-icon" style="background: <?php echo $info['color']; ?>">
                                    <i class="fas <?php echo $info['icon']; ?>"></i>
                                </div>
                                <div class="table-info">
                                    <h3><?php echo htmlspecialchars($table); ?></h3>
                                    <p><?php echo htmlspecialchars($info['desc']); ?></p>
                                </div>
                                <div class="table-status">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="settings-footer">
                        <div class="selected-count">
                            <i class="fas fa-info-circle"></i>
                            <span id="count-display"><?php echo count($selectedTables); ?> table(s) selected</span>
                        </div>
                        <button type="submit" name="save_settings" class="kb-btn kb-btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="db-preview-section">
                <h2><i class="fas fa-eye"></i> Knowledge Source Preview</h2>
                <p>The chatbot will use data from the following selected tables:</p>
                <div id="preview-list" class="preview-list">
                    <?php if (empty($selectedTables)): ?>
                        <div class="preview-empty">
                            <i class="fas fa-inbox"></i>
                            <p>No tables selected. The chatbot will only use manual knowledge entries.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($selectedTables as $table): 
                            $info = $tableInfo[$table] ?? ['icon' => 'fa-database', 'desc' => 'Database table', 'color' => '#607D8B'];
                        ?>
                        <div class="preview-item">
                            <div class="preview-icon" style="background: <?php echo $info['color']; ?>">
                                <i class="fas <?php echo $info['icon']; ?>"></i>
                            </div>
                            <div class="preview-details">
                                <strong><?php echo htmlspecialchars($table); ?></strong>
                                <span><?php echo htmlspecialchars($info['desc']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Token expiration countdown
        const tokenExpires = new Date('<?php echo $tokenData['expires_at']; ?>').getTime();
        
        function updateTimer() {
            const now = new Date().getTime();
            const distance = tokenExpires - now;
            
            if (distance < 0) {
                document.getElementById('token-timer').textContent = 'EXPIRED';
                document.getElementById('token-timer').style.color = '#f44336';
                setTimeout(() => {
                    window.location.href = 'cb-knowledge-settings.php?error=token_expired';
                }, 2000);
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('token-timer').textContent = 
                minutes + 'm ' + seconds + 's';
            
            if (minutes < 5) {
                document.getElementById('token-timer').style.color = '#ff9800';
            }
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);

        // Toggle card selection
        function toggleCard(checkbox) {
            const card = checkbox.closest('.table-card');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateCount();
        }

        // Select all tables
        function selectAll() {
            document.querySelectorAll('input[name="tables[]"]').forEach(cb => {
                cb.checked = true;
                toggleCard(cb);
            });
        }

        // Deselect all tables
        function deselectAll() {
            document.querySelectorAll('input[name="tables[]"]').forEach(cb => {
                cb.checked = false;
                toggleCard(cb);
            });
        }

        // Update count display
        function updateCount() {
            const count = document.querySelectorAll('input[name="tables[]"]:checked').length;
            document.getElementById('count-display').textContent = count + ' table(s) selected';
        }
    </script>

    <style>
        .db-settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .db-settings-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2rem;
        }

        .db-settings-header h1 i {
            color: #4CAF50;
            margin-right: 10px;
        }

        .db-settings-header p {
            margin: 0;
            color: #666;
        }

        .token-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .token-notice i {
            font-size: 1.2rem;
        }

        .db-tables-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .db-tables-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .db-tables-header h2 {
            margin: 0;
            color: #333;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .tables-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .tables-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .tables-grid::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .tables-grid::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .table-card {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .table-card:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .table-card.selected {
            border-color: #4CAF50;
            background: #f1f8f4;
        }

        .table-card input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .table-card label {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            margin: 0;
        }

        .table-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .table-info {
            flex: 1;
        }

        .table-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #333;
        }

        .table-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .table-status {
            opacity: 0;
            transition: opacity 0.3s ease;
            color: #4CAF50;
            font-size: 1.5rem;
        }

        .table-card.selected .table-status {
            opacity: 1;
        }

        .settings-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .selected-count {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .db-preview-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .db-preview-section h2 {
            margin: 0 0 15px 0;
            color: #333;
        }

        .db-preview-section > p {
            color: #666;
            margin-bottom: 20px;
        }

        .preview-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .preview-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .preview-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .preview-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .preview-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .preview-empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .preview-empty i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }

        .preview-icon {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .preview-details {
            display: flex;
            flex-direction: column;
        }

        .preview-details strong {
            color: #333;
            font-size: 1rem;
        }

        .preview-details span {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .db-settings-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .tables-grid {
                grid-template-columns: 1fr;
            }

            .db-tables-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .settings-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
        }
    </style>
</body>
</html>
