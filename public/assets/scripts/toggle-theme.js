const checkbox = document.getElementById('theme-toggle');
const themeLink = document.getElementById("theme-style");

function getPathToRoot() {
  return window.location.pathname.split('/').slice(1, -1).map(() => '..').join('/') || '.';
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return null;
}

function setCookie(name, value, days = 365) {
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
}

const savedTheme = getCookie("theme") || "light";

const pathToRoot = getPathToRoot();
if (savedTheme === "dark") {
  checkbox.checked = true;
  themeLink.href = `${pathToRoot}/assets/styles/dark-mode.css`;
} else {
  checkbox.checked = false;
  themeLink.href = `${pathToRoot}/assets/styles/light-mode.css`;
}

function updateTheme() {
  const pathToRoot = getPathToRoot();
  const theme = checkbox.checked ? "dark" : "light";

  document.documentElement.setAttribute('data-theme', theme);
  themeLink.href = `${pathToRoot}/assets/styles/${theme}-mode.css`;
  setCookie("theme", theme);
}

checkbox.addEventListener("change", updateTheme);