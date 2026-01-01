/* =====================================================
    CONFIG
===================================================== */
const API_URL = "api.php";

/* =====================================================
   DOM HELPERS
===================================================== */
const $ = (id) => document.getElementById(id);

/* =====================================================
   DOM ELEMENTS
===================================================== */
const els = {
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

/* =====================================================
   SWEETALERT HELPERS
===================================================== */
const toast = (title, icon = "success") =>
  Swal.fire({
    title,
    icon,
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
  });

const confirmBox = (title, text) =>
  Swal.fire({
    title,
    text,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete it!",
  });

const loading = (title = "Processing...") =>
  Swal.fire({
    title,
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: Swal.showLoading,
  });

const errorBox = (msg) =>
  Swal.fire({ icon: "error", title: "Error", text: msg });

/* =====================================================
   MODAL HELPERS
===================================================== */
const openModal = (id) => $(id)?.classList.replace("hidden", "flex");
const closeModal = (id) => $(id)?.classList.replace("flex", "hidden");

/* =====================================================
   API HELPER
===================================================== */
async function api(action, payload = {}) {
  const res = await fetch(`${API_URL}?action=${action}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  return res.json();
}

/* =====================================================
   LOAD MENU DATA
===================================================== */
async function loadData() {
  try {
    const res = await fetch(`${API_URL}?action=fetch_all`);
    const data = await res.json();

    if (!data.ok) throw new Error(data.msg || "Load failed");

    window._menuData = data;

    els.parentCount.textContent = data.parents.length;
    els.groupCount.textContent = data.groups.length;
    els.itemCount.textContent = data.items.length;

    populateSelects(data);
    renderMenu(data.parents, data.groups, data.items);
  } catch (err) {
    console.error(err);
    els.menuStructure.innerHTML = `
            <div class="text-center text-red-500 py-6">
                Failed to load menu data
            </div>
        `;
  }
}

/* =====================================================
   POPULATE SELECTS
===================================================== */
function populateSelects({ parents, groups }) {
  const parentOptions = parents
    .map((p) => `<option value="${p.id}">${p.title}</option>`)
    .join("");

  els.parentSelect.innerHTML =
    els.editGroupParentSelect.innerHTML = `<option value="">-- No parent --</option>${parentOptions}`;

  const groupOptions = groups
    .map((g) => `<option value="${g.id}">${g.group_title}</option>`)
    .join("");

  els.groupSelect.innerHTML =
    els.editItemGroupSelect.innerHTML = `<option value="">-- Select Group --</option>${groupOptions}`;
}

/* =====================================================
   RENDER MENU TREE
===================================================== */
function renderMenu(parents, groups, items) {
  if (!parents.length) {
    els.menuStructure.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                No menu items yet.
            </div>`;
    return;
  }

  els.menuStructure.innerHTML = parents
    .sort((a, b) => a.position - b.position)
    .map((p) => renderParent(p, groups, items))
    .join("");
}

function renderParent(p, groups, items) {
  const childGroups = groups
    .filter((g) => g.parent_id == p.id)
    .sort((a, b) => a.position - b.position)
    .map((g) => renderGroup(g, items))
    .join("");

  return `
        <div class="border rounded-lg p-4 mb-4 bg-white">
            <div class="flex justify-between mb-3">
                <b>${p.title}</b>
                <div>
                    <button onclick="editParent(${p.id})" class="text-blue-600 hover:text-blue-900 p-2 rounded hover:bg-blue-50 transition duration-150"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteParent(${p.id})" class="text-red-600 p-2 hover:text-red-900 transition"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            ${childGroups}
        </div>
    `;
}

function renderGroup(g, items) {
  const childItems = items
    .filter((i) => i.group_id == g.id)
    .sort((a, b) => a.position - b.position)
    .map(
      (i) => `
            <div class="ml-6 flex justify-between bg-gray-50 p-2 rounded">
                <span>${i.item_title}</span>
                <div>
                    <button onclick="editGroup(${g.id})" class="text-blue-600 hover:text-blue-900 p-2 rounded hover:bg-blue-50 transition duration-150"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteGroup(${g.id})" class="text-red-600 p-2 hover:text-red-900 transition"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `
    )
    .join("");

  return `
        <div class="ml-4 border-l pl-4 mt-3">
            <div class="flex justify-between mb-2">
                <span>${g.group_title}</span>
                <div>
                    <button onclick="editGroup(${g.id})" class="text-blue-600 hover:text-blue-900 p-2 rounded hover:bg-blue-50 transition duration-150"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteGroup(${g.id})" class="text-red-600 p-2 hover:text-red-900 transition"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            ${childItems}
        </div>
    `;
}

/* =====================================================
   EDIT HANDLERS
===================================================== */
function editParent(id) {
  const p = _menuData.parents.find((x) => x.id == id);
  if (!p) return;
  $("editParentId").value = p.id;
  $("editParentTitle").value = p.title;
  $("editParentPosition").value = p.position;
  openModal("editParentModal");
}

function editGroup(id) {
  const g = _menuData.groups.find((x) => x.id == id);
  if (!g) return;
  $("editGroupId").value = g.id;
  $("editGroupTitle").value = g.group_title;
  $("editGroupUrl").value = g.link_url || "";
  els.editGroupParentSelect.value = g.parent_id || "";
  $("editGroupPosition").value = g.position;
  openModal("editGroupModal");
}

function editItem(id) {
  const i = _menuData.items.find((x) => x.id == id);
  if (!i) return;
  $("editItemId").value = i.id;
  $("editItemTitle").value = i.item_title;
  $("editItemUrl").value = i.link_url;
  els.editItemGroupSelect.value = i.group_id;
  $("editItemPosition").value = i.position;
  openModal("editItemModal");
}

/* =====================================================
   DELETE HANDLERS
===================================================== */
async function deleteEntity(type, id, label) {
  const ok = await confirmBox(
    `Delete ${label}?`,
    "This action cannot be undone"
  );
  if (!ok.isConfirmed) return;

  loading();

  const res = await api(`delete_${type}`, { id });
  Swal.close();

  res.ok ? (toast("Deleted"), loadData()) : errorBox(res.msg);
}

const deleteParent = (id) => deleteEntity("parent", id, "Parent");
const deleteGroup = (id) => deleteEntity("group", id, "Group");
const deleteItem = (id) => deleteEntity("item", id, "Item");

/* =====================================================
   FORM HANDLERS
===================================================== */
function bindForm(form, action, closeId = null) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    loading();

    const data = Object.fromEntries(new FormData(form));
    const res = await api(action, data);

    Swal.close();

    if (res.ok) {
      toast("Success");
      form.reset();
      if (closeId) closeModal(closeId);
      loadData();
    } else {
      errorBox(res.msg);
    }
  });
}

bindForm(els.addParentForm, "add_parent");
bindForm(els.addGroupForm, "add_group");
bindForm(els.addItemForm, "add_item");

bindForm(els.editParentForm, "edit_parent", "editParentModal");
bindForm(els.editGroupForm, "edit_group", "editGroupModal");
bindForm(els.editItemForm, "edit_item", "editItemModal");
