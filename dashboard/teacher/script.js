document.getElementById('profileIcon').addEventListener('click', function () {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
});

// Close dropdown when clicking outside
window.addEventListener('click', function (e) {
    const dropdown = document.getElementById('profileDropdown');
    if (!e.target.matches('#profileIcon') && !dropdown.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});
const modeToggle = document.getElementById('modeToggle');
const body = document.body;

modeToggle.addEventListener('click', function () {
    body.classList.toggle('dark-mode');
});
