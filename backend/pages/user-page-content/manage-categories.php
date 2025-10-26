<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Get all categories
$sql = "SELECT * FROM categories ORDER BY display_order ASC, name ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="manage-categories.css">
    <title>Manage Categories | Neo Exclusive Cafe</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    <?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="main-container">
        <div class="page-header">
            <p class="page-subtitle">Organize your products by creating and managing categories</p>
        </div>

        <div class="actions-bar">
            <button class="btn btn-primary" onclick="openAddModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add New Category
            </button>
        </div>

        <div class="categories-container">
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Count products in this category
                            $count_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = " . $row['id'] . " AND deleted_at IS NULL";
                            $count_result = mysqli_query($conn, $count_sql);
                            $count = mysqli_fetch_assoc($count_result)['count'];
                            
                            $statusClass = $row['is_active'] ? 'active' : 'inactive';
                            $statusText = $row['is_active'] ? 'Active' : 'Inactive';
                            
                            echo "<tr data-id='" . $row['id'] . "'>
                                    <td><span class='order-badge'>" . $row['display_order'] . "</span></td>
                                    <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['description'] ?? 'No description') . "</td>
                                    <td><span class='status-badge status-" . $statusClass . "'>" . $statusText . "</span></td>
                                    <td><span class='count-badge'>" . $count . " products</span></td>
                                    <td>
                                        <div class='action-buttons'>
                                            <button class='btn-action btn-edit' onclick='openEditModal(" . json_encode($row) . ")' title='Edit Category'>
                                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                    <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
                                                    <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
                                                </svg>
                                            </button>
                                            <button class='btn-action btn-delete' onclick='deleteCategory(" . $row['id'] . ", \"" . addslashes($row['name']) . "\", " . $count . ")' title='Delete Category'>
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
                        echo "<tr class='no-results'><td colspan='6'>
                                <div class='empty-state'>
                                    <svg width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                        <path d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'></path>
                                    </svg>
                                    <h3>No categories found</h3>
                                    <p>Start by adding your first category.</p>
                                </div>
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Category</h2>
                <button class="modal-close" onclick="closeModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <form id="categoryForm" class="modal-form">
                <input type="hidden" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" rows="3" maxlength="500"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="displayOrder">Display Order</label>
                        <input type="number" id="displayOrder" min="0" value="0">
                        <small>Lower numbers appear first</small>
                    </div>

                    <div class="form-group">
                        <label for="isActive">Status</label>
                        <select id="isActive">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script src="manage-categories.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
