// Enhanced Calendar with Admin Features
let currentDate = new Date();
let dateLimits = {};
let showCompletedOrders = false;

// Initialize calendar when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  cleanupPastDates(); // Clean up old dates first
  loadToggleState(); // Load saved toggle state first
  renderCalendar(currentDate);
  loadOrderLimit();
  loadAvailTodayOrderLimit();
  loadDateLimitsForMonth(currentDate); // Add this line to load date limits
  setupEventListeners();
  setupModalEventListeners(); // Add modal event listeners
});

function setupEventListeners() {
  // Navigation buttons
  document.getElementById("prev").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
    loadDateLimitsForMonth(currentDate); // Load date limits for new month
  };

  document.getElementById("next").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
    loadDateLimitsForMonth(currentDate); // Load date limits for new month
  };

  // Order limit input change handler removed - business hours moved to dashboard

  // Modal close functionality
  const orderModal = document.getElementById("orderModal");
  const dailyLimitModal = document.getElementById("dailyLimitModal");

  // Close button handlers
  document.querySelectorAll(".close").forEach((closeBtn) => {
    closeBtn.onclick = function () {
      // Check which modal this close button belongs to
      const parentModal = this.closest(".modal, .order-details-modal");
      if (parentModal) {
        if (parentModal.id === "dateLimitModal") {
          closeDateLimitModal();
        } else {
          parentModal.style.display = "none";
        }
      }
    };
  });

  // Window click handler for overlay close
  window.onclick = function (event) {
    if (event.target === orderModal) {
      orderModal.style.display = "none";
    }
    if (event.target === dailyLimitModal) {
      dailyLimitModal.style.display = "none";
    }
  };

  // Confirm button handler
  const confirmBtn = document.getElementById("confirmComplete");
  if (confirmBtn) {
    confirmBtn.onclick = function () {
      const orderId = this.dataset.orderId;
      if (orderId) {
        completeOrder(orderId);
        confirmationModal.style.display = "none";
        orderModal.style.display = "none";
      }
    };
  }
}

function setupModalEventListeners() {
  // Additional modal event setup if needed
  console.log("Modal event listeners setup complete");
}

function renderCalendar(date) {
  const daysContainer = document.querySelector(".days");
  const monthYear = document.getElementById("monthYear");
  daysContainer.innerHTML = "";
  monthYear.textContent = date.toLocaleString("default", {
    month: "long",
    year: "numeric",
  });

  const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
  const daysInMonth = new Date(
    date.getFullYear(),
    date.getMonth() + 1,
    0
  ).getDate();

  // Add empty cells for days before the first day of the month
  for (let i = 0; i < firstDay; i++) {
    daysContainer.innerHTML += `<div class="day empty"></div>`;
  }

  // Add days of the month
  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(date.getFullYear(), date.getMonth(), i);
    // Fix date string creation to avoid timezone issues
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(i).padStart(2, "0");
    const dateStr = `${year}-${month}-${day}`;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let dayClass = "day";
    let dayContent = i;
    let clickHandler = "";

    // Check if it's today
    if (dayDate.getTime() === today.getTime()) {
      dayClass += " today";
      dayContent += '<div class="today-indicator">Today</div>';
      // No click handler for today - make it non-interactable
    } else if (dayDate < today) {
      // Check if it's a past date
      dayClass += " past-date";
      dayContent += '<div class="past-indicator">Past</div>';
    } else {
      // Check if date has orders or limits
      clickHandler = `onclick="openDateLimitModal('${dateStr}')"`;

      // Check if date is not accepting orders
      if (
        dateLimits[dateStr] &&
        (dateLimits[dateStr].limit === 0 ||
          dateLimits[dateStr].status === "not_accepting")
      ) {
        dayClass += " not-accepting-orders";
        dayContent += '<div class="not-accepting-overlay">✕</div>';
      }
    }

    daysContainer.innerHTML += `<div class="day ${dayClass}" data-date="${dateStr}" ${clickHandler}>${dayContent}</div>`;
  }

  // Load orders for this month
  loadOrdersForMonth(date);
}

function loadOrdersForMonth(date) {
  const startDate = new Date(date.getFullYear(), date.getMonth(), 1);
  const endDate = new Date(date.getFullYear(), date.getMonth() + 1, 0);

  console.log("Loading orders for month:", {
    start: startDate.toISOString().split("T")[0],
    end: endDate.toISOString().split("T")[0],
    showCompleted: showCompletedOrders,
  });

  fetch(
    `get-orders.php?start=${startDate.toISOString().split("T")[0]}&end=${
      endDate.toISOString().split("T")[0]
    }&showCompleted=${showCompletedOrders}`
  )
    .then((response) => {
      console.log("Response status:", response.status);
      return response.json();
    })
    .then((orders) => {
      console.log("Orders received:", orders);
      displayOrdersOnCalendar(orders);
    })
    .catch((error) => {
      console.error("Error loading orders:", error);
    });
}

function displayOrdersOnCalendar(orders) {
  // Clear existing order indicators
  document.querySelectorAll(".day .order-indicator").forEach((indicator) => {
    indicator.remove();
  });

  orders.forEach((order) => {
    try {
      // Get the date from the order - it could be in 'start' field or 'date' field
      let orderDateStr = order.start || order.date;

      if (!orderDateStr) {
        console.warn("Order missing date:", order);
        return;
      }

      // Handle different date formats
      let orderDate;
      if (typeof orderDateStr === "string") {
        // If it's already a date string in YYYY-MM-DD format
        if (orderDateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
          orderDate = new Date(orderDateStr + "T12:00:00");
        } else if (orderDateStr.includes("T")) {
          // If it's already in ISO format
          orderDate = new Date(orderDateStr);
        } else {
          // Try to parse as regular date
          orderDate = new Date(orderDateStr);
        }
      } else {
        orderDate = new Date(orderDateStr);
      }

      // Check if the date is valid
      if (isNaN(orderDate.getTime())) {
        console.warn(
          "Invalid date for order:",
          order,
          "Date string:",
          orderDateStr
        );
        return;
      }

      const dateStr = orderDate.toISOString().split("T")[0];
      const dayElement = document.querySelector(`[data-date="${dateStr}"]`);

      if (dayElement) {
        const orderIndicator = document.createElement("div");

        // Get status from extendedProps or use a default
        const status =
          (order.extendedProps && order.extendedProps.status) || "pending";

        orderIndicator.className = `order-indicator ${status.toLowerCase()}`;
        orderIndicator.innerHTML = `#${order.id}`;
        orderIndicator.onclick = (e) => {
          e.stopPropagation();
          showOrderDetails(order.id);
        };
        dayElement.appendChild(orderIndicator);
      } else {
        console.warn("Day element not found for date:", dateStr);
      }
    } catch (error) {
      console.error("Error processing order:", order, "Error:", error);
    }
  });
}

function showDateLimit(date) {
  console.log("showDateLimit input date:", date);

  // Hide the complete order button if present
  var completeOrderBtn = document.getElementById("completeOrderBtn");
  if (completeOrderBtn) {
    completeOrderBtn.style.display = "none";
  }

  const modal = document.getElementById("orderModal");
  const orderInfo = document.getElementById("orderInfo");

  // Convert date for display
  const displayDate = new Date(date + "T12:00:00").toLocaleDateString();

  orderInfo.innerHTML = `
        <div class="close-btnn" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 2px solid #f1f5f9;">
            <div></div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #1e293b;">Set Order Limit for ${displayDate}</h3>
            <span class="close" style="color: #64748b; font-size: 1.5rem; font-weight: 400; cursor: pointer;">&times;</span>
        </div>
        <div id="dateLimitContainer" class="date-limit-controls" style="padding: 1.5rem;">
            <div class="limit-input-group">
                <input type="number" id="dateLimit" min="0" class="limit-input">
                <button onclick="updateDateLimit('${date}')" class="update-btn">Update</button>
            </div>
            <div class="not-accepting-group">
                <button onclick="setNotAcceptingOrders('${date}')" class="not-accepting-btn">Not Accepting Orders</button>
            </div>
        </div>
    `;
  modal.style.display = "block";

  // Add click event listener to the dynamically created close button
  setTimeout(() => {
    const dynamicCloseBtn = modal.querySelector("#orderInfo .close");
    if (dynamicCloseBtn) {
      dynamicCloseBtn.onclick = function () {
        modal.style.display = "none";
      };
    }
  }, 0);

  // Fetch current limit for the date
  fetch("get-date-limits.php?date=" + date)
    .then((response) => response.text())
    .then((text) => {
      const jsonStr = text.replace(/<!--[\s\S]*?-->/g, "").trim();
      try {
        const data = JSON.parse(jsonStr);
        console.log("Date limit API response for", date, ":", data);
        if (data.success) {
          const limit =
            data.dates && data.dates[0]
              ? data.dates[0].limit
              : data.default_limit;
          document.getElementById("dateLimit").value = limit;
          console.log("Set limit input to:", limit);
        } else {
          throw new Error("Invalid response format");
        }
      } catch (e) {
        console.error("Error parsing JSON:", e, "Response:", jsonStr);
        document.getElementById("dateLimitContainer").innerHTML =
          "Error loading limit settings. Please try refreshing the page.";
      }
    })
    .catch((error) => {
      console.error("Error fetching default limit:", error);
      document.getElementById("dateLimitContainer").innerHTML =
        "Error loading limit settings. Please try refreshing the page.";
    });
}

function setNotAcceptingOrders(date) {
  if (confirm("Are you sure you want to set this date to not accept orders?")) {
    console.log("setNotAcceptingOrders input date:", date);

    fetch("update-limit.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        type: "date",
        date: date,
        limit: 0,
        status: "not_accepting",
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
      })
      .then((text) => {
        console.log("Raw update response:", text);
        try {
          const data = JSON.parse(text);
          console.log("Parsed update response for date", date, ":", data);

          if (data.success) {
            // Update the dateLimits object
            dateLimits[date] = {
              limit: 0,
              is_full: true,
              active_orders: 0,
              remaining_slots: 0,
              status: "not_accepting",
            };

            // Update the calendar display
            renderCalendar(currentDate);

            // Close modal
            document.getElementById("orderModal").style.display = "none";

            alert("Date set to not accept orders successfully!");
          } else {
            throw new Error(data.error || "Unknown error");
          }
        } catch (e) {
          console.error("Error updating date limit:", e);
          alert("Error updating date limit: " + e.message);
        }
      })
      .catch((error) => {
        console.error("Error updating limit:", error);
        alert("Error updating limit. Please try again.");
      });
  }
}

function updateDateLimit(date) {
  const limit = document.getElementById("dateLimit").value;
  if (limit === "") {
    alert("Please enter a valid limit");
    return;
  }

  console.log("updateDateLimit input date:", date);
  const limitValue = parseInt(limit);

  fetch("update-limit.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      type: "date",
      date: date,
      limit: limitValue,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Update limit response for date", date, ":", data);
      if (data.success) {
        // Update the dateLimits object
        if (limitValue === 0) {
          dateLimits[date] = {
            limit: 0,
            is_full: false,
            active_orders: 0,
            status: "not_accepting",
          };
        } else {
          dateLimits[date] = {
            limit: limitValue,
            is_full: false,
            active_orders: 0,
            status: "accepting",
          };
        }

        // Update the calendar display
        renderCalendar(currentDate);

        alert("Date limit updated successfully!");
        document.getElementById("orderModal").style.display = "none";
      } else {
        alert("Error updating limit: " + (data.error || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error updating limit:", error);
      alert("Error updating limit. Please try again.");
    });
}

function updateBusinessHours() {
  const openingTime = document.getElementById("openingTime").value;
  const closingTime = document.getElementById("closingTime").value;

  // Debug: Log the values being sent
  console.log("Opening time value:", openingTime);
  console.log("Closing time value:", closingTime);
  console.log("Opening time type:", typeof openingTime);
  console.log("Closing time type:", typeof closingTime);

  if (!openingTime || !closingTime) {
    alert("Please enter both opening and closing times");
    return;
  }

  // Special case: allow 00:00 for both times when order limit is 0 (system closed)
  if (openingTime === "00:00" && closingTime === "00:00") {
    // This is allowed when system is closed
  } else if (openingTime >= closingTime) {
    alert("Closing time must be after opening time");
    return;
  }

  const requestData = {
    openingTime: openingTime,
    closingTime: closingTime,
  };

  console.log("Request data being sent:", requestData);

  fetch("update-business-hours.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(requestData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Business hours updated successfully!");
      } else {
        alert(
          "Error updating business hours: " + (data.error || "Unknown error")
        );
      }
    })
    .catch((error) => {
      console.error("Error updating business hours:", error);
      alert("Error updating business hours. Please try again.");
    });
}



function showOrderDetails(orderId) {
  fetch("get-order-details.php?id=" + orderId)
    .then((response) => response.json())
    .then((order) => {
      const modal = document.getElementById("orderModal");
      const orderInfo = document.getElementById("orderInfo");

      // Format display date based on order type
      const displayDate =
        order.order_type === "Pick-up"
          ? `<p><strong>Pickup Date:</strong> ${order.pickup_date || "N/A"}</p>`
          : `<p><strong>Delivery Date:</strong> ${
              order.delivery_date || "N/A"
            }</p>`;

      // Build order items HTML if available
      let itemsHtml = "";
      if (order.items && order.items.length > 0) {
        itemsHtml = `
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

        // Clear any existing items first
        let processedItems = new Set();
        order.items.forEach((item) => {
          // Check if this item has already been processed
          if (!processedItems.has(item.product_name)) {
            const subtotal = (item.price * item.quantity).toFixed(2);
            itemsHtml += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                <td>₱${subtotal}</td>
                            </tr>
                        `;
            processedItems.add(item.product_name);
          }
        });

        itemsHtml += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="total-label">Total Amount:</td>
                                <td class="total-value">₱${parseFloat(
                                  order.total_amount
                                ).toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                `;
      }

      // Add status badge
      const statusBadge = `
                <div class="status-badge ${order.status.toLowerCase()}">
                    ${order.status}
                </div>
            `;

      orderInfo.innerHTML = `
                <div class="modal-header">
                    <h3>Order Details</h3>
                    <span class="close" onclick="document.getElementById('orderModal').style.display='none'">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="order-details-grid">
                        <div class="order-details-section">
                            <h3>Order Information ${statusBadge}</h3>
                            <p><strong>Order #:</strong> ${order.order_id}</p>
                            <p><strong>Order Date:</strong> ${
                              order.order_date
                            }</p>
                            <p><strong>Delivery Mode:</strong> ${
                              order.order_type
                            }</p>
                            ${displayDate}
                            <p><strong>Time:</strong> ${order.pickup_time}</p>
                            <p><strong>Payment Method:</strong> ${
                              order.payment_method || "N/A"
                            }</p>
                        </div>
                        
                        <div class="order-details-section">
                            <h3>Customer Information</h3>
                            <p><strong>Name:</strong> ${order.customer_name}</p>
                            <p><strong>Email:</strong> ${
                              order.customer_email || "N/A"
                            }</p>
                            <p><strong>Contact:</strong> ${
                              order.customer_contact || "N/A"
                            }</p>
                            <p><strong>Address:</strong> ${
                              order.customer_address || "N/A"
                            }</p>
                            ${
                              order.notes
                                ? `<p><strong>Notes:</strong> ${order.notes}</p>`
                                : ""
                            }
                        </div>
                    </div>
                    
                    ${itemsHtml}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('orderModal').style.display='none'">Close</button>
                </div>
            `;

      modal.style.display = "block";
    })
    .catch((error) => {
      console.error("Error fetching order details:", error);
      alert("Error loading order details. Please try again.");
    });
}

// Function to refresh the default limit display
function refreshDefaultLimit() {
  fetch("get-date-limits.php?get_default=true")
    .then((response) => response.text())
    .then((text) => {
      // Clean the response by removing any HTML comments
      const jsonStr = text.replace(/<!--[\s\S]*?-->/g, "").trim();
      try {
        const data = JSON.parse(jsonStr);
        if (data.success && data.default_limit !== undefined) {
          document.getElementById("dailyLimit").value = data.default_limit;
        } else {
          console.error("Invalid response format:", data);
        }
      } catch (e) {
        console.error("Error parsing JSON:", e, "Response:", jsonStr);
      }
    })
    .catch((error) => console.error("Error fetching default limit:", error));
}

// Function to load toggle state from localStorage
function loadToggleState() {
  const savedState = localStorage.getItem("showCompletedOrders");
  const toggleCheckbox = document.getElementById("toggleCompletedBtn");

  if (savedState !== null) {
    showCompletedOrders = savedState === "true";
    if (toggleCheckbox) {
      toggleCheckbox.checked = showCompletedOrders;
    }
  }

  console.log("Loaded toggle state:", showCompletedOrders);
}

// Function to toggle completed orders display
function toggleCompletedOrders() {
  const toggleCheckbox = document.getElementById("toggleCompletedBtn");

  console.log(
    "Toggle function called, checkbox state:",
    toggleCheckbox.checked
  );

  // Get the state from the checkbox
  showCompletedOrders = toggleCheckbox.checked;

  // Save the state to localStorage
  localStorage.setItem("showCompletedOrders", showCompletedOrders.toString());

  console.log("showCompletedOrders is now:", showCompletedOrders);

  // Refresh the calendar to show/hide completed orders and reload date limits
  renderCalendar(currentDate);
  loadDateLimitsForMonth(currentDate);
}

// Order Limit Functions
function loadOrderLimit() {
  const dailyLimitInput = document.getElementById("dailyLimit");
  
  fetch("get-date-limits.php?get_default=true")
    .then((response) => response.text())
    .then((text) => {
      // Clean the response by removing any HTML comments
      const jsonStr = text.replace(/<!--[\s\S]*?-->/g, "").trim();
      try {
        const data = JSON.parse(jsonStr);
        if (data.success && data.default_limit !== undefined) {
          dailyLimitInput.value = data.default_limit;
          dailyLimitInput.disabled = false;
          dailyLimitInput.placeholder = "";
        } else {
          console.error("Invalid response format:", data);
          dailyLimitInput.placeholder = "Error loading";
        }
      } catch (e) {
        console.error("Error parsing JSON:", e, "Response:", jsonStr);
        dailyLimitInput.placeholder = "Error loading";
      }
    })
    .catch((error) => {
      console.error("Error fetching default limit:", error);
      dailyLimitInput.placeholder = "Error loading";
    });
}

function updateDailyLimit() {
  const limit = document.getElementById("dailyLimit").value;
  const limitValue = parseInt(limit);
  
  console.log("updateDailyLimit called with value:", limit, "parsed:", limitValue);
  
  if (!limit || isNaN(limitValue) || limitValue < 0) {
    alert("Please enter a valid limit (0 or greater)");
    return;
  }

  console.log("Sending update request to update-limit.php");

  fetch("update-limit.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      type: "daily",
      limit: limitValue,
    }),
  })
    .then((response) => {
      console.log("Update response status:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Update response data:", data);
      if (data.success) {
        alert("Daily limit updated successfully!");
        // Refresh the calendar and reload date limits
        renderCalendar(currentDate);
        loadDateLimitsForMonth(currentDate);
        // Reload the order limit to show updated value
        loadOrderLimit();
      } else {
        alert("Error updating limit: " + (data.error || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error updating limit:", error);
      alert("Error updating order limit. Please try again.");
    });
}

// Available Today Order Limit Functions
function loadAvailTodayOrderLimit() {
  const availtodayLimitInput = document.getElementById("availtodayOrderLimit");
  
  fetch("availtoday-order-limit-api.php?action=get_limit")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        availtodayLimitInput.value = data.limit_orders;
        availtodayLimitInput.disabled = false;
        availtodayLimitInput.placeholder = "";
        updateAvailTodayOrderLimitStatus(data.limit_orders);
      } else {
        console.error("Error loading availtoday order limit:", data.error);
        availtodayLimitInput.placeholder = "Error loading";
      }
    })
    .catch((error) => {
      console.error("Error loading availtoday order limit:", error);
      availtodayLimitInput.placeholder = "Error loading";
    });
}

function updateAvailTodayOrderLimit() {
  const limitInput = document.getElementById("availtodayOrderLimit");
  const limit = parseInt(limitInput.value);

  if (isNaN(limit) || limit < 0) {
    alert("Please enter a valid limit (0 or positive number)");
    return;
  }

  const formData = new FormData();
  formData.append("action", "update_limit");
  formData.append("limit", limit);

  fetch("availtoday-order-limit-api.php?action=update_limit", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Available Today order limit updated successfully!");
        updateAvailTodayOrderLimitStatus(data.limit_orders);

        // Note: Business hours management has been moved to dashboard
        // Automatic business hours updates are disabled
      } else {
        alert(
          "Error updating Available Today order limit: " +
            (data.error || "Unknown error")
        );
      }
    })
    .catch((error) => {
      console.error("Error updating Available Today order limit:", error);
      alert("Error updating Available Today order limit. Please try again.");
    });
}

function updateAvailTodayOrderLimitStatus(limit) {
  const statusElement = document.getElementById("availtodayOrderLimitStatus");
  if (statusElement) {
    if (limit === 0) {
      statusElement.textContent = "CLOSED";
      statusElement.className = "status-indicator closed";
    } else {
      statusElement.textContent = "OPEN";
      statusElement.className = "status-indicator open";
    }
  }
}

function loadDateLimitsForMonth(date) {
  const startDate = new Date(date.getFullYear(), date.getMonth(), 1);
  const endDate = new Date(date.getFullYear(), date.getMonth() + 1, 0);

  console.log("Loading date limits for month:", {
    start: startDate.toISOString().split("T")[0],
    end: endDate.toISOString().split("T")[0],
  });

  fetch(
    `get-date-limits.php?start=${startDate.toISOString().split("T")[0]}&end=${
      endDate.toISOString().split("T")[0]
    }`
  )
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text();
    })
    .then((text) => {
      try {
        const jsonStr = text.replace(/<!--[\s\S]*?-->/g, "").trim();
        const data = JSON.parse(jsonStr);

        if (data.success && data.dates) {
          // Clear existing date limits
          dateLimits = {};

          // Populate dateLimits with the fetched data
          data.dates.forEach((dateInfo) => {
            dateLimits[dateInfo.date] = {
              limit: parseInt(dateInfo.limit) || 0,
              count: parseInt(dateInfo.current_orders) || 0,
              is_full:
                dateInfo.is_full ||
                parseInt(dateInfo.current_orders) >= parseInt(dateInfo.limit),
              active_orders: parseInt(dateInfo.active_orders) || 0,
              remaining_slots:
                parseInt(dateInfo.limit) -
                (parseInt(dateInfo.current_orders) || 0),
              status:
                dateInfo.status ||
                (parseInt(dateInfo.limit) === 0
                  ? "not_accepting"
                  : "accepting"),
            };
          });

          console.log("Date limits loaded:", dateLimits);

          // Re-render calendar with updated date limits
          renderCalendar(currentDate);
        } else {
          console.warn("No date limits data received or invalid format");
        }
      } catch (e) {
        console.error("Error parsing date limits response:", e);
      }
    })
    .catch((error) => {
      console.error("Error loading date limits:", error);
    });
}

// Daily Limit Modal Functions
function openDailyLimitModal() {
  const modal = document.getElementById("dailyLimitModal");
  const input = document.getElementById("modalDailyLimit");
  const currentLimit = document.getElementById("dailyLimit").value;

  input.value = currentLimit;
  modal.style.display = "block";
}

function closeDailyLimitModal() {
  const modal = document.getElementById("dailyLimitModal");
  modal.style.display = "none";
}

function saveDailyLimit() {
  const newLimit = document.getElementById("modalDailyLimit").value;
  document.getElementById("dailyLimit").value = newLimit;

  updateDailyLimit();
  closeDailyLimitModal();
}

// Make functions available globally
window.openDailyLimitModal = openDailyLimitModal;
window.closeDailyLimitModal = closeDailyLimitModal;
window.saveDailyLimit = saveDailyLimit;

// Date Limit Modal Functions
let selectedDate = "";

function openDateLimitModal(date) {
  selectedDate = date;
  const modal = document.getElementById("dateLimitModal");
  const modalTitle = document.getElementById("modalTitle");
  const dateLimitInput = document.getElementById("dateLimitInput");
  const notAcceptingCheckbox = document.getElementById("notAcceptingOrders");

  console.log("Opening modal for date:", date); // Debug log

  // Format date for display - Fix timezone issue
  const dateParts = date.split("-");
  const year = parseInt(dateParts[0]);
  const month = parseInt(dateParts[1]) - 1; // Month is 0-based
  const day = parseInt(dateParts[2]);
  const dateObj = new Date(year, month, day);

  console.log("Date parts:", { year, month: month + 1, day }); // Debug log
  console.log("Created date object:", dateObj); // Debug log

  const formattedDate = dateObj.toLocaleDateString("en-US", {
    month: "numeric",
    day: "numeric",
    year: "numeric",
  });

  console.log("Formatted date:", formattedDate); // Debug log

  modalTitle.textContent = `Set Order Limit for ${formattedDate}`;

  // Check if we have existing limit data for this date
  if (dateLimits[date]) {
    dateLimitInput.value = dateLimits[date].limit;
    notAcceptingCheckbox.checked =
      dateLimits[date].status === "not_accepting" ||
      dateLimits[date].limit === 0;
  } else {
    // Use default daily limit
    const defaultLimit = document.getElementById("dailyLimit").value;
    dateLimitInput.value = defaultLimit;
    notAcceptingCheckbox.checked = false;
  }

  // Handle checkbox change to disable/enable input
  notAcceptingCheckbox.onchange = function () {
    if (this.checked) {
      dateLimitInput.value = 0;
      dateLimitInput.disabled = true;
    } else {
      dateLimitInput.disabled = false;
      // Restore default limit if available
      const defaultLimit = document.getElementById("dailyLimit").value;
      dateLimitInput.value = defaultLimit;
    }
  };

  // Set initial state
  dateLimitInput.disabled = notAcceptingCheckbox.checked;

  modal.style.display = "block";
}

function closeDateLimitModal() {
  const modal = document.getElementById("dateLimitModal");
  modal.style.display = "none";
  selectedDate = "";
}

function saveDateLimit() {
  const dateLimitInput = document.getElementById("dateLimitInput");
  const notAcceptingCheckbox = document.getElementById("notAcceptingOrders");

  const limit = notAcceptingCheckbox.checked
    ? 0
    : parseInt(dateLimitInput.value);

  if (isNaN(limit) || limit < 0) {
    alert("Please enter a valid limit (0 or greater)");
    return;
  }

  console.log("Saving date limit for:", selectedDate, "limit:", limit);

  // Show loading state
  const saveBtn = document.querySelector(".modal-footer .btn-primary");
  const originalText = saveBtn.textContent;
  saveBtn.textContent = "Updating...";
  saveBtn.disabled = true;

  fetch("update-limit.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      type: "date",
      date: selectedDate,
      limit: limit,
    }),
  })
    .then((response) => response.text())
    .then((text) => {
      try {
        const data = JSON.parse(text);
        console.log("Save response:", data);

        if (data.success) {
          // Update the dateLimits object
          dateLimits[selectedDate] = {
            limit: limit,
            is_full: limit === 0,
            active_orders: dateLimits[selectedDate]?.active_orders || 0,
            remaining_slots:
              limit - (dateLimits[selectedDate]?.active_orders || 0),
            status: limit === 0 ? "not_accepting" : "accepting",
          };

          // Update the calendar display
          renderCalendar(currentDate);

          // Close modal
          closeDateLimitModal();

          alert("Order limit updated successfully!");
        } else {
          throw new Error(data.error || "Unknown error");
        }
      } catch (e) {
        console.error("Error updating date limit:", e);
        alert("Error updating date limit: " + e.message);
      }
    })
    .catch((error) => {
      console.error("Error updating limit:", error);
      alert("Error updating limit. Please try again.");
    })
    .finally(() => {
      // Reset button state
      saveBtn.textContent = originalText;
      saveBtn.disabled = false;
    });
}

// Make date limit functions available globally
window.openDateLimitModal = openDateLimitModal;
window.closeDateLimitModal = closeDateLimitModal;
window.saveDateLimit = saveDateLimit;


// Cleanup Past Dates Function
function cleanupPastDates() {
  fetch("cleanup-past-dates.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        console.log("Cleaned up past dates:", data.deleted_count, "date(s) removed");
      } else {
        console.warn("Failed to cleanup past dates:", data.error);
      }
    })
    .catch((error) => {
      console.error("Error cleaning up past dates:", error);
    });
}
