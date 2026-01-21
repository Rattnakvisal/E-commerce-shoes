/* =====================================================
   CONFIG
===================================================== */
const RELOAD_DELAY = 700;

/* =====================================================
   SWEETALERT HELPERS (GLOBAL – NO OK ANYWHERE)
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
};

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

    try {
      return await res.json();
    } catch {
      throw new Error("Invalid server response");
    }
  },
};

/* =====================================================
   CATEGORY MANAGER
===================================================== */
const CategoryManager = {
  async add(data) {
    SwalHelper.loading("Adding category...");

    try {
      const res = await CategoryAPI.request(data);
      Swal.close();

      if (!res.success) return SwalHelper.error(res.message);

      SwalHelper.success("Category added", res.message);
      setTimeout(() => location.reload(), RELOAD_DELAY);
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

      if (!res.success) return SwalHelper.error(res.message);

      SwalHelper.success("Category updated", res.message);
      setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch {
      Swal.close();
      SwalHelper.error("Network error. Please try again.");
    }
  },

  async delete(id, name, productCount) {
    if (productCount > 0) {
      return Swal.fire({
        icon: "warning",
        title: "Cannot delete category",
        html: `
          <p class="text-gray-600 mt-2">
            Category <b>${name}</b> has <b>${productCount}</b> product(s).
            Please reassign or delete them first.
          </p>
        `,
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
      });
    }

    const res = await SwalHelper.confirmDelete(
      "Delete category?",
      `Delete <b>${name}</b>? This action cannot be undone.`,
    );

    if (!res.isConfirmed) return;

    SwalHelper.loading("Deleting category...");

    try {
      const data = await CategoryAPI.request({
        action: "delete_category",
        category_id: id,
      });

      Swal.close();

      if (!data.success) return SwalHelper.error(data.message);

      SwalHelper.success("Category deleted", data.message);
      document.getElementById(`category-row-${id}`)?.remove();
      setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch {
      Swal.close();
      SwalHelper.error("Network error. Please try again.");
    }
  },
};

/* =====================================================
   MODAL HANDLERS
===================================================== */
const Modal = {
  openEdit(id, name) {
    document.getElementById("edit_category_id").value = id;
    document.getElementById("edit_category_name").value = name;
    document.getElementById("editModal").classList.remove("hidden");
    setTimeout(
      () => document.getElementById("edit_category_name").focus(),
      100,
    );
  },

  closeEdit() {
    document.getElementById("editModal").classList.add("hidden");
  },
};

/* =====================================================
   GLOBAL FUNCTIONS (USED BY HTML)
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
   FORM SUBMITS
===================================================== */
document
  .getElementById("addCategoryForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));

    if (!data.category_name?.trim()) {
      return SwalHelper.error("Category name is required");
    }

    await CategoryManager.add(data);
    e.target.reset();
  });

document
  .getElementById("editCategoryForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));

    if (!data.category_name?.trim()) {
      return SwalHelper.error("Category name is required");
    }

    await CategoryManager.update(data);
    Modal.closeEdit();
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
  if (e.key === "Escape") Modal.closeEdit();
});
