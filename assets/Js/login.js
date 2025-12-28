document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const submitBtn = form.querySelector('button[type="submit"]');

    /* -----------------------------
       Toggle password visibility
    ------------------------------ */
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const icon = toggleBtn.querySelector('i');
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isHidden);
            icon.classList.toggle('fa-eye-slash', isHidden);
        });
    }

    /* -----------------------------
       Show error message
    ------------------------------ */
    function showError(message) {
        removeError();

        const errorDiv = document.createElement('div');
        errorDiv.id = 'loginError';
        errorDiv.className = 'mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in';
        errorDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Login Error</h3>
                    <p class="mt-1 text-sm text-red-700">${message}</p>
                </div>
            </div>
        `;

        form.prepend(errorDiv);

        setTimeout(removeError, 5000);
    }

    function removeError() {
        const error = document.getElementById('loginError');
        if (error) error.remove();
    }

    /* -----------------------------
       Form validation
    ------------------------------ */
    form.addEventListener('submit', (e) => {

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        if (!email || !password) {
            e.preventDefault();
            showError('Please fill in all required fields.');
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            e.preventDefault();
            showError('Please enter a valid email address.');
            return;
        }

        /* -----------------------------
           Loading state
        ------------------------------ */
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';

        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
        }, 3000);
    });

    /* -----------------------------
       Social login (demo only)
    ------------------------------ */
    document.querySelectorAll('[data-social]').forEach(btn => {
        btn.addEventListener('click', () => {
            alert(`Social login with ${btn.dataset.social} coming soon.`);
        });
    });

});
