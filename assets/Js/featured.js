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

  // Clear new image preview
  document.getElementById("newImagePreview").classList.add("hidden");

  // Update image preview
  const previewContainer = document.getElementById("imagePreviewContainer");
  if (image) {
    previewContainer.innerHTML = `
                    <div class="relative">
                        <img src="${image}" alt="Current featured image" 
                             class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                        <div class="absolute top-2 right-2 bg-white/80 rounded-full p-2">
                            <span class="text-xs font-medium text-gray-700">Current</span>
                        </div>
                    </div>
                `;
  } else {
    previewContainer.innerHTML =
      '<p class="text-sm text-gray-500">No image currently set</p>';
  }

  document.getElementById("modalOverlay").classList.remove("hidden");
  document.getElementById("featuredModal").classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("modalOverlay").classList.add("hidden");
  document.getElementById("featuredModal").classList.add("hidden");
  document.body.style.overflow = "auto";
}

function previewImage(input) {
  const preview = document.getElementById("newImagePreview");
  const previewImg = document.getElementById("newImagePreviewImg");

  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = function (e) {
      previewImg.src = e.target.result;
      preview.classList.remove("hidden");
    };

    reader.readAsDataURL(input.files[0]);
  }
}

function removeNewImage() {
  document.getElementById("imageInput").value = "";
  document.getElementById("newImagePreview").classList.add("hidden");
}

function confirmEdit(id, pid, title, position, active, image) {
  if (typeof Swal === "undefined") {
    // fallback to direct open
    openEditModal(id, pid, title, position, active, image);
    return;
  }

  Swal.fire({
    title: "Edit featured item?",
    text: "Open editor for this featured item.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      openEditModal(id, pid, title, position, active, image);
    }
  });
}

function confirmDelete(url) {
  if (typeof Swal === "undefined") {
    if (
      confirm(
        "Are you sure you want to delete this featured item? This action cannot be undone."
      )
    ) {
      window.location = url;
    }
    return;
  }

  Swal.fire({
    title: "Delete featured item?",
    text: "This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    confirmButtonColor: "#d33",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location = url;
    }
  });
}

// Close modal on ESC key
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closeModal();
  }
});

// Close modal when clicking outside
document
  .getElementById("featuredModal")
  ?.addEventListener("click", function (e) {
    if (e.target === this) {
      closeModal();
    }
  });
