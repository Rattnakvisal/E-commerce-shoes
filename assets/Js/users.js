/* =====================================================
   CONFIG
===================================================== */
const BASE_URL = "/E-commerce-shoes";
const USERS_API_URL = `${BASE_URL}/admin/process/users/users_api.php`;
const RELOAD_DELAY = 700;

/* =====================================================
   API HELPER
===================================================== */
async function apiRequest(action, options = {}) {
  const method = options.method || "GET";

  const url =
    method === "GET"
      ? `${USERS_API_URL}?action=${action}&${new URLSearchParams(options.params || {})}`
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

const debounce = (fn, delay = 300) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
};

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
const showLoading = (msg = "Loading...") =>
  Swal.fire({
    title: msg,
    allowOutsideClick: false,
    didOpen: Swal.showLoading,
  });

const showSuccess = (msg) =>
  Swal.fire({
    icon: "success",
    title: msg,
    timer: 1200,
    showConfirmButton: false,
  });

const showError = (msg) =>
  Swal.fire({ icon: "error", title: "Error", text: msg });

/* =====================================================
   LIVE SEARCH (TABLE FILTER)
===================================================== */
const searchInput = document.getElementById("liveUserSearch");
const userRows = document.querySelectorAll("tbody tr[data-user-id]");

if (searchInput) {
  searchInput.addEventListener(
    "input",
    debounce((e) => {
      const q = e.target.value.toLowerCase().trim();

      userRows.forEach((row) => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? "" : "none";
      });
    }, 200),
  );
}

/* =====================================================
   USER ACTIONS
===================================================== */
async function viewUser(userId) {
  try {
    const { user } = await apiRequest("get_user", { params: { id: userId } });

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

async function editUser(userId) {
  const confirm = await Swal.fire({
    title: "Edit user?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Edit",
  });

  if (!confirm.isConfirmed) return;

  try {
    const { user } = await apiRequest("get_user", { params: { id: userId } });

    ["user_id", "name", "email", "phone", "role", "status"].forEach((k) => {
      const el = document.getElementById(`edit_${k}`);
      if (el) el.value = user[k] || "";
    });

    document.getElementById("edit_password").value = "";
    document.getElementById("editUserModal")?.classList.remove("hidden");
  } catch (e) {
    showError(e.message);
  }
}

async function deleteUser(userId) {
  const confirm = await Swal.fire({
    title: "Delete user?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
  });

  if (!confirm.isConfirmed) return;

  try {
    await apiRequest("delete", {
      method: "POST",
      body: formData({ user_id: userId }),
    });
    showSuccess("User deleted");
    delayReload();
  } catch (e) {
    showError(e.message);
  }
}

async function updateUserRole(userId, role) {
  try {
    await apiRequest("update_role", {
      method: "POST",
      body: formData({ user_id: userId, role }),
    });
    showSuccess("Role updated");
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
    showSuccess("Status updated");
    delayReload();
  } catch (e) {
    showError(e.message);
  }
}

/* =====================================================
   ADD / EDIT FORMS
===================================================== */
document
  .getElementById("editUserForm")
  ?.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      await apiRequest("update", {
        method: "POST",
        body: new FormData(e.target),
      });
      showSuccess("User updated");
      delayReload();
    } catch (e) {
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
      await apiRequest("create", { method: "POST", body: new FormData(f) });
      Swal.close();
      showSuccess("User created");
      delayReload();
    } catch (e) {
      Swal.close();
      showError(e.message);
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
});
