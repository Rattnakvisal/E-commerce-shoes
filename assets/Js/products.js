/* =====================================================
   CONFIG
===================================================== */
const PRODUCTS_API_URL = "add-product.php";
const RELOAD_DELAY = 700;

/* =====================================================
   UTILITIES
===================================================== */
const delayReload = () => setTimeout(() => location.reload(), RELOAD_DELAY);

/* =====================================================
   SWEETALERT HELPERS (MATCH USERS)
===================================================== */
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
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
  });
}

function confirmEdit(title, text) {
  return Swal.fire({
    icon: "question",
    title,
    html: `<p class="text-gray-600 mt-2">${text}</p>`,
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
  });
}

function confirmDelete(title, text) {
  return Swal.fire({
    icon: "warning",
    title,
    html: `<p class="text-gray-600 mt-2">${text}</p>`,
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
  });
}

/* =====================================================
   DOM ELEMENTS
===================================================== */
const productModal = document.getElementById("productModal");
const modalTitle = document.getElementById("modalTitle");
const productForm = document.getElementById("productForm");
const formAction = document.getElementById("formAction");
const productId = document.getElementById("productId");

const productName = document.getElementById("productName");
const productDescription = document.getElementById("productDescription");
const productCategory = document.getElementById("productCategory");
const productPrice = document.getElementById("productPrice");
const productCost = document.getElementById("productCost");
const productStock = document.getElementById("productStock");
const productStatus = document.getElementById("productStatus");

const productImage = document.getElementById("productImage");
const imagePreview = document.getElementById("imagePreview");
const previewImage = document.getElementById("previewImage");

const submitBtn = document.getElementById("submitBtn");
const submitText = document.getElementById("submitText");

/* =====================================================
   MODAL HANDLERS
===================================================== */
function openModal() {
  productModal?.classList.remove("hidden");
  productModal?.classList.add("flex");
}

function closeModal() {
  productModal?.classList.add("hidden");
  productModal?.classList.remove("flex");
  resetForm();
}

function resetForm() {
  productForm?.reset();
  productId.value = "";
  formAction.value = "add";
  modalTitle.textContent = "Add Product";
  submitText.textContent = "Add Product";
  submitBtn.className =
    "px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition";
  imagePreview.classList.add("hidden");
}

/* =====================================================
   IMAGE PREVIEW
===================================================== */
productImage?.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return imagePreview.classList.add("hidden");

  const reader = new FileReader();
  reader.onload = (ev) => {
    previewImage.src = ev.target.result;
    imagePreview.classList.remove("hidden");
  };
  reader.readAsDataURL(file);
});

/* =====================================================
   EDIT PRODUCT
===================================================== */
async function editProduct(id) {
  const ok = await confirmEdit(
    "Edit product?",
    "You can update product details, pricing, stock, or image.",
  );
  if (!ok.isConfirmed) return;

  try {
    showLoading("Loading product...");

    const res = await fetch(`${PRODUCTS_API_URL}?action=get_one&id=${id}`);
    const data = await res.json();
    Swal.close();

    if (!data.success || !data.data) {
      return showError(data.message || "Failed to load product");
    }

    const p = data.data;

    productId.value = p.product_id ?? p.id ?? "";
    productName.value = p.name ?? "";
    productDescription.value = p.description ?? "";
    productCategory.value = p.category_id ?? "";
    productPrice.value = p.price ?? "";
    productCost.value = p.cost ?? "";
    productStock.value = p.stock ?? "";
    productStatus.value = p.status ?? "active";

    if (p.image_url) {
      previewImage.src = p.image_url;
      imagePreview.classList.remove("hidden");
    } else {
      imagePreview.classList.add("hidden");
    }

    formAction.value = "update";
    modalTitle.textContent = "Edit Product";
    submitText.textContent = "Update Product";
    submitBtn.className =
      "px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition";

    openModal();
  } catch {
    Swal.close();
    showError("Network error. Please try again.");
  }
}

/* =====================================================
   DELETE PRODUCT
===================================================== */
async function deleteProduct(id) {
  const row = document.querySelector(`tr[data-id="${id}"]`);
  const name =
    row?.querySelector("td:nth-child(2) .font-medium")?.textContent ??
    "this product";

  const ok = await confirmDelete(
    "Delete product?",
    `Are you sure you want to delete <b>${name}</b>? This action cannot be undone.`,
  );
  if (!ok.isConfirmed) return;

  try {
    showLoading("Deleting product...");

    const fd = new FormData();
    fd.append("action", "delete");
    fd.append("id", id);

    const r = await fetch(PRODUCTS_API_URL, {
      method: "POST",
      body: fd,
    });
    const data = await r.json();

    Swal.close();

    if (!data.success) {
      return showError(data.message || "Delete failed");
    }

    showSuccess("Product deleted successfully");
    delayReload();
  } catch {
    Swal.close();
    showError("Network error. Please try again.");
  }
}

/* =====================================================
   FORM SUBMIT (ADD / UPDATE)
===================================================== */
productForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  if (!productName.value.trim()) {
    return showError("Product name is required");
  }
  if (productPrice.value <= 0) {
    return showError("Price must be greater than 0");
  }
  if (productStock.value < 0) {
    return showError("Stock must be 0 or greater");
  }

  try {
    submitBtn.disabled = true;
    submitText.textContent =
      formAction.value === "add" ? "Adding..." : "Updating...";

    const fd = new FormData(productForm);
    fd.append("action", formAction.value === "add" ? "create" : "update");

    const res = await fetch(PRODUCTS_API_URL, {
      method: "POST",
      body: fd,
    });
    const data = await res.json();

    submitBtn.disabled = false;
    submitText.textContent =
      formAction.value === "add" ? "Add Product" : "Update Product";

    if (!data.success) {
      return showError(data.message || "Operation failed");
    }

    showSuccess(
      formAction.value === "add"
        ? "Product added successfully"
        : "Product updated successfully",
    );

    closeModal();
    delayReload();
  } catch {
    submitBtn.disabled = false;
    showError("Network error. Please try again.");
  }
});

/* =====================================================
   EVENT BINDINGS
===================================================== */
document.getElementById("openAddProduct")?.addEventListener("click", () => {
  resetForm();
  openModal();
});

document.getElementById("closeModal")?.addEventListener("click", closeModal);
document.getElementById("cancelBtn")?.addEventListener("click", closeModal);

productModal?.addEventListener("click", (e) => {
  if (e.target === productModal) closeModal();
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  editProduct,
  deleteProduct,
});
