document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (!form) return;

  // Helpers to find inputs by id OR by name
  const $ = (id, selectorFallback) =>
    document.getElementById(id) || form.querySelector(selectorFallback);

  const nameInput = $("name", 'input[name="name"]');
  const emailInput = $("email", 'input[name="email"]');
  const passwordInput = $("password", 'input[name="password"]');
  const confirmInput = $("confirm_password", 'input[name="confirm_password"]');
  const agreeTerms = $("agree_terms", 'input[name="agree_terms"]');

  const togglePasswordBtn = $("togglePassword", "#togglePassword");
  const toggleConfirmBtn = $("toggleConfirmPassword", "#toggleConfirmPassword");

  const strengthBar = $("strengthBar", "#strengthBar");
  const strengthText = $("strengthText", "#strengthText");

  const matchDiv = $("passwordMatch", "#passwordMatch");
  const mismatchDiv = $("passwordMismatch", "#passwordMismatch");

  // If required inputs missing, stop (avoid runtime errors)
  if (!nameInput || !emailInput || !passwordInput || !confirmInput) return;

  /* -----------------------------
      Error message helper
  ------------------------------ */
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

  /* -----------------------------
      Toggle password visibility
  ------------------------------ */
  function toggleVisibility(input, btn) {
    if (!input || !btn) return;

    const icon = btn.querySelector("i");
    const isHidden = input.type === "password";

    input.type = isHidden ? "text" : "password";

    // If you use fa-eye initially, this will switch correctly
    if (icon) {
      icon.classList.toggle("fa-eye", !isHidden);
      icon.classList.toggle("fa-eye-slash", isHidden);
    }

    btn.setAttribute("aria-pressed", String(isHidden));
  }

  togglePasswordBtn?.addEventListener("click", () =>
    toggleVisibility(passwordInput, togglePasswordBtn),
  );

  toggleConfirmBtn?.addEventListener("click", () =>
    toggleVisibility(confirmInput, toggleConfirmBtn),
  );

  /* -----------------------------
      Password strength checker
  ------------------------------ */
  const strengthStyles = {
    weak: {
      bar: "bg-rose-500",
      text: "text-rose-600",
      label: "Weak",
    },
    fair: {
      bar: "bg-amber-500",
      text: "text-amber-600",
      label: "Fair",
    },
    good: {
      bar: "bg-sky-500",
      text: "text-sky-600",
      label: "Good",
    },
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
    if (
      !strengthBar ||
      !strengthText ||
      typeof strengthBar.style === "undefined"
    )
      return;

    let key = "weak";
    if (score >= 90) key = "strong";
    else if (score >= 70) key = "good";
    else if (score >= 40) key = "fair";

    const s = strengthStyles[key];

    strengthBar.style.width = `${score}%`;

    // keep rounded + height and only swap color class
    strengthBar.className = `h-2 rounded-full transition-all ${s.bar}`;
    strengthText.textContent = s.label;
    strengthText.className = `text-xs font-medium ${s.text}`;
  }

  /* -----------------------------
      Password match checker
  ------------------------------ */
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

  // Bind events (only if optional UI exists)
  passwordInput.addEventListener("input", () => {
    const score = getStrengthScore(passwordInput.value);
    if (
      strengthBar &&
      strengthText &&
      typeof strengthBar.style !== "undefined"
    ) {
      updateStrengthUI(score);
    }
    checkPasswordMatch();
  });

  confirmInput.addEventListener("input", checkPasswordMatch);

  /* -----------------------------
      Form validation
  ------------------------------ */
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
