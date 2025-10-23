// PATCH FILE: Updates for product-list.js
// Instructions: Replace the filterProducts function and add updateFilterCounts function

// Updated filterProducts function - handles new status_id 4 for Same Day Order
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
    // Updated to check for status_id == 4
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
      <td colspan="8">
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

// Updated filterUnavailableByType function - handles new unavailable type
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
      (product) => product.unavailable_status_id == 2
    );
  } else if (selectedValue === "unavailable-pickup") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id == 1
    );
  } else if (selectedValue === "unavailable-delivery-pickup") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id == 3
    );
  } else if (selectedValue === "unavailable-today") {
    filteredProducts = allProductsData.filter(
      (product) => product.unavailable_status_id == 4
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
      <td colspan="8">
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

// Updated createProductRow function - handles status_id 4 for Same Day Order
function createProductRow(product) {
  const quantity = Number.parseInt(product.quantity) || 0;
  const quantityClass =
    quantity <= 5
      ? "low-stock"
      : quantity <= 10
      ? "medium-stock"
      : "good-stock";
  
  // Update to show "Same Day Order" for status_id == 4
  const displayStatus = (product.status_id == 4) ? 'Same Day Order' : (product.status_name || 'Unknown');
  const statusClass = displayStatus.toLowerCase().replace(/ /g, '-');

  let imagePath = "";
  if (product.image_url) {
    imagePath = "/assets/" + product.image_url;
  }

  const row = document.createElement("tr");
  row.setAttribute("data-status", displayStatus);
  row.setAttribute("data-name", product.name.toLowerCase());
  row.setAttribute("data-sku", product.sku.toLowerCase());

  // Format available days
  const formattedDays = formatAvailableDays(product.available_days);
  
  // Format selected dates
  const selectedDates = product.status_id == 4 ? product.todays_product_dates : product.regular_today_dates;
  const formattedDates = formatSelectedDates(selectedDates);

  row.innerHTML = `
    <td>
      <div class='product-image-container'>
        <img class='product-image' src='${imagePath}' alt='${product.name}' loading='lazy'>
        ${product.is_featured == 1 ? "<span class='featured-badge'>★</span>" : ""}
      </div>
    </td>
    <td><span class='sku-text'>${product.sku}</span></td>
    <td>
      <div class='product-info'>
        <span class='product-name'>${product.name}</span>
      </div>
    </td>
    <td><span class='price-text'>₱${Number.parseFloat(product.price).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}</span></td>
    <td>
      <div class='status-container'>
        <span class='status-badge status-${statusClass}'>${displayStatus}</span>
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
    <td><span class='available-days-text'>${formattedDays}</span></td>
    <td><span class='selected-dates-text'>${formattedDates}</span></td>
    <td>
      <div class='action-buttons'>
        <button class='btn-action btn-edit' onclick="openEditModal(
          '${product.id}', '${product.name.replace(/'/g, "\\'")}', '${(product.description || '').replace(/'/g, "\\'")}', '${product.price}', '${product.status_id}',
          ${product.is_featured == 1 ? "true" : "false"}, ${product.show_when_unavailable == 1 ? "true" : "false"},
          ${product.hide_when_unavailable == 1 ? "true" : "false"}, ${quantity},
          '${(product.available_days || "").replace(/'/g, "\\'")}', '${displayStatus.replace(/'/g, "\\'")}', 
          '${product.unavailable_status_id || "null"}', '${(product.unavailable_status_name || "").replace(/'/g, "\\'")}', 
          '${product.availtoday_status_id || "null"}', '${(product.availtoday_status_name || "").replace(/'/g, "\\'")}',
          '${(product.todays_product_dates || "").replace(/'/g, "\\'")}', '${(product.regular_today_dates || "").replace(/'/g, "\\'")}'
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

// Helper function to format selected dates
function formatSelectedDates(datesString) {
  if (!datesString) return "";
  
  const dates = datesString.split(',').filter(d => d.trim());
  if (dates.length === 0) return "";
  
  const formattedDates = [];
  for (const date of dates) {
    const dateObj = new Date(date.trim());
    if (!isNaN(dateObj.getTime())) {
      formattedDates.push(`${dateObj.getMonth() + 1}/${dateObj.getDate()}`);
    }
  }
  
  if (formattedDates.length === 0) return "";
  
  if (formattedDates.length <= 3) {
    return formattedDates.join(' · ');
  } else {
    const visibleDates = formattedDates.slice(0, 3);
    const allDates = formattedDates.join(' · ');
    return `<span class="dates-display" data-tooltip="${allDates}">${visibleDates.join(' · ')} <span class="more-dates">+${formattedDates.length - 3}</span></span>`;
  }
}

// Add updateFilterCounts function
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
