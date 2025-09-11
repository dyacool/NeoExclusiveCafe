// Bulk Order Form JavaScript
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("bulkOrderForm");
  const submitBtn = document.getElementById("submitBtn");
  const discardBtn = document.getElementById("discardBtn");
  const orderSummary = document.getElementById("orderSummary");
  const selectedProductsInput = document.getElementById("selectedProducts");
  const totalAmountInput = document.getElementById("totalAmount");

  let selectedProducts = [];

  // Initialize form
  initializeForm();

  function initializeForm() {
    // Add event listeners to product checkboxes
    const productCheckboxes = document.querySelectorAll(".product-select");
    productCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", handleProductSelection);
    });

    // Add event listeners to quantity inputs
    const quantityInputs = document.querySelectorAll(".quantity-field");
    quantityInputs.forEach((input) => {
      input.addEventListener("input", handleQuantityChange);
    });

    // Add event listeners to form inputs for validation
    const requiredInputs = form.querySelectorAll(
      "input[required], textarea[required], select[required]"
    );
    requiredInputs.forEach((input) => {
      input.addEventListener("input", validateForm);
      input.addEventListener("blur", validateForm);
      input.addEventListener("change", validateForm);
    });

    // Order type change event
    const orderTypeSelect = document.getElementById("order_type");
    const deliveryAddressGroup = document.getElementById(
      "delivery_address_group"
    );
    const deliveryAddressField = document.getElementById("delivery_address");

    orderTypeSelect.addEventListener("change", function () {
      if (this.value === "delivery") {
        deliveryAddressGroup.style.display = "block";
        deliveryAddressGroup.classList.add("show");
        deliveryAddressField.required = true;
      } else {
        deliveryAddressGroup.style.display = "none";
        deliveryAddressGroup.classList.remove("show");
        deliveryAddressField.required = false;
        deliveryAddressField.value = "";
      }
      validateForm();
    });

    // Discard button event
    discardBtn.addEventListener("click", discardForm);

    // Form submission
    form.addEventListener("submit", handleFormSubmission);

    // Initialize order summary
    updateOrderSummary();
    validateForm();
  }

  function handleProductSelection(event) {
    const checkbox = event.target;
    const productItem = checkbox.closest(".product-item");
    const quantityInput = productItem.querySelector(".quantity-input");
    const quantityField = productItem.querySelector(".quantity-field");

    if (checkbox.checked) {
      // Show quantity input
      quantityInput.style.display = "block";
      productItem.classList.add("selected");

      // Add to selected products
      addProductToSelection(checkbox);
    } else {
      // Hide quantity input
      quantityInput.style.display = "none";
      productItem.classList.remove("selected");

      // Remove from selected products
      removeProductFromSelection(checkbox.value);
    }

    updateOrderSummary();
    validateForm();
  }

  function handleQuantityChange(event) {
    const quantityField = event.target;
    const productId = quantityField.id.replace("quantity_", "");
    const quantity = parseInt(quantityField.value) || 12;

    // Ensure minimum quantity is 12
    if (quantity < 12) {
      quantityField.value = 12;
      return;
    }

    // Update quantity in selected products
    updateProductQuantity(productId, quantity);
    updateOrderSummary();
  }

  function addProductToSelection(checkbox) {
    const productId = checkbox.value;
    const productName = checkbox.dataset.name;
    const productPrice = parseFloat(checkbox.dataset.price);
    const quantityField = document.getElementById(`quantity_${productId}`);
    const quantity = parseInt(quantityField.value) || 12;

    const product = {
      id: productId,
      name: productName,
      price: productPrice,
      quantity: quantity,
    };

    selectedProducts.push(product);
  }

  function removeProductFromSelection(productId) {
    selectedProducts = selectedProducts.filter(
      (product) => product.id !== productId
    );
  }

  function updateProductQuantity(productId, quantity) {
    const product = selectedProducts.find((p) => p.id === productId);
    if (product) {
      product.quantity = quantity;
    }
  }

  function updateOrderSummary() {
    if (selectedProducts.length === 0) {
      orderSummary.innerHTML = "<p>No products selected</p>";
      totalAmountInput.value = "0";
      return;
    }

    let summaryHTML = "";
    let grandTotal = 0;

    selectedProducts.forEach((product) => {
      const subtotal = product.price * product.quantity;
      grandTotal += subtotal;

      summaryHTML += `
                <div class="summary-item">
                    <div class="item-details">
                        <div class="item-name">${escapeHtml(product.name)}</div>
                        <div class="item-calculation">₱${formatPrice(
                          product.price
                        )} × ${product.quantity}</div>
                    </div>
                    <div class="item-total">₱${formatPrice(subtotal)}</div>
                </div>
            `;
    });

    summaryHTML += `
            <div class="summary-item total">
                <div class="item-details">
                    <div class="item-name">Total Amount</div>
                </div>
                <div class="item-total">₱${formatPrice(grandTotal)}</div>
            </div>
        `;

    orderSummary.innerHTML = summaryHTML;
    totalAmountInput.value = grandTotal.toFixed(2);

    // Update hidden field with selected products
    selectedProductsInput.value = JSON.stringify(selectedProducts);
  }

  function validateForm() {
    const requiredFields = form.querySelectorAll(
      "input[required], textarea[required], select[required]"
    );
    let isValid = true;

    // Check required fields
    requiredFields.forEach((field) => {
      if (!field.value.trim()) {
        isValid = false;
        return;
      }
    });

    // Special validation for delivery address based on order type
    const orderType = document.getElementById("order_type").value;
    const deliveryAddress = document.getElementById("delivery_address");

    if (orderType === "delivery" && !deliveryAddress.value.trim()) {
      isValid = false;
    }

    // Check if at least one product is selected
    if (selectedProducts.length === 0) {
      isValid = false;
    }

    // Check date validation
    const dateField = document.getElementById("date_needed");
    if (dateField.value) {
      const selectedDate = new Date(dateField.value);
      const minDate = new Date();
      minDate.setDate(minDate.getDate() + 14);

      if (selectedDate < minDate) {
        isValid = false;
      }
    }

    // Enable/disable submit button
    submitBtn.disabled = !isValid;

    return isValid;
  }

  function discardForm() {
    if (
      confirm(
        "Are you sure you want to discard all changes? This action cannot be undone."
      )
    ) {
      // Reset form
      form.reset();

      // Clear selected products
      selectedProducts = [];

      // Hide delivery address field
      const deliveryAddressGroup = document.getElementById(
        "delivery_address_group"
      );
      const deliveryAddressField = document.getElementById("delivery_address");
      deliveryAddressGroup.style.display = "none";
      deliveryAddressGroup.classList.remove("show");
      deliveryAddressField.required = false;

      // Hide all quantity inputs and remove selected class
      document.querySelectorAll(".product-item").forEach((item) => {
        item.classList.remove("selected");
        const quantityInput = item.querySelector(".quantity-input");
        quantityInput.style.display = "none";
      });

      // Reset quantity values to minimum (12)
      document.querySelectorAll(".quantity-field").forEach((field) => {
        field.value = 12;
      });

      // Update order summary
      updateOrderSummary();
      validateForm();

      // Scroll to top
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  }

  function handleFormSubmission(event) {
    if (!validateForm()) {
      event.preventDefault();
      alert(
        "Please fill in all required fields and select at least one product."
      );
      return false;
    }

    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";
    form.classList.add("loading");

    // The form will submit normally
    return true;
  }

  function formatPrice(price) {
    return price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
  }

  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  // Utility function to copy billing address to delivery address
  function copyBillingToDelivery() {
    const billingAddress = document.getElementById("billing_address").value;
    const deliveryAddress = document.getElementById("delivery_address");

    if (confirm("Copy billing address to delivery address?")) {
      deliveryAddress.value = billingAddress;
      validateForm();
    }
  }

  // Add copy button functionality (optional)
  function addCopyAddressButton() {
    const deliveryGroup = document
      .getElementById("delivery_address")
      .closest(".form-group");
    const copyButton = document.createElement("button");
    copyButton.type = "button";
    copyButton.textContent = "Same as billing address";
    copyButton.className = "btn btn-secondary";
    copyButton.style.marginTop = "10px";
    copyButton.style.padding = "8px 16px";
    copyButton.style.fontSize = "14px";
    copyButton.addEventListener("click", copyBillingToDelivery);

    deliveryGroup.appendChild(copyButton);
  }

  // Initialize copy address button
  addCopyAddressButton();

  // Real-time validation feedback
  function addValidationFeedback() {
    const inputs = form.querySelectorAll("input[required], textarea[required]");

    inputs.forEach((input) => {
      input.addEventListener("blur", function () {
        if (this.value.trim() === "") {
          this.style.borderColor = "#dc3545";
        } else {
          this.style.borderColor = "#28a745";
        }
      });

      input.addEventListener("input", function () {
        if (this.value.trim() !== "") {
          this.style.borderColor = "#28a745";
        }
      });
    });
  }

  // Initialize validation feedback
  addValidationFeedback();

  // Phone number formatting
  function formatPhoneNumber() {
    const phoneInput = document.getElementById("contact");
    phoneInput.addEventListener("input", function () {
      // Remove all non-digit characters
      let value = this.value.replace(/\D/g, "");

      // Limit to 11 digits for Philippine numbers
      if (value.length > 11) {
        value = value.slice(0, 11);
      }

      // Format the number
      if (value.length >= 7) {
        value = value.replace(/(\d{4})(\d{3})(\d{4})/, "$1-$2-$3");
      } else if (value.length >= 4) {
        value = value.replace(/(\d{4})(\d{0,3})/, "$1-$2");
      }

      this.value = value;
    });
  }

  // Initialize phone formatting
  formatPhoneNumber();

  // Clear form data on page load to prevent restoration
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      // Page was restored from back/forward cache
      form.reset();
    }
  });

  // Prevent form resubmission on refresh
  window.onload = function () {
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
  };
});
