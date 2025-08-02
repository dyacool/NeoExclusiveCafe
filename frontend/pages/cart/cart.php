<?php
session_start();

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/users/cart.css" />
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/users/confirmations.css" />
    <title>Shopping Cart</title>
    <style>
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
        }
        .confirmation-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            min-width: 300px;
        }
        .confirmation-modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .confirmation-modal-buttons button {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .confirm-btn {
            background-color: #f44336;
            color: white;
        }
        .cancel-btn {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>

<div id="confirmationPopup" class="confirmation-popup"></div>

<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-modal-content">
        <h3>Remove Item</h3>
        <p>Are you sure you want to remove this item from your cart?</p>
        <div class="confirmation-modal-buttons">
            <button class="cancel-btn" onclick="closeConfirmationModal()">Cancel</button>
            <button class="confirm-btn" id="confirmRemoveBtn">Remove</button>
        </div>
    </div>
</div>

<?php include '../../user-includes/user-header.php'; ?>

<div class="wrapper fade-in">
    <div class="main-container">
        <h2>Shopping Cart</h2>
        <p class="cart-info">
            Please note: NeoCafe operates on a preorder basis to ensure the freshness of our baked goods.<br>
            Orders must be placed at least 24 hours in advance.
        </p>

        <?php
        $stmt = $conn->prepare("
            SELECT c.id AS cart_id, c.quantity, c.price,
                   p.id AS product_id, p.name AS product_name, p.quantity as product_stock,
                   pi.image_url
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
        <form method="POST" action="checkout.php" id="cartForm">
            <div class="select-all-container">
                <input type="checkbox" id="selectAll" class="item-checkbox">
                <label for="selectAll">Select All Items</label>
            </div>
            
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th></th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        $cart_items = [];
                        while ($row = $result->fetch_assoc()):
                            $imageUrl = $row['image_url'] ? "../../" . $row['image_url'] : "/NeoExclusiveCafe/assets/default-product.jpg";
                            $item_total = $row['price'] * $row['quantity'];
                            $subtotal += $item_total;
                            $cart_items[] = $row['cart_id'];
                        ?>
                        <tr data-cart-id="<?= $row['cart_id'] ?>" data-product-id="<?= $row['product_id'] ?>" data-stock="<?= $row['product_stock'] ?>" data-price="<?= $row['price'] ?>" data-quantity="<?= $row['quantity'] ?>">
                            <td>
                                <input type="checkbox" name="selected_cart_ids[]" value="<?= $row['cart_id'] ?>" class="item-checkbox" data-total="<?= $item_total ?>">
                            </td>
                            <td><img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width: 60px; height: 60px; object-fit: cover;"></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] - 1 ?>)">-</button>
                                    <span class="quantity"><?= $row['quantity'] ?></span>
                                    <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] + 1 ?>)">+</button>
                                </div>
                            </td>
                            <td>₱<?= number_format($row['price'], 2) ?></td>
                            <td class="item-total">₱<?= number_format($item_total, 2) ?></td>
                            <td>
                                <button class="remove-btn" type="button" onclick="showConfirmationModal(<?= $row['cart_id'] ?>)">
                                    <svg viewBox="0 0 448 512" class="svgIcon"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <div class="cart-summary">
                <div class="cart-total"><h3>Subtotal: ₱<span id="displaySubtotal">0.00</span></h3></div>
                <label><input type="checkbox" id="termsCheckbox" required /> I have read and agreed with the Terms and Conditions</label>
                <button type="submit" name="checkout" class="checkout-btn" onclick="return validateCart()">Proceed to Checkout</button>
                <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                <input type="hidden" name="cart_items" id="cartItemsInput" value="">
            </div>
        </form>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="/NeoExclusiveCafe/pages/users/product-dashboard.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let currentCartId = null;

function showConfirmation(message, isError = false) {
    const popup = document.getElementById('confirmationPopup');
    popup.textContent = message;
    popup.className = 'confirmation-popup' + (isError ? ' error' : '');
    popup.classList.add('show');
    
    setTimeout(() => {
        popup.classList.remove('show');
        popup.classList.add('hide');
        setTimeout(() => {
            popup.classList.remove('hide');
        }, 300);
    }, 3000);
}

function showConfirmationModal(cartId) {
    currentCartId = cartId;
    document.getElementById('confirmationModal').style.display = 'block';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    currentCartId = null;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox:not(#selectAll)');
    
    // Set up event listeners
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSubtotal();
    });
    
    // Setup individual checkbox listeners
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSubtotal();
            updateSelectAllCheckbox();
        });
    });

    // Setup confirmation modal button
    document.getElementById('confirmRemoveBtn').addEventListener('click', function() {
        if (currentCartId) {
            removeItem(currentCartId);
            closeConfirmationModal();
        }
    });
    
    // Initialize subtotal
    updateSubtotal();
});

function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox:not(#selectAll)');
    const checkedBoxes = document.querySelectorAll('.item-checkbox:not(#selectAll):checked');
    
    selectAllCheckbox.checked = checkedBoxes.length === itemCheckboxes.length;
}

function updateSubtotal() {
    let subtotal = 0;
    const selectedCartIds = [];
    
    document.querySelectorAll('.item-checkbox:not(#selectAll):checked').forEach(checkbox => {
        const row = checkbox.closest('tr');
        const price = parseFloat(row.dataset.price);
        const quantity = parseInt(row.dataset.quantity);
        subtotal += price * quantity;
        selectedCartIds.push(checkbox.value);
    });
    
    document.getElementById('displaySubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('subtotalInput').value = subtotal;
    document.getElementById('cartItemsInput').value = selectedCartIds.join(',');
}

function validateCart() {
    // Check if terms are accepted
    if (!document.getElementById('termsCheckbox').checked) {
        showConfirmation('Please accept the Terms and Conditions', true);
        return false;
    }
    
    // Check if any item is selected
    const selectedItems = document.querySelectorAll('.item-checkbox:not(#selectAll):checked');
    if (selectedItems.length === 0) {
        showConfirmation('Please select at least one item to checkout', true);
        return false;
    }

    // Check stock availability for selected items only
    let hasInsufficientStock = false;
    selectedItems.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const quantity = parseInt(row.dataset.quantity);
        const stock = parseInt(row.dataset.stock);
        if (quantity > stock) {
            hasInsufficientStock = true;
            showConfirmation(`Insufficient stock for ${row.querySelector('td:nth-child(3)').textContent}. Available: ${stock}`, true);
        }
    });

    if (hasInsufficientStock) {
        return false;
    }

    return true;
}

function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) return;

    const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
    const stock = parseInt(row.dataset.stock);
    
    if (newQuantity > stock) {
        showConfirmation(`Cannot exceed available stock of ${stock}`, true);
        return;
    }

    fetch("../../php/users/update-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else showConfirmation("Error: " + data.error, true);
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while updating the cart", true);
    });
}

function removeItem(cartId) {
    fetch("../../php/users/remove-from-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showConfirmation("Item removed successfully");
            setTimeout(() => location.reload(), 1000);
        }
        else showConfirmation("Error: " + data.error, true);
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while removing the item", true);
    });
}

// Update the remove button click handler in the table
document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.onclick = function() {
        const cartId = this.closest('tr').dataset.cartId;
        showConfirmationModal(cartId);
    };
});
</script>
</body>
</html>