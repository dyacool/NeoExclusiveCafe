// Global variables
let currentDeleteId = null;

// Notification System
function showNotification(message, type = "success") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notification) => notification.remove());

  // Get notification container
  const container = document.getElementById("notification-container");

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-message">${message}</span>
      <button class="notification-close" onclick="closeNotification(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  `;

  // Add to container
  container.appendChild(notification);

  // Show notification with animation
  setTimeout(() => notification.classList.add("show"), 100);

  // Auto-hide after 5 seconds
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, 5000);
}

function closeNotification(button) {
  const notification = button.closest(".notification");
  notification.classList.remove("show");
  setTimeout(() => notification.remove(), 300);
}

// Loading state management
function setButtonLoading(button, isLoading, originalText = "") {
  if (isLoading) {
    button.disabled = true;
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = `
      <span class="loading-spinner"></span>
      Loading...
    `;
    button.classList.add("loading");
  } else {
    button.disabled = false;
    button.innerHTML = button.dataset.originalText || originalText;
    button.classList.remove("loading");
  }
}

// Modal functions
function openAddModal() {
  console.log("openAddModal called");
  const modal = document.getElementById("addModal");
  console.log("Modal element:", modal);
  if (modal) {
    modal.style.display = "block";
    console.log("Modal display set to block");
  } else {
    console.error("Modal element not found!");
  }

  const form = document.getElementById("addLocationForm");
  if (form) {
    form.reset();
    console.log("Form reset");
  } else {
    console.error("Form element not found!");
  }
}

function closeAddModal() {
  document.getElementById("addModal").style.display = "none";
}

function openEditModal(id, municipality, city, postalCode, deliveryFee) {
  document.getElementById("editModal").style.display = "block";
  document.getElementById("editLocationId").value = id;
  document.getElementById("editMunicipality").value = municipality;
  document.getElementById("editCity").value = city;
  document.getElementById("editPostalCode").value = postalCode;
  document.getElementById("editDeliveryFee").value = deliveryFee;
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

function deleteLocation(id) {
  currentDeleteId = id;
  document.getElementById("deleteModal").style.display = "block";
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
  currentDeleteId = null;
}

function confirmDelete() {
  if (currentDeleteId) {
    const deleteButton = document.querySelector("#deleteModal .btn-danger");
    deleteButton.disabled = true;
    deleteButton.dataset.originalText = deleteButton.innerHTML;
    deleteButton.innerHTML = `
      <span class="loading-spinner"></span>
      Deleting...
    `;
    deleteButton.classList.add("loading");

    // Send delete request
    fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=delete&delivery_id=${currentDeleteId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        setButtonLoading(deleteButton, false);

        if (data.success) {
          showNotification(
            data.message || "Location deleted successfully!",
            "success"
          );
          closeDeleteModal();
          setTimeout(() => location.reload(), 1500);
        } else {
          showNotification(data.message || "Error deleting location", "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        setButtonLoading(deleteButton, false);
        showNotification("Error deleting location: " + error.message, "error");
      });
  }
}

// Form submission functions
function addLocation(event) {
  console.log("addLocation called");
  event.preventDefault();

  const submitButton = event.target.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.dataset.originalText = submitButton.innerHTML;
  submitButton.innerHTML = `
    <span class="loading-spinner"></span>
    Adding...
  `;
  submitButton.classList.add("loading");

  const formData = new FormData(event.target);
  formData.append("action", "add");

  console.log("Form data:", Object.fromEntries(formData));

  fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Response status:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Response data:", data);
      setButtonLoading(submitButton, false);

      if (data.success) {
        showNotification(
          data.message || "Location added successfully!",
          "success"
        );
        closeAddModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification(data.message || "Error adding location", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      setButtonLoading(submitButton, false);
      showNotification("Error adding location: " + error.message, "error");
    });
}

function updateLocation(event) {
  event.preventDefault();

  const submitButton = event.target.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.dataset.originalText = submitButton.innerHTML;
  submitButton.innerHTML = `
    <span class="loading-spinner"></span>
    Updating...
  `;
  submitButton.classList.add("loading");

  const formData = new FormData(event.target);
  formData.append("action", "update");

  fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      setButtonLoading(submitButton, false);

      if (data.success) {
        showNotification(
          data.message || "Location updated successfully!",
          "success"
        );
        closeEditModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification(data.message || "Error updating location", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      setButtonLoading(submitButton, false);
      showNotification("Error updating location: " + error.message, "error");
    });
}

// Sorting functions
function sortLocations(sortType, buttonElement) {
  // Remove active class from all sort buttons
  document
    .querySelectorAll(".sort-btn")
    .forEach((btn) => btn.classList.remove("active"));

  // Add active class to clicked button
  buttonElement.classList.add("active");

  const tableBody = document.getElementById("locationsTableBody");
  const rows = Array.from(tableBody.querySelectorAll("tr"));

  // Skip if no data rows (only no-results message)
  if (rows.length === 1 && rows[0].classList.contains("no-results")) {
    return;
  }

  rows.sort((a, b) => {
    let aValue, bValue;

    switch (sortType) {
      case "az":
        aValue = a.dataset.municipality?.toLowerCase() || "";
        bValue = b.dataset.municipality?.toLowerCase() || "";
        return aValue.localeCompare(bValue);

      case "za":
        aValue = a.dataset.municipality?.toLowerCase() || "";
        bValue = b.dataset.municipality?.toLowerCase() || "";
        return bValue.localeCompare(aValue);

      case "postal":
        aValue = parseInt(a.dataset.postal) || 0;
        bValue = parseInt(b.dataset.postal) || 0;
        return aValue - bValue;

      case "fee":
        aValue = parseFloat(a.dataset.fee) || 0;
        bValue = parseFloat(b.dataset.fee) || 0;
        return aValue - bValue;

      default:
        return 0;
    }
  });

  // Clear table body and append sorted rows
  tableBody.innerHTML = "";
  rows.forEach((row) => tableBody.appendChild(row));
}

// Input validation
document.addEventListener("DOMContentLoaded", function () {
  // Postal code validation - only allow 4 digits
  const postalInputs = document.querySelectorAll('input[name="postal_code"]');
  postalInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      // Remove any non-digit characters
      this.value = this.value.replace(/\D/g, "");

      // Limit to 4 digits
      if (this.value.length > 4) {
        this.value = this.value.slice(0, 4);
      }
    });
  });

  // Delivery fee validation - only allow positive numbers
  const feeInputs = document.querySelectorAll('input[name="delivery_fee"]');
  feeInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      // Remove negative sign if present
      if (this.value < 0) {
        this.value = Math.abs(this.value);
      }
    });
  });

  // Close modals when clicking outside
  window.addEventListener("click", function (event) {
    const modals = document.querySelectorAll(".modal");
    modals.forEach((modal) => {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // Close modals with Escape key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      const openModals = document.querySelectorAll('.modal[style*="block"]');
      openModals.forEach((modal) => {
        modal.style.display = "none";
      });
    }
  });
});
