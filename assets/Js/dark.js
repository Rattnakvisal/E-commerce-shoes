/* ============================================================================
   Enhanced Global Dark Mode Toggle System
   Features:
   - Stores preference in `adminTheme`
   - Supports: 'dark', 'light', 'system'
   - Applies primary color --primary-color = #0C2C55 when dark
   - Responsive system preference detection
   - Theme change events for other components
   - Persist theme per user/session
   - Support for multiple toggle buttons
   - Accessibility improvements
   - Animation support
============================================================================ */

(function () {
  const STORAGE_KEY = "adminTheme";
  const DARK_PRIMARY = "#0C2C55";
  const LIGHT_PRIMARY =
    getComputedStyle(document.documentElement).getPropertyValue(
      "--primary-color",
    ) || "#3b82f6";

  // Theme configuration
  const THEMES = {
    light: {
      name: "light",
      icon: "moon",
      iconClass: "fas fa-moon",
      label: "Light Mode",
    },
    dark: {
      name: "dark",
      icon: "sun",
      iconClass: "fas fa-sun text-yellow-400",
      label: "Dark Mode",
    },
    system: {
      name: "system",
      icon: "desktop",
      iconClass: "fas fa-desktop",
      label: "System Default",
    },
  };

  // State management
  let currentTheme = null;
  let systemPrefersDark = false;
  let observers = [];

  // Initialize system preference detection
  function initSystemPreference() {
    const mq = window.matchMedia("(prefers-color-scheme: dark)");
    systemPrefersDark = mq.matches;

    mq.addEventListener("change", (e) => {
      systemPrefersDark = e.matches;
      if (currentTheme === "system") {
        applyTheme("system", true);
        notifyObservers();
      }
    });
  }

  // Get saved theme from storage
  function getSavedTheme() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      return saved && THEMES[saved] ? saved : "system";
    } catch (e) {
      console.warn("Could not access localStorage, using system default");
      return "system";
    }
  }

  // Save theme to storage
  function setSavedTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {
      console.warn("Could not save theme to localStorage");
    }
  }

  // Apply CSS variables for theme
  function applyThemeVariables(isDark) {
    const doc = document.documentElement;

    if (isDark) {
      // Dark mode variables
      doc.style.setProperty("--primary-color", DARK_PRIMARY);
      doc.style.setProperty(
        "--primary-color-dark",
        shadeColor(DARK_PRIMARY, -20),
      );
      doc.style.setProperty(
        "--primary-color-light",
        shadeColor(DARK_PRIMARY, 20),
      );

      // Extended color palette for dark mode
      doc.style.setProperty("--bg-primary", "#0b1e2b");
      doc.style.setProperty("--bg-secondary", "#10273a");
      doc.style.setProperty("--text-primary", "#e6f0fb");
      doc.style.setProperty("--text-secondary", "#9fb0c4");
      doc.style.setProperty("--border-color", "rgba(255, 255, 255, 0.06)");

      // Theme color for mobile browsers
      const meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.setAttribute("content", DARK_PRIMARY);

      // Add data attribute for CSS targeting
      doc.setAttribute("data-theme-mode", "dark");
    } else {
      // Light mode variables
      if (LIGHT_PRIMARY) {
        doc.style.setProperty("--primary-color", LIGHT_PRIMARY);
        doc.style.setProperty(
          "--primary-color-dark",
          shadeColor(LIGHT_PRIMARY, -20),
        );
        doc.style.setProperty(
          "--primary-color-light",
          shadeColor(LIGHT_PRIMARY, 20),
        );
      }

      // Reset extended palette
      doc.style.removeProperty("--bg-primary");
      doc.style.removeProperty("--bg-secondary");
      doc.style.removeProperty("--text-primary");
      doc.style.removeProperty("--text-secondary");
      doc.style.removeProperty("--border-color");

      // Reset theme color
      const meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.setAttribute("content", LIGHT_PRIMARY || "");

      // Update data attribute
      doc.setAttribute("data-theme-mode", "light");
    }
  }

  // Helper function to shade colors
  function shadeColor(color, percent) {
    let R = parseInt(color.substring(1, 3), 16);
    let G = parseInt(color.substring(3, 5), 16);
    let B = parseInt(color.substring(5, 7), 16);

    R = parseInt((R * (100 + percent)) / 100);
    G = parseInt((G * (100 + percent)) / 100);
    B = parseInt((B * (100 + percent)) / 100);

    R = R < 255 ? R : 255;
    G = G < 255 ? G : 255;
    B = B < 255 ? B : 255;

    const RR =
      R.toString(16).length === 1 ? "0" + R.toString(16) : R.toString(16);
    const GG =
      G.toString(16).length === 1 ? "0" + G.toString(16) : G.toString(16);
    const BB =
      B.toString(16).length === 1 ? "0" + B.toString(16) : B.toString(16);

    return "#" + RR + GG + BB;
  }

  // Apply theme to document
  function applyTheme(theme, isSystemChange = false) {
    const html = document.documentElement;
    let isDark = false;

    // Determine if dark mode should be active
    if (theme === "system") {
      isDark = systemPrefersDark;
    } else if (theme === "dark") {
      isDark = true;
    }

    // Apply dark class with transition prevention
    html.classList.add("theme-transitioning");

    // Request animation frame for smooth transition
    requestAnimationFrame(() => {
      // Toggle dark class
      if (isDark) {
        html.classList.add("dark");
      } else {
        html.classList.remove("dark");
      }

      // Apply CSS variables
      applyThemeVariables(isDark);

      // Update data attributes
      html.setAttribute("data-theme", theme);
      currentTheme = theme;

      // Update UI
      updateToggleButtons();
      updateThemeSelector();

      // Remove transition class after animation
      setTimeout(() => {
        html.classList.remove("theme-transitioning");
      }, 300);

      // Notify observers if not a system change
      if (!isSystemChange) {
        notifyObservers();
      }
    });
  }

  // Update all toggle buttons on page
  function updateToggleButtons() {
    const isDark = document.documentElement.classList.contains("dark");
    const buttons = document.querySelectorAll("[data-theme-toggle]");

    buttons.forEach((button) => {
      const type = button.getAttribute("data-theme-toggle") || "icon";

      if (type === "icon") {
        button.innerHTML = isDark
          ? '<i class="fas fa-sun text-yellow-400"></i>'
          : '<i class="fas fa-moon"></i>';
      } else if (type === "label") {
        button.textContent = isDark ? "Light Mode" : "Dark Mode";
      }

      // Update aria-label for accessibility
      button.setAttribute(
        "aria-label",
        isDark ? "Switch to light mode" : "Switch to dark mode",
      );
    });
  }

  // Update theme selector dropdown if present
  function updateThemeSelector() {
    const selector = document.getElementById("themeSelector");
    if (!selector) return;

    // Update selected option
    const options = selector.querySelectorAll("option");
    options.forEach((option) => {
      option.selected = option.value === currentTheme;
    });

    // Update dropdown label
    const dropdownBtn = selector
      .closest(".dropdown")
      ?.querySelector(".dropdown-toggle");
    if (dropdownBtn) {
      const theme = THEMES[currentTheme];
      dropdownBtn.innerHTML = `<i class="${theme.iconClass} mr-2"></i>${theme.label}`;
    }
  }

  // Toggle between light/dark (skipping system)
  function toggleTheme() {
    const current = getSavedTheme();
    let nextTheme;

    if (current === "dark") {
      nextTheme = "light";
    } else if (current === "light") {
      nextTheme = "dark";
    } else {
      // If system is selected, toggle based on current appearance
      const isDark = document.documentElement.classList.contains("dark");
      nextTheme = isDark ? "light" : "dark";
    }

    setTheme(nextTheme);
  }

  // Set specific theme
  function setTheme(theme) {
    if (!THEMES[theme]) {
      console.error(`Invalid theme: ${theme}`);
      return;
    }

    setSavedTheme(theme);
    applyTheme(theme);

    // Dispatch custom event for other scripts
    document.dispatchEvent(
      new CustomEvent("themeChange", {
        detail: {
          theme,
          isDark: document.documentElement.classList.contains("dark"),
        },
      }),
    );
  }

  // Get current theme
  function getCurrentTheme() {
    return currentTheme;
  }

  // Get current theme mode (light/dark)
  function getCurrentMode() {
    return document.documentElement.classList.contains("dark")
      ? "dark"
      : "light";
  }

  // Add observer for theme changes
  function addObserver(callback) {
    if (typeof callback === "function") {
      observers.push(callback);
    }
  }

  // Remove observer
  function removeObserver(callback) {
    observers = observers.filter((obs) => obs !== callback);
  }

  // Notify all observers
  function notifyObservers() {
    const mode = getCurrentMode();
    observers.forEach((callback) => {
      try {
        callback({ theme: currentTheme, mode });
      } catch (e) {
        console.warn("Theme observer error:", e);
      }
    });
  }

  // Initialize event listeners
  function initEventListeners() {
    // Toggle buttons
    document.addEventListener("click", (e) => {
      const toggleBtn = e.target.closest("[data-theme-toggle]");
      if (toggleBtn) {
        e.preventDefault();
        toggleTheme();
      }

      // Theme selector options
      const themeOption = e.target.closest("[data-theme-option]");
      if (themeOption) {
        e.preventDefault();
        const theme = themeOption.getAttribute("data-theme-option");
        if (theme) setTheme(theme);
      }
    });

    // Theme selector dropdown
    const themeSelector = document.getElementById("themeSelector");
    if (themeSelector) {
      themeSelector.addEventListener("change", (e) => {
        setTheme(e.target.value);
      });
    }
  }

  // Create theme selector UI if not present
  function createThemeSelector() {
    // Check if selector already exists
    if (document.getElementById("themeSelector")) return;

    // Create dropdown in header if there's a user menu
    const userMenu = document.querySelector(".user-menu, .header-actions");
    if (userMenu) {
      const selectorHtml = `
        <div class="dropdown theme-selector">
          <button class="dropdown-toggle btn btn-sm btn-outline flex items-center" 
                  type="button" data-toggle="dropdown">
            <i class="${THEMES[currentTheme].iconClass} mr-2"></i>
            <span>${THEMES[currentTheme].label}</span>
          </button>
          <div class="dropdown-menu">
            <a href="#" data-theme-option="light" class="dropdown-item">
              <i class="fas fa-sun text-yellow-500 mr-2"></i>Light
            </a>
            <a href="#" data-theme-option="dark" class="dropdown-item">
              <i class="fas fa-moon text-blue-500 mr-2"></i>Dark
            </a>
            <a href="#" data-theme-option="system" class="dropdown-item">
              <i class="fas fa-desktop text-gray-500 mr-2"></i>System
            </a>
          </div>
        </div>
      `;
      userMenu.insertAdjacentHTML("beforeend", selectorHtml);
    }
  }

  // Initialize the theme system
  function init() {
    // Initialize system preference
    initSystemPreference();

    // Get and apply saved theme
    const savedTheme = getSavedTheme();
    applyTheme(savedTheme);

    // Initialize event listeners
    initEventListeners();

    // Create theme selector UI
    createThemeSelector();

    // Add transition styles
    addTransitionStyles();

    console.log(`Theme initialized: ${savedTheme} (${getCurrentMode()})`);
  }

  // Add smooth transition styles
  function addTransitionStyles() {
    if (document.getElementById("theme-transition-styles")) return;

    const style = document.createElement("style");
    style.id = "theme-transition-styles";
    style.textContent = `
      html.theme-transitioning,
      html.theme-transitioning *,
      html.theme-transitioning *::before,
      html.theme-transitioning *::after {
        transition: background-color 0.3s ease, 
                    border-color 0.3s ease, 
                    color 0.3s ease,
                    fill 0.3s ease,
                    stroke 0.3s ease !important;
        transition-delay: 0s !important;
      }
      
      .theme-selector .dropdown-menu {
        min-width: 160px;
      }
    `;
    document.head.appendChild(style);
  }

  // Expose public API
  window.siteTheme = {
    init,
    toggleTheme,
    setTheme,
    getCurrentTheme,
    getCurrentMode,
    getSavedTheme,
    addObserver,
    removeObserver,
    THEMES,
  };

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
