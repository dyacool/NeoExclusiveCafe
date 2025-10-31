// Privacy Policy Management JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializeEditor();
  setupFormHandling();
});

let quill;
let isContentChanged = false;
let initialContent = "";
let initialTitle = "";
let isInitializing = true;

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
    placeholder: "Enter your privacy policy content here...",
    readOnly: false,
  });

  // Set initial content from textarea
  const textarea = document.getElementById("content");
  if (textarea && textarea.value.trim()) {
    quill.root.innerHTML = textarea.value;
  }

  // Store initial content for comparison
  setTimeout(() => {
    initialContent = quill.root.innerHTML;
    initialTitle = document.getElementById("title")
      ? document.getElementById("title").value
      : "";
    isInitializing = false;
  }, 100);

  // Listen for content changes
  quill.on("text-change", function () {
    if (!isInitializing) {
      checkForChanges();
      // Update hidden textarea
      if (textarea) {
        textarea.value = quill.root.innerHTML;
      }
    }
  });
}

// Check if content has actually changed
function checkForChanges() {
  const currentContent = quill ? quill.root.innerHTML : "";
  const currentTitle = document.getElementById("title")
    ? document.getElementById("title").value
    : "";

  isContentChanged =
    currentContent !== initialContent || currentTitle !== initialTitle;
  updateSaveStatus();
}

// Update save status indicator
function updateSaveStatus() {
  const saveBtn = document.querySelector(".btn-primary");
  if (!saveBtn) return;

  const iconHtml =
    '<svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';

  if (isContentChanged) {
    saveBtn.innerHTML = iconHtml + "Save Changes";
    saveBtn.style.backgroundColor = "var(--orange-600)";
  } else {
    saveBtn.innerHTML = iconHtml + "Update Privacy Policy";
    saveBtn.style.backgroundColor = "var(--green-600)";
  }
}

// Setup form handling
function setupFormHandling() {
  const form = document.getElementById("privacyForm");
  const titleInput = document.getElementById("title");

  // Track title changes
  if (titleInput) {
    titleInput.addEventListener("input", function () {
      if (!isInitializing) {
        checkForChanges();
      }
    });
  }

  // Handle form submission
  if (form) {
    form.addEventListener("submit", function (e) {
      const title = document.getElementById("title");
      const titleValue = title ? title.value.trim() : "";

      if (!titleValue) {
        e.preventDefault();
        showNotification("Please enter a title", "error");
        if (title) title.focus();
        return;
      }

      if (!quill) {
        e.preventDefault();
        showNotification("Editor not initialized", "error");
        return;
      }

      const content = quill.root.innerHTML;
      const contentText = quill.getText().trim();

      if (!contentText || contentText.length === 0) {
        e.preventDefault();
        showNotification("Please enter content", "error");
        quill.focus();
        return;
      }

      // Update hidden textarea before submission
      const textarea = document.getElementById("content");
      if (textarea) {
        textarea.value = content;
      }

      // Reset change tracking after successful submission
      isContentChanged = false;

      // Allow form to submit normally
    });
  }

  // Prevent accidental navigation away with unsaved changes
  window.addEventListener("beforeunload", function (e) {
    if (isContentChanged && !isInitializing) {
      e.preventDefault();
      e.returnValue =
        "You have unsaved changes. Are you sure you want to leave?";
      return e.returnValue;
    }
  });
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
