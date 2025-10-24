// Bulk Order Form JavaScript - Updated for Quotation System (No Prices)

let selectedProducts = [];

// Toggle quantity section visibility when checkbox is checked/unchecked
function toggleQuantitySection(productId) {
  const checkbox = document.getElementById("product_" + productId);
  const quantitySection = document.getElementById(
    "quantity_section_" + productId
  );
  const quantityInput = document.getElementById("quantity_" + productId);

  if (checkbox && quantitySection) {
    if (checkbox.checked) {
      quantitySection.style.display = "block";
      quantitySection.classList.add("show");
      // Reset to minimum quantity when checked
      if (quantityInput) {
        quantityInput.value = 10;
      }
    } else {
      quantitySection.style.display = "none";
      quantitySection.classList.remove("show");
      // Reset quantity when unchecked
      if (quantityInput) {
        quantityInput.value = 10;
      }
    }
    // Update the order summary
    updateOrderSummary();
  }
}

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
  // Discard button
  const discardBtn = document.getElementById("discardBtn");
  if (discardBtn) {
    discardBtn.addEventListener("click", function () {
      if (
        confirm(
          "Are you sure you want to discard this quotation request? All information will be lost."
        )
      ) {
        clearForm();
      }
    });
  }

  // Review Order button
  const reviewOrderBtn = document.getElementById("reviewOrderBtn");
  if (reviewOrderBtn) {
    console.log("Review Order button found and event listener attached");
    reviewOrderBtn.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("Review Order button clicked");
      console.log("Selected products:", selectedProducts);
      if (validateForm()) {
        console.log("Form is valid, showing modal");
        showConfirmationModal();
      } else {
        console.log("Form validation failed");
      }
    });
  } else {
    console.error("Review Order button not found!");
  }

  // Confirmation modal event listeners
  setupConfirmationModal();
}

function updateQuantity(productId, change) {
  const input = document.getElementById("quantity_" + productId);
  if (!input) return;

  let newValue = parseInt(input.value) + change;
  if (newValue < 10) newValue = 10; // Minimum is 10

  input.value = newValue;
  updateOrderSummary();
}

function updateOrderSummary() {
  const summaryContainer = document.getElementById("orderSummary");
  if (!summaryContainer) return;

  selectedProducts = [];

  // Get all checked product checkboxes
  const checkboxes = document.querySelectorAll(".product-checkbox:checked");

  checkboxes.forEach((checkbox) => {
    const productId = checkbox.value;
    const productName = checkbox.getAttribute("data-name");
    const quantityInput = document.getElementById("quantity_" + productId);

    if (quantityInput) {
      const qty = parseInt(quantityInput.value) || 10;

      selectedProducts.push({
        id: productId,
        name: productName,
        quantity: qty,
      });
    }
  });

  // Update the summary display
  if (selectedProducts.length === 0) {
    summaryContainer.innerHTML = `
      <div class="summary-empty">
        <h4>No Products Selected</h4>
        <p>Choose products from the selection above to see your order summary</p>
      </div>
    `;
  } else {
    let tableHTML = `
      <table class="summary-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
          </tr>
        </thead>
        <tbody>
    `;

    let totalItems = 0;

    selectedProducts.forEach((product) => {
      tableHTML += `
        <tr>
          <td>${product.name}</td>
          <td>${product.quantity}</td>
        </tr>
      `;
      totalItems += product.quantity;
    });

    tableHTML += `
        </tbody>
        <tfoot>
          <tr>
            <td><strong>Total Items:</strong></td>
            <td><strong>${totalItems}</strong></td>
          </tr>
        </tfoot>
      </table>
      <p class="pricing-note">Pricing will be provided after we review your quotation request.</p>
    `;

    summaryContainer.innerHTML = tableHTML;
  }

  // Update button state
  updateSubmitButton();
}

function validateForm() {
  console.log("Validating form...");

  // Check customer information
  const name = document.getElementById("name").value.trim();
  const contact = document.getElementById("contact").value.trim();
  const email = document.getElementById("email").value.trim();
  const billingAddress = document
    .getElementById("billing_address")
    .value.trim();
  const orderType = document.getElementById("order_type").value;
  const purpose = document.getElementById("purpose").value.trim();
  const dateNeeded = document.getElementById("date_needed").value;
  const timeNeeded = document.getElementById("time_needed").value;

  console.log("Form fields:", {
    name,
    contact,
    email,
    billingAddress,
    orderType,
    purpose,
    dateNeeded,
    timeNeeded,
  });

  if (
    !name ||
    !contact ||
    !email ||
    !billingAddress ||
    !orderType ||
    !purpose ||
    !dateNeeded ||
    !timeNeeded
  ) {
    alert("Please fill in all required fields.");
    return false;
  }

  // Check delivery address if delivery is selected
  if (orderType === "delivery") {
    const deliveryAddress = document
      .getElementById("delivery_address")
      .value.trim();
    if (!deliveryAddress) {
      alert("Please provide a delivery address.");
      return false;
    }
  }

  // Check if at least one product is selected with minimum quantity
  let hasValidProducts = false;
  let invalidProducts = [];

  selectedProducts.forEach((product) => {
    if (product.quantity >= 10) {
      hasValidProducts = true;
    } else if (product.quantity > 0 && product.quantity < 10) {
      invalidProducts.push(product.name);
    }
  });

  if (!hasValidProducts) {
    alert("Please select at least one product with a minimum quantity of 10.");
    return false;
  }

  if (invalidProducts.length > 0) {
    alert(
      "The following products have a quantity less than 10:\n\n" +
        invalidProducts.join("\n") +
        "\n\nPlease increase the quantity to at least 10 or set to 0."
    );
    return false;
  }

  return true;
}

function showConfirmationModal() {
  console.log("showConfirmationModal called");
  const modal = document.getElementById("confirmationModal");

  if (!modal) {
    console.error("Modal element not found!");
    alert("Error: Could not show confirmation modal. Please refresh the page.");
    return;
  }

  console.log("Populating modal with form data...");

  // Populate customer information
  try {
    document.getElementById("confirm-name").textContent =
      document.getElementById("name").value;
    document.getElementById("confirm-contact").textContent =
      document.getElementById("contact").value;
    document.getElementById("confirm-email").textContent =
      document.getElementById("email").value;
    document.getElementById("confirm-billing-address").textContent =
      document.getElementById("billing_address").value;
  } catch (error) {
    console.error("Error populating customer info:", error);
  }

  const deliverySection = document.getElementById("delivery-address-section");
  const orderType = document.getElementById("order_type").value;

  if (orderType === "delivery") {
    deliverySection.style.display = "block";
    document.getElementById("confirm-delivery-address").textContent =
      document.getElementById("delivery_address").value;
  } else {
    deliverySection.style.display = "none";
  }

  // Populate order details
  document.getElementById("confirm-order-type").textContent =
    orderType.charAt(0).toUpperCase() + orderType.slice(1);
  document.getElementById("confirm-purpose").textContent =
    document.getElementById("purpose").value;
  document.getElementById("confirm-date-needed").textContent = formatDate(
    document.getElementById("date_needed").value
  );
  document.getElementById("confirm-time-needed").textContent = formatTime(
    document.getElementById("time_needed").value
  );

  const note = document.getElementById("note").value.trim();
  if (note) {
    document.getElementById("confirm-note").textContent = note;
  } else {
    document.getElementById("confirm-note").textContent = "None";
  }

  // Populate items
  const itemsBody = document.getElementById("confirm-order-items");
  itemsBody.innerHTML = "";
  let totalItems = 0;

  selectedProducts.forEach((product) => {
    const row = itemsBody.insertRow();
    row.innerHTML = `
      <td>${product.name}</td>
      <td>-</td>
      <td>${product.quantity}</td>
      <td>-</td>
    `;
    totalItems += product.quantity;
  });

  console.log("Modal populated, showing it now...");
  modal.classList.add("show");
}

function setupConfirmationModal() {
  const modal = document.getElementById("confirmationModal");
  if (!modal) {
    console.error("Confirmation modal not found!");
    return;
  }

  const closeBtn = modal.querySelector(".close");
  const editBtn = document.getElementById("editOrderBtn");
  const confirmBtn = document.getElementById("confirmSubmitBtn");

  console.log("Modal elements:", { modal, closeBtn, editBtn, confirmBtn });

  if (closeBtn) {
    closeBtn.onclick = function () {
      modal.classList.remove("show");
    };
  }

  if (editBtn) {
    editBtn.onclick = function () {
      modal.classList.remove("show");
    };
  }

  if (confirmBtn) {
    confirmBtn.onclick = function () {
      submitFinalForm();
    };
  }

  window.onclick = function (event) {
    if (event.target === modal) {
      modal.classList.remove("show");
    }
  };
}

function submitFinalForm() {
  // Copy all form data to the hidden final submission form
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

  // Submit the hidden form
  document.getElementById("finalSubmissionForm").submit();
}

function clearForm() {
  document.getElementById("bulkOrderForm").reset();
  selectedProducts = [];
  updateOrderSummary();

  // Reset all quantity inputs
  document.querySelectorAll('[id^="qty-"]').forEach((input) => {
    input.value = 0;
  });

  // Clear storage
  localStorage.clear();
  sessionStorage.clear();
}

function updateSubmitButton() {
  const btn = document.getElementById("reviewOrderBtn");
  if (!btn) return;

  const hasProducts =
    selectedProducts.length > 0 &&
    selectedProducts.some((p) => p.quantity >= 10);
  btn.disabled = !hasProducts;

  console.log("Button state updated:", { hasProducts, selectedProducts });
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
}

// Prevent form restoration
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});
