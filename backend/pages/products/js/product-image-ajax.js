/**
 * Product Image AJAX Management
 *
 * Handles real-time image uploads and deletions to Cloudinary via AJAX
 * Provides instant visual feedback and manages image metadata
 */

// Configuration
const MAX_ADDITIONAL_IMAGES = 3;
const UPLOAD_ENDPOINT = "/backend/api/upload-product-image.php";
const DELETE_ENDPOINT = "/backend/api/delete-product-image.php";

// State management
let uploadingCount = 0;
let additionalImagesCount = 0;

/**
 * Get CSRF token from hidden field or meta tag
 */
function getCsrfToken() {
  const tokenField = document.getElementById("csrf_token");
  if (tokenField) {
    return tokenField.value;
  }

  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  if (tokenMeta) {
    return tokenMeta.getAttribute("content");
  }

  console.error("CSRF token not found");
  return "";
}

/**
 * Upload image to Cloudinary via AJAX
 *
 * @param {File} file - Image file to upload
 * @param {string} imageType - 'primary' or 'additional'
 * @returns {Promise<Object>} Upload result
 */
async function uploadImageToCloudinary(file, imageType) {
  const formData = new FormData();
  formData.append("image", file);
  formData.append("image_type", imageType);
  formData.append("csrf_token", getCsrfToken());

  // Get product name from form for folder structure
  const productNameField = document.querySelector('input[name="name"]');
  const productName = productNameField
    ? productNameField.value
    : "Unnamed_Product";

  console.log("Product name field:", productNameField);
  console.log("Product name value:", productName);

  formData.append("product_name", productName);

  try {
    showLoadingIndicator(imageType);
    uploadingCount++;

    const response = await fetch(UPLOAD_ENDPOINT, {
      method: "POST",
      body: formData,
    });

    // Check if response is ok
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Get response text first to debug JSON issues
    const responseText = await response.text();
    console.log("Raw response:", responseText);

    let result;
    try {
      result = JSON.parse(responseText);
    } catch (jsonError) {
      console.error("JSON parse error:", jsonError);
      console.error("Response text:", responseText);
      throw new Error("Invalid JSON response from server");
    }

    if (result.success) {
      addImagePreview(result.url, result.public_id, imageType);
      storeImageMetadata(result.url, result.public_id, imageType);
      showSuccessIndicator(imageType);

      if (imageType === "additional") {
        additionalImagesCount++;
        updateAdditionalImagesButton();
      }

      return result;
    } else {
      showErrorMessage(result.error || "Upload failed", imageType);
      return null;
    }
  } catch (error) {
    console.error("Upload error:", error);
    showErrorMessage("Upload failed: " + error.message, imageType);
    return null;
  } finally {
    uploadingCount--;
    hideLoadingIndicator(imageType);
  }
}

/**
 * Delete image from Cloudinary via AJAX
 *
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 * @returns {Promise<boolean>} Success status
 */
async function deleteImageFromCloudinary(publicId, imageType) {
  const formData = new FormData();
  formData.append("public_id", publicId);
  formData.append("csrf_token", getCsrfToken());

  try {
    // Don't show loading indicator for deletion to avoid confusion
    // showLoadingIndicator(imageType);

    const response = await fetch(DELETE_ENDPOINT, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      removeImagePreview(publicId);
      removeImageMetadata(publicId, imageType);

      if (imageType === "additional") {
        additionalImagesCount--;
        updateAdditionalImagesButton();
      } else if (imageType === "primary") {
        // Show the upload button again when primary image is removed
        const uploadBtn = document.getElementById("primaryUploadBtn");
        if (uploadBtn) {
          uploadBtn.style.display = "flex";
        }
      }

      return true;
    } else {
      showErrorMessage(result.error || "Delete failed", imageType);
      return false;
    }
  } catch (error) {
    console.error("Delete error:", error);
    showErrorMessage("Delete failed: " + error.message, imageType);
    return false;
  } finally {
    // Don't hide loading indicator since we're not showing it
    // hideLoadingIndicator(imageType);
  }
}

/**
 * Store image metadata in hidden form fields
 *
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function storeImageMetadata(url, publicId, imageType) {
  if (imageType === "primary") {
    const urlField = document.getElementById("primary_image_url");
    const idField = document.getElementById("primary_image_public_id");

    if (urlField) urlField.value = url;
    if (idField) idField.value = publicId;
  } else {
    const urlsField = document.getElementById("additional_image_urls");
    const idsField = document.getElementById("additional_image_public_ids");

    if (urlsField && idsField) {
      const urls = urlsField.value ? JSON.parse(urlsField.value) : [];
      const ids = idsField.value ? JSON.parse(idsField.value) : [];

      urls.push(url);
      ids.push(publicId);

      urlsField.value = JSON.stringify(urls);
      idsField.value = JSON.stringify(ids);
    }
  }
}

/**
 * Remove image metadata from hidden form fields
 *
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function removeImageMetadata(publicId, imageType) {
  if (imageType === "primary") {
    const urlField = document.getElementById("primary_image_url");
    const idField = document.getElementById("primary_image_public_id");

    if (urlField) urlField.value = "";
    if (idField) idField.value = "";
  } else {
    const urlsField = document.getElementById("additional_image_urls");
    const idsField = document.getElementById("additional_image_public_ids");

    if (urlsField && idsField) {
      const urls = urlsField.value ? JSON.parse(urlsField.value) : [];
      const ids = idsField.value ? JSON.parse(idsField.value) : [];

      const index = ids.indexOf(publicId);
      if (index > -1) {
        urls.splice(index, 1);
        ids.splice(index, 1);
      }

      urlsField.value = JSON.stringify(urls);
      idsField.value = JSON.stringify(ids);
    }
  }
}

/**
 * Add image preview to UI
 *
 * @param {string} url - Cloudinary URL
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
function addImagePreview(url, publicId, imageType) {
  if (imageType === "primary") {
    const previewContainer = document.getElementById("primaryPreviewContainer");
    const uploadBtn = document.getElementById("primaryUploadBtn");

    if (!previewContainer) {
      console.error("Primary preview container not found");
      return;
    }

    // Check if image already exists to prevent duplicates
    const existingImage = previewContainer.querySelector(
      `[data-public-id="${publicId}"]`
    );
    if (existingImage) {
      console.log("Primary image already exists, skipping duplicate");
      return;
    }

    // Clear existing primary image and hide upload button
    previewContainer.innerHTML = "";
    if (uploadBtn) {
      uploadBtn.style.display = "none";
    }

    const previewDiv = document.createElement("div");
    previewDiv.className = "image-preview primary-preview-item";
    previewDiv.dataset.publicId = publicId;
    previewDiv.dataset.imageType = imageType;

    previewDiv.innerHTML = `
        <img src="${url}" alt="Product primary image" loading="lazy">
        <button type="button" class="remove-image-btn remove-btn" onclick="handleRemoveImage('${publicId}', '${imageType}')" title="Remove image">
            ×
        </button>
    `;

    previewContainer.appendChild(previewDiv);
    previewContainer.style.display = "flex";
  } else if (imageType === "additional") {
    const previewContainer = document.getElementById(
      "additionalPreviewContainer"
    );
    const uploadBtn = document.getElementById("additionalUploadBtn");

    if (!previewContainer) {
      console.error("Additional preview container not found");
      return;
    }

    // Check if image already exists to prevent duplicates
    const existingImage = previewContainer.querySelector(
      `[data-public-id="${publicId}"]`
    );
    if (existingImage) {
      console.log("Additional image already exists, skipping duplicate");
      return;
    }

    // Show the container and add 'active' class
    previewContainer.style.display = "grid";
    previewContainer.classList.add("active");

    const previewDiv = document.createElement("div");
    previewDiv.className = "image-preview image-preview-item";
    previewDiv.dataset.publicId = publicId;
    previewDiv.dataset.imageType = imageType;

    previewDiv.innerHTML = `
        <img src="${url}" alt="Product additional image" loading="lazy">
        <button type="button" class="remove-image-btn remove-btn" onclick="handleRemoveImage('${publicId}', '${imageType}')" title="Remove image">
            ×
        </button>
    `;

    previewContainer.appendChild(previewDiv);
  }
}

/**
 * Remove image preview from UI
 *
 * @param {string} publicId - Cloudinary public ID
 */
function removeImagePreview(publicId) {
  const previewDiv = document.querySelector(
    `.image-preview[data-public-id="${publicId}"]`
  );
  if (previewDiv) {
    const imageType = previewDiv.dataset.imageType;
    previewDiv.remove();

    // Handle primary image removal
    if (imageType === "primary") {
      const uploadBtn = document.getElementById("primaryUploadBtn");
      if (uploadBtn) {
        uploadBtn.style.display = "flex";
      }
    }

    // If this was an additional image and container is now empty, hide it and show upload button
    if (imageType === "additional") {
      const additionalContainer = document.getElementById(
        "additionalPreviewContainer"
      );
      if (additionalContainer && additionalContainer.children.length === 0) {
        additionalContainer.style.display = "none";
        additionalContainer.classList.remove("active");

        // Show upload button when no additional images
        const uploadBtn = document.getElementById("additionalUploadBtn");
        if (uploadBtn) {
          uploadBtn.style.display = "flex";
        }
      }
    }
  }
}

/**
 * Show loading indicator
 *
 * @param {string} imageType - 'primary' or 'additional'
 */
function showLoadingIndicator(imageType) {
  const indicator = document.getElementById(imageType + "LoadingIndicator");
  if (indicator) {
    indicator.style.display = "flex";
  }

  // Disable upload buttons during upload
  disableUploadButtons(true);
}

/**
 * Hide loading indicator
 *
 * @param {string} imageType - 'primary' or 'additional'
 */
function hideLoadingIndicator(imageType) {
  const indicator = document.getElementById(imageType + "LoadingIndicator");
  if (indicator) {
    indicator.style.display = "none";
  }

  // Re-enable upload buttons if no uploads in progress
  if (uploadingCount === 0) {
    disableUploadButtons(false);
  }
}

/**
 * Show success indicator briefly
 *
 * @param {string} imageType - 'primary' or 'additional'
 */
function showSuccessIndicator(imageType) {
  const indicator = document.getElementById(imageType + "SuccessIndicator");
  if (indicator) {
    indicator.style.display = "flex";
    setTimeout(() => {
      indicator.style.display = "none";
    }, 2000);
  }
}

/**
 * Show error message
 *
 * @param {string} message - Error message
 * @param {string} imageType - 'primary' or 'additional'
 */
function showErrorMessage(message, imageType) {
  // Create error notification
  const errorDiv = document.createElement("div");
  errorDiv.className = "image-error-notification";
  errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;

  // Find the appropriate parent container
  const uploadContainer =
    imageType === "primary"
      ? document.querySelector(".primary-image-upload")
      : document.querySelector(".additional-images-upload");

  if (uploadContainer) {
    uploadContainer.appendChild(errorDiv);

    // Remove after 5 seconds
    setTimeout(() => {
      if (errorDiv && errorDiv.parentNode) {
        errorDiv.remove();
      }
    }, 5000);
  }

  console.error("Image error:", message);
}

/**
 * Disable/enable upload buttons
 *
 * @param {boolean} disabled - Whether to disable buttons
 */
function disableUploadButtons(disabled) {
  const primaryBtn = document.getElementById("primaryUploadBtn");
  const additionalBtn = document.getElementById("additionalUploadBtn");

  if (primaryBtn) {
    primaryBtn.style.pointerEvents = disabled ? "none" : "auto";
    primaryBtn.style.opacity = disabled ? "0.5" : "1";
  }

  if (additionalBtn) {
    additionalBtn.style.pointerEvents = disabled ? "none" : "auto";
    additionalBtn.style.opacity = disabled ? "0.5" : "1";
  }
}

/**
 * Update additional images button state
 */
function updateAdditionalImagesButton() {
  const btn = document.getElementById("additionalUploadBtn");
  const input = document.getElementById("additionalImagesInput");
  const container = document.querySelector(".additional-images-upload");

  if (additionalImagesCount >= MAX_ADDITIONAL_IMAGES) {
    if (btn) {
      btn.style.pointerEvents = "none";
      btn.style.opacity = "0.5";
      btn.textContent = `Maximum ${MAX_ADDITIONAL_IMAGES} images reached`;
    }
    if (input) {
      input.disabled = true;
    }
    if (container) {
      container.classList.add("max-reached");
    }
  } else {
    if (btn) {
      btn.style.pointerEvents = "auto";
      btn.style.opacity = "1";
      btn.textContent = `Click to Upload Additional Images (${additionalImagesCount}/${MAX_ADDITIONAL_IMAGES})`;
    }
    if (input) {
      input.disabled = false;
    }
    if (container) {
      container.classList.remove("max-reached");
    }
  }
}

/**
 * Handle remove image button click
 *
 * @param {string} publicId - Cloudinary public ID
 * @param {string} imageType - 'primary' or 'additional'
 */
async function handleRemoveImage(publicId, imageType) {
  // Optional: Confirm deletion
  // if (!confirm('Are you sure you want to remove this image?')) {
  //     return;
  // }

  // Disable the remove button to prevent double-clicks
  const previewDiv = document.querySelector(
    `.image-preview[data-public-id="${publicId}"]`
  );
  if (previewDiv) {
    const removeBtn = previewDiv.querySelector(".remove-image-btn");
    if (removeBtn) {
      removeBtn.disabled = true;
      removeBtn.style.opacity = "0.5";
    }
  }

  const success = await deleteImageFromCloudinary(publicId, imageType);

  if (!success && previewDiv) {
    // Re-enable button if deletion failed
    const removeBtn = previewDiv.querySelector(".remove-image-btn");
    if (removeBtn) {
      removeBtn.disabled = false;
      removeBtn.style.opacity = "1";
    }
  }
}

/**
 * Validate file before upload
 *
 * @param {File} file - File to validate
 * @returns {Object} Validation result
 */
function validateFile(file) {
  const maxSize = 10 * 1024 * 1024; // 10MB
  const allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];

  if (!allowedTypes.includes(file.type)) {
    return {
      valid: false,
      error: "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.",
    };
  }

  if (file.size > maxSize) {
    return {
      valid: false,
      error: "File size exceeds 10MB limit.",
    };
  }

  return { valid: true };
}

/**
 * Handle primary image file input change
 */
async function handlePrimaryImageChange(event) {
  console.log("Primary image change event triggered");
  const file = event.target.files[0];
  if (!file) {
    console.log("No file selected");
    return;
  }

  console.log("Selected file:", file.name, file.type, file.size);

  // Validate file
  const validation = validateFile(file);
  if (!validation.valid) {
    console.error("File validation failed:", validation.error);
    showErrorMessage(validation.error, "primary");
    event.target.value = ""; // Clear input
    return;
  }

  // If there's an existing primary image, delete it first
  const existingPublicId = document.getElementById(
    "primary_image_public_id"
  )?.value;
  if (existingPublicId) {
    console.log("Deleting existing primary image:", existingPublicId);
    await deleteImageFromCloudinary(existingPublicId, "primary");
  }

  // Upload new image
  console.log("Uploading new primary image");
  await uploadImageToCloudinary(file, "primary");

  // Clear input so same file can be selected again if needed
  event.target.value = "";
}

/**
 * Handle additional images file input change
 */
async function handleAdditionalImagesChange(event) {
  console.log("Additional images change event triggered");
  const files = Array.from(event.target.files);
  if (files.length === 0) {
    console.log("No files selected");
    return;
  }

  console.log(
    "Selected files:",
    files.length,
    "Current count:",
    additionalImagesCount
  );

  // Check if we can add more images
  const remainingSlots = MAX_ADDITIONAL_IMAGES - additionalImagesCount;
  if (remainingSlots <= 0) {
    console.log("Maximum images reached");
    showErrorMessage(
      `Maximum ${MAX_ADDITIONAL_IMAGES} additional images allowed.`,
      "additional"
    );
    event.target.value = "";
    return;
  }

  // Limit files to remaining slots
  const filesToUpload = files.slice(0, remainingSlots);

  if (files.length > remainingSlots) {
    console.log("Too many files selected, limiting to", remainingSlots);
    showErrorMessage(
      `Only ${remainingSlots} more image(s) can be added.`,
      "additional"
    );
  }

  // Upload files sequentially
  for (const file of filesToUpload) {
    console.log("Processing file:", file.name);

    // Validate file
    const validation = validateFile(file);
    if (!validation.valid) {
      console.error("File validation failed:", validation.error);
      showErrorMessage(validation.error, "additional");
      continue;
    }

    // Upload image
    console.log("Uploading additional image:", file.name);
    await uploadImageToCloudinary(file, "additional");
  }

  // Clear input
  event.target.value = "";
  console.log(
    "Additional images upload complete. Total count:",
    additionalImagesCount
  );
}

/**
 * Initialize event listeners
 */
function initializeImageUpload() {
  console.log("Initializing image upload...");

  // Prevent multiple initializations
  if (window.imageUploadInitialized) {
    console.log("Image upload already initialized, skipping...");
    return;
  }

  // Clear any conflicting event listeners from add-product.php
  const primaryInput = document.getElementById("primaryImageInput");
  const additionalInput = document.getElementById("additionalImagesInput");

  // Remove all existing event listeners by replacing elements
  if (primaryInput) {
    const newPrimaryInput = primaryInput.cloneNode(true);
    primaryInput.parentNode.replaceChild(newPrimaryInput, primaryInput);
  }

  if (additionalInput) {
    const newAdditionalInput = additionalInput.cloneNode(true);
    additionalInput.parentNode.replaceChild(
      newAdditionalInput,
      additionalInput
    );
  }

  // Get the new elements after replacement
  const primaryInputNew = document.getElementById("primaryImageInput");
  const additionalInputNew = document.getElementById("additionalImagesInput");
  const primaryContainer = document.querySelector(".primary-image-upload");
  const additionalContainer = document.querySelector(
    ".additional-images-upload"
  );

  // Remove any existing click listeners from containers
  if (primaryContainer) {
    primaryContainer.replaceWith(primaryContainer.cloneNode(true));
  }
  if (additionalContainer) {
    additionalContainer.replaceWith(additionalContainer.cloneNode(true));
  }

  // Get the containers again after replacement
  const primaryContainerNew = document.querySelector(".primary-image-upload");
  const additionalContainerNew = document.querySelector(
    ".additional-images-upload"
  );

  if (primaryInputNew && primaryContainerNew) {
    // Add file input change listener
    primaryInputNew.addEventListener("change", handlePrimaryImageChange);

    // Make entire container clickable - single event listener
    primaryContainerNew.addEventListener(
      "click",
      function (e) {
        // Don't trigger if clicking on remove button or existing image
        if (
          e.target.closest(".remove-image-btn") ||
          e.target.closest(".remove-btn") ||
          e.target.closest(".image-preview")
        ) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        primaryInputNew.click();
      },
      { once: false }
    );

    console.log("Primary image upload initialized");
  } else {
    console.error("Primary image elements not found:", {
      primaryInputNew,
      primaryContainerNew,
    });
  }

  if (additionalInputNew && additionalContainerNew) {
    // Add file input change listener
    additionalInputNew.addEventListener("change", handleAdditionalImagesChange);

    // Make entire container clickable - single event listener
    additionalContainerNew.addEventListener(
      "click",
      function (e) {
        // Don't trigger if clicking on remove button or existing image
        if (
          e.target.closest(".remove-image-btn") ||
          e.target.closest(".remove-btn") ||
          e.target.closest(".image-preview")
        ) {
          return;
        }
        // Don't trigger if maximum images reached
        if (additionalImagesCount >= MAX_ADDITIONAL_IMAGES) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        additionalInputNew.click();
      },
      { once: false }
    );

    console.log("Additional image upload initialized");
  } else {
    console.error("Additional image elements not found:", {
      additionalInputNew,
      additionalContainerNew,
    });
  }

  // Count existing additional images on page load and set up container
  const existingAdditionalImages = document.querySelectorAll(
    "#additionalPreviewContainer .image-preview"
  );
  additionalImagesCount = existingAdditionalImages.length;

  // Set up additional container visibility
  const additionalPreviewContainer = document.getElementById(
    "additionalPreviewContainer"
  );
  if (additionalPreviewContainer && existingAdditionalImages.length > 0) {
    additionalPreviewContainer.style.display = "grid";
    additionalPreviewContainer.classList.add("active");
  }

  updateAdditionalImagesButton();

  // Clear any global variables that might interfere
  if (window.additionalImagesArray) {
    window.additionalImagesArray = [];
  }

  // Mark as initialized
  window.imageUploadInitialized = true;

  console.log(
    "Product image AJAX initialized with",
    additionalImagesCount,
    "existing additional images"
  );
}

// Initialize only once when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    // Single initialization with delay to override add-product.php inline scripts
    setTimeout(function () {
      if (!window.imageUploadInitialized) {
        initializeImageUpload();
      }
    }, 150);
  });
} else {
  // If document is already ready, run immediately but only once
  setTimeout(function () {
    if (!window.imageUploadInitialized) {
      initializeImageUpload();
    }
  }, 150);
}

// Make functions globally available
window.handleRemoveImage = handleRemoveImage;
window.uploadImageToCloudinary = uploadImageToCloudinary;
window.deleteImageFromCloudinary = deleteImageFromCloudinary;
