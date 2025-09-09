document.addEventListener("DOMContentLoaded", function () {
  // Get form elements
  const loginForm = document.getElementById("login-form");
  const signupForm = document.getElementById("signup-form");
  const showSignupLink = document.getElementById("show-signup");
  const showLoginLink = document.getElementById("show-login");

  // Form switching functionality
  function showLogin() {
    if (loginForm && signupForm) {
      loginForm.classList.remove("hidden");
      signupForm.classList.add("hidden");
    }
  }

  function showSignup() {
    if (loginForm && signupForm) {
      signupForm.classList.remove("hidden");
      loginForm.classList.add("hidden");
    }
  }

  // Event listeners for form switching
  if (showSignupLink) {
    showSignupLink.addEventListener("click", function (e) {
      e.preventDefault();
      showSignup();
    });
  }

  if (showLoginLink) {
    showLoginLink.addEventListener("click", function (e) {
      e.preventDefault();
      showLogin();
    });
  }

  // Enhanced form validation
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function validatePassword(password) {
    return password.length >= 8;
  }

  function validateName(name) {
    const nameRegex = /^[a-zA-Z-' ]*$/;
    return nameRegex.test(name) && name.trim().length > 0;
  }

  // Real-time validation for signup form
  const signupInputs = document.querySelectorAll("#signup-form input");
  signupInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      validateInput(this);
    });

    input.addEventListener("input", function () {
      clearValidationState(this);
    });
  });

  // Real-time validation for login form
  const loginInputs = document.querySelectorAll("#login-form input");
  loginInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      validateInput(this);
    });

    input.addEventListener("input", function () {
      clearValidationState(this);
    });
  });

  function validateInput(input) {
    const value = input.value.trim();
    let isValid = true;
    let errorMessage = "";

    switch (input.type) {
      case "email":
        isValid = validateEmail(value);
        errorMessage = "Please enter a valid email address";
        break;
      case "password":
        if (input.name === "password") {
          isValid = validatePassword(value);
          errorMessage = "Password must be at least 8 characters long";
        } else if (input.name === "confirm-password") {
          const passwordField = document.querySelector(
            'input[name="password"]'
          );
          isValid = value === passwordField.value;
          errorMessage = "Passwords do not match";
        }
        break;
      case "text":
        if (input.name === "firstname" || input.name === "lastname") {
          isValid = validateName(value);
          errorMessage = "Please enter a valid name (letters only)";
        } else {
          isValid = value.length > 0;
          errorMessage = "This field is required";
        }
        break;
    }

    if (!isValid && value.length > 0) {
      showInputError(input, errorMessage);
    } else {
      clearInputError(input);
    }
  }

  function showInputError(input, message) {
    clearInputError(input);
    input.style.borderColor = "#ef4444";
    input.style.boxShadow = "0 0 0 3px rgba(239, 68, 68, 0.1)";

    const errorDiv = document.createElement("div");
    errorDiv.className = "error-message";
    errorDiv.style.cssText = `
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            margin-bottom: 0.5rem;
        `;
    errorDiv.textContent = message;

    input.parentNode.appendChild(errorDiv);
  }

  function clearInputError(input) {
    input.style.borderColor = "#e5e7eb";
    input.style.boxShadow = "";

    const errorMsg = input.parentNode.querySelector(".error-message");
    if (errorMsg) {
      errorMsg.remove();
    }
  }

  function clearValidationState(input) {
    clearInputError(input);
  }

  // Form submission with loading states
  const submitButtons = document.querySelectorAll('input[type="submit"]');
  submitButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const form = this.closest("form");
      const inputs = form.querySelectorAll("input[required]");
      let isFormValid = true;

      // Validate all required fields
      inputs.forEach((input) => {
        if (!input.value.trim()) {
          isFormValid = false;
          showInputError(input, "This field is required");
        }
      });

      if (!isFormValid) {
        // Prevent form submission if validation fails
        e.preventDefault();
        return false;
      }

      // If form is valid, allow submission and show loading state
      // Show loading state
      this.disabled = true;
      this.value = "Processing...";

      // Re-enable after 5 seconds (in case of slow response)
      setTimeout(() => {
        this.disabled = false;
        this.value =
          this.name === "signup-submit" ? "Create Account" : "Login";
      }, 5000);
    });
  });

  // Enhanced keyboard navigation
  document.addEventListener("keydown", function (e) {
    // ESC key to close alerts
    if (e.key === "Escape") {
      const alerts = document.querySelectorAll(".alert.show, .salert.show");
      alerts.forEach((alert) => {
        alert.style.display = "none";
        alert.classList.remove("show");
      });
    }

    // Enter key to submit form
    if (e.key === "Enter" && e.target.matches("input")) {
      const form = e.target.closest("form");
      if (form) {
        const submitBtn = form.querySelector('input[type="submit"]');
        if (submitBtn) {
          submitBtn.click();
        }
      }
    }

    // Tab navigation enhancement
    if (e.key === "Tab") {
      const activeForm = document.querySelector(".auth-form:not(.hidden)");
      if (activeForm) {
        const focusableElements = activeForm.querySelectorAll(
          'input, button, a, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey && document.activeElement === firstElement) {
          e.preventDefault();
          lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    }
  });

  // Auto-close alerts after 10 seconds
  function setupAutoCloseAlerts() {
    const alerts = document.querySelectorAll(".alert.show, .salert.show");
    alerts.forEach((alert) => {
      setTimeout(() => {
        if (alert.classList.contains("show")) {
          alert.style.opacity = "0";
          setTimeout(() => {
            alert.style.display = "none";
            alert.classList.remove("show");
          }, 300);
        }
      }, 10000);
    });
  }

  // Initialize auto-close for any existing alerts
  setupAutoCloseAlerts();

  // Password visibility toggle (optional enhancement)
  function addPasswordToggle() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach((input) => {
      const wrapper = document.createElement("div");
      wrapper.style.position = "relative";
      wrapper.style.display = "flex";
      wrapper.style.alignItems = "center";

      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      const toggleBtn = document.createElement("button");
      toggleBtn.type = "button";
      toggleBtn.innerHTML = "👁️";
      toggleBtn.style.cssText = `
                position: absolute;
                right: 0.75rem;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 1rem;
                color: #6b7280;
                padding: 0;
                z-index: 10;
            `;

      toggleBtn.addEventListener("click", function () {
        if (input.type === "password") {
          input.type = "text";
          this.innerHTML = "🙈";
        } else {
          input.type = "password";
          this.innerHTML = "👁️";
        }
      });

      wrapper.appendChild(toggleBtn);
    });
  }

  // Uncomment the next line to enable password visibility toggle
  // addPasswordToggle();

  // Check if we need to switch forms based on URL parameters or user preference
  const urlParams = new URLSearchParams(window.location.search);
  const formType = urlParams.get("form");

  if (formType === "signup") {
    showSignup();
  } else {
    showLogin(); // Default to login
  }

  // Initialize form state
  showLogin();

  // Smooth animations for form elements
  const animateElements = document.querySelectorAll(
    ".input-field, .submit-btn, .toggle-link"
  );
  animateElements.forEach((element, index) => {
    element.style.opacity = "0";
    element.style.transform = "translateY(20px)";

    setTimeout(() => {
      element.style.transition = "all 0.5s ease";
      element.style.opacity = "1";
      element.style.transform = "translateY(0)";
    }, index * 100);
  });

  // Add focus management for better accessibility
  function manageFocus() {
    const activeForm = document.querySelector(".auth-form:not(.hidden)");
    if (activeForm) {
      const firstInput = activeForm.querySelector("input");
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
      }
    }
  }

  // Call focus management when forms switch
  if (showSignupLink) {
    showSignupLink.addEventListener("click", manageFocus);
  }
  if (showLoginLink) {
    showLoginLink.addEventListener("click", manageFocus);
  }

  console.log("Login/Signup redesigned page initialized successfully");
});
