document.addEventListener('DOMContentLoaded', function () {
  // Page detection
  const isProfilePage = document.getElementById('password-form') !== null;

  // Password fields by page
  let activePasswordField, confirmPasswordField, currentPasswordField, submitButton, acceptCheckbox;

  if (isProfilePage) {
    currentPasswordField = document.getElementById('current_password');
    activePasswordField = document.getElementById('new_password');
    confirmPasswordField = document.getElementById('confirm_password');
    submitButton = document.querySelector('button[name="update_password"]');
  } else {
    const passwordField = document.getElementById('password');
    confirmPasswordField = document.getElementById('confirm-password');
    const newPasswordField = document.getElementById('new-password');
    activePasswordField = passwordField || newPasswordField;
    submitButton = document.querySelector('.register-btn') || document.querySelector('.reset-btn');
    acceptCheckbox = document.getElementById('accept-checkbox');
  }

  if (!activePasswordField) return;

  const criteriaItems = document.querySelectorAll('.password-requirements li');
  if (criteriaItems.length === 0) return;

  // Add icons
  if (isProfilePage) {
    criteriaItems.forEach(item => {
      if (!item.querySelector('i')) {
        const icon = document.createElement('i');
        icon.className = 'fas fa-xmark';
        item.prepend(icon);
      }
    });
  }

  // Regex
  const criteria = [
    { regex: /.{8,}/, index: 0, description: 'Au moins 8 caractères' },
    { regex: /[A-Z]/, index: 1, description: 'Une lettre majuscule' },
    { regex: /[a-z]/, index: 2, description: 'Une lettre minuscule' },
    { regex: /[0-9]/, index: 3, description: 'Un chiffre' },
    { regex: /[@$!%*?&\-]/, index: 4, description: 'Un caractère spécial' }
  ];

  // Disables the default button
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.classList.add('disabled-btn');
  }

  // Check all criteria
  function areAllCriteriaMet(password) {
    return criteria.every(criterion => criterion.regex.test(password));
  }

  // Update criteria
  function updateCriteriaDisplay(password) {
    criteria.forEach(criterion => {
      const item = criteriaItems[criterion.index];
      if (!item) return;

      const icon = item.querySelector('i');
      if (!icon) return;

      if (criterion.regex.test(password)) {
        icon.className = 'fas fa-check';
        item.classList.add('valid');
        item.classList.remove('invalid');
      } else {
        icon.className = 'fas fa-xmark';
        item.classList.add('invalid');
        item.classList.remove('valid');
      }
    });
  }

  // Check if the passwords match
  function checkPasswordsMatch() {
    if (!confirmPasswordField) return true;

    const password = activePasswordField.value;
    const confirmPassword = confirmPasswordField.value;

    if (confirmPassword === '') return false;

    if (password === confirmPassword) {
      confirmPasswordField.classList.add('match');
      confirmPasswordField.classList.remove('no-match');
      return true;
    } else {
      confirmPasswordField.classList.add('no-match');
      confirmPasswordField.classList.remove('match');
      return false;
    }
  }

  // Check if password is filled in
  function isCurrentPasswordFilled() {
    return !isProfilePage || (currentPasswordField && currentPasswordField.value.trim() !== '');
  }

  // Enables/disables the button
  function validateForm() {
    // Checking all criteria
    const password = activePasswordField.value;
    const criteriaMet = areAllCriteriaMet(password);
    const passwordsMatch = checkPasswordsMatch();

    // Specific checks according to the page
    let additionalChecks = true;

    if (isProfilePage) {
      // Verification for profile page
      additionalChecks = isCurrentPasswordFilled();
    } else {
      // Verification for the register page
      additionalChecks = !acceptCheckbox || acceptCheckbox.checked;
    }

    // Enables/disables the button
    if (submitButton) {
      const isFormValid = criteriaMet && passwordsMatch && additionalChecks;
      submitButton.disabled = !isFormValid;

      if (isFormValid) {
        submitButton.classList.remove('disabled-btn');
      } else {
        submitButton.classList.add('disabled-btn');
      }
    }
  }

  // Event listeners
  activePasswordField.addEventListener('input', function () {
    updateCriteriaDisplay(this.value);
    validateForm();
  });

  if (confirmPasswordField) {
    confirmPasswordField.addEventListener('input', validateForm);
  }

  if (acceptCheckbox) {
    acceptCheckbox.addEventListener('change', validateForm);
  }

  if (isProfilePage && currentPasswordField) {
    currentPasswordField.addEventListener('input', validateForm);
  }

  // Styles CSS
  const style = document.createElement('style');
  style.textContent = `
    .password-requirements li {
      transition: color 0.3s ease;
    }
    .password-requirements li.valid {
      color: var(--green-color);
    }
    .password-requirements li.invalid {
      color: var(--red-color);
    }
    .password-requirements li i.fa-check {
      color: var(--green-color);
    }
    .password-requirements li i.fa-xmark {
      color: var(--red-color);
    }
    input.match {
      border: 1px solid var(--green-color) !important;
    }
    input.no-match {
      border: 1px solid var(--red-color) !important;
    }
    .disabled-btn {
      opacity: 0.6;
      cursor: not-allowed !important;
    }
  `;
  document.head.appendChild(style);

  // Password display/hiding
  const toggleButtons = document.querySelectorAll('.password-toggle');
  toggleButtons.forEach(button => {
    button.addEventListener('click', function () {
      const input = this.previousElementSibling;
      const icon = this.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
      }
    });
  });

  // Validation on page load
  updateCriteriaDisplay(activePasswordField.value);
  validateForm();
});