// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Toggle confirm password visibility
document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
    const confirmPasswordInput = document.getElementById('confirm_password');
    const icon = this.querySelector('i');

    if (confirmPasswordInput.type === 'password') {
        confirmPasswordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        confirmPasswordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Password strength checker
document.getElementById('password').addEventListener('input', function () {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    // Calculate password strength
    let strength = 0;

    // Length check
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 10;

    // Character variety checks
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[a-z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;

    // Update strength bar
    strength = Math.min(strength, 100);
    strengthBar.style.width = `${strength}%`;

    // Update strength text and color
    if (strength < 40) {
        strengthBar.className = 'password-strength bg-red-500 rounded-full';
        strengthText.textContent = 'Weak';
        strengthText.className = 'text-xs font-medium text-red-500';
    } else if (strength < 70) {
        strengthBar.className = 'password-strength bg-yellow-500 rounded-full';
        strengthText.textContent = 'Fair';
        strengthText.className = 'text-xs font-medium text-yellow-500';
    } else if (strength < 90) {
        strengthBar.className = 'password-strength bg-blue-500 rounded-full';
        strengthText.textContent = 'Good';
        strengthText.className = 'text-xs font-medium text-blue-500';
    } else {
        strengthBar.className = 'password-strength bg-green-500 rounded-full';
        strengthText.textContent = 'Strong';
        strengthText.className = 'text-xs font-medium text-green-500';
    }

    // Check password match
    checkPasswordMatch();
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    const mismatchDiv = document.getElementById('passwordMismatch');

    if (confirmPassword.length === 0) {
        matchDiv.classList.add('hidden');
        mismatchDiv.classList.add('hidden');
        return;
    }

    if (password === confirmPassword) {
        matchDiv.classList.remove('hidden');
        mismatchDiv.classList.add('hidden');
    } else {
        matchDiv.classList.add('hidden');
        mismatchDiv.classList.remove('hidden');
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function (e) {
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const agreeTerms = document.getElementById('agree_terms').checked;

    // Basic validation
    if (!name || !email || !password || !confirmPassword || !agreeTerms) {
        e.preventDefault();
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in';
        errorDiv.innerHTML = `
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Validation Error</h3>
                            <div class="mt-1 text-sm text-red-700">
                                Please fill in all required fields and agree to the terms.
                            </div>
                        </div>
                    </div>
                `;

        const form = this;
        const firstChild = form.firstElementChild;
        form.insertBefore(errorDiv, firstChild);

        setTimeout(() => {
            errorDiv.remove();
        }, 5000);

        return false;
    }

    // Email validation
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return false;
    }

    // Password length validation
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return false;
    }

    // Password match validation
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }

    return true;
});
