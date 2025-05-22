document.addEventListener('DOMContentLoaded', function() {
  // Add event listeners to AJAX forms
  document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      handleAjaxSubmit(this);
    });
  });
  
  // Password toggle functionality
  document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', function() {
      const input = this.previousElementSibling;
      const icon = this.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });
});

// Function to toggle edit forms visibility
function toggleEditForm(fieldName) {
  const field = document.getElementById(fieldName + '-field');
  const form = document.getElementById(fieldName + '-form');

  if (field.style.display === 'none') {
    field.style.display = 'flex';
    form.style.display = 'none';
    
    // Reset form values
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
      if (input.name !== 'current_password' && input.name !== 'new_password' && input.name !== 'confirm_password') {
        input.value = input.defaultValue;
      } else {
        input.value = '';
      }
    });
  } else {
    field.style.display = 'none';
    form.style.display = 'block';
  }
}

function handleAjaxSubmit(form) {
  const fieldName = form.dataset.field;
  const submitButton = form.querySelector('.save-btn');
  const originalContent = submitButton.innerHTML;
  
  // Show loading state
  form.classList.add('loading');
  submitButton.classList.add('loading');
  submitButton.innerHTML = originalContent + '<span class="loading-spinner"></span>';
  
  // Create form data
  const formData = new FormData(form);
  formData.append('ajax', 'true');
  formData.append('action', 'update_' + fieldName);
  
  // Send AJAX request
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Remove loading state
    form.classList.remove('loading');
    submitButton.classList.remove('loading');
    submitButton.innerHTML = originalContent;
    
    if (data.success) {
      // Update display value if provided
      if (data.value !== undefined) {
        const displayElement = document.getElementById(fieldName + '-display');
        if (displayElement) {
          displayElement.textContent = data.value;
        }
      }
      
      // Hide form and show field
      toggleEditForm(fieldName);
      
      // Show success notification
      showNotification(data.message, 'success');
      
      // Clear password fields
      if (fieldName === 'password') {
        form.querySelectorAll('input[type="password"]').forEach(input => {
          input.value = '';
        });
      }
    } else {
      showNotification(data.message, 'error');
    }
  })
  .catch(error => {
    // Remove loading state
    form.classList.remove('loading');
    submitButton.classList.remove('loading');
    submitButton.innerHTML = originalContent;
    
    showNotification('Erreur de connexion', 'error');
    console.error('Error:', error);
  });
}

function showNotification(message, type) {
  // Remove existing notifications
  document.querySelectorAll('.notification').forEach(n => n.remove());
  
  // Create new notification
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Show notification
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
  
  // Hide notification after 3 seconds
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 3000);
}