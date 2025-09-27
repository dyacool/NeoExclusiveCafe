// Terms and Conditions Management JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializeEditor();
  setupFormHandling();
});

let quill;
let isContentChanged = false;

// Initialize Quill.js Editor
function initializeEditor() {
  // Initialize Quill editor
  quill = new Quill("#editor-container", {
    theme: "snow",
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        ["bold", "italic", "underline", "strike"],
        [{ color: [] }, { background: [] }],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ indent: "-1" }, { indent: "+1" }],
        [{ align: [] }],
        ["link", "blockquote", "code-block"],
        ["clean"],
      ],
    },
    placeholder: "Enter your terms and conditions content here...",
    readOnly: false,
  });

  // Set initial content from textarea
  const textarea = document.getElementById("content");
  if (textarea.value.trim()) {
    quill.root.innerHTML = textarea.value;
  }

  // Listen for content changes
  quill.on("text-change", function () {
    isContentChanged = true;
    updateSaveStatus();
    // Update hidden textarea
    textarea.value = quill.root.innerHTML;
  });
}

// Setup form handling
function setupFormHandling() {
  const form = document.getElementById("termsForm");
  const saveBtn = form.querySelector(".btn-save");
  const titleInput = document.getElementById("title");

  // Track title changes
  titleInput.addEventListener("input", function () {
    isContentChanged = true;
    updateSaveStatus();
  });

  // Handle form submission
  form.addEventListener("submit", function (e) {
    const title = document.getElementById("title").value.trim();

    if (!title) {
      e.preventDefault();
      showNotification("Please enter a title", "error");
      document.getElementById("title").focus();
      return;
    }

    if (!quill) {
      e.preventDefault();
      showNotification("Editor not initialized", "error");
      return;
    }

    const content = quill.root.innerHTML;
    if (!content.trim() || content === "<p><br></p>") {
      e.preventDefault();
      showNotification("Please enter content", "error");
      quill.focus();
      return;
    }

    // Update hidden textarea before submission
    document.getElementById("content").value = content;

    // Allow form to submit normally
  });

  // Prevent accidental navigation away with unsaved changes
  window.addEventListener("beforeunload", function (e) {
    if (isContentChanged) {
      e.preventDefault();
      e.returnValue =
        "You have unsaved changes. Are you sure you want to leave?";
      return e.returnValue;
    }
  });
}

// Update save status indicator
function updateSaveStatus() {
  const saveBtn = document.querySelector(".btn-save");
  if (isContentChanged) {
    saveBtn.textContent = "Save Changes";
    saveBtn.style.background = "#dc3545";
  } else {
    saveBtn.textContent = "Update Terms";
    saveBtn.style.background = "#2d5a27";
  }
}

// Update last updated timestamp
function updateLastUpdated() {
  const lastUpdatedEl = document.querySelector(".last-updated");
  if (lastUpdatedEl) {
    const now = new Date();
    lastUpdatedEl.textContent = `Last updated: ${now.toLocaleDateString(
      "en-US",
      {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      }
    )}`;
  }
}

// Set button loading state
function setButtonLoading(button, loading) {
  if (loading) {
    button.disabled = true;
    button.classList.add("loading");
    button.dataset.originalText = button.textContent;
    button.textContent = "Saving...";
  } else {
    button.disabled = false;
    button.classList.remove("loading");
    button.textContent = button.dataset.originalText || "Save";
  }
}

// Show notification
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

  // Add CSS for animation
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
