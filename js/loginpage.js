/*
 * Sweet Chicken POS - Login page behavior
 * Extracted from loginpage.php
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.previousElementSibling;
            if (!input) return;

            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
            }

            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });
});