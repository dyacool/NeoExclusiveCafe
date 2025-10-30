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
    pickup: allProductsData.filter(p => p.status_id == 1).length,
    delivery: allProductsData.filter(p => p.status_id == 2).length,
    deliveryPickup: allProductsData.filter(p => p.status_id == 3).length,
    availableToday: allProductsData.filter(p => p.status_id == 4).length,
    featured: allProductsData.filter(p => p.is_featured == 1).length,
    unavailable: allProductsData.filter(p => p.unavailable_status_id !== null).length
  };
  
  // Update count badges
  const countElements = {
    'count-all': counts.all,
    'count-pickup': counts.pickup,
    'count-delivery': counts.delivery,
    'count-delivery-pickup': counts.deliveryPickup,
    'count-available-today': counts.availableToday,
    'count-featured': counts.featured,
    'count-unavailable': counts.unavailable
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
    Sunday: "S",
    Monday: "M",
    Tuesday: "T",
    Wednesday: "W",
    Thursday: "Th",
    Friday: "F",
    Saturday: "Sa",
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
    product.status_id == 4 ? 'Same Day Order' : (product.status_name || "Unknown")
  }</span>
        ${
          product.status_id == 4 && product.availtoday_status_name
            ? `<span class='availtoday-badge'>For ${product.availtoday_status_name}</span>`
            : (product.status_id == 1 || product.status_id == 2 || product.status_id == 3) && product.availtoday_status_name
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
  document.getElementById("editProductStatus").value = status;
  
  // Set category
  const categorySelect = document.getElementById("editProductCategory");
  if (categorySelect) {
    categorySelect.value = (categoryId && categoryId !== 'null') ? categoryId : '';
  }
  
  // Update dynamic status name label
  const dynamicStatusLabel = document.getElementById("dynamicStatusName");
  if (dynamicStatusLabel) {
    dynamicStatusLabel.textContent = statusName || "Product";
  }

  // Handle availtoday_status dropdown - set value first (always preserve it)
  const availtodayOptions = document.getElementById("editAvailtodayOptions");
  const availtodaySelect = document.getElementById("editAvailtodayStatus");

  if (availtodayOptions && availtodaySelect) {
    // Always set the value (preserve it even when hidden)
    availtodaySelect.value = (availtodayStatusId && availtodayStatusId !== "null") ? availtodayStatusId : "";
    
    // Show/hide dropdown based on status
    if (status == 4) {
      // Same Day Order (status_id 4) - always show dropdown
      availtodayOptions.style.display = "block";
    } else if ((status == 1 || status == 2 || status == 3) && availtodayStatusId && availtodayStatusId !== "null") {
      // Pick Up, Delivery, or Delivery or Pick Up - show if they have availtoday_status_id set
      availtodayOptions.style.display = "block";
    } else {
      // Hide by default
      availtodayOptions.style.display = "none";
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
  const currentAvailableDaysSpan = document.getElementById("currentAvailableDays");
  if (currentAvailableDaysSpan) {
    if (availableDays) {
      currentAvailableDaysSpan.textContent = availableDays;
    } else {
      currentAvailableDaysSpan.textContent = "Not set";
    }
  }

  // Show/hide available days container for specific statuses
  const regularAvailableDaysContainer = document.getElementById("regularAvailableDaysContainer");
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

  // Handle isAvailableToday radio button initial state
  const isAvailableTodayContainer = document.getElementById(
    "isAvailableTodayContainer"
  );
  const isAvailableTodayRadio = document.getElementById("isAvailableToday");
  const availableTodayCalendarContainer = document.getElementById(
    "availableTodayCalendarContainer"
  );

  if (isAvailableTodayContainer && isAvailableTodayRadio) {
    if (statusName === "Pick Up" || statusName === "Delivery" || statusName === "Delivery or Pick Up") {
      isAvailableTodayContainer.style.display = "block";

      // Check if availtoday_status_id is not null to activate the radio button
      if (
        availtodayStatusId &&
        availtodayStatusId !== "null" &&
        availtodayStatusId !== ""
      ) {
        isAvailableTodayRadio.checked = true;

        // Show the availtoday_status dropdown when radio is checked
        if (availtodayOptions) {
          availtodayOptions.style.display = "block";
        }

        // Show the Available Today calendar container when radio is checked
        if (availableTodayCalendarContainer) {
          availableTodayCalendarContainer.style.display = "block";
        }

        // Set the editAvailableTodayStatus dropdown value (for isAvailableToday radio)
        const editAvailableTodayStatusSelect = document.getElementById(
          "editAvailableTodayStatus"
        );
        if (editAvailableTodayStatusSelect) {
          editAvailableTodayStatusSelect.value = availtodayStatusId;
        }
      } else {
        // Reset the radio button state if no availtoday_status_id
        isAvailableTodayRadio.checked = false;
        if (availableTodayCalendarContainer) {
          availableTodayCalendarContainer.style.display = "none";
        }
      }
    } else {
      isAvailableTodayContainer.style.display = "none";
      isAvailableTodayRadio.checked = false;
      if (availableTodayCalendarContainer) {
        availableTodayCalendarContainer.style.display = "none";
      }
    }
  }

  setupStatusChangeListener();
  setupAvailabilityRadioListeners();
  setupIsAvailableTodayListener();
  loadProductImages(id);

  document.getElementById("editModal").style.display = "flex";
  document.body.style.overflow = "hidden";

  // Initialize calendars and apply status change after modal is shown
  setTimeout(() => {
    if (window.modalCalendarHandler) {
      window.modalCalendarHandler.initializeEditModalCalendars();
      window.modalCalendarHandler.handleEditStatusChange();

      // Set selected dates in calendars
      if (todaysProductDates && status == 4) {
        const dates = todaysProductDates.split(",").filter((d) => d.trim());
        window.modalCalendarHandler.setTodaysProductDates(dates);
      }

      if (regularTodayDates && (status == 1 || status == 2 || status == 3)) {
        const dates = regularTodayDates.split(",").filter((d) => d.trim());
        if (dates.length > 0) {
          // Check the isAvailableToday radio button
          const isAvailableTodayRadio =
            document.getElementById("isAvailableToday");
          if (isAvailableTodayRadio) {
            isAvailableTodayRadio.checked = true;
            // Trigger change event to show the calendar
            isAvailableTodayRadio.dispatchEvent(new Event("change"));
          }
          window.modalCalendarHandler.setAvailableTodayDates(dates);
        }
      }
    }
    
    // Initialize SDO quantity manager
    if (typeof initializeSDOQuantities === 'function') {
      initializeSDOQuantities(id);
    }
  }, 200); // Increased timeout to ensure calendars are fully initialized
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
      const regularAvailableDaysContainer = document.getElementById("regularAvailableDaysContainer");
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
        if (selectedStatus === "Pick Up" || selectedStatus === "Delivery" || selectedStatus === "Delivery or Pick Up") {
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

        const quantityField = document.getElementById("editProductQuantity");
        if (quantityField) {
          quantityField.disabled = false;
          quantityField.style.opacity = "1";
        }
      }
    });

    unavailableRadio.addEventListener("change", function () {
      if (this.checked) {
        const statusSelect = document.getElementById("editProductStatus");
        const currentStatus = statusSelect.value;

        let unavailableTypeId = null;
        if (currentStatus === "1") unavailableTypeId = "1";
        else if (currentStatus === "2") unavailableTypeId = "2";
        else if (currentStatus === "3") unavailableTypeId = "3";
        else if (currentStatus === "4") unavailableTypeId = "4";

        if (unavailableTypeId) {
          unavailableTypeSelect.value = unavailableTypeId;
        }
        unavailableTypeContainer.style.display = "block";

        const messageElement = unavailableTypeContainer.querySelector("small");
        if (messageElement) {
          let statusText = "";
          if (currentStatus === "1") statusText = "Pick Up";
          else if (currentStatus === "2") statusText = "Delivery";
          else if (currentStatus === "3") statusText = "Delivery or Pick Up";
          else if (currentStatus === "4") statusText = "Same Day Order";

          messageElement.textContent = `Will be set to: Unavailable ${statusText}`;
        }

        const quantityField = document.getElementById("editProductQuantity");
        if (quantityField) {
          quantityField.value = "0";
          quantityField.disabled = true;
          quantityField.style.opacity = "0.5";
        }
      }
    });
  }
}

function setupIsAvailableTodayListener() {
  const isAvailableTodayRadio = document.getElementById("isAvailableToday");
  const availtodayOptions = document.getElementById("editAvailtodayOptions");
  const availableTodayDaysContainer = document.getElementById(
    "availableTodayDaysContainer"
  );

  if (isAvailableTodayRadio) {
    isAvailableTodayRadio.addEventListener("change", function () {
      if (this.checked) {
        // Show the availtoday select dropdown
        if (availtodayOptions) {
          availtodayOptions.style.display = "block";
        }
        // Show the checkbox-group2 for Available Today days
        if (availableTodayDaysContainer) {
          availableTodayDaysContainer.style.display = "block";
        }
      } else {
        // Hide the availtoday select dropdown
        if (availtodayOptions) {
          availtodayOptions.style.display = "none";
          // Don't clear the value - preserve it in case user switches back
        }
        // Hide the checkbox-group2 for Available Today days
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
      }
    });
  }
}

function closeModal() {
  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "auto";

  // Don't manipulate the global days container - it should always be visible
  // The modal has its own days container that's handled separately

  resetFormToOriginal();
}

// Image management functions
function loadProductImages(productId) {
  currentProductImages = { primary: null, additional: [] };
  pendingImageChanges = {
    primary: null,
    additional: { toAdd: [], toRemove: [] },
  };
  tempImageInfo = { primary: null, additional: [] };

  fetch(`get-product-images.php?product_id=${productId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        currentProductImages = data.images;
        displayProductImages();
      } else {
        console.error("Failed to load images:", data.error);
        showNotification("Failed to load product images", "error");
      }
    })
    .catch((error) => {
      console.error("Error loading images:", error);
      showNotification("Error loading product images", "error");
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
      : effectivePrimary.image_url.startsWith("http://") || effectivePrimary.image_url.startsWith("https://")
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
        : image.image_url.startsWith("http://") || image.image_url.startsWith("https://")
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
  const formData = new FormData();
  formData.append("image", file);
  formData.append("product_id", productId);
  formData.append("image_type", "primary");

  fetch("upload-temp-image.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tempId = "temp_" + Date.now();
        const tempImage = {
          id: tempId,
          image_url: data.temp_path,
          is_temp: true,
        };

        tempImageInfo.primary = {
          id: tempId,
          filename: data.temp_filename,
          path: data.temp_path,
        };
        pendingImageChanges.primary = tempImage;
        displayProductImages();
        showNotification("Primary image added (pending save)", "success");
      } else {
        showNotification("Error uploading image: " + data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error uploading primary image:", error);
      showNotification("Error uploading primary image", "error");
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
  const formData = new FormData();
  formData.append("image", file);
  formData.append("product_id", productId);
  formData.append("image_type", "additional");

  fetch("upload-temp-image.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tempId = "temp_" + Date.now();
        const tempImage = {
          id: tempId,
          image_url: data.temp_path,
          is_temp: true,
        };

        tempImageInfo.additional.push({
          id: tempId,
          filename: data.temp_filename,
          path: data.temp_path,
        });
        pendingImageChanges.additional.toAdd.push(tempImage);
        displayProductImages();
        showNotification("Additional image added (pending save)", "success");
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

function handleFormSubmit(event) {
  event.preventDefault();

  const statusSelect = document.getElementById("editProductStatus");
  const selectedStatus = statusSelect.options[statusSelect.selectedIndex].text;
  const selectedValue = statusSelect.value;

  // Validate availtoday_status when Same Day Order is selected
  if (selectedValue == 4) {
    const availtodaySelect = document.getElementById("editAvailtodayStatus");
    if (
      !availtodaySelect ||
      availtodaySelect.value === "" ||
      availtodaySelect.value === "null"
    ) {
      showNotification(
        "Please select an option for 'Same Day Order'.",
        "error"
      );
      return;
    }
  }

  // Collect available days from GLOBAL checkboxes (not modal checkboxes)
  const availableDays = [];
  if (
    selectedStatus === "Delivery" ||
    selectedStatus === "Pick Up" ||
    selectedStatus === "Delivery or Pick Up"
  ) {
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

  // Collect Same Day Order days from checkbox-group2
  const availableTodayDays = [];
  const isAvailableTodayRadio = document.getElementById("isAvailableToday");
  if (isAvailableTodayRadio && isAvailableTodayRadio.checked) {
    const todayDayCheckboxes = {
      edit_today_sunday: "Sunday",
      edit_today_monday: "Monday",
      edit_today_tuesday: "Tuesday",
      edit_today_wednesday: "Wednesday",
      edit_today_thursday: "Thursday",
      edit_today_friday: "Friday",
      edit_today_saturday: "Saturday",
    };

    Object.keys(todayDayCheckboxes).forEach((checkboxId) => {
      const checkbox = document.getElementById(checkboxId);
      if (checkbox && checkbox.checked) {
        availableTodayDays.push(todayDayCheckboxes[checkboxId]);
      }
    });
  }

  const isAvailable = document.getElementById("editAvailable").checked;
  const unavailableTypeId =
    document.getElementById("editUnavailableType").value || null;
  
  // Get availtoday_status_id logic:
  // - If status is "Same Day Order" (4), always use the dropdown value
  // - If status is "Pick Up" (1), "Delivery" (2), or "Delivery or Pick Up" (3) AND "Set to same day order too" is checked, use the dropdown value
  // - Otherwise, set to null
  const selectedStatusId = document.getElementById("editProductStatus").value;
  // Reuse isAvailableTodayRadio from line 1385
  const isSetToSameDayToo = isAvailableTodayRadio ? isAvailableTodayRadio.checked : false;
  
  let availtodayStatusId = null;
  if (selectedStatusId == 4) {
    // Same Day Order - always use dropdown value
    availtodayStatusId = document.getElementById("editAvailtodayStatus").value || null;
  } else if ((selectedStatusId == 1 || selectedStatusId == 2 || selectedStatusId == 3) && isSetToSameDayToo) {
    // Pick Up, Delivery, or Delivery or Pick Up with "Set to same day order too" checked
    availtodayStatusId = document.getElementById("editAvailtodayStatus").value || null;
  }
  
  console.log("DEBUG: Selected status_id:", selectedStatusId);
  console.log("DEBUG: Is set to same day too:", isSetToSameDayToo);
  console.log("DEBUG: availtodayStatusId value:", availtodayStatusId);
  console.log("DEBUG: editAvailtodayStatus dropdown value:", document.getElementById("editAvailtodayStatus")?.value);

  // Get calendar data
  const todaysProductDatesInput = document.getElementById("todaysProductDates");
  const availableTodayDatesInput = document.getElementById(
    "availableTodayDates"
  );

  const todaysProductDates = todaysProductDatesInput
    ? todaysProductDatesInput.value.split(",").filter((d) => d.trim())
    : [];
  const availableTodayDates = availableTodayDatesInput
    ? availableTodayDatesInput.value.split(",").filter((d) => d.trim())
    : [];

  // Collect SDO quantities BEFORE creating formData (while modal is still open)
  const sdoQuantitiesData = {};
  const sdoInputs = document.querySelectorAll('.sdo-quantity-input[data-date]');
  sdoInputs.forEach(input => {
    const date = input.getAttribute('data-date');
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
    status_id: document.getElementById("editProductStatus").value,
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
    is_available_today: isAvailableTodayRadio
      ? isAvailableTodayRadio.checked
      : false,
    available_today_days: availableTodayDays,
    todays_product_dates: JSON.stringify(todaysProductDates),
    available_today_dates: JSON.stringify(availableTodayDates),
    pending_image_changes: pendingImageChanges,
  };
  
  console.log("DEBUG: Final formData being sent:", formData);

  const submitBtn = document.querySelector('button[type="submit"][form="editProductForm"]');
  const originalText = submitBtn ? submitBtn.textContent : null;
  if (submitBtn) {
    submitBtn.textContent = "Saving...";
    submitBtn.disabled = true;
  }

  const uploadPromises = [];

  // Handle image operations
  if (pendingImageChanges.primary === "remove") {
    const deleteRemovedFormData = new FormData();
    deleteRemovedFormData.append("product_id", formData.id);
    uploadPromises.push(
      fetch("delete-removed-images.php", {
        method: "POST",
        body: deleteRemovedFormData,
      }).then((response) => response.json())
    );
  } else if (tempImageInfo.primary) {
    const primaryFormData = new FormData();
    primaryFormData.append("temp_filename", tempImageInfo.primary.filename);
    primaryFormData.append("product_id", formData.id);
    primaryFormData.append("image_type", "primary");
    primaryFormData.append("action", "move");
    uploadPromises.push(
      fetch("move-temp-to-permanent.php", {
        method: "POST",
        body: primaryFormData,
      }).then((response) => response.json())
    );
  }

  if (pendingImageChanges.additional.toRemove.length > 0) {
    const deleteRemovedFormData = new FormData();
    deleteRemovedFormData.append("product_id", formData.id);
    uploadPromises.push(
      fetch("delete-removed-images.php", {
        method: "POST",
        body: deleteRemovedFormData,
      }).then((response) => response.json())
    );
  }

  tempImageInfo.additional.forEach((tempInfo) => {
    const additionalFormData = new FormData();
    additionalFormData.append("temp_filename", tempInfo.filename);
    additionalFormData.append("product_id", formData.id);
    additionalFormData.append("image_type", "additional");
    additionalFormData.append("action", "move");
    uploadPromises.push(
      fetch("move-temp-to-permanent.php", {
        method: "POST",
        body: additionalFormData,
      }).then((response) => response.json())
    );
  });

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
        // Save SDO quantities if the function exists and we have quantities to save
        if (typeof saveSDOQuantitiesWithData === 'function' && Object.keys(sdoQuantitiesData).length > 0) {
          return saveSDOQuantitiesWithData(formData.id, sdoQuantitiesData).then(() => {
            showNotification("Product updated successfully!", "success");
            closeModal();
            setTimeout(() => location.reload(), 1000);
          }).catch((error) => {
            console.error("Error saving SDO quantities:", error);
            showNotification("Product updated but failed to save quantities", "warning");
            closeModal();
            setTimeout(() => location.reload(), 1000);
          });
        } else {
          showNotification("Product updated successfully!", "success");
          closeModal();
          setTimeout(() => location.reload(), 1000);
        }
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
  document.getElementById("count-available-today").textContent = counts["Same Day Order"];
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
  document.getElementById("editProductStatus").value =
    originalFormData.status || "";

  // Reset availtoday_status dropdown
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

    const restoreFormData = new FormData();
    restoreFormData.append("product_id", originalFormData.id);

    fetch("restore-removed-images.php", {
      method: "POST",
      body: restoreFormData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          console.error("Failed to restore removed images:", data.error);
        }
      })
      .catch((error) => {
        console.error("Error restoring removed images:", error);
      });

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
  const checkboxes = document.querySelectorAll('input[name="global_available_days[]"]:checked');
  const selectedDays = Array.from(checkboxes).map(cb => cb.value);
  
  if (selectedDays.length === 0) {
    alert('Please select at least one day before applying.');
    return;
  }
  
  if (!confirm(`This will update available days for all Pick Up, Delivery, and Delivery or Pick Up products to: ${selectedDays.join(', ')}.\n\nAre you sure you want to continue?`)) {
    return;
  }
  
  // Show loading state
  const button = event.target;
  const originalText = button.textContent;
  button.textContent = 'Applying...';
  button.disabled = true;
  
  // Send AJAX request
  fetch('/backend/pages/products/update-global-available-days.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      days: selectedDays
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(`Success! Updated ${data.updated_count} products.`);
      // Reload the page to show updated data
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while updating available days.');
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
  if (!confirm(`This will apply the selected days (${selectedDays.join(", ")}) to all products with Pick Up, Delivery, or Delivery or Pick Up status. Continue?`)) {
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
        return response.text().then(text => {
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
      showNotification("An error occurred while applying days: " + error.message, "error");
    });
}
