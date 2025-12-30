/* =====================================================
   SWEETALERT2 HELPER FUNCTIONS
===================================================== */
const SwalHelpers = {
  toast(title, icon = "success") {
    Swal.fire({
      title,
      icon,
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });
  },

  loading(title = "Processing...") {
    Swal.fire({
      title,
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => Swal.showLoading(),
    });
  },

  success(title, text = "") {
    return Swal.fire({
      icon: "success",
      title,
      text,
      confirmButtonText: "OK",
      confirmButtonColor: "#3085d6",
    });
  },

  error(text) {
    return Swal.fire({
      icon: "error",
      title: "Error",
      text,
      confirmButtonText: "OK",
      confirmButtonColor: "#d33",
    });
  },

  confirmDelete(
    title = "Are you sure?",
    text = "You won't be able to revert this!"
  ) {
    return Swal.fire({
      title,
      text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      reverseButtons: true,
    });
  },
};

/* =====================================================
   DOM ELEMENTS
===================================================== */
const Elements = {
  userModal: document.getElementById("userModal"),
  modalTitle: document.getElementById("modalTitle"),
  userForm: document.getElementById("userForm"),
  formAction: document.getElementById("formAction"),
  formUserId: document.getElementById("formUserId"),
  formName: document.getElementById("formName"),
  formEmail: document.getElementById("formEmail"),
  formPassword: document.getElementById("formPassword"),
  formRole: document.getElementById("formRole"),
  passwordField: document.getElementById("passwordField"),
  passwordLabel: document.getElementById("passwordLabel"),
  passwordHint: document.getElementById("passwordHint"),
  submitBtn: document.getElementById("submitBtn"),
  submitText: document.getElementById("submitText"),
  loadingSpinner: document.getElementById("loadingSpinner"),
  openAddUserBtn: document.getElementById("openAddUserBtn"),
  closeModalBtn: document.getElementById("closeModal"),
  cancelBtn: document.getElementById("cancelBtn"),
  roleFilter: document.getElementById("roleFilter"),
  sortFilter: document.getElementById("sortFilter"),
};

/* =====================================================
   MODAL CONTROLLER
===================================================== */
const ModalController = {
  open() {
    Elements.userModal.classList.remove("hidden");
    Elements.userModal.classList.add("flex");
    setTimeout(() => Elements.formName?.focus(), 100);
  },

  close() {
    Elements.userModal.classList.add("hidden");
    Elements.userModal.classList.remove("flex");
    this.reset();
  },

  reset() {
    Elements.userForm.reset();
    Elements.formUserId.value = "";
    Elements.formAction.value = "create";
    Elements.modalTitle.textContent = "Add User";
    Elements.submitText.textContent = "Add User";
    Elements.submitBtn.className =
      "px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition";
    Elements.passwordLabel.textContent = "Password *";
    Elements.passwordHint.textContent = "Minimum 6 characters";
    Elements.formPassword.required = true;
  },

  setupForEdit(user) {
    Elements.formUserId.value = user.user_id;
    Elements.formName.value = user.name || "";
    Elements.formEmail.value = user.email || "";
    Elements.formRole.value = user.role || "customer";
    Elements.formAction.value = "update";
    Elements.modalTitle.textContent = "Edit User";
    Elements.submitText.textContent = "Update User";
    Elements.submitBtn.className =
      "px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition";
    Elements.passwordLabel.textContent =
      "Password (leave blank to keep current)";
    Elements.passwordHint.textContent = "Leave blank to keep current password";
    Elements.formPassword.required = false;
  },
};

/* =====================================================
   API SERVICE
===================================================== */
const ApiService = {
  async request(data, method = "POST") {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));

    const response = await fetch("user_api.php", { method, body: formData });
    return await response.json().catch(() => ({
      success: false,
      message: "Failed to parse server response",
    }));
  },

  async getUser(id) {
    const response = await fetch(`user_api.php?action=get_user&id=${id}`);
    return await response.json().catch(() => ({
      success: false,
      message: "Failed to parse server response",
    }));
  },
};

/* =====================================================
   USER MANAGER
===================================================== */
const UserManager = {
  async edit(id) {
    SwalHelpers.loading("Loading user details...");

    const data = await ApiService.getUser(id);
    Swal.close();

    if (data?.success) {
      ModalController.setupForEdit(data.user);
      ModalController.open();
    } else {
      SwalHelpers.error(data?.message || "Failed to load user");
    }
  },

  async delete(id, userName) {
    const result = await SwalHelpers.confirmDelete(
      "Delete User?",
      `Are you sure you want to delete "${userName}"?`
    );
    if (!result.isConfirmed) return;

    SwalHelpers.loading("Deleting user...");
    const data = await ApiService.request({ action: "delete", user_id: id });
    Swal.close();

    if (data?.success) {
      await SwalHelpers.success("Deleted!", "User has been deleted.");
      document.querySelector(`tr[data-id="${id}"]`)?.remove();
    } else {
      SwalHelpers.error(data?.message || "Failed to delete user");
    }
  },

  async changeRole(id, userName) {
    const { value: role } = await Swal.fire({
      title: "Change User Role",
      text: `Select new role for "${userName}"`,
      input: "select",
      inputOptions: { admin: "Admin", staff: "Staff", customer: "Customer" },
      inputPlaceholder: "Select a role",
      showCancelButton: true,
      confirmButtonText: "Change Role",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#3085d6",
      inputValidator: (value) => (!value ? "Please select a role" : null),
    });

    if (!role) return;

    SwalHelpers.loading("Updating role...");
    const data = await ApiService.request({
      action: "update_role",
      user_id: id,
      role,
    });
    Swal.close();

    if (data?.success) {
      await SwalHelpers.success(
        "Role Updated!",
        `User role changed to ${role}.`
      );
      this.updateRoleInUI(id, role);
    } else {
      SwalHelpers.error(data?.message || "Failed to update role");
    }
  },

  updateRoleInUI(id, role) {
    const roleSpan = document.querySelector(
      `tr[data-id="${id}"] .role-cell span`
    );
    if (!roleSpan) return;

    roleSpan.textContent = role.charAt(0).toUpperCase() + role.slice(1);

    const roleClasses = {
      admin: "role-admin",
      staff: "role-staff",
      customer: "role-customer",
    };

    roleSpan.className = `px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
      roleClasses[role] || "role-customer"
    }`;
  },
};

/* =====================================================
   FORM VALIDATOR
===================================================== */
const FormValidator = {
  validate() {
    if (!Elements.formName.value.trim()) {
      SwalHelpers.error("Name is required");
      return false;
    }

    if (!Elements.formEmail.value.trim()) {
      SwalHelpers.error("Email is required");
      return false;
    }

    if (
      Elements.formAction.value === "create" &&
      (!Elements.formPassword.value || Elements.formPassword.value.length < 6)
    ) {
      SwalHelpers.error("Password must be at least 6 characters");
      return false;
    }

    return true;
  },
};

/* =====================================================
   FORM SUBMIT HANDLER
===================================================== */
const FormHandler = {
  setLoading(isLoading) {
    Elements.submitBtn.disabled = isLoading;
    Elements.loadingSpinner.classList.toggle("hidden", !isLoading);

    const action = Elements.formAction.value;
    Elements.submitText.textContent = isLoading
      ? action === "create"
        ? "Creating..."
        : "Updating..."
      : action === "create"
      ? "Add User"
      : "Update User";
  },

  async handleSubmit(e) {
    e.preventDefault();
    if (!FormValidator.validate()) return;

    this.setLoading(true);

    try {
      const data = await ApiService.request({
        action: Elements.formAction.value,
        user_id: Elements.formUserId.value,
        name: Elements.formName.value.trim(),
        email: Elements.formEmail.value.trim(),
        password: Elements.formPassword.value,
        role: Elements.formRole.value,
      });

      this.setLoading(false);

      if (data?.success) {
        const message =
          Elements.formAction.value === "create"
            ? "User Created!"
            : "User Updated!";
        await SwalHelpers.success(message, data.message);
        ModalController.close();
        window.location.reload();
      } else {
        SwalHelpers.error(data?.message || "Operation failed");
      }
    } catch (error) {
      this.setLoading(false);
      SwalHelpers.error("Network error. Please try again.");
    }
  },
};

/* =====================================================
   FILTER CONTROLLER
===================================================== */
const FilterController = {
  update() {
    const search = document.querySelector('input[name="search"]')?.value || "";
    const role = Elements.roleFilter?.value || "";
    const sort = Elements.sortFilter?.value || "";

    let url = `user.php?page=1`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (role) url += `&role=${encodeURIComponent(role)}`;
    if (sort) url += `&sort=${encodeURIComponent(sort)}`;

    window.location.href = url;
  },
};

/* =====================================================
   UTILITY FUNCTIONS
===================================================== */
function refreshData() {
  SwalHelpers.loading("Refreshing data...");
  setTimeout(() => {
    localStorage.setItem("users_refreshed", "1");
    window.location.reload();
  }, 150);
}

/* =====================================================
   EVENT LISTENERS SETUP
===================================================== */
function setupEventListeners() {
  Elements.openAddUserBtn?.addEventListener("click", () => {
    ModalController.reset();
    ModalController.open();
  });

  Elements.closeModalBtn?.addEventListener("click", () =>
    ModalController.close()
  );
  Elements.cancelBtn?.addEventListener("click", () => ModalController.close());

  // Form events
  Elements.userForm?.addEventListener("submit", (e) =>
    FormHandler.handleSubmit(e)
  );

  // Filter events
  const applyBtn = document.getElementById("applyFiltersBtn");
  applyBtn?.addEventListener("click", () => FilterController.update());

  const searchForm = document.getElementById("searchForm");
  searchForm?.addEventListener("submit", (e) => {
    e.preventDefault();
    FilterController.update();
  });

  // Modal backdrop click
  Elements.userModal?.addEventListener("click", (e) => {
    if (e.target === Elements.userModal) ModalController.close();
  });

  // Update pagination links to preserve current filters and handle clicks
  function updatePaginationLinks() {
    const pagination = document.getElementById("usersPagination");
    if (!pagination) return;
    const links = pagination.querySelectorAll("a.pagination-link");
    links.forEach((link) => {
      const page = link.dataset.page;
      if (!page) return;
      if (link.dataset.bound) return; // avoid duplicate handlers
      link.dataset.bound = "1";
      link.addEventListener("click", (evt) => {
        evt.preventDefault();
        let url = `user.php?page=${encodeURIComponent(page)}`;
        window.location.href = url;
      });
    });
  }
  // Run once initially
  setTimeout(updatePaginationLinks, 50);
}

/* =====================================================
   INITIALIZATION
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  setupEventListeners();
  if (localStorage.getItem("users_refreshed")) {
    localStorage.removeItem("users_refreshed");
    setTimeout(() => SwalHelpers.toast("Data refreshed!", "success"), 300);
  }
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
window.editUser = UserManager.edit.bind(UserManager);
window.deleteUser = UserManager.delete.bind(UserManager);
window.quickChangeRole = UserManager.changeRole.bind(UserManager);
window.refreshData = refreshData;
