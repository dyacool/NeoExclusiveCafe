// Global variables
let allProductsData = [];
let currentProductImages = { primary: null, additional: [] };
let originalFormData = {};
let pendingImageChanges = {
  primary: null,
  additional: { toAdd: [], toRemove: [] },
};
const tempImageFiles = { primary: null, additional: [] };
let tempImageInfo = { primary: null, additional: [] };

document.addEventListener("DOMContentLoaded", () => {
  // Load all products data
  const allProductsContainer = document.getElementById("allProductsData");
  if (allProductsContainer) {
    try {
      allProductsData = JSON.parse(allProductsContainer.textContent);
    } catch (e) {
      console.error("Error parsing all products data:", e);
      allProductsData = [];
    }
  }

  updateFilterCounts();
  setupSearch();
  setupModal();
  setupImageUploadListeners();
});

// Update filter counts
function updateFilterCounts() {
  if (!allProductsData || allProductsData.length === 0) return;

  const counts = {
    all: allProductsData.length,
    pickup: allProductsData.filter((p) => p.status_id == 1).length,
    delivery: allProductsData.filter((p) => p.status_id == 2).length,
    deliveryPickup: allProductsData.filter((p) => p.status_id == 3).length,
    availableToday: allProductsData.filter((p) => p.status_id == 4).length,
    featured: allProductsData.filter((p) => p.is_featured == 1).length,
    unavailable: allProductsData.filter((p) => p.unavailable_status_id !== null)
      .length,
  };

  // Update count badges
  const countElements = {
    "count-all": counts.all,
    "count-pickup": counts.pickup,
    "count-delivery": counts.delivery,
    "count-delivery-pickup": counts.deliveryPickup,
    "count-available-today": counts.availableToday,
    "count-featured": counts.featured,
    "count-unavailable": counts.unavailable,
  };

  for (const [id, count] of Object.entries(countElements)) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = count;
    }
  }
}

// Filter functionality
function filterProducts(status, button) {
  document
    .querySelectorAll(".filter-btn")
    .forEach((btn) => btn.classList.remove("active"));
  button.classList.add("active");

  const unavailableDropdown = document.getElementById(
    "unavailableTypeDropdown"
  );
  if (unavailableDropdown) {
    if (status === "Unavailable") {
      unavailableDropdown.style.display = "inline-block";
    } else {
      unavailableDropdown.style.display = "none";
      unavailableDropdown.value = "all-unavailable";
    }
  }

  let filteredProducts = [];

  if (status === "all") {
    filteredProducts = allProductsData;
  } else if (status === "featured") {
    filteredProducts = allProductsData.filter(
      (product) => product.is_featured == 1
    );
  } else if (status === "Unavailable") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id !== null
    );
  } else if (status === "Same Day Order") {
    filteredProducts = allProductsData.filter(
      (product) => product.status_id == 4
    );
  } else {
    filteredProducts = allProductsData.filter(
      (product) => product.status_name === status
    );
  }

  const tbody = document.getElementById("productTableBody");
  tbody.innerHTML = "";

  if (filteredProducts.length > 0) {
    filteredProducts.forEach((product) => {
      const row = createProductRow(product);
      tbody.appendChild(row);
    });
  } else {
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

  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = status === "all" ? "flex" : "none";
  }
}

function filterUnavailableByType() {
  const dropdown = document.getElementById("unavailableTypeDropdown");
  const selectedValue = dropdown.value;

  let filteredProducts = [];

  if (selectedValue === "all-unavailable") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id !== null
    );
  } else if (selectedValue === "unavailable-delivery") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id === 2
    );
  } else if (selectedValue === "unavailable-pickup") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id === 1
    );
  } else if (selectedValue === "unavailable-today") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id === 3
    );
  }

  const tbody = document.getElementById("productTableBody");
  tbody.innerHTML = "";

  if (filteredProducts.length > 0) {
    filteredProducts.forEach((product) => {
      const row = createProductRow(product);
      tbody.appendChild(row);
    });
  } else {
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
}

function formatAvailableDays(availableDays) {
  if (!availableDays) return "Not Applicable";

  const dayMap = {
    Sunday: "Sun",
    Monday: "Mon",
    Tuesday: "Tue",
    Wednesday: "Wed",
    Thursday: "Thu",
    Friday: "Fri",
    Saturday: "Sat",
  };

  const days = availableDays.split(", ");
  const formattedDays = days.map((day) => dayMap[day] || day);
  return formattedDays.join(", ");
}

function createProductRow(product) {
  const quantity = Number.parseInt(product.quantity) || 0;
  const quantityClass =
    quantity <= 5
      ? "low-stock"
      : quantity <= 10
      ? "medium-stock"
      : "good-stock";
  const statusClass = (product.status_name || "Unknown")
    .toLowerCase()
    .replace(" ", "-");

  let imagePath = "";
  if (product.image_url) {
    imagePath = "/assets/" + product.image_url;
  }

  const row = document.createElement("tr");
  row.setAttribute("data-status", product.status_name || "Unknown");
  row.setAttribute("data-name", product.name.toLowerCase());
  row.setAttribute("data-sku", product.sku.toLowerCase());

  row.innerHTML = `
    <td>
      <div class='product-image-container'>
        <img class='product-image' src='${imagePath}' alt='${
    product.name
  }' loading='lazy'>
        ${
          product.is_featured == 1
            ? "<span class='featured-badge'>★</span>"
            : ""
        }
      </div>
    </td>
    <td><span class='sku-text'>${product.sku}</span></td>
    <td>
      <div class='product-info'>
        <span class='product-name'>${product.name}</span>
      </div>
    </td>
    <td><span class='price-text'>₱${Number.parseFloat(
      product.price
    ).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}</span></td>
    <td>
      <div class='status-container'>
        <span class='status-badge status-${statusClass}'>${
    product.status_id == 4 ? "Same Day Order" : product.status_name || "Unknown"
  }</span>
        ${
          product.status_id == 4 && product.availtoday_status_name
            ? `<span class='availtoday-badge'>For ${product.availtoday_status_name}</span>`
            : (product.status_id == 1 ||
                product.status_id == 2 ||
                product.status_id == 3) &&
              product.availtoday_status_name
            ? `<span class='availtoday-badge-also'>Also for SDO: ${product.availtoday_status_name}</span>`
            : ""
        }
        <span class='stock-badge ${quantityClass}'>
          <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
            <path d='M20 7h-9'></path><path d='M14 17H5'></path>
            <circle cx='17' cy='17' r='3'></circle><circle cx='7' cy='7' r='3'></circle>
          </svg>
          ${quantity} in stock
        </span>
      </div>
    </td>
    <td><span class='available-days-text'>${formatAvailableDays(
      product.available_days
    )}</span></td>
    <td>
      <div class='action-buttons'>
        <button class='btn-action btn-edit' onclick="openEditModal(
          '${product.id}', '${product.name.replace(/'/g, "\\'")}', '${
    product.price
  }', '${product.status_id}',
          ${product.is_featured == 1 ? "true" : "false"}, ${
    product.show_when_unavailable == 1 ? "true" : "false"
  },
          ${product.hide_when_unavailable == 1 ? "true" : "false"}, ${quantity},
          '${(product.available_days || "").replace(/'/g, "\\'")}', '${(
    product.status_name || "Unknown"
  ).replace(/'/g, "\\'")}', '${product.unavailable_status_id || "null"}', '${(
    product.unavailable_status_name || ""
  ).replace(/'/g, "\\'")}', '${product.availtoday_status_id || "null"}', '${(
    product.availtoday_status_name || ""
  ).replace(/'/g, "\\'")}'
        )" title='Edit Product'>
          <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
            <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
            <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
          </svg>
        </button>
        <button class='btn-action btn-delete' onclick='softDeleteProduct(${
          product.id
        })' title='Delete Product'>
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
    location.reload();
    return;
  }

  const filteredProducts = allProductsData.filter(
    (product) =>
      product.name.toLowerCase().includes(searchTerm) ||
      product.sku.toLowerCase().includes(searchTerm)
  );

  const tbody = document.getElementById("productTableBody");
  tbody.innerHTML = "";

  if (filteredProducts.length > 0) {
    filteredProducts.forEach((product) => {
      const row = createProductRow(product);
      tbody.appendChild(row);
    });
  } else {
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

  const paginationContainer = document.querySelector(".pagination-container");
  if (paginationContainer) {
    paginationContainer.style.display = "none";
  }
}

// Sort functionality
const sortStates = { 1: null, 2: null, 3: null, az: null };

function toggleSort(columnIndex) {
  Object.keys(sortStates).forEach((key) => {
    if (key != columnIndex) {
      sortStates[key] = null;
    }
  });

  const currentState = sortStates[columnIndex];
  document.querySelectorAll(".sort-btn").forEach((btn) => {
    btn.classList.remove("active", "asc", "desc");
  });

  const newState =
    currentState === null || currentState === "desc" ? "asc" : "desc";
  sortStates[columnIndex] = newState;

  const buttonIds = { 1: "sort-sku", 2: "sort-name", 3: "sort-price" };
  const button = document.getElementById(buttonIds[columnIndex]);
  button.classList.add("active", newState);

  sortTable(columnIndex, newState);
}

function sortTable(columnIndex, direction) {
  const tbody = document.querySelector("#productTableBody");
  const rows = Array.from(tbody.querySelectorAll("tr:not(.no-results)"));

  rows.sort((rowA, rowB) => {
    let cellA = rowA.cells[columnIndex].textContent.trim();
    let cellB = rowB.cells[columnIndex].textContent.trim();

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

  tbody.innerHTML = "";
  rows.forEach((row) => tbody.appendChild(row));
}

// Modal functionality
function setupModal() {
  const modal = document.getElementById("editModal");
  const overlay = document.querySelector(".modal-overlay");

  if (overlay) {
    overlay.addEventListener("click", closeModal);
  }

  const form = document.getElementById("editProductForm");
  if (form) {
    form.addEventListener("submit", handleFormSubmit);
  }
}

// Show/hide modal loading overlay
function showModalLoading(show) {
  let loadingOverlay = document.getElementById("modalLoadingOverlay");

  if (show) {
    if (!loadingOverlay) {
      // Create loading overlay if it doesn't exist
      loadingOverlay = document.createElement("div");
      loadingOverlay.id = "modalLoadingOverlay";
      loadingOverlay.className = "modal-loading-overlay";
      loadingOverlay.innerHTML = `
        <div class="modal-loading-spinner">
          <div class="spinner"></div>
          <p>Loading product data...</p>
        </div>
      `;

      const modalContent = document.querySelector("#editModal .modal-content");
      if (modalContent) {
        modalContent.appendChild(loadingOverlay);
      }
    }
    loadingOverlay.style.display = "flex";
  } else {
    if (loadingOverlay) {
      loadingOverlay.style.display = "none";
    }
  }
}

// Show/hide modal saving overlay
function showModalSaving(show) {
  let savingOverlay = document.getElementById("modalSavingOverlay");

  if (show) {
    if (!savingOverlay) {
      // Create saving overlay if it doesn't exist
      savingOverlay = document.createElement("div");
      savingOverlay.id = "modalSavingOverlay";
      savingOverlay.className = "modal-loading-overlay"; // Reuse same styling
      savingOverlay.innerHTML = `
        <div class="modal-loading-spinner">
          <div class="spinner"></div>
          <p>Saving product...</p>
        </div>
      `;

      const modalContent = document.querySelector("#editModal .modal-content");
      if (modalContent) {
        modalContent.appendChild(savingOverlay);
      }
    }
    savingOverlay.style.display = "flex";
  } else {
    if (savingOverlay) {
      savingOverlay.style.display = "none";
    }
  }
}

function openEditModal(
  id,
  name,
  description,
  price,
  status,
  isFeature,
  showWhenUnavailable,
  hideWhenUnavailable,
  quantity,
  availableDays,
  statusName,
  unavailableStatusId,
  unavailableStatusName,
  availtodayStatusId,
  availtodayStatusName,
  todaysProductDates,
  regularTodayDates,
  categoryId
) {
  // Show modal with loading state immediately
  const modal = document.getElementById("editModal");
  modal.style.display = "flex";
  document.body.style.overflow = "hidden";

  // Show loading overlay
  showModalLoading(true);

  // Store original form data
  originalFormData = {
    id,
    name,
    description,
    price,
    status,
    isFeature,
    showWhenUnavailable,
    hideWhenUnavailable,
    quantity,
    availableDays,
    statusName,
    unavailableStatusId,
    unavailableStatusName,
    availtodayStatusId,
    availtodayStatusName,
    categoryId,
    todaysProductDates,
    regularTodayDates,
  };

  // Populate form fields
  document.getElementById("editProductId").value = id;
  document.getElementById("editProductName").value = name;
  document.getElementById("editProductDescription").value = description || "";
  document.getElementById("editProductPrice").value = price;
  document.getElementById("editProductQuantity").value = quantity;

  // Set category
  const categorySelect = document.getElementById("editProductCategory");
  if (categorySelect) {
    categorySelect.value =
      categoryId && categoryId !== "null" ? categoryId : "";
  }

  // Initialize checkbox states based on product data
  const preOrderCheckbox = document.getElementById("editPreOrderCheckbox");
  const sameDayCheckbox = document.getElementById("editSameDayCheckbox");
  const preOrderOptions = document.getElementById("editPreOrderOptions");
  const sameDayOptions = document.getElementById("editSameDayOptions");
  const preOrderDropdown = document.getElementById("editPreOrderStatus");
  const sameDayDropdown = document.getElementById("editSameDayStatus");
  const todaysCalendarContainer = document.getElementById(
    "todaysProductCalendarContainer"
  );
  const availableTodayCalendarContainer = document.getElementById(
    "availableTodayCalendarContainer"
  );

  // Determine checkbox states
  const isPreOrder = status == 1 || status == 2 || status == 3;
  const isSameDay =
    status == 4 || (availtodayStatusId && availtodayStatusId !== "null");

  // Set pre-order checkbox and dropdown
  if (preOrderCheckbox) {
    preOrderCheckbox.checked = isPreOrder;
    if (isPreOrder && preOrderOptions && preOrderDropdown) {
      preOrderOptions.style.display = "block";
      preOrderDropdown.value = status;
    } else if (preOrderOptions) {
      preOrderOptions.style.display = "none";
    }
  }

  // Set same-day checkbox and dropdown
  if (sameDayCheckbox) {
    sameDayCheckbox.checked = isSameDay;
    if (isSameDay && sameDayOptions && sameDayDropdown) {
      sameDayOptions.style.display = "block";
      sameDayDropdown.value =
        availtodayStatusId && availtodayStatusId !== "null"
          ? availtodayStatusId
          : "1";

      // Show appropriate calendar based on whether pre-order is also checked
      if (isPreOrder) {
        // Both pre-order and same-day: show availableTodayCalendar
        if (availableTodayCalendarContainer)
          availableTodayCalendarContainer.style.display = "block";
        if (todaysCalendarContainer)
          todaysCalendarContainer.style.display = "none";
      } else {
        // Only same-day: show todaysProductCalendar
        if (todaysCalendarContainer)
          todaysCalendarContainer.style.display = "block";
        if (availableTodayCalendarContainer)
          availableTodayCalendarContainer.style.display = "none";
      }
    } else {
      // Same-day not checked: hide both calendars
      if (sameDayOptions) sameDayOptions.style.display = "none";
      if (todaysCalendarContainer)
        todaysCalendarContainer.style.display = "none";
      if (availableTodayCalendarContainer)
        availableTodayCalendarContainer.style.display = "none";
    }
  }

  const isFeatureBool =
    isFeature === true ||
    isFeature === "true" ||
    isFeature === 1 ||
    isFeature === "1";
  const featuredSelect = document.getElementById("editIsFeature");
  if (featuredSelect) {
    featuredSelect.value = isFeatureBool ? "1" : "0";
  }

  // Set visibility option
  const visibilitySelect = document.getElementById("editVisibilityOption");
  const showWhenUnavailableBool =
    showWhenUnavailable === true ||
    showWhenUnavailable === "true" ||
    showWhenUnavailable === 1 ||
    showWhenUnavailable === "1";
  const hideWhenUnavailableBool =
    hideWhenUnavailable === true ||
    hideWhenUnavailable === "true" ||
    hideWhenUnavailable === 1 ||
    hideWhenUnavailable === "1";

  if (showWhenUnavailableBool) {
    visibilitySelect.value = "show";
  } else if (hideWhenUnavailableBool) {
    visibilitySelect.value = "hide";
  } else {
    visibilitySelect.value = "default";
  }

  // Set availability radio buttons
  const availableRadio = document.getElementById("editAvailable");
  const unavailableRadio = document.getElementById("editUnavailable");
  const unavailableTypeContainer = document.getElementById(
    "editUnavailableTypeContainer"
  );
  const unavailableTypeSelect = document.getElementById("editUnavailableType");

  if (unavailableStatusId && unavailableStatusId !== "null") {
    unavailableRadio.checked = true;
    availableRadio.checked = false;
    unavailableTypeContainer.style.display = "block";
    unavailableTypeSelect.value = unavailableStatusId;

    const quantityField = document.getElementById("editProductQuantity");
    if (quantityField) {
      quantityField.value = "0";
      quantityField.disabled = true;
      quantityField.style.opacity = "0.5";
      quantityField.style.cursor = "not-allowed";
    }
  } else {
    availableRadio.checked = true;
    unavailableRadio.checked = false;
    unavailableTypeContainer.style.display = "none";
    unavailableTypeSelect.value = "";

    const quantityField = document.getElementById("editProductQuantity");
    if (quantityField) {
      // Check if status is Same Day Order (status_id 4)
      if (status == 4) {
        quantityField.value = "0";
        quantityField.disabled = true;
        quantityField.style.opacity = "0.5";
        quantityField.style.cursor = "not-allowed";
      } else {
        quantityField.disabled = false;
        quantityField.style.opacity = "1";
        quantityField.style.cursor = "text";
      }
    }
  }

  // Display current available days (read-only)
  const currentAvailableDaysSpan = document.getElementById(
    "currentAvailableDays"
  );
  if (currentAvailableDaysSpan) {
    if (availableDays) {
      currentAvailableDaysSpan.textContent = availableDays;
    } else {
      currentAvailableDaysSpan.textContent = "Not set";
    }
  }

  // Show/hide available days container for specific statuses
  const regularAvailableDaysContainer = document.getElementById(
    "regularAvailableDaysContainer"
  );
  if (regularAvailableDaysContainer) {
    if (
      statusName === "Delivery" ||
      statusName === "Pick Up" ||
      statusName === "Delivery or Pick Up"
    ) {
      regularAvailableDaysContainer.style.display = "block";
    } else {
      regularAvailableDaysContainer.style.display = "none";
    }
  }

  // Update quantity field state based on checkbox states
  updateQuantityFieldState();

  setupAvailabilityRadioListeners();

  // Load images and wait for them to complete before hiding loading
  loadProductImages(id).then(() => {
    // Initialize calendars after images are loaded
    setTimeout(() => {
      if (window.modalCalendarHandler) {
        window.modalCalendarHandler.initializeEditModalCalendars();

        // Set selected dates based on product type
        if (isSameDay) {
          if (isPreOrder) {
            // Both pre-order and same-day: use availableTodayCalendar with regularTodayDates
            if (regularTodayDates) {
              const dates = regularTodayDates
                .split(",")
                .filter((d) => d.trim());
              if (dates.length > 0) {
                window.modalCalendarHandler.setAvailableTodayDates(dates);
              }
            }
          } else {
            // Only same-day: use todaysProductCalendar with todaysProductDates
            if (todaysProductDates) {
              const dates = todaysProductDates
                .split(",")
                .filter((d) => d.trim());
              if (dates.length > 0) {
                window.modalCalendarHandler.setTodaysProductDates(dates);
              }
            }
          }
        }
      }

      // Initialize SDO quantity manager
      if (typeof initializeSDOQuantities === "function") {
        initializeSDOQuantities(id);
      }

      // Hide loading overlay after everything is initialized (including Cloudinary images)
      showModalLoading(false);

      // Reset save button state to ensure it's not stuck in loading state
      const saveButton = document.querySelector(".modal-footer .btn-primary");
      if (saveButton) {
        saveButton.disabled = false;
        saveButton.innerHTML = "Save Changes";
      }
    }, 200);
  });
}

function setupStatusChangeListener() {
  const statusSelect = document.getElementById("editProductStatus");
  const availableDaysContainer = document.querySelector(
    ".checkbox-group.days-group"
  );
  const unavailableRadio = document.getElementById("editUnavailable");
  const unavailableTypeContainer = document.getElementById(
    "editUnavailableTypeContainer"
  );
  const unavailableTypeSelect = document.getElementById("editUnavailableType");
  const availtodayOptions = document.getElementById("editAvailtodayOptions");
  const availtodaySelect = document.getElementById("editAvailtodayStatus");
  const isAvailableTodayContainer = document.getElementById(
    "isAvailableTodayContainer"
  );

  if (statusSelect) {
    statusSelect.addEventListener("change", function () {
      const selectedStatus = this.options[this.selectedIndex].text;
      const selectedValue = this.value;

      // Update dynamic status name label
      const dynamicStatusLabel = document.getElementById("dynamicStatusName");
      if (dynamicStatusLabel) {
        dynamicStatusLabel.textContent = selectedStatus || "Product";
      }

      // Show/hide available days container for specific statuses
      const regularAvailableDaysContainer = document.getElementById(
        "regularAvailableDaysContainer"
      );
      if (regularAvailableDaysContainer) {
        if (
          selectedStatus === "Delivery" ||
          selectedStatus === "Pick Up" ||
          selectedStatus === "Delivery or Pick Up"
        ) {
          regularAvailableDaysContainer.style.display = "block";
        } else {
          regularAvailableDaysContainer.style.display = "none";
        }
      }

      // Handle isAvailableToday radio button visibility - only show for Pick Up, Delivery, or Delivery or Pick Up
      if (isAvailableTodayContainer) {
        if (
          selectedStatus === "Pick Up" ||
          selectedStatus === "Delivery" ||
          selectedStatus === "Delivery or Pick Up"
        ) {
          isAvailableTodayContainer.style.display = "block";
        } else {
          isAvailableTodayContainer.style.display = "none";
          // Reset the radio button and hide related elements when not eligible
          const isAvailableTodayRadio =
            document.getElementById("isAvailableToday");
          if (isAvailableTodayRadio) {
            isAvailableTodayRadio.checked = false;
          }
          if (availtodayOptions) {
            availtodayOptions.style.display = "none";
          }
          const availableTodayDaysContainer = document.getElementById(
            "availableTodayDaysContainer"
          );
          if (availableTodayDaysContainer) {
            availableTodayDaysContainer.style.display = "none";
          }
        }
      }

      // Handle availtoday_status dropdown visibility
      if (availtodayOptions && availtodaySelect) {
        if (selectedValue == 4) {
          // Same Day Order (status_id 4)
          availtodayOptions.style.display = "block";
          availtodaySelect.setAttribute("required", "required");
        } else {
          availtodayOptions.style.display = "none";
          availtodaySelect.removeAttribute("required");
          // Don't clear the value - preserve it
        }
      }

      // Handle quantity field for Same Day Order (status_id 4)
      const quantityField = document.getElementById("editProductQuantity");
      if (quantityField) {
        if (selectedValue == 4) {
          // Same Day Order - disable quantity field and set to 0
          quantityField.value = "0";
          quantityField.disabled = true;
          quantityField.style.opacity = "0.5";
          quantityField.style.cursor = "not-allowed";
        } else if (!unavailableRadio || !unavailableRadio.checked) {
          // Other statuses - enable quantity field (unless unavailable is checked)
          quantityField.disabled = false;
          quantityField.style.opacity = "1";
          quantityField.style.cursor = "text";
        }
      }

      // Call the modal calendar handler for additional calendar logic
      if (window.modalCalendarHandler) {
        window.modalCalendarHandler.handleEditStatusChange();
      }

      if (unavailableRadio && unavailableRadio.checked) {
        const currentStatus = this.value;
        let unavailableTypeId = null;

        if (currentStatus === "1") unavailableTypeId = "1";
        else if (currentStatus === "2") unavailableTypeId = "2";
        else if (currentStatus === "3") unavailableTypeId = "3";
        else if (currentStatus === "4") unavailableTypeId = "4";

        if (unavailableTypeId) {
          unavailableTypeSelect.value = unavailableTypeId;
        }

        const messageElement = unavailableTypeContainer.querySelector("small");
        if (messageElement && unavailableRadio.checked) {
          let statusText = "";
          if (currentStatus === "1") statusText = "Pick Up";
          else if (currentStatus === "2") statusText = "Delivery";
          else if (currentStatus === "3") statusText = "Delivery or Pick Up";
          else if (currentStatus === "4") statusText = "Same Day Order";

          messageElement.textContent = `Will be set to: Unavailable ${statusText}`;
        }
      }
    });
  }
}

function setupAvailabilityRadioListeners() {
  const availableRadio = document.getElementById("editAvailable");
  const unavailableRadio = document.getElementById("editUnavailable");
  const unavailableTypeContainer = document.getElementById(
    "editUnavailableTypeContainer"
  );
  const unavailableTypeSelect = document.getElementById("editUnavailableType");

  if (availableRadio && unavailableRadio) {
    availableRadio.addEventListener("change", function () {
      if (this.checked) {
        unavailableTypeContainer.style.display = "none";
        unavailableTypeSelect.value = "";
        // Update quantity field state based on checkbox states
        updateQuantityFieldState();
      }
    });

    unavailableRadio.addEventListener("change", function () {
      if (this.checked) {
        // Determine unavailable type based on checkbox states
        const preOrderCheckbox = document.getElementById(
          "editPreOrderCheckbox"
        );
        const sameDayCheckbox = document.getElementById("editSameDayCheckbox");
        const preOrderDropdown = document.getElementById("editPreOrderStatus");

        let unavailableTypeId = null;
        if (preOrderCheckbox && preOrderCheckbox.checked && preOrderDropdown) {
          unavailableTypeId = preOrderDropdown.value;
        } else if (sameDayCheckbox && sameDayCheckbox.checked) {
          unavailableTypeId = "4";
        }

        if (unavailableTypeId) {
          unavailableTypeSelect.value = unavailableTypeId;
        }
        unavailableTypeContainer.style.display = "block";

        const messageElement = unavailableTypeContainer.querySelector("small");
        if (messageElement) {
          let statusText = "";
          if (unavailableTypeId === "1") statusText = "Pick Up";
          else if (unavailableTypeId === "2") statusText = "Delivery";
          else if (unavailableTypeId === "3")
            statusText = "Delivery or Pick Up";
          else if (unavailableTypeId === "4") statusText = "Same Day Order";

          messageElement.textContent = `Will be set to: Unavailable ${statusText}`;
        }

        // Update quantity field state
        updateQuantityFieldState();
      }
    });
  }
}

function closeModal() {
  // Reset modal content to prevent showing stale data
  resetModalContent();

  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "auto";

  // Don't manipulate the global days container - it should always be visible
  // The modal has its own days container that's handled separately

  resetFormToOriginal();
}

// Reset modal content to default/empty state
function resetModalContent() {
  // Reset form fields to empty/default values
  document.getElementById("editProductId").value = "";
  document.getElementById("editProductName").value = "";
  document.getElementById("editProductDescription").value = "";
  document.getElementById("editProductPrice").value = "";
  document.getElementById("editProductQuantity").value = "";

  // Reset checkboxes
  const preOrderCheckbox = document.getElementById("editPreOrderCheckbox");
  const sameDayCheckbox = document.getElementById("editSameDayCheckbox");
  if (preOrderCheckbox) preOrderCheckbox.checked = false;
  if (sameDayCheckbox) sameDayCheckbox.checked = false;

  // Hide option divs
  const preOrderOptions = document.getElementById("editPreOrderOptions");
  const sameDayOptions = document.getElementById("editSameDayOptions");
  if (preOrderOptions) preOrderOptions.style.display = "none";
  if (sameDayOptions) sameDayOptions.style.display = "none";

  // Reset dropdowns
  const categorySelect = document.getElementById("editProductCategory");
  const featuredSelect = document.getElementById("editIsFeature");
  const visibilitySelect = document.getElementById("editVisibilityOption");
  if (categorySelect) categorySelect.value = "";
  if (featuredSelect) featuredSelect.value = "0";
  if (visibilitySelect) visibilitySelect.value = "default";

  // Reset availability radio
  const availableRadio = document.getElementById("editAvailable");
  const unavailableRadio = document.getElementById("editUnavailable");
  if (availableRadio) availableRadio.checked = true;
  if (unavailableRadio) unavailableRadio.checked = false;

  // Hide unavailable type container
  const unavailableTypeContainer = document.getElementById(
    "editUnavailableTypeContainer"
  );
  if (unavailableTypeContainer) unavailableTypeContainer.style.display = "none";

  // Reset images
  currentProductImages = { primary: null, additional: [] };
  pendingImageChanges = {
    primary: null,
    additional: { toAdd: [], toRemove: [] },
  };
  tempImageInfo = { primary: null, additional: [] };

  // Clear image containers
  const primaryContainer = document.getElementById("editPrimaryImageContainer");
  const additionalContainer = document.getElementById(
    "editAdditionalImagesContainer"
  );
  if (primaryContainer) {
    primaryContainer.innerHTML = `
      <button type="button" class="upload-btn-overlay" onclick="document.getElementById('editPrimaryImageInput').click()">
        Click to Upload Image
      </button>
      <div class="image-placeholder" id="editPrimaryPlaceholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21,15 16,10 5,21"></polyline>
        </svg>
        <span>No primary image</span>
      </div>
    `;
    primaryContainer.classList.remove("has-image");
  }
  if (additionalContainer) {
    additionalContainer.innerHTML = `
      <button type="button" class="upload-btn-overlay" onclick="document.getElementById('editAdditionalImagesInput').click()">
        Click to Upload Images
      </button>
      <div class="image-placeholder" id="editAdditionalPlaceholder">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21,15 16,10 5,21"></polyline>
        </svg>
        <span>No additional images</span>
      </div>
    `;
    additionalContainer.classList.remove("has-images");
  }

  // Hide calendar containers
  const todaysCalendarContainer = document.getElementById(
    "todaysProductCalendarContainer"
  );
  const availableTodayCalendarContainer = document.getElementById(
    "availableTodayCalendarContainer"
  );
  if (todaysCalendarContainer) todaysCalendarContainer.style.display = "none";
  if (availableTodayCalendarContainer)
    availableTodayCalendarContainer.style.display = "none";
}

// Image management functions
function loadProductImages(productId) {
  currentProductImages = { primary: null, additional: [] };
  pendingImageChanges = {
    primary: null,
    additional: { toAdd: [], toRemove: [] },
  };
  tempImageInfo = { primary: null, additional: [] };

  return fetch(`get-product-images.php?product_id=${productId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        currentProductImages = data.images;
        displayProductImages();
        return true;
      } else {
        console.error("Failed to load images:", data.error);
        showNotification("Failed to load product images", "error");
        return false;
      }
    })
    .catch((error) => {
      console.error("Error loading images:", error);
      showNotification("Error loading product images", "error");
      return false;
    });
}

function displayProductImages() {
  const primaryContainer = document.getElementById("editPrimaryImageContainer");
  const additionalContainer = document.getElementById(
    "editAdditionalImagesContainer"
  );

  if (!primaryContainer || !additionalContainer) {
    console.error("Image containers not found");
    return;
  }

  // Calculate effective images
  let effectivePrimary = currentProductImages.primary;
  let effectiveAdditional = [...(currentProductImages.additional || [])];

  if (pendingImageChanges.primary === "remove") {
    effectivePrimary = null;
  } else if (pendingImageChanges.primary) {
    effectivePrimary = pendingImageChanges.primary;
  }

  effectiveAdditional = effectiveAdditional.filter(
    (img) => !pendingImageChanges.additional.toRemove.includes(img.id)
  );
  effectiveAdditional = [
    ...effectiveAdditional,
    ...pendingImageChanges.additional.toAdd,
  ];

  // Display primary image
  if (effectivePrimary) {
    const imageUrl = effectivePrimary.cloud_url
      ? effectivePrimary.cloud_url
      : effectivePrimary.is_temp
      ? `/${effectivePrimary.image_url}`
      : effectivePrimary.image_url.startsWith("http://") ||
        effectivePrimary.image_url.startsWith("https://")
      ? effectivePrimary.image_url
      : effectivePrimary.image_url.startsWith("assets/")
      ? `/${effectivePrimary.image_url}`
      : `/assets/${effectivePrimary.image_url}`;

    primaryContainer.innerHTML = `
      <div class="image-preview">
        <img src="${imageUrl}" alt="Primary image">
        <button class="remove-image" onclick="removePrimaryImage()">×</button>
      </div>
    `;
    primaryContainer.classList.add("has-image");
  } else {
    primaryContainer.innerHTML = `
      <button type="button" class="upload-btn-overlay" onclick="document.getElementById('editPrimaryImageInput').click()">
        Click to Upload Image
      </button>
      <div class="image-placeholder" id="editPrimaryPlaceholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21,15 16,10 5,21"></polyline>
        </svg>
        <span>No primary image</span>
      </div>
    `;
    primaryContainer.classList.remove("has-image");
  }

  // Display additional images
  if (effectiveAdditional && effectiveAdditional.length > 0) {
    const imagesGrid = document.createElement("div");
    imagesGrid.className = "additional-images-grid";

    effectiveAdditional.forEach((image, index) => {
      const imageUrl = image.cloud_url
        ? image.cloud_url
        : image.is_temp
        ? `/${image.image_url}`
        : image.image_url.startsWith("http://") ||
          image.image_url.startsWith("https://")
        ? image.image_url
        : image.image_url.startsWith("assets/")
        ? `/${image.image_url}`
        : `/assets/${image.image_url}`;

      const imagePreview = document.createElement("div");
      imagePreview.className = "image-preview";

      if (image.is_temp) {
        imagePreview.innerHTML = `
          <img src="${imageUrl}" alt="Additional image ${index + 1}">
          <button class="remove-image" data-temp-id="${image.id}">×</button>
        `;
        const removeButton = imagePreview.querySelector(".remove-image");
        removeButton.addEventListener("click", () =>
          removeAdditionalImage(image.id)
        );
      } else {
        imagePreview.innerHTML = `
          <img src="${imageUrl}" alt="Additional image ${index + 1}">
          <button class="remove-image" onclick="removeAdditionalImage(${
            image.id
          })">×</button>
        `;
      }

      imagesGrid.appendChild(imagePreview);
    });

    // Always add upload button if less than 3 images
    if (effectiveAdditional.length < 3) {
      const uploadButton = document.createElement("div");
      uploadButton.className = "additional-upload-button";
      uploadButton.innerHTML = "+";
      uploadButton.onclick = () =>
        document.getElementById("editAdditionalImagesInput").click();
      imagesGrid.appendChild(uploadButton);
    }

    additionalContainer.innerHTML = "";
    additionalContainer.appendChild(imagesGrid);
    additionalContainer.classList.add("has-images");
  } else {
    // Reset to original structure with upload button and placeholder
    additionalContainer.innerHTML = `
    <button type="button" class="upload-btn-overlay" onclick="document.getElementById('editAdditionalImagesInput').click()">
      Click to Upload Images
    </button>
    <div class="image-placeholder" id="editAdditionalPlaceholder">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <circle cx="8.5" cy="8.5" r="1.5"></circle>
        <polyline points="21,15 16,10 5,21"></polyline>
      </svg>
      <span>No additional images</span>
    </div>
  `;
    additionalContainer.classList.remove("has-images");
  }
}

function updateAdditionalImagesButtonState() {
  const additionalContainer = document.getElementById(
    "editAdditionalImagesContainer"
  );
  const uploadOverlay = additionalContainer?.querySelector(
    ".upload-btn-overlay"
  );

  if (uploadOverlay) {
    let effectiveAdditional = [...(currentProductImages.additional || [])];
    effectiveAdditional = effectiveAdditional.filter(
      (img) => !pendingImageChanges.additional.toRemove.includes(img.id)
    );
    effectiveAdditional = [
      ...effectiveAdditional,
      ...pendingImageChanges.additional.toAdd,
    ];

    const currentCount = effectiveAdditional.length;

    if (currentCount >= 3) {
      uploadOverlay.disabled = true;
      uploadOverlay.style.opacity = "0.5";
      uploadOverlay.style.cursor = "not-allowed";
      uploadOverlay.textContent = "Maximum 3 images reached";
    } else {
      uploadOverlay.disabled = false;
      uploadOverlay.style.opacity = "1";
      uploadOverlay.style.cursor = "pointer";
      uploadOverlay.textContent = `Click to Upload Images (${
        3 - currentCount
      } remaining)`;
    }
  }
}

function setupImageUploadListeners() {
  const primaryImageInput = document.getElementById("editPrimaryImageInput");
  if (primaryImageInput) {
    primaryImageInput.addEventListener("change", function (e) {
      if (this.files && this.files[0]) {
        uploadPrimaryImage(this.files[0]);
      }
    });
  }

  const additionalImagesInput = document.getElementById(
    "editAdditionalImagesInput"
  );
  if (additionalImagesInput) {
    additionalImagesInput.addEventListener("change", function (e) {
      if (this.files && this.files.length > 0) {
        let effectiveAdditional = [...(currentProductImages.additional || [])];
        effectiveAdditional = effectiveAdditional.filter(
          (img) => !pendingImageChanges.additional.toRemove.includes(img.id)
        );
        effectiveAdditional = [
          ...effectiveAdditional,
          ...pendingImageChanges.additional.toAdd,
        ];

        const currentCount = effectiveAdditional.length;
        const filesToAdd = this.files.length;

        if (currentCount + filesToAdd > 3) {
          showCustomConfirm(
            "Image Limit Exceeded",
            `3 images only can be posted for additional images. You currently have ${currentCount} images and are trying to add ${filesToAdd} more. Please remove some existing images first.`,
            () => {
              this.value = "";
            }
          );
          return;
        }

        Array.from(this.files).forEach((file) => {
          uploadAdditionalImage(file);
        });
      }
    });
  }

  updateAdditionalImagesButtonState();
}

function uploadPrimaryImage(file) {
  let effectivePrimary = currentProductImages.primary;
  if (pendingImageChanges.primary === "remove") {
    effectivePrimary = null;
  } else if (pendingImageChanges.primary) {
    effectivePrimary = pendingImageChanges.primary;
  }

  if (effectivePrimary) {
    showCustomConfirm(
      "Image Limit Exceeded",
      "1 image only can be posted for primary image. Please remove the existing one first.",
      () => {}
    );
    return;
  }

  const productId = document.getElementById("editProductId").value;
  const productName =
    document.getElementById("editProductName").value || "Product";

  // Get CSRF token
  const csrfToken = document.getElementById("csrf_token")?.value;
  if (!csrfToken) {
    showNotification(
      "Security token missing. Please refresh the page.",
      "error"
    );
    return;
  }

  const formData = new FormData();
  formData.append("image", file);
  formData.append("product_id", productId);
  formData.append("product_name", productName);
  formData.append("image_type", "primary");
  formData.append("csrf_token", csrfToken);

  // Show loading state
  showNotification("Uploading primary image...", "info");

  fetch("/backend/api/upload-product-image.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tempId = "temp_" + Date.now();
        const tempImage = {
          id: tempId,
          image_url: data.url,
          cloud_url: data.url,
          cloud_public_id: data.public_id,
          is_temp: true,
        };

        tempImageInfo.primary = {
          id: tempId,
          url: data.url,
          public_id: data.public_id,
        };
        pendingImageChanges.primary = tempImage;
        displayProductImages();
        showNotification("Primary image uploaded successfully", "success");
      } else {
        showNotification("Error uploading image: " + data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error uploading primary image:", error);
      showNotification(
        "Error uploading primary image: " + error.message,
        "error"
      );
    });
}

function uploadAdditionalImage(file) {
  let effectiveAdditional = [...(currentProductImages.additional || [])];
  effectiveAdditional = effectiveAdditional.filter(
    (img) => !pendingImageChanges.additional.toRemove.includes(img.id)
  );
  effectiveAdditional = [
    ...effectiveAdditional,
    ...pendingImageChanges.additional.toAdd,
  ];

  if (effectiveAdditional.length >= 3) {
    showCustomConfirm(
      "Image Limit Exceeded",
      "3 images only can be posted for additional images. Please remove some existing images first.",
      () => {}
    );
    return;
  }

  const productId = document.getElementById("editProductId").value;
  const productName =
    document.getElementById("editProductName").value || "Product";

  // Get CSRF token
  const csrfToken = document.getElementById("csrf_token")?.value;
  if (!csrfToken) {
    showNotification(
      "Security token missing. Please refresh the page.",
      "error"
    );
    return;
  }

  const formData = new FormData();
  formData.append("image", file);
  formData.append("product_id", productId);
  formData.append("product_name", productName);
  formData.append("image_type", "additional");
  formData.append("csrf_token", csrfToken);

  // Show loading state
  showNotification("Uploading additional image...", "info");

  fetch("/backend/api/upload-product-image.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tempId = "temp_" + Date.now();
        const tempImage = {
          id: tempId,
          image_url: data.url,
          cloud_url: data.url,
          cloud_public_id: data.public_id,
          is_temp: true,
        };

        tempImageInfo.additional.push({
          id: tempId,
          url: data.url,
          public_id: data.public_id,
        });
        pendingImageChanges.additional.toAdd.push(tempImage);
        displayProductImages();
        showNotification("Additional image uploaded successfully", "success");
      } else {
        showNotification("Error uploading image: " + data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error uploading additional image:", error);
      showNotification("Error uploading additional image", "error");
    });
}

function removePrimaryImage() {
  if (currentProductImages.primary) {
    showCustomConfirm(
      "Remove Primary Image",
      "Are you sure you want to remove the primary image?",
      () => {
        const productId = document.getElementById("editProductId").value;
        const formData = new FormData();
        formData.append("image_id", currentProductImages.primary.id);
        formData.append("product_id", productId);

        fetch("remove-individual-image.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              pendingImageChanges.primary = "remove";
              displayProductImages();

              // Show appropriate message based on whether file was missing
              if (data.file_missing) {
                showNotification(
                  "Primary image removed from database (file was already missing)",
                  "success"
                );
              } else {
                showNotification(
                  "Primary image moved to temporary storage (pending save)",
                  "success"
                );
              }
            } else {
              showNotification(
                "Error removing primary image: " + data.error,
                "error"
              );
            }
          })
          .catch((error) => {
            console.error("Error removing primary image:", error);
            showNotification("Error removing primary image", "error");
          });
      }
    );
  }
}

function removeAdditionalImage(imageId) {
  showCustomConfirm(
    "Remove Image",
    "Are you sure you want to remove this image?",
    () => {
      if (typeof imageId === "string" && imageId.startsWith("temp_")) {
        pendingImageChanges.additional.toAdd =
          pendingImageChanges.additional.toAdd.filter(
            (img) => img.id !== imageId
          );
        tempImageInfo.additional = tempImageInfo.additional.filter(
          (temp) => temp.id !== imageId
        );
        displayProductImages();
        showNotification("Image removed from pending additions", "success");
      } else {
        const productId = document.getElementById("editProductId").value;
        const formData = new FormData();
        formData.append("image_id", imageId);
        formData.append("product_id", productId);

        fetch("remove-individual-image.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              pendingImageChanges.additional.toRemove.push(imageId);
              displayProductImages();

              // Show appropriate message based on whether file was missing
              if (data.file_missing) {
                showNotification(
                  "Image removed from database (file was already missing)",
                  "success"
                );
              } else {
                showNotification(
                  "Image moved to temporary storage (pending save)",
                  "success"
                );
              }
            } else {
              showNotification("Error removing image: " + data.error, "error");
            }
          })
          .catch((error) => {
            console.error("Error removing image:", error);
            showNotification("Error removing image", "error");
          });
      }
    }
  );
}

// Checkbox event handlers for new UI
function handlePreOrderCheckboxChange() {
  const checkbox = document.getElementById("editPreOrderCheckbox");
  const optionsDiv = document.getElementById("editPreOrderOptions");
  const dropdown = document.getElementById("editPreOrderStatus");
  const sameDayCheckbox = document.getElementById("editSameDayCheckbox");

  if (checkbox.checked) {
    optionsDiv.style.display = "block";
    // Set default value if not already set
    if (!dropdown.value) {
      dropdown.value = "1"; // Default to Pick Up
    }

    // If same-day is also checked, switch to availableTodayCalendar
    if (sameDayCheckbox && sameDayCheckbox.checked) {
      const todaysCalendarContainer = document.getElementById(
        "todaysProductCalendarContainer"
      );
      const availableTodayCalendarContainer = document.getElementById(
        "availableTodayCalendarContainer"
      );
      if (todaysCalendarContainer)
        todaysCalendarContainer.style.display = "none";
      if (availableTodayCalendarContainer)
        availableTodayCalendarContainer.style.display = "block";
    }
  } else {
    optionsDiv.style.display = "none";

    // If same-day is checked, switch to todaysProductCalendar
    if (sameDayCheckbox && sameDayCheckbox.checked) {
      const todaysCalendarContainer = document.getElementById(
        "todaysProductCalendarContainer"
      );
      const availableTodayCalendarContainer = document.getElementById(
        "availableTodayCalendarContainer"
      );
      if (todaysCalendarContainer)
        todaysCalendarContainer.style.display = "block";
      if (availableTodayCalendarContainer)
        availableTodayCalendarContainer.style.display = "none";
    }
  }

  // Update quantity field state
  updateQuantityFieldState();
}

function handleSameDayCheckboxChange() {
  const checkbox = document.getElementById("editSameDayCheckbox");
  const optionsDiv = document.getElementById("editSameDayOptions");
  const dropdown = document.getElementById("editSameDayStatus");
  const preOrderCheckbox = document.getElementById("editPreOrderCheckbox");

  // Determine which calendar to show based on whether pre-order is also checked
  const todaysCalendarContainer = document.getElementById(
    "todaysProductCalendarContainer"
  );
  const availableTodayCalendarContainer = document.getElementById(
    "availableTodayCalendarContainer"
  );

  if (checkbox.checked) {
    optionsDiv.style.display = "block";

    // Show appropriate calendar based on pre-order checkbox state
    if (preOrderCheckbox && preOrderCheckbox.checked) {
      // Both pre-order and same-day: use availableTodayCalendar
      if (availableTodayCalendarContainer)
        availableTodayCalendarContainer.style.display = "block";
      if (todaysCalendarContainer)
        todaysCalendarContainer.style.display = "none";
    } else {
      // Only same-day: use todaysProductCalendar
      if (todaysCalendarContainer)
        todaysCalendarContainer.style.display = "block";
      if (availableTodayCalendarContainer)
        availableTodayCalendarContainer.style.display = "none";
    }

    // Set default value if not already set
    if (!dropdown.value) {
      dropdown.value = "1"; // Default to Pick Up
    }
    // Initialize calendar if needed
    if (window.modalCalendarHandler) {
      window.modalCalendarHandler.initializeEditModalCalendars();
    }
  } else {
    optionsDiv.style.display = "none";
    if (todaysCalendarContainer) todaysCalendarContainer.style.display = "none";
    if (availableTodayCalendarContainer)
      availableTodayCalendarContainer.style.display = "none";
  }

  // Update quantity field state
  updateQuantityFieldState();
}

function updateQuantityFieldState() {
  const preOrderChecked = document.getElementById(
    "editPreOrderCheckbox"
  ).checked;
  const sameDayChecked = document.getElementById("editSameDayCheckbox").checked;
  const quantityField = document.getElementById("editProductQuantity");
  const unavailableRadio = document.getElementById("editUnavailable");

  // Disable quantity if:
  // 1. Product is unavailable, OR
  // 2. Only same-day is checked (not pre-order)
  if (unavailableRadio && unavailableRadio.checked) {
    quantityField.value = "0";
    quantityField.disabled = true;
    quantityField.style.opacity = "0.5";
    quantityField.style.cursor = "not-allowed";
  } else if (sameDayChecked && !preOrderChecked) {
    quantityField.value = "0";
    quantityField.disabled = true;
    quantityField.style.opacity = "0.5";
    quantityField.style.cursor = "not-allowed";
  } else {
    quantityField.disabled = false;
    quantityField.style.opacity = "1";
    quantityField.style.cursor = "text";
  }
}

function validateCheckboxSelection() {
  const preOrderChecked = document.getElementById(
    "editPreOrderCheckbox"
  ).checked;
  const sameDayChecked = document.getElementById("editSameDayCheckbox").checked;

  if (!preOrderChecked && !sameDayChecked) {
    showNotification(
      "Please select at least one order type (Pre-order or Same-day order)",
      "error"
    );
    return false;
  }
  return true;
}

function handleFormSubmit(event) {
  event.preventDefault();

  // Validate checkbox selection first
  if (!validateCheckboxSelection()) {
    return;
  }

  // Show saving overlay
  showModalSaving(true);

  const preOrderChecked = document.getElementById(
    "editPreOrderCheckbox"
  ).checked;
  const sameDayChecked = document.getElementById("editSameDayCheckbox").checked;

  let statusId = null;
  let availtodayStatusId = null;

  if (preOrderChecked && sameDayChecked) {
    // Both checked: status_id = pre-order value, availtoday_status_id = same-day value
    statusId = document.getElementById("editPreOrderStatus").value;
    availtodayStatusId = document.getElementById("editSameDayStatus").value;
  } else if (preOrderChecked) {
    // Only pre-order: status_id = pre-order value, availtoday_status_id = NULL
    statusId = document.getElementById("editPreOrderStatus").value;
    availtodayStatusId = null;
  } else if (sameDayChecked) {
    // Only same-day: status_id = 4, availtoday_status_id = same-day value
    statusId = 4;
    availtodayStatusId = document.getElementById("editSameDayStatus").value;
  }

  console.log("DEBUG: Checkbox-based status_id:", statusId);
  console.log(
    "DEBUG: Checkbox-based availtoday_status_id:",
    availtodayStatusId
  );

  // Validate same-day dropdown when same-day checkbox is checked
  if (sameDayChecked) {
    const sameDayDropdown = document.getElementById("editSameDayStatus");
    if (!sameDayDropdown || !sameDayDropdown.value) {
      showNotification(
        "Please select a shipping method for same-day order.",
        "error"
      );
      return;
    }
  }

  // Collect available days from GLOBAL checkboxes (for pre-order products)
  const availableDays = [];
  if (preOrderChecked) {
    const globalDayCheckboxes = {
      global_sunday: "Sunday",
      global_monday: "Monday",
      global_tuesday: "Tuesday",
      global_wednesday: "Wednesday",
      global_thursday: "Thursday",
      global_friday: "Friday",
      global_saturday: "Saturday",
    };

    Object.keys(globalDayCheckboxes).forEach((checkboxId) => {
      const checkbox = document.getElementById(checkboxId);
      if (checkbox && checkbox.checked) {
        availableDays.push(globalDayCheckboxes[checkboxId]);
      }
    });
  }

  const isAvailable = document.getElementById("editAvailable").checked;
  const unavailableTypeId =
    document.getElementById("editUnavailableType").value || null;

  // Get calendar data based on checkbox states
  let sameDayDates = [];
  if (sameDayChecked) {
    if (preOrderChecked) {
      // Both checked: use availableTodayDates
      const availableTodayDatesInput = document.getElementById(
        "availableTodayDates"
      );
      sameDayDates = availableTodayDatesInput
        ? availableTodayDatesInput.value.split(",").filter((d) => d.trim())
        : [];
    } else {
      // Only same-day: use todaysProductDates
      const todaysProductDatesInput =
        document.getElementById("todaysProductDates");
      sameDayDates = todaysProductDatesInput
        ? todaysProductDatesInput.value.split(",").filter((d) => d.trim())
        : [];
    }
  }

  // Collect SDO quantities BEFORE creating formData (while modal is still open)
  const sdoQuantitiesData = {};
  const sdoInputs = document.querySelectorAll(".sdo-quantity-input[data-date]");
  sdoInputs.forEach((input) => {
    const date = input.getAttribute("data-date");
    const quantity = parseInt(input.value) || 0;
    sdoQuantitiesData[date] = quantity;
  });
  console.log("DEBUG: Collected SDO quantities:", sdoQuantitiesData);

  const formData = {
    id: document.getElementById("editProductId").value,
    name: document.getElementById("editProductName").value,
    description: document.getElementById("editProductDescription").value,
    price: document.getElementById("editProductPrice").value,
    quantity: document.getElementById("editProductQuantity").value,
    status_id: statusId,
    category_id: document.getElementById("editProductCategory").value || null,
    is_featured: document.getElementById("editIsFeature").value === "1",
    show_when_unavailable:
      document.getElementById("editVisibilityOption").value === "show",
    hide_when_unavailable:
      document.getElementById("editVisibilityOption").value === "hide",
    available_days: availableDays,
    is_available: isAvailable,
    unavailable_status_id: isAvailable ? null : unavailableTypeId,
    availtoday_status_id: availtodayStatusId,
    // NEW: Send is_available_today flag to backend
    is_available_today: preOrderChecked && sameDayChecked,
    // Send dates in the appropriate field based on product type
    todays_product_dates:
      statusId == 4 ? JSON.stringify(sameDayDates) : JSON.stringify([]),
    available_today_dates:
      statusId != 4 && availtodayStatusId
        ? JSON.stringify(sameDayDates)
        : JSON.stringify([]),
    // Add SDO quantities to formData
    sdo_quantities: JSON.stringify(sdoQuantitiesData),
    pending_image_changes: pendingImageChanges,
  };

  console.log("DEBUG: Final formData being sent:", formData);

  const submitBtn = document.querySelector(
    'button[type="submit"][form="editProductForm"]'
  );
  const originalText = submitBtn ? submitBtn.textContent : null;
  if (submitBtn) {
    submitBtn.textContent = "Saving...";
    submitBtn.disabled = true;
  }

  // Images are already uploaded to Cloudinary via AJAX
  // We just need to include the image metadata in the formData

  // Add primary image metadata if uploaded
  if (tempImageInfo.primary) {
    formData.primary_image_url = tempImageInfo.primary.url;
    formData.primary_image_public_id = tempImageInfo.primary.public_id;
  } else if (pendingImageChanges.primary === "remove") {
    formData.remove_primary_image = true;
  }

  // Add additional images metadata if uploaded
  if (tempImageInfo.additional && tempImageInfo.additional.length > 0) {
    formData.additional_image_urls = JSON.stringify(
      tempImageInfo.additional.map((img) => img.url)
    );
    formData.additional_image_public_ids = JSON.stringify(
      tempImageInfo.additional.map((img) => img.public_id)
    );
  }

  // Add images to remove
  if (pendingImageChanges.additional.toRemove.length > 0) {
    formData.remove_additional_image_ids = JSON.stringify(
      pendingImageChanges.additional.toRemove
    );
  }

  // No need for uploadPromises anymore - images are already on Cloudinary
  const uploadPromises = [];

  Promise.all(uploadPromises)
    .then((uploadResults) => {
      const failedUploads = uploadResults.filter((result) => !result.success);
      if (failedUploads.length > 0) {
        throw new Error(
          "Some image uploads failed: " +
            failedUploads.map((r) => r.error).join(", ")
        );
      }

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
      return response.text().then((text) => {
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
        // SDO quantities are now saved together with the product in update-product.php
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
      // Hide saving overlay
      showModalSaving(false);

      if (submitBtn && originalText) {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });
}

function softDeleteProduct(id) {
  showDeleteModal(id);
}

// Delete Modal Functions
function showDeleteModal(productId) {
  const modal = document.getElementById("deleteModal");
  if (modal) {
    modal.style.display = "flex";
    modal.setAttribute("data-product-id", productId);
  }
}

function hideDeleteModal() {
  const modal = document.getElementById("deleteModal");
  if (modal) {
    modal.style.display = "none";
    modal.removeAttribute("data-product-id");
  }
}

function confirmDelete() {
  const modal = document.getElementById("deleteModal");
  const productId = modal.getAttribute("data-product-id");

  if (!productId) return;

  // Hide modal first
  hideDeleteModal();

  // Show loading notification
  showNotification("Deleting product...", "info");

  fetch("/backend/pages/products/delete-product.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${productId}`,
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

function updateFilterCounts() {
  const counts = {
    all: allProductsData.length,
    "Pick Up": 0,
    Delivery: 0,
    featured: 0,
    Unavailable: 0,
    "Same Day Order": 0,
  };

  allProductsData.forEach((product) => {
    let status = product.status_name;
    // Map legacy term to new label for counting
    if (status === "Available Today") status = "Same Day Order";
    if (counts.hasOwnProperty(status)) counts[status]++;
    if (product.is_featured == 1) {
      counts.featured++;
    }
    if (product.unavailable_status_id !== null) {
      counts["Unavailable"]++;
    }
  });

  document.getElementById("count-all").textContent = counts.all;
  document.getElementById("count-pickup").textContent = counts["Pick Up"];
  document.getElementById("count-delivery").textContent = counts["Delivery"];
  document.getElementById("count-featured").textContent = counts.featured;
  document.getElementById("count-unavailable").textContent =
    counts["Unavailable"];
  document.getElementById("count-available-today").textContent =
    counts["Same Day Order"];
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.style.cssText = `
    position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 0.5rem;
    color: white; font-weight: 500; z-index: 9999; transform: translateX(100%);
    transition: transform 0.3s ease;
    ${
      type === "success"
        ? "background-color: var(--green-600);"
        : "background-color: var(--red-600);"
    }
  `;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 100);
  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    setTimeout(() => {
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
}

function showCustomConfirm(title, message, onConfirm) {
  const existingDialog = document.getElementById("customConfirmDialog");
  if (existingDialog) {
    document.body.removeChild(existingDialog);
  }

  const overlay = document.createElement("div");
  overlay.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.5); z-index: 10000;
    display: flex; align-items: center; justify-content: center;
  `;

  const dialog = document.createElement("div");
  dialog.id = "customConfirmDialog";
  dialog.style.cssText = `
    background: white; border-radius: 8px; padding: 24px; max-width: 400px; width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); animation: slideIn 0.3s ease;
  `;

  const style = document.createElement("style");
  style.textContent = `
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  `;
  document.head.appendChild(style);

  dialog.innerHTML = `
    <h3 style="margin: 0 0 16px 0; color: #333; font-size: 18px;">${title}</h3>
    <p style="margin: 0 0 24px 0; color: #666; line-height: 1.5;">${message}</p>
    <div style="display: flex; gap: 12px; justify-content: flex-end;">
      <button id="cancelBtn" style="padding: 8px 16px; border: 1px solid #ddd; background: white; color: #666; border-radius: 4px; cursor: pointer; font-size: 14px;">Cancel</button>
      <button id="confirmBtn" style="padding: 8px 16px; border: none; background: #dc3545; color: white; border-radius: 4px; cursor: pointer; font-size: 14px;">Remove</button>
    </div>
  `;

  overlay.appendChild(dialog);
  document.body.appendChild(overlay);

  const cancelBtn = document.getElementById("cancelBtn");
  const confirmBtn = document.getElementById("confirmBtn");

  const closeDialog = () => {
    document.body.removeChild(overlay);
    document.head.removeChild(style);
  };

  cancelBtn.addEventListener("click", closeDialog);
  confirmBtn.addEventListener("click", () => {
    closeDialog();
    onConfirm();
  });

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      closeDialog();
    }
  });

  const handleEscape = (e) => {
    if (e.key === "Escape") {
      closeDialog();
      document.removeEventListener("keydown", handleEscape);
    }
  };
  document.addEventListener("keydown", handleEscape);
}

function resetFormToOriginal() {
  if (Object.keys(originalFormData).length === 0) {
    return;
  }

  // Reset form fields
  document.getElementById("editProductName").value =
    originalFormData.name || "";
  document.getElementById("editProductPrice").value =
    originalFormData.price || "";
  document.getElementById("editProductQuantity").value =
    originalFormData.quantity || "";

  // Reset checkboxes
  const preOrderCheckbox = document.getElementById("editPreOrderCheckbox");
  const sameDayCheckbox = document.getElementById("editSameDayCheckbox");
  const preOrderOptions = document.getElementById("editPreOrderOptions");
  const sameDayOptions = document.getElementById("editSameDayOptions");

  if (preOrderCheckbox) {
    preOrderCheckbox.checked = false;
    if (preOrderOptions) preOrderOptions.style.display = "none";
  }

  if (sameDayCheckbox) {
    sameDayCheckbox.checked = false;
    if (sameDayOptions) sameDayOptions.style.display = "none";
  }

  // Hide calendars
  const todaysCalendarContainer = document.getElementById(
    "todaysProductCalendarContainer"
  );
  const availableTodayCalendarContainer = document.getElementById(
    "availableTodayCalendarContainer"
  );
  if (todaysCalendarContainer) todaysCalendarContainer.style.display = "none";
  if (availableTodayCalendarContainer)
    availableTodayCalendarContainer.style.display = "none";

  // Reset availtoday_status dropdown (keeping for backward compatibility)
  const availtodayOptions = document.getElementById("editAvailtodayOptions");
  const availtodaySelect = document.getElementById("editAvailtodayStatus");
  if (availtodayOptions && availtodaySelect) {
    if (originalFormData.status == 3) {
      // Available Today
      availtodayOptions.style.display = "block";
      availtodaySelect.value = originalFormData.availtodayStatusId || "";
      if (availtodaySelect.value === "null") {
        availtodaySelect.value = "";
      }
    } else {
      availtodayOptions.style.display = "none";
      availtodaySelect.value = "";
    }
  }

  const isFeatureBool =
    originalFormData.isFeature === true ||
    originalFormData.isFeature === "true" ||
    originalFormData.isFeature === 1 ||
    originalFormData.isFeature === "1";
  const featuredSelect = document.getElementById("editIsFeature");
  if (featuredSelect) {
    featuredSelect.value = isFeatureBool ? "1" : "0";
  }

  // Set visibility option
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

  // Reset checkboxes
  const availableDaysArray = originalFormData.availableDays
    ? originalFormData.availableDays.split(", ")
    : [];
  const dayCheckboxes = {
    edit_sunday: "Sunday",
    edit_monday: "Monday",
    edit_tuesday: "Tuesday",
    edit_wednesday: "Wednesday",
    edit_thursday: "Thursday",
    edit_friday: "Friday",
    edit_saturday: "Saturday",
  };

  Object.keys(dayCheckboxes).forEach((checkboxId) => {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
      checkbox.checked = false;
    }
  });

  availableDaysArray.forEach((day) => {
    const checkboxId = Object.keys(dayCheckboxes).find(
      (key) => dayCheckboxes[key] === day
    );
    if (checkboxId) {
      const checkbox = document.getElementById(checkboxId);
      if (checkbox) {
        checkbox.checked = true;
      }
    }
  });

  // Don't manipulate the days-group display on modal close
  // The visibility should be controlled by the status change event, not on reset
  // This prevents the global filter days-group from being hidden when modal closes

  // Reset isAvailableToday radio button and related elements
  const isAvailableTodayContainer = document.getElementById(
    "isAvailableTodayContainer"
  );
  const isAvailableTodayRadio = document.getElementById("isAvailableToday");
  const availableTodayDaysContainer = document.getElementById(
    "availableTodayDaysContainer"
  );

  if (isAvailableTodayContainer && isAvailableTodayRadio) {
    if (
      originalFormData.statusName === "Pick Up" ||
      originalFormData.statusName === "Delivery"
    ) {
      isAvailableTodayContainer.style.display = "block";
    } else {
      isAvailableTodayContainer.style.display = "none";
    }
    isAvailableTodayRadio.checked = false;
  }

  if (availableTodayDaysContainer) {
    availableTodayDaysContainer.style.display = "none";
    // Reset all checkboxes in checkbox-group2
    const todayCheckboxes = availableTodayDaysContainer.querySelectorAll(
      'input[type="checkbox"]'
    );
    todayCheckboxes.forEach((checkbox) => {
      checkbox.checked = false;
    });
  }

  // Reset image changes
  pendingImageChanges = {
    primary: null,
    additional: { toAdd: [], toRemove: [] },
  };
  tempImageInfo = { primary: null, additional: [] };

  // Cleanup temp images
  if (originalFormData.id) {
    const cleanupFormData = new FormData();
    cleanupFormData.append("product_id", originalFormData.id);

    fetch("cleanup-temp-images.php", { method: "POST", body: cleanupFormData })
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          console.error("Failed to cleanup temp images:", data.error);
        }
      })
      .catch((error) => {
        console.error("Error cleaning up temp images:", error);
      });

    // Note: restore-removed-images.php is deprecated (Cloudinary is used now)
    // Skip calling it to avoid unnecessary errors
    // const restoreFormData = new FormData();
    // restoreFormData.append("product_id", originalFormData.id);
    // fetch("restore-removed-images.php", { method: "POST", body: restoreFormData })

    loadProductImages(originalFormData.id);
  }

  originalFormData = {};
}

// Delete Modal Event Listeners
document.addEventListener("DOMContentLoaded", function () {
  // Close delete modal when clicking outside of it
  window.addEventListener("click", function (event) {
    const modal = document.getElementById("deleteModal");
    if (modal && event.target === modal) {
      hideDeleteModal();
    }
  });

  // Close delete modal with Escape key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      const modal = document.getElementById("deleteModal");
      if (modal && modal.style.display === "flex") {
        hideDeleteModal();
      }
    }
  });
});

// Global Available Days functionality
function updateGlobalAvailableDays() {
  // This function is called when checkboxes change
  // We don't need to do anything here, just let the checkboxes update
}

function applyGlobalAvailableDays() {
  const checkboxes = document.querySelectorAll(
    'input[name="global_available_days[]"]:checked'
  );
  const selectedDays = Array.from(checkboxes).map((cb) => cb.value);

  if (selectedDays.length === 0) {
    alert("Please select at least one day before applying.");
    return;
  }

  if (
    !confirm(
      `This will update available days for all Pick Up, Delivery, and Delivery or Pick Up products to: ${selectedDays.join(
        ", "
      )}.\n\nAre you sure you want to continue?`
    )
  ) {
    return;
  }

  // Show loading state
  const button = event.target;
  const originalText = button.textContent;
  button.textContent = "Applying...";
  button.disabled = true;

  // Send AJAX request
  fetch("/backend/pages/products/update-global-available-days.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      days: selectedDays,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert(`Success! Updated ${data.updated_count} products.`);
        // Reload the page to show updated data
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while updating available days.");
    })
    .finally(() => {
      button.textContent = originalText;
      button.disabled = false;
    });
}

// Global Available Days Functions
function updateGlobalAvailableDays() {
  // This function is called when checkboxes change
  // We don't need to do anything here - just for future use
}

function applyGlobalAvailableDays() {
  // Collect selected days
  const selectedDays = [];
  const dayCheckboxes = {
    global_sunday: "Sunday",
    global_monday: "Monday",
    global_tuesday: "Tuesday",
    global_wednesday: "Wednesday",
    global_thursday: "Thursday",
    global_friday: "Friday",
    global_saturday: "Saturday",
  };

  Object.keys(dayCheckboxes).forEach((checkboxId) => {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox && checkbox.checked) {
      selectedDays.push(dayCheckboxes[checkboxId]);
    }
  });

  if (selectedDays.length === 0) {
    showNotification("Please select at least one day", "error");
    return;
  }

  // Confirm action
  if (
    !confirm(
      `This will apply the selected days (${selectedDays.join(
        ", "
      )}) to all products with Pick Up, Delivery, or Delivery or Pick Up status. Continue?`
    )
  ) {
    return;
  }

  // Show saving notification
  showNotification("Applying days to products...", "info");

  // Send request to apply days
  fetch("apply-global-days.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ days: selectedDays }),
  })
    .then((response) => {
      if (!response.ok) {
        return response.text().then((text) => {
          console.error("Server error response:", text);
          throw new Error(`Server error: ${response.status}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showNotification(
          `Successfully applied to ${data.updated_count} products!`,
          "success"
        );
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification("Error: " + data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "An error occurred while applying days: " + error.message,
        "error"
      );
    });
}

// Toggle Preorder Days Settings
function togglePreorderDaysSettings() {
  const toggleButton = document.getElementById("preorderDaysToggle");
  const content = document.getElementById("preorderDaysContent");

  if (content.style.display === "none" || content.style.display === "") {
    content.style.display = "block";
    toggleButton.classList.add("expanded");
  } else {
    content.style.display = "none";
    toggleButton.classList.remove("expanded");
  }
}

// Update the current selection text when checkboxes change
function updateGlobalAvailableDays() {
  const checkboxes = document.querySelectorAll(
    'input[name="global_available_days[]"]'
  );
  const selectedDays = [];

  checkboxes.forEach((checkbox) => {
    if (checkbox.checked) {
      selectedDays.push(checkbox.value);
    }
  });

  // Update the current selection display in the toggle button
  const currentSelectionSpan = document.querySelector(
    ".preorder-days-toggle .current-selection"
  );
  if (currentSelectionSpan) {
    if (selectedDays.length > 0) {
      currentSelectionSpan.textContent = `(${selectedDays.length} days selected)`;
    } else {
      currentSelectionSpan.textContent = "(No days selected)";
    }
  }

  // Update the current selection display in the content
  const currentSelectionP = document.querySelector(
    ".preorder-days-content p strong"
  );
  if (currentSelectionP && currentSelectionP.parentNode) {
    currentSelectionP.parentNode.innerHTML =
      "<strong>Current Selection:</strong> " +
      (selectedDays.length > 0 ? selectedDays.join(", ") : "None selected");
  }
}

/**
 * Save product changes via AJAX without page refresh
 */
async function saveProductChanges(event) {
  console.log("=== SAVE PRODUCT CHANGES CALLED ===");

  // Prevent form submission if event is provided
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  console.log("saveProductChanges called");

  const form = document.getElementById("editProductForm");
  const productId = document.getElementById("editProductId").value;
  const saveButton = document.querySelector(".modal-footer .btn-primary");

  if (!saveButton) {
    console.error("Save button not found");
    return false;
  }

  // Show modal saving overlay
  showModalSaving(true);

  // Disable save button and show loading state
  const originalButtonText = saveButton.innerHTML;
  saveButton.disabled = true;
  saveButton.innerHTML =
    '<svg class="spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg> Saving...';

  try {
    // Collect form data
    const formData = new FormData();
    formData.append("csrf_token", document.getElementById("csrf_token").value);
    formData.append("product_id", productId);
    formData.append("name", document.getElementById("editProductName").value);
    formData.append(
      "description",
      document.getElementById("editProductDescription").value
    );
    formData.append("price", document.getElementById("editProductPrice").value);
    formData.append(
      "quantity",
      document.getElementById("editProductQuantity").value
    );
    formData.append(
      "category_id",
      document.getElementById("editProductCategory").value
    );
    formData.append(
      "is_featured",
      document.getElementById("editIsFeature").value
    );
    formData.append(
      "visibility_option",
      document.getElementById("editVisibilityOption").value
    );

    // Order types
    const preOrderChecked = document.getElementById(
      "editPreOrderCheckbox"
    ).checked;
    const sameDayChecked = document.getElementById(
      "editSameDayCheckbox"
    ).checked;

    console.log("Pre-Order Checkbox:", preOrderChecked);
    console.log("Same-Day Checkbox:", sameDayChecked);

    formData.append("preOrderCheckbox", preOrderChecked ? "true" : "false");
    formData.append("sameDayCheckbox", sameDayChecked ? "true" : "false");

    if (preOrderChecked) {
      formData.append(
        "status_id",
        document.getElementById("editPreOrderStatus").value
      );

      // Collect available days from GLOBAL checkboxes (for pre-order products)
      const globalDayCheckboxes = {
        global_sunday: "Sunday",
        global_monday: "Monday",
        global_tuesday: "Tuesday",
        global_wednesday: "Wednesday",
        global_thursday: "Thursday",
        global_friday: "Friday",
        global_saturday: "Saturday",
      };

      Object.keys(globalDayCheckboxes).forEach((checkboxId) => {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox && checkbox.checked) {
          formData.append("available_days[]", globalDayCheckboxes[checkboxId]);
        }
      });
    }

    if (sameDayChecked) {
      formData.append(
        "availtoday_status_id",
        document.getElementById("editSameDayStatus").value
      );

      // Add dates based on whether pre-order is also checked
      if (preOrderChecked) {
        // Both checked: use availableTodayDates
        const availableTodayDates = document.getElementById(
          "availableTodayDates"
        );
        if (availableTodayDates) {
          formData.append("available_today_dates", availableTodayDates.value);
        }
      } else {
        // Only same-day: use todaysProductDates
        const todaysProductDates =
          document.getElementById("todaysProductDates");
        if (todaysProductDates) {
          formData.append("todays_product_dates", todaysProductDates.value);
        }
      }

      // Collect SDO quantities (for both same-day only and pre-order + same-day)
      // Collect from DOM inputs to ensure we have the latest values
      const sdoQuantities = {};

      // Check which container is visible
      const todayContainer = document.getElementById(
        "sdoQuantityContainerToday"
      );
      const regularContainer = document.getElementById(
        "sdoQuantityContainerRegular"
      );
      console.log(
        "Today container visible:",
        todayContainer && todayContainer.offsetParent !== null
      );
      console.log(
        "Regular container visible:",
        regularContainer && regularContainer.offsetParent !== null
      );

      const quantityInputs = document.querySelectorAll(
        ".sdo-quantity-input[data-date]"
      );

      console.log("Found", quantityInputs.length, "quantity inputs");
      console.log("Quantity inputs:", quantityInputs);

      quantityInputs.forEach((input) => {
        const date = input.getAttribute("data-date");
        const quantity = parseInt(input.value) || 0;
        console.log(`Collecting: ${date} = ${quantity}`);
        sdoQuantities[date] = quantity;
      });

      console.log("Collected SDO quantities:", sdoQuantities);

      // Validate quantities before sending
      if (Object.keys(sdoQuantities).length > 0) {
        let isValid = true;
        for (const [date, quantity] of Object.entries(sdoQuantities)) {
          // Validate date format
          if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            console.error(`Invalid date format: ${date}`);
            isValid = false;
            break;
          }

          // Validate quantity
          const qty = parseInt(quantity);
          if (isNaN(qty) || qty < 0) {
            console.error(`Invalid quantity for ${date}: ${quantity}`);
            isValid = false;
            break;
          }
        }

        if (!isValid) {
          throw new Error(
            "Invalid SDO quantity data. Please check dates and quantities."
          );
        }

        formData.append("sdo_quantities", JSON.stringify(sdoQuantities));
        console.log("SDO quantities added to FormData");
      } else {
        console.log("No SDO quantities to save");
      }
    }

    // Debug: Log FormData contents
    console.log("=== FormData Contents ===");
    for (let [key, value] of formData.entries()) {
      console.log(`${key}:`, value);
    }

    // Send AJAX request
    const response = await fetch("/backend/api/update-product.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });

    // Get the response text first to debug
    const responseText = await response.text();
    console.log("Raw response:", responseText);

    // Try to parse as JSON
    let result;
    try {
      result = JSON.parse(responseText);
    } catch (parseError) {
      console.error("JSON parse error:", parseError);
      console.error("Response text:", responseText);
      throw new Error(
        "Server returned invalid JSON. Check console for details."
      );
    }

    if (result.success) {
      // Update the product row in the table without refreshing
      updateProductRow(result.product);

      // Update allProductsData array
      const index = allProductsData.findIndex((p) => p.id == productId);
      if (index !== -1) {
        allProductsData[index] = result.product;
      }

      // Show success message
      showNotification("Product updated successfully!", "success");

      // Hide saving overlay
      showModalSaving(false);

      // Re-enable save button BEFORE closing modal
      saveButton.disabled = false;
      saveButton.innerHTML = originalButtonText;

      // Close modal
      closeModal();

      // Update filter counts
      updateFilterCounts();
    } else {
      throw new Error(result.error || "Failed to update product");
    }
  } catch (error) {
    console.error("Error saving product:", error);
    showNotification("Error: " + error.message, "error");

    // Hide saving overlay
    showModalSaving(false);

    // Re-enable save button
    saveButton.disabled = false;
    saveButton.innerHTML = originalButtonText;
  }

  return false; // Prevent form submission
}

/**
 * Update a product row in the table with new data
 */
function updateProductRow(product) {
  const row = document.querySelector(`tr[data-product-id="${product.id}"]`);
  if (!row) {
    console.error("Product row not found:", product.id);
    return;
  }

  // Update row attributes
  row.setAttribute("data-status", product.status_name || "Unknown");
  row.setAttribute("data-name", product.name.toLowerCase());

  // Update product name
  const nameCell = row.querySelector(".product-name");
  if (nameCell) {
    nameCell.textContent = product.name;
  }

  // Update category
  const categoryCell = row.querySelector(".category-text");
  if (categoryCell) {
    categoryCell.innerHTML = product.category_name
      ? product.category_name
      : '<span style="color: #9ca3af;">No Category</span>';
  }

  // Update price
  const priceCell = row.querySelector(".price-text");
  if (priceCell) {
    priceCell.textContent = "₱" + parseFloat(product.price).toFixed(2);
  }

  // Update status badges
  const statusContainer = row.querySelector(".status-container");
  if (statusContainer) {
    const hasPreOrder = [1, 2, 3].includes(parseInt(product.status_id));
    const hasSameDayOrder = product.availtoday_status_name != null;

    let statusHTML = "";

    // Main status badge
    if (hasPreOrder && hasSameDayOrder) {
      statusHTML +=
        '<span class="status-badge status-both">Pre-Order & Same Day Order</span>';
    } else if (product.status_id == 4) {
      statusHTML +=
        '<span class="status-badge status-same-day-order">Same Day Order</span>';
    } else {
      statusHTML +=
        '<span class="status-badge status-pre-order">Pre-Order</span>';
    }

    // Pre-order delivery type
    if (hasPreOrder) {
      const deliveryType = product.status_name || "";
      if (deliveryType.includes("Delivery or Pick")) {
        statusHTML +=
          '<span class="delivery-badge delivery-preorder">PO: Delivery/Pick-Up</span>';
      } else {
        statusHTML += `<span class="delivery-badge delivery-preorder">PO: ${deliveryType}</span>`;
      }
    }

    // Same-day delivery type
    if (hasSameDayOrder) {
      const deliveryType = product.availtoday_status_name || "";
      if (deliveryType.includes("Delivery or Pick")) {
        statusHTML +=
          '<span class="delivery-badge delivery-sameday">SDO: Delivery/Pick-Up</span>';
      } else {
        statusHTML += `<span class="delivery-badge delivery-sameday">SDO: ${deliveryType}</span>`;
      }
    }

    // Stock badge - show appropriate stock based on product type
    let stockDisplay = "";
    let quantityClass = "";

    if (product.status_id == 4) {
      // Same Day Order only - show today's stock
      const today = new Date().toISOString().split("T")[0];
      const todaysDates = product.todays_product_dates
        ? product.todays_product_dates.split(",")
        : [];
      const isTodayAvailable = todaysDates.includes(today);

      if (isTodayAvailable && product.sameday_stock_today !== undefined) {
        const sdoStock = parseInt(product.sameday_stock_today) || 0;
        quantityClass =
          sdoStock <= 5
            ? "low-stock"
            : sdoStock <= 10
            ? "medium-stock"
            : "good-stock";
        stockDisplay = `<span class="sameday-stock">${sdoStock}</span> in stock`;
      } else {
        quantityClass = "na-stock";
        stockDisplay = "N/A";
      }
    } else {
      // Pre-order - show regular quantity
      const quantity = parseInt(product.quantity) || 0;
      quantityClass =
        quantity <= 5
          ? "low-stock"
          : quantity <= 10
          ? "medium-stock"
          : "good-stock";
      stockDisplay = `<span class="preorder-stock">${quantity}</span> in stock`;
    }

    statusHTML += `<span class="stock-badge ${quantityClass}">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 7h-9"></path>
        <path d="M14 17H5"></path>
        <circle cx="17" cy="17" r="3"></circle>
        <circle cx="7" cy="7" r="3"></circle>
      </svg>
      ${stockDisplay}
    </span>`;

    statusContainer.innerHTML = statusHTML;
  }

  // Update featured badge
  const imageContainer = row.querySelector(".product-image-container");
  if (imageContainer) {
    const existingBadge = imageContainer.querySelector(".featured-badge");
    if (product.is_featured == 1 && !existingBadge) {
      imageContainer.innerHTML += '<span class="featured-badge">★</span>';
    } else if (product.is_featured == 0 && existingBadge) {
      existingBadge.remove();
    }
  }

  // Update available days
  const availableDaysCell = row.querySelector(".available-days-text");
  if (availableDaysCell) {
    availableDaysCell.textContent = formatAvailableDays(
      product.available_days || ""
    );
  }

  // Update selected dates
  const selectedDatesCell = row.querySelector(".selected-dates-text");
  if (selectedDatesCell) {
    const dates =
      product.status_id == 4
        ? product.todays_product_dates
        : product.regular_today_dates;
    selectedDatesCell.innerHTML = formatSelectedDates(dates);
  }

  // Add a brief highlight animation
  row.style.backgroundColor = "#d1fae5";
  setTimeout(() => {
    row.style.transition = "background-color 1s ease";
    row.style.backgroundColor = "";
  }, 100);
}

/**
 * Format selected dates for display
 */
function formatSelectedDates(datesString) {
  if (!datesString) return "";

  const dates = datesString.split(",").filter((d) => d.trim());
  if (dates.length === 0) return "";

  const formattedDates = dates.map((date) => {
    const d = new Date(date.trim() + "T00:00:00");
    return `${d.getMonth() + 1}/${d.getDate()}`;
  });

  if (formattedDates.length <= 3) {
    return formattedDates.join(" · ");
  } else {
    const visible = formattedDates.slice(0, 3).join(" · ");
    const all = formattedDates.join(" · ");
    return `<span class="dates-display" data-tooltip="${all}">${visible} <span class="more-dates">+${
      formattedDates.length - 3
    }</span></span>`;
  }
}

/**
 * Show notification message
 */
function showNotification(message, type = "success") {
  // Remove existing notifications
  const existing = document.querySelector(".notification-toast");
  if (existing) {
    existing.remove();
  }

  // Create notification
  const notification = document.createElement("div");
  notification.className = `notification-toast notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        ${
          type === "success"
            ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline>'
            : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
        }
      </svg>
      <span>${message}</span>
    </div>
  `;

  document.body.appendChild(notification);

  // Trigger animation
  setTimeout(() => notification.classList.add("show"), 10);

  // Auto-remove after 3 seconds
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Setup form submission handler
function setupFormHandler() {
  console.log("Setting up form handler...");
  const form = document.getElementById("editProductForm");
  if (form) {
    // Remove the form's action attribute to prevent default submission
    form.removeAttribute("action");
    form.removeAttribute("method");

    // Add our AJAX submit handler with capture phase to ensure it runs first
    form.addEventListener(
      "submit",
      function (e) {
        console.log("Form submit event captured");
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        saveProductChanges(e);
        return false;
      },
      true
    );

    console.log("AJAX form handler attached");
  } else {
    console.log("Form not found, will retry...");
    // Retry after a short delay if form doesn't exist yet
    setTimeout(setupFormHandler, 500);
  }
}

// Call setup when DOM is ready and also after a delay to catch dynamically loaded forms
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    setupFormHandler();
    // Also setup again after a delay to catch modal forms
    setTimeout(setupFormHandler, 1000);
  });
} else {
  setupFormHandler();
  setTimeout(setupFormHandler, 1000);
}

// Make saveProductChanges globally available so it can be called from onclick
window.saveProductChanges = saveProductChanges;

/**
 * Wrapper function to open edit modal from button with data attributes
 * This prevents issues with special characters in product data
 */
function openEditModalFromButton(button) {
  const dataset = button.dataset;

  openEditModal(
    dataset.productId,
    dataset.productName,
    dataset.productDescription,
    dataset.productPrice,
    dataset.productStatus,
    dataset.productFeatured,
    dataset.productShowUnavailable,
    dataset.productHideUnavailable,
    parseInt(dataset.productQuantity),
    dataset.productAvailableDays,
    dataset.productStatusName,
    dataset.productUnavailableStatusId,
    dataset.productUnavailableStatusName,
    dataset.productAvailtodayStatusId,
    dataset.productAvailtodayStatusName,
    dataset.productTodaysDates,
    dataset.productRegularTodayDates,
    dataset.productCategoryId
  );
}

// Make wrapper function globally available
window.openEditModalFromButton = openEditModalFromButton;
