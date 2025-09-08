<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../auth/login-signup.php");
        exit();
    }

    // Include config file for base URL
    require_once __DIR__ . "/../admin-includes/config.php";

    // Pagination settings
    $items_per_page = 12;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/backend/pages/user-page-content/delivery-locations.css">
    <script src="/backend/pages/user-page-content/delivery-locations.js" defer></script>
    <title>Delivery Areas & Delivery Fees Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<div class="delivery-locations-container">
    <div class="main-container">
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-content">
                <h1>Delivery Areas & Delivery Fees</h1>
                <p class="page-subtitle">Manage delivery locations and their associated fees</p>
            </div>
                    
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Location
                </button>
            </div>
        </div>

        <!-- Sort Controls -->
        <div class="sort-controls">
            <div class="sort-group">
                <label class="sort-label">Sort by:</label>
                <div class="sort-buttons">
                    <button class="sort-btn active" id="sort-az" onclick="sortLocations('az', this)">
                        A–Z
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    
                    <button class="sort-btn" id="sort-za" onclick="sortLocations('za', this)">
                        Z–A
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    
                    <button class="sort-btn" id="sort-postal" onclick="sortLocations('postal', this)">
                        Postal Code
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    
                    <button class="sort-btn" id="sort-fee" onclick="sortLocations('fee', this)">
                        Delivery Fee (Smallest to Highest)
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Locations Table -->
        <div class="locations-container">
            <div class="table-wrapper">
                <table class="locations-table">
                    <thead>
                        <tr>
                            <th>Municipality</th>
                            <th>City</th>
                            <th>Postal Code</th>
                            <th>Delivery Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="locationsTableBody">
                        <?php
                            $conn = new mysqli("localhost", "root", "", "crud");
                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }

                            // Check if delivery_locations table exists, if not create it
                            $table_check = "SHOW TABLES LIKE 'delivery_locations'";
                            $table_result = $conn->query($table_check);
                            
                            if ($table_result->num_rows == 0) {
                                // Create table if it doesn't exist
                                $create_table = "CREATE TABLE delivery_locations (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    municipality VARCHAR(255) NOT NULL,
                                    city VARCHAR(255) NOT NULL,
                                    postal_code VARCHAR(4) NOT NULL,
                                    delivery_fee DECIMAL(10,2) NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                )";
                                
                                if (!$conn->query($create_table)) {
                                    echo "<tr class='no-results'><td colspan='5'>Error creating table: " . $conn->error . "</td></tr>";
                                } else {
                                    echo "<tr class='no-results'><td colspan='5'>No delivery locations found. Click 'Add Location' to get started.</td></tr>";
                                }
                            } else {
                                // Count total locations for pagination
                                $count_sql = "SELECT COUNT(*) as total FROM delivery_locations";
                                $count_result = $conn->query($count_sql);
                                $total_locations = $count_result->fetch_assoc()['total'];
                                $total_pages = ceil($total_locations / $items_per_page);

                                // Query with LIMIT and OFFSET for pagination
                                $sql = "SELECT id, municipality, city, postal_code, delivery_fee 
                                       FROM delivery_locations 
                                       ORDER BY municipality ASC 
                                       LIMIT $items_per_page OFFSET $offset";
                                
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr data-municipality='" . strtolower($row['municipality']) . "' data-city='" . strtolower($row['city']) . "' data-postal='" . $row['postal_code'] . "' data-fee='" . $row['delivery_fee'] . "'>
                                                <td>
                                                    <span class='municipality-text'>" . htmlspecialchars($row['municipality']) . "</span>
                                                </td>
                                                <td>
                                                    <span class='city-text'>" . htmlspecialchars($row['city']) . "</span>
                                                </td>
                                                <td>
                                                    <span class='postal-text'>" . htmlspecialchars($row['postal_code']) . "</span>
                                                </td>
                                                <td>
                                                    <span class='fee-text'>₱" . number_format($row['delivery_fee'], 2) . "</span>
                                                </td>
                                                <td>
                                                    <div class='action-buttons'>
                                                        <button class='btn-action btn-edit' onclick=\"openEditModal(
                                                            '" . $row["id"] . "',     
                                                            '" . addslashes($row["municipality"]) . "', 
                                                            '" . addslashes($row["city"]) . "', 
                                                            '" . $row["postal_code"] . "',
                                                            '" . $row["delivery_fee"] . "'
                                                        )\" title='Edit Location'>
                                                            <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                                <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
                                                                <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
                                                            </svg>
                                                        </button>
                                                        <button class='btn-action btn-delete' onclick='deleteLocation(" . $row["id"] . ")' title='Delete Location'>
                                                            <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                                <polyline points='3,6 5,6 21,6'></polyline>
                                                                <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr class='no-results'><td colspan='5'>No delivery locations found. Click 'Add Location' to get started.</td></tr>";
                                }
                            }

                            $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if (isset($total_pages) && $total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="pagination-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Location Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ADD LOCATION</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <form id="addLocationForm" onsubmit="addLocation(event)">
            <div class="form-group">
                <label for="addMunicipality">Municipality *</label>
                <input type="text" id="addMunicipality" name="municipality" required>
            </div>
            <div class="form-group">
                <label for="addCity">City *</label>
                <input type="text" id="addCity" name="city" required>
            </div>
            <div class="form-group">
                <label for="addPostalCode">Postal Code *</label>
                <input type="text" id="addPostalCode" name="postal_code" maxlength="4" pattern="[0-9]{4}" required>
                <small>4 digits only</small>
            </div>
            <div class="form-group">
                <label for="addDeliveryFee">Delivery Fee *</label>
                <input type="number" id="addDeliveryFee" name="delivery_fee" step="0.01" min="0" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Location Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>EDIT LOCATION</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editLocationForm" onsubmit="updateLocation(event)">
            <input type="hidden" id="editLocationId" name="id">
            <div class="form-group">
                <label for="editMunicipality">Municipality *</label>
                <input type="text" id="editMunicipality" name="municipality" required>
            </div>
            <div class="form-group">
                <label for="editCity">City *</label>
                <input type="text" id="editCity" name="city" required>
            </div>
            <div class="form-group">
                <label for="editPostalCode">Postal Code *</label>
                <input type="text" id="editPostalCode" name="postal_code" maxlength="4" pattern="[0-9]{4}" required>
                <small>4 digits only</small>
            </div>
            <div class="form-group">
                <label for="editDeliveryFee">Delivery Fee *</label>
                <input type="number" id="editDeliveryFee" name="delivery_fee" step="0.01" min="0" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <span class="close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this location?</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Yes</button>
        </div>
    </div>
</div>

</body>
</html>
