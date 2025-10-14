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
          "Are you sure you want to discard this order? All entered information will be lost."
        )
      ) {
        clearForm();
      }
    });
  }

  // Form submission
  const form = document.getElementById("bulkOrderForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      if (!validateForm()) {
        e.preventDefault();
      } else {
        // Update hidden fields before submission
        document.getElementById("selectedProducts").value =
          JSON.stringify(selectedProducts);
        document.getElementById("totalAmount").value = totalAmount;

        // Show loading state
        const submitBtn = document.getElementById("submitBtn");
        submitBtn.classList.add("loading");
        submitBtn.disabled = true;
      }
    });
  }
}

function handleProductSelection(checkbox) {
  const productId = checkbox.value;
  const productCard = document.getElementById("card_" + productId);
  const quantitySection = document.getElementById(
    "quantity_section_" + productId
  );

  if (checkbox.checked) {
    // Add product to selection
    if (productCard) productCard.classList.add("selected");
    if (quantitySection) quantitySection.classList.add("show");

    // Add to selected products array
    const productData = {
      id: productId,
      name: checkbox.dataset.name,
      price: parseFloat(checkbox.dataset.price),
      quantity: 12,
    };
    selectedProducts.push(productData);

    // Initialize quantity controls
    initializeQuantityControls(productId);
  } else {
    // Remove product from selection
    if (productCard) productCard.classList.remove("selected");
    if (quantitySection) quantitySection.classList.remove("show");

    // Remove from selected products array
    selectedProducts = selectedProducts.filter((p) => p.id !== productId);
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

    // Add event listener for quantity changes
    quantityField.addEventListener("change", function () {
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
  if (!quantityField) return;

  let quantity = parseInt(quantityField.value) || 12;

  // Ensure minimum quantity
  if (quantity < 12) {
    quantity = 12;
    quantityField.value = 12;
  }

  // Update the selected products array
  const productIndex = selectedProducts.findIndex((p) => p.id === productId);
  if (productIndex !== -1) {
    selectedProducts[productIndex].quantity = quantity;
  }

  updateSubtotal(productId);
  updateOrderSummary();
  updateSubmitButton();
}

function updateSubtotal(productId) {
  const quantityField = document.getElementById("quantity_" + productId);
  const checkbox = document.getElementById("product_" + productId);
  const subtotalElement = document.getElementById("subtotal_" + productId);

  if (quantityField && checkbox && subtotalElement) {
    const quantity = parseInt(quantityField.value) || 12;
    const price = parseFloat(checkbox.dataset.price) || 0;
    const subtotal = price * quantity;

    subtotalElement.textContent = subtotal.toFixed(2);
  }
}

function updateOrderSummary() {
  const orderSummary = document.getElementById("orderSummary");
  if (!orderSummary) return;

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
}

function updateSubmitButton() {
  const submitBtn = document.getElementById("submitBtn");
  if (!submitBtn) return;

  if (selectedProducts.length > 0) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = `Submit Order (₱${totalAmount.toFixed(2)})`;
  } else {
    submitBtn.disabled = true;
    submitBtn.innerHTML = "Submit Order";
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
