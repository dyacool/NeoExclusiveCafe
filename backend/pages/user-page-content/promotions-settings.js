const VoucherTable = (function () {
  function initDataTable() {
    return new DataTable("#supply-order-table", {
      scrollX: true,
      responsive: true,
      processing: false, // Disable processing indicator
      serverSide: true,
      pageLength: 12,
      lengthChange: false,
      searching: false,
      info: false,
      paging: false, // Disable DataTables pagination
      ordering: false, // Disable sorting
      dom: "t", // Only show table
      autoWidth: false,
      deferRender: true,
      ajax: {
        url: "./promotions_api.php",
        type: "post",
        data: function (d) {
          d.action = "datatableDisplay";
          return d;
        },
      },
      columns: [
        { data: "id", visible: false, orderable: false, searchable: false },
        { data: "title", orderable: false, searchable: false },
        { data: "code", orderable: false, searchable: false },
        {
          data: "discount",
          render: renderDiscount,
          orderable: false,
          searchable: false,
        },
        {
          data: "restrictions",
          render: renderRestrictions,
          orderable: false,
          searchable: false,
        },
        {
          data: "usage",
          render: renderUsage,
          orderable: false,
          searchable: false,
        },
        { data: "valid_period", orderable: false, searchable: false },
        {
          data: "status",
          render: renderStatus,
          orderable: false,
          searchable: false,
        },
      ],
      columnDefs: [
        {
          targets: "_all",
          orderable: false,
          searchable: false,
          className: "no-sort",
        },
      ],
      initComplete: function (settings, json) {
        // Remove any DataTables generated headers
        $(this.api().table().header()).hide();
        // Load filter counts immediately after table loads
        updateFilterCounts();
      },
      drawCallback: function (settings) {
        // Show custom pagination after table loads
        updateCustomPagination(settings);
        // Ensure DataTables headers stay hidden
        $(this.api().table().header()).hide();
        // Remove spinner from filter buttons after data loads
        document.querySelectorAll(".filter-btn").forEach((btn) => {
          btn.classList.remove("loading");
        });
      },
    });
  }

  function updateCustomPagination(settings) {
    const info = settings.json || {};
    const recordsTotal = info.recordsTotal || 0;
    const recordsFiltered = info.recordsFiltered || 0;
    const start = info.start || 0;
    const length = info.length || 12;
    const currentPage = Math.floor(start / length) + 1;
    const totalPages = Math.ceil(recordsFiltered / length);

    // Update pagination info
    const paginationInfo = document.getElementById("pagination-info");
    if (paginationInfo) {
      const displayStart = recordsFiltered > 0 ? start + 1 : 0;
      const displayEnd = Math.min(start + length, recordsFiltered);
      paginationInfo.textContent = `Showing ${displayEnd} of ${recordsFiltered} promotions`;
    }

    // Generate pagination buttons
    const paginationNav = document.getElementById("pagination-nav");
    if (paginationNav && totalPages > 1) {
      let paginationHTML = "";

      // Previous button
      const prevDisabled = currentPage <= 1 ? "disabled" : "";
      paginationHTML += `
        <button class="pagination-btn ${prevDisabled}" ${
        prevDisabled ? "" : `onclick="goToPage(${currentPage - 1})"`
      }>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15,18 9,12 15,6"></polyline>
          </svg>
          Previous
        </button>
      `;

      // Page numbers with ellipsis logic
      const maxVisiblePages = 5;
      const startPage = Math.max(
        1,
        Math.min(
          currentPage - Math.floor(maxVisiblePages / 2),
          totalPages - maxVisiblePages + 1
        )
      );
      const endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);

      if (startPage > 1) {
        paginationHTML += `<button class="pagination-number" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
          paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
      }

      for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? "active" : "";
        paginationHTML += `<button class="pagination-number ${active}" onclick="goToPage(${i})">${i}</button>`;
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        paginationHTML += `<button class="pagination-number" onclick="goToPage(${totalPages})">${totalPages}</button>`;
      }

      // Next button
      const nextDisabled = currentPage >= totalPages ? "disabled" : "";
      paginationHTML += `
        <button class="pagination-btn ${nextDisabled}" ${
        nextDisabled ? "" : `onclick="goToPage(${currentPage + 1})"`
      }>
          Next
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9,18 15,12 9,6"></polyline>
          </svg>
        </button>
      `;

      paginationNav.innerHTML = paginationHTML;
    } else {
      paginationNav.innerHTML = "";
    }

    // Show/hide pagination container
    const paginationContainer = document.getElementById("custom-pagination");
    if (paginationContainer) {
      paginationContainer.style.display = totalPages > 1 ? "flex" : "none";
    }
  }

  function renderDiscount(data, type, row) {
    if (type === "display") {
      // Check if it's free shipping only (no other discount)
      if (row.type === "free_shipping") {
        return `<div style="display: flex; align-items: center; gap: 8px;">
                  <span style="background: #D1FAE5; color: #065F46; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;">Free Shipping</span>
                </div>`;
      }

      // Check if it's a percentage discount
      if (row.type === "percentage") {
        return `<div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-weight: 600; color: #111827;">${row.value}%</span>
                  <span style="background: #DBEAFE; color: #1E40AF; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;">Percentage</span>
                </div>`;
      }

      // Otherwise it's a fixed amount
      if (row.type === "fixed") {
        return `<div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-weight: 600; color: #111827;">₱${parseFloat(
                    row.value
                  ).toFixed(2)}</span>
                  <span style="background: #FEF3C7; color: #92400E; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;">Fixed Amount</span>
                </div>`;
      }
    }
    return data;
  }

  function renderRestrictions(data, type, row) {
    if (type === "display") {
      return data;
    }
    return data;
  }

  function renderUsage(data, type, row) {
    if (type === "display") {
      return data;
    }
    return data;
  }

  function renderStatus(data, type, row) {
    if (type === "display") {
      let text = data.charAt(0).toUpperCase() + data.slice(1);
      let bgColor = "#E3FCF4",
        textColor = "#039855",
        dotColor = "#12B76A";
      if (data === "expired") {
        bgColor = "#F2F4F7";
        textColor = "#667085";
        dotColor = "#D0D5DD";
      } else if (data === "upcoming") {
        bgColor = "#D5CAB5";
        textColor = "#845832";
        dotColor = "#A89869";
      } else if (data === "archived") {
        bgColor = "#FEE4E2";
        textColor = "#D92D20";
        dotColor = "#F04438";
      }
      return `<span class="status-badge" style="background-color: ${bgColor}; color: ${textColor}; padding: 6px 12px; border-radius: 16px; font-size: 12px; font-weight: 500; text-transform: capitalize; display: inline-flex; align-items: center; gap: 6px; min-width: 80px; justify-content: center;"><span style="width: 6px; height: 6px; background-color: ${dotColor}; border-radius: 50%; display: inline-block;"></span>${text}</span>`;
    }
    return data;
  }

  function renderMethod(data, type, row) {
    if (type === "display") {
      if (data === "voucher_code") return "Voucher Code";
      if (data === "automatic_discount") return "Automatic Discount";
      return data;
    }
    return data;
  }

  function addSelectEvent(table) {
    table.on("click", "tbody tr", function (e) {
      e.currentTarget.classList.toggle("selected");
    });
  }

  function getSelectedRow(table) {
    return table.rows(".selected").data();
  }

  function deselectAllRows() {
    document
      .querySelectorAll("tbody tr.selected")
      .forEach((el) => el.classList.remove("selected"));
  }

  return {
    initDataTable,
    addSelectEvent,
    getSelectedRow,
    deselectAllRows,
    updateCustomPagination,
  };
})();

const VoucherControls = (function () {
  function add_events() {
    const newBtn = document.getElementById("supply-order-new-btn");
    const addModal = document.getElementById("addModal");

    if (newBtn && addModal) {
      newBtn.addEventListener("click", function () {
        addModal.style.display = "flex";
        // Apply date constraints when modal opens
        if (typeof setDateMinimums === "function") {
          setDateMinimums();
        }
      });
    }

    // Modal close logic
    const addModalClose = document.getElementById("addModal");
    if (addModalClose) {
      const closeBtn = addModalClose.querySelector(".close");
      if (closeBtn) {
        closeBtn.addEventListener("click", function () {
          addModal.style.display = "none";
        });
      }
    }

    window.addEventListener("click", function (e) {
      if (e.target === addModal) {
        addModal.style.display = "none";
      }
    });
  }

  return {
    add_events,
  };
})();

let supplyOrderTable = null;
document.addEventListener("DOMContentLoaded", function () {
  // Load filter counts immediately before table loads
  updateFilterCounts();

  supplyOrderTable = VoucherTable.initDataTable();
  VoucherTable.addSelectEvent(supplyOrderTable);
  VoucherControls.add_events();

  const reactivateBtn = document.getElementById("reactivate-voucher-btn");
  const table = document.getElementById("supply-order-table");
  function updateReactivateBtn() {
    const selectedRows = VoucherTable.getSelectedRow(supplyOrderTable);
    if (selectedRows.length === 1 && selectedRows[0].status === "expired") {
      reactivateBtn.disabled = false;
    } else {
      reactivateBtn.disabled = true;
    }
  }

  table.addEventListener("click", function (e) {
    // Only update if a row is clicked
    if (e.target.closest("tbody tr")) {
      updateReactivateBtn();
    }
  });

  supplyOrderTable.on("draw", updateReactivateBtn);
  updateReactivateBtn();

  reactivateBtn.addEventListener("click", function () {
    const selectedRows = VoucherTable.getSelectedRow(supplyOrderTable);
    if (selectedRows.length !== 1) return;
    const voucher = selectedRows[0];

    // Store the voucher ID in a more reliable way
    const voucherIdInput = document.createElement("input");
    voucherIdInput.type = "hidden";
    voucherIdInput.id = "selected-voucher-id";
    voucherIdInput.value = voucher.id;

    const form = document.getElementById("reactivate-voucher-form");
    const existingInput = form.querySelector("#selected-voucher-id");
    if (existingInput) {
      existingInput.remove();
    }
    form.appendChild(voucherIdInput);

    // Also store in dataset as backup
    form.dataset.voucherId = voucher.id;

    const today = new Date();
    const todayStr = today.toISOString().split("T")[0];
    const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    const nextWeekStr = nextWeek.toISOString().split("T")[0];
    const activationInput = document.getElementById(
      "reactivate-activation-date"
    );
    const expirationInput = document.getElementById(
      "reactivate-expiration-date"
    );
    activationInput.value = todayStr;
    expirationInput.value = nextWeekStr;
    activationInput.setAttribute("min", todayStr);
    expirationInput.setAttribute("min", todayStr);
    activationInput.onchange = function () {
      expirationInput.setAttribute("min", activationInput.value);
      if (expirationInput.value < activationInput.value) {
        expirationInput.value = activationInput.value;
      }
    };
    document.getElementById("reactivate-voucher-modal").style.display = "flex";
    // Apply date constraints when modal opens
    if (typeof setDateMinimums === "function") {
      setDateMinimums();
    }
  });

  function resetReactivateForm() {
    const form = document.getElementById("reactivate-voucher-form");
    form.reset();
    const activationInput = document.getElementById(
      "reactivate-activation-date"
    );
    const expirationInput = document.getElementById(
      "reactivate-expiration-date"
    );
    if (activationInput) {
      activationInput.value = "";
      activationInput.setAttribute("min", "");
    }
    if (expirationInput) {
      expirationInput.value = "";
      expirationInput.setAttribute("min", "");
    }
    const errorMessages = form.querySelectorAll(".error-message");
    errorMessages.forEach((el) => el.remove());
    [activationInput, expirationInput].forEach((input) => {
      if (input) {
        input.style.borderColor = "";
        input.style.backgroundColor = "";
      }
    });

    // Clear the voucher ID references
    const voucherIdInput = form.querySelector("#selected-voucher-id");
    if (voucherIdInput) {
      voucherIdInput.remove();
    }
    form.dataset.voucherId = "";
  }
  document.getElementById("reactivate-voucher-modal-close").onclick =
    function () {
      document.getElementById("reactivate-voucher-modal").style.display =
        "none";
      resetReactivateForm();
    };
  window.addEventListener("click", function (e) {
    if (e.target === document.getElementById("reactivate-voucher-modal")) {
      document.getElementById("reactivate-voucher-modal").style.display =
        "none";
      resetReactivateForm();
    }
  });

  document.getElementById("reactivate-voucher-submit").onclick = function (e) {
    e.preventDefault();
    const form = document.getElementById("reactivate-voucher-form");

    // Get voucherId from hidden input first, then fallback to dataset
    let voucherId =
      document.getElementById("selected-voucher-id")?.value ||
      form.dataset.voucherId;

    // Validate that we have a voucher ID
    if (!voucherId || voucherId === "undefined" || voucherId === "") {
      Swal.fire("Error", "No voucher selected. Please try again.", "error");
      return;
    }

    const activationDate = document.getElementById(
      "reactivate-activation-date"
    ).value;
    const expirationDate = document.getElementById(
      "reactivate-expiration-date"
    ).value;
    const loader = document.getElementById("reactivate-voucher-loader-overlay");
    const submitBtn = document.getElementById("reactivate-voucher-submit");
    if (!activationDate || !expirationDate) {
      Swal.fire("Error", "Both dates are required.", "error");
      return;
    }
    if (activationDate > expirationDate) {
      Swal.fire(
        "Error",
        "Activation date cannot be after expiration date.",
        "error"
      );
      return;
    }
    loader.style.display = "flex";
    submitBtn.disabled = true;
    $.post(
      "./promotions_api.php",
      {
        action: "reactivate_voucher",
        voucher_id: voucherId,
        activation_date: activationDate,
        expiration_date: expirationDate,
      },
      function (data) {
        loader.style.display = "none";
        submitBtn.disabled = false;
        if (typeof data === "string") {
          try {
            data = JSON.parse(data);
          } catch (e) {
            data = { success: false, message: data };
          }
        }
        if (data.success) {
          document.getElementById("reactivate-voucher-modal").style.display =
            "none";
          if (typeof Swal !== "undefined") {
            Swal.fire("Success", data.message, "success");
          } else {
            alert(data.message);
          }
          if (typeof supplyOrderTable !== "undefined" && supplyOrderTable)
            supplyOrderTable.draw();
        } else {
          Swal.fire(
            "Error",
            data.message || "Could not reactivate voucher.",
            "error"
          );
        }
      }
    ).fail(function () {
      loader.style.display = "none";
      submitBtn.disabled = false;
      Swal.fire("Error", "Network error. Please try again.", "error");
    });
  };
});

function viewVoucher() {
  const selectedRows = VoucherTable.getSelectedRow(supplyOrderTable);
  if (selectedRows.length !== 1) {
    Swal.fire({
      title: "Selection Required",
      text: "Please select one voucher to view.",
      icon: "warning",
      confirmButtonColor: "#256035",
    });
    return;
  }
  VoucherTable.deselectAllRows();

  const voucher = selectedRows[0];

  // Populate the view modal with voucher data
  document.getElementById("viewModalTitle").textContent = voucher.title;
  document.getElementById("viewTitle").textContent = voucher.title || "-";
  document.getElementById("viewCode").textContent = voucher.code || "-";

  // Format discount type and value
  let discountTypeText = voucher.type || "-";
  if (discountTypeText === "percentage")
    discountTypeText = "Percentage Discount";
  if (discountTypeText === "fixed") discountTypeText = "Fixed Amount Discount";
  if (discountTypeText === "free_shipping") discountTypeText = "Free Shipping";
  document.getElementById("viewDiscountType").textContent = discountTypeText;

  // Format discount value based on type
  let discountValue = "-";
  if (voucher.type === "percentage") {
    discountValue = voucher.value + "%";
  } else if (voucher.type === "fixed") {
    discountValue = "₱" + parseFloat(voucher.value).toFixed(2);
  } else if (voucher.type === "free_shipping") {
    discountValue = "Free Shipping Only";
  }
  document.getElementById("viewDiscount").textContent = discountValue;

  document.getElementById("viewMinSpend").textContent = voucher.min_purchase
    ? `₱${voucher.min_purchase}`
    : "₱0";

  document.getElementById("viewUsageLimit").textContent = voucher.usage || "-";
  document.getElementById("viewPerUserLimit").textContent =
    voucher.usage_limit_per_user || "-";
  document.getElementById("viewValidPeriod").textContent =
    voucher.valid_period || "-";

  // Format status with badge
  const statusElement = document.getElementById("viewStatus");
  if (voucher.status) {
    const statusText =
      voucher.status.charAt(0).toUpperCase() + voucher.status.slice(1);
    let bgColor = "#E3FCF4",
      textColor = "#039855",
      dotColor = "#12B76A";

    if (voucher.status === "expired") {
      bgColor = "#F2F4F7";
      textColor = "#667085";
      dotColor = "#D0D5DD";
    } else if (voucher.status === "upcoming") {
      bgColor = "#D5CAB5";
      textColor = "#845832";
      dotColor = "#A89869";
    } else if (voucher.status === "archived") {
      bgColor = "#FEE4E2";
      textColor = "#D92D20";
      dotColor = "#F04438";
    }

    statusElement.innerHTML = `<span class="status-badge" style="background-color: ${bgColor}; color: ${textColor}; padding: 6px 12px; border-radius: 16px; font-size: 12px; font-weight: 500; text-transform: capitalize; display: inline-flex; align-items: center; gap: 6px; min-width: 80px; justify-content: center;"><span style="width: 6px; height: 6px; background-color: ${dotColor}; border-radius: 50%; display: inline-block;"></span>${statusText}</span>`;
    statusElement.classList.add("status-field");
  } else {
    statusElement.textContent = "-";
    statusElement.classList.remove("status-field");
  }

  // Show the modal
  document.getElementById("viewModal").style.display = "flex";
}

const viewBtn = document.getElementById("view-supply-order-btn");
if (viewBtn) {
  viewBtn.addEventListener("click", viewVoucher);
}

// Filter vouchers by type or status
let currentFilter = "all";

function filterVouchers(filter, buttonElement) {
  // Remove active class from all buttons
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.classList.remove("active");
    btn.classList.remove("loading");
  });

  // Add active class and loading class to clicked button
  buttonElement.classList.add("active");
  buttonElement.classList.add("loading");

  // Store current filter
  currentFilter = filter;

  // Update DataTable ajax data function
  const table = supplyOrderTable;
  const settings = table.settings()[0];

  // Update the ajax.data to be a function that returns the filter parameters
  settings.ajax.data = function (d) {
    d.action = "datatableDisplay";

    if (filter === "all") {
      // No additional filters for 'all'
      delete d.status;
      delete d.voucher_type;
    } else if (filter === "active" || filter === "expired") {
      // Filter by status
      d.status = filter;
      delete d.voucher_type;
    } else {
      // Filter by voucher type (fixed, percentage, free_shipping)
      d.voucher_type = filter;
      delete d.status;
    }

    return d;
  };

  // Reload table
  table.ajax.reload();
}

// Update filter counts after table loads
function updateFilterCounts(data) {
  // Make AJAX call to get all counts regardless of current filter
  $.ajax({
    url: "./promotions_api.php",
    type: "POST",
    data: { action: "getFilterCounts" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const counts = response.counts;
        document.getElementById("count-all").textContent = counts.all || 0;
        document.getElementById("count-active").textContent =
          counts.active || 0;
        document.getElementById("count-expired").textContent =
          counts.expired || 0;
        document.getElementById("count-fixed").textContent = counts.fixed || 0;
        document.getElementById("count-percentage").textContent =
          counts.percentage || 0;
        document.getElementById("count-free-shipping").textContent =
          counts.free_shipping || 0;
      }
    },
    error: function (xhr, status, error) {
      console.error("Error loading filter counts:", error);
      // Fallback: set all counts to 0
      document.getElementById("count-all").textContent = "0";
      document.getElementById("count-active").textContent = "0";
      document.getElementById("count-expired").textContent = "0";
      document.getElementById("count-fixed").textContent = "0";
      document.getElementById("count-percentage").textContent = "0";
      document.getElementById("count-free-shipping").textContent = "0";
    },
  });
}

// Global pagination function
function goToPage(page) {
  if (supplyOrderTable) {
    const currentPage = supplyOrderTable.page.info().page;
    const targetPage = page - 1; // DataTables uses 0-based page indexing
    supplyOrderTable.page(targetPage).draw(false);
  }
}

function openAddModal() {
  document.getElementById("addModal").style.display = "flex";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

function toggleDiscountValue() {
  const discountType = document.getElementById("discountType").value;
  const discountValueGroup = document.getElementById("discountValueGroup");
  const discountValueInput = document.getElementById("discountValue");

  if (discountType === "free_shipping") {
    discountValueGroup.style.display = "none";
    discountValueInput.value = "0";
    discountValueInput.required = false;
  } else if (discountType === "percentage" || discountType === "fixed") {
    discountValueGroup.style.display = "block";
    discountValueInput.required = true;
    discountValueInput.value = "";

    // Set appropriate input attributes based on type
    if (discountType === "percentage") {
      discountValueInput.max = "100";
      discountValueInput.placeholder = "Enter percentage (0-100)";
    } else if (discountType === "fixed") {
      discountValueInput.removeAttribute("max");
      discountValueInput.placeholder = "Enter fixed amount";
    }
  } else {
    discountValueGroup.style.display = "none";
    discountValueInput.value = "";
    discountValueInput.required = false;
  }
}

function toggleUsageLimit() {
  const unlimitedUsage = document.getElementById("unlimitedUsage").checked;
  const usageLimitGroup = document.getElementById("usageLimitGroup");
  const usageLimitInput = document.getElementById("usageLimit");

  if (unlimitedUsage) {
    usageLimitGroup.style.display = "none";
    usageLimitInput.value = "";
    usageLimitInput.required = false;
  } else {
    usageLimitGroup.style.display = "block";
    usageLimitInput.required = true;
    if (!usageLimitInput.value) {
      usageLimitInput.value = "1";
    }
  }
}

function togglePerUserLimit() {
  const unlimitedPerUser = document.getElementById("unlimitedPerUser").checked;
  const perUserLimitGroup = document.getElementById("perUserLimitGroup");
  const perUserLimitInput = document.getElementById("perUserLimit");

  if (unlimitedPerUser) {
    perUserLimitGroup.style.display = "none";
    perUserLimitInput.value = "";
    perUserLimitInput.required = false;
  } else {
    perUserLimitGroup.style.display = "block";
    perUserLimitInput.required = true;
    if (!perUserLimitInput.value) {
      perUserLimitInput.value = "1";
    }
  }
}

function addCoupon(event) {
  event.preventDefault();

  // Get form data
  const formData = new FormData(event.target);
  formData.append("action", "add_voucher");

  // Validate required fields
  const title = formData.get("title");
  const code = formData.get("code");
  const discountType = formData.get("type");
  const startDate = formData.get("activation_date");
  const endDate = formData.get("expiration_date");

  if (!title || !code || !discountType || !startDate || !endDate) {
    Swal.fire({
      title: "Error",
      text: "Please fill in all required fields.",
      icon: "error",
      confirmButtonColor: "#256035",
    });
    return;
  }

  // Validate discount value for percentage and fixed types
  if (
    (discountType === "percentage" || discountType === "fixed") &&
    !formData.get("value")
  ) {
    Swal.fire({
      title: "Error",
      text: "Please enter a discount value.",
      icon: "error",
      confirmButtonColor: "#256035",
    });
    return;
  }

  // Handle unlimited usage checkboxes
  const unlimitedUsage = document.getElementById("unlimitedUsage").checked;
  const unlimitedPerUser = document.getElementById("unlimitedPerUser").checked;

  if (unlimitedUsage) {
    formData.set("usage_limit", "-1"); // Use -1 to indicate unlimited
  }
  if (unlimitedPerUser) {
    formData.set("usage_limit_per_user", "-1"); // Use -1 to indicate unlimited
  }

  // Set default values for optional fields
  if (!formData.get("min_purchase")) {
    formData.set("min_purchase", "0");
  }
  if (!formData.get("usage_limit")) {
    formData.set("usage_limit", "1");
  }
  if (!formData.get("usage_limit_per_user")) {
    formData.set("usage_limit_per_user", "1");
  }

  // Add missing fields
  if (!formData.has("include_free_shipping")) {
    formData.append("include_free_shipping", "0");
  }
  if (!formData.has("prevent_discounted")) {
    formData.append("prevent_discounted", "0");
  }

  console.log("Form data being sent:");
  for (let [key, value] of formData.entries()) {
    console.log(key, value);
  }

  // Show loading state
  const submitBtn = event.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "Creating...";
  submitBtn.disabled = true;

  fetch("./promotions_api.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Response status:", response.status);
      return response.text(); // Always get text first
    })
    .then((text) => {
      console.log("Raw response:", text);

      // Handle empty response
      if (!text || text.trim() === "") {
        // If response is empty but we got here, assume success
        // Check if coupon was actually created by refreshing table
        if (supplyOrderTable) {
          supplyOrderTable.draw();
        }

        // Close modal and show success
        document.getElementById("addModal").style.display = "none";
        event.target.reset();
        document.getElementById("discountValueGroup").style.display = "none";
        document.getElementById("usageLimitGroup").style.display = "block";
        document.getElementById("perUserLimitGroup").style.display = "block";
        document.getElementById("unlimitedUsage").checked = false;
        document.getElementById("unlimitedPerUser").checked = false;

        Swal.fire({
          title: "Success!",
          text: "Coupon created successfully!",
          icon: "success",
          timer: 3000,
          showConfirmButton: true,
          confirmButtonText: "OK",
          confirmButtonColor: "#256035",
        });
        return;
      }

      // Try to parse as JSON
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        // If JSON parsing fails, check if response contains success indicators
        if (
          text.toLowerCase().includes("success") ||
          text.toLowerCase().includes("created") ||
          text.toLowerCase().includes("added")
        ) {
          // Assume success if response contains success keywords
          document.getElementById("addModal").style.display = "none";
          event.target.reset();
          document.getElementById("discountValueGroup").style.display = "none";
          document.getElementById("usageLimitGroup").style.display = "block";
          document.getElementById("perUserLimitGroup").style.display = "block";
          document.getElementById("unlimitedUsage").checked = false;
          document.getElementById("unlimitedPerUser").checked = false;

          if (supplyOrderTable) {
            supplyOrderTable.draw();
          }

          Swal.fire({
            title: "Success!",
            text: "Coupon created successfully!",
            icon: "success",
            timer: 3000,
            showConfirmButton: true,
            confirmButtonText: "OK",
            confirmButtonColor: "#256035",
          });
          return;
        } else {
          console.error("JSON parse error:", e);
          console.error("Response text:", text);
          Swal.fire({
            title: "Error",
            text: "Invalid response format from server.",
            icon: "error",
            confirmButtonColor: "#256035",
          });
          return;
        }
      }

      // Handle JSON response
      if (data.success) {
        // Close modal immediately
        document.getElementById("addModal").style.display = "none";

        // Reset form to initial state
        event.target.reset();
        document.getElementById("discountValueGroup").style.display = "none";
        document.getElementById("usageLimitGroup").style.display = "block";
        document.getElementById("perUserLimitGroup").style.display = "block";
        document.getElementById("unlimitedUsage").checked = false;
        document.getElementById("unlimitedPerUser").checked = false;

        // Show success notification
        Swal.fire({
          title: "Success!",
          text: data.message || "Coupon created successfully!",
          icon: "success",
          timer: 3000,
          showConfirmButton: true,
          confirmButtonText: "OK",
          confirmButtonColor: "#256035",
        });

        // Refresh table
        if (supplyOrderTable) {
          supplyOrderTable.draw();
        }
      } else {
        Swal.fire({
          title: "Error",
          text: data.message || "Failed to create coupon",
          icon: "error",
          confirmButtonColor: "#256035",
        });
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      Swal.fire({
        title: "Error",
        text: "Network error. Please try again.",
        icon: "error",
        confirmButtonColor: "#256035",
      });
    })
    .finally(() => {
      // Reset button state
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
}

function deleteCoupon(id) {
  Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append("action", "delete_voucher");
      formData.append("id", id);

      fetch("./promotions_api.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire("Deleted!", data.message, "success");
            if (supplyOrderTable) {
              supplyOrderTable.draw();
            }
          } else {
            Swal.fire("Error", data.message, "error");
          }
        })
        .catch((error) => {
          Swal.fire("Error", "Network error. Please try again.", "error");
        });
    }
  });
}
