// Chatbot Knowledge Base JavaScript
let previewMode = "formatted";
let currentContent = "";
let lastSavedContent = "";

// Initialize the application
$(document).ready(function () {
  initializeKnowledgeBase();
  setupEventListeners();
  loadCurrentKnowledge();
});

// Initialize the knowledge base functionality
function initializeKnowledgeBase() {
  console.log("Chatbot Knowledge Base initialized");
  updateWordCount();
}

// Set up all event listeners
function setupEventListeners() {
  // Content change handler
  $("#knowledge-content").on("input", function () {
    currentContent = $(this).val();
    updatePreview();
    updateWordCount();

    // Auto-save indicator (optional)
    if (currentContent !== lastSavedContent) {
      $(".btn-primary").addClass("unsaved");
    }
  });

  // Form submission
  $("#knowledge-form").on("submit", function (e) {
    e.preventDefault();
    saveKnowledge();
  });

  // Keyboard shortcuts
  $(document).on("keydown", function (e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === "s") {
      e.preventDefault();
      saveKnowledge();
    }

    // Ctrl+R or Cmd+R to reset (with confirmation)
    if ((e.ctrlKey || e.metaKey) && e.key === "r") {
      e.preventDefault();
      resetContent();
    }
  });

  // Window resize handler for responsive adjustments
  $(window).on("resize", function () {
    adjustLayout();
  });
}

// Load current knowledge from server
function loadCurrentKnowledge() {
  showMessage("Loading knowledge base...", "info");

  fetch("get-knowledge.php")
    .then((response) => response.json())
    .then((res) => {
      if (res.success) {
        const content = res.content || "";
        $("#knowledge-content").val(content);
        $("#knowledge-form").data("id", res.id);

        currentContent = content;
        lastSavedContent = content;

        updatePreview();
        updateWordCount();

        hideMessage();

        if (content) {
          showMessage("Knowledge base loaded successfully!", "success", 3000);
        }
      } else {
        showMessage(
          "Error loading knowledge base: " + (res.error || "Unknown error"),
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Failed to load knowledge base:", error);
      showMessage(
        "Failed to load knowledge base. Please check your connection.",
        "error"
      );
    });
}

// Save knowledge to server
function saveKnowledge() {
  const content = $("#knowledge-content").val().trim();

  if (!content) {
    showMessage("Please enter some content before saving.", "error");
    return;
  }

  // Show loading state
  const submitBtn = $(".btn-primary");
  const originalHtml = submitBtn.html();
  submitBtn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin"></i> Saving...')
    .addClass("loading");

  fetch("save-knowledge.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "content=" + encodeURIComponent(content),
  })
    .then((response) => response.json())
    .then((res) => {
      if (res.success) {
        lastSavedContent = content;
        showMessage("Knowledge base saved successfully!", "success", 3000);
        submitBtn.removeClass("unsaved");
        updateLastUpdated();
      } else {
        showMessage(
          "Failed to save: " + (res.error || "Unknown error occurred"),
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Failed to save knowledge base:", error);
      showMessage("Failed to save knowledge base. Please try again.", "error");
    })
    .finally(() => {
      // Restore button state
      submitBtn
        .prop("disabled", false)
        .html(originalHtml)
        .removeClass("loading");
    });
}

// Update the preview based on current mode
function updatePreview() {
  const content = $("#knowledge-content").val();
  const previewContainer = $("#knowledge-preview");

  if (!content.trim()) {
    previewContainer.html(`
            <div class="preview-placeholder">
                <i class="fas fa-file-alt"></i>
                <p>Start typing in the editor to see your content preview...</p>
            </div>
        `);
    return;
  }

  if (previewMode === "formatted") {
    const linkifiedContent = linkifyText(content);
    const formattedContent = formatContent(linkifiedContent);
    previewContainer.html(formattedContent);
  } else {
    const chatPreview = createChatPreview(content);
    previewContainer.html(chatPreview);
  }
}

// Convert URLs to clickable links
function linkifyText(text) {
  const urlRegex =
    /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;

  return text.replace(urlRegex, function (url) {
    const href = url.startsWith("http") ? url : "https://" + url;
    return `<a href="${href}" target="_blank" rel="noopener noreferrer">${url}</a>`;
  });
}

// Format content for better readability
function formatContent(content) {
  // First apply text formatting (bold/italic)
  let formatted = content;

  // Convert **bold** and __bold__ to <strong>
  formatted = formatted.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
  formatted = formatted.replace(/__(.*?)__/g, "<strong>$1</strong>");

  // Convert *italic* and _italic_ to <em>
  formatted = formatted.replace(/\*(.*?)\*/g, "<em>$1</em>");
  formatted = formatted.replace(/_(.*?)_/g, "<em>$1</em>");

  // Convert line breaks to paragraphs
  const paragraphs = formatted.split("\n\n").filter((p) => p.trim());

  return paragraphs
    .map((paragraph) => {
      const trimmed = paragraph.trim();

      // Check if it's a list item
      if (
        trimmed.startsWith("•") ||
        trimmed.startsWith("-") ||
        trimmed.startsWith("*")
      ) {
        const lines = trimmed.split("\n").filter((line) => line.trim());
        const listItems = lines
          .map((line) => {
            const cleaned = line.replace(/^[•\-*]\s*/, "");
            return `<li>${cleaned}</li>`;
          })
          .join("");
        return `<ul>${listItems}</ul>`;
      }

      // Check if it's a header (starts with capitals and is short)
      if (trimmed.length < 80 && /^[A-Z][^.!?]*$/.test(trimmed)) {
        return `<h4>${trimmed}</h4>`;
      }

      // Regular paragraph
      return `<p>${trimmed.replace(/\n/g, "<br>")}</p>`;
    })
    .join("");
}

// Create chat-style preview
function createChatPreview(content) {
  const chunks = content.split("\n\n").filter((chunk) => chunk.trim());

  let chatHtml = '<div class="chat-preview">';

  chunks.forEach((chunk, index) => {
    const isBot = index % 2 === 0;
    const messageClass = isBot ? "chat-message bot" : "chat-message";
    const linkified = linkifyText(chunk.trim());

    chatHtml += `<div class="${messageClass}">${linkified}</div>`;
  });

  chatHtml += "</div>";
  return chatHtml;
}

// Toggle preview mode
function togglePreviewMode(mode) {
  previewMode = mode;

  // Update active states
  $(".preview-tools .tool-btn").removeClass("active");
  $(
    `.preview-tools .tool-btn[onclick="togglePreviewMode('${mode}')"]`
  ).addClass("active");

  updatePreview();
}

// Update word count only
function updateWordCount() {
  const content = $("#knowledge-content").val();
  const words = content.trim() ? content.trim().split(/\s+/).length : 0;
  $("#word-count").text(words);
}

// Update last updated timestamp
function updateLastUpdated() {
  const now = new Date();
  const formatted =
    now.toLocaleDateString() +
    " " +
    now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  $("#last-updated").text(formatted);
}

// Text formatting functions
function formatText(type) {
  const textarea = document.getElementById("knowledge-content");
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selectedText = textarea.value.substring(start, end);

  if (!selectedText) {
    showMessage("Please select text to format.", "error", 3000);
    return;
  }

  let formattedText = selectedText;

  switch (type) {
    case "bold":
      formattedText = `**${selectedText}**`;
      break;
    case "italic":
      formattedText = `*${selectedText}*`;
      break;
  }

  textarea.setRangeText(formattedText, start, end);
  textarea.focus();

  // Update preview
  $("#knowledge-content").trigger("input");
}

// Insert template
function insertTemplate() {
  const template = `Cafe Information:
• Name: [Your Cafe Name]
• Location: [Your Address]
• Hours: [Opening Hours]
• Phone: [Phone Number]
• Website: [Your Website]

Menu Highlights:
• [Popular Item 1] - [Description and Price]
• [Popular Item 2] - [Description and Price]
• [Popular Item 3] - [Description and Price]

Services:
• Dine-in service
• Takeaway orders
• Delivery available
• Special events hosting

Policies:
• Refund policy: [Your policy]
• Delivery charges: [Your charges]
• Minimum order: [Amount if applicable]

Special Offers:
• [Current promotions]
• [Loyalty program details]
• [Special discounts]

Contact Information:
For more information, visit our website: [Your Website]
Follow us on social media: [Social Media Links]`;

  const textarea = $("#knowledge-content");
  const currentPos = textarea[0].selectionStart;
  const content = textarea.val();

  const newContent =
    content.substring(0, currentPos) + template + content.substring(currentPos);
  textarea.val(newContent);
  textarea.trigger("input");
  textarea.focus();
}

// Reset content with confirmation
function resetContent() {
  if (currentContent && currentContent !== lastSavedContent) {
    if (!confirm("You have unsaved changes. Are you sure you want to reset?")) {
      return;
    }
  }

  $("#knowledge-content").val(lastSavedContent);
  currentContent = lastSavedContent;
  updatePreview();
  updateStatistics();
  $(".btn-primary").removeClass("unsaved");

  showMessage("Content reset to last saved version.", "info", 3000);
}

// Show message to user
function showMessage(text, type = "info", duration = 0) {
  // Remove existing messages
  $(".message").remove();

  const messageHtml = `
        <div class="message ${type}">
            <i class="fas fa-${getMessageIcon(type)}"></i>
            ${text}
        </div>
    `;

  $(".chatbot-header").after(messageHtml);

  if (duration > 0) {
    setTimeout(() => {
      $(".message").fadeOut(300, function () {
        $(this).remove();
      });
    }, duration);
  }
}

// Hide message
function hideMessage() {
  $(".message").fadeOut(300, function () {
    $(this).remove();
  });
}

// Get icon for message type
function getMessageIcon(type) {
  switch (type) {
    case "success":
      return "check-circle";
    case "error":
      return "exclamation-triangle";
    case "info":
      return "info-circle";
    default:
      return "info-circle";
  }
}

// Adjust layout for responsive design
function adjustLayout() {
  // This can be expanded for more responsive adjustments
  const width = $(window).width();

  if (width < 968) {
    // Mobile adjustments
    $(".knowledge-textarea").css("min-height", "300px");
  } else {
    // Desktop adjustments
    $(".knowledge-textarea").css("min-height", "500px");
  }
}

// Auto-save functionality (optional)
let autoSaveTimer;
function startAutoSave() {
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(() => {
    if (currentContent !== lastSavedContent && currentContent.trim()) {
      console.log("Auto-saving...");
      saveKnowledge();
    }
  }, 30000); // Auto-save after 30 seconds of inactivity
}

// Copy knowledge to clipboard
function copyToClipboard() {
  const content = $("#knowledge-content").val();
  if (!content) {
    showMessage("No content to copy.", "error", 3000);
    return;
  }

  navigator.clipboard
    .writeText(content)
    .then(() => {
      showMessage("Content copied to clipboard!", "success", 3000);
    })
    .catch(() => {
      showMessage("Failed to copy content.", "error", 3000);
    });
}

// Export functions for global access
window.togglePreviewMode = togglePreviewMode;
window.formatText = formatText;
window.resetContent = resetContent;
window.copyToClipboard = copyToClipboard;
