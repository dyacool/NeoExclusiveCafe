// Dashboard JavaScript functionality - v2.0 FRESH
console.log("[Dashboard.js v2.0] File loading started - FRESH VERSION");

let currentDate = new Date();
let topProductsChart = null;
let salesPerProductChart = null;

// Business hours update for dashboard - defined early for inline onclick
function updateBusinessHours() {
  console.log("[updateBusinessHours v2.0] Function called - FRESH");
  const openingTime = document.getElementById("openingTime")?.value;
  const closingTime = document.getElementById("closingTime")?.value;
  const saveBtn = document.getElementById("saveHoursBtn");
  const buttonText = saveBtn.querySelector(".button-text");
  const loadingSpinner = saveBtn.querySelector(".loading-spinner");

  if (!openingTime || !closingTime) {
    alert("Please enter both opening and closing times");
    return;
  }

  // Allow 00:00 - 00:00 as a special case (closed system). Otherwise require closing after opening
  if (
    !(openingTime === "00:00" && closingTime === "00:00") &&
    openingTime >= closingTime
  ) {
    alert("Closing time must be after opening time");
    return;
  }

  // Show loading state
  saveBtn.disabled = true;
  buttonText.style.display = "none";
  loadingSpinner.style.display = "inline-block";

  fetch("../calendar/update-business-hours.php", {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ openingTime, closingTime }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data && data.success) {
        alert("Business hours updated successfully!");
      } else {
        alert(
          "Error updating business hours: " +
            ((data && data.error) || "Unknown error")
        );
      }
    })
    .catch((error) => {
      console.error("Error updating business hours:", error);
      alert("Error updating business hours. Please try again.");
    })
    .finally(() => {
      // Reset button state
      saveBtn.disabled = false;
      buttonText.style.display = "inline-block";
      loadingSpinner.style.display = "none";
    });
}

// Expose for inline onclick
window.updateBusinessHours = updateBusinessHours;
console.log(
  "[Dashboard.js v2.0] updateBusinessHours exposed on window, type:",
  typeof window.updateBusinessHours
);
console.log(
  "[Dashboard.js v2.0] Testing global access:",
  typeof updateBusinessHours
);

// Initialize dashboard
function initializeDashboard() {
  console.log("Dashboard initialized");

  // Add smooth scroll behavior
  document.documentElement.style.scrollBehavior = "smooth";

  // Initialize animations
  animateCounters();
}

// Animate counter numbers
function animateCounters() {
  const counters = document.querySelectorAll(".card-value");

  counters.forEach((counter) => {
    // Check if this is a currency value (has decimals)
    const isCurrency = counter.textContent.includes("₱");
    const hasDecimals = counter.textContent.includes(".");

    // Extract the numeric value, preserving decimals if present
    const cleanText = counter.textContent.replace(/[₱,\s]/g, "");
    const target = parseFloat(cleanText) || 0;

    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;

    const timer = setInterval(() => {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }

      const prefix = isCurrency ? "₱" : "";
      const suffix = counter.textContent.includes("%") ? "%" : "";

      // Format with decimals for currency, without for counts
      const formattedValue =
        isCurrency || hasDecimals
          ? current.toLocaleString("en-US", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })
          : Math.floor(current).toLocaleString();

      counter.textContent = prefix + formattedValue + suffix;
    }, 16);
  });
}

// Calendar functionality
function generateCalendar(date = currentDate) {
  const year = date.getFullYear();
  const month = date.getMonth();

  // Month names
  const monthNames = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ];

  // Update month/year display
  const monthYearElement = document.getElementById("calendar-month-year");
  if (monthYearElement) {
    monthYearElement.textContent = `${monthNames[month]} ${year}`;
  }

  // Get calendar container
  const calendarDays = document.getElementById("calendar-days");
  if (!calendarDays) return;

  // Clear existing days
  calendarDays.innerHTML = "";

  // Get first day of month and number of days
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();
  const today = new Date();

  // Add days from previous month
  for (let i = firstDay - 1; i >= 0; i--) {
    const dayElement = document.createElement("div");
    dayElement.classList.add("calendar-day", "other-month");
    dayElement.textContent = daysInPrevMonth - i;
    calendarDays.appendChild(dayElement);
  }

  // Add days of current month
  for (let day = 1; day <= daysInMonth; day++) {
    const dayElement = document.createElement("div");
    dayElement.classList.add("calendar-day");
    dayElement.textContent = day;

    // Highlight today
    if (
      year === today.getFullYear() &&
      month === today.getMonth() &&
      day === today.getDate()
    ) {
      dayElement.classList.add("today");
    }

    // Add click event
    dayElement.addEventListener("click", () => {
      // Remove previous selection
      const previousSelected = calendarDays.querySelector(".selected");
      if (previousSelected) {
        previousSelected.classList.remove("selected");
      }

      // Add selection to clicked day
      dayElement.classList.add("selected");
    });

    calendarDays.appendChild(dayElement);
  }

  // Add days from next month to fill the grid
  const totalCells = calendarDays.children.length;
  const remainingCells = 35 - totalCells; // 6 rows * 7 days = 42

  for (let day = 1; day <= remainingCells && totalCells < 35; day++) {
    const dayElement = document.createElement("div");
    dayElement.classList.add("calendar-day", "other-month");
    dayElement.textContent = day;
    calendarDays.appendChild(dayElement);
  }
}

// Calendar navigation
function previousMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  generateCalendar(currentDate);
}

function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  generateCalendar(currentDate);
}

// Create charts
function createCharts() {
  createTopProductsChart();
  createSalesPerProductChart();
}

// Top 10 Products Chart
function createTopProductsChart() {
  const ctx = document.getElementById("top-products-chart");
  if (!ctx) return;

  // Destroy existing chart if it exists
  if (topProductsChart) {
    topProductsChart.destroy();
  }

  // Prepare data
  const labels = topProductsData.map((item) => {
    return item.name.length > 12
      ? item.name.substring(0, 12) + "..."
      : item.name;
  });
  const data = topProductsData.map((item) => parseInt(item.total_sold));

  topProductsChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Units Sold",
          data: data,
          backgroundColor: "#86efac",
          borderRadius: 4,
          maxBarThickness: 40,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: "#6b7280",
            font: {
              size: 11,
            },
          },
        },
        y: {
          grid: {
            color: "#f3f4f6",
          },
          ticks: {
            color: "#6b7280",
            font: {
              size: 11,
            },
            stepSize: 50,
          },
          beginAtZero: true,
        },
      },
      interaction: {
        intersect: false,
        mode: "index",
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });
}

// Sales Per Product Chart
function createSalesPerProductChart() {
  const ctx = document.getElementById("sales-per-product-chart");
  if (!ctx) return;

  // Destroy existing chart if it exists
  if (salesPerProductChart) {
    salesPerProductChart.destroy();
  }

  // Prepare data - show revenue for top 10 products
  const labels = topProductsData.map((item) => {
    return item.name.length > 12
      ? item.name.substring(0, 12) + "..."
      : item.name;
  });
  const revenueData = topProductsData.map((item) =>
    parseFloat(item.total_revenue)
  );
  const unitsData = topProductsData.map((item) => parseInt(item.total_sold));

  salesPerProductChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Revenue (₱)",
          data: revenueData,
          backgroundColor: "#86efac",
          borderRadius: 4,
          maxBarThickness: 30,
          yAxisID: "y",
        },
        {
          label: "Units Sold",
          data: unitsData,
          backgroundColor: "#c5fad8ff",
          borderRadius: 4,
          maxBarThickness: 30,
          yAxisID: "y1",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "top",
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              size: 12,
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: "#6b7280",
            font: {
              size: 11,
            },
          },
        },
        y: {
          type: "linear",
          display: true,
          position: "left",
          grid: {
            color: "#f3f4f6",
          },
          ticks: {
            color: "#6b7280",
            font: {
              size: 11,
            },
            callback: function (value) {
              return "₱" + value.toLocaleString();
            },
          },
          beginAtZero: true,
        },
        y1: {
          type: "linear",
          display: true,
          position: "right",
          grid: {
            drawOnChartArea: false,
          },
          ticks: {
            color: "#6b7280",
            font: {
              size: 11,
            },
          },
          beginAtZero: true,
        },
      },
      interaction: {
        intersect: false,
        mode: "index",
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });
}

// Refresh data function
function refreshDashboard() {
  // Show loading indicator
  const cards = document.querySelectorAll(
    ".service-card, .chart-card, .table-card"
  );
  cards.forEach((card) => {
    card.style.opacity = "0.7";
  });

  // Simulate data refresh (replace with actual API call)
  setTimeout(() => {
    // Restore opacity
    cards.forEach((card) => {
      card.style.opacity = "1";
    });

    // Recreate charts with new data
    createCharts();

    console.log("Dashboard refreshed");
  }, 1000);
}

// Utility function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
}

// Utility function to format numbers
function formatNumber(number) {
  return new Intl.NumberFormat("en-US").format(number);
}

// Auto-refresh functionality
function startAutoRefresh(interval = 300000) {
  // 5 minutes default
  setInterval(() => {
    refreshDashboard();
  }, interval);
}

// Export functions for global access
window.previousMonth = previousMonth;
window.nextMonth = nextMonth;
window.refreshDashboard = refreshDashboard;

// Start auto-refresh when dashboard loads
document.addEventListener("DOMContentLoaded", function () {
  // Start auto-refresh for dashboard data
  startAutoRefresh();
});

// Handle window resize for responsive charts
window.addEventListener("resize", function () {
  if (topProductsChart) {
    topProductsChart.resize();
  }
  if (salesPerProductChart) {
    salesPerProductChart.resize();
  }
});

// Add some interactivity to service cards
document.addEventListener("DOMContentLoaded", function () {
  const serviceCards = document.querySelectorAll(".service-card");

  serviceCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-4px)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(-2px)";
    });
  });
});

// Dark mode toggle (optional feature)
function toggleDarkMode() {
  document.body.classList.toggle("dark-mode");
  localStorage.setItem(
    "darkMode",
    document.body.classList.contains("dark-mode")
  );
}

// Load dark mode preference and initialize dashboard
document.addEventListener("DOMContentLoaded", function () {
  if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark-mode");
  }

  // Initialize dashboard functionality
  initializeDashboard();

  // Load current business hours into inputs on admin dashboard
  try {
    fetch("../calendar/get-business-hours.php", { credentials: "include" })
      .then((r) => r.json())
      .then((data) => {
        if (data && data.success && data.businessHours) {
          const opening = (data.businessHours.opening_time || "").slice(0, 5);
          const closing = (data.businessHours.closing_time || "").slice(0, 5);
          const openingInput = document.getElementById("openingTime");
          const closingInput = document.getElementById("closingTime");
          if (openingInput)
            openingInput.value = opening || openingInput.value || "08:00";
          if (closingInput)
            closingInput.value = closing || closingInput.value || "17:00";
        }
      })
      .catch(() => {});
  } catch (_) {}
});
