// Privacy Policy JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializePage();
});

function initializePage() {
  // Add smooth scrolling for anchor links
  const anchorLinks = document.querySelectorAll('a[href^="#"]');
  anchorLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Add copy functionality for email links
  const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
  emailLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const email = this.getAttribute("href").replace("mailto:", "");
      copyToClipboard(email);
    });
  });

  // Initialize print functionality
  setupPrintStyles();
}

function copyToClipboard(text = null) {
  const textToCopy = text || window.location.href;

  if (navigator.clipboard && window.isSecureContext) {
    // Use modern clipboard API
    navigator.clipboard
      .writeText(textToCopy)
      .then(() => {
        showNotification("Copied to clipboard!", "success");
      })
      .catch((err) => {
        console.error("Failed to copy: ", err);
        fallbackCopyTextToClipboard(textToCopy);
      });
  } else {
    // Fallback for older browsers
    fallbackCopyTextToClipboard(textToCopy);
  }
}

function fallbackCopyTextToClipboard(text) {
  const textArea = document.createElement("textarea");
  textArea.value = text;

  // Avoid scrolling to bottom
  textArea.style.top = "0";
  textArea.style.left = "0";
  textArea.style.position = "fixed";

  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  try {
    const successful = document.execCommand("copy");
    const msg = successful ? "Copied to clipboard!" : "Failed to copy";
    showNotification(msg, successful ? "success" : "error");
  } catch (err) {
    console.error("Fallback: Oops, unable to copy", err);
    showNotification("Failed to copy to clipboard", "error");
  }

  document.body.removeChild(textArea);
}

function showNotification(message, type = "success") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notification) => notification.remove());

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            margin-left: 10px;
        ">&times;</button>
    `;

  // Style the notification
  Object.assign(notification.style, {
    position: "fixed",
    top: "20px",
    right: "20px",
    padding: "15px 20px",
    borderRadius: "5px",
    color: "white",
    background: type === "success" ? "#28a745" : "#dc3545",
    boxShadow: "0 4px 6px rgba(0, 0, 0, 0.1)",
    zIndex: "10000",
    display: "flex",
    alignItems: "center",
    animation: "slideInRight 0.3s ease-out",
  });

  // Add CSS for animation if not already present
  if (!document.querySelector("#notification-styles")) {
    const style = document.createElement("style");
    style.id = "notification-styles";
    style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
    document.head.appendChild(style);
  }

  document.body.appendChild(notification);

  // Auto remove after 3 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 3000);
}

function setupPrintStyles() {
  // Add print-specific event listeners
  window.addEventListener("beforeprint", function () {
    // Hide elements that shouldn't be printed
    const elementsToHide = document.querySelectorAll(
      ".privacy-actions, .breadcrumb, .contact-info"
    );
    elementsToHide.forEach((element) => {
      element.style.display = "none";
    });
  });

  window.addEventListener("afterprint", function () {
    // Restore hidden elements after printing
    const elementsToShow = document.querySelectorAll(
      ".privacy-actions, .breadcrumb, .contact-info"
    );
    elementsToShow.forEach((element) => {
      element.style.display = "";
    });
  });
}

// Add scroll to top functionality for long content
function addScrollToTop() {
  const scrollButton = document.createElement("button");
  scrollButton.innerHTML = "↑";
  scrollButton.className = "scroll-to-top";
  scrollButton.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #2d5a27;
        color: white;
        border: none;
        font-size: 20px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    `;

  scrollButton.addEventListener("click", function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });

  document.body.appendChild(scrollButton);

  // Show/hide scroll button based on scroll position
  window.addEventListener("scroll", function () {
    if (window.pageYOffset > 300) {
      scrollButton.style.opacity = "1";
    } else {
      scrollButton.style.opacity = "0";
    }
  });
}

// Initialize scroll to top when page loads
document.addEventListener("DOMContentLoaded", function () {
  addScrollToTop();
});

// Add keyboard shortcuts
document.addEventListener("keydown", function (e) {
  // Ctrl/Cmd + P for print
  if ((e.ctrlKey || e.metaKey) && e.key === "p") {
    e.preventDefault();
    window.print();
  }

  // Ctrl/Cmd + C to copy page URL (when not in input field)
  if (
    (e.ctrlKey || e.metaKey) &&
    e.key === "c" &&
    !["INPUT", "TEXTAREA"].includes(e.target.tagName)
  ) {
    copyToClipboard();
  }
});
