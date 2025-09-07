const VoucherTable = (function () {
  function initDataTable() {
    return new DataTable("#supply-order-table", {
      scrollX: true,
      responsive: true,
      processing: true,
      serverSide: true,
      ajax: {
        url: "./promotions_api.php",
        type: "post",
        data: { action: "datatableDisplay" },
      },
      columns: [
        { data: "id", visible: false },
        { data: "title" },
        { data: "application_method", render: renderMethod },
        { data: "code" },
        { data: "discount", render: renderDiscount },
        { data: "restrictions", render: renderRestrictions },
        { data: "usage", render: renderUsage },
        { data: "valid_period" },
        { data: "sale_channel" },
        { data: "status", render: renderStatus },
      ],
    });
  }

  function renderDiscount(data, type, row) {
    if (type === "display") {
      
      if (row.discount.includes("Free Shipping Only")) {
        return `<span class='status-badge upcoming'>Free Shipping Only</span>`;
      }
      let html = data;
      
      html = html.replace(/\$/g, "₱");
      if (
        parseInt(row.include_free_shipping) === 1 &&
        row.type !== "free_shipping"
      ) {
        html += `<br><span class='status-badge' style='background:#E3FCF4;color:#039855;font-size:12px;padding:2px 8px;border-radius:12px;'>Free Shipping</span>`;
      }
      return html;
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
  };
})();


const VoucherControls = (function () {
  function add_events() {
    const newBtn = document.getElementById("supply-order-new-btn");
    const addModal = document.getElementById("addModal");

    if (newBtn && addModal) {
      newBtn.addEventListener("click", function () {
        addModal.style.display = "flex";
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
    document.getElementById("reactivate-voucher-form").dataset.voucherId =
      voucher.id;
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
    const voucherId = form.dataset.voucherId;
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
    if (typeof Swal !== "undefined") {
      Swal.fire("Please select one voucher to view.", "warning");
  } else {
      alert("Please select one voucher to view.");
    }
    return;
  }
  VoucherTable.deselectAllRows();
  
  const voucher = selectedRows[0];
  Swal.fire({
    title: voucher.title,
    html: `
      <div style="text-align: left;">
        <p><strong>Code:</strong> ${voucher.code}</p>
        <p><strong>Type:</strong> ${voucher.type}</p>
        <p><strong>Discount:</strong> ${voucher.discount}</p>
        <p><strong>Status:</strong> ${voucher.status}</p>
        <p><strong>Valid Period:</strong> ${voucher.valid_period}</p>
      </div>
    `,
    showConfirmButton: true
  });
}

const viewBtn = document.getElementById("view-supply-order-btn");
if (viewBtn) {
  viewBtn.addEventListener("click", viewVoucher);
}

const VoucherFilter = (function () {
  const filter_btn = document.getElementById("filter-btn");
  const filterContainer = document.querySelector(".filter-container");
  const apply_btn = document.getElementById("apply-filters-btn");
  const reset_btn = document.getElementById("reset-filters-btn");

  const voucherTypeFilter = document.getElementById("voucher-type-filter");
  const valueMin = document.getElementById("value-min");
  const valueMax = document.getElementById("value-max");
  const valueRangeFieldset = document.getElementById("value-range-fieldset");
  const minPurchaseMin = document.getElementById("min-purchase-min");
  const minPurchaseMax = document.getElementById("min-purchase-max");
  const statusFilter = document.getElementById("status-filter");
  const validityFrom = document.getElementById("validity-from");
  const validityTo = document.getElementById("validity-to");
  const appliesToFilter = document.getElementById("applies-to-filter");
  const usageLimitMin = document.getElementById("usage-limit-min");
  const usageLimitMax = document.getElementById("usage-limit-max");
  const usageLimitUserMin = document.getElementById("usage-limit-user-min");
  const usageLimitUserMax = document.getElementById("usage-limit-user-max");
  const usageLimitType = document.getElementById("usage-limit-type");
  const usageLimitUserType = document.getElementById("usage-limit-user-type");

  function add_events() {
    filter_btn.addEventListener("click", toggle_filter_container);
    apply_btn.addEventListener("click", () => {
      draw_table_filter();
      toggle_filter_container();
    });
    reset_btn.addEventListener("click", reset);

    voucherTypeFilter.addEventListener("change", function () {
      if (voucherTypeFilter.value === "free_shipping") {
        valueRangeFieldset.style.display = "none";
      } else {
        valueRangeFieldset.style.display = "block";
      }
    });
    if (voucherTypeFilter.value === "free_shipping") {
      valueRangeFieldset.style.display = "none";
  } else {
      valueRangeFieldset.style.display = "block";
    }
    validityFrom.addEventListener(
      "change",
      () => (validityTo.min = validityFrom.value)
    );
    validityTo.addEventListener(
      "change",
      () => (validityFrom.max = validityTo.value)
    );
    appliesToFilter.value = "";
    usageLimitMin.value = "";
    usageLimitMax.value = "";
    usageLimitUserMin.value = "";
    usageLimitUserMax.value = "";
    usageLimitType.value = "";
    usageLimitUserType.value = "";
  }

  function toggle_filter_container() {
    if (
      filterContainer.style.display === "none" ||
      filterContainer.style.display === ""
    ) {
      filterContainer.style.display = "flex";
    } else {
      filterContainer.style.display = "none";
    }
  }

  function reset() {
    voucherTypeFilter.value = "";
    valueMin.value = "";
    valueMax.value = "";
    minPurchaseMin.value = "";
    minPurchaseMax.value = "";
    statusFilter.value = "";
    validityFrom.value = "";
    validityTo.value = "";
    valueRangeFieldset.style.display = "block";
    appliesToFilter.value = "";
    usageLimitMin.value = "";
    usageLimitMax.value = "";
    usageLimitUserMin.value = "";
    usageLimitUserMax.value = "";
    usageLimitType.value = "";
    usageLimitUserType.value = "";
    draw_table_filter();
    toggle_filter_container();
  }

  function draw_table_filter() {
    if (
      valueMin.value &&
      valueMax.value &&
      parseFloat(valueMin.value) > parseFloat(valueMax.value)
    ) {
      Swal.fire("Invalid Value Range!", "error");
      return;
    }
    if (
      minPurchaseMin.value &&
      minPurchaseMax.value &&
      parseFloat(minPurchaseMin.value) > parseFloat(minPurchaseMax.value)
    ) {
      Swal.fire("Invalid Min Purchase Range!", "error");
      return;
    }
    if (
      usageLimitMin.value &&
      usageLimitMax.value &&
      parseInt(usageLimitMin.value) > parseInt(usageLimitMax.value)
    ) {
      Swal.fire("Invalid Usage Limit Range!", "error");
      return;
    }
    if (
      usageLimitUserMin.value &&
      usageLimitUserMax.value &&
      parseInt(usageLimitUserMin.value) > parseInt(usageLimitUserMax.value)
    ) {
      Swal.fire("Invalid Usage Limit Per User Range!", "error");
      return;
    }
    supplyOrderTable.context[0].ajax.data = {
      action: "datatableDisplay",
      voucher_type: voucherTypeFilter.value,
      value_min: valueMin.value,
      value_max: valueMax.value,
      min_purchase_min: minPurchaseMin.value,
      min_purchase_max: minPurchaseMax.value,
      status: statusFilter.value,
      applies_to: appliesToFilter.value,
      usage_limit_min: usageLimitMin.value,
      usage_limit_max: usageLimitMax.value,
      usage_limit_type: usageLimitType.value,
      usage_limit_user_min: usageLimitUserMin.value,
      usage_limit_user_max: usageLimitUserMax.value,
      usage_limit_user_type: usageLimitUserType.value,
      validity_from: validityFrom.value,
      validity_to: validityTo.value,
    };
    supplyOrderTable.draw();
  }

  return {
    add_events,
    draw_table_filter,
  };
})();


if (VoucherFilter && VoucherFilter.add_events) {
  VoucherFilter.add_events();
}


function openAddModal() {
  document.getElementById('addModal').style.display = 'flex';
}

function openEditModal(id) {

  fetch('./get-coupon.php?id=' + id)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const coupon = data.coupon;
        document.getElementById('editId').value = coupon.id;
        document.getElementById('editTitle').value = coupon.title;
        document.getElementById('editCode').value = coupon.code;
        document.getElementById('editApplicationMethod').value = coupon.application_method || 'voucher_code';
        document.getElementById('editDiscountType').value = coupon.discount_type;
        document.getElementById('editDiscountValue').value = coupon.discount_value;
        document.getElementById('editMinSpend').value = coupon.min_spend;
        document.getElementById('editApplicableTo').value = coupon.applicable_to;
        document.getElementById('editUsageLimit').value = coupon.usage_limit || '';
        document.getElementById('editPerUserLimit').value = coupon.usage_limit_per_user || '';
        document.getElementById('editStartDate').value = coupon.start_date;
        document.getElementById('editEndDate').value = coupon.end_date;
        document.getElementById('editStatus').value = coupon.status;
        
       
        document.getElementById('editUnlimitedUsage').checked = !coupon.usage_limit;
        document.getElementById('editUnlimitedPerUser').checked = !coupon.usage_limit_per_user;
        
        toggleEditUsageLimit();
        toggleEditPerUserLimit();
        toggleEditDiscountValue();
        
        document.getElementById('editModal').style.display = 'flex';
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    })
    .catch(error => {
      Swal.fire('Error', 'Failed to load coupon data', 'error');
    });
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}

function toggleDiscountValue() {
  const discountType = document.getElementById('discountType').value;
  const discountValueGroup = document.getElementById('discountValueGroup');
  
  if (discountType === 'free_shipping') {
    discountValueGroup.style.display = 'none';
    document.getElementById('discountValue').value = '';
  } else {
    discountValueGroup.style.display = 'block';
  }
}

function toggleEditDiscountValue() {
  const discountType = document.getElementById('editDiscountType').value;
  const discountValueGroup = document.getElementById('editDiscountValueGroup');
  
  if (discountType === 'free_shipping') {
    discountValueGroup.style.display = 'none';
    document.getElementById('editDiscountValue').value = '';
  } else {
    discountValueGroup.style.display = 'block';
  }
}

function toggleUsageLimit() {
  const unlimitedUsage = document.getElementById('unlimitedUsage').checked;
  const usageLimitGroup = document.getElementById('usageLimitGroup');
  
  if (unlimitedUsage) {
    usageLimitGroup.style.display = 'none';
    document.getElementById('usageLimit').value = '';
  } else {
    usageLimitGroup.style.display = 'block';
  }
}

function toggleEditUsageLimit() {
  const unlimitedUsage = document.getElementById('editUnlimitedUsage').checked;
  const usageLimitGroup = document.getElementById('editUsageLimitGroup');
  
  if (unlimitedUsage) {
    usageLimitGroup.style.display = 'none';
    document.getElementById('editUsageLimit').value = '';
  } else {
    usageLimitGroup.style.display = 'block';
  }
}

function togglePerUserLimit() {
  const unlimitedPerUser = document.getElementById('unlimitedPerUser').checked;
  const perUserLimitGroup = document.getElementById('perUserLimitGroup');
  
  if (unlimitedPerUser) {
    perUserLimitGroup.style.display = 'none';
    document.getElementById('perUserLimit').value = '';
  } else {
    perUserLimitGroup.style.display = 'block';
  }
}

function toggleEditPerUserLimit() {
  const unlimitedPerUser = document.getElementById('editUnlimitedPerUser').checked;
  const perUserLimitGroup = document.getElementById('editPerUserLimitGroup');
  
  if (unlimitedPerUser) {
    perUserLimitGroup.style.display = 'none';
    document.getElementById('editPerUserLimit').value = '';
  } else {
    perUserLimitGroup.style.display = 'block';
  }
}

function addCoupon(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  formData.append('action', 'add_voucher');
  
 
  if (!formData.has('include_free_shipping')) formData.append('include_free_shipping', 0);
  if (!formData.has('prevent_discounted')) formData.append('prevent_discounted', 0);
  
 
  console.log('Form data being sent:');
  for (let [key, value] of formData.entries()) {
    console.log(key, value);
  }
  
  fetch('./promotions_api.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Response status:', response.status);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.text(); 
  })
  .then(text => {
    console.log('Raw response:', text);
    try {
      const data = JSON.parse(text);
      if (data.success) {
        Swal.fire('Success', data.message, 'success');
        document.getElementById('addModal').style.display = 'none';
        event.target.reset();
        if (supplyOrderTable) {
          supplyOrderTable.draw();
        }
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    } catch (e) {
      console.error('JSON parse error:', e);
      console.error('Response text:', text);
      Swal.fire('Error', 'Invalid response from server', 'error');
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    Swal.fire('Error', 'Network error. Please try again.', 'error');
  });
}

function updateCoupon(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  formData.append('action', 'update_voucher');
  
 
  if (!formData.has('include_free_shipping')) formData.append('include_free_shipping', 0);
  if (!formData.has('prevent_discounted')) formData.append('prevent_discounted', 0);
  
  fetch('./promotions_api.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
      Swal.fire('Success', data.message, 'success');
      document.getElementById('editModal').style.display = 'none';
      if (supplyOrderTable) {
        supplyOrderTable.draw();
      }
      } else {
      Swal.fire('Error', data.message, 'error');
    }
  })
  .catch(error => {
    Swal.fire('Error', 'Network error. Please try again.', 'error');
  });
}

function deleteCoupon(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append('action', 'delete_voucher');
      formData.append('id', id);
      
      fetch('./promotions_api.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
      if (data.success) {
          Swal.fire('Deleted!', data.message, 'success');
          if (supplyOrderTable) {
            supplyOrderTable.draw();
          }
      } else {
          Swal.fire('Error', data.message, 'error');
        }
      })
      .catch(error => {
        Swal.fire('Error', 'Network error. Please try again.', 'error');
      });
    }
  });
    }
