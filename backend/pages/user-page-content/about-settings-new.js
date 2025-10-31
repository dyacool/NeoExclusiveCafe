document.addEventListener("DOMContentLoaded", function () {
  // Initialize Quill editor
  const quill = new Quill("#editor-container", {
    theme: "snow",
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ["bold", "italic", "underline", "strike"],
        [{ color: [] }, { background: [] }],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ align: [] }],
        ["link", "image"],
        ["clean"],
      ],
    },
    placeholder: "Enter about page content...",
  });

  // Load existing content
  const aboutTextarea = document.getElementById("about_text");
  if (aboutTextarea && aboutTextarea.value) {
    quill.root.innerHTML = aboutTextarea.value;
  }

  // Sync content with textarea on form submission
  const form = document.getElementById("aboutForm");
  if (form) {
    form.addEventListener("submit", function () {
      if (aboutTextarea) {
        aboutTextarea.value = quill.root.innerHTML;
      }
    });
  }

  // Update textarea content when Quill content changes
  quill.on("text-change", function () {
    if (aboutTextarea) {
      aboutTextarea.value = quill.root.innerHTML;
    }
  });

  // Enhanced image preview functionality
  const imageInput = document.getElementById("about_image");
  const imagePreview = document.getElementById("image-preview");
  const fileName = document.getElementById("file-name");

  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];

      console.log("File input changed:", file ? file.name : "No file");

      if (file) {
        // Update file name display
        if (fileName) {
          fileName.textContent = file.name;
          fileName.classList.add("has-file");
          console.log("Updated file name to:", file.name);
        }

        // Validate file type
        const allowedTypes = [
          "image/jpeg",
          "image/jpg",
          "image/png",
          "image/gif",
          "image/webp",
        ];
        if (!allowedTypes.includes(file.type)) {
          showAlert(
            "Please select a valid image file (JPG, PNG, GIF, WebP)",
            "error"
          );
          resetFileInput();
          return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
          showAlert("File size must be less than 5MB", "error");
          resetFileInput();
          return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function (e) {
          if (imagePreview) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = "block";

            // Hide no-image placeholder if it exists
            const placeholder = document.querySelector(".no-image-placeholder");
            if (placeholder) {
              placeholder.style.display = "none";
            }
          }
        };
        reader.onerror = function () {
          showAlert("Error reading file. Please try again.", "error");
          resetFileInput();
        };
        reader.readAsDataURL(file);
      } else {
        resetFileInput();
      }
    });
  }

  // Helper function to reset file input
  function resetFileInput() {
    if (imageInput) {
      imageInput.value = "";
    }
    if (fileName) {
      fileName.textContent = "No file selected";
      fileName.classList.remove("has-file");
    }
  }

  // Store quill instance globally for other functions
  window.quill = quill;

  // Form validation
  if (form) {
    form.addEventListener("submit", function (e) {
      const title = document.getElementById("title");
      const titleValue = title ? title.value.trim() : "";
      const content = quill.getText().trim();

      if (!titleValue) {
        e.preventDefault();
        showAlert("Please enter a title for the about page", "error");
        if (title) title.focus();
        return;
      }

      if (!content) {
        e.preventDefault();
        showAlert("Please enter content for the about page", "error");
        quill.focus();
        return;
      }

      // Sync content before submission
      if (aboutTextarea) {
        aboutTextarea.value = quill.root.innerHTML;
      }
    });
  }
});

// Preview about page function
function previewAbout() {
  // Open about page in new window/tab for preview
  const baseUrl = window.location.origin;
  const previewUrl = baseUrl + "/frontend/pages/about.php";
  window.open(previewUrl, "_blank");
}

// Enhanced show alert function
function showAlert(message, type) {
  // Remove existing alerts
  const existingAlerts = document.querySelectorAll(".alert");
  existingAlerts.forEach((alert) => alert.remove());

  // Create new alert
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;

  const icon =
    type === "success"
      ? `<svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>`
      : `<svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
      </svg>`;

  alertDiv.innerHTML = `${icon}<span>${message}</span>`;

  // Insert alert at the top of the admin container
  const adminContainer = document.querySelector(".admin-container");
  const pageHeader = document.querySelector(".page-header");
  if (adminContainer && pageHeader) {
    adminContainer.insertBefore(alertDiv, pageHeader.nextSibling);
  }

  // Auto-remove alert after 5 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove();
    }
  }, 5000);
}

// Enhanced file name updating function (called inline from HTML)
function updateFileName(input) {
  const fileName = document.getElementById("file-name");
  if (!fileName) {
    console.error("Could not find file-name element");
    return;
  }

  const file = input.files && input.files[0];
  console.log("updateFileName called with file:", file ? file.name : "No file");

  if (file) {
    fileName.textContent = file.name;
    fileName.classList.add("has-file");
    console.log("File name updated to:", file.name);
  } else {
    fileName.textContent = "No file selected";
    fileName.classList.remove("has-file");
    console.log("File name reset to: No file selected");
  }
}

// Keyboard navigation support for file upload button
document.addEventListener("DOMContentLoaded", function () {
  const fileUploadBtn = document.querySelector(".file-upload-btn");
  if (fileUploadBtn) {
    fileUploadBtn.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        this.click();
      }
    });
  }
});
