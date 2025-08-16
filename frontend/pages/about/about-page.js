// About Page JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializePage();
  setupImageLazyLoading();
  addSmoothScrolling();
});

function initializePage() {
  // Add enhanced image interactions
  const aboutImage = document.querySelector(".about-image img");
  if (aboutImage) {
    setupImageInteractions(aboutImage);
  }

  // Add print functionality
  setupPrintStyles();

  // Add copy functionality for any email links
  const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
  emailLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const email = this.getAttribute("href").replace("mailto:", "");
      copyToClipboard(email);
    });
  });

  // Add keyboard shortcuts
  setupKeyboardShortcuts();
}

function setupImageInteractions(image) {
  // Add click to zoom functionality
  image.addEventListener("click", function () {
    toggleImageZoom(this);
  });

  // Add error handling for broken images
  image.addEventListener("error", function () {
    this.style.display = "none";
    const placeholder = document.createElement("div");
    placeholder.className = "image-placeholder";
    placeholder.innerHTML = `
            <div class="placeholder-content">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="#ccc">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <p>Image not available</p>
            </div>
        `;
    this.parentNode.appendChild(placeholder);
  });

  // Add loading animation
  image.addEventListener("load", function () {
    this.classList.add("loaded");
  });
}

function toggleImageZoom(image) {
  const modal = document.createElement("div");
  modal.className = "image-modal";
  modal.innerHTML = `
        <div class="modal-backdrop" onclick="closeImageModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <img src="${image.src}" alt="${image.alt}">
                <button class="close-button" onclick="closeImageModal()">&times;</button>
            </div>
        </div>
    `;

  // Style the modal
  Object.assign(modal.style, {
    position: "fixed",
    top: "0",
    left: "0",
    width: "100%",
    height: "100%",
    zIndex: "10000",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
  });

  const backdrop = modal.querySelector(".modal-backdrop");
  Object.assign(backdrop.style, {
    position: "absolute",
    top: "0",
    left: "0",
    width: "100%",
    height: "100%",
    background: "rgba(0, 0, 0, 0.8)",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    cursor: "pointer",
  });

  const content = modal.querySelector(".modal-content");
  Object.assign(content.style, {
    position: "relative",
    maxWidth: "90%",
    maxHeight: "90%",
    cursor: "default",
  });

  const modalImage = modal.querySelector("img");
  Object.assign(modalImage.style, {
    maxWidth: "100%",
    maxHeight: "100%",
    borderRadius: "10px",
    boxShadow: "0 10px 30px rgba(0, 0, 0, 0.5)",
  });

  const closeButton = modal.querySelector(".close-button");
  Object.assign(closeButton.style, {
    position: "absolute",
    top: "-15px",
    right: "-15px",
    width: "40px",
    height: "40px",
    borderRadius: "50%",
    background: "#fff",
    border: "none",
    fontSize: "24px",
    cursor: "pointer",
    boxShadow: "0 2px 10px rgba(0, 0, 0, 0.3)",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
  });

  document.body.appendChild(modal);
  document.body.style.overflow = "hidden";

  // Add CSS for animation if not already present
  if (!document.querySelector("#modal-styles")) {
    const style = document.createElement("style");
    style.id = "modal-styles";
    style.textContent = `
            .image-modal {
                animation: fadeIn 0.3s ease-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .image-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 300px;
                background: #f8f9fa;
                border: 2px dashed #dee2e6;
                border-radius: 10px;
                color: #6c757d;
            }
            .placeholder-content {
                text-align: center;
            }
            .placeholder-content p {
                margin-top: 10px;
                font-style: italic;
            }
            .about-image img.loaded {
                animation: imageSlideIn 0.5s ease-out;
            }
            @keyframes imageSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
    document.head.appendChild(style);
  }
}

function closeImageModal() {
  const modal = document.querySelector(".image-modal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
}

function setupImageLazyLoading() {
  const images = document.querySelectorAll('img[loading="lazy"]');

  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.classList.add("lazy-loaded");
          observer.unobserve(img);
        }
      });
    });

    images.forEach((img) => imageObserver.observe(img));
  }
}

function addSmoothScrolling() {
  // Add smooth scrolling for any anchor links
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
}

function copyToClipboard(text = null) {
  const textToCopy = text || window.location.href;

  if (navigator.clipboard && window.isSecureContext) {
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
    fallbackCopyTextToClipboard(textToCopy);
  }
}

function fallbackCopyTextToClipboard(text) {
  const textArea = document.createElement("textarea");
  textArea.value = text;
  textArea.style.position = "fixed";
  textArea.style.top = "0";
  textArea.style.left = "0";

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
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notification) => notification.remove());

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

  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 3000);
}

function setupPrintStyles() {
  window.addEventListener("beforeprint", function () {
    const elementsToHide = document.querySelectorAll(
      ".breadcrumb, .about-actions"
    );
    elementsToHide.forEach((element) => {
      element.style.display = "none";
    });
  });

  window.addEventListener("afterprint", function () {
    const elementsToShow = document.querySelectorAll(
      ".breadcrumb, .about-actions"
    );
    elementsToShow.forEach((element) => {
      element.style.display = "";
    });
  });
}

function setupKeyboardShortcuts() {
  document.addEventListener("keydown", function (e) {
    // Ctrl/Cmd + P for print
    if ((e.ctrlKey || e.metaKey) && e.key === "p") {
      e.preventDefault();
      window.print();
    }

    // Escape to close modal
    if (e.key === "Escape") {
      closeImageModal();
    }
  });
}

// Add scroll to top functionality
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

// Global functions
window.closeImageModal = closeImageModal;
