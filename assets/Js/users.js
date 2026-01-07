/* =====================================================
   CONFIG
===================================================== */
const BASE_URL = "/E-commerce-shoes";

// USERS API
const USERS_API_URL = `${BASE_URL}/admin/process/users/users_api.php`;

/* =====================================================
   API HELPER
===================================================== */
async function apiRequest(action, options = {}) {
  const method = options.method || "GET";

  const url =
    method === "GET"
      ? `${USERS_API_URL}?action=${action}&${new URLSearchParams(
          options.params || {}
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
    throw new Error("Server returned invalid JSON");
  }

  if (!res.ok || data.success === false) {
    throw new Error(data.message || "Request failed");
  }

  return data;
}

/* =====================================================
   MODAL CONTROLS
===================================================== */
function openEditUserModal() {
  document.getElementById("editUserModal")?.classList.remove("hidden");
}

function closeEditUserModal() {
  document.getElementById("editUserModal")?.classList.add("hidden");
}

/* =====================================================
   VIEW USER
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
          <p><strong>Email:</strong> ${esc(user.email)}</p>
          <p><strong>Phone:</strong> ${esc(user.phone || "-")}</p>
          <p><strong>Role:</strong> ${esc(user.role)}</p>
          <p><strong>Status:</strong> ${esc(user.status)}</p>
          <p><strong>Joined:</strong> ${new Date(
            user.created_at
          ).toLocaleDateString()}</p>
        </div>
      `,
      confirmButtonText: "Close",
    });
  } catch (e) {
    showError(e.message);
  }
}

function confirmEdit(title, text) {
  return Swal.fire({
    title: title || "Edit user?",
    text: text || "Open editor for this user.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
    reverseButtons: false,
  });
}

/* =====================================================
   EDIT USER
===================================================== */
async function editUser(userId) {
  const confirmed = await confirmEdit(
    "Edit user?",
    "Open editor for this user."
  );
  if (!confirmed || !confirmed.isConfirmed) return;
  try {
    if (typeof Swal !== "undefined") Swal.close();

    const { user } = await apiRequest("get_user", {
      params: { id: userId },
    });

    document.getElementById("edit_user_id").value = user.user_id;
    document.getElementById("edit_name").value = user.name || "";
    document.getElementById("edit_email").value = user.email || "";
    document.getElementById("edit_phone").value = user.phone || "";
    document.getElementById("edit_role").value = user.role || "customer";
    document.getElementById("edit_status").value = user.status || "active";
    document.getElementById("edit_password").value = "";

    openEditUserModal();
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   EDIT FORM SUBMIT
===================================================== */
document
  .getElementById("editUserForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fd = new FormData(e.target);
    fd.append("action", "update");

    try {
      await apiRequest("update", {
        method: "POST",
        body: fd,
      });

      showSuccess("User updated");
      setTimeout(() => location.reload(), 800);
    } catch (e) {
      showError(e.message);
    }
  });

/* =====================================================
   DELETE USER
===================================================== */
async function deleteUser(userId) {
  const confirm = await Swal.fire({
    title: "Delete user?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
  });

  if (!confirm.isConfirmed) return;

  try {
    await apiRequest("delete", {
      method: "POST",
      body: formData({ user_id: userId }),
    });

    showSuccess("User deleted");
    setTimeout(() => location.reload(), 800);
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   UPDATE ROLE
===================================================== */
async function updateUserRole(userId, role) {
  try {
    await apiRequest("update_role", {
      method: "POST",
      body: formData({ user_id: userId, role }),
    });

    showSuccess("Role updated");
    setTimeout(() => location.reload(), 800);
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   TOGGLE STATUS
===================================================== */
async function toggleUserStatus(userId, action) {
  const status = action === "deactivate" ? "inactive" : "active";

  try {
    await apiRequest("update_status", {
      method: "POST",
      body: formData({ user_id: userId, status }),
    });

    showSuccess("Status updated");
    setTimeout(() => location.reload(), 800);
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   HELPERS
===================================================== */
function formData(obj) {
  const fd = new FormData();
  Object.entries(obj).forEach(([k, v]) => fd.append(k, v));
  return fd;
}

function esc(text = "") {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/* =====================================================
   ALERTS
===================================================== */
function showLoading(msg = "Loading...") {
  Swal.fire({
    title: msg,
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });
}

function showSuccess(msg) {
  Swal.fire({
    icon: "success",
    title: msg,
    timer: 1200,
    showConfirmButton: false,
  });
}

function showError(msg) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: msg,
  });
}

function showAddUserModal() {
  document.getElementById("addUserModal").classList.remove("hidden");
}

function closeAddUserModal() {
  document.getElementById("addUserModal").classList.add("hidden");
}

function closeModal() {
  document.getElementById("userDetailsModal").classList.add("hidden");
}

// Handle add user form submission -> call users API
document
  .getElementById("addUserForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const form = this;
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;
    const confirm_password = form.confirm_password.value;
    const role = form.role.value;
    const phone = form.phone.value || "";
    const status = form.status.value || "active";

    if (password !== confirm_password) {
      Swal.fire("Error", "Passwords do not match", "error");
      return;
    }

    try {
      showLoading("Creating user...");
      await apiRequest("create", {
        method: "POST",
        body: formData({
          name,
          email,
          password,
          role,
          phone,
          status,
        }),
      });

      Swal.close();
      showSuccess("User created");
      closeAddUserModal();
      form.reset();
      setTimeout(() => location.reload(), 800);
    } catch (err) {
      Swal.close();
      showError(err.message);
    }
  });

// Close modals on escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeModal();
    closeAddUserModal();
  }
});

// Close modals on overlay click
document.getElementById("userDetailsModal").addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) {
    closeModal();
  }
});

document.getElementById("addUserModal").addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) {
    closeAddUserModal();
  }
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
window.viewUser = viewUser;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.updateUserRole = updateUserRole;
window.toggleUserStatus = toggleUserStatus;
