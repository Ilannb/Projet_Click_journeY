document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.action-buttons').forEach(div => {
    div.style.display = 'block';
  });
  
  // Add event listeners to AJAX action buttons
  document.querySelectorAll('.ajax-action').forEach(button => {
    button.addEventListener('click', function() {
      const action = this.dataset.action;
      const userId = this.dataset.userId;
      
      // Confirm deletion
      if (action === 'delete') {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
          return;
        }
      }
      
      performUserAction(action, userId, this);
    });
  });
});

function performUserAction(action, userId, button) {
  // Show loading state
  button.classList.add('loading');
  const originalContent = button.innerHTML;
  button.innerHTML = originalContent + '<span class="loading-spinner"></span>';
  
  // Create form data
  const formData = new FormData();
  formData.append('ajax', 'true');
  formData.append('action', action);
  formData.append('user_id', userId);
  
  // Send AJAX request
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Remove loading state
    button.classList.remove('loading');
    button.innerHTML = originalContent;
    
    if (data.success) {
      if (data.deleted) {
        // Remove the row from table
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        row.remove();
      } else if (data.new_role) {
        // Update the user's role and buttons
        updateUserRole(userId, data.new_role);
      }
      showNotification(data.message, 'success');
    } else {
      showNotification(data.message, 'error');
    }
  })
  .catch(error => {
    // Remove loading state
    button.classList.remove('loading');
    button.innerHTML = originalContent;
    showNotification('Erreur de connexion', 'error');
    console.error('Error:', error);
  });
}

function updateUserRole(userId, newRole) {
  const row = document.querySelector(`tr[data-user-id="${userId}"]`);
  const statusSpan = row.querySelector('.status');
  const actionsDiv = row.querySelector('.action-buttons');
  
  // Update status display
  statusSpan.className = `status ${newRole.toLowerCase()}`;
  statusSpan.dataset.role = newRole;
  
  switch(newRole) {
    case 'admin':
      statusSpan.textContent = 'Admin';
      break;
    case 'vip':
      statusSpan.textContent = 'VIP';
      break;
    case 'user':
      statusSpan.textContent = '';
      break;
    case 'banned':
      statusSpan.textContent = 'Banni';
      break;
  }
  
  // Update action buttons
  let buttonsHTML = '';
  
  if (newRole !== 'banned') {
    if (newRole === 'user') {
      buttonsHTML += `<button type="button" class="action-btn promote ajax-action" data-action="promote" data-user-id="${userId}" title="Promouvoir">
                       <i class="fas fa-crown"></i>
                     </button>`;
    } else if (newRole === 'vip') {
      buttonsHTML += `<button type="button" class="action-btn demote ajax-action" data-action="demote" data-user-id="${userId}" title="Rétrograder">
                       <i class="fas fa-level-down-alt"></i>
                     </button>`;
    }
    
    buttonsHTML += `<button type="button" class="action-btn ban ajax-action" data-action="ban" data-user-id="${userId}" title="Bannir">
                     <i class="fas fa-ban"></i>
                   </button>`;
  } else {
    buttonsHTML += `<button type="button" class="action-btn unban ajax-action" data-action="unban" data-user-id="${userId}" title="Débannir">
                     <i class="fas fa-unlock"></i>
                   </button>`;
  }
  
  buttonsHTML += `<button type="button" class="action-btn delete ajax-action" data-action="delete" data-user-id="${userId}" title="Supprimer">
                   <i class="fas fa-trash"></i>
                 </button>`;
  
  actionsDiv.innerHTML = buttonsHTML;
  
  // Re-attach event listeners to new buttons
  actionsDiv.querySelectorAll('.ajax-action').forEach(button => {
    button.addEventListener('click', function() {
      const action = this.dataset.action;
      const userId = this.dataset.userId;
      
      if (action === 'delete') {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
          return;
        }
      }
      
      performUserAction(action, userId, this);
    });
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