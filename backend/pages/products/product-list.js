// Global variable to store all products data
let allProductsData = [];

document.addEventListener("DOMContentLoaded", () => {
  // Load all products data from the hidden container
  const allProductsContainer = document.getElementById("allProductsData");
  if (allProductsContainer) {
    try {
      allProductsData = JSON.parse(allProductsContainer.textContent);
    } catch (e) {
      console.error("Error parsing all products data:", e);
      allProductsData = [];
    }
  }

  // Initialize filter counts
  updateFilterCounts();

  // Set up search functionality
  setupSearch();

  // Set up modal functionality
  setupModal();
});

// Filter functionality
function filterProducts(status, button) {
  // Update active button
  document
    .querySelectorAll(".filter-btn")
    .forEach((btn) => btn.classList.remove("active"));
  button.classList.add("active");

  // Filter products from all products data
  let filteredProducts = [];
  
  if (status === "all") {
    filteredProducts = allProductsData;
  } else if (status === "featured") {
    filteredProducts = allProductsData.filter(product => product.is_featured == 1);
  } else {
    // For status-based filters (Pick Up, Delivery, Unavailable)
    filteredProducts = allProductsData.filter(product => product.status_name === status);
  }

  // Clear current table
  const tbody = document.getElementById("productTableBody");
  tbody.innerHTML = "";

  // Add filtered products to table
  if (filteredProducts.length > 0) {
    filteredProducts.forEach((product) => {
      const row = createProductRow(product);
      tbody.appendChild(row);
    });
  } else {
    // Show empty state
    const emptyRow = document.createElement("tr");
    emptyRow.className = "no-results";
    emptyRow.innerHTML = `
      <td colspan="7">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <h3>No products found</h3>
          <p>Try adjusting your filter criteria.</p>
        </div>
      </td>
    `;
    tbody.appendChild(emptyRow);
  }

  // Handle pagination visibility - show only for "all" filter
  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = status === "all" ? "flex" : "none";
  }
}

// Function to format available days in compact format
function formatAvailableDays(availableDays) {
  if (!availableDays) return "Not set";
  
  const dayMap = {
    'Sunday': 'S',
    'Monday': 'M', 
    'Tuesday': 'T',
    'Wednesday': 'W',
    'Thursday': 'Th',
    'Friday': 'F',
    'Saturday': 'Sa'
  };
  
  const days = availableDays.split(', ');
  const formattedDays = days.map(day => dayMap[day] || day);
  
  return formattedDays.join(', ');
}

// Function to create a product row from product data
function createProductRow(product) {
  const quantity = parseInt(product.quantity) || 0;
  const quantityClass = quantity <= 5 ? 'low-stock' : (quantity <= 10 ? 'medium-stock' : 'good-stock');
  const statusClass = product.status_name.toLowerCase().replace(' ', '-');
  
  // Construct image path
  let imagePath = '';
  if (product.image_url) {
    imagePath = '/assets/' + product.image_url;
  }
  
  const row = document.createElement('tr');
  row.setAttribute('data-status', product.status_name);
  row.setAttribute('data-name', product.name.toLowerCase());
  row.setAttribute('data-sku', product.sku.toLowerCase());
  
  row.innerHTML = `
    <td>
      <div class='product-image-container'>
        <img class='product-image' src='${imagePath}' alt='${product.name}' loading='lazy'>
        ${product.is_featured == 1 ? "<span class='featured-badge'>★</span>" : ""}
      </div>
    </td>
    <td>
      <span class='sku-text'>${product.sku}</span>
    </td>
    <td>
      <div class='product-info'>
        <span class='product-name'>${product.name}</span>
      </div>
    </td>
    <td>
      <span class='price-text'>₱${parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
    </td>
    <td>
      <div class='status-container'>
        <span class='status-badge status-${statusClass}'>${product.status_name}</span>
        <span class='stock-badge ${quantityClass}'>
          <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
            <path d='M20 7h-9'></path>
            <path d='M14 17H5'></path>
            <circle cx='17' cy='17' r='3'></circle>
            <circle cx='7' cy='7' r='3'></circle>
          </svg>
          ${quantity} in stock
        </span>
      </div>
    </td>
    <td>
      <span class='available-days-text'>${formatAvailableDays(product.available_days)}</span>
    </td>
    <td>
      <div class='action-buttons'>
        <button class='btn-action btn-edit' onclick="openEditModal(
          '${product.id}',
          '${product.name.replace(/'/g, "\\'")}',
          '${product.price}',
          '${product.status_id}',
          ${product.is_featured == 1 ? "true" : "false"},
          ${product.show_when_unavailable == 1 ? "true" : "false"},
          ${product.hide_when_unavailable == 1 ? "true" : "false"},
          ${quantity},
          '${(product.available_days || "").replace(/'/g, "\\'")}',
          '${product.status_name.replace(/'/g, "\\'")}'
        )" title='Edit Product'>
          <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
            <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
            <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
          </svg>
        </button>
        <button class='btn-action btn-delete' onclick='softDeleteProduct(${product.id})' title='Delete Product'>
          <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
            <polyline points='3,6 5,6 21,6'></polyline>
            <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
          </svg>
        </button>
      </div>
    </td>
  `;
  
  return row;
}

// Search functionality
function setupSearch() {
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", searchProducts);
  }
}

function searchProducts() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  
  if (!searchTerm) {
    // If no search term, restore original paginated view
    location.reload();
    return;
  }
  
  // Filter products from all products data
  const filteredProducts = allProductsData.filter(product => 
    product.name.toLowerCase().includes(searchTerm) || 
    product.sku.toLowerCase().includes(searchTerm)
  );

  // Clear current table
  const tbody = document.getElementById("productTableBody");
  tbody.innerHTML = "";

  // Add filtered products to table
  if (filteredProducts.length > 0) {
    filteredProducts.forEach((product) => {
      const row = createProductRow(product);
      tbody.appendChild(row);
    });
  } else {
    // Show empty state
    const emptyRow = document.createElement("tr");
    emptyRow.className = "no-results";
    emptyRow.innerHTML = `
      <td colspan="7">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <h3>No products found</h3>
          <p>Try adjusting your search criteria.</p>
        </div>
      </td>
    `;
    tbody.appendChild(emptyRow);
  }

  // Hide pagination when searching
  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = "none";
  }
}

// Sort functionality - updated for toggle buttons
const sortStates = {
  1: null, // SKU
  2: null, // Name
  3: null, // Price
  az: null, // A-Z
};

function toggleSort(columnIndex) {
  // Reset all other sort states
  Object.keys(sortStates).forEach((key) => {
    if (key != columnIndex) {
      sortStates[key] = null;
    }
  });

  // Get current state for this column
  const currentState = sortStates[columnIndex];

  // Reset all buttons
  document.querySelectorAll(".sort-btn").forEach((btn) => {
    btn.classList.remove("active", "asc", "desc");
  });

  // Toggle state: null -> asc -> desc -> asc -> desc...
  let newState;
  if (currentState === null || currentState === "desc") {
    newState = "asc";
  } else {
    newState = "desc";
  }

  // Update state
  sortStates[columnIndex] = newState;

  // Update button appearance
  const buttonIds = { 1: "sort-sku", 2: "sort-name", 3: "sort-price" };
  const button = document.getElementById(buttonIds[columnIndex]);
  button.classList.add("active", newState);

  // Perform sort
  sortTable(columnIndex, newState);
}

function toggleAlphabeticalSort() {
  // Reset all other sort states
  Object.keys(sortStates).forEach((key) => {
    if (key !== "az") {
      sortStates[key] = null;
    }
  });

  // Get current state
  const currentState = sortStates["az"];

  // Reset all buttons
  document.querySelectorAll(".sort-btn").forEach((btn) => {
    btn.classList.remove("active", "asc", "desc");
  });

  // Toggle state: null -> asc -> desc -> asc -> desc...
  let newState;
  if (currentState === null || currentState === "desc") {
    newState = "asc";
  } else {
    newState = "desc";
  }

  // Update state
  sortStates["az"] = newState;

  // Update button appearance
  const button = document.getElementById("sort-az");
  button.classList.add("active", newState);

  // Perform alphabetical sort (on name column - index 2)
  sortTable(2, newState);
}

function sortTable(columnIndex, direction) {
  const tbody = document.querySelector("#productTableBody");
  const rows = Array.from(tbody.querySelectorAll("tr:not(.no-results)"));

  // Sort all rows (both visible and hidden)
  rows.sort((rowA, rowB) => {
    let cellA = rowA.cells[columnIndex].textContent.trim();
    let cellB = rowB.cells[columnIndex].textContent.trim();

    // Handle price column (remove currency symbol and convert to number)
    if (columnIndex === 3) {
      cellA = Number.parseFloat(cellA.replace(/[^\d.]/g, "")) || 0;
      cellB = Number.parseFloat(cellB.replace(/[^\d.]/g, "")) || 0;
    }

    let comparison = 0;
    if (typeof cellA === "number" && typeof cellB === "number") {
      comparison = cellA - cellB;
    } else {
      comparison = cellA.localeCompare(cellB, undefined, { numeric: true });
    }

    return direction === "asc" ? comparison : -comparison;
  });

  // Clear tbody and re-append all rows in sorted order
  tbody.innerHTML = "";
  rows.forEach((row) => tbody.appendChild(row));
}

// Function to restore original paginated view
function restoreOriginalView() {
  location.reload();
}

// Modal functionality
function setupModal() {
  const modal = document.getElementById("editModal");
  const overlay = document.querySelector(".modal-overlay");

  if (overlay) {
    overlay.addEventListener("click", closeModal);
  }

  // Form submission
  const form = document.getElementById("editProductForm");
  if (form) {
    form.addEventListener("submit", handleFormSubmit);
  }
}

function openEditModal(
  id,
  name,
  price,
  status,
  isFeature,
  showWhenUnavailable,
  hideWhenUnavailable,
  quantity,
  availableDays,
  statusName
) {
  // Debug: Log the status values
  console.log("Status ID passed:", status);
  console.log("Status Name passed:", statusName);
  
  // Store original form data for cancel functionality
  originalFormData = {
    id: id,
    name: name,
    price: price,
    status: status,
    isFeature: isFeature,
    showWhenUnavailable: showWhenUnavailable,
    hideWhenUnavailable: hideWhenUnavailable,
    quantity: quantity,
    availableDays: availableDays,
    statusName: statusName
  };
  
  // Populate form fields
  document.getElementById("editProductId").value = id;
  document.getElementById("editProductName").value = name;
  document.getElementById("editProductPrice").value = price;
  document.getElementById("editProductQuantity").value = quantity;
  document.getElementById("editProductStatus").value = status;
  document.getElementById("editIsFeature").value = isFeature ? "1" : "0";

  // Set visibility option
  const visibilitySelect = document.getElementById("editVisibilityOption");
  if (showWhenUnavailable) {
    visibilitySelect.value = "show";
  } else if (hideWhenUnavailable) {
    visibilitySelect.value = "hide";
  } else {
    visibilitySelect.value = "default";
  }

  // Set available days checkboxes
  const availableDaysArray = availableDays ? availableDays.split(', ') : [];
  const dayCheckboxes = {
    'edit_sunday': 'Sunday',
    'edit_monday': 'Monday',
    'edit_tuesday': 'Tuesday',
    'edit_wednesday': 'Wednesday',
    'edit_thursday': 'Thursday',
    'edit_friday': 'Friday',
    'edit_saturday': 'Saturday'
  };

  // Uncheck all checkboxes first
  Object.keys(dayCheckboxes).forEach(checkboxId => {
    document.getElementById(checkboxId).checked = false;
  });

  // Check the appropriate checkboxes
  availableDaysArray.forEach(day => {
    const checkboxId = Object.keys(dayCheckboxes).find(key => dayCheckboxes[key] === day);
    if (checkboxId) {
      document.getElementById(checkboxId).checked = true;
    }
  });

  // Show/hide available days checkboxes based on product status
  const availableDaysContainer = document.querySelector('.checkbox-group.days-group');
  if (availableDaysContainer) {
    if (statusName === 'Delivery') {
      availableDaysContainer.style.display = 'block';
    } else {
      availableDaysContainer.style.display = 'none';
    }
  }

  // Add event listener to status dropdown to dynamically show/hide available days
  const statusSelect = document.getElementById("editProductStatus");
  if (statusSelect) {
    // Remove existing event listeners by using removeEventListener (if we had a reference)
    // For simplicity, we'll just add the listener and let it work
    statusSelect.addEventListener('change', function() {
      const selectedStatus = this.options[this.selectedIndex].text;
      if (availableDaysContainer) {
        if (selectedStatus === 'Delivery') {
          availableDaysContainer.style.display = 'block';
        } else {
          availableDaysContainer.style.display = 'none';
        }
      }
    });
  }

  // Load product images
  loadProductImages(id);

  // Show modal
  document.getElementById("editModal").style.display = "flex";
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "auto";
  
  // Reset available days visibility when modal is closed
  const availableDaysContainer = document.querySelector('.checkbox-group.days-group');
  if (availableDaysContainer) {
    availableDaysContainer.style.display = 'block'; // Reset to default visibility
  }
  
  // Reset form fields to original values (cancel any unsaved changes)
  resetFormToOriginal();
}

function handleFormSubmit(event) {
  event.preventDefault();

  // Get the selected status to determine if we should include available days
  const statusSelect = document.getElementById("editProductStatus");
  const selectedStatus = statusSelect.options[statusSelect.selectedIndex].text;
  
  // Collect available days from checkboxes only if status is Delivery
  const availableDays = [];
  if (selectedStatus === 'Delivery') {
    const dayCheckboxes = {
      'edit_sunday': 'Sunday',
      'edit_monday': 'Monday',
      'edit_tuesday': 'Tuesday',
      'edit_wednesday': 'Wednesday',
      'edit_thursday': 'Thursday',
      'edit_friday': 'Friday',
      'edit_saturday': 'Saturday'
    };

    Object.keys(dayCheckboxes).forEach(checkboxId => {
      const checkbox = document.getElementById(checkboxId);
      if (checkbox && checkbox.checked) {
        availableDays.push(dayCheckboxes[checkboxId]);
      }
    });
  }

  const formData = {
    id: document.getElementById("editProductId").value,
    name: document.getElementById("editProductName").value,
    price: document.getElementById("editProductPrice").value,
    quantity: document.getElementById("editProductQuantity").value,
    status_id: document.getElementById("editProductStatus").value,
    is_featured: document.getElementById("editIsFeature").value === "1",
    show_when_unavailable:
      document.getElementById("editVisibilityOption").value === "show",
    hide_when_unavailable:
      document.getElementById("editVisibilityOption").value === "hide",
    available_days: availableDays,
    pending_image_changes: pendingImageChanges
  };

  // Show loading state
  const submitBtn = event.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "Saving...";
  submitBtn.disabled = true;

  // First, handle image operations (move temp to permanent or remove existing)
  const uploadPromises = [];
  
  // Handle primary image operations
  if (pendingImageChanges.primary === 'remove') {
    // Permanently delete removed primary image
    const deleteRemovedFormData = new FormData();
    deleteRemovedFormData.append('product_id', formData.id);
    
    uploadPromises.push(
      fetch('delete-removed-images.php', {
        method: 'POST',
        body: deleteRemovedFormData
      }).then(response => response.json())
    );
  } else if (tempImageInfo.primary) {
    // Move new primary image to permanent location
    const primaryFormData = new FormData();
    primaryFormData.append('temp_filename', tempImageInfo.primary.filename);
    primaryFormData.append('product_id', formData.id);
    primaryFormData.append('image_type', 'primary');
    primaryFormData.append('action', 'move');
    
    uploadPromises.push(
      fetch('move-temp-to-permanent.php', {
        method: 'POST',
        body: primaryFormData
      }).then(response => response.json())
    );
  }
  
  // Handle additional image operations
  if (pendingImageChanges.additional.toRemove.length > 0) {
    // Permanently delete removed additional images
    const deleteRemovedFormData = new FormData();
    deleteRemovedFormData.append('product_id', formData.id);
    
    uploadPromises.push(
      fetch('delete-removed-images.php', {
        method: 'POST',
        body: deleteRemovedFormData
      }).then(response => response.json())
    );
  }
  
  // Move additional images if pending
  tempImageInfo.additional.forEach(tempInfo => {
    const additionalFormData = new FormData();
    additionalFormData.append('temp_filename', tempInfo.filename);
    additionalFormData.append('product_id', formData.id);
    additionalFormData.append('image_type', 'additional');
    additionalFormData.append('action', 'move');
    
    uploadPromises.push(
      fetch('move-temp-to-permanent.php', {
        method: 'POST',
        body: additionalFormData
      }).then(response => response.json())
    );
  });


  
  // Wait for all image uploads to complete, then submit the form
  Promise.all(uploadPromises)
    .then(uploadResults => {
      // Check if any uploads failed
      const failedUploads = uploadResults.filter(result => !result.success);
      if (failedUploads.length > 0) {
        throw new Error('Some image uploads failed: ' + failedUploads.map(r => r.error).join(', '));
      }
      
      // Debug: Log current location
      console.log("Current location:", window.location.href);
      console.log("Current pathname:", window.location.pathname);
      
      // Send update request
      return fetch("update-product.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text().then(text => {
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("Response text:", text);
          throw new Error("Invalid JSON response from server");
        }
      });
    })
    .then((data) => {
      if (data.success) {
        showNotification("Product updated successfully!", "success");
        closeModal();
        setTimeout(() => location.reload(), 1000);
      } else {
        showNotification("Error: " + data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "An error occurred while updating the product: " + error.message,
        "error"
      );
    })
    .finally(() => {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
}

// Delete functionality
function softDeleteProduct(id) {
  if (
    !confirm(
      "Are you sure you want to delete this product? This action will move it to the archive."
    )
  ) {
    return;
  }

  fetch("/backend/pages/products/delete-product.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((response) => response.text())
    .then((data) => {
      showNotification("Product moved to archive!", "success");
      setTimeout(() => location.reload(), 1000);
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "An error occurred while deleting the product.",
        "error"
      );
    });
}

// Utility functions
function updateFilterCounts() {
  const counts = {
    all: allProductsData.length,
    "Pick Up": 0,
    "Delivery": 0,
    featured: 0,
    "Unavailable": 0,
  };

  // Count from all products data
  allProductsData.forEach((product) => {
    const status = product.status_name;
    
    // Count by status
    if (counts.hasOwnProperty(status)) {
      counts[status]++;
    }
    
    // Count featured products
    if (product.is_featured == 1) {
      counts.featured++;
    }
  });

  // Update count displays
  document.getElementById("count-all").textContent = counts.all;
  document.getElementById("count-pickup").textContent = counts["Pick Up"];
  document.getElementById("count-delivery").textContent = counts["Delivery"];
  document.getElementById("count-featured").textContent = counts.featured;
  document.getElementById("count-unavailable").textContent = counts["Unavailable"];
}



function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 500;
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        ${
          type === "success"
            ? "background-color: var(--green-600);"
            : "background-color: var(--red-600);"
        }
    `;
  notification.textContent = message;

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 100);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    setTimeout(() => {
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
}

// Custom confirmation dialog
function showCustomConfirm(title, message, onConfirm) {
  // Remove any existing confirmation dialogs
  const existingDialog = document.getElementById('customConfirmDialog');
  if (existingDialog) {
    document.body.removeChild(existingDialog);
  }

  // Create overlay
  const overlay = document.createElement('div');
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
  `;

  // Create dialog
  const dialog = document.createElement('div');
  dialog.id = 'customConfirmDialog';
  dialog.style.cssText = `
    background: white;
    border-radius: 8px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    animation: slideIn 0.3s ease;
  `;

  // Add CSS animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  `;
  document.head.appendChild(style);

  dialog.innerHTML = `
    <h3 style="margin: 0 0 16px 0; color: #333; font-size: 18px;">${title}</h3>
    <p style="margin: 0 0 24px 0; color: #666; line-height: 1.5;">${message}</p>
    <div style="display: flex; gap: 12px; justify-content: flex-end;">
      <button id="cancelBtn" style="
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: white;
        color: #666;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
      ">Cancel</button>
      <button id="confirmBtn" style="
        padding: 8px 16px;
        border: none;
        background: #dc3545;
        color: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
      ">Remove</button>
    </div>
  `;

  overlay.appendChild(dialog);
  document.body.appendChild(overlay);

  // Add event listeners
  const cancelBtn = document.getElementById('cancelBtn');
  const confirmBtn = document.getElementById('confirmBtn');

  const closeDialog = () => {
    document.body.removeChild(overlay);
    document.head.removeChild(style);
  };

  cancelBtn.addEventListener('click', closeDialog);
  confirmBtn.addEventListener('click', () => {
    closeDialog();
    onConfirm();
  });

  // Close on overlay click
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      closeDialog();
    }
  });

  // Close on Escape key
  const handleEscape = (e) => {
    if (e.key === 'Escape') {
      closeDialog();
      document.removeEventListener('keydown', handleEscape);
    }
  };
  document.addEventListener('keydown', handleEscape);
}

// Image Management Functions
let currentProductImages = {
  primary: null,
  additional: []
};

// Store original form data for cancel functionality
let originalFormData = {};

// Store pending image changes (not saved until form is submitted)
let pendingImageChanges = {
  primary: null, // null = no change, object = new image, 'remove' = remove image
  additional: {
    toAdd: [],
    toRemove: []
  }
};

// Store temporary image files for upload
let tempImageFiles = {
  primary: null,
  additional: []
};

// Store temporary image info (filenames and paths)
let tempImageInfo = {
  primary: null,
  additional: []
};

function loadProductImages(productId) {
  // Reset current images
  currentProductImages = {
    primary: null,
    additional: []
  };
  
  // Reset pending changes
  pendingImageChanges = {
    primary: null,
    additional: {
      toAdd: [],
      toRemove: []
    }
  };
  
  // Reset temporary image info
  tempImageInfo = {
    primary: null,
    additional: []
  };
  
  // Reset temporary image info
  tempImageInfo = {
    primary: null,
    additional: []
  };

  // Fetch product images from server
  fetch(`test-get-images.php?product_id=${productId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        currentProductImages = data.images;
        displayProductImages();
      } else {
        console.error('Failed to load images:', data.error);
        showNotification('Failed to load product images', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading images:', error);
      showNotification('Error loading product images', 'error');
    });
}

function displayProductImages() {
  const primaryContainer = document.getElementById('editPrimaryImageContainer');
  const additionalContainer = document.getElementById('editAdditionalImagesContainer');
  const primaryPlaceholder = document.getElementById('editPrimaryPlaceholder');
  const additionalPlaceholder = document.getElementById('editAdditionalPlaceholder');
  const removePrimaryBtn = document.getElementById('editRemovePrimaryBtn');

  // Check if containers exist
  if (!primaryContainer || !additionalContainer) {
    console.error('Image containers not found');
    return;
  }

  // Calculate effective images (current + pending changes)
  let effectivePrimary = currentProductImages.primary;
  let effectiveAdditional = [...(currentProductImages.additional || [])];
  
  // Apply pending changes
  if (pendingImageChanges.primary === 'remove') {
    effectivePrimary = null;
  } else if (pendingImageChanges.primary) {
    effectivePrimary = pendingImageChanges.primary;
  }
  
  // Remove images marked for deletion
  effectiveAdditional = effectiveAdditional.filter(img => 
    !pendingImageChanges.additional.toRemove.includes(img.id)
  );
  
  // Add pending images
  effectiveAdditional = [...effectiveAdditional, ...pendingImageChanges.additional.toAdd];

  // Display primary image
  if (effectivePrimary) {
    // Construct proper image URL
    let imageUrl;
    if (effectivePrimary.is_temp) {
      // For temporary images, use the temp_path directly
      imageUrl = `/${effectivePrimary.image_url}`;
    } else {
      // For permanent images, ensure proper path
      imageUrl = effectivePrimary.image_url.startsWith('assets/') 
        ? `/${effectivePrimary.image_url}` 
        : `/assets/${effectivePrimary.image_url}`;
    }
    
    primaryContainer.innerHTML = `
      <div class="image-preview">
        <img src="${imageUrl}" alt="Primary image">
        <button class="remove-image" onclick="removePrimaryImage()">×</button>
      </div>
    `;
    primaryContainer.classList.add('has-image');
    if (removePrimaryBtn) {
      removePrimaryBtn.style.display = 'block';
    }
  } else {
    // Create placeholder HTML if the original placeholder is not available
    const placeholderHTML = primaryPlaceholder ? primaryPlaceholder.outerHTML : `
      <div class="image-placeholder" id="editPrimaryPlaceholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21,15 16,10 5,21"></polyline>
        </svg>
        <span>No primary image</span>
      </div>
    `;
    primaryContainer.innerHTML = placeholderHTML;
    primaryContainer.classList.remove('has-image');
    if (removePrimaryBtn) {
      removePrimaryBtn.style.display = 'none';
    }
  }

  // Display additional images
  if (effectiveAdditional && effectiveAdditional.length > 0) {
    const imagesGrid = document.createElement('div');
    imagesGrid.className = 'additional-images-grid';
    
    effectiveAdditional.forEach((image, index) => {
      // Construct proper image URL
      let imageUrl;
      if (image.is_temp) {
        // For temporary images, use the temp_path directly
        imageUrl = `/${image.image_url}`;
      } else {
        // For permanent images, ensure proper path
        imageUrl = image.image_url.startsWith('assets/') 
          ? `/${image.image_url}` 
          : `/assets/${image.image_url}`;
      }
      
      const imagePreview = document.createElement('div');
      imagePreview.className = 'image-preview';
      
      // Handle temporary vs permanent image removal
      let removeButton;
      if (image.is_temp) {
        // For temporary images, use a data attribute and event listener
        imagePreview.innerHTML = `
          <img src="${imageUrl}" alt="Additional image ${index + 1}">
          <button class="remove-image" data-temp-id="${image.id}">×</button>
        `;
        removeButton = imagePreview.querySelector('.remove-image');
        removeButton.addEventListener('click', () => removeAdditionalImage(image.id));
      } else {
        // For permanent images, use onclick attribute
        imagePreview.innerHTML = `
          <img src="${imageUrl}" alt="Additional image ${index + 1}">
          <button class="remove-image" onclick="removeAdditionalImage(${image.id})">×</button>
        `;
      }
      
      imagesGrid.appendChild(imagePreview);
    });
    
    additionalContainer.innerHTML = '';
    additionalContainer.appendChild(imagesGrid);
    additionalContainer.classList.add('has-images');
  } else {
    // Create placeholder HTML if the original placeholder is not available
    const placeholderHTML = additionalPlaceholder ? additionalPlaceholder.outerHTML : `
      <div class="image-placeholder" id="editAdditionalPlaceholder">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21,15 16,10 5,21"></polyline>
        </svg>
        <span>No additional images</span>
      </div>
    `;
    additionalContainer.innerHTML = placeholderHTML;
    additionalContainer.classList.remove('has-images');
  }
  
  // Update button states after displaying images
  updateAdditionalImagesButtonState();
}

function removePrimaryImage() {
  if (currentProductImages.primary) {
    showCustomConfirm(
      'Remove Primary Image',
      'Are you sure you want to remove the primary image?',
      () => {
        // For permanent primary images, move to temporary storage
        const productId = document.getElementById("editProductId").value;
        const formData = new FormData();
        formData.append('image_id', currentProductImages.primary.id);
        formData.append('product_id', productId);
        
        fetch('remove-individual-image.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mark primary image for removal (pending change)
            pendingImageChanges.primary = 'remove';
            displayProductImages();
            showNotification('Primary image moved to temporary storage (pending save)', 'success');
          } else {
            showNotification('Error removing primary image: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error removing primary image:', error);
          showNotification('Error removing primary image', 'error');
        });
      }
    );
  }
}

function removeAdditionalImage(imageId) {
  showCustomConfirm(
    'Remove Image',
    'Are you sure you want to remove this image?',
    () => {
      // Check if it's a temporary image
      if (typeof imageId === 'string' && imageId.startsWith('temp_')) {
        // Remove from pending additions
        pendingImageChanges.additional.toAdd = pendingImageChanges.additional.toAdd.filter(
          img => img.id !== imageId
        );
        
        // Remove from tempImageInfo
        tempImageInfo.additional = tempImageInfo.additional.filter(
          temp => temp.id !== imageId
        );
        
        displayProductImages();
        showNotification('Image removed from pending additions', 'success');
      } else {
        // For permanent images, move to temporary storage
        const productId = document.getElementById("editProductId").value;
        const formData = new FormData();
        formData.append('image_id', imageId);
        formData.append('product_id', productId);
        
        fetch('remove-individual-image.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mark permanent image for removal (pending change)
            pendingImageChanges.additional.toRemove.push(imageId);
            displayProductImages();
            showNotification('Image moved to temporary storage (pending save)', 'success');
          } else {
            showNotification('Error removing image: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error removing image:', error);
          showNotification('Error removing image', 'error');
        });
      }
    }
  );
}

// Set up image upload event listeners
document.addEventListener('DOMContentLoaded', function() {
  // Primary image upload
  const primaryImageInput = document.getElementById('editPrimaryImageInput');
  if (primaryImageInput) {
    primaryImageInput.addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        uploadPrimaryImage(this.files[0]);
      }
    });
  }

  // Additional images upload
  const additionalImagesInput = document.getElementById('editAdditionalImagesInput');
  if (additionalImagesInput) {
    additionalImagesInput.addEventListener('change', function(e) {
      if (this.files && this.files.length > 0) {
        // Calculate effective additional images count (including pending changes)
        let effectiveAdditional = [...(currentProductImages.additional || [])];
        
        // Remove images marked for deletion
        effectiveAdditional = effectiveAdditional.filter(img => 
          !pendingImageChanges.additional.toRemove.includes(img.id)
        );
        
        // Add pending images
        effectiveAdditional = [...effectiveAdditional, ...pendingImageChanges.additional.toAdd];
        
        const currentCount = effectiveAdditional.length;
        const filesToAdd = this.files.length;
        
        // Check if adding these files would exceed the limit
        if (currentCount + filesToAdd > 3) {
          showCustomConfirm(
            'Image Limit Exceeded',
            `3 images only can be posted for additional images. You currently have ${currentCount} images and are trying to add ${filesToAdd} more. Please remove some existing images first.`,
            () => {
              // User confirmed, clear the input
              this.value = '';
            }
          );
          return;
        }
        
        // Process files within limit
        Array.from(this.files).forEach(file => {
          uploadAdditionalImage(file);
        });
      }
    });
  }
  
  // Update additional images button state based on current count
  updateAdditionalImagesButtonState();
});

// Function to update additional images button state
function updateAdditionalImagesButtonState() {
  const additionalImagesInput = document.getElementById('editAdditionalImagesInput');
  const addImagesBtn = document.querySelector('button[onclick="document.getElementById(\'editAdditionalImagesInput\').click()"]');
  
  if (additionalImagesInput && addImagesBtn) {
    // Calculate effective additional images count (including pending changes)
    let effectiveAdditional = [...(currentProductImages.additional || [])];
    
    // Remove images marked for deletion
    effectiveAdditional = effectiveAdditional.filter(img => 
      !pendingImageChanges.additional.toRemove.includes(img.id)
    );
    
    // Add pending images
    effectiveAdditional = [...effectiveAdditional, ...pendingImageChanges.additional.toAdd];
    
    const currentCount = effectiveAdditional.length;
    
    if (currentCount >= 3) {
      addImagesBtn.disabled = true;
      addImagesBtn.style.opacity = '0.5';
      addImagesBtn.title = 'Maximum 3 additional images reached';
    } else {
      addImagesBtn.disabled = false;
      addImagesBtn.style.opacity = '1';
      addImagesBtn.title = `Add Images (${3 - currentCount} remaining)`;
    }
  }
}

function uploadPrimaryImage(file) {
  // Check if we already have a primary image (including pending changes)
  let effectivePrimary = currentProductImages.primary;
  if (pendingImageChanges.primary === 'remove') {
    effectivePrimary = null;
  } else if (pendingImageChanges.primary) {
    effectivePrimary = pendingImageChanges.primary;
  }
  
  if (effectivePrimary) {
    showCustomConfirm(
      'Image Limit Exceeded',
      '1 image only can be posted for primary image. Please remove the existing one first.',
      () => {
        // User confirmed, do nothing (just close the dialog)
      }
    );
    return;
  }
  
  // Get product ID
  const productId = document.getElementById("editProductId").value;
  
  // Upload to temporary directory
  const formData = new FormData();
  formData.append('image', file);
  formData.append('product_id', productId);
  formData.append('image_type', 'primary');
  
  fetch('upload-temp-image.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Create a temporary preview object with unique ID
      const tempId = 'temp_' + Date.now();
      const tempImage = {
        id: tempId,
        image_url: data.temp_path,
        is_temp: true
      };
      
      // Store temp image info with the same ID
      tempImageInfo.primary = {
        id: tempId,
        filename: data.temp_filename,
        path: data.temp_path
      };
      
      // Mark as pending change
      pendingImageChanges.primary = tempImage;
      displayProductImages();
      showNotification('Primary image added (pending save)', 'success');
    } else {
      showNotification('Error uploading image: ' + data.error, 'error');
    }
  })
  .catch(error => {
    console.error('Error uploading primary image:', error);
    showNotification('Error uploading primary image', 'error');
  });
}

function uploadAdditionalImage(file) {
  // Calculate effective additional images count (including pending changes)
  let effectiveAdditional = [...(currentProductImages.additional || [])];
  
  // Remove images marked for deletion
  effectiveAdditional = effectiveAdditional.filter(img => 
    !pendingImageChanges.additional.toRemove.includes(img.id)
  );
  
  // Add pending images
  effectiveAdditional = [...effectiveAdditional, ...pendingImageChanges.additional.toAdd];
  
  // Check if we already have 3 additional images
  if (effectiveAdditional.length >= 3) {
    showCustomConfirm(
      'Image Limit Exceeded',
      '3 images only can be posted for additional images. Please remove some existing images first.',
      () => {
        // User confirmed, do nothing (just close the dialog)
      }
    );
    return;
  }
  
  // Get product ID
  const productId = document.getElementById("editProductId").value;
  
  // Upload to temporary directory
  const formData = new FormData();
  formData.append('image', file);
  formData.append('product_id', productId);
  formData.append('image_type', 'additional');
  
  fetch('upload-temp-image.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Create a temporary preview object with unique ID
      const tempId = 'temp_' + Date.now();
      const tempImage = {
        id: tempId,
        image_url: data.temp_path,
        is_temp: true
      };
      
      // Store temp image info with the same ID
      tempImageInfo.additional.push({
        id: tempId,
        filename: data.temp_filename,
        path: data.temp_path
      });
      
      // Add to pending changes
      pendingImageChanges.additional.toAdd.push(tempImage);
      displayProductImages();
      showNotification('Additional image added (pending save)', 'success');
    } else {
      showNotification('Error uploading image: ' + data.error, 'error');
    }
  })
  .catch(error => {
    console.error('Error uploading additional image:', error);
    showNotification('Error uploading additional image', 'error');
  });
}

// Function to reset form to original values when canceling
function resetFormToOriginal() {
  if (Object.keys(originalFormData).length === 0) {
    return; // No original data to reset to
  }
  
  // Reset form fields to original values
  document.getElementById("editProductName").value = originalFormData.name || '';
  document.getElementById("editProductPrice").value = originalFormData.price || '';
  document.getElementById("editProductQuantity").value = originalFormData.quantity || '';
  document.getElementById("editProductStatus").value = originalFormData.status || '';
  document.getElementById("editIsFeature").value = originalFormData.isFeature ? "1" : "0";
  
  // Reset visibility option
  const visibilitySelect = document.getElementById("editVisibilityOption");
  if (visibilitySelect) {
    if (originalFormData.showWhenUnavailable) {
      visibilitySelect.value = "show";
    } else if (originalFormData.hideWhenUnavailable) {
      visibilitySelect.value = "hide";
    } else {
      visibilitySelect.value = "default";
    }
  }
  
  // Reset available days checkboxes
  const availableDaysArray = originalFormData.availableDays ? originalFormData.availableDays.split(', ') : [];
  const dayCheckboxes = {
    'edit_sunday': 'Sunday',
    'edit_monday': 'Monday',
    'edit_tuesday': 'Tuesday',
    'edit_wednesday': 'Wednesday',
    'edit_thursday': 'Thursday',
    'edit_friday': 'Friday',
    'edit_saturday': 'Saturday'
  };
  
  // Uncheck all checkboxes first
  Object.keys(dayCheckboxes).forEach(checkboxId => {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
      checkbox.checked = false;
    }
  });
  
  // Check the appropriate checkboxes based on original data
  availableDaysArray.forEach(day => {
    const checkboxId = Object.keys(dayCheckboxes).find(key => dayCheckboxes[key] === day);
    if (checkboxId) {
      const checkbox = document.getElementById(checkboxId);
      if (checkbox) {
        checkbox.checked = true;
      }
    }
  });
  
  // Reset available days visibility based on original status
  const availableDaysContainer = document.querySelector('.checkbox-group.days-group');
  if (availableDaysContainer) {
    if (originalFormData.statusName === 'Delivery') {
      availableDaysContainer.style.display = 'block';
    } else {
      availableDaysContainer.style.display = 'none';
    }
  }
  
  // Reset pending image changes
  pendingImageChanges = {
    primary: null,
    additional: {
      toAdd: [],
      toRemove: []
    }
  };
  
  // Reset temporary image info
  tempImageInfo = {
    primary: null,
    additional: []
  };
  
  // Clean up temporary images from server
  if (originalFormData.id) {
    const cleanupFormData = new FormData();
    cleanupFormData.append('product_id', originalFormData.id);
    
    fetch('cleanup-temp-images.php', {
      method: 'POST',
      body: cleanupFormData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Temporary images cleaned up:', data.cleaned_files);
      } else {
        console.error('Failed to cleanup temp images:', data.error);
      }
    })
    .catch(error => {
      console.error('Error cleaning up temp images:', error);
    });
    
    // Restore removed images
    const restoreFormData = new FormData();
    restoreFormData.append('product_id', originalFormData.id);
    
    fetch('restore-removed-images.php', {
      method: 'POST',
      body: restoreFormData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Removed images restored:', data.restored_images);
      } else {
        console.error('Failed to restore removed images:', data.error);
      }
    })
    .catch(error => {
      console.error('Error restoring removed images:', error);
    });
    
    // Reload original images
    loadProductImages(originalFormData.id);
  }
  
  // Clear original form data
  originalFormData = {};
}
