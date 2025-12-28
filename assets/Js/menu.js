// Menu Management JavaScript
const API_URL = 'api.php';

// DOM Elements
const parentSelect = document.getElementById('parentSelect');
const groupSelect = document.getElementById('groupSelect');
const addParentForm = document.getElementById('addParentForm');
const addGroupForm = document.getElementById('addGroupForm');
const addItemForm = document.getElementById('addItemForm');
const parentCount = document.getElementById('parentCount');
const groupCount = document.getElementById('groupCount');
const itemCount = document.getElementById('itemCount');
const menuStructure = document.getElementById('menuStructure');
const editParentForm = document.getElementById('editParentForm');
const editGroupForm = document.getElementById('editGroupForm');
const editItemForm = document.getElementById('editItemForm');
const editGroupParentSelect = document.getElementById('editGroupParentSelect');
const editItemGroupSelect = document.getElementById('editItemGroupSelect');

/* =====================================================
   SWEETALERT2 HELPERS
===================================================== */
function showToast(title, icon = 'success') {
    Swal.fire({
        title: title,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function confirmDelete(title, text) {
    return Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true
    });
}

function showSuccess(title, text = '') {
    return Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6'
    });
}

function showError(text) {
    return Swal.fire({
        icon: 'error',
        title: 'Error',
        text: text || 'Something went wrong!',
        confirmButtonText: 'OK',
        confirmButtonColor: '#d33'
    });
}

function showLoading(title = 'Processing...') {
    Swal.fire({
        title: title,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

/* =====================================================
   MODAL HELPERS
===================================================== */
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/* =====================================================
   LOAD DATA
===================================================== */
async function loadData() {
    try {
        const res = await fetch(`${API_URL}?action=fetch_all`);
        const data = await res.json();

        if (!data.ok) {
            throw new Error(data.msg || 'Failed to load data');
        }

        window._menuData = data;

        // Update counts
        parentCount.textContent = data.parents.length;
        groupCount.textContent = data.groups.length;
        itemCount.textContent = data.items.length;

        // Populate parent select dropdowns
        parentSelect.innerHTML = '<option value="">-- No parent --</option>';
        editGroupParentSelect.innerHTML = '<option value="">-- No parent --</option>';
        data.parents.forEach(p => {
            const option = `<option value="${p.id}">${p.title}</option>`;
            parentSelect.innerHTML += option;
            editGroupParentSelect.innerHTML += option;
        });

        // Populate group select dropdowns
        groupSelect.innerHTML = '<option value="">-- Select Group --</option>';
        editItemGroupSelect.innerHTML = '<option value="">-- Select Group --</option>';
        data.groups.forEach(g => {
            const option = `<option value="${g.id}">${g.group_title}</option>`;
            groupSelect.innerHTML += option;
            editItemGroupSelect.innerHTML += option;
        });

        renderMenu(data.parents, data.groups, data.items);

    } catch (error) {
        console.error('Load data error:', error);
        menuStructure.innerHTML = `
                    <div class="text-center text-red-500 py-6">
                        Failed to load menu data: ${error.message}
                    </div>
                `;
    }
}

/* =====================================================
   RENDER MENU STRUCTURE
===================================================== */
function renderMenu(parents, groups, items) {
    if (parents.length === 0) {
        menuStructure.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>No menu items yet. Start by adding a parent menu.</p>
                    </div>
                `;
        return;
    }

    let html = '';

    parents.sort((a, b) => a.position - b.position).forEach(p => {
        html += `
                <div class="border border-gray-200 rounded-lg p-4 mb-4 bg-white">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <b class="text-lg text-gray-800">${p.title}</b>
                            <span class="ml-2 text-sm text-gray-500">Position: ${p.position}</span>
                        </div>
                        <div class="space-x-2">
                            <button onclick="editParent(${p.id})" class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-3 py-1 rounded transition text-sm">
                                <i class="fas fa-edit mr-1"></i>
                            </button>
                            <button onclick="deleteParent(${p.id})" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1 rounded transition text-sm">
                                <i class="fas fa-trash mr-1"></i>
                            </button>
                        </div>
                    </div>
                `;

        const parentGroups = groups.filter(g => g.parent_id == p.id).sort((a, b) => a.position - b.position);

        if (parentGroups.length > 0) {
            parentGroups.forEach(g => {
                html += `
                        <div class="ml-4 mt-3 pl-4 border-l-2 border-gray-300">
                            <div class="flex justify-between items-center mb-2">
                                <div>
                                    <span class="font-medium text-gray-700">${g.group_title}</span>
                                    <span class="ml-2 text-sm text-gray-500">Position: ${g.position}</span>
                                    ${g.link_url ? `<span class="ml-2 text-sm text-blue-500"><i class="fas fa-link mr-1"></i>${g.link_url}</span>` : ''}
                                </div>
                                <div class="space-x-2">
                                    <button onclick="editGroup(${g.id})" class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2 py-1 rounded text-xs transition">
                                        <i class="fas fa-edit mr-1"></i>
                                    </button>
                                    <button onclick="deleteGroup(${g.id})" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-xs transition">
                                        <i class="fas fa-trash mr-1"></i>
                                    </button>
                                </div>
                            </div>
                        `;

                const groupItems = items.filter(i => i.group_id == g.id).sort((a, b) => a.position - b.position);

                if (groupItems.length > 0) {
                    html += `<div class="ml-6 mt-2 space-y-2">`;
                    groupItems.forEach(it => {
                        html += `
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded border">
                                    <div>
                                        <span class="text-gray-700">${it.item_title}</span>
                                        <span class="ml-2 text-sm text-gray-500">Position: ${it.position}</span>
                                        <span class="ml-2 text-sm text-blue-500"><i class="fas fa-link mr-1"></i>${it.link_url}</span>
                                    </div>
                                    <div class="space-x-2">
                                        <button onclick="editItem(${it.id})" class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2 py-1 rounded text-xs transition">
                                            <i class="fas fa-edit mr-1"></i>
                                        </button>
                                        <button onclick="deleteItem(${it.id})" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-xs transition">
                                            <i class="fas fa-trash mr-1"></i>
                                        </button>
                                    </div>
                                </div>
                                `;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            });
        }
        html += `</div>`;
    });

    menuStructure.innerHTML = html;
}

/* =====================================================
   EDIT FUNCTIONS
===================================================== */
function editParent(id) {
    const p = window._menuData.parents.find(x => x.id == id);
    if (!p) return;
    document.getElementById('editParentId').value = p.id;
    document.getElementById('editParentTitle').value = p.title;
    document.getElementById('editParentPosition').value = p.position;
    openModal('editParentModal');
}

function editGroup(id) {
    const g = window._menuData.groups.find(x => x.id == id);
    if (!g) return;
    document.getElementById('editGroupId').value = g.id;
    document.getElementById('editGroupTitle').value = g.group_title;
    document.getElementById('editGroupUrl').value = g.link_url || '';
    document.getElementById('editGroupParentSelect').value = g.parent_id || '';
    document.getElementById('editGroupPosition').value = g.position;
    openModal('editGroupModal');
}

function editItem(id) {
    const i = window._menuData.items.find(x => x.id == id);
    if (!i) return;
    document.getElementById('editItemId').value = i.id;
    document.getElementById('editItemTitle').value = i.item_title;
    document.getElementById('editItemUrl').value = i.link_url;
    document.getElementById('editItemGroupSelect').value = i.group_id;
    document.getElementById('editItemPosition').value = i.position;
    openModal('editItemModal');
}

/* =====================================================
   DELETE FUNCTIONS
===================================================== */
async function deleteParent(id) {
    const parent = window._menuData.parents.find(x => x.id == id);
    if (!parent) return;

    const result = await confirmDelete(
        'Delete Parent Menu?',
        `Are you sure you want to delete "${parent.title}" and all its groups and items? This action cannot be undone.`
    );

    if (!result.isConfirmed) return;

    showLoading('Deleting parent menu...');

    try {
        const res = await fetch(`${API_URL}?action=delete_parent`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Deleted!', 'Parent menu has been deleted.');
            loadData();
        } else {
            await showError(data.msg || 'Failed to delete parent menu.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Delete parent error:', error);
    }
}

async function deleteGroup(id) {
    const group = window._menuData.groups.find(x => x.id == id);
    if (!group) return;

    const result = await confirmDelete(
        'Delete Menu Group?',
        `Are you sure you want to delete "${group.group_title}" and all its items?`
    );

    if (!result.isConfirmed) return;

    showLoading('Deleting group...');

    try {
        const res = await fetch(`${API_URL}?action=delete_group`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Deleted!', 'Menu group has been deleted.');
            loadData();
        } else {
            await showError(data.msg || 'Failed to delete group.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Delete group error:', error);
    }
}

async function deleteItem(id) {
    const item = window._menuData.items.find(x => x.id == id);
    if (!item) return;

    const result = await confirmDelete(
        'Delete Menu Item?',
        `Are you sure you want to delete "${item.item_title}"?`
    );

    if (!result.isConfirmed) return;

    showLoading('Deleting item...');

    try {
        const res = await fetch(`${API_URL}?action=delete_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Deleted!', 'Menu item has been deleted.');
            loadData();
        } else {
            await showError(data.msg || 'Failed to delete item.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Delete item error:', error);
    }
}

/* =====================================================
   FORM SUBMISSIONS
===================================================== */
addParentForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Adding parent menu...');

    const body = Object.fromEntries(new FormData(addParentForm));
    try {
        const res = await fetch(`${API_URL}?action=add_parent`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Success!', 'Parent menu added successfully.');
            loadData();
            addParentForm.reset();
        } else {
            await showError(data.msg || 'Failed to add parent menu.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Add parent error:', error);
    }
});

addGroupForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Adding menu group...');

    const body = Object.fromEntries(new FormData(addGroupForm));
    try {
        const res = await fetch(`${API_URL}?action=add_group`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Success!', 'Menu group added successfully.');
            loadData();
            addGroupForm.reset();
        } else {
            await showError(data.msg || 'Failed to add menu group.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Add group error:', error);
    }
});

addItemForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Adding menu item...');

    const body = Object.fromEntries(new FormData(addItemForm));
    try {
        const res = await fetch(`${API_URL}?action=add_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Success!', 'Menu item added successfully.');
            loadData();
            addItemForm.reset();
        } else {
            await showError(data.msg || 'Failed to add menu item.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Add item error:', error);
    }
});

// Edit form submissions
editParentForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Updating parent menu...');

    const body = Object.fromEntries(new FormData(editParentForm));
    try {
        const res = await fetch(`${API_URL}?action=edit_parent`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Updated!', 'Parent menu updated successfully.');
            closeModal('editParentModal');
            loadData();
        } else {
            await showError(data.msg || 'Failed to update parent menu.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Update parent error:', error);
    }
});

editGroupForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Updating menu group...');

    const body = Object.fromEntries(new FormData(editGroupForm));
    try {
        const res = await fetch(`${API_URL}?action=edit_group`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Updated!', 'Menu group updated successfully.');
            closeModal('editGroupModal');
            loadData();
        } else {
            await showError(data.msg || 'Failed to update menu group.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Update group error:', error);
    }
});

editItemForm.addEventListener('submit', async e => {
    e.preventDefault();

    showLoading('Updating menu item...');

    const body = Object.fromEntries(new FormData(editItemForm));
    try {
        const res = await fetch(`${API_URL}?action=edit_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        Swal.close();

        if (data.ok) {
            await showSuccess('Updated!', 'Menu item updated successfully.');
            closeModal('editItemModal');
            loadData();
        } else {
            await showError(data.msg || 'Failed to update menu item.');
        }
    } catch (error) {
        Swal.close();
        await showError('Network error. Please try again.');
        console.error('Update item error:', error);
    }
});

/* =====================================================
   UTILITY FUNCTIONS
===================================================== */
function refreshData() {
    showLoading('Refreshing data...');
    setTimeout(() => {
        localStorage.setItem('menu_refreshed', '1');
        window.location.reload();
    }, 150);
}

/* =====================================================
   INITIALIZATION
===================================================== */
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('menu_refreshed')) {
        localStorage.removeItem('menu_refreshed');
        setTimeout(() => showToast('Data refreshed!', 'success'), 300);
    }
});
