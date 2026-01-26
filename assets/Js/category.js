/* =====================================================
   CONFIG
===================================================== */
const RELOAD_DELAY = 700;
const DO_RELOAD = true;

/* =====================================================
   SWEETALERT HELPERS
===================================================== */
const SwalHelper = {
  loading(title = "Processing...") {
    Swal.fire({
      title,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading(),
    });
  },

  success(title, text = "") {
    return Swal.fire({
      icon: "success",
      title,
      text: text || undefined,
      showConfirmButton: false,
      timer: 1200,
      timerProgressBar: true,
    });
  },

  error(text) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text,
      showConfirmButton: false,
      timer: 2200,
      timerProgressBar: true,
    });
  },

  confirmDelete(title, text) {
    return Swal.fire({
      icon: "warning",
      title,
      html: `<p class="text-gray-600 mt-2">${text}</p>`,
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#6b7280",
    });
  },

  warn(title, html, ms = 2600) {
    return Swal.fire({
      icon: "warning",
      title,
      html,
      showConfirmButton: false,
      timer: ms,
      timerProgressBar: true,
    });
  },
};

/* =====================================================
   EDIT CATEGORY (SweetAlert)
===================================================== */
async function editCategory(id, currentName) {
  const { value: newName } = await Swal.fire({
    title: "Edit Category",
    input: "text",
    inputLabel: "Category name",
    inputValue: currentName,
    inputPlaceholder: "Enter category name",
    showCancelButton: true,
    confirmButtonText: "Update",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#4f46e5", // indigo
    cancelButtonColor: "#6b7280",
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return "Category name is required";
      }
    },
  });

  if (!newName) return;

  SwalHelper.loading("Updating category...");

  try {
    const res = await CategoryAPI.request({
      action: "update_category",
      category_id: id,
      category_name: newName.trim(),
    });

    Swal.close();

    if (!res.success) {
      return SwalHelper.error(res.message || "Update failed");
    }

    SwalHelper.success("Category updated", res.message || "");

    const row = document.getElementById(`category-row-${id}`);
    if (row) {
      const nameCell = row.querySelector("td:first-child .text-sm");
      if (nameCell) nameCell.textContent = newName.trim();
    }
  } catch (e) {
    Swal.close();
    SwalHelper.error("Network error. Please try again.");
  }
}

/* =====================================================
   API HELPER
===================================================== */
const CategoryAPI = {
  async request(payload) {
    const fd = new FormData();
    Object.entries(payload).forEach(([k, v]) => fd.append(k, v));

    const res = await fetch("", {
      method: "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: fd,
    });

    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      throw new Error("Invalid server response");
    }
  },
};

/* =====================================================
   UI: Add Category Panel Toggle
===================================================== */
const AddPanel = {
  els: {
    openBtn: null,
    closeBtn: null,
    cancelBtn: null,
    panel: null,
    input: null,
  },

  init() {
    this.els.openBtn = document.getElementById("toggleAddCategory");
    this.els.closeBtn = document.getElementById("closeAddCategory");
    this.els.cancelBtn = document.getElementById("cancelAddCategory");
    this.els.panel = document.getElementById("addCategoryPanel");
    this.els.input = document.getElementById("category_name");

    if (!this.els.openBtn || !this.els.panel) return;

    const open = () => this.open();
    const close = () => this.close();

    this.els.openBtn.addEventListener("click", open);
    this.els.closeBtn?.addEventListener("click", close);
    this.els.cancelBtn?.addEventListener("click", close);
  },

  open() {
    if (!this.els.panel) return;
    this.els.panel.classList.remove("hidden");
    setTimeout(() => this.els.input?.focus(), 60);
  },

  close() {
    if (!this.els.panel) return;
    this.els.panel.classList.add("hidden");
    if (this.els.input) this.els.input.value = "";
  },
};

/* =====================================================
   MODAL (Edit)
===================================================== */
const Modal = {
  openEdit(id, name) {
    const idEl = document.getElementById("edit_category_id");
    const nameEl = document.getElementById("edit_category_name");
    const modal = document.getElementById("editModal");

    if (!idEl || !nameEl || !modal) return;

    idEl.value = id;
    nameEl.value = name;
    modal.classList.remove("hidden");
    setTimeout(() => nameEl.focus(), 100);
  },

  closeEdit() {
    document.getElementById("editModal")?.classList.add("hidden");
  },
};

/* =====================================================
   DOM HELPERS (optional real-time updates)
===================================================== */
function escapeHtml(str = "") {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function updateTotalBadge(delta) {
  const badge = document.querySelector(
    "h2 span.bg-indigo-100, h2 span.bg-blue-100",
  );
  if (!badge) return;
  const n = parseInt(badge.textContent || "0", 10) || 0;
  badge.textContent = String(Math.max(0, n + delta));
}

function appendCategoryRow({ id, name }) {
  const tbody = document.getElementById("categoriesTableBody");
  if (!tbody) return;

  const safeName = escapeHtml(name);
  const today = new Date();
  const dateLabel = today.toLocaleDateString(undefined, {
    month: "short",
    day: "2-digit",
    year: "numeric",
  });

  const tr = document.createElement("tr");
  tr.className = "hover:bg-gray-50 transition";
  tr.id = `category-row-${id}`;
  tr.innerHTML = `
    <td class="px-6 py-4">
      <div class="text-sm font-medium text-gray-900">${safeName}</div>
    </td>
    <td class="px-6 py-4">
      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
        <i class="fas fa-box mr-1"></i> 0 products
      </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(dateLabel)}</td>
    <td class="px-6 py-4 whitespace-nowrap text-sm">
      <div class="flex items-center space-x-2">
        <button
          class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 text-sm"
          data-action="edit" data-id="${id}" data-name="${safeName}">
          <i class="fas fa-edit mr-2"></i> Edit
        </button>
        <button
          class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm"
          data-action="delete" data-id="${id}" data-name="${safeName}" data-count="0">
          <i class="fas fa-trash mr-2"></i> Delete
        </button>
      </div>
    </td>
  `;

  tbody.prepend(tr);
}

function updateRowName(id, name) {
  const row = document.getElementById(`category-row-${id}`);
  if (!row) return;
  const nameCell = row.querySelector("td:nth-child(1) .text-sm");
  if (nameCell) nameCell.textContent = name;

  // update data-name for delegated buttons
  row.querySelectorAll("[data-action]").forEach((btn) => {
    btn.dataset.name = name;
  });
}

/* =====================================================
   CATEGORY MANAGER
===================================================== */
const CategoryManager = {
  async add(data) {
    SwalHelper.loading("Adding category...");
    try {
      const res = await CategoryAPI.request(data);
      Swal.close();

      if (!res.success) return SwalHelper.error(res.message || "Add failed");

      SwalHelper.success("Category added", res.message || "");

      const newId = Number(res.category_id || 0);
      if (newId > 0) {
        appendCategoryRow({ id: newId, name: data.category_name });
        updateTotalBadge(1);
      }

      AddPanel.close();

      if (DO_RELOAD) setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch {
      Swal.close();
      SwalHelper.error("Network error. Please try again.");
    }
  },

  async update(data) {
    SwalHelper.loading("Updating category...");
    try {
      const res = await CategoryAPI.request(data);
      Swal.close();

      if (!res.success) return SwalHelper.error(res.message || "Update failed");

      SwalHelper.success("Category updated", res.message || "");
      updateRowName(Number(data.category_id), data.category_name);

      Modal.closeEdit();
      if (DO_RELOAD) setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch {
      Swal.close();
      SwalHelper.error("Network error. Please try again.");
    }
  },

  async delete(id, name, productCount) {
    if (productCount > 0) {
      return SwalHelper.warn(
        "Cannot delete category",
        `<p class="text-gray-600 mt-2">
          Category <b>${escapeHtml(name)}</b> has <b>${productCount}</b> product(s).<br>
          Please reassign or delete them first.
        </p>`,
      );
    }

    const res = await SwalHelper.confirmDelete(
      "Delete category?",
      `Delete <b>${escapeHtml(name)}</b>? This action cannot be undone.`,
    );

    if (!res.isConfirmed) return;

    SwalHelper.loading("Deleting category...");
    try {
      const out = await CategoryAPI.request({
        action: "delete_category",
        category_id: id,
      });

      Swal.close();
      if (!out.success) return SwalHelper.error(out.message || "Delete failed");

      SwalHelper.success("Category deleted", out.message || "");
      document.getElementById(`category-row-${id}`)?.remove();
      updateTotalBadge(-1);

      if (DO_RELOAD) setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch {
      Swal.close();
      SwalHelper.error("Network error. Please try again.");
    }
  },
};

/* =====================================================
   GLOBAL FUNCTIONS (backward compatible with inline onclick)
===================================================== */
function editCategory(id, name) {
  Modal.openEdit(id, name);
}
function deleteCategory(id, name, count) {
  CategoryManager.delete(id, name, count);
}
function closeEditModal() {
  Modal.closeEdit();
}

/* =====================================================
   FORMS
===================================================== */
document
  .getElementById("addCategoryForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));

    if (!data.category_name?.trim())
      return SwalHelper.error("Category name is required");
    await CategoryManager.add(data);

    e.target.reset();
  });

document
  .getElementById("editCategoryForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));

    if (!data.category_name?.trim())
      return SwalHelper.error("Category name is required");
    await CategoryManager.update(data);
  });

/* =====================================================
   SEARCH
===================================================== */
document.getElementById("searchCategory")?.addEventListener("input", (e) => {
  const term = e.target.value.toLowerCase();
  document.querySelectorAll("#categoriesTableBody tr").forEach((row) => {
    row.style.display = row.textContent.toLowerCase().includes(term)
      ? ""
      : "none";
  });
});

/* =====================================================
   MODAL CLOSE EVENTS
===================================================== */
document.getElementById("editModal")?.addEventListener("click", (e) => {
  if (e.target === e.currentTarget) Modal.closeEdit();
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    Modal.closeEdit();
    AddPanel.close();
  }
});

/* =====================================================
   DELEGATED BUTTONS (optional: for new row buttons)
===================================================== */
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-action]");
  if (!btn) return;

  const action = btn.dataset.action;
  const id = Number(btn.dataset.id || 0);
  const name = btn.dataset.name || "";
  const count = Number(btn.dataset.count || 0);

  if (action === "edit") Modal.openEdit(id, name);
  if (action === "delete") CategoryManager.delete(id, name, count);
});

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  AddPanel.init();
});
