// Terms and Conditions Management JavaScript
document.addEventListener("DOMContentLoaded", function () {
  initializeEditor();
  setupFormHandling();
  setupAutoSave();
});

let quill;
let autoSaveInterval;
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
    e.preventDefault();
    saveTerms();
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

// Setup auto-save functionality
function setupAutoSave() {
  // Auto-save every 2 minutes
  autoSaveInterval = setInterval(function () {
    if (isContentChanged) {
      saveDraft(true); // Silent auto-save
    }
  }, 120000); // 2 minutes
}

// Save terms and conditions
function saveTerms() {
  const form = document.getElementById("termsForm");
  const saveBtn = form.querySelector(".btn-save");
  const title = document.getElementById("title").value.trim();

  if (!title) {
    showNotification("Please enter a title", "error");
    document.getElementById("title").focus();
    return;
  }

  if (!quill) {
    showNotification("Editor not initialized", "error");
    return;
  }

  const content = quill.root.innerHTML;
  if (!content.trim() || content === "<p><br></p>") {
    showNotification("Please enter content", "error");
    quill.focus();
    return;
  }

  // Show loading state
  setButtonLoading(saveBtn, true);

  // Update hidden textarea before submission
  document.getElementById("content").value = content;

  // Create form data
  const formData = new FormData();
  formData.append("title", title);
  formData.append("content", content);

  // Submit form
  fetch(window.location.href, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.text())
    .then((html) => {
      // Parse response to check for success/error messages
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");
      const successAlert = doc.querySelector(".alert.success");
      const errorAlert = doc.querySelector(".alert.error");

      if (successAlert) {
        showNotification(
          "Terms and conditions updated successfully!",
          "success"
        );
        isContentChanged = false;
        updateSaveStatus();
        updateLastUpdated();
      } else if (errorAlert) {
        showNotification(errorAlert.textContent.trim(), "error");
      } else {
        showNotification(
          "Terms and conditions updated successfully!",
          "success"
        );
        isContentChanged = false;
        updateSaveStatus();
        updateLastUpdated();
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("An error occurred while saving", "error");
    })
    .finally(() => {
      setButtonLoading(saveBtn, false);
    });
}

// Save draft (without refreshing page)
function saveDraft(silent = false) {
  const title = document.getElementById("title").value.trim();

  if (!title || !quill) {
    if (!silent) {
      showNotification("Please enter a title and content", "error");
    }
    return;
  }

  const content = quill.root.innerHTML;
  if (!content.trim() || content === "<p><br></p>") {
    if (!silent) {
      showNotification("Please enter content", "error");
    }
    return;
  }

  const draftBtn = document.querySelector(".btn-draft");
  if (draftBtn && !silent) {
    setButtonLoading(draftBtn, true);
  }

  // Save to localStorage as backup
  const draftData = {
    title: title,
    content: content,
    timestamp: new Date().toISOString(),
  };
  localStorage.setItem("termsConditionsDraft", JSON.stringify(draftData));

  if (!silent) {
    setTimeout(() => {
      showNotification("Draft saved locally", "success");
      if (draftBtn) {
        setButtonLoading(draftBtn, false);
      }
    }, 500);
  }
}

// Load draft from localStorage
function loadDraft() {
  const draftData = localStorage.getItem("termsConditionsDraft");
  if (draftData) {
    try {
      const draft = JSON.parse(draftData);
      if (confirm("A draft was found. Would you like to load it?")) {
        document.getElementById("title").value = draft.title;
        if (quill) {
          quill.root.innerHTML = draft.content;
          document.getElementById("content").value = draft.content;
        }
        showNotification("Draft loaded", "success");
        isContentChanged = true;
        updateSaveStatus();
      }
    } catch (e) {
      console.error("Error loading draft:", e);
    }
  }
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

// Clear localStorage on successful save
window.addEventListener("beforeunload", function () {
  if (!isContentChanged) {
    localStorage.removeItem("termsConditionsDraft");
  }
});

// Check for draft on page load
setTimeout(() => {
  loadDraft();
}, 1000);

// Global function for save draft button
window.saveDraft = saveDraft;
