// Promotions Management JavaScript
let currentSort = "title";
let sortOrder = "ASC";
let selectedRows = [];

// Initialize the page
document.addEventListener("DOMContentLoaded", function () {
  // Set default dates
  const today = new Date().toISOString().split("T")[0];
  const nextMonth = new Date();
  nextMonth.setMonth(nextMonth.getMonth() + 1);
  const nextMonthStr = nextMonth.toISOString().split("T")[0];

  document.getElementById("startDate").value = today;
  document.getElementById("endDate").value = nextMonthStr;

  updateBulkActions();
});

// Search functionality
function searchCoupons() {
  const searchTerm = document.getElementById("searchInput").value;
  const url = new URL(window.location);

  if (searchTerm) {
    url.searchParams.set("search", searchTerm);
  } else {
    url.searchParams.delete("search");
  }

  url.searchParams.delete("page"); // Reset to first page
  window.location.href = url.toString();
}

// Sort functionality
function toggleSort(column) {
  const sortBtn = document.getElementById("sort-" + column);

  // Remove active class from all sort buttons
  document.querySelectorAll(".sort-btn").forEach((btn) => {
    btn.classList.remove("active", "desc");
  });

  // Set new sort
  if (currentSort === column && sortOrder === "ASC") {
    sortOrder = "DESC";
    sortBtn.classList.add("desc");
  } else {
    sortOrder = "ASC";
    currentSort = column;
  }

  sortBtn.classList.add("active");

  // Apply sort
  const url = new URL(window.location);
  url.searchParams.set("sort", column);
  url.searchParams.set("order", sortOrder);
  url.searchParams.delete("page"); // Reset to first page
  window.location.href = url.toString();
}

// Modal functions
function openAddModal() {
  document.getElementById("addModal").style.display = "block";
  document.body.style.overflow = "hidden";

  // Reset form
  document.getElementById("addCouponForm").reset();

  // Set default dates
  const today = new Date().toISOString().split("T")[0];
  const nextMonth = new Date();
  nextMonth.setMonth(nextMonth.getMonth() + 1);
  const nextMonthStr = nextMonth.toISOString().split("T")[0];

  document.getElementById("startDate").value = today;
  document.getElementById("endDate").value = nextMonthStr;

  // Reset switches
  document.getElementById("unlimitedUsage").checked = false;
  document.getElementById("unlimitedPerUser").checked = false;
  toggleUsageLimit();
  togglePerUserLimit();
  toggleDiscountValue();
}

function openEditModal(id) {
  document.getElementById("editModal").style.display = "block";
  document.body.style.overflow = "hidden";

  // Fetch coupon data
  fetch("get-coupon.php?id=" + id)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const coupon = data.coupon;

        // Populate form
        document.getElementById("editId").value = coupon.id;
        document.getElementById("editTitle").value = coupon.title;
        document.getElementById("editCode").value = coupon.code;
        document.getElementById("editDiscountType").value =
          coupon.discount_type;
        document.getElementById("editDiscountValue").value =
          coupon.discount_value;
        document.getElementById("editMinSpend").value = coupon.min_spend;
        document.getElementById("editApplicableTo").value =
          coupon.applicable_to;
        document.getElementById("editStartDate").value = coupon.start_date;
        document.getElementById("editEndDate").value = coupon.end_date;
        document.getElementById("editStatus").value = coupon.status;

        // Handle usage limits
        if (coupon.usage_limit) {
          document.getElementById("editUnlimitedUsage").checked = false;
          document.getElementById("editUsageLimit").value = coupon.usage_limit;
        } else {
          document.getElementById("editUnlimitedUsage").checked = true;
        }

        if (coupon.usage_limit_per_user) {
          document.getElementById("editUnlimitedPerUser").checked = false;
          document.getElementById("editPerUserLimit").value =
            coupon.usage_limit_per_user;
        } else {
          document.getElementById("editUnlimitedPerUser").checked = true;
        }

        // Update UI
        toggleEditUsageLimit();
        toggleEditPerUserLimit();
        toggleEditDiscountValue();
      } else {
        alert("Error loading coupon data: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error loading coupon data");
    });
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
  document.body.style.overflow = "auto";
}

// Close modal when clicking outside
window.onclick = function (event) {
  const addModal = document.getElementById("addModal");
  const editModal = document.getElementById("editModal");

  if (event.target === addModal) {
    closeModal("addModal");
  }
  if (event.target === editModal) {
    closeModal("editModal");
  }
};

// Toggle discount value field
function toggleDiscountValue() {
  const discountType = document.getElementById("discountType").value;
  const discountValueGroup = document.getElementById("discountValueGroup");
  const discountValue = document.getElementById("discountValue");

  if (discountType === "shipping") {
    discountValueGroup.style.display = "none";
    discountValue.required = false;
    discountValue.value = "0";
  } else {
    discountValueGroup.style.display = "block";
    discountValue.required = true;

    if (discountType === "percentage") {
      discountValue.max = "100";
      discountValue.previousElementSibling.textContent =
        "Discount Percentage *";
    } else {
      discountValue.removeAttribute("max");
      discountValue.previousElementSibling.textContent =
        "Discount Amount (₱) *";
    }
  }
}

function toggleEditDiscountValue() {
  const discountType = document.getElementById("editDiscountType").value;
  const discountValueGroup = document.getElementById("editDiscountValueGroup");
  const discountValue = document.getElementById("editDiscountValue");

  if (discountType === "shipping") {
    discountValueGroup.style.display = "none";
    discountValue.required = false;
    discountValue.value = "0";
  } else {
    discountValueGroup.style.display = "block";
    discountValue.required = true;

    if (discountType === "percentage") {
      discountValue.max = "100";
      discountValue.previousElementSibling.textContent =
        "Discount Percentage *";
    } else {
      discountValue.removeAttribute("max");
      discountValue.previousElementSibling.textContent =
        "Discount Amount (₱) *";
    }
  }
}

// Toggle usage limit
function toggleUsageLimit() {
  const unlimited = document.getElementById("unlimitedUsage").checked;
  const usageLimitGroup = document.getElementById("usageLimitGroup");
  const usageLimit = document.getElementById("usageLimit");

  if (unlimited) {
    usageLimitGroup.style.display = "none";
    usageLimit.required = false;
    usageLimit.value = "";
  } else {
    usageLimitGroup.style.display = "block";
    usageLimit.required = true;
  }
}

function toggleEditUsageLimit() {
  const unlimited = document.getElementById("editUnlimitedUsage").checked;
  const usageLimitGroup = document.getElementById("editUsageLimitGroup");
  const usageLimit = document.getElementById("editUsageLimit");

  if (unlimited) {
    usageLimitGroup.style.display = "none";
    usageLimit.required = false;
    usageLimit.value = "";
  } else {
    usageLimitGroup.style.display = "block";
    usageLimit.required = true;
  }
}

// Toggle per user limit
function togglePerUserLimit() {
  const unlimited = document.getElementById("unlimitedPerUser").checked;
  const perUserLimitGroup = document.getElementById("perUserLimitGroup");
  const perUserLimit = document.getElementById("perUserLimit");

  if (unlimited) {
    perUserLimitGroup.style.display = "none";
    perUserLimit.required = false;
    perUserLimit.value = "";
  } else {
    perUserLimitGroup.style.display = "block";
    perUserLimit.required = true;
  }
}

function toggleEditPerUserLimit() {
  const unlimited = document.getElementById("editUnlimitedPerUser").checked;
  const perUserLimitGroup = document.getElementById("editPerUserLimitGroup");
  const perUserLimit = document.getElementById("editPerUserLimit");

  if (unlimited) {
    perUserLimitGroup.style.display = "none";
    perUserLimit.required = false;
    perUserLimit.value = "";
  } else {
    perUserLimitGroup.style.display = "block";
    perUserLimit.required = true;
  }
}

// Select all functionality
function toggleSelectAll() {
  const selectAllCheckbox =
    document.getElementById("selectAll") ||
    document.getElementById("headerSelectAll");
  const rowCheckboxes = document.querySelectorAll(".row-select");

  rowCheckboxes.forEach((checkbox) => {
    checkbox.checked = selectAllCheckbox.checked;
  });

  updateBulkActions();
}

function updateBulkActions() {
  const selectedCheckboxes = document.querySelectorAll(".row-select:checked");
  const bulkDeleteBtn = document.getElementById("bulkDeleteBtn");
  const selectAllCheckbox = document.getElementById("selectAll");
  const headerSelectAllCheckbox = document.getElementById("headerSelectAll");
  const totalRows = document.querySelectorAll(".row-select").length;

  selectedRows = Array.from(selectedCheckboxes).map((cb) => cb.value);

  if (selectedRows.length > 0) {
    bulkDeleteBtn.style.display = "inline-flex";
    bulkDeleteBtn.textContent = `Delete Selected (${selectedRows.length})`;
  } else {
    bulkDeleteBtn.style.display = "none";
  }

  // Update select all checkboxes
  if (selectAllCheckbox) {
    selectAllCheckbox.indeterminate =
      selectedRows.length > 0 && selectedRows.length < totalRows;
    selectAllCheckbox.checked =
      selectedRows.length === totalRows && totalRows > 0;
  }

  if (headerSelectAllCheckbox) {
    headerSelectAllCheckbox.indeterminate =
      selectedRows.length > 0 && selectedRows.length < totalRows;
    headerSelectAllCheckbox.checked =
      selectedRows.length === totalRows && totalRows > 0;
  }
}

// Add coupon
function addCoupon(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  const submitBtn = event.target.querySelector('button[type="submit"]');

  submitBtn.disabled = true;
  submitBtn.textContent = "Creating...";

  fetch("add-coupon.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Coupon created successfully!");
        closeModal("addModal");
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error creating coupon");
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = "Create Coupon";
    });
}

// Update coupon
function updateCoupon(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  const submitBtn = event.target.querySelector('button[type="submit"]');

  submitBtn.disabled = true;
  submitBtn.textContent = "Updating...";

  fetch("update-coupon.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Coupon updated successfully!");
        closeModal("editModal");
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error updating coupon");
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = "Update Coupon";
    });
}

// Delete single coupon
function deleteCoupon(id) {
  if (!confirm("Are you sure you want to delete this coupon?")) {
    return;
  }

  fetch("delete-coupon.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: id }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Coupon deleted successfully!");
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error deleting coupon");
    });
}

// Bulk delete
function bulkDelete() {
  if (selectedRows.length === 0) {
    alert("Please select at least one coupon to delete.");
    return;
  }

  if (
    !confirm(
      `Are you sure you want to delete ${selectedRows.length} coupon(s)?`
    )
  ) {
    return;
  }

  fetch("delete-coupon.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ ids: selectedRows }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Coupons deleted successfully!");
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error deleting coupons");
    });
}

// Form validation
document.addEventListener("DOMContentLoaded", function () {
  // Code validation - uppercase and remove spaces
  const codeInputs = ["code", "editCode"];
  codeInputs.forEach((inputId) => {
    const input = document.getElementById(inputId);
    if (input) {
      input.addEventListener("input", function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
      });
    }
  });

  // Date validation
  const forms = ["addCouponForm", "editCouponForm"];
  forms.forEach((formId) => {
    const form = document.getElementById(formId);
    if (form) {
      form.addEventListener("submit", function (event) {
        const prefix = formId === "addCouponForm" ? "" : "edit";
        const startDate = document.getElementById(prefix + "StartDate").value;
        const endDate = document.getElementById(prefix + "EndDate").value;

        if (new Date(startDate) >= new Date(endDate)) {
          event.preventDefault();
          alert("End date must be after start date.");
          return false;
        }

        // Validate discount value for percentage
        const discountType = document.getElementById(
          prefix + "DiscountType"
        ).value;
        const discountValue = document.getElementById(
          prefix + "DiscountValue"
        ).value;

        if (discountType === "percentage" && parseFloat(discountValue) > 100) {
          event.preventDefault();
          alert("Percentage discount cannot be more than 100%.");
          return false;
        }

        if (discountType !== "shipping" && parseFloat(discountValue) <= 0) {
          event.preventDefault();
          alert("Discount value must be greater than 0.");
          return false;
        }
      });
    }
  });
});
