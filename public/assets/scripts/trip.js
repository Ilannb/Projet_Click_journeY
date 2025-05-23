document.addEventListener('DOMContentLoaded', function() {
  const startDateInput = document.getElementById('temp_start_date');
  const endDateInput = document.getElementById('end_date');
  const updateButton = document.querySelector('.update-dates-button');
  
  // Fetch the duration from a data-duration attribute on a HTML element
  const durationElement = document.querySelector('[data-duration]');
  const duration = durationElement ? parseInt(durationElement.getAttribute('data-duration')) : 
                   calculateDurationFromDates() || 7;
  
  // Function to estimate the duration from available data
  function calculateDurationFromDates() {
      const start = new Date(startDateInput.value);
      const end = new Date(endDateInput.value);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      return diffDays;
  }
  
  // Function to estimate the end date
  function calculateEndDate(startDate) {
      const start = new Date(startDate);
      const end = new Date(start);
      end.setDate(start.getDate() + duration - 1);
      return end.toISOString().split('T')[0];
  }
  
  // Automatic update of the end date
  startDateInput.addEventListener('change', function() {
      const newEndDate = calculateEndDate(this.value);
      endDateInput.value = newEndDate;
      
      // Url update without reloading
      const url = new URL(window.location);
      url.searchParams.set('temp_start_date', this.value);
      window.history.replaceState({}, '', url);
      
      // Optionnal : Displays a confirmation message
      showUpdateMessage();
  });
  
  // Prevents reloading when clicking the button
  updateButton.addEventListener('click', function(e) {
      e.preventDefault();
      
      // If needed, Manually triggers the event change 
      startDateInput.dispatchEvent(new Event('change'));
      
      // Displays a confirmation message
      showUpdateMessage();
  });
  
  // Function that displays a confirmation message
  function showUpdateMessage() {
      // Creates or updates the message
      let message = document.getElementById('date-update-message');
      if (!message) {
          message = document.createElement('div');
          message.id = 'date-update-message';
          message.className = 'update-message';
          message.innerHTML = '<i class="fas fa-check-circle"></i> Dates mises à jour avec succès !';
          
          // Insert the message after the form
          const form = document.querySelector('.date-selection-form');
          form.parentNode.insertBefore(message, form.nextSibling);
      }
      
      // Displays the message with an animation
      message.style.display = 'block';
      message.style.opacity = '0';
      message.style.transform = 'translateY(-10px)';
      
      // Fade in animation
      setTimeout(() => {
          message.style.transition = 'all 0.3s ease';
          message.style.opacity = '1';
          message.style.transform = 'translateY(0)';
      }, 10);
      
      // Hides the message after 3s
      setTimeout(() => {
          message.style.opacity = '0';
          message.style.transform = 'translateY(-10px)';
          setTimeout(() => {
              message.style.display = 'none';
          }, 300);
      }, 3000);
  }
});