// SweetAlert2 Helper Functions
const SwalHelper = {
    toast: (title, icon = 'success') => {
        Swal.fire({
            title,
            icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    },

    loading: (title = 'Processing...') => {
        Swal.fire({
            title,
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });
    },

    success: (title, text = '') => {
        return Swal.fire({
            icon: 'success',
            title,
            text,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        });
    },

    error: (text) => {
        return Swal.fire({
            icon: 'error',
            title: 'Error',
            text,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
    },

    confirmDelete: (title, text) => {
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
};

// API Helper
const CategoryAPI = {
    request: async (data) => {
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const response = await fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        try {
            return await response.json();
        } catch (error) {
            throw new Error('Failed to parse server response');
        }
    }
};

// Category Management Functions
const CategoryManager = {
    add: async (formData) => {
        SwalHelper.loading('Adding category...');

        try {
            const data = await CategoryAPI.request(formData);
            Swal.close();

            if (data.success) {
                await SwalHelper.success('Success!', data.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                SwalHelper.error(data.message);
            }
        } catch (error) {
            Swal.close();
            SwalHelper.error('Network error. Please try again.');
        }
    },

    update: async (formData) => {
        SwalHelper.loading('Updating category...');

        try {
            const data = await CategoryAPI.request(formData);
            Swal.close();

            if (data.success) {
                await SwalHelper.success('Success!', data.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                SwalHelper.error(data.message);
            }
        } catch (error) {
            Swal.close();
            SwalHelper.error('Network error. Please try again.');
        }
    },

    delete: async (categoryId, categoryName, productCount) => {
        if (productCount > 0) {
            Swal.fire({
                title: 'Cannot Delete Category',
                html: `Category "<strong>${categoryName}</strong>" has <strong>${productCount}</strong> product(s).<br><br>Please reassign or delete these products before deleting the category.`,
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
            });
            return;
        }

        const result = await SwalHelper.confirmDelete(
            'Delete Category?',
            `Are you sure you want to delete "<strong>${categoryName}</strong>"?`
        );

        if (!result.isConfirmed) return;

        SwalHelper.loading('Deleting category...');

        try {
            const data = await CategoryAPI.request({
                action: 'delete_category',
                category_id: categoryId
            });

            Swal.close();

            if (data.success) {
                await SwalHelper.success('Deleted!', data.message);
                // Remove row from table
                const row = document.getElementById(`category-row-${categoryId}`);
                if (row) row.remove();
                // Update stats
                setTimeout(() => window.location.reload(), 1500);
            } else {
                SwalHelper.error(data.message);
            }
        } catch (error) {
            Swal.close();
            SwalHelper.error('Network error. Please try again.');
        }
    }
};

// Modal Functions
const Modal = {
    openEdit: (categoryId, categoryName) => {
        document.getElementById('edit_category_id').value = categoryId;
        document.getElementById('edit_category_name').value = categoryName;
        document.getElementById('editModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('edit_category_name').focus(), 100);
    },

    closeEdit: () => {
        document.getElementById('editModal').classList.add('hidden');
    }
};

// Event Handlers
function editCategory(categoryId, categoryName) {
    Modal.openEdit(categoryId, categoryName);
}

function deleteCategory(categoryId, categoryName, productCount) {
    CategoryManager.delete(categoryId, categoryName, productCount);
}

function closeEditModal() {
    Modal.closeEdit();
}

// Form Submissions
document.getElementById('addCategoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    if (!formData.get('category_name').trim()) {
        SwalHelper.error('Category name is required');
        return;
    }

    await CategoryManager.add(Object.fromEntries(formData));
    form.reset();
});

document.getElementById('editCategoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    if (!formData.get('category_name').trim()) {
        SwalHelper.error('Category name is required');
        return;
    }

    await CategoryManager.update(Object.fromEntries(formData));
    Modal.closeEdit();
});

// Search Functionality
document.getElementById('searchCategory').addEventListener('input', function (e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#categoriesTableBody tr');

    rows.forEach(row => {
        const categoryName = row.querySelector('td:first-child').textContent.toLowerCase();
        row.style.display = categoryName.includes(searchTerm) ? '' : 'none';
    });
});

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('editModal')) {
        Modal.closeEdit();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        Modal.closeEdit();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Auto-focus category name field
    setTimeout(() => {
        const categoryInput = document.getElementById('category_name');
        if (categoryInput) categoryInput.focus();
    }, 500);
});