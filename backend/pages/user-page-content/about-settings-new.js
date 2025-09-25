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
  if (aboutTextarea.value) {
    quill.root.innerHTML = aboutTextarea.value;
  }

  // Sync content with textarea on form submission
  const form = document.getElementById("aboutForm");
  form.addEventListener("submit", function () {
    aboutTextarea.value = quill.root.innerHTML;
  });

  // Update textarea content when Quill content changes
  quill.on("text-change", function () {
    aboutTextarea.value = quill.root.innerHTML;
  });

  // Image preview functionality
  const imageInput = document.getElementById("about_image");
  const imagePreview = document.getElementById("image-preview");

  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const fileName = document.getElementById("file-name"); // Get element each time to ensure it exists
      const file = e.target.files[0];

      console.log("File input changed:", file ? file.name : "No file");
      console.log("File name element found:", fileName ? "Yes" : "No");

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
          this.value = "";
          if (fileName) {
            fileName.textContent = "No file selected";
            fileName.classList.remove("has-file");
          }
          return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
          showAlert("File size must be less than 5MB", "error");
          this.value = "";
          if (fileName) {
            fileName.textContent = "No file selected";
            fileName.classList.remove("has-file");
          }
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
        reader.readAsDataURL(file);
      } else {
        // No file selected
        if (fileName) {
          fileName.textContent = "No file selected";
          fileName.classList.remove("has-file");
          console.log("Reset file name to: No file selected");
        }
      }
    });
  }

  // Store quill instance globally for other functions
  window.quill = quill;
});

// Preview about page function
function previewAbout() {
  // Open about page in new window/tab for preview
  const baseUrl = window.location.origin;
  const previewUrl = baseUrl + "/frontend/pages/about.php";
  window.open(previewUrl, "_blank");
}

// Show alert function
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

  alertDiv.innerHTML = `
        ${icon}
        <span>${message}</span>
    `;

  // Insert alert at the top of the admin container
  const adminContainer = document.querySelector(".admin-container");
  const pageHeader = document.querySelector(".page-header");
  adminContainer.insertBefore(alertDiv, pageHeader.nextSibling);

  // Auto-remove alert after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Form validation
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("aboutForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      const title = document.getElementById("title").value.trim();
      const quill = window.quill || new Quill("#editor-container");
      const content = quill.getText().trim();

      if (!title) {
        e.preventDefault();
        showAlert("Please enter a title for the about page", "error");
        document.getElementById("title").focus();
        return;
      }

      if (!content) {
        e.preventDefault();
        showAlert("Please enter content for the about page", "error");
        quill.focus();
        return;
      }

      // Sync content before submission
      document.getElementById("about_text").value = quill.root.innerHTML;
    });
  }
});

// Backup function for file name updating (called inline from HTML)
function updateFileName(input) {
  const fileName = document.getElementById("file-name");
  const file = input.files[0];

  console.log("updateFileName called with file:", file ? file.name : "No file");

  if (fileName) {
    if (file) {
      fileName.textContent = file.name;
      fileName.classList.add("has-file");
      console.log("File name updated to:", file.name);
    } else {
      fileName.textContent = "No file selected";
      fileName.classList.remove("has-file");
      console.log("File name reset to: No file selected");
    }
  } else {
    console.log("ERROR: Could not find file-name element");
  }
}
