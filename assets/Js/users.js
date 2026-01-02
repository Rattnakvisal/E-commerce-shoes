const _segments = window.location.pathname.split("/").filter(Boolean);
const APP_ROOT = _segments.length ? "/" + _segments[0] : "";
const USERS_API_URL = `${APP_ROOT}/admin/process/users/users_api_fixed.php`;

/* =========================================
   API HELPER
========================================= */
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

  // Read raw text first (prevents 'body stream already read')
  const text = await res.text();
  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch (err) {
      throw new Error(`Server error: ${text.slice(0, 150)}`);
    }
  } else {
    data = {};
  }

  if (!res.ok || data.success === false) {
    throw new Error(data.message || "Request failed");
  }

  return data;
}

/* =========================================
   VIEW USER
========================================= */
async function viewUser(userId) {
  try {
    showLoading("Loading user...");
    const { user } = await apiRequest("get_user", {
      method: "GET",
      params: { id: userId },
    });
    renderUserModal({ user });
  } catch (e) {
    showError(e.message);
  } finally {
    Swal.close();
  }
}

/* =========================================
   EDIT USER
========================================= */
async function editUser(userId) {
  try {
    showLoading("Loading user...");
    const { user } = await apiRequest("get_user", {
      method: "GET",
      params: { id: userId },
    });

    Swal.close();

    const result = await Swal.fire({
      title: "Edit User",
      html: userEditForm(user),
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: "Save",
      preConfirm: () => ({
        name: document.getElementById("editName").value.trim(),
        email: document.getElementById("editEmail").value.trim(),
        password: document.getElementById("editPassword").value,
        role: document.getElementById("editRole").value,
      }),
    });

    if (!result.isConfirmed) return;

    await apiRequest("update", {
      method: "POST",
      body: formData({ user_id: userId, ...result.value }),
    });

    showSuccess("User updated");
    setTimeout(() => location.reload(), 800);
  } catch (e) {
    showError(e.message);
  }
}

/* =========================================
   DELETE USER
========================================= */
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

/* =========================================
   ROLE CHANGE
========================================= */
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

/* =========================================
   STATUS CHANGE (FIXED)
========================================= */
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

/* =========================================
   MODAL RENDER
========================================= */
function renderUserModal({ user }) {
  Swal.fire({
    title: esc(user.name),
    html: `
      <div class="text-left space-y-2">
        <p><strong>Email:</strong> ${esc(user.email)}</p>
        <p><strong>Role:</strong> ${esc(user.role)}</p>
        <p><strong>Status:</strong> ${esc(user.status)}</p>
        <p><strong>Joined:</strong> ${new Date(
          user.created_at
        ).toLocaleDateString()}</p>
      </div>
    `,
    confirmButtonText: "Close",
  });
}

/* =========================================
   FORM TEMPLATES
========================================= */
function userEditForm(user) {
  return `
    <input id="editName" class="swal2-input" placeholder="Name" value="${esc(
      user.name
    )}">
    <input id="editEmail" class="swal2-input" placeholder="Email" value="${esc(
      user.email
    )}">
    <input id="editPassword" type="password" class="swal2-input" placeholder="New password (optional)">
    <select id="editRole" class="swal2-select">
      ${["customer", "staff", "admin"]
        .map(
          (r) =>
            `<option value="${r}" ${user.role === r ? "selected" : ""}>
              ${r}
            </option>`
        )
        .join("")}
    </select>
  `;
}

/* =========================================
   HELPERS
========================================= */
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

/* =========================================
   ALERT HELPERS
========================================= */
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
  Swal.fire({ icon: "error", title: "Error", text: msg });
}

/* =========================================
   GLOBAL EXPORTS
========================================= */
window.viewUser = viewUser;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.updateUserRole = updateUserRole;
window.toggleUserStatus = toggleUserStatus;
