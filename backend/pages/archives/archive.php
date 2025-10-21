<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: /login/admin/admin-login.php");
        exit();
    }

    // Include database configuration
    require_once __DIR__ . '/../../../config/database-config.php';
    
    // Pagination settings
    $items_per_page = 12;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Get database connection
    $conn = getDatabaseConnection();
    
    // Count total archived products
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NOT NULL";
    $count_result = $conn->query($count_sql);
    $total_archived = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_archived / $items_per_page);
    
    // Get archived products with pagination
    $sql = "SELECT p.id, p.sku, p.name, p.price, p.deleted_at, pi.image_url 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.deleted_at IS NOT NULL
            ORDER BY p.deleted_at DESC
            LIMIT $items_per_page OFFSET $offset";
    
    $result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/backend/pages/archives/archive.css">
    <title>Archive - Deleted Products</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<div class="archive-container">
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <p>Manage deleted products - restore or permanently delete items</p>
            </div>

            <div class="search-section">
              <div class="search-container">
                  <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <path d="m21 21-4.35-4.35"></path>
                  </svg>
                  <input type="text" class="search-input" placeholder="Search archived products..." id="searchInput" oninput="searchProducts()">
              </div>
            </div>
        </div>

        <!-- Search Section -->


        <!-- Archive Stats -->
        <div class="archive-stats">
            <div class="stats-content">
                <div class="stats-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="5" rx="1"></rect>
                        <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path>
                        <path d="M10 12h4"></path>
                    </svg>
                </div>
                <div class="stats-text">
                    <h3><?php echo $total_archived; ?></h3>
                    <p>Products in Archive</p>
                </div>
            </div>
        </div>

        <!-- Archive Products Container -->
        <div class="archive-products-container">
            <div class="table-wrapper">
                <table class="archive-table">
                    <tbody id="archiveTableBody">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $deleted_date = date('M j, Y', strtotime($row['deleted_at']));
                                echo "<tr data-name='" . strtolower($row['name']) . "' data-sku='" . strtolower($row['sku']) . "'>
                                        <td>
                                            <div class='product-image-container'>";
                                // Construct image path
                                $imagePath = '/assets/images/no-image.jpg';
                                if (!empty($row['image_url'])) {
                                    // Split path into directory and filename
                                    $pathParts = pathinfo($row['image_url']);
                                    $dirPath = $pathParts['dirname'];
                                    $fileName = $pathParts['basename'];
                                    
                                    // URL encode each path segment separately
                                    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $dirPath)));
                                    $encodedFileName = rawurlencode($fileName);
                                    
                                    // Combine encoded parts
                                    $imagePath = '/assets/' . $encodedPath . '/' . $encodedFileName;
                                    
                                    // Verify file exists
                                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
                                    if (!file_exists($fullPath)) {
                                        $imagePath = '/assets/images/no-image.jpg';
                                    }
                                }
                                echo "<img class='product-image' src='" . htmlspecialchars($imagePath) . "' alt='" . htmlspecialchars($row['name']) . "' loading='lazy' onerror=\"this.src='/assets/images/no-image.jpg'\">";
                                echo "</div>
                                        </td>
                                        <td>
                                            <span class='sku-text'>" . $row['sku'] . "</span>
                                        </td>
                                        <td>
                                            <div class='product-info'>
                                                <span class='product-name'>" . htmlspecialchars($row['name']) . "</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class='price-text'>₱" . number_format($row['price'], 2) . "</span>
                                        </td>
                                        <td>
                                            <span class='deleted-date'>" . $deleted_date . "</span>
                                        </td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='btn-action btn-restore' onclick='restoreProduct(" . $row['id'] . ")' title='Restore Product'>
                                                    Restore
                                                </button>
                                                <button class='btn-action btn-delete' onclick='deletePermanently(" . $row['id'] . ")' title='Delete Permanently'>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr class='no-results'><td colspan='6'>
                                    <div class='empty-state'>
                                        <svg width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                            <rect x='2' y='3' width='20' height='5' rx='1'></rect>
                                            <path d='M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8'></path>
                                            <path d='M10 12h4'></path>
                                        </svg>
                                        <h3>Archive is empty</h3>
                                        <p>No deleted products found in the archive.</p>
                                    </div>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?php echo min($total_archived, $items_per_page * $current_page); ?> of <?php echo $total_archived; ?> archived products
                </div>
                <nav class="pagination">
                    <?php
                    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
                    echo '<a class="pagination-btn ' . $prev_disabled . '" ' . 
                         ($prev_disabled == '' ? 'href="?page=' . ($current_page - 1) . '"' : '') . '>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15,18 9,12 15,6"></polyline>
                            </svg>
                            Previous
                         </a>';
                    
                    $max_visible_pages = 5;
                    $start_page = max(1, min($current_page - floor($max_visible_pages / 2), $total_pages - $max_visible_pages + 1));
                    $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
                    
                    if ($start_page > 1) {
                        echo '<a class="pagination-number" href="?page=1">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $current_page) ? 'active' : '';
                        echo '<a class="pagination-number ' . $active . '" href="?page=' . $i . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a class="pagination-number" href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                    }
                    
                    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
                    echo '<a class="pagination-btn ' . $next_disabled . '" ' . 
                         ($next_disabled == '' ? 'href="?page=' . ($current_page + 1) . '"' : '') . '>
                            Next
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                         </a>';
                    ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/backend/pages/archives/archive.js"></script>
<?php $conn->close(); ?>
</body>
</html>
