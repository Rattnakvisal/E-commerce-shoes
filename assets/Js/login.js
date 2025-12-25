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
// Form validation
document.querySelector('form').addEventListener('submit', function (e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    if (!email || !password) {
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
                                Please fill in all required fields.
                            </div>
                        </div>
                    </div>
                `;

        // Insert error message
        const form = this;
        const firstChild = form.firstElementChild;
        form.insertBefore(errorDiv, firstChild);

        // Remove error after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);

        return false;
    }

    // Basic email validation
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return false;
    }

    return true;
});

// Social login buttons (demo)
document.querySelectorAll('button[type="button"]').forEach(button => {
    if (button.textContent.includes('Google') || button.textContent.includes('Microsoft')) {
        button.addEventListener('click', function () {
            const provider = this.textContent.trim();
            alert(`Social login with ${provider} would be implemented here.`);
        });
    }
});

// Add loading state to submit button
document.querySelector('form').addEventListener('submit', function () {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
    submitBtn.disabled = true;

    // Reset button after 3 seconds (in case of error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});