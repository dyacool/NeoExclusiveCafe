// Shopping Cart JavaScript - Duplicated from cart.php functionality

let currentCartId = null;

function showConfirmation(message, isError = false) {
  const popup = document.getElementById("confirmationPopup");
  popup.textContent = message;
  popup.className = "confirmation-popup" + (isError ? " error" : "");
  popup.classList.add("show");

  setTimeout(() => {
    popup.classList.remove("show");
    popup.classList.add("hide");
    setTimeout(() => {
      popup.classList.remove("hide");
    }, 300);
  }, 3000);
}

function showConfirmationModal(cartId) {
  currentCartId = cartId;
  document.getElementById("confirmationModal").style.display = "block";
}

function closeConfirmationModal() {
  document.getElementById("confirmationModal").style.display = "none";
  currentCartId = null;
}

function checkMixedSelection() {
  const pickupChecked =
    document.querySelectorAll(".pickup-checkbox:checked").length > 0;
  const deliveryChecked =
    document.querySelectorAll(".delivery-checkbox:checked").length > 0;
  const warning = document.getElementById("mixedSelectionWarning");

  if (pickupChecked && deliveryChecked) {
    warning.style.display = "block";
    return true; // Mixed selection detected
  } else {
    warning.style.display = "none";
    return false; // No mixed selection
  }
}

function preventMixedSelection(clickedCheckbox) {
  const isPickup = clickedCheckbox.classList.contains("pickup-checkbox");
  const isDelivery = clickedCheckbox.classList.contains("delivery-checkbox");

  if (isPickup) {
    // If user is trying to check a pickup item, check if delivery items are already selected
    const deliveryChecked =
      document.querySelectorAll(".delivery-checkbox:checked").length > 0;
    if (deliveryChecked) {
      showConfirmation(
        "⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck delivery items first.",
        true
      );
      clickedCheckbox.checked = false;
      return false;
    }
  } else if (isDelivery) {
    // If user is trying to check a delivery item, check if pickup items are already selected
    const pickupChecked =
      document.querySelectorAll(".pickup-checkbox:checked").length > 0;
    if (pickupChecked) {
      showConfirmation(
        "⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck pickup items first.",
        true
      );
      clickedCheckbox.checked = false;
      return false;
    }
  }

  return true;
}

document.addEventListener("DOMContentLoaded", function () {
  // Initialize section checkboxes
  const selectAllPickup = document.getElementById("selectAllPickup");
  const selectAllDelivery = document.getElementById("selectAllDelivery");
  const pickupCheckboxes = document.querySelectorAll(".pickup-checkbox");
  const deliveryCheckboxes = document.querySelectorAll(".delivery-checkbox");

  // Set up section select all event listeners
  if (selectAllPickup) {
    selectAllPickup.addEventListener("change", function () {
      if (this.checked) {
        // Check if delivery items are already selected
        const deliveryChecked =
          document.querySelectorAll(".delivery-checkbox:checked").length > 0;
        if (deliveryChecked) {
          showConfirmation(
            "⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck delivery items first.",
            true
          );
          this.checked = false;
          return;
        }

        // Warn user about Select All limitation
        showConfirmation(
          "⚠️ Select All Notice: When all pickup items are selected, you can only delete them (not checkout) due to potentially incompatible pickup days. Select specific items for checkout.",
          true
        );
      }

      const visiblePickupCheckboxes = Array.from(pickupCheckboxes).filter(
        (cb) => {
          const row = cb.closest("tr");
          return row && row.style.display !== "none";
        }
      );

      visiblePickupCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllPickup.checked;
      });
      updateSubtotal();
      checkMixedSelection();

      // Apply smart filtering after select all
      if (this.checked) {
        applySmartFilter();
      }
    });
  }

  if (selectAllDelivery) {
    selectAllDelivery.addEventListener("change", function () {
      if (this.checked) {
        // Check if pickup items are already selected
        const pickupChecked =
          document.querySelectorAll(".pickup-checkbox:checked").length > 0;
        if (pickupChecked) {
          showConfirmation(
            "⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck pickup items first.",
            true
          );
          this.checked = false;
          return;
        }

        // Warn user about Select All limitation
        showConfirmation(
          "⚠️ Select All Notice: When all delivery items are selected, you can only delete them (not checkout) due to potentially incompatible delivery days. Select specific items for checkout.",
          true
        );
      }

      const visibleDeliveryCheckboxes = Array.from(deliveryCheckboxes).filter(
        (cb) => {
          const row = cb.closest("tr");
          return row && row.style.display !== "none";
        }
      );

      visibleDeliveryCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllDelivery.checked;
      });
      updateSubtotal();
      checkMixedSelection();

      // Apply smart filtering after select all
      if (this.checked) {
        applySmartFilter(); // This function will now handle both pickup and delivery
      }
    });
  }

  // Setup individual checkbox listeners
  const allItemCheckboxes = document.querySelectorAll(".item-checkbox");
  allItemCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      if (this.checked) {
        // Check if this would create a mixed selection
        if (!preventMixedSelection(this)) {
          return; // Checkbox was unchecked by preventMixedSelection
        }
      }
      updateSubtotal();
      updateSectionCheckboxes();
      checkMixedSelection();

      // Apply smart filtering for both pickup and delivery items
      if (
        this.classList.contains("pickup-checkbox") ||
        this.classList.contains("delivery-checkbox")
      ) {
        applySmartFilter();
      }
    });
  });

  // Setup confirmation modal button
  const confirmBtn = document.getElementById("confirmRemoveBtn");
  if (confirmBtn) {
    confirmBtn.addEventListener("click", function () {
      if (currentCartId) {
        removeItem(currentCartId);
        closeConfirmationModal();
      }
    });
  }

  // Initialize subtotal
  updateSubtotal();
});

function updateSectionCheckboxes() {
  const pickupCheckboxes = document.querySelectorAll(".pickup-checkbox");
  const deliveryCheckboxes = document.querySelectorAll(".delivery-checkbox");
  const selectAllPickup = document.getElementById("selectAllPickup");
  const selectAllDelivery = document.getElementById("selectAllDelivery");

  const checkedPickup = document.querySelectorAll(".pickup-checkbox:checked");
  const checkedDelivery = document.querySelectorAll(
    ".delivery-checkbox:checked"
  );

  if (selectAllPickup) {
    selectAllPickup.checked =
      checkedPickup.length === pickupCheckboxes.length &&
      pickupCheckboxes.length > 0;
  }
  if (selectAllDelivery) {
    selectAllDelivery.checked =
      checkedDelivery.length === deliveryCheckboxes.length &&
      deliveryCheckboxes.length > 0;
  }
}

function updateSubtotal() {
  let subtotal = 0;
  const selectedCartIds = [];

  document.querySelectorAll(".item-checkbox:checked").forEach((checkbox) => {
    const row = checkbox.closest("tr");
    const price = parseFloat(row.dataset.price);
    const quantity = parseInt(row.dataset.quantity);
    subtotal += price * quantity;
    selectedCartIds.push(checkbox.value);
  });

  document.getElementById("displaySubtotal").textContent = subtotal.toFixed(2);
  document.getElementById("subtotalInput").value = subtotal;
  document.getElementById("cartItemsInput").value = selectedCartIds.join(",");
}

function validateCart() {
  // Check if terms are accepted
  if (!document.getElementById("termsCheckbox").checked) {
    showConfirmation("Please accept the Terms and Conditions", true);
    return false;
  }

  // Check if any item is selected
  const selectedItems = document.querySelectorAll(".item-checkbox:checked");

  if (selectedItems.length === 0) {
    showConfirmation("Please select at least one item to checkout", true);
    return false;
  }

  // Check for mixed selection
  if (checkMixedSelection()) {
    showConfirmation(
      "You cannot mix Pickup and Delivery products in the same order. Please select items from only one category.",
      true
    );
    return false;
  }

  // Check stock availability for selected items only
  let hasInsufficientStock = false;
  selectedItems.forEach((checkbox) => {
    const row = checkbox.closest("tr");
    const quantity = parseInt(row.dataset.quantity);
    const stock = parseInt(row.dataset.stock);
    if (quantity > stock) {
      hasInsufficientStock = true;
      showConfirmation(
        `Insufficient stock for ${
          row.querySelector("td:nth-child(3)").textContent
        }. Available: ${stock}`,
        true
      );
    }
  });

  if (hasInsufficientStock) {
    return false;
  }

  // Check day compatibility for both pickup and delivery items
  const selectedPickupItems = Array.from(selectedItems).filter((checkbox) =>
    checkbox.classList.contains("pickup-checkbox")
  );
  const selectedDeliveryItems = Array.from(selectedItems).filter((checkbox) =>
    checkbox.classList.contains("delivery-checkbox")
  );

  // Check pickup day compatibility
  if (selectedPickupItems.length > 1) {
    const pickupDaysList = selectedPickupItems
      .map((checkbox) => {
        const row = checkbox.closest("tr");
        return row.dataset.days || "";
      })
      .filter((days) => days);

    if (pickupDaysList.length > 1) {
      const commonDays = getCommonDays(pickupDaysList);
      if (commonDays.length === 0) {
        showConfirmation(
          "⚠️ Pickup Day Conflict: The selected pickup items have no common pickup days. Please select items that can be picked up on the same day.",
          true
        );
        return false;
      }
    }
  }

  // Check delivery day compatibility
  if (selectedDeliveryItems.length > 1) {
    const deliveryDaysList = selectedDeliveryItems
      .map((checkbox) => {
        const row = checkbox.closest("tr");
        return row.dataset.days || "";
      })
      .filter((days) => days);

    if (deliveryDaysList.length > 1) {
      const commonDays = getCommonDays(deliveryDaysList);
      if (commonDays.length === 0) {
        showConfirmation(
          "⚠️ Delivery Day Conflict: The selected delivery items have no common delivery days. Please select items that can be delivered on the same day.",
          true
        );
        return false;
      }
    }
  }

  // Check if Select All was used for pickup (prevent checkout)
  const selectAllPickup = document.getElementById("selectAllPickup");
  if (
    selectAllPickup &&
    selectAllPickup.checked &&
    selectedPickupItems.length > 1
  ) {
    showConfirmation(
      "⚠️ Select All Limitation: You cannot checkout when all pickup items are selected due to potential day conflicts. Please select specific compatible items.",
      true
    );
    return false;
  }

  // Check if Select All was used for delivery (prevent checkout)
  const selectAllDelivery = document.getElementById("selectAllDelivery");
  if (
    selectAllDelivery &&
    selectAllDelivery.checked &&
    selectedDeliveryItems.length > 1
  ) {
    showConfirmation(
      "⚠️ Select All Limitation: You cannot checkout when all delivery items are selected due to potential day conflicts. Please select specific compatible items.",
      true
    );
    return false;
  }

  return true;
}

function updateQuantity(cartId, newQuantity) {
  if (newQuantity < 1) return;

  const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
  const stock = parseInt(row.dataset.stock);

  if (newQuantity > stock) {
    showConfirmation(`Cannot exceed available stock of ${stock}`, true);
    return;
  }

  fetch("update-cart.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `cart_id=${cartId}&quantity=${newQuantity}`,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) location.reload();
      else
        showConfirmation(
          "Error: " + (data.error || "Failed to update quantity"),
          true
        );
    })
    .catch((err) => {
      console.error("Error:", err);
      showConfirmation("An error occurred while updating the cart", true);
    });
}

function removeItem(cartId) {
  fetch("remove-from-cart.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `cart_id=${cartId}`,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showConfirmation("Item removed successfully");
        setTimeout(() => location.reload(), 1000);
      } else showConfirmation("Error: " + data.error, true);
    })
    .catch((err) => {
      console.error("Error:", err);
      showConfirmation("An error occurred while removing the item", true);
    });
}

// Function to filter items by selected day
function filterByDay(sectionType) {
  const filterId = sectionType === "pickup" ? "pickupDaysFilter" : "daysFilter";
  const selectedDay = document.getElementById(filterId).value;
  const table = document.querySelector(`.${sectionType}-table`);

  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");
  let visibleRows = 0;

  rows.forEach((row) => {
    const availableDays = row.dataset.days || "";

    if (selectedDay === "" || availableDays.includes(selectedDay)) {
      row.style.display = "";
      visibleRows++;
    } else {
      row.style.display = "none";
      // Uncheck the checkbox if the row is hidden
      const checkbox = row.querySelector(".item-checkbox");
      if (checkbox && checkbox.checked) {
        checkbox.checked = false;
        updateSubtotal();
      }
    }
  });

  // Update the "Select All" checkbox state
  updateSelectAllState();

  // Show/hide empty message if needed
  const emptyMessage = table.parentNode.querySelector(".empty-message");
  if (visibleRows === 0 && !emptyMessage) {
    const message = document.createElement("div");
    message.className = "empty-message";
    message.style.cssText =
      "text-align: center; padding: 20px; color: #666; font-style: italic;";
    const sectionText = sectionType === "pickup" ? "pickup" : "delivery";
    message.textContent = selectedDay
      ? `No ${sectionText} items available for ${selectedDay}`
      : `No ${sectionText} items in cart`;
    table.parentNode.appendChild(message);
  } else if (visibleRows > 0 && emptyMessage) {
    emptyMessage.remove();
  }
}

// Update selectAllInSection to only select visible rows
function selectAllInSection(section) {
  const sectionCheckbox = document.getElementById(
    `selectAll${section.charAt(0).toUpperCase() + section.slice(1)}`
  );
  const checkboxes = document.querySelectorAll(`.${section}-checkbox`);

  checkboxes.forEach((checkbox) => {
    const row = checkbox.closest("tr");
    // Only select checkboxes in visible rows
    if (row && row.style.display !== "none") {
      checkbox.checked = sectionCheckbox.checked;
    }
  });

  updateSubtotal();
}

// Update the select all state based on visible items only
function updateSelectAllState() {
  const pickupCheckboxes = Array.from(
    document.querySelectorAll(".pickup-checkbox")
  ).filter((cb) => {
    const row = cb.closest("tr");
    return row && row.style.display !== "none";
  });

  const deliveryCheckboxes = Array.from(
    document.querySelectorAll(".delivery-checkbox")
  ).filter((cb) => {
    const row = cb.closest("tr");
    return row && row.style.display !== "none";
  });

  const selectAllPickup = document.getElementById("selectAllPickup");
  const selectAllDelivery = document.getElementById("selectAllDelivery");

  if (selectAllPickup && pickupCheckboxes.length > 0) {
    const checkedCount = pickupCheckboxes.filter((cb) => cb.checked).length;
    selectAllPickup.checked = checkedCount === pickupCheckboxes.length;
    selectAllPickup.indeterminate =
      checkedCount > 0 && checkedCount < pickupCheckboxes.length;
  }

  if (selectAllDelivery && deliveryCheckboxes.length > 0) {
    const checkedCount = deliveryCheckboxes.filter((cb) => cb.checked).length;
    selectAllDelivery.checked = checkedCount === deliveryCheckboxes.length;
    selectAllDelivery.indeterminate =
      checkedCount > 0 && checkedCount < deliveryCheckboxes.length;
  }
}

// Function to get common days between multiple products
function getCommonDays(daysList) {
  if (daysList.length === 0) return [];
  if (daysList.length === 1) {
    // Return the single day string as an array
    return daysList[0]
      .split(", ")
      .map((day) => day.trim())
      .filter((day) => day);
  }

  // Convert day strings to arrays
  const dayArrays = daysList.map((days) =>
    days
      .split(", ")
      .map((day) => day.trim())
      .filter((day) => day)
  );

  // Find intersection of all day arrays
  return dayArrays.reduce((common, current) =>
    common.filter((day) => current.includes(day))
  );
}

// Function to check if two products have common days
function hasCommonDays(days1, days2) {
  if (!days1 || !days2) return false;

  const array1 = days1
    .split(", ")
    .map((day) => day.trim())
    .filter((day) => day);
  const array2 = days2
    .split(", ")
    .map((day) => day.trim())
    .filter((day) => day);

  return array1.some((day) => array2.includes(day));
}

// Smart filtering based on selected products (both pickup and delivery)
function applySmartFilter() {
  const pickupTable = document.querySelector(".pickup-table");
  const deliveryTable = document.querySelector(".delivery-table");

  if (!pickupTable && !deliveryTable) return;

  // Handle pickup table filtering
  if (pickupTable) {
    const pickupRows = pickupTable.querySelectorAll("tbody tr");
    const selectedPickupRows = Array.from(pickupRows).filter((row) => {
      const checkbox = row.querySelector(".pickup-checkbox");
      return checkbox && checkbox.checked;
    });

    if (selectedPickupRows.length > 0) {
      const pickupDaysList = selectedPickupRows
        .map((row) => row.dataset.days || "")
        .filter((days) => days);
      const commonPickupDays =
        pickupDaysList.length > 0 ? getCommonDays(pickupDaysList) : [];

      pickupRows.forEach((row) => {
        const checkbox = row.querySelector(".pickup-checkbox");
        const rowDays = row.dataset.days || "";

        // Always show selected rows
        if (checkbox && checkbox.checked) {
          row.style.display = "";
          row.classList.remove("filtered-out");
          return;
        }

        // For unselected rows, check if they have any common days
        if (
          commonPickupDays.length === 0 ||
          hasCommonDays(rowDays, commonPickupDays.join(", "))
        ) {
          row.style.display = "";
          row.classList.remove("filtered-out");
        } else {
          row.style.display = "none";
          row.classList.add("filtered-out");
        }
      });
    } else {
      // If no pickup items selected, show all pickup items
      pickupRows.forEach((row) => {
        row.style.display = "";
        row.classList.remove("filtered-out");
      });
    }
  }

  // Handle delivery table filtering
  if (deliveryTable) {
    const deliveryRows = deliveryTable.querySelectorAll("tbody tr");
    const selectedDeliveryRows = Array.from(deliveryRows).filter((row) => {
      const checkbox = row.querySelector(".delivery-checkbox");
      return checkbox && checkbox.checked;
    });

    if (selectedDeliveryRows.length > 0) {
      const deliveryDaysList = selectedDeliveryRows
        .map((row) => row.dataset.days || "")
        .filter((days) => days);
      const commonDeliveryDays =
        deliveryDaysList.length > 0 ? getCommonDays(deliveryDaysList) : [];

      deliveryRows.forEach((row) => {
        const checkbox = row.querySelector(".delivery-checkbox");
        const rowDays = row.dataset.days || "";

        // Always show selected rows
        if (checkbox && checkbox.checked) {
          row.style.display = "";
          row.classList.remove("filtered-out");
          return;
        }

        // For unselected rows, check if they have any common days
        if (
          commonDeliveryDays.length === 0 ||
          hasCommonDays(rowDays, commonDeliveryDays.join(", "))
        ) {
          row.style.display = "";
          row.classList.remove("filtered-out");
        } else {
          row.style.display = "none";
          row.classList.add("filtered-out");
        }
      });
    } else {
      // If no delivery items selected, show all delivery items
      deliveryRows.forEach((row) => {
        row.style.display = "";
        row.classList.remove("filtered-out");
      });
    }
  }

  // Update select all state
  updateSelectAllState();
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("confirmationModal");
  if (event.target == modal) {
    closeConfirmationModal();
  }
};
