(function () {
  // Block rendering until the theme is set
  document.documentElement.style.display = 'none';

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  const savedTheme = getCookie('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);

  // Add the correct stylesheet
  const themeLink = document.getElementById('theme-style');
  if (themeLink) {
    themeLink.href = `assets/styles/${savedTheme}-mode.css`;
  }

  // Show the page once everything is ready
  window.addEventListener('DOMContentLoaded', () => {
    document.documentElement.style.display = '';
  });
})();