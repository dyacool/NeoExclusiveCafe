// Global variables
let currentDeleteId = null;

// Modal functions
function openAddModal() {
  document.getElementById("addModal").style.display = "block";
  document.getElementById("addLocationForm").reset();
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
    // Send delete request
    fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=delete&id=${currentDeleteId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert("Error deleting location: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Error deleting location");
      });

    closeDeleteModal();
  }
}

// Form submission functions
function addLocation(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  formData.append("action", "add");

  fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        alert("Error adding location: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error adding location");
    });
}

function updateLocation(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  formData.append("action", "update");

  fetch("/backend/pages/user-page-content/delivery-locations-handler.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        alert("Error updating location: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error updating location");
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
