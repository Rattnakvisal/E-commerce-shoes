document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (!form) return;

  // Find inputs by id first, then fallback to name
  const emailInput =
    document.getElementById("email") ||
    form.querySelector('input[name="email"]');
  const passwordInput =
    document.getElementById("password") ||
    form.querySelector('input[name="password"]');

  const toggleBtn =
    document.getElementById("togglePassword") ||
    form.querySelector("#togglePassword");

  const submitBtn = form.querySelector('button[type="submit"]');

  // If essential fields are missing, stop (avoid errors)
  if (!emailInput || !passwordInput || !submitBtn) return;

  const originalBtnHTML = submitBtn.innerHTML;
  const originalBtnDisabled = submitBtn.disabled;

  /* -----------------------------
      Helpers: error UI
  ------------------------------ */
  function removeError() {
    const error = document.getElementById("loginError");
    if (error) error.remove();
  }

  function showError(message) {
    removeError();

    const errorDiv = document.createElement("div");
    errorDiv.id = "loginError";
    errorDiv.className =
      "mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm";
    errorDiv.innerHTML = `
      <div class="flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <div>
          <div class="font-semibold">Login Error</div>
          <div class="mt-0.5">${message}</div>
        </div>
      </div>
    `;

    form.prepend(errorDiv);

    // Auto-hide after 5 seconds
    window.setTimeout(removeError, 5000);
  }

  /* -----------------------------
      Loading state
  ------------------------------ */
  function setLoading(isLoading) {
    submitBtn.disabled = isLoading;

    if (isLoading) {
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
    } else {
      submitBtn.innerHTML = originalBtnHTML;
      submitBtn.disabled = originalBtnDisabled;
    }
  }

  /* -----------------------------
      Toggle password visibility
  ------------------------------ */
  if (toggleBtn) {
    // ensure we look up the password input inside the same form/container
    const localPassword =
      passwordInput ||
      toggleBtn.closest("form")?.querySelector('input[name="password"]');

    toggleBtn.addEventListener("click", (e) => {
      e.preventDefault();
      const icon = toggleBtn.querySelector("i");
      const input = localPassword || passwordInput;
      if (!input) return;

      const nowVisible = input.type === "password";
      input.type = nowVisible ? "text" : "password";

      if (icon) {
        icon.className = nowVisible
          ? "fa-solid fa-eye"
          : "fa-solid fa-eye-slash";
      }

      // accessibility: reflect current visible state
      toggleBtn.setAttribute("aria-pressed", String(nowVisible));
    });
  }

  /* -----------------------------
      Form validation
  ------------------------------ */
  form.addEventListener("submit", (e) => {
    removeError();

    const email = String(emailInput.value || "").trim();
    const password = String(passwordInput.value || "").trim();

    if (!email || !password) {
      e.preventDefault();
      showError("Please fill in all required fields.");
      return;
    }

    // Simple email validation
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
      e.preventDefault();
      showError("Please enter a valid email address.");
      return;
    }

    // Lock UI while submitting (server will respond normally)
    setLoading(true);

    // Optional safety: if the request hangs, re-enable after 10s
    window.setTimeout(() => setLoading(false), 10000);
  });

  /* -----------------------------
      Social login (demo only)
  ------------------------------ */
  document.querySelectorAll("[data-social]").forEach((btn) => {
    btn.addEventListener("click", () => {
      alert(`Social login with ${btn.dataset.social} coming soon.`);
    });
  });
});
