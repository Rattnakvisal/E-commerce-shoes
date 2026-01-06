// SweetAlert2 helpers
function showToast(title, icon = "success") {
  Swal.fire({
    title: title,
    icon: icon,
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });
}

function showLoading(title = "Processing...") {
  Swal.fire({
    title: title,
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => Swal.showLoading(),
  });
}

function showSuccess(title, text = "") {
  return Swal.fire({
    icon: "success",
    title: title,
    text: text,
    confirmButtonText: "OK",
    confirmButtonColor: "#3085d6",
  });
}

function showError(text) {
  return Swal.fire({
    icon: "error",
    title: "Error",
    text: text,
    confirmButtonText: "OK",
    confirmButtonColor: "#d33",
  });
}

function confirmDelete(title, text) {
  return Swal.fire({
    title: title || "Are you sure?",
    text: text || "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete it!",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    reverseButtons: true,
  });
}

function confirmEdit(title, text) {
  return Swal.fire({
    title: title || "Edit product?",
    text: text || "Open editor for this product.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
    reverseButtons: false,
  });
}

// DOM Elements
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

// Modal Functions
function openModal() {
  if (!productModal) return;
  productModal.classList.remove("hidden");
  productModal.classList.add("flex");
}

function closeModal() {
  if (productModal) {
    productModal.classList.add("hidden");
    productModal.classList.remove("flex");
  }
  resetForm();
}

function resetForm() {
  if (productForm) productForm.reset();
  if (productId) productId.value = "";
  if (formAction) formAction.value = "add";
  if (modalTitle) modalTitle.textContent = "Add Product";
  if (submitText) submitText.textContent = "Add Product";
  if (submitBtn)
    submitBtn.className =
      "px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition";
  if (imagePreview && imagePreview.classList)
    imagePreview.classList.add("hidden");
}

// Image Preview
if (productImage) {
  productImage.addEventListener("change", function (e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        if (previewImage) previewImage.src = e.target.result;
        if (imagePreview && imagePreview.classList)
          imagePreview.classList.remove("hidden");
      };
      reader.readAsDataURL(file);
    } else {
      if (imagePreview && imagePreview.classList)
        imagePreview.classList.add("hidden");
    }
  });
}

// Edit Product
async function editProduct(id) {
  const confirmed = await confirmEdit(
    "Edit product?",
    "Open editor for this product."
  );
  if (!confirmed.isConfirmed) return;

  try {
    const response = await fetch("add-product.php?action=get_one&id=" + id);
    const data = await response.json();

    Swal.close();

    if (data.success) {
      const product = data.data;
      if (productId) productId.value = product.product_id ?? product.id ?? "";
      if (productName) productName.value = product.name ?? product.NAME ?? "";
      if (productDescription)
        productDescription.value =
          product.description ?? product.DESCRIPTION ?? "";
      if (productCategory)
        productCategory.value =
          product.category_id ?? product.category_id ?? "";
      if (productPrice)
        productPrice.value = product.price ?? product.PRICE ?? "";
      if (productCost) productCost.value = product.cost ?? product.COST ?? "";
      if (productStock)
        productStock.value = product.stock ?? product.STOCK ?? "";
      if (productStatus)
        productStatus.value = product.status ?? product.STATUS ?? "";

      // Handle image preview
      if (product.image_url) {
        if (previewImage) previewImage.src = product.image_url;
        if (imagePreview && imagePreview.classList)
          imagePreview.classList.remove("hidden");
      } else {
        if (imagePreview && imagePreview.classList)
          imagePreview.classList.add("hidden");
      }

      if (formAction) formAction.value = "update";
      if (modalTitle) modalTitle.textContent = "Edit Product";
      if (submitText) submitText.textContent = "Update Product";
      if (submitBtn)
        submitBtn.className =
          "px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition";

      openModal();
    } else {
      showError(data.message || "Failed to load product");
    }
  } catch (error) {
    Swal.close();
    showError("Network error. Please try again.");
    console.error("Edit error:", error);
  }
}

// Delete Product
async function deleteProduct(id) {
  const productRow = document.querySelector(`tr[data-id="${id}"]`);
  const productName = productRow
    ? productRow.querySelector("td:nth-child(2) .font-medium").textContent
    : "this product";

  const result = await confirmDelete(
    "Delete Product?",
    `Are you sure you want to delete "${productName}"? This action cannot be undone.`
  );

  if (!result.isConfirmed) return;

  showLoading("Deleting product...");

  try {
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    const response = await fetch("add-product.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    Swal.close();

    if (data.success) {
      await showSuccess("Deleted!", "Product has been deleted successfully.");
      // Remove row from table
      if (productRow) {
        productRow.remove();
      }
      // Refresh page to update stats
      window.location.reload();
    } else {
      showError(data.message || "Failed to delete product");
    }
  } catch (error) {
    Swal.close();
    showError("Network error. Please try again.");
    console.error("Delete error:", error);
  }
}

// Form Submission
if (productForm) {
  productForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    if (!productName || !productName.value || !productName.value.trim()) {
      showError("Product name is required");
      return;
    }

    if (
      !productPrice ||
      !productPrice.value ||
      parseFloat(productPrice.value) <= 0
    ) {
      showError("Price must be greater than 0");
      return;
    }

    if (
      !productStock ||
      !productStock.value ||
      parseInt(productStock.value) < 0
    ) {
      showError("Stock must be 0 or greater");
      return;
    }

    // Show loading state
    if (submitBtn) submitBtn.disabled = true;
    if (submitText)
      submitText.textContent =
        formAction && formAction.value === "add" ? "Adding..." : "Updating...";

    try {
      const fd = new FormData();
      const act =
        formAction.value === "add"
          ? "create"
          : formAction.value === "update"
          ? "update"
          : formAction.value;
      fd.append("action", act);
      if (
        formAction &&
        formAction.value === "update" &&
        productId &&
        productId.value
      )
        fd.append("product_id", productId.value);
      fd.append("NAME", productName.value);
      fd.append("DESCRIPTION", productDescription.value);
      fd.append("category_id", productCategory.value);
      fd.append("price", productPrice.value);
      fd.append("cost", productCost.value);
      fd.append("stock", productStock.value);
      fd.append("STATUS", productStatus.value);
      // include image file if selected
      if (productImage && productImage.files && productImage.files[0]) {
        fd.append("image", productImage.files[0]);
      }

      const response = await fetch("add-product.php", {
        method: "POST",
        body: fd,
      });

      const data = await response.json();

      // Reset button state
      if (submitBtn) submitBtn.disabled = false;
      if (submitText)
        submitText.textContent =
          formAction && formAction.value === "add"
            ? "Add Product"
            : "Update Product";

      if (data.success) {
        await showSuccess(
          formAction.value === "add" ? "Product Added!" : "Product Updated!",
          data.message
        );

        closeModal();
        // Refresh page
        window.location.reload();
      } else {
        showError(data.message || "Operation failed");
      }
    } catch (error) {
      // Reset button state
      if (submitBtn) submitBtn.disabled = false;
      if (submitText)
        submitText.textContent =
          formAction && formAction.value === "add"
            ? "Add Product"
            : "Update Product";

      showError("Network error. Please try again.");
      console.error("Form submission error:", error);
    }
  });
}

// Event Listeners
const openAddBtn = document.getElementById("openAddProduct");
const closeModalBtn = document.getElementById("closeModal");
const cancelBtnEl = document.getElementById("cancelBtn");

if (openAddBtn) {
  openAddBtn.addEventListener("click", () => {
    resetForm();
    openModal();
  });
}

if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
if (cancelBtnEl) cancelBtnEl.addEventListener("click", closeModal);

// Close modal when clicking outside
if (productModal) {
  productModal.addEventListener("click", (e) => {
    if (e.target === productModal) {
      closeModal();
    }
  });
}
