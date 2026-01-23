/* =====================================================
   CONFIG
===================================================== */
const API_URL = "api.php";

/* =====================================================
   DOM HELPERS
===================================================== */
const $ = (id) => document.getElementById(id);
let els = {};

/* =====================================================
   SWEETALERT HELPERS (match users.js style)
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

function confirmBox(title, text) {
  return Swal.fire({
    title: title || "Are you sure?",
    text: text || "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
  });
}

function confirmDelete(
  title = "Delete item?",
  text = "This action cannot be undone",
) {
  return confirmBox(title, text);
}

/* compatibility wrappers used below */
const toast = (title) => showSuccess(title);
const loading = (msg) => showLoading(msg);
const errorBox = (msg) => showError(msg);

/* =====================================================
   MODAL HELPERS
===================================================== */
const openModal = (id) => $(id)?.classList.replace("hidden", "flex");
const closeModal = (id) => $(id)?.classList.replace("flex", "hidden");

/* =====================================================
   API
===================================================== */
async function api(action, payload = {}) {
  const res = await fetch(`${API_URL}?action=${action}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  try {
    return await res.json();
  } catch {
    throw new Error("Invalid API response");
  }
}

/* =====================================================
   LOAD DATA
===================================================== */
async function loadData() {
  try {
    const res = await fetch(`${API_URL}?action=fetch_all`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.msg);

    window._menuData = data;

    els.parentCount.textContent = data.parents.length;
    els.groupCount.textContent = data.groups.length;
    els.itemCount.textContent = data.items.length;

    populateSelects(data);
    renderMenu(data.parents, data.groups, data.items);
  } catch (err) {
    console.error(err);
    els.menuStructure.innerHTML = `<div class="text-center text-red-500 py-6">Failed to load menu data</div>`;
  }
}

/* =====================================================
   SELECT POPULATION
===================================================== */
function populateSelects({ parents, groups }) {
  const parentOptions =
    `<option value="">-- No parent --</option>` +
    parents.map((p) => `<option value="${p.id}">${p.title}</option>`).join("");

  els.parentSelect.innerHTML = parentOptions;
  els.editGroupParentSelect.innerHTML = parentOptions;

  const groupOptions =
    `<option value="">-- Select Group --</option>` +
    groups
      .map((g) => `<option value="${g.id}">${g.group_title}</option>`)
      .join("");

  els.groupSelect.innerHTML = groupOptions;
  els.editItemGroupSelect.innerHTML = groupOptions;
}

/* =====================================================
   RENDER MENU TREE
===================================================== */
function renderMenu(parents, groups, items) {
  if (!parents.length) {
    els.menuStructure.innerHTML = `<div class="text-center py-8 text-gray-500">No menu items yet.</div>`;
    return;
  }

  els.menuStructure.innerHTML = parents
    .sort((a, b) => a.position - b.position)
    .map((p) => renderParent(p, groups, items))
    .join("");
}

function renderParent(p, groups, items) {
  return `
    <div class="border rounded-lg p-4 bg-white">
      <div class="flex justify-between mb-2">
        <div class="flex items-center gap-3">
          <b>${p.title}</b>
          <button id="parent-toggle-${p.id}" onclick="toggleParent(${p.id})"
            class="text-gray-400 hover:text-gray-600 p-1">
            <i class="fas fa-chevron-up"></i>
          </button>
        </div>
        <div>
          <button onclick="editParent(${
            p.id
          })" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
          <button onclick="deleteParent(${
            p.id
          })" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <div id="parent-children-${p.id}">
        ${groups
          .filter((g) => g.parent_id == p.id)
          .map((g) => renderGroup(g, items))
          .join("")}
      </div>
    </div>
  `;
}

function renderGroup(g, items) {
  return `
    <div class="ml-4 border-l pl-4 mt-2">
      <div class="flex justify-between">
        <div class="flex items-center gap-3">
          <span>${g.group_title}</span>
          <button id="group-toggle-${g.id}" onclick="toggleGroup(${g.id})"
            class="text-gray-400 hover:text-gray-600 p-1">
            <i class="fas fa-chevron-up"></i>
          </button>
        </div>
        <div>
          <button onclick="editGroup(${
            g.id
          })" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
          <button onclick="deleteGroup(${
            g.id
          })" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <div id="group-children-${g.id}">
        ${items
          .filter((i) => i.group_id == g.id)
          .map(
            (i) => `
          <div class="ml-4 flex justify-between text-sm bg-gray-50 p-2 rounded mt-1">
            ${i.item_title}
            <div>
              <button onclick="editItem(${i.id})" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
              <button onclick="deleteItem(${i.id})" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
            </div>
          </div>
        `,
          )
          .join("")}
      </div>
    </div>
  `;
}

/* =====================================================
   TOGGLES
===================================================== */
function toggleParent(id) {
  toggleBlock(`parent-children-${id}`, `parent-toggle-${id}`);
}

function toggleGroup(id) {
  toggleBlock(`group-children-${id}`, `group-toggle-${id}`);
}

function toggleBlock(contentId, toggleBtnId) {
  const block = $(contentId);
  const icon = $(`${toggleBtnId}`)?.querySelector("i");
  if (!block || !icon) return;

  block.classList.toggle("hidden");
  icon.classList.toggle("fa-chevron-up");
  icon.classList.toggle("fa-chevron-down");
}

/* =====================================================
   EDIT HANDLERS
===================================================== */
function editParent(id) {
  (async () => {
    const confirmed = await confirmEdit(
      "Edit parent?",
      "Open the editor to update this parent's title or position.",
    );
    if (!confirmed.isConfirmed) return;

    const p = _menuData.parents.find((x) => x.id == id);
    $("editParentId").value = p.id;
    $("editParentTitle").value = p.title;
    $("editParentPosition").value = p.position;
    openModal("editParentModal");
  })();
}

function editGroup(id) {
  (async () => {
    const confirmed = await confirmEdit(
      "Edit group?",
      "Open the editor to update the group's title, link, parent, or position.",
    );
    if (!confirmed.isConfirmed) return;

    const g = _menuData.groups.find((x) => x.id == id);
    $("editGroupId").value = g.id;
    $("editGroupTitle").value = g.group_title;
    $("editGroupUrl").value = g.link_url || "";
    els.editGroupParentSelect.value = g.parent_id || "";
    $("editGroupPosition").value = g.position;
    openModal("editGroupModal");
  })();
}

function editItem(id) {
  (async () => {
    const confirmed = await confirmEdit(
      "Edit item?",
      "Open the editor to update the item's title, link, group, or position.",
    );
    if (!confirmed.isConfirmed) return;

    const i = _menuData.items.find((x) => x.id == id);
    $("editItemId").value = i.id;
    $("editItemTitle").value = i.item_title;
    $("editItemUrl").value = i.link_url;
    els.editItemGroupSelect.value = i.group_id;
    $("editItemPosition").value = i.position;
    openModal("editItemModal");
  })();
}

/* Edit confirm (match users/products style) */
function confirmEdit(title, text) {
  return Swal.fire({
    icon: "question",
    title: title || "Edit item?",
    html: `
      <p class="text-gray-600 mt-2">
        ${text || "Open the editor to update this item."}
      </p>
    `,
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
  });
}

/* Centered success modal to match screenshot/style */
function showSuccessModal(title = "Success", text = "") {
  return Swal.fire({
    icon: "success",
    title,
    text: text || undefined,
    confirmButtonText: "OK",
    confirmButtonColor: "#6b46c1",
  });
}

/* =====================================================
   DELETE
===================================================== */
async function deleteEntity(type, id, label) {
  const ok = await confirmBox(
    `Delete ${label}?`,
    "This action cannot be undone",
  );
  if (!ok.isConfirmed) return;

  loading();
  const res = await api(`delete_${type}`, { id });
  Swal.close();
  if (res.ok) {
    await showSuccessModal("Deleted", `${label} removed successfully.`);
    loadData();
  } else {
    errorBox(res.msg);
  }
}

const deleteParent = (id) => deleteEntity("parent", id, "Parent");
const deleteGroup = (id) => deleteEntity("group", id, "Group");
const deleteItem = (id) => deleteEntity("item", id, "Item");

/* =====================================================
   FORMS
===================================================== */
function bindForm(form, action, closeId = null) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    loading();

    const data = Object.fromEntries(new FormData(form));
    const res = await api(action, data);
    Swal.close();
    if (res.ok) {
      // Choose message based on action type
      let title = "Success";
      let text = "";
      if (action.startsWith("add_")) {
        title = "Created";
        text = "New item created successfully.";
      } else if (action.startsWith("edit_")) {
        title = "Updated";
        text = "Changes saved successfully.";
      }

      await showSuccessModal(title, text);
      form.reset();
      if (closeId) closeModal(closeId);
      loadData();
    } else {
      errorBox(res.msg);
    }
  });
}

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  els = {
    parentSelect: $("parentSelect"),
    groupSelect: $("groupSelect"),
    addParentForm: $("addParentForm"),
    addGroupForm: $("addGroupForm"),
    addItemForm: $("addItemForm"),
    editParentForm: $("editParentForm"),
    editGroupForm: $("editGroupForm"),
    editItemForm: $("editItemForm"),
    editGroupParentSelect: $("editGroupParentSelect"),
    editItemGroupSelect: $("editItemGroupSelect"),
    parentCount: $("parentCount"),
    groupCount: $("groupCount"),
    itemCount: $("itemCount"),
    menuStructure: $("menuStructure"),
  };

  bindForm(els.addParentForm, "add_parent");
  bindForm(els.addGroupForm, "add_group");
  bindForm(els.addItemForm, "add_item");
  bindForm(els.editParentForm, "edit_parent", "editParentModal");
  bindForm(els.editGroupForm, "edit_group", "editGroupModal");
  bindForm(els.editItemForm, "edit_item", "editItemModal");

  loadData();
});
