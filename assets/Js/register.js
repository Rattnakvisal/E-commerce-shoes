document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('form');

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const agreeTerms = document.getElementById('agree_terms');

    const togglePasswordBtn = document.getElementById('togglePassword');
    const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');

    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    const matchDiv = document.getElementById('passwordMatch');
    const mismatchDiv = document.getElementById('passwordMismatch');

    /* ---------------------------------
       Toggle password visibility
    ---------------------------------- */
    function toggleVisibility(input, btn) {
        const icon = btn.querySelector('i');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
    }

    togglePasswordBtn?.addEventListener('click', () =>
        toggleVisibility(passwordInput, togglePasswordBtn)
    );

    toggleConfirmBtn?.addEventListener('click', () =>
        toggleVisibility(confirmInput, toggleConfirmBtn)
    );

    /* ---------------------------------
       Password strength checker
    ---------------------------------- */
    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        let strength = 0;

        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 10;
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;

        strength = Math.min(strength, 100);
        strengthBar.style.width = `${strength}%`;

        updateStrengthUI(strength);
        checkPasswordMatch();
    });

    function updateStrengthUI(strength) {
        let color = 'red';
        let text = 'Weak';

        if (strength >= 90) {
            color = 'green';
            text = 'Strong';
        } else if (strength >= 70) {
            color = 'blue';
            text = 'Good';
        } else if (strength >= 40) {
            color = 'yellow';
            text = 'Fair';
        }

        strengthBar.className = `password-strength bg-${color}-500 rounded-full`;
        strengthText.textContent = text;
        strengthText.className = `text-xs font-medium text-${color}-500`;
    }

    /* ---------------------------------
       Password match checker
    ---------------------------------- */
    confirmInput.addEventListener('input', checkPasswordMatch);

    function checkPasswordMatch() {
        if (!confirmInput.value) {
            matchDiv.classList.add('hidden');
            mismatchDiv.classList.add('hidden');
            return;
        }

        if (passwordInput.value === confirmInput.value) {
            matchDiv.classList.remove('hidden');
            mismatchDiv.classList.add('hidden');
        } else {
            matchDiv.classList.add('hidden');
            mismatchDiv.classList.remove('hidden');
        }
    }

    /* ---------------------------------
       Error message helper
    ---------------------------------- */
    function showError(message) {
        const existing = document.getElementById('registerError');
        if (existing) existing.remove();

        const div = document.createElement('div');
        div.id = 'registerError';
        div.className = 'mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in';
        div.innerHTML = `
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Validation Error</h3>
                    <p class="mt-1 text-sm text-red-700">${message}</p>
                </div>
            </div>
        `;

        form.prepend(div);
        setTimeout(() => div.remove(), 5000);
    }

    /* ---------------------------------
       Form validation
    ---------------------------------- */
    form.addEventListener('submit', (e) => {

        if (
            !nameInput.value.trim() ||
            !emailInput.value.trim() ||
            !passwordInput.value ||
            !confirmInput.value ||
            !agreeTerms.checked
        ) {
            e.preventDefault();
            showError('Please fill in all required fields and agree to the terms.');
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value)) {
            e.preventDefault();
            showError('Please enter a valid email address.');
            return;
        }

        if (passwordInput.value.length < 8) {
            e.preventDefault();
            showError('Password must be at least 8 characters long.');
            return;
        }

        if (passwordInput.value !== confirmInput.value) {
            e.preventDefault();
            showError('Passwords do not match.');
        }
    });
});
