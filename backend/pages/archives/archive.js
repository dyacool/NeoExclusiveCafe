document.addEventListener("DOMContentLoaded", () => {
  // Initialize search functionality
  setupSearch();
});

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
    "#archiveTableBody tr:not(.no-results)"
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

// Restore product functionality
function restoreProduct(id) {
  if (!confirm("Are you sure you want to restore this product?")) {
    return;
  }

  fetch("/backend/pages/archives/restore-product.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showNotification("Product restored successfully!", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        throw new Error(data.error || "Unknown error occurred");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        error.message || "An error occurred while restoring the product.",
        "error"
      );
    });
}

// Delete permanently functionality
function deletePermanently(id) {
  if (
    !confirm(
      "Are you sure you want to permanently delete this product? This action cannot be undone!"
    )
  ) {
    return;
  }

  fetch("/backend/pages/archives/delete-permanently.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showNotification("Product permanently deleted!", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        throw new Error(data.error || "Unknown error occurred");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        error.message || "An error occurred while deleting the product.",
        "error"
      );
    });
}

// Utility functions
function toggleEmptyState(show) {
  const emptyState = document.querySelector(".no-results");
  const normalRows = document.querySelectorAll(
    "#archiveTableBody tr:not(.no-results)"
  );

  if (show && !emptyState) {
    // Create empty state if it doesn't exist
    const tbody = document.getElementById("archiveTableBody");
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
                    <p>Try adjusting your search criteria.</p>
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
  notification.className = `notification ${type}`;
  notification.textContent = message;

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.classList.add("show");
  }, 100);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}
