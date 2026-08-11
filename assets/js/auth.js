/**
 * FINDLY - Campus Lost & Found
 * Authentication & Form Validation Handler
 */

document.addEventListener('DOMContentLoaded', function () {
  // Initialize Login Form Validation
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      if (!validateLoginForm()) {
        e.preventDefault();
      }
    });

    // Real-time input cleaning on type
    loginForm.querySelectorAll('input').forEach(input => {
      input.addEventListener('input', function () {
        clearInputError(this);
      });
    });
  }

  // Initialize Register Form Validation
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      if (!validateRegisterForm()) {
        e.preventDefault();
      }
    });

    // Real-time input cleaning on type
    registerForm.querySelectorAll('input, select').forEach(input => {
      input.addEventListener('input', function () {
        clearInputError(this);
      });
    });
  }
});

/**
 * Validate Login Form Inputs
 */
function validateLoginForm() {
  let isValid = true;

  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');

  // Validate Email
  if (!emailInput.value.trim()) {
    showInputError(emailInput, 'Email address is required.');
    isValid = false;
  } else if (!isValidEmail(emailInput.value.trim())) {
    showInputError(emailInput, 'Please enter a valid email address.');
    isValid = false;
  } else {
    clearInputError(emailInput);
  }

  // Validate Password
  if (!passwordInput.value) {
    showInputError(passwordInput, 'Password is required.');
    isValid = false;
  } else {
    clearInputError(passwordInput);
  }

  return isValid;
}

/**
 * Validate Register Form Inputs
 */
function validateRegisterForm() {
  let isValid = true;

  const fullName = document.getElementById('full_name');
  const identifier = document.getElementById('identifier');
  const email = document.getElementById('email');
  const contactNumber = document.getElementById('contact_number');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const accountTypeStudent = document.getElementById('account_type_student');
  const accountTypeStaff = document.getElementById('account_type_staff');

  // Full Name
  if (!fullName.value.trim()) {
    showInputError(fullName, 'Full name is required.');
    isValid = false;
  } else {
    clearInputError(fullName);
  }

  // Enrollment / Employee Number
  if (!identifier.value.trim()) {
    showInputError(identifier, 'Enrollment or Employee number is required.');
    isValid = false;
  } else {
    clearInputError(identifier);
  }

  // Email Address
  if (!email.value.trim()) {
    showInputError(email, 'Email address is required.');
    isValid = false;
  } else if (!isValidEmail(email.value.trim())) {
    showInputError(email, 'Please enter a valid email address.');
    isValid = false;
  } else {
    clearInputError(email);
  }

  // Contact Number
  if (!contactNumber.value.trim()) {
    showInputError(contactNumber, 'Contact number is required.');
    isValid = false;
  } else if (!isValidPhone(contactNumber.value.trim())) {
    showInputError(contactNumber, 'Please enter a valid contact phone number.');
    isValid = false;
  } else {
    clearInputError(contactNumber);
  }

  // Password
  if (!password.value) {
    showInputError(password, 'Password is required.');
    isValid = false;
  } else if (password.value.length < 6) {
    showInputError(password, 'Password must be at least 6 characters long.');
    isValid = false;
  } else {
    clearInputError(password);
  }

  // Confirm Password
  if (!confirmPassword.value) {
    showInputError(confirmPassword, 'Please confirm your password.');
    isValid = false;
  } else if (confirmPassword.value !== password.value) {
    showInputError(confirmPassword, 'Passwords do not match.');
    isValid = false;
  } else {
    clearInputError(confirmPassword);
  }

  // Account Type
  const accountTypeContainer = document.getElementById('account_type_container');
  if (!accountTypeStudent.checked && !accountTypeStaff.checked) {
    if (accountTypeContainer) {
      showErrorAlert(accountTypeContainer, 'Please select an Account Type (Student or Staff).');
    }
    isValid = false;
  }

  return isValid;
}

/**
 * Utility: Email regex check
 */
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

/**
 * Utility: Phone regex check
 */
function isValidPhone(phone) {
  const re = /^[0-9+\-\s()]{7,15}$/;
  return re.test(phone);
}

/**
 * Display inline input error
 */
function showInputError(inputElement, message) {
  const formGroup = inputElement.closest('.form-group-custom');
  if (!formGroup) return;

  inputElement.classList.add('is-invalid');
  formGroup.classList.add('has-error');

  let errorContainer = formGroup.querySelector('.invalid-feedback-custom');
  if (!errorContainer) {
    errorContainer = document.createElement('div');
    errorContainer.className = 'invalid-feedback-custom';
    formGroup.appendChild(errorContainer);
  }

  errorContainer.textContent = message;
  errorContainer.style.display = 'block';
}

/**
 * Clear inline input error
 */
function clearInputError(inputElement) {
  const formGroup = inputElement.closest('.form-group-custom');
  if (!formGroup) return;

  inputElement.classList.remove('is-invalid');
  formGroup.classList.remove('has-error');

  const errorContainer = formGroup.querySelector('.invalid-feedback-custom');
  if (errorContainer) {
    errorContainer.style.display = 'none';
  }
}

/**
 * General Alert Message
 */
function showErrorAlert(container, message) {
  let alert = container.querySelector('.alert-custom');
  if (!alert) {
    alert = document.createElement('div');
    alert.className = 'alert-custom alert alert-danger';
    container.appendChild(alert);
  }
  alert.textContent = message;
  alert.style.display = 'block';
}
