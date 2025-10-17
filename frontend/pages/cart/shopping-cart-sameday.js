/**
 * Shopping Cart Same-Day Functionality
 * Handles cart operations specific to same-day/available today products
 */

let currentCartId = null;

// Confirmation popup functionality
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

function showConfirmationModalSameDay(cartId) {
    currentCartId = cartId;
    document.getElementById('confirmationModal').style.display = 'block';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    currentCartId = null;
}

// Update quantity for same-day items
function updateQuantitySameDay(cartId, newQuantity) {
    if (newQuantity < 1) {
        showConfirmationModalSameDay(cartId);
        return;
    }

    const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
    const stock = parseInt(row.dataset.stock);
    
    if (newQuantity > stock) {
        showConfirmation(`Cannot exceed available stock of ${stock}`, true);
        return;
    }

    fetch("update-cart-quantity-sameday.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showConfirmation("Error: " + (data.error || "Failed to update quantity"), true);
        }
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while updating the cart", true);
    });
}

// Remove item from same-day cart
function removeFromCartSameDay(cartId) {
    fetch("remove-from-cart-sameday.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showConfirmation("Item removed successfully");
            setTimeout(() => location.reload(), 1000);
        } else {
            showConfirmation("Error: " + data.error, true);
        }
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while removing the item", true);
    });
}

// Update subtotal calculation
function updateSubtotal() {
    let subtotal = 0;
    const selectedCartIds = [];

    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
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

// Validate cart before checkout
function validateCart() {
    // Check if terms are accepted
    if (!document.getElementById('termsCheckbox').checked) {
        showConfirmation('Please accept the Terms and Conditions', true);
        return false;
    }

    // Check if any item is selected
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    
    if (selectedItems.length === 0) {
        showConfirmation('Please select at least one item to checkout', true);
        return false;
    }

    // Check stock availability
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

// Update select all checkbox state
function updateSamedaySelectAllState() {
    const samedayCheckboxes = document.querySelectorAll('.sameday-checkbox');
    const selectAllSameDay = document.getElementById('selectAllSameDay');
    const checkedSameday = document.querySelectorAll('.sameday-checkbox:checked');
    
    if (selectAllSameDay) {
        selectAllSameDay.checked = checkedSameday.length === samedayCheckboxes.length && samedayCheckboxes.length > 0;
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Setup select all checkbox
    const selectAllSameDay = document.getElementById('selectAllSameDay');
    if (selectAllSameDay) {
        selectAllSameDay.addEventListener('change', function() {
            const samedayCheckboxes = document.querySelectorAll('.sameday-checkbox');
            samedayCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSubtotal();
        });
    }
    
    // Setup individual checkbox listeners
    const allItemCheckboxes = document.querySelectorAll('.sameday-checkbox');
    allItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSubtotal();
            updateSamedaySelectAllState();
        });
    });
    
    // Setup confirmation modal button
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (currentCartId) {
                removeFromCartSameDay(currentCartId);
                closeConfirmationModal();
            }
        });
    }
    
    // Initialize subtotal
    updateSubtotal();
});

// Page transition functionality
function smoothSwitchToPage(targetPage, event) {
    console.log('smoothSwitchToPage called with:', targetPage);
    
    if (event) {
        event.preventDefault();
    }
    
    // Don't switch if already on the target page
    const currentPage = window.location.pathname.split('/').pop();
    
    if (currentPage === targetPage) {
        return;
    }
    
    // Show transition overlay
    const overlay = document.getElementById('pageTransitionOverlay');
    
    if (overlay) {
        overlay.style.display = 'block';
        overlay.offsetHeight; // Force reflow
        overlay.classList.add('active');
        
        // Navigate after short delay
        setTimeout(() => {
            window.location.href = targetPage;
        }, 500);
    } else {
        // Fallback if overlay not found
        window.location.href = targetPage;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target == modal) {
        closeConfirmationModal();
    }
};

