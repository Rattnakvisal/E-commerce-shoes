/* =====================================================
   USERS PAGE FULL JS (Single Modal like Products)
   - One modal: #userModal (Add/Edit)
   - SweetAlert: loading/success/error/confirm
   - API: users_api.php?action=...
   - Avatar upload: click + drag/drop + preview + remove
   - Overlay click + ESC close
   - Works with your PHP API actions: get_user, create, update, delete, update_role, update_status
===================================================== */
(() => {
  "use strict";

  /* ===============================
     CONFIG
  ================================ */
  const BASE_URL = "/E-commerce-shoes";
  const USERS_API_URL = `${BASE_URL}/admin/controller/users/users_api.php`;
  const RELOAD_DELAY = 700;

  const MAX_BYTES = 2 * 1024 * 1024;
  const ALLOWED_TYPES = new Set([
    "image/png",
    "image/jpeg",
    "image/gif",
    "image/webp",
  ]);

  /* ===============================
     DOM HELPERS
  ================================ */
  const $ = (sel, root = document) => root.querySelector(sel);

  const esc = (text = "") => {
    const div = document.createElement("div");
    div.textContent = String(text ?? "");
    return div.innerHTML;
  };

  const delayReload = () => setTimeout(() => location.reload(), RELOAD_DELAY);

  const toFormData = (obj) => {
    const fd = new FormData();
    Object.entries(obj || {}).forEach(([k, v]) => fd.append(k, v));
    return fd;
  };

  /* ===============================
     API HELPER
  ================================ */
  async function apiRequest(action, options = {}) {
    const method = options.method || "GET";

    const url =
      method === "GET"
        ? `${USERS_API_URL}?action=${encodeURIComponent(action)}&${new URLSearchParams(
            options.params || {},
          )}`
        : `${USERS_API_URL}?action=${encodeURIComponent(action)}`;

    const res = await fetch(url, {
      method,
      body: options.body || null,
      credentials: "same-origin",
    });

    const text = await res.text();
    let data = {};
    try {
      data = text ? JSON.parse(text) : {};
    } catch {
      throw new Error("Invalid server response");
    }

    if (!res.ok || data.success === false) {
      throw new Error(data.message || "Request failed");
    }
    return data;
  }

  /* ===============================
     SWEETALERT HELPERS
  ================================ */
  function showLoading(msg = "Loading...") {
    Swal.fire({
      title: msg,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading(),
    });
  }

  function showSuccess(title, text = "") {
    return Swal.fire({
      icon: "success",
      title,
      text: text || undefined,
      showConfirmButton: false,
      timer: 1200,
      timerProgressBar: true,
    });
  }

  function showError(msg) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: msg,
      confirmButtonColor: "#dc2626",
    });
  }

  function confirmEdit(title, text) {
    return Swal.fire({
      icon: "question",
      title: title || "Edit user?",
      html: `<p class="text-gray-600 mt-2">${
        text || "Open editor to update this user."
      }</p>`,
      showCancelButton: true,
      confirmButtonText: "Edit",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#6b46c1",
      cancelButtonColor: "#6b7280",
    });
  }

  function confirmDelete() {
    return Swal.fire({
      title: "Delete user?",
      text: "This action cannot be undone",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#dc2626",
    });
  }

  /* ===============================
     MODAL DOM (Single Modal)
  ================================ */
  const modal = $("#userModal");
  const overlay = modal?.querySelector(".modal-overlay");

  const closeBtn = $("#closeUserModal");
  const cancelBtn = $("#cancelUserBtn");

  const modalTitle = $("#userModalTitle");
  const saveText = $("#saveUserText");

  const form = $("#userForm");
  const formAction = $("#userFormAction");
  const userId = $("#userId");
  const oldAvatar = $("#oldAvatar");

  const nameEl = $("#userName");
  const emailEl = $("#userEmail");
  const phoneEl = $("#userPhone");
  const roleEl = $("#userRole");
  const statusEl = $("#userStatus");

  const pwLabel = $("#passwordLabel");
  const pwHint = $("#passwordHint");
  const pwEl = $("#userPassword");
  const confirmWrap = $("#confirmWrap");
  const confirmEl = $("#userConfirmPassword");

  const avatarInput = $("#userAvatar");
  const uploadBox = $("#userUploadBox");
  const previewWrap = $("#userImagePreview");
  const previewImg = $("#userPreviewImage");
  const removeImgBtn = $("#removeUserImage");

  function openModal() {
    modal?.classList.remove("hidden");
  }
  function closeModal() {
    modal?.classList.add("hidden");
  }

  function clearPreview() {
    if (avatarInput) avatarInput.value = "";
    if (previewImg) previewImg.src = "";
    previewWrap?.classList.add("hidden");
  }

  function setPreview(src) {
    if (!src || !previewImg || !previewWrap) return;
    previewImg.src = src;
    previewWrap.classList.remove("hidden");
  }

  function validateImage(file) {
    if (!file) return "No file selected";
    if (file.size > MAX_BYTES) return "Image too large. Max 2MB.";
    if (file.type && !ALLOWED_TYPES.has(file.type))
      return "Only JPG/PNG/GIF/WEBP allowed.";
    return "";
  }

  /* ===============================
     OPEN ADD / OPEN EDIT
  ================================ */
  function openAddUserModal() {
    modalTitle.textContent = "Add User";
    saveText.textContent = "Add User";
    formAction.value = "add";

    userId.value = "";
    oldAvatar.value = "";

    form?.reset?.(); // reset fields
    // Ensure defaults
    roleEl.value = "customer";
    statusEl.value = "active";

    // password required + confirm visible
    pwLabel.textContent = "Password *";
    pwEl.required = true;
    pwHint.classList.add("hidden");
    confirmWrap.classList.remove("hidden");
    confirmEl.required = true;

    clearPreview();
    openModal();
  }

  async function editUser(id) {
    const ok = await confirmEdit(
      "Edit user?",
      "You can update name, email, phone, role, status, password, and avatar.",
    );
    if (!ok.isConfirmed) return;

    try {
      showLoading("Loading user...");

      const { user } = await apiRequest("get_user", { params: { id } });

      Swal.close();

      modalTitle.textContent = "Edit User";
      saveText.textContent = "Save Changes";
      formAction.value = "edit"; // only internal label

      userId.value = user.user_id ?? user.id ?? "";
      oldAvatar.value = user.avatar_url ?? "";

      nameEl.value = user.name ?? "";
      emailEl.value = user.email ?? "";
      phoneEl.value = user.phone ?? "";
      roleEl.value = user.role ?? "customer";
      statusEl.value = user.status ?? "active";

      // password optional + confirm hidden
      pwLabel.textContent = "New Password (optional)";
      pwEl.required = false;
      pwEl.value = "";
      pwHint.classList.remove("hidden");
      confirmWrap.classList.add("hidden");
      confirmEl.required = false;
      confirmEl.value = "";

      clearPreview();
      if (user.avatar_url) setPreview(user.avatar_url);

      openModal();
    } catch (e) {
      Swal.close();
      showError(e.message);
    }
  }

  /* ===============================
     VIEW USER (optional)
  ================================ */
  async function viewUser(id) {
    try {
      const { user } = await apiRequest("get_user", { params: { id } });
      Swal.fire({
        title: esc(user.name),
        html: `
          <div class="text-left space-y-2">
            <p><b>Email:</b> ${esc(user.email)}</p>
            <p><b>Phone:</b> ${esc(user.phone || "-")}</p>
            <p><b>Role:</b> ${esc(user.role)}</p>
            <p><b>Status:</b> ${esc(user.status || "-")}</p>
            <p><b>Joined:</b> ${user.created_at ? new Date(user.created_at).toLocaleDateString() : "-"}</p>
          </div>
        `,
      });
    } catch (e) {
      showError(e.message);
    }
  }

  /* ===============================
     DELETE / ROLE / STATUS
  ================================ */
  async function deleteUser(id) {
    const res = await confirmDelete();
    if (!res.isConfirmed) return;

    try {
      showLoading("Deleting user...");
      await apiRequest("delete", {
        method: "POST",
        body: toFormData({ user_id: id }),
      });
      Swal.close();
      showSuccess("User deleted", "The user account has been removed.");
      delayReload();
    } catch (e) {
      Swal.close();
      showError(e.message);
    }
  }

  async function updateUserRole(id, role) {
    try {
      await apiRequest("update_role", {
        method: "POST",
        body: toFormData({ user_id: id, role }),
      });
      showSuccess("Role updated", "User role updated.");
      delayReload();
    } catch (e) {
      showError(e.message);
    }
  }

  async function toggleUserStatus(id, action) {
    const status = action === "deactivate" ? "inactive" : "active";
    try {
      await apiRequest("update_status", {
        method: "POST",
        body: toFormData({ user_id: id, status }),
      });
      showSuccess("Status updated", `User is now ${status}.`);
      delayReload();
    } catch (e) {
      showError(e.message);
    }
  }

  /* ===============================
     SUBMIT FORM (Add/Edit)
  ================================ */
  form?.addEventListener("submit", async (e) => {
    e.preventDefault();

    try {
      const isAdd = !userId.value; // add if no id

      // Validate password confirm on add
      if (isAdd) {
        const p1 = (pwEl.value || "").trim();
        const p2 = (confirmEl.value || "").trim();
        if (p1.length < 6)
          return showError("Password must be at least 6 characters");
        if (p1 !== p2) return showError("Passwords do not match");
      } else {
        // edit: if password provided, validate length
        if (
          (pwEl.value || "").trim() !== "" &&
          (pwEl.value || "").trim().length < 6
        ) {
          return showError("Password must be at least 6 characters");
        }
      }

      showLoading(isAdd ? "Creating user..." : "Updating user...");

      // Build body
      const fd = new FormData(form);

      // remove any conflicting form 'action' field so query param wins
      try {
        fd.delete("action");
      } catch (e) {}

      // IMPORTANT: your API action names (sent in query string)
      const actionName = isAdd ? "create" : "update";

      await apiRequest(actionName, { method: "POST", body: fd });

      Swal.close();
      showSuccess(
        isAdd ? "User created" : "User updated",
        "Saved successfully.",
      );
      closeModal();
      delayReload();
    } catch (err) {
      Swal.close();
      showError(err.message);
    }
  });

  /* ===============================
     UPLOAD (Click + DragDrop + Preview + Remove)
  ================================ */
  function handleFile(file) {
    const msg = validateImage(file);
    if (msg) {
      showError(msg);
      if (avatarInput) avatarInput.value = "";
      return;
    }

    // if new image selected -> clear old avatar value (so backend knows replace)
    if (oldAvatar) oldAvatar.value = "";

    setPreview(URL.createObjectURL(file));
  }

  // click upload box opens input
  uploadBox?.addEventListener("click", (e) => {
    // ignore clicks on remove button
    if (
      removeImgBtn &&
      (e.target === removeImgBtn || removeImgBtn.contains(e.target))
    )
      return;
    avatarInput?.click();
  });

  avatarInput?.addEventListener("change", () => {
    const f = avatarInput.files?.[0];
    if (f) handleFile(f);
  });

  // remove image
  removeImgBtn?.addEventListener("click", () => {
    clearPreview();
    // mark for removal so backend clears avatar_url
    if (oldAvatar) oldAvatar.value = "__REMOVE__";
  });

  // drag style
  const addDrag = () => uploadBox?.classList.add("border-indigo-400");
  const rmDrag = () => uploadBox?.classList.remove("border-indigo-400");

  ["dragenter", "dragover"].forEach((evt) => {
    uploadBox?.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      addDrag();
    });
  });

  ["dragleave", "drop"].forEach((evt) => {
    uploadBox?.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      rmDrag();
    });
  });

  uploadBox?.addEventListener("drop", (e) => {
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;

    // Put into input so FormData includes it
    const dt = new DataTransfer();
    dt.items.add(file);
    avatarInput.files = dt.files;

    handleFile(file);
  });

  /* ===============================
     CLOSE (overlay, X, cancel, ESC)
  ================================ */
  function wireClose() {
    overlay?.addEventListener("click", closeModal);
    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal && !modal.classList.contains("hidden"))
        closeModal();
    });
  }

  /* ===============================
     INIT + EXPORT GLOBALS
  ================================ */
  function init() {
    if (!modal || !form) {
      // still export actions even if modal missing
      Object.assign(window, {
        editUser,
        deleteUser,
        toggleUserStatus,
        updateUserRole,
        viewUser,
        openAddUserModal,
      });
      return;
    }
    wireClose();

    Object.assign(window, {
      openAddUserModal,
      editUser,
      viewUser,
      deleteUser,
      updateUserRole,
      toggleUserStatus,
      closeUserModal: closeModal,
    });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
function refreshUsers() {
  Swal.fire({
    title: "Refreshing…",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  setTimeout(() => location.reload(), 600);
}
