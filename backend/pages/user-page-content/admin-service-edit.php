<?php
// Load admin authentication (handles session, database, and auth check)
require_once __DIR__ . '/../../login/admin/admin-auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Edit Service Section";

require_once "../admin-includes/activity-logger.php";
require_once "../admin-includes/navbar/navbar.php";

function debug_log($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

function ensure_tables_exist($conn) {
    $create_settings_table = "CREATE TABLE IF NOT EXISTS `service_section_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL DEFAULT 'Our Services',
        `subtitle` text NOT NULL DEFAULT 'What we offer',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!mysqli_query($conn, $create_settings_table)) {
        debug_log("Error creating service_section_settings table: " . mysqli_error($conn));
        return false;
    }
    
    $create_cards_table = "CREATE TABLE IF NOT EXISTS `service_cards` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `icon_name` varchar(50) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `display_order` int(11) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!mysqli_query($conn, $create_cards_table)) {
        debug_log("Error creating service_cards table: " . mysqli_error($conn));
        return false;
    }
    
    return true;
}

ensure_tables_exist($conn);

$settings = [
    'title' => 'Our Services',
    'subtitle' => 'What we offer'
];

$check_settings = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_section_settings");
$settings_count = mysqli_fetch_assoc($check_settings)['count'];

if ($settings_count == 0) {
    $insert_default = "INSERT INTO service_section_settings (title, subtitle) VALUES ('Our Services', 'What we offer')";
    if (mysqli_query($conn, $insert_default)) {
        debug_log("Inserted default service section settings");
    } else {
        debug_log("Error inserting default settings: " . mysqli_error($conn));
    }
}

if (isset($_POST['update_settings'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    
    debug_log("Updating service settings - Title: $title, Subtitle: $subtitle");
    
    $check_settings = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_section_settings");
    $settings_count = mysqli_fetch_assoc($check_settings)['count'];
    
    if ($settings_count == 0) {
        $insert_query = "INSERT INTO service_section_settings (title, subtitle) VALUES ('$title', '$subtitle')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Service section settings created successfully!";
            debug_log("Service settings created successfully");
            $settings['title'] = $title;
            $settings['subtitle'] = $subtitle;
        } else {
            $error_message = "Error creating settings: " . mysqli_error($conn);
            debug_log("Error creating settings: " . mysqli_error($conn));
        }
    } else {
        $update_query = "UPDATE service_section_settings SET title = '$title', subtitle = '$subtitle' WHERE id = 1";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Service section settings updated successfully!";
            debug_log("Service settings updated successfully");
            // Update local settings
            $settings['title'] = $title;
            $settings['subtitle'] = $subtitle;
        } else {
            $error_message = "Error updating settings: " . mysqli_error($conn);
            debug_log("Error updating settings: " . mysqli_error($conn));
        }
    }
}

// Process form submission for adding a new card
if (isset($_POST['add_card'])) {
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $title = mysqli_real_escape_string($conn, $_POST['card_title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $order = (int)$_POST['display_order'];
    
    $insert_query = "INSERT INTO service_cards (icon_name, title, description, display_order) 
                    VALUES ('$icon', '$title', '$description', $order)";
    
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "New service card added successfully!";
        debug_log("New service card added successfully");
        $new_card_id = mysqli_insert_id($conn);
        logAdminActivity($conn, 'CREATE', "Added new service card: $title", 'service_cards', $new_card_id);
    } else {
        $error_message = "Error adding card: " . mysqli_error($conn);
        debug_log("Error adding card: " . mysqli_error($conn));
    }
}

// Process form submission for updating a card
if (isset($_POST['update_card'])) {
    $card_id = (int)$_POST['card_id'];
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $title = mysqli_real_escape_string($conn, $_POST['card_title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $order = (int)$_POST['display_order'];
    
    $update_query = "UPDATE service_cards SET 
                    icon_name = '$icon', 
                    title = '$title', 
                    description = '$description', 
                    display_order = $order 
                    WHERE id = $card_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Service card updated successfully!";
        debug_log("Service card updated successfully");
        logAdminActivity($conn, 'UPDATE', "Updated service card: $title", 'service_cards', $card_id);
    } else {
        $error_message = "Error updating card: " . mysqli_error($conn);
        debug_log("Error updating card: " . mysqli_error($conn));
    }
}

// Process card deletion
if (isset($_GET['delete_card']) && isset($_GET['id'])) {
    $card_id = (int)$_GET['id'];
    
    // Get card title for logging
    $get_title_query = "SELECT title FROM service_cards WHERE id = $card_id";
    $title_result = mysqli_query($conn, $get_title_query);
    $card_title = ($title_result && mysqli_num_rows($title_result) > 0) ? mysqli_fetch_assoc($title_result)['title'] : 'Unknown';
    
    $delete_query = "DELETE FROM service_cards WHERE id = $card_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $success_message = "Service card deleted successfully!";
        debug_log("Service card deleted successfully");
        logAdminActivity($conn, 'DELETE', "Deleted service card: $card_title", 'service_cards', $card_id);
    } else {
        $error_message = "Error deleting card: " . mysqli_error($conn);
        debug_log("Error deleting card: " . mysqli_error($conn));
    }
}

// Get current settings
$settings_query = "SELECT * FROM service_section_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);

if (!$settings_result) {
    debug_log("Error fetching settings: " . mysqli_error($conn));
} else {
    // If settings exist in database, use those values
    if (mysqli_num_rows($settings_result) > 0) {
        $db_settings = mysqli_fetch_assoc($settings_result);
        if ($db_settings) {
            $settings = $db_settings;
            debug_log("Retrieved settings from database: " . print_r($settings, true));
        }
    } else {
        debug_log("No settings found in database, using defaults");
    }
}

// Check if cards table has any entries, if not add some defaults
$check_cards = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_cards");
$cards_count = mysqli_fetch_assoc($check_cards)['count'];

if ($cards_count == 0) {
    $insert_default_cards = "INSERT INTO `service_cards` (`icon_name`, `title`, `description`, `display_order`) VALUES
        ('star', 'Quality Service', 'We provide high-quality service to all our customers.', 1),
        ('truck', 'Fast Delivery', 'Quick and reliable delivery to your doorstep.', 2),
        ('info', 'Customer Support', 'Our team is always ready to assist you with any questions.', 3);";
    
    if (mysqli_query($conn, $insert_default_cards)) {
        debug_log("Inserted default service cards");
    } else {
        debug_log("Error inserting default cards: " . mysqli_error($conn));
    }
}

// Get all service cards
$cards_query = "SELECT * FROM service_cards ORDER BY display_order ASC";
$cards_result = mysqli_query($conn, $cards_query);

if (!$cards_result) {
    debug_log("Error fetching cards: " . mysqli_error($conn));
    $cards = [];
} else {
    $cards = [];
    while ($card = mysqli_fetch_assoc($cards_result)) {
        $cards[] = $card;
    }
    debug_log("Retrieved " . count($cards) . " service cards from database");
}
?>
<link rel="stylesheet" href="admin-service-edit.css">
<?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

<div class="admin-container">    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="admin-section">
        <h2>Section Settings</h2>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="title">Section Title: <span class="char-count">(0/60 characters)</span></label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?php echo str_replace('\'', "'", htmlspecialchars($settings['title'])); ?>" 
                       maxlength="60" required>
                <p class="help-text">The main heading for your service section.</p>
            </div>
            
            <div class="form-group">
                <label for="subtitle">Section Subtitle: <span class="char-count">(0/120 characters)</span></label>
                <textarea id="subtitle" name="subtitle" class="form-control" rows="3" 
                          maxlength="120" required><?php echo str_replace('\'', "'", htmlspecialchars($settings['subtitle'])); ?></textarea>
                <p class="help-text">A brief description that appears below the title.</p>
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" name="update_settings" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
    
    <div class="admin-section">
        <h2>Service Cards</h2>
        
        <div class="card-list">
            <?php foreach ($cards as $card):
                // Generate an icon based on the icon_name
                $icon_svg = '';
                switch ($card['icon_name']) {
                    case 'star':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><path fill="currentColor" d="M22 10.1c.1-.5-.3-1.1-.8-1.1l-5.7-.8L12.9 3c-.1-.2-.2-.3-.4-.4c-.5-.3-1.1-.1-1.4.4L8.6 8.2L2.9 9c-.3 0-.5.1-.6.3c-.4.4-.4 1 0 1.4l4.1 4l-1 5.7c0 .2 0 .4.1.6c.3.5.9.7 1.4.4l5.1-2.7l5.1 2.7c.1.1.3.1.5.1h.2c.5-.1.9-.6.8-1.2l-1-5.7l4.1-4c.2-.1.3-.3.3-.5z"/></svg>';
                        break;
                    case 'truck':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M10 17h4V5H2v12h3m15 0h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5m0 8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></g></svg>';
                        break;
                    case 'diamond':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 512 512"><path fill="currentColor" fill-rule="evenodd" d="m384 85.333l85.333 85.334v256H42.667V182.113l42.666 49.777V384h341.334V181.333L373.333 128h-46.985l-34.34-42.667zM384 320v32H128v-32zM256 64l53.333 74.667l-138.666 160L32 138.667L85.333 64zm-20.664 192H384v32H234.667v-31.22zm-41.663-106.667H147.64l23.027 91.478zm-79.059 0H81.86l58.37 80.05zm144.839 0h-32.734l-25.611 80.033zm30.74 42.666L384 192v32H262.765zM137.214 96h-35.433l-22.853 32h36.953zm39.306 0h-11.745l-13.609 32h38.974zm63.01 0h-35.412l21.334 32h36.931z"/></svg>';
                        break;
                    case 'paper-bag':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M8 3h8a2 2 0 0 1 2 2v1.82a5 5 0 0 0 .528 2.236l.944 1.888A5 5 0 0 1 20 13.18V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5.82a5 5 0 0 1 .528-2.236L6 8V5a2 2 0 0 1 2-2z"/><path d="M12 15a2 2 0 1 0 4 0a2 2 0 1 0-4 0m-6 6a2 2 0 0 0 2-2v-5.82a5 5 0 0 0-.528-2.236L6 8m5-1h2"/></g></svg>';
                        break;
                    case 'clock':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 16 16"><path fill="currentColor" d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8s8-3.6 8-8s-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6s6 2.7 6 6s-2.7 6-6 6z"/><path fill="currentColor" d="M8 3H7v6h5V8H8z"/></svg>';
                        break;
                    case 'info':
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0m-9 11c6.075 0 11-4.925 11-11S18.075 1 12 1S1 5.925 1 12s4.925 11 11 11m0-13.8a1.2 1.2 0 1 0 0-2.4a1.2 1.2 0 0 0 0 2.4m1 1.8v6h-2v-6z" clip-rule="evenodd"/></svg>';
                        break;
                    default:
                        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 12 12"><path fill="currentColor" d="M6 9.5A1.5 1.5 0 0 0 7.5 11h2A1.5 1.5 0 0 0 11 9.5v-3A1.5 1.5 0 0 0 9.5 5h-2A1.5 1.5 0 0 0 6 6.5v3Zm1.5.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-2ZM1 5.5A1.5 1.5 0 0 0 2.5 7h1A1.5 1.5 0 0 0 5 5.5v-3A1.5 1.5 0 0 0 3.5 1h-1A1.5 1.5 0 0 0 1 2.5v3Zm1.5.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-1Zm5-2a1.5 1.5 0 1 1 0-3h2a1.5 1.5 0 0 1 0 3h-2ZM7 2.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 0-1h-2a.5.5.5 0 0 0-.5.5Zm-6 7A1.5 1.5 0 0 0 2.5 11h1a1.5 1.5 0 0 0 0-3h-1A1.5 1.5 0 0 0 1 9.5Zm1.5.5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1h-1Z"/></svg>';
                }
            ?>
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo str_replace('\'', "'", htmlspecialchars($card['title'])); ?></h3>

                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-primary edit-card-btn" 
                                    data-id="<?php echo $card['id']; ?>"
                                    data-icon="<?php echo htmlspecialchars($card['icon_name']); ?>"
                                    data-title="<?php echo htmlspecialchars($card['title']); ?>"
                                    data-description="<?php echo htmlspecialchars($card['description']); ?>"
                                    data-order="<?php echo $card['display_order']; ?>">
                                Edit
                            </button>
                            <a href="?delete_card=1&id=<?php echo $card['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this card?')">
                                Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Icon:</strong> <?php echo $icon_svg; ?></p>
                        <p><strong>Description:</strong> <?php echo str_replace('\'', "'", htmlspecialchars($card['description'])); ?></p>

                        <p><strong>Display Order:</strong> <?php echo $card['display_order']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-success mt-3" id="add-card-btn">Add New Card</button>
        </div>
    </div>
    
    <!-- Add/Edit Card Modal -->
    <div class="modal" id="cardModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Service Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="card_id" name="card_id">
                        
                        <div class="form-group">
                            <label for="icon">Icon:</label>
                            <select id="icon" name="icon" class="form-control" required>
                                <option value="star">Star</option>
                                <option value="truck">Truck</option>
                                <option value="diamond">Diamond</option>
                                <option value="paper-bag">Paper Bag</option>
                                <option value="clock">Clock</option>
                                <option value="info">Info</option>
                                <option value="default">Default</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="card_title">Title:</label>
                            <input type="text" id="card_title" name="card_title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="display_order">Display Order:</label>
                            <input type="number" id="display_order" name="display_order" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveCardBtn" name="add_card">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add new card button
        document.getElementById('add-card-btn').addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Add New Service Card';
            document.getElementById('card_id').value = '';
            document.getElementById('icon').value = 'star';
            document.getElementById('card_title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('display_order').value = '<?php echo count($cards) + 1; ?>';
            
            document.getElementById('saveCardBtn').name = 'add_card';
            document.getElementById('saveCardBtn').textContent = 'Add Card';
            
            document.getElementById('cardModal').style.display = 'block';
        });
        
        // Edit card buttons
        const editButtons = document.querySelectorAll('.edit-card-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const icon = this.getAttribute('data-icon');
                const title = this.getAttribute('data-title');
                const description = this.getAttribute('data-description');
                const order = this.getAttribute('data-order');
                
                document.getElementById('modalTitle').textContent = 'Edit Service Card';
                document.getElementById('card_id').value = id;
                document.getElementById('icon').value = icon;
                document.getElementById('card_title').value = title;
                document.getElementById('description').value = description;
                document.getElementById('display_order').value = order;
                
                document.getElementById('saveCardBtn').name = 'update_card';
                document.getElementById('saveCardBtn').textContent = 'Update Card';
                
                document.getElementById('cardModal').style.display = 'block';
            });
        });
        
        // Close modal
        const closeButtons = document.querySelectorAll('.close, .btn-secondary');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('cardModal').style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('cardModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
        
        // Character count functionality
        const titleInput = document.getElementById('title');
        const titleCount = titleInput.parentElement.querySelector('.char-count');
        
        titleInput.addEventListener('input', function() {
            titleCount.textContent = `(${this.value.length}/60 characters)`;
            if (this.value.length >= 60) {
                titleCount.style.color = '#dc3545';
            } else {
                titleCount.style.color = '#6c757d';
            }
        });
        
        // Trigger once to initialize
        titleInput.dispatchEvent(new Event('input'));
        
        // For subtitle field
        const subtitleInput = document.getElementById('subtitle');
        const subtitleCount = subtitleInput.parentElement.querySelector('.char-count');
        
        subtitleInput.addEventListener('input', function() {
            subtitleCount.textContent = `(${this.value.length}/120 characters)`;
            if (this.value.length >= 120) {
                subtitleCount.style.color = '#dc3545';
            } else {
                subtitleCount.style.color = '#6c757d';
            }
        });
        
        // Trigger once to initialize
        subtitleInput.dispatchEvent(new Event('input'));
    });
</script>

<?php require_once "../admin-includes/footer/admin-footer.php"; ?>
