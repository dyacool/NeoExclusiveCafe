// Bulk Order Form JavaScript - Enhanced User Experience

let selectedProducts = [];
let totalAmount = 0;

// Initialize the form when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeForm();
  setupEventListeners();
  updateOrderSummary(); // Initialize empty summary
});

function initializeForm() {
  // Clear any existing form data
  localStorage.clear();
  sessionStorage.clear();

  // Initialize delivery address visibility
  const orderTypeSelect = document.getElementById("order_type");
  const deliveryAddressGroup = document.getElementById(
    "delivery_address_group"
  );

  if (orderTypeSelect && deliveryAddressGroup) {
    orderTypeSelect.addEventListener("change", function () {
      if (this.value === "delivery") {
        deliveryAddressGroup.style.display = "block";
        deliveryAddressGroup.classList.add("show");
        document.getElementById("delivery_address").required = true;
      } else {
        deliveryAddressGroup.style.display = "none";
        deliveryAddressGroup.classList.remove("show");
        document.getElementById("delivery_address").required = false;
      }
    });
  }
}

function setupEventListeners() {
  // Product selection event listeners
  const productCheckboxes = document.querySelectorAll(".product-select");
  productCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      handleProductSelection(this);
    });
  });

  // Discard button
  const discardBtn = document.getElementById("discardBtn");
  if (discardBtn) {
    discardBtn.addEventListener("click", function () {
      if (
        confirm(
          "Are you sure you want to discard this order? All information will be lost."
        )
      ) {
        clearForm();
      }
    });
  }

  // Review Order button (renamed from submitBtn)
  const reviewOrderBtn = document.getElementById("reviewOrderBtn");
  if (reviewOrderBtn) {
    reviewOrderBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (validateForm()) {
        showConfirmationModal();
      }
    });
  }

  // Confirmation modal event listeners
  setupConfirmationModal();
}

function handleProductSelection(checkbox) {
  const productId = String(checkbox.value); // Ensure it's a string for consistency
  const productCard = document.getElementById("card_" + productId);
  const quantitySection = document.getElementById(
    "quantity_section_" + productId
  );

  if (checkbox.checked) {
    // Add product to selection
    if (productCard) productCard.classList.add("selected");
    if (quantitySection) quantitySection.classList.add("show");

    // Get current quantity from the field (in case it was changed before checking)
    const quantityField = document.getElementById("quantity_" + productId);
    const currentQuantity = quantityField
      ? parseInt(quantityField.value) || 12
      : 12;

    // Add to selected products array
    const productData = {
      id: productId,
      name: checkbox.dataset.name,
      price: parseFloat(checkbox.dataset.price),
      quantity: currentQuantity,
    };
    selectedProducts.push(productData);

    console.log("Product added to selection:", productData); // Debug log

    // Initialize quantity controls
    initializeQuantityControls(productId);
  } else {
    // Remove product from selection
    if (productCard) productCard.classList.remove("selected");
    if (quantitySection) quantitySection.classList.remove("show");

    // Remove from selected products array
    selectedProducts = selectedProducts.filter(
      (p) => String(p.id) !== String(productId)
    );

    console.log("Product removed from selection:", productId); // Debug log
  }

  updateOrderSummary();
  updateSubmitButton();
}

function initializeQuantityControls(productId) {
  const quantityField = document.getElementById("quantity_" + productId);

  if (quantityField) {
    // Set minimum value and default
    quantityField.min = 12;
    quantityField.value = 12;

    // Add event listeners for quantity changes (both change and input events)
    quantityField.addEventListener("change", function () {
      updateQuantity(productId);
    });

    quantityField.addEventListener("input", function () {
      updateQuantity(productId);
    });

    // Add event listener for keyboard input
    quantityField.addEventListener("keyup", function () {
      updateQuantity(productId);
    });

    // Update initial subtotal
    updateSubtotal(productId);
  }
}

function increaseQuantity(productId) {
  const quantityField = document.getElementById("quantity_" + productId);
  if (quantityField) {
    const currentValue = parseInt(quantityField.value) || 12;
    quantityField.value = currentValue + 1;
    updateQuantity(productId);
  }
}

function decreaseQuantity(productId) {
  const quantityField = document.getElementById("quantity_" + productId);
  if (quantityField) {
    const currentValue = parseInt(quantityField.value) || 12;
    if (currentValue > 12) {
      quantityField.value = currentValue - 1;
      updateQuantity(productId);
    }
  }
}

function updateQuantity(productId) {
  const quantityField = document.getElementById("quantity_" + productId);
  if (!quantityField) {
    console.error("Quantity field not found for product:", productId);
    return;
  }

  let quantity = parseInt(quantityField.value) || 12;

  // Ensure minimum quantity
  if (quantity < 12) {
    quantity = 12;
    quantityField.value = 12;
  }

  // Convert productId to string for consistent comparison
  const productIdStr = String(productId);

  // Update the selected products array
  const productIndex = selectedProducts.findIndex(
    (p) => String(p.id) === productIdStr
  );

  if (productIndex !== -1) {
    selectedProducts[productIndex].quantity = quantity;
    console.log(
      `✓ Updated product ${productId} quantity to ${quantity}`,
      selectedProducts[productIndex]
    ); // Debug log
  } else {
    console.error(
      `✗ Product ${productId} not found in selectedProducts array`,
      selectedProducts
    );
  }

  updateSubtotal(productId);
  updateOrderSummary();
  updateSubmitButton();
}

// Make updateQuantity globally accessible for HTML onchange attribute
window.updateQuantity = updateQuantity;

function updateSubtotal(productId) {
  const quantityField = document.getElementById("quantity_" + productId);
  const checkbox = document.getElementById("product_" + productId);
  const subtotalElement = document.getElementById("subtotal_" + productId);

  if (quantityField && checkbox && subtotalElement) {
    const quantity = parseInt(quantityField.value) || 12;
    const price = parseFloat(checkbox.dataset.price) || 0;
    const subtotal = price * quantity;

    // Add visual feedback
    subtotalElement.classList.add("updating");
    subtotalElement.textContent = subtotal.toFixed(2);

    // Remove visual feedback after animation
    setTimeout(() => {
      subtotalElement.classList.remove("updating");
    }, 300);
  }
}

function updateOrderSummary() {
  const orderSummary = document.getElementById("orderSummary");
  if (!orderSummary) return;

  console.log("📊 Updating Order Summary with products:", selectedProducts); // Debug log

  if (selectedProducts.length === 0) {
    orderSummary.innerHTML = `
            <div class="summary-empty">
                <h4>No Products Selected</h4>
                <p>Choose products from the selection above to see your order summary</p>
            </div>
        `;
    totalAmount = 0;
    return;
  }

  let summaryHTML = '<div class="summary-items">';
  let total = 0;
  let totalItems = 0;

  selectedProducts.forEach((product) => {
    const subtotal = product.price * product.quantity;
    total += subtotal;
    totalItems += product.quantity;

    console.log(
      `  - ${product.name}: ${product.quantity} × ₱${product.price} = ₱${subtotal}`
    ); // Debug log

    summaryHTML += `
            <div class="summary-item">
                <div class="item-details">
                    <div class="item-name">${product.name}</div>
                    <div class="item-calculation">${
                      product.quantity
                    } × ₱${product.price.toFixed(2)}</div>
                </div>
                <div class="item-total">${subtotal.toFixed(2)}</div>
            </div>
        `;
  });

  summaryHTML += "</div>";
  summaryHTML += '<div class="summary-divider"></div>';
  summaryHTML += `
        <div class="summary-totals">
            <div class="total-row">
                <span class="total-label">Subtotal</span>
                <span class="total-value">₱${total.toFixed(2)}</span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total Amount</span>
                <span class="total-value">${total.toFixed(2)}</span>
            </div>
        </div>
        <div class="items-count">
            <strong>${totalItems}</strong> total items selected
        </div>
    `;

  orderSummary.innerHTML = summaryHTML;
  totalAmount = total;

  console.log(
    `💰 Order Summary Total: ₱${total.toFixed(2)} (${totalItems} items)`
  ); // Debug log
}

function updateSubmitButton() {
  const reviewOrderBtn = document.getElementById("reviewOrderBtn");
  if (!reviewOrderBtn) return;

  if (selectedProducts.length > 0) {
    reviewOrderBtn.disabled = false;
    reviewOrderBtn.innerHTML = `Review Order (₱${totalAmount.toFixed(2)})`;
  } else {
    reviewOrderBtn.disabled = true;
    reviewOrderBtn.innerHTML = "Review Order";
  }
}

function validateForm() {
  // Check if products are selected
  if (selectedProducts.length === 0) {
    alert("Please select at least one product for your bulk order.");
    return false;
  }

  // Check required fields
  const requiredFields = [
    "name",
    "contact",
    "email",
    "billing_address",
    "order_type",
    "purpose",
    "date_needed",
    "time_needed",
  ];

  for (const field of requiredFields) {
    const element = document.getElementById(field);
    if (!element || !element.value.trim()) {
      if (element) element.focus();
      alert(`Please fill in the ${field.replace("_", " ")} field.`);
      return false;
    }
  }

  // Check delivery address if delivery is selected
  const orderType = document.getElementById("order_type");
  if (orderType && orderType.value === "delivery") {
    const deliveryAddress = document.getElementById("delivery_address");
    if (!deliveryAddress || !deliveryAddress.value.trim()) {
      if (deliveryAddress) deliveryAddress.focus();
      alert("Please provide a delivery address.");
      return false;
    }
  }

  return true;
}

function clearForm() {
  // Reset form
  const form = document.getElementById("bulkOrderForm");
  if (form) form.reset();

  // Clear selections
  selectedProducts = [];
  totalAmount = 0;

  // Reset product cards
  document.querySelectorAll(".product-card").forEach((card) => {
    card.classList.remove("selected");
  });

  // Hide quantity sections
  document.querySelectorAll(".quantity-section").forEach((section) => {
    section.classList.remove("show");
  });

  // Reset delivery address visibility
  const deliveryGroup = document.getElementById("delivery_address_group");
  if (deliveryGroup) {
    deliveryGroup.style.display = "none";
    deliveryGroup.classList.remove("show");
  }

  // Update summary and button
  updateOrderSummary();
  updateSubmitButton();

  // Scroll to top
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// Prevent form restoration on page load
window.addEventListener("load", function () {
  // Clear any stored form data
  if (typeof Storage !== "undefined") {
    localStorage.clear();
    sessionStorage.clear();
  }

  // Reset form to prevent browser auto-fill restoration
  setTimeout(() => {
    const form = document.getElementById("bulkOrderForm");
    if (form) {
      form.reset();
      selectedProducts = [];
      totalAmount = 0;
      updateOrderSummary();
      updateSubmitButton();
    }
  }, 100);
});

// Prevent back button cache issues
window.addEventListener("pageshow", function (event) {
  if (
    event.persisted ||
    (window.performance && window.performance.navigation.type === 2)
  ) {
    window.location.reload();
  }
});

// Global functions for quantity buttons (called from HTML onclick)
window.increaseQuantity = increaseQuantity;
window.decreaseQuantity = decreaseQuantity;

// Confirmation Modal Functions
function setupConfirmationModal() {
  const modal = document.getElementById("confirmationModal");
  const closeBtn = modal.querySelector(".close");
  const editOrderBtn = document.getElementById("editOrderBtn");
  const confirmSubmitBtn = document.getElementById("confirmSubmitBtn");

  // Close modal event listeners
  closeBtn.addEventListener("click", closeConfirmationModal);
  editOrderBtn.addEventListener("click", closeConfirmationModal);

  // Click outside modal to close
  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeConfirmationModal();
    }
  });

  // Confirm submit button
  confirmSubmitBtn.addEventListener("click", function () {
    submitFinalOrder();
  });
}

function showConfirmationModal() {
  const modal = document.getElementById("confirmationModal");

  // Populate customer information
  document.getElementById("confirm-name").textContent =
    document.getElementById("name").value;
  document.getElementById("confirm-contact").textContent =
    document.getElementById("contact").value;
  document.getElementById("confirm-email").textContent =
    document.getElementById("email").value;
  document.getElementById("confirm-billing-address").textContent =
    document.getElementById("billing_address").value;

  // Populate order details
  const orderType = document.getElementById("order_type").value;
  document.getElementById("confirm-order-type").textContent =
    orderType === "delivery" ? "Delivery" : "Pickup";

  const deliveryAddressSection = document.getElementById(
    "delivery-address-section"
  );
  if (orderType === "delivery") {
    deliveryAddressSection.style.display = "block";
    document.getElementById("confirm-delivery-address").textContent =
      document.getElementById("delivery_address").value;
  } else {
    deliveryAddressSection.style.display = "none";
  }

  document.getElementById("confirm-purpose").textContent =
    document.getElementById("purpose").value;
  document.getElementById("confirm-date-needed").textContent = formatDate(
    document.getElementById("date_needed").value
  );
  document.getElementById("confirm-time-needed").textContent = formatTime(
    document.getElementById("time_needed").value
  );
  document.getElementById("confirm-note").textContent =
    document.getElementById("note").value || "None";

  // Populate order items
  const orderItemsContainer = document.getElementById("confirm-order-items");
  orderItemsContainer.innerHTML = "";

  selectedProducts.forEach((product) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${product.name}</td>
      <td>₱${product.price.toFixed(2)}</td>
      <td>${product.quantity}</td>
      <td>₱${(product.price * product.quantity).toFixed(2)}</td>
    `;
    orderItemsContainer.appendChild(row);
  });

  // Update total amount
  document.getElementById(
    "confirm-total-amount"
  ).textContent = `₱${totalAmount.toFixed(2)}`;

  // Show modal
  modal.style.display = "block";
  document.body.style.overflow = "hidden"; // Prevent background scrolling
}

function closeConfirmationModal() {
  const modal = document.getElementById("confirmationModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto"; // Restore scrolling
}

function submitFinalOrder() {
  // Populate hidden form with all data
  document.getElementById("final-name").value =
    document.getElementById("name").value;
  document.getElementById("final-contact").value =
    document.getElementById("contact").value;
  document.getElementById("final-email").value =
    document.getElementById("email").value;
  document.getElementById("final-billing-address").value =
    document.getElementById("billing_address").value;
  document.getElementById("final-order-type").value =
    document.getElementById("order_type").value;
  document.getElementById("final-delivery-address").value =
    document.getElementById("delivery_address").value;
  document.getElementById("final-purpose").value =
    document.getElementById("purpose").value;
  document.getElementById("final-date-needed").value =
    document.getElementById("date_needed").value;
  document.getElementById("final-time-needed").value =
    document.getElementById("time_needed").value;
  document.getElementById("final-note").value =
    document.getElementById("note").value;
  document.getElementById("final-selected-products").value =
    JSON.stringify(selectedProducts);
  document.getElementById("final-total-amount").value = totalAmount;

  // Show loading state
  const confirmSubmitBtn = document.getElementById("confirmSubmitBtn");
  confirmSubmitBtn.innerHTML = "Submitting...";
  confirmSubmitBtn.disabled = true;

  // Submit the form
  document.getElementById("finalSubmissionForm").submit();
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const date = new Date();
  date.setHours(parseInt(hours), parseInt(minutes));
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });
}
