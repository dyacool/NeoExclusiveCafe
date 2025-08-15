// Terms and Conditions Frontend JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializeInteractions();
  setupScrollToTop();
  setupSmoothScrolling();
});

// Initialize interactive features
function initializeInteractions() {
  // Add reading progress indicator
  addReadingProgress();

  // Add smooth scroll to sections
  addSectionNavigation();

  // Initialize copy to clipboard functionality
  setupCopyFunctionality();

  // Add print functionality
  setupPrintFunctionality();

  // Add keyboard navigation
  setupKeyboardNavigation();
}

// Add reading progress indicator
function addReadingProgress() {
  const progressBar = document.createElement("div");
  progressBar.className = "reading-progress";
  progressBar.innerHTML = '<div class="reading-progress-bar"></div>';

  // Add CSS for progress bar
  const style = document.createElement("style");
  style.textContent = `
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: rgba(255, 255, 255, 0.1);
            z-index: 9999;
        }
        .reading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2d5a27, #4a8f3a);
            width: 0%;
            transition: width 0.1s ease;
        }
    `;
  document.head.appendChild(style);
  document.body.appendChild(progressBar);

  // Update progress on scroll
  window.addEventListener("scroll", updateReadingProgress);
}

// Update reading progress
function updateReadingProgress() {
  const progressBar = document.querySelector(".reading-progress-bar");
  const content = document.querySelector(".content-wrapper");

  if (!progressBar || !content) return;

  const contentTop = content.offsetTop;
  const contentHeight = content.offsetHeight;
  const windowHeight = window.innerHeight;
  const scrollTop = window.pageYOffset;

  const scrollDistance = scrollTop - contentTop + windowHeight;
  const totalDistance = contentHeight;
  const percentage = Math.min(
    Math.max((scrollDistance / totalDistance) * 100, 0),
    100
  );

  progressBar.style.width = percentage + "%";
}

// Add section navigation
function addSectionNavigation() {
  const headings = document.querySelectorAll(
    ".content-wrapper h2, .content-wrapper h3"
  );

  if (headings.length <= 1) return;

  // Create table of contents
  const toc = document.createElement("div");
  toc.className = "table-of-contents";
  toc.innerHTML = '<h4>Table of Contents</h4><ul class="toc-list"></ul>';

  const tocList = toc.querySelector(".toc-list");

  headings.forEach((heading, index) => {
    // Add ID to heading
    const id = `section-${index + 1}`;
    heading.id = id;

    // Create TOC item
    const tocItem = document.createElement("li");
    tocItem.className =
      heading.tagName.toLowerCase() === "h2" ? "toc-main" : "toc-sub";
    tocItem.innerHTML = `<a href="#${id}">${heading.textContent}</a>`;
    tocList.appendChild(tocItem);
  });

  // Add CSS for TOC
  const style = document.createElement("style");
  style.textContent = `
        .table-of-contents {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            max-width: 400px;
        }
        .table-of-contents h4 {
            color: #2d5a27;
            margin: 0 0 15px 0;
            font-size: 1.1rem;
        }
        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc-main {
            margin-bottom: 8px;
        }
        .toc-sub {
            margin-left: 20px;
            margin-bottom: 5px;
        }
        .toc-list a {
            color: #2d5a27;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .toc-list a:hover {
            color: #1a4018;
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .table-of-contents {
                max-width: 100%;
            }
        }
    `;
  document.head.appendChild(style);

  // Insert TOC after the first paragraph
  const firstParagraph = document.querySelector(".content-wrapper p");
  if (firstParagraph) {
    firstParagraph.parentNode.insertBefore(toc, firstParagraph.nextSibling);
  }
}

// Setup copy to clipboard functionality
function setupCopyFunctionality() {
  // For the copy button in actions
  window.copyToClipboard = function () {
    const url = window.location.href;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(url)
        .then(() => {
          showNotification("Link copied to clipboard!", "success");
        })
        .catch(() => {
          fallbackCopy(url);
        });
    } else {
      fallbackCopy(url);
    }
  };

  // Add copy buttons to sections
  const headings = document.querySelectorAll(
    ".content-wrapper h2, .content-wrapper h3"
  );
  headings.forEach((heading) => {
    const copyBtn = document.createElement("button");
    copyBtn.className = "section-copy-btn";
    copyBtn.innerHTML = "🔗";
    copyBtn.title = "Copy link to this section";
    copyBtn.onclick = () => {
      const url =
        window.location.origin + window.location.pathname + "#" + heading.id;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
          showNotification("Section link copied!", "success");
        });
      }
    };

    heading.style.position = "relative";
    heading.appendChild(copyBtn);
  });

  // Add CSS for section copy buttons
  const style = document.createElement("style");
  style.textContent = `
        .section-copy-btn {
            position: absolute;
            right: -30px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 5px;
            border-radius: 3px;
        }
        .content-wrapper h2:hover .section-copy-btn,
        .content-wrapper h3:hover .section-copy-btn {
            opacity: 0.7;
        }
        .section-copy-btn:hover {
            opacity: 1 !important;
            background-color: #f0f0f0;
        }
        @media (max-width: 768px) {
            .section-copy-btn {
                position: static;
                transform: none;
                margin-left: 10px;
                opacity: 0.7;
            }
        }
    `;
  document.head.appendChild(style);
}

// Fallback copy function
function fallbackCopy(text) {
  const textArea = document.createElement("textarea");
  textArea.value = text;
  textArea.style.position = "fixed";
  textArea.style.left = "-999999px";
  textArea.style.top = "-999999px";
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  try {
    document.execCommand("copy");
    showNotification("Link copied to clipboard!", "success");
  } catch (err) {
    showNotification("Failed to copy link", "error");
  }

  document.body.removeChild(textArea);
}

// Setup print functionality
function setupPrintFunctionality() {
  window.addEventListener("beforeprint", () => {
    document.body.classList.add("printing");
  });

  window.addEventListener("afterprint", () => {
    document.body.classList.remove("printing");
  });
}

// Setup smooth scrolling
function setupSmoothScrolling() {
  document.addEventListener("click", (e) => {
    if (e.target.matches('a[href^="#"]')) {
      e.preventDefault();
      const targetId = e.target.getAttribute("href");
      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        targetElement.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });

        // Update URL without jumping
        history.pushState(null, null, targetId);
      }
    }
  });
}

// Setup keyboard navigation
function setupKeyboardNavigation() {
  document.addEventListener("keydown", (e) => {
    // Ctrl/Cmd + P for print
    if ((e.ctrlKey || e.metaKey) && e.key === "p") {
      e.preventDefault();
      window.print();
    }

    // Escape to close notifications
    if (e.key === "Escape") {
      const notifications = document.querySelectorAll(".notification");
      notifications.forEach((notification) => notification.remove());
    }
  });
}

// Setup scroll to top functionality
function setupScrollToTop() {
  const scrollToTopBtn = document.createElement("button");
  scrollToTopBtn.className = "scroll-to-top";
  scrollToTopBtn.innerHTML = "↑";
  scrollToTopBtn.title = "Scroll to top";
  scrollToTopBtn.onclick = () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  };

  // Add CSS for scroll to top button
  const style = document.createElement("style");
  style.textContent = `
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: #2d5a27;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.3);
        }
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        .scroll-to-top:hover {
            background-color: #1a4018;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(45, 90, 39, 0.4);
        }
        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    `;
  document.head.appendChild(style);
  document.body.appendChild(scrollToTopBtn);

  // Show/hide scroll to top button
  window.addEventListener("scroll", () => {
    if (window.pageYOffset > 300) {
      scrollToTopBtn.classList.add("visible");
    } else {
      scrollToTopBtn.classList.remove("visible");
    }
  });
}

// Show notification function
function showNotification(message, type = "success") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notification) => notification.remove());

  // Create notification
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

  // Style notification
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

  // Add animation CSS if not exists
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

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 5000);
}

// Handle URL hash on page load
window.addEventListener("load", () => {
  if (window.location.hash) {
    setTimeout(() => {
      const target = document.querySelector(window.location.hash);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    }, 100);
  }
});

// Analytics and user engagement tracking (optional)
function trackUserEngagement() {
  let startTime = Date.now();
  let maxScroll = 0;

  window.addEventListener("scroll", () => {
    const scrollPercent =
      (window.pageYOffset / (document.body.scrollHeight - window.innerHeight)) *
      100;
    maxScroll = Math.max(maxScroll, scrollPercent);
  });

  window.addEventListener("beforeunload", () => {
    const timeSpent = Date.now() - startTime;
    const engagement = {
      timeSpent: timeSpent,
      maxScroll: maxScroll,
      timestamp: new Date().toISOString(),
    };

    // Store engagement data (you can send this to your analytics service)
    localStorage.setItem("termsEngagement", JSON.stringify(engagement));
  });
}

// Initialize engagement tracking
trackUserEngagement();
