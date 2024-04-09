const darkModeToggle = document.getElementById('dark-mode-toggle');
const body = document.body;
const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
const prefersLightMode = window.matchMedia('(prefers-color-scheme: light)').matches;
// Set initial state based on user preference
if (prefersDarkMode) {
body.classList.add('dark-mode');
darkModeToggle.checked = true;
} else if (prefersLightMode) {
body.classList.remove('dark-mode');
darkModeToggle.checked = false;
}
// Add event listener for toggling dark mode
darkModeToggle.addEventListener('change', () => {
body.classList.toggle('dark-mode');
});
