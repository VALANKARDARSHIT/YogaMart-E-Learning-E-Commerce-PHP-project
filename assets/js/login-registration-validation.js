/**
 * YogaMart Login & Registration Helper Utilities
 * Contains validation and UI feedback functions.
 * Form submission is handled via AJAX in login-registration.php
 */

// --- UI Feedback Functions ---

/**
 * Shows the custom popup overlay with a message.
 */
function showPopup(msg, content) {
    const popup = document.getElementById("popup");
    if (popup) {
        const title = popup.querySelector("h2");
        const body = popup.querySelector("p");
        if (title) title.textContent = msg;
        if (body) body.textContent = content;
        popup.style.display = "flex";
    }
}

/**
 * Generic toast notification
 */
function showGlobalToast(message, type = 'success') {
    if (window.showToast) {
        window.showToast(message, type);
    }
}

// --- Validation Helpers ---

/**
 * Validates email format using regex
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Displays an error message under a specific input field
 */
function showError(fieldId, message) { 
    const field = document.getElementById(fieldId);
    if (!field) return;

    let errorElement = document.getElementById(`${fieldId}-error`);
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.id = `${fieldId}-error`;
        errorElement.className = 'inline-error'; // Use the new styled class
        
        // Find appropriate container
        const inputGroup = field.closest('.input-group');
        const termsContainer = field.closest('.terms');
        
        if (inputGroup) {
            inputGroup.appendChild(errorElement);
        } else if (termsContainer) {
            termsContainer.appendChild(errorElement);
        } else {
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
    }

    field.classList.add('error');
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    
    // Auto-clear when user types
    field.oninput = () => {
        field.classList.remove('error');
        errorElement.style.display = 'none';
    };
}

/**
 * Clears all visible error messages and highlights
 */
function clearErrorMessages() {
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.inline-error').forEach(el => el.style.display = 'none');
}

// --- Password Visibility ---

/**
 * Toggles input type between password and text
 */
function togglePassword(fieldId, toggleElement) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    const icon = toggleElement.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        field.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}
// --- Forgot Password Modal ---

function openForgot() {
    const modal = document.getElementById('forgotModal');
    if (modal) modal.style.display = 'flex';
}

function closeForgot() {
    const modal = document.getElementById('forgotModal');
    if (modal) modal.style.display = 'none';
}

function submitForgot(btn) {
    const emailInput = document.getElementById('forgot-email');
    const email = emailInput ? emailInput.value.trim() : '';
    
    clearErrorMessages();

    if (!email) {
        showError('forgot-email', 'Please enter your email address.');
        return;
    }
    
    if (!isValidEmail(email)) {
        showError('forgot-email', 'Please enter a valid email address.');
        return;
    }
    
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Sending...';
    }

    const formData = new FormData();
    formData.append('forgot_email', email);

    fetch('login-registration.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.isForgotOtpContext = true;
            window.isLoginOtpContext = false;
            closeForgot();
            // showPopup is defined in this file and handled in login-registration.php
            showPopup("OTP Sent", "A password reset OTP has been sent to " + email);
        } else {
            showGlobalToast(data.error || 'Failed to send OTP.', 'error');
        }
    })
    .catch(err => {
        console.error('Forgot Password Error:', err);
        showGlobalToast('Network error. Please try again.', 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';
        }
    });
}

// Log loading status for debugging
console.log('YogaMart Utilities Loaded.');
