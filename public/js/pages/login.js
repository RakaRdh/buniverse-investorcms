/**
 * Login Page Interactive Script
 */

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('passwordEyeIcon');
    if (!passwordInput || !eyeIcon) return;

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.setAttribute('data-lucide', 'eye-off');
    } else {
        passwordInput.type = 'password';
        eyeIcon.setAttribute('data-lucide', 'eye');
    }

    if (window.lucide) window.lucide.createIcons();
}

function fillDemo(username, password) {
    const usernameInput = document.getElementById('loginUsernameInput') || document.getElementById('loginEmailInput');
    const passwordInput = document.getElementById('passwordInput');
    if (usernameInput && passwordInput) {
        usernameInput.value = username;
        passwordInput.value = password;
        usernameInput.focus();
    }
}
