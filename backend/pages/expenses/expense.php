<?php
    // Use admin-auth for authentication
    require_once __DIR__ . '/../../login/admin/admin-auth.php';
    require_once __DIR__ . "/../admin-includes/database.php";

    // Default date range (last 30 days)
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $selected_period = '30days';
    
    // Handle custom date range and period selection
    if (isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        $selected_period = 'custom';
    } else if (isset($_GET['period'])) {
        $selected_period = $_GET['period'];
        switch ($_GET['period']) {
            case '7days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
        }
    }

    // Check if expenses table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
    $table_exists = mysqli_num_rows($table_check) > 0;
    
    // Initialize variables
    $total_records = 0;
    $total_pages = 0;
    $total_amount = 0;
    $expenses = [];
    $category_totals = [];
    
    if ($table_exists) {
        // Pagination setup
        $records_per_page = 20;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $offset = ($page - 1) * $records_per_page;
        
        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) as total FROM expenses 
                      WHERE DATE(created_at) BETWEEN ? AND ?";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        
        if ($count_stmt) {
            mysqli_stmt_bind_param($count_stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count_row = mysqli_fetch_assoc($count_result);
            $total_records = $count_row['total'];
            $total_pages = ceil($total_records / $records_per_page);
            mysqli_stmt_close($count_stmt);
        }
        
        // Fetch expenses
        $sql = "SELECT * FROM expenses 
                WHERE DATE(created_at) BETWEEN ? AND ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssii", $start_date, $end_date, $records_per_page, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Calculate totals
            while ($row = mysqli_fetch_assoc($result)) {
                $total_amount += $row['amount'];
                $expenses[] = $row;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        // Calculate totals by category
        $category_sql = "SELECT category, SUM(amount) as total 
                         FROM expenses 
                         WHERE DATE(created_at) BETWEEN ? AND ?
                         GROUP BY category";
        $category_stmt = mysqli_prepare($conn, $category_sql);
        
        if ($category_stmt) {
            mysqli_stmt_bind_param($category_stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($category_stmt);
            $category_result = mysqli_stmt_get_result($category_stmt);
            
            while ($row = mysqli_fetch_assoc($category_result)) {
                $category_totals[$row['category']] = $row['total'];
            }
            mysqli_stmt_close($category_stmt);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="expense.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Expense Management</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="expense-container">
        <div class="main-container">
            <?php if (!$table_exists): ?>
                <!-- Setup Notice -->
                <div class="setup-notice">
                    <div class="setup-content">
                        <i class="fa-solid fa-database fa-3x" style="color: var(--orange-500); margin-bottom: 1rem;"></i>
                        <h2>Database Setup Required</h2>
                        <p>The expenses table hasn't been created yet. Click the button below to set up the database.</p>
                        <a href="create-table.php" class="btn-setup">
                            <i class="fa-solid fa-wrench"></i> Setup Database
                        </a>
                    </div>
                </div>
            <?php else: ?>
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <p class="page-subtitle">Track and manage business expenses</p>
                </div>
                <div class="header-actions">
                    <button class="btn-add-expense" onclick="openAddExpenseModal()">
                        <i class="fa-solid fa-plus"></i> Add Expense
                    </button>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h3>Total Expenses</h3>
                        <p class="amount">₱<?php echo number_format($total_amount, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <h3>Total Records</h3>
                        <p class="amount"><?php echo $total_records; ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </div>
                        <h3>Fixed Costs</h3>
                        <p class="amount">₱<?php echo number_format($category_totals['Fixed Costs'] ?? 0, 2); ?></p>
                        <p class="period">Category total</p>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                        </div>
                        <h3>Variable Costs</h3>
                        <p class="amount">₱<?php echo number_format($category_totals['Variable Costs'] ?? 0, 2); ?></p>
                        <p class="period">Category total</p>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path>
                                <line x1="16" y1="8" x2="2" y2="22"></line>
                                <line x1="17.5" y1="15" x2="9" y2="15"></line>
                            </svg>
                        </div>
                        <h3>Overhead Costs</h3>
                        <p class="amount">₱<?php echo number_format($category_totals['Overhead Costs'] ?? 0, 2); ?></p>
                        <p class="period">Category total</p>
                    </div>
                </div>
            </div>
            <!-- Filter Controls -->
            <div class="controls-section">
                <div class="filter-group">
                    <label class="filter-label">Time Period:</label>
                    <div class="filter-buttons">
                        <button class="filter-btn <?php echo $selected_period == '7days' ? 'active' : ''; ?>" onclick="filterByPeriod('7days')">
                            Last 7 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == '30days' ? 'active' : ''; ?>" onclick="filterByPeriod('30days')">
                            Last 30 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == '90days' ? 'active' : ''; ?>" onclick="filterByPeriod('90days')">
                            Last 90 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == 'custom' ? 'active' : ''; ?>" onclick="toggleCustomFilter()">
                            Custom Range
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Date Filter -->
            <div id="custom-filter" class="custom-filter <?php echo $selected_period == 'custom' ? 'active' : ''; ?>">
                <div class="date-input-group">
                    <label class="filter-label">Start Date:</label>
                    <input type="date" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="date-input-group">
                    <label class="filter-label">End Date:</label>
                    <input type="date" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <button onclick="applyCustomFilter()" class="btn btn-primary">Apply Filter</button>
            </div>

            <!-- Expenses Table -->
            <div class="expense-container-table">
                <div class="table-wrapper">
                    <table class="expense-table" id="expenseTable">
                        <thead>
                            <tr>
                                <th>Date Created</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="expenses-tbody">
                            <?php if (count($expenses) > 0): ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($expense['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($expense['name']); ?></td>
                                        <td>
                                            <span class="category-badge category-<?php echo strtolower(str_replace(' ', '-', $expense['category'])); ?>">
                                                <?php echo htmlspecialchars($expense['category']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                        <td class="note-cell"><?php echo htmlspecialchars($expense['note']); ?></td>
                                        <td>
                                            <button class="btn-delete" onclick="deleteExpense(<?php echo $expense['id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-expenses">
                                        <div style="text-align: center; padding: 3rem;">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; opacity: 0.5;">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.35-4.35"></path>
                                            </svg>
                                            <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">No expenses found</h3>
                                            <p style="color: var(--gray-500);">No expenses recorded for the selected period</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <span>Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> expenses</span>
                        </div>
                        
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => ($page - 1)])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => ($page + 1)])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-overlay" onclick="closeAddExpenseModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Expense</h2>
                <button class="close-modal" onclick="closeAddExpenseModal()">&times;</button>
            </div>
            <form id="addExpenseForm" onsubmit="submitExpense(event)">
                <div class="form-group">
                    <label for="expense-name">Name <span class="required">*</span></label>
                    <input type="text" id="expense-name" name="name" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="expense-category">Category <span class="required">*</span></label>
                    <select id="expense-category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Fixed Costs">Fixed Costs (Staff Salary, Bills)</option>
                        <option value="Variable Costs">Variable Costs (Ingredients, Packaging, Utilities)</option>
                        <option value="Overhead Costs">Overhead Costs (Equipment Maintenance)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="expense-amount">Amount <span class="required">*</span></label>
                    <input type="number" id="expense-amount" name="amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="expense-note">Note</label>
                    <textarea id="expense-note" name="note" maxlength="100" rows="3"></textarea>
                    <small class="char-count"><span id="char-count">0</span>/100 characters</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddExpenseModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-text">Add Expense</span>
                        <span class="btn-loader" style="display: none;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Adding...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-overlay" onclick="closeDeleteModal()"></div>
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-delete-confirm" id="deleteBtn" onclick="confirmDelete()">
                    <span class="btn-text">Delete</span>
                    <span class="btn-loader" style="display: none;">
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script>
        let deleteExpenseId = null;

        // Character counter for note field
        document.getElementById('expense-note')?.addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('char-count').textContent = charCount;
        });

        // Filter functions
        function filterByPeriod(period) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('period', period);
            urlParams.delete('start_date');
            urlParams.delete('end_date');
            urlParams.delete('page');
            window.location.search = urlParams.toString();
        }

        function toggleCustomFilter() {
            const customFilter = document.getElementById('custom-filter');
            customFilter.classList.toggle('active');
        }

        function applyCustomFilter() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (startDate && endDate) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('start_date', startDate);
                urlParams.set('end_date', endDate);
                urlParams.delete('period');
                urlParams.delete('page');
                window.location.search = urlParams.toString();
            } else {
                alert('Please select both start and end dates');
            }
        }

        // Modal functions
        function openAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'flex';
            document.getElementById('addExpenseForm').reset();
            document.getElementById('char-count').textContent = '0';
        }

        function closeAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteExpenseId = null;
        }

        // Submit expense
        function submitExpense(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            
            // Show loader
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-flex';
            submitBtn.disabled = true;
            
            const formData = new FormData(event.target);
            
            fetch('add-expense.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // Debug log
                
                if (data.success) {
                    alert('Expense added successfully!');
                    window.location.reload();
                } else {
                    if (data.setup_required) {
                        if (confirm(data.error + '\n\nWould you like to set up the database now?')) {
                            window.location.href = 'create-table.php';
                        }
                    } else {
                        alert('Error: ' + (data.error || 'Failed to add expense'));
                    }
                    // Hide loader on error
                    btnText.style.display = 'inline';
                    btnLoader.style.display = 'none';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding expense. Please try again.');
                // Hide loader on error
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                submitBtn.disabled = false;
            });
        }

        // Delete expense
        function deleteExpense(id) {
            deleteExpenseId = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function confirmDelete() {
            if (!deleteExpenseId) return;
            
            const deleteBtn = document.getElementById('deleteBtn');
            const btnText = deleteBtn.querySelector('.btn-text');
            const btnLoader = deleteBtn.querySelector('.btn-loader');
            
            // Show loader
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-flex';
            deleteBtn.disabled = true;
            
            fetch('delete-expense.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: deleteExpenseId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Expense deleted successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete expense'));
                    // Hide loader on error
                    btnText.style.display = 'inline';
                    btnLoader.style.display = 'none';
                    deleteBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting expense. Please try again.');
                // Hide loader on error
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                deleteBtn.disabled = false;
            })
            .finally(() => {
                if (deleteBtn.disabled) {
                    // Only close if we're still in loading state (success case)
                    closeDeleteModal();
                }
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addExpenseModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === addModal) {
                closeAddExpenseModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // Render Expense Categories Chart
        function renderExpenseCategoriesChart() {
            const ctx = document.getElementById('expenseCategoriesChart').getContext('2d');
            
            // Get values from PHP
            const fixedCosts = parseFloat('<?php echo $category_totals['Fixed Costs'] ?? 0; ?>');
            const variableCosts = parseFloat('<?php echo $category_totals['Variable Costs'] ?? 0; ?>');
            const overheadCosts = parseFloat('<?php echo $category_totals['Overhead Costs'] ?? 0; ?>');
            
            const labels = ['Fixed Costs', 'Variable Costs', 'Overhead Costs'];
            const data = [fixedCosts, variableCosts, overheadCosts];
            const colors = ['#3b82f6', '#22c55e', '#f59e0b'];
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderExpenseCategoriesChart();
        });
    </script>
</body>
</html>
