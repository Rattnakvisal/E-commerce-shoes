/* =====================================================
   CONFIG
===================================================== */
const BASE_URL = "/E-commerce-shoes";
const USERS_API_URL = `${BASE_URL}/admin/controller/users/users_api.php`;
const RELOAD_DELAY = 700;

/* =====================================================
   API HELPER
===================================================== */
async function apiRequest(action, options = {}) {
  const method = options.method || "GET";

  const url =
    method === "GET"
      ? `${USERS_API_URL}?action=${action}&${new URLSearchParams(
          options.params || {},
        )}`
      : `${USERS_API_URL}?action=${action}`;

  const res = await fetch(url, {
    method,
    body: options.body || null,
    credentials: "same-origin",
  });

  const text = await res.text();
  let data;

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

/* =====================================================
   UTILITIES
===================================================== */
const delayReload = () => setTimeout(() => location.reload(), RELOAD_DELAY);

const formData = (obj) => {
  const fd = new FormData();
  Object.entries(obj).forEach(([k, v]) => fd.append(k, v));
  return fd;
};

const esc = (text = "") => {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
};

/* =====================================================
   SWEETALERT HELPERS (SAME AS PRODUCTS)
===================================================== */
function showLoading(msg = "Loading...") {
  Swal.fire({
    title: msg,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    },
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

/* Edit confirm (Products style) */
function confirmEdit(title, text) {
  return Swal.fire({
    icon: "question",
    title: title || "Edit user?",
    html: `
      <p class="text-gray-600 mt-2">
        ${text || "Open the editor to update this user's information, role, or status."}
      </p>
    `,
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
  });
}

/* Delete confirm (Products style) */
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

/* =====================================================
   USER ACTIONS
===================================================== */
async function viewUser(userId) {
  try {
    const { user } = await apiRequest("get_user", {
      params: { id: userId },
    });

    Swal.fire({
      title: esc(user.name),
      html: `
        <div class="text-left space-y-2">
          <p><b>Email:</b> ${esc(user.email)}</p>
          <p><b>Phone:</b> ${esc(user.phone || "-")}</p>
          <p><b>Role:</b> ${esc(user.role)}</p>
          <p><b>Status:</b> ${esc(user.status)}</p>
          <p><b>Joined:</b> ${new Date(user.created_at).toLocaleDateString()}</p>
        </div>
      `,
    });
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   EDIT USER (OLD EDIT-ONLY MODAL)
===================================================== */
async function editUser(userId) {
  const confirmed = await confirmEdit(
    "Edit user?",
    "You can update user details, role, status, or reset the password.",
  );
  if (!confirmed.isConfirmed) return;

  try {
    showLoading("Loading user...");

    const { user } = await apiRequest("get_user", {
      params: { id: userId },
    });

    Swal.close();

    document.getElementById("edit_user_id").value =
      user.user_id ?? user.id ?? "";
    document.getElementById("edit_name").value = user.name ?? "";
    document.getElementById("edit_email").value = user.email ?? "";
    document.getElementById("edit_phone").value = user.phone ?? "";
    document.getElementById("edit_role").value = user.role ?? "customer";
    document.getElementById("edit_status").value = user.status ?? "active";
    document.getElementById("edit_password").value = "";

    document.getElementById("editUserModal").classList.remove("hidden");
  } catch (e) {
    Swal.close();
    showError(e.message);
  }
}

/* =====================================================
   DELETE / ROLE / STATUS
===================================================== */
async function deleteUser(userId) {
  const res = await confirmDelete();
  if (!res.isConfirmed) return;

  try {
    showLoading("Deleting user...");

    await apiRequest("delete", {
      method: "POST",
      body: formData({ user_id: userId }),
    });

    Swal.close();
    showSuccess("User deleted", "The user account has been removed.");
    delayReload();
  } catch (e) {
    Swal.close();
    showError(e.message);
  }
}

async function updateUserRole(userId, role) {
  try {
    await apiRequest("update_role", {
      method: "POST",
      body: formData({ user_id: userId, role }),
    });

    showSuccess("Role updated", "User role has been updated.");
    delayReload();
  } catch (e) {
    showError(e.message);
  }
}

async function toggleUserStatus(userId, action) {
  const status = action === "deactivate" ? "inactive" : "active";

  try {
    await apiRequest("update_status", {
      method: "POST",
      body: formData({ user_id: userId, status }),
    });

    showSuccess(
      "Status updated",
      `User has been ${status === "active" ? "activated" : "deactivated"}.`,
    );
    delayReload();
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   FORMS
===================================================== */
document
  .getElementById("editUserForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();

    try {
      showLoading("Updating user...");

      await apiRequest("update", {
        method: "POST",
        body: new FormData(e.target),
      });

      Swal.close();
      showSuccess("User updated", "User information updated successfully.");
      closeEditUserModal();
      delayReload();
    } catch (e) {
      Swal.close();
      showError(e.message);
    }
  });

document
  .getElementById("addUserForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const f = e.target;

    if (f.password.value !== f.confirm_password.value) {
      return showError("Passwords do not match");
    }

    try {
      showLoading("Creating user...");

      await apiRequest("create", {
        method: "POST",
        body: new FormData(f),
      });

      Swal.close();
      showSuccess("User created", "New user account created successfully.");
      closeAddUserModal();
      delayReload();
    } catch (e) {
      Swal.close();
      showError(e.message);
    }
  });

/* =====================================================
   MODAL HELPERS
===================================================== */
function showAddUserModal() {
  document.getElementById("addUserForm")?.reset();
  document.getElementById("addUserModal")?.classList.remove("hidden");
}

function closeAddUserModal() {
  document.getElementById("addUserModal")?.classList.add("hidden");
}

function closeEditUserModal() {
  document.getElementById("editUserModal")?.classList.add("hidden");
}

/* Overlay + ESC */
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) {
    closeAddUserModal();
    closeEditUserModal();
  }
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeAddUserModal();
    closeEditUserModal();
  }
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  viewUser,
  editUser,
  deleteUser,
  updateUserRole,
  toggleUserStatus,
  showAddUserModal,
  closeAddUserModal,
  closeEditUserModal,
});
