// Export dropdown functionality
document.addEventListener("DOMContentLoaded", function () {
  const exportDropdownBtn = document.getElementById("exportDropdownBtn");
  const exportDropdown = document.getElementById("exportDropdown");

  if (exportDropdownBtn && exportDropdown) {
    exportDropdownBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      exportDropdown.classList.toggle("hidden");
      exportDropdown.classList.toggle("animate-fade-in-down");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
      if (
        !exportDropdown.contains(e.target) &&
        !exportDropdownBtn.contains(e.target)
      ) {
        exportDropdown.classList.add("hidden");
      }
    });
  }

  // Export item click handlers
  const exportItems = document.querySelectorAll(".export-item");
  exportItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      const type = this.getAttribute("data-type");
      const format = this.getAttribute("data-format");

      // Add downloading animation
      this.classList.add("downloading");

      // Remove animation after delay
      setTimeout(() => {
        this.classList.remove("downloading");
      }, 1500);

      // Track export event (you can add analytics here)
      console.log(`Exporting ${type} as ${format}`);
    });
  });

  // Quick export buttons
  const quickExportBtns = document.querySelectorAll(".export-quick-btn");
  quickExportBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      const type = this.getAttribute("data-type");
      const format = this.getAttribute("data-format");

      // Add pulse animation
      this.classList.add("animate-pulse");
      setTimeout(() => {
        this.classList.remove("animate-pulse");
      }, 1000);
    });
  });

  // Progress bar animations
  const progressBars = document.querySelectorAll(".report-progress");
  progressBars.forEach((bar) => {
    const width = bar.style.getPropertyValue("--target-width") || "0%";
    bar.style.width = width;
  });
});

async function initCharts() {
  await initOrdersChart();
}
