document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (!form) return;

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

  if (!emailInput || !passwordInput || !submitBtn) return;

  const originalBtnHTML = submitBtn.innerHTML;

  /* =========================
     Error UI
  ========================= */
  const ERROR_ID = "loginError";

  function removeError() {
    document.getElementById(ERROR_ID)?.remove();
  }

  function showError(message) {
    removeError();

    const div = document.createElement("div");
    div.id = ERROR_ID;
    div.className =
      "mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm";
    div.innerHTML = `
      <div class="flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <div>
          <div class="font-semibold">Login Error</div>
          <div class="mt-0.5">${message}</div>
        </div>
      </div>
    `;
    form.prepend(div);

    window.setTimeout(removeError, 5000);
  }

  /* =========================
     Loading state
  ========================= */
  function setLoading(isLoading) {
    submitBtn.disabled = isLoading;

    if (isLoading) {
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
    } else {
      submitBtn.innerHTML = originalBtnHTML;
    }
  }

  /* =========================
     Toggle password visibility
  ========================= */
  function setEyeIcon(isVisible) {
    const icon = toggleBtn?.querySelector("i");
    if (!icon) return;
    // visible => eye-slash, hidden => eye
    icon.className = isVisible ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
    toggleBtn?.setAttribute("aria-pressed", String(isVisible));
  }

  if (toggleBtn) {
    setEyeIcon(false);

    toggleBtn.addEventListener("click", (e) => {
      e.preventDefault();

      const willShow = passwordInput.type === "password";
      passwordInput.type = willShow ? "text" : "password";

      setEyeIcon(willShow);
      passwordInput.focus();
    });
  }

  /* =========================
     Form validation
  ========================= */
  form.addEventListener("submit", (e) => {
    removeError();

    const email = String(emailInput.value || "").trim();
    const password = String(passwordInput.value || "");

    if (!email || !password) {
      e.preventDefault();
      showError("Please fill in all required fields.");
      return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
      e.preventDefault();
      showError("Please enter a valid email address.");
      return;
    }

    setLoading(true);

    // fallback unlock (if server is slow / network issue)
    window.setTimeout(() => setLoading(false), 10000);
  });
});
