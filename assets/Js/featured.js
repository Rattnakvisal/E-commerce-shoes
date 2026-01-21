/* =====================================================
   SWEETALERT HELPERS (MATCH PRODUCTS & USERS)
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
    confirmButtonColor: "#dc2626",
  });
}

/* Confirm edit (Products style) */
function confirmEditFeatured(cb) {
  return Swal.fire({
    icon: "question",
    title: "Edit featured item?",
    html: `
      <p class="text-gray-600 mt-2">
        Open the editor to update the featured product, image, or position.
      </p>
    `,
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
  }).then((res) => {
    if (res.isConfirmed && typeof cb === "function") cb();
  });
}

/* Confirm delete */
function confirmDeleteFeatured(cb) {
  return Swal.fire({
    title: "Delete featured item?",
    text: "This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
  }).then((res) => {
    if (res.isConfirmed && typeof cb === "function") cb();
  });
}

/* =====================================================
   MODAL HELPERS
===================================================== */
function openAddModal() {
  document.getElementById("featuredForm").reset();
  document.getElementById("featuredId").value = "";
  document.getElementById("oldImage").value = "";
  document.getElementById("modalTitle").textContent = "Add Featured Item";
  document.getElementById("newImagePreview").classList.add("hidden");
  document.getElementById("imagePreviewContainer").innerHTML = "";
  document.getElementById("isActive").checked = true;

  document.getElementById("modalOverlay").classList.remove("hidden");
  document.getElementById("featuredModal").classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function openEditModal(id, pid, title, position, active, image) {
  document.getElementById("featuredId").value = id;
  document.getElementById("productId").value = pid;
  document.getElementById("titleInput").value = title;
  document.getElementById("positionInput").value = position;
  document.getElementById("oldImage").value = image || "";
  document.getElementById("isActive").checked = !!active;
  document.getElementById("modalTitle").textContent = "Edit Featured Item";

  document.getElementById("newImagePreview").classList.add("hidden");

  const preview = document.getElementById("imagePreviewContainer");
  preview.innerHTML = image
    ? `
      <div class="relative">
        <img src="${image}" class="w-full h-64 object-cover rounded-lg border">
        <span class="absolute top-2 right-2 bg-white/80 px-2 py-1 rounded text-xs">
          Current
        </span>
      </div>`
    : '<p class="text-sm text-gray-500">No image currently set</p>';

  document.getElementById("modalOverlay").classList.remove("hidden");
  document.getElementById("featuredModal").classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("modalOverlay").classList.add("hidden");
  document.getElementById("featuredModal").classList.add("hidden");
  document.body.style.overflow = "auto";
}

/* =====================================================
   IMAGE PREVIEW
===================================================== */
function previewImage(input) {
  const preview = document.getElementById("newImagePreview");
  const img = document.getElementById("newImagePreviewImg");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      img.src = e.target.result;
      preview.classList.remove("hidden");
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeNewImage() {
  document.getElementById("imageInput").value = "";
  document.getElementById("newImagePreview").classList.add("hidden");
}

/* =====================================================
   ACTION CONFIRMATIONS
===================================================== */
function confirmEdit(id, pid, title, position, active, image) {
  confirmEditFeatured(() =>
    openEditModal(id, pid, title, position, active, image),
  );
}

function confirmDelete(url) {
  confirmDeleteFeatured(() => {
    showLoading("Deleting...");
    window.location = url;
  });
}

/* =====================================================
   GLOBAL EVENTS
===================================================== */
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
});

document.getElementById("featuredModal")?.addEventListener("click", (e) => {
  if (e.target === e.currentTarget) closeModal();
});
