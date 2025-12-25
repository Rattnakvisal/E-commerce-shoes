// SweetAlert2 helpers
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

function showLoading(title = 'Processing...') {
    Swal.fire({
        title: title,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => Swal.showLoading()
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
        text: text,
        confirmButtonText: 'OK',
        confirmButtonColor: '#d33'
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

// DOM Elements
const productModal = document.getElementById('productModal');
const modalTitle = document.getElementById('modalTitle');
const productForm = document.getElementById('productForm');
const formAction = document.getElementById('formAction');
const productId = document.getElementById('productId');
const productName = document.getElementById('productName');
const productDescription = document.getElementById('productDescription');
const productCategory = document.getElementById('productCategory');
const productPrice = document.getElementById('productPrice');
const productCost = document.getElementById('productCost');
const productStock = document.getElementById('productStock');
const productStatus = document.getElementById('productStatus');
const productImage = document.getElementById('productImage');
const imagePreview = document.getElementById('imagePreview');
const previewImage = document.getElementById('previewImage');
const submitBtn = document.getElementById('submitBtn');
const submitText = document.getElementById('submitText');
const loadingSpinner = document.getElementById('loadingSpinner');

// Modal Functions
function openModal() {
    productModal.classList.remove('hidden');
    productModal.classList.add('flex');
}

function closeModal() {
    productModal.classList.add('hidden');
    productModal.classList.remove('flex');
    resetForm();
}

function resetForm() {
    productForm.reset();
    productId.value = '';
    formAction.value = 'add';
    modalTitle.textContent = 'Add Product';
    submitText.textContent = 'Add Product';
    submitBtn.className = 'px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition';
    imagePreview.classList.add('hidden');
}

// Image Preview
productImage.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImage.src = e.target.result;
            imagePreview.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    } else {
        imagePreview.classList.add('hidden');
    }
});

// Edit Product
async function editProduct(id) {
    showLoading('Loading product details...');

    try {
        const response = await fetch('add-product.php?action=get_one&id=' + id);
        const data = await response.json();

        Swal.close();

        if (data.success) {
            const product = data.data;
            // tolerate different column name casings
            productId.value = product.product_id ?? product.product_id ?? product.id ?? '';
            productName.value = product.name ?? product.NAME ?? '';
            productDescription.value = product.description ?? product.DESCRIPTION ?? '';
            productCategory.value = product.category_id ?? product.category_id ?? '';
            productPrice.value = product.price ?? product.PRICE ?? '';
            productCost.value = product.cost ?? product.COST ?? '';
            productStock.value = product.stock ?? product.STOCK ?? '';
            productStatus.value = product.status ?? product.STATUS ?? '';

            // Handle image preview
            if (product.image_url) {
                previewImage.src = product.image_url;
                imagePreview.classList.remove('hidden');
            } else {
                imagePreview.classList.add('hidden');
            }

            formAction.value = 'update';
            modalTitle.textContent = 'Edit Product';
            submitText.textContent = 'Update Product';
            submitBtn.className = 'px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition';

            openModal();
        } else {
            showError(data.message || 'Failed to load product');
        }
    } catch (error) {
        Swal.close();
        showError('Network error. Please try again.');
        console.error('Edit error:', error);
    }
}

/* =====================================================
   REFRESH FUNCTION
===================================================== */
function refreshData() {
    showLoading('Refreshing data...');
    try {
        // mark for post-reload success message
        localStorage.setItem('products_refreshed', '1');
        setTimeout(() => {
            window.location.reload();
        }, 150);
    } catch (error) {
        Swal.close();
        showError('Failed to refresh data');
        console.error('Refresh error:', error);
    }
}

// After reload, show a short success modal if refresh was requested
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (localStorage.getItem('products_refreshed')) {
            localStorage.removeItem('products_refreshed');
            showToast('Data refreshed!', 'success');
        }
    } catch (e) {
        // ignore
    }
});

// Delete Product
async function deleteProduct(id) {
    const productRow = document.querySelector(`tr[data-id="${id}"]`);
    const productName = productRow ? productRow.querySelector('td:nth-child(2) .font-medium').textContent : 'this product';

    const result = await confirmDelete(
        'Delete Product?',
        `Are you sure you want to delete "${productName}"? This action cannot be undone.`
    );

    if (!result.isConfirmed) return;

    showLoading('Deleting product...');

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const response = await fetch('add-product.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            await showSuccess('Deleted!', 'Product has been deleted successfully.');
            // Remove row from table
            if (productRow) {
                productRow.remove();
            }
            // Refresh page to update stats
            window.location.reload();
        } else {
            showError(data.message || 'Failed to delete product');
        }
    } catch (error) {
        Swal.close();
        showError('Network error. Please try again.');
        console.error('Delete error:', error);
    }
}

// Form Submission
productForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Validate required fields
    if (!productName.value.trim()) {
        showError('Product name is required');
        return;
    }

    if (!productPrice.value || parseFloat(productPrice.value) <= 0) {
        showError('Price must be greater than 0');
        return;
    }

    if (!productStock.value || parseInt(productStock.value) < 0) {
        showError('Stock must be 0 or greater');
        return;
    }

    // Show loading state
    submitBtn.disabled = true;
    submitText.textContent = formAction.value === 'add' ? 'Adding...' : 'Updating...';
    loadingSpinner.classList.remove('hidden');

    try {
        // Build FormData using field names expected by add-product.php
        const fd = new FormData();
        // map action value: frontend uses 'add'/'update', backend expects 'create'/'update'
        const act = formAction.value === 'add' ? 'create' : (formAction.value === 'update' ? 'update' : formAction.value);
        fd.append('action', act);
        if (formAction.value === 'update' && productId.value) fd.append('product_id', productId.value);
        // server expects upper-case NAME/DESCRIPTION/STATUS but also handles lower-case for other fields
        fd.append('NAME', productName.value);
        fd.append('DESCRIPTION', productDescription.value);
        fd.append('category_id', productCategory.value);
        fd.append('price', productPrice.value);
        fd.append('cost', productCost.value);
        fd.append('stock', productStock.value);
        fd.append('STATUS', productStatus.value);
        // include image file if selected
        if (productImage.files && productImage.files[0]) {
            fd.append('image', productImage.files[0]);
        }

        const response = await fetch('add-product.php', {
            method: 'POST',
            body: fd
        });

        const data = await response.json();

        // Reset button state
        submitBtn.disabled = false;
        submitText.textContent = formAction.value === 'add' ? 'Add Product' : 'Update Product';
        loadingSpinner.classList.add('hidden');

        if (data.success) {
            await showSuccess(
                formAction.value === 'add' ? 'Product Added!' : 'Product Updated!',
                data.message
            );

            closeModal();
            // Refresh page
            window.location.reload();
        } else {
            showError(data.message || 'Operation failed');
        }
    } catch (error) {
        // Reset button state
        submitBtn.disabled = false;
        submitText.textContent = formAction.value === 'add' ? 'Add Product' : 'Update Product';
        loadingSpinner.classList.add('hidden');

        showError('Network error. Please try again.');
        console.error('Form submission error:', error);
    }
});

// Event Listeners
document.getElementById('openAddProduct').addEventListener('click', () => {
    resetForm();
    openModal();
});

document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('cancelBtn').addEventListener('click', closeModal);

// Close modal when clicking outside
productModal.addEventListener('click', (e) => {
    if (e.target === productModal) {
        closeModal();
    }
});