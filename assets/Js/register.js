document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (!form) return;

  // Helper: get element by id OR fallback selector inside form
  const getEl = (id, fallbackSelector) =>
    document.getElementById(id) || form.querySelector(fallbackSelector);

  const nameInput = getEl("name", 'input[name="name"]');
  const emailInput = getEl("email", 'input[name="email"]');
  const passwordInput = getEl("password", 'input[name="password"]');
  const confirmInput = getEl(
    "confirm_password",
    'input[name="confirm_password"]',
  );
  const agreeTerms = getEl("agree_terms", 'input[name="agree_terms"]');

  const strengthBar = getEl("strengthBar", "#strengthBar");
  const strengthText = getEl("strengthText", "#strengthText");
  const matchDiv = getEl("passwordMatch", "#passwordMatch");
  const mismatchDiv = getEl("passwordMismatch", "#passwordMismatch");

  if (!nameInput || !emailInput || !passwordInput || !confirmInput) return;

  /* ----------------------------------------
     Error helper
  ----------------------------------------- */
  function removeError() {
    const existing = document.getElementById("registerError");
    if (existing) existing.remove();
  }

  function showError(message) {
    removeError();

    const div = document.createElement("div");
    div.id = "registerError";
    div.className =
      "mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm";
    div.innerHTML = `
      <div class="flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <div>
          <div class="font-semibold">Validation Error</div>
          <div class="mt-0.5">${message}</div>
        </div>
      </div>
    `;

    form.prepend(div);
    window.setTimeout(removeError, 5000);
  }

  /* ----------------------------------------
     Create "view password" buttons if missing
  ----------------------------------------- */
  function ensurePasswordWrapper(input) {
    // If already wrapped or input has parent with relative, keep it
    const parent = input.parentElement;
    const hasRelativeParent =
      parent && parent.classList && parent.classList.contains("relative");

    if (hasRelativeParent) return parent;

    // Wrap input inside relative div
    const wrap = document.createElement("div");
    wrap.className = "relative";

    // Insert wrapper before input, then move input into wrapper
    parent.insertBefore(wrap, input);
    wrap.appendChild(input);

    // Add right padding for icon button
    input.classList.add("pr-12");

    return wrap;
  }

  function createToggleButton() {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
      "absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-900";
    btn.setAttribute("aria-label", "Toggle password visibility");
    btn.setAttribute("aria-pressed", "false");
    btn.innerHTML = `<i class="fa-regular fa-eye"></i>`;
    return btn;
  }

  function setIcon(btn, isVisible) {
    const icon = btn.querySelector("i");
    if (!icon) return;
    // visible => eye-slash, hidden => eye
    icon.className = isVisible
      ? "fa-regular fa-eye-slash"
      : "fa-regular fa-eye";
    btn.setAttribute("aria-pressed", String(isVisible));
  }

  function attachVisibilityToggle(input, existingBtn) {
    const wrapper = ensurePasswordWrapper(input);

    let btn = existingBtn;
    if (!btn) {
      btn = createToggleButton();
      wrapper.appendChild(btn);
    }

    // default icon based on input type
    setIcon(btn, input.type !== "password");

    btn.addEventListener("click", () => {
      const nowVisible = input.type === "password";
      input.type = nowVisible ? "text" : "password";
      setIcon(btn, nowVisible);
      input.focus();
    });
  }

  // If you have buttons in HTML with these IDs, they will be used; otherwise created automatically.
  const togglePasswordBtn = getEl("togglePassword", "#togglePassword");
  const toggleConfirmBtn = getEl(
    "toggleConfirmPassword",
    "#toggleConfirmPassword",
  );

  attachVisibilityToggle(passwordInput, togglePasswordBtn);
  attachVisibilityToggle(confirmInput, toggleConfirmBtn);

  /* ----------------------------------------
     Password strength
  ----------------------------------------- */
  const strengthStyles = {
    weak: { bar: "bg-rose-500", text: "text-rose-600", label: "Weak" },
    fair: { bar: "bg-amber-500", text: "text-amber-600", label: "Fair" },
    good: { bar: "bg-sky-500", text: "text-sky-600", label: "Good" },
    strong: {
      bar: "bg-emerald-500",
      text: "text-emerald-600",
      label: "Strong",
    },
  };

  function getStrengthScore(pw) {
    let score = 0;
    if (pw.length >= 8) score += 25;
    if (pw.length >= 12) score += 10;
    if (/[A-Z]/.test(pw)) score += 20;
    if (/[a-z]/.test(pw)) score += 15;
    if (/[0-9]/.test(pw)) score += 20;
    if (/[^A-Za-z0-9]/.test(pw)) score += 20;
    return Math.min(score, 100);
  }

  function updateStrengthUI(score) {
    if (!strengthBar || !strengthText) return;

    let key = "weak";
    if (score >= 90) key = "strong";
    else if (score >= 70) key = "good";
    else if (score >= 40) key = "fair";

    const s = strengthStyles[key];
    strengthBar.style.width = `${score}%`;
    strengthBar.className = `h-2 rounded-full transition-all ${s.bar}`;
    strengthText.textContent = s.label;
    strengthText.className = `text-xs font-medium ${s.text}`;
  }

  /* ----------------------------------------
     Password match checker
  ----------------------------------------- */
  function checkPasswordMatch() {
    if (!matchDiv || !mismatchDiv) return;

    const pw = passwordInput.value;
    const cf = confirmInput.value;

    if (!cf) {
      matchDiv.classList.add("hidden");
      mismatchDiv.classList.add("hidden");
      return;
    }

    if (pw === cf) {
      matchDiv.classList.remove("hidden");
      mismatchDiv.classList.add("hidden");
    } else {
      matchDiv.classList.add("hidden");
      mismatchDiv.classList.remove("hidden");
    }
  }

  passwordInput.addEventListener("input", () => {
    updateStrengthUI(getStrengthScore(passwordInput.value));
    checkPasswordMatch();
  });

  confirmInput.addEventListener("input", checkPasswordMatch);

  /* ----------------------------------------
     Form validation
  ----------------------------------------- */
  form.addEventListener("submit", (e) => {
    removeError();

    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const pw = passwordInput.value;
    const cf = confirmInput.value;

    if (!name || !email || !pw || !cf) {
      e.preventDefault();
      showError("Please fill in all required fields.");
      return;
    }

    if (agreeTerms && !agreeTerms.checked) {
      e.preventDefault();
      showError("Please agree to the terms.");
      return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
      e.preventDefault();
      showError("Please enter a valid email address.");
      return;
    }

    if (pw.length < 8) {
      e.preventDefault();
      showError("Password must be at least 8 characters long.");
      return;
    }

    if (pw !== cf) {
      e.preventDefault();
      showError("Passwords do not match.");
      return;
    }
  });
});
