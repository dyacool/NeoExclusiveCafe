/**
 * SDO Quantity Manager
 * Manages quantity per day for Same Day Order products
 */

let sdoQuantities = Object.create(null); // Store quantities: { 'YYYY-MM-DD': quantity }
let sdoQuantitiesLoading = false; // Track loading state
let sdoQuantitiesLoaded = false; // Track if quantities have been loaded

/**
 * Initialize quantity manager for a product
 */
function initializeSDOQuantities(productId) {
  console.log("Initializing SDO quantities for product:", productId);
  // Reset state
  sdoQuantities = Object.create(null);
  sdoQuantitiesLoading = true;
  sdoQuantitiesLoaded = false;

  // Disable all quantity inputs during loading
  disableQuantityInputs(true);

  // Fetch existing quantities from server
  const url = `get-sdo-quantities.php?product_id=${productId}`;
  console.log("Fetching SDO quantities from:", url);

  fetch(url)
    .then((response) => {
      console.log("Fetch response status:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Loaded SDO quantities response:", data);
      console.log("Data success:", data.success);
      console.log("Data quantities:", data.quantities);
      console.log("Quantities keys:", Object.keys(data.quantities || {}));

      if (data.success) {
        // Ensure we copy to a plain object
        const quantities = data.quantities || {};
        sdoQuantities = Object.create(null);
        Object.keys(quantities).forEach((key) => {
          console.log(`Setting sdoQuantities[${key}] = ${quantities[key]}`);
          sdoQuantities[key] = quantities[key];
        });
        console.log("Final sdoQuantities object:", sdoQuantities);
        updateQuantityDisplay();
      } else {
        console.error("Failed to load SDO quantities:", data.message);
      }

      // Mark as loaded and enable inputs
      sdoQuantitiesLoading = false;
      sdoQuantitiesLoaded = true;
      disableQuantityInputs(false);
    })
    .catch((error) => {
      console.error("Error loading SDO quantities:", error);
      // Mark as loaded even on error so user can still edit
      sdoQuantitiesLoading = false;
      sdoQuantitiesLoaded = true;
      disableQuantityInputs(false);
    });
}

/**
 * Get the correct container based on which calendar is active
 */
function getActiveContainer() {
  const todaysProductDates = document.getElementById("todaysProductDates");
  const availableTodayDates = document.getElementById("availableTodayDates");

  // Check which calendar has dates
  if (todaysProductDates && todaysProductDates.value) {
    return document.getElementById("sdoQuantityContainerToday");
  } else if (availableTodayDates && availableTodayDates.value) {
    return document.getElementById("sdoQuantityContainerRegular");
  }

  // Default to Today container if visible
  const todayContainer = document.getElementById("sdoQuantityContainerToday");
  if (todayContainer && todayContainer.offsetParent !== null) {
    return todayContainer;
  }

  return document.getElementById("sdoQuantityContainerRegular");
}

/**
 * Update quantity display when dates change
 */
function updateQuantityDisplay() {
  const container = getActiveContainer();
  if (!container) {
    console.error("SDO Quantity container not found");
    return;
  }

  // Get selected dates from calendar
  const todaysProductDates = document.getElementById("todaysProductDates");
  const availableTodayDates = document.getElementById("availableTodayDates");

  let selectedDates = [];

  if (todaysProductDates && todaysProductDates.value) {
    console.log("Raw todaysProductDates value:", todaysProductDates.value);
    selectedDates = todaysProductDates.value.split(",").filter((d) => d.trim());
  } else if (availableTodayDates && availableTodayDates.value) {
    console.log("Raw availableTodayDates value:", availableTodayDates.value);
    selectedDates = availableTodayDates.value
      .split(",")
      .filter((d) => d.trim());
  }

  console.log("Selected dates after split:", selectedDates);

  // Only clean up quantities if we have selected dates
  // Don't remove quantities if calendar is empty (might be loading)
  if (selectedDates.length > 0) {
    const currentDates = Object.keys(sdoQuantities);
    currentDates.forEach((date) => {
      if (!selectedDates.includes(date)) {
        console.log("Removing quantity for unselected date:", date);
        delete sdoQuantities[date];
      }
    });
  }

  // If no dates selected AND no saved quantities, show placeholder
  if (selectedDates.length === 0 && Object.keys(sdoQuantities).length === 0) {
    container.innerHTML =
      '<p style="color: #6b7280; font-size: 13px;">Select dates to set quantities</p>';
    return;
  }

  // If we have saved quantities but no selected dates, display the saved quantities
  if (selectedDates.length === 0 && Object.keys(sdoQuantities).length > 0) {
    selectedDates = Object.keys(sdoQuantities);
    console.log("Using saved quantity dates:", selectedDates);
  }

  // Sort dates
  selectedDates.sort();

  // Build quantity inputs
  let html = '<div class="sdo-quantity-list">';
  html +=
    '<h4 style="margin-bottom: 10px; font-size: 14px; color: #374151;">Set Quantity Per Day:</h4>';

  // Add "Set All" input at the top
  html += `
        <div class="sdo-set-all-container">
            <label for="setAllQuantity" style="font-size: 13px; color: #374151; font-weight: 500;">Set all dates to:</label>
            <div style="display: flex; gap: 8px; align-items: center;">
                <input 
                    type="number" 
                    id="setAllQuantity" 
                    placeholder="0"
                    min="0" 
                    step="1"
                    class="sdo-quantity-input"
                    style="width: 100px;"
                />
                <button 
                    type="button"
                    onclick="applyQuantityToAll()"
                    class="btn-apply-all"
                    style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; white-space: nowrap;"
                >
                    Apply to All
                </button>
            </div>
        </div>
        <hr style="margin: 12px 0; border: none; border-top: 1px solid #e5e7eb;">
    `;

  selectedDates.forEach((date) => {
    console.log("Processing date:", date, "Type:", typeof date);
    const dateObj = new Date(date + "T00:00:00");
    console.log("Date object:", dateObj);
    const formattedDate = dateObj.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
    console.log("Formatted date:", formattedDate);

    const quantity = sdoQuantities[date] || 0;
    console.log("Quantity for", date, ":", quantity);

    html += `
            <div class="sdo-quantity-item">
                <label for="qty_${date}">${formattedDate}</label>
                <input 
                    type="number" 
                    id="qty_${date}" 
                    data-date="${date}"
                    value="${quantity}" 
                    min="0" 
                    step="1"
                    class="sdo-quantity-input"
                    onchange="updateSDOQuantity('${date}', this.value)"
                />
            </div>
        `;
  });

  html += "</div>";
  container.innerHTML = html;
}

/**
 * Disable or enable all quantity inputs
 */
function disableQuantityInputs(disabled) {
  const setAllInput = document.getElementById("setAllQuantity");
  if (setAllInput) {
    setAllInput.disabled = disabled;
  }

  const applyButton = document.querySelector(".btn-apply-all");
  if (applyButton) {
    applyButton.disabled = disabled;
    applyButton.style.opacity = disabled ? "0.5" : "1";
    applyButton.style.cursor = disabled ? "not-allowed" : "pointer";
  }

  const dateInputs = document.querySelectorAll(
    ".sdo-quantity-input[data-date]"
  );
  dateInputs.forEach((input) => {
    input.disabled = disabled;
    input.style.opacity = disabled ? "0.5" : "1";
    input.style.cursor = disabled ? "not-allowed" : "text";
  });
}

/**
 * Apply quantity to all selected dates
 */
function applyQuantityToAll() {
  // Prevent action if still loading
  if (sdoQuantitiesLoading) {
    console.log("SDO quantities still loading, please wait...");
    return;
  }

  const setAllInput = document.getElementById("setAllQuantity");
  if (!setAllInput) return;

  const quantity = parseInt(setAllInput.value) || 0;

  // Get all date inputs
  const dateInputs = document.querySelectorAll(
    ".sdo-quantity-input[data-date]"
  );
  dateInputs.forEach((input) => {
    const date = input.getAttribute("data-date");
    input.value = quantity;
    updateSDOQuantity(date, quantity);
  });

  // Clear the "set all" input
  setAllInput.value = "";
}

/**
 * Update quantity for a specific date
 */
function updateSDOQuantity(date, quantity) {
  sdoQuantities[date] = parseInt(quantity) || 0;
}

/**
 * Get all quantities for saving
 */
function getSDOQuantities() {
  return sdoQuantities;
}

/**
 * Save quantities to server (with pre-collected data)
 */
function saveSDOQuantitiesWithData(productId, quantitiesToSave) {
  console.log(
    "Saving SDO quantities with pre-collected data:",
    quantitiesToSave
  );
  console.log("Type:", typeof quantitiesToSave);
  console.log("Is Array:", Array.isArray(quantitiesToSave));
  console.log("Keys:", Object.keys(quantitiesToSave));

  const payload = {
    product_id: productId,
    quantities: quantitiesToSave,
  };

  console.log("Payload to send:", JSON.stringify(payload));

  return fetch("update-sdo-quantities.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("SDO quantities save response:", data);
      // Update the global object with saved values
      sdoQuantities = Object.create(null);
      Object.keys(quantitiesToSave).forEach((key) => {
        sdoQuantities[key] = quantitiesToSave[key];
      });
      // Refresh the display to show saved values
      updateQuantityDisplay();
      return data;
    });
}

/**
 * Save quantities to server (legacy - collects from DOM)
 */
function saveSDOQuantities(productId) {
  // Create a fresh plain object to ensure proper JSON serialization
  const quantitiesToSave = {};

  // Collect current values from all input fields before saving
  const dateInputs = document.querySelectorAll(
    ".sdo-quantity-input[data-date]"
  );
  console.log("Found", dateInputs.length, "date inputs");
  dateInputs.forEach((input) => {
    const date = input.getAttribute("data-date");
    const rawValue = input.value;
    const quantity = parseInt(rawValue) || 0;
    console.log(`Input for ${date}: raw="${rawValue}", parsed=${quantity}`);
    quantitiesToSave[date] = quantity;
  });

  return saveSDOQuantitiesWithData(productId, quantitiesToSave);
}

// Add CSS styles
const style = document.createElement("style");
style.textContent = `
    .sdo-quantity-list {
        max-height: 400px;
        overflow-y: auto;
        padding: 12px;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }
    
    .sdo-set-all-container {
        padding: 10px;
        background: #fff;
        border-radius: 4px;
        border: 1px solid #e5e7eb;
        margin-bottom: 8px;
    }
    
    .sdo-quantity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .sdo-quantity-item:last-child {
        border-bottom: none;
    }
    
    .sdo-quantity-item label {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
    }
    
    .sdo-quantity-input {
        width: 80px;
        padding: 6px 10px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 13px;
        text-align: center;
    }
    
    .sdo-quantity-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .btn-apply-all:hover {
        background: #2563eb !important;
    }
    
    .btn-apply-all:active {
        background: #1d4ed8 !important;
    }
`;
document.head.appendChild(style);
