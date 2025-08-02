document.addEventListener("DOMContentLoaded", () => {
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

  // Filter table rows
  const rows = document.querySelectorAll(
    "#productTableBody tr:not(.no-results)"
  );
  let visibleCount = 0;

  rows.forEach((row) => {
    const rowStatus = row.getAttribute("data-status");
    const shouldShow = status === "all" || rowStatus === status;

    row.style.display = shouldShow ? "" : "none";
    if (shouldShow) visibleCount++;
  });

  // Handle pagination visibility
  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = status === "all" ? "flex" : "none";
  }

  // Show/hide empty state
  toggleEmptyState(visibleCount === 0);
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
  const rows = document.querySelectorAll(
    "#productTableBody tr:not(.no-results)"
  );
  let visibleCount = 0;

  rows.forEach((row) => {
    const name = row.getAttribute("data-name") || "";
    const sku = row.getAttribute("data-sku") || "";
    const shouldShow = name.includes(searchTerm) || sku.includes(searchTerm);

    row.style.display = shouldShow ? "" : "none";
    if (shouldShow) visibleCount++;
  });

  // Hide pagination when searching
  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = searchTerm ? "none" : "flex";
  }

  // Show/hide empty state
  toggleEmptyState(visibleCount === 0);
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

  // Re-add empty state if needed
  const visibleRows = rows.filter((row) => row.style.display !== "none");
  if (visibleRows.length === 0) {
    toggleEmptyState(true);
  }
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
  quantity
) {
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

  // Show modal
  document.getElementById("editModal").style.display = "flex";
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "auto";
}

function handleFormSubmit(event) {
  event.preventDefault();

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
  };

  // Show loading state
  const submitBtn = event.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "Saving...";
  submitBtn.disabled = true;

  // Send update request
  fetch("/backend/pages/products/update-product.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(formData),
  })
    .then((response) => response.json())
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
        "An error occurred while updating the product.",
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
  const rows = document.querySelectorAll(
    "#productTableBody tr:not(.no-results)"
  );
  const counts = {
    all: rows.length,
    "Bread of the Week": 0,
    Available: 0,
    Unavailable: 0,
  };

  rows.forEach((row) => {
    const status = row.getAttribute("data-status");
    if (counts.hasOwnProperty(status)) {
      counts[status]++;
    }
  });

  // Update count displays
  document.getElementById("count-all").textContent = counts.all;
  document.getElementById("count-featured").textContent =
    counts["Bread of the Week"];
  document.getElementById("count-available").textContent = counts["Available"];
  document.getElementById("count-unavailable").textContent =
    counts["Unavailable"];
}

function toggleEmptyState(show) {
  const emptyState = document.querySelector(".no-results");
  const normalRows = document.querySelectorAll(
    "#productTableBody tr:not(.no-results)"
  );

  if (show && !emptyState) {
    // Create empty state if it doesn't exist
    const tbody = document.getElementById("productTableBody");
    const emptyRow = document.createElement("tr");
    emptyRow.className = "no-results";
    emptyRow.innerHTML = `
            <td colspan="6">
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria.</p>
                </div>
            </td>
        `;
    tbody.appendChild(emptyRow);
  } else if (emptyState) {
    emptyState.style.display = show ? "" : "none";
  }
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
