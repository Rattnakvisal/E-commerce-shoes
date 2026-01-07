// Modal Functions
function openAddModal() {
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-plus mr-2 text-indigo-600"></i> Add Slide';
  document.getElementById("slideId").value = "0";
  document.getElementById("oldImage").value = "";
  document.getElementById("modalTitleInput").value = "";
  document.getElementById("modalDescription").value = "";
  document.getElementById("modalLinkUrl").value = "";
  document.getElementById("modalButtonText").value = "";
  document.getElementById("modalDisplayOrder").value =
    "<?php echo $totalSlides + 1; ?>";
  const statusEl = document.getElementById("modalIsActive");
  if (statusEl) statusEl.value = "1";

  // Hide current image
  document.getElementById("currentImageContainer").classList.add("hidden");
  document.getElementById("newImageContainer").classList.add("hidden");

  openModal();
}

function openEditModal(
  id,
  title,
  description,
  linkUrl,
  buttonText,
  displayOrder,
  isActive,
  imageUrl
) {
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-edit mr-2 text-yellow-600"></i> Edit Slide';
  document.getElementById("slideId").value = id;
  document.getElementById("oldImage").value = imageUrl;
  document.getElementById("modalTitleInput").value = title;
  document.getElementById("modalDescription").value = description;
  document.getElementById("modalLinkUrl").value = linkUrl;
  document.getElementById("modalButtonText").value = buttonText;
  document.getElementById("modalDisplayOrder").value = displayOrder;
  const statusEl = document.getElementById("modalIsActive");
  if (statusEl) statusEl.value = isActive == 1 ? "1" : "0";

  if (imageUrl) {
    var currentImgEl = document.getElementById("currentImagePreview");
    var currentVideoEl = document.getElementById("currentVideoPreview");
    if (/\.(mp4)(\?.*)?$/i.test(imageUrl)) {
      // show video preview
      if (currentVideoEl) {
        currentVideoEl.src = imageUrl;
        currentVideoEl.classList.remove("hidden");
      }
      if (currentImgEl) {
        currentImgEl.classList.add("hidden");
        currentImgEl.removeAttribute("src");
      }
    } else {
      if (currentImgEl) {
        currentImgEl.src = imageUrl;
        currentImgEl.classList.remove("hidden");
      }
      if (currentVideoEl) {
        currentVideoEl.classList.add("hidden");
        currentVideoEl.src = "";
      }
    }
    document.getElementById("currentImageContainer").classList.remove("hidden");
  } else {
    document.getElementById("currentImageContainer").classList.add("hidden");
  }

  // Hide new image preview
  document.getElementById("newImageContainer").classList.add("hidden");

  openModal();
}

function openModal() {
  const slideModal = document.getElementById("slideModal");
  const overlay = document.getElementById("modalOverlay");
  if (slideModal) {
    slideModal.classList.remove("hidden");
    slideModal.classList.add("flex");
  }
  if (overlay) {
    overlay.classList.remove("hidden");
  }
  // prevent background scroll
  document.documentElement.style.overflow = "hidden";
}

function closeModal() {
  const slideModal = document.getElementById("slideModal");
  const overlay = document.getElementById("modalOverlay");
  if (slideModal) {
    slideModal.classList.add("hidden");
    slideModal.classList.remove("flex");
  }
  if (overlay) {
    overlay.classList.add("hidden");
  }
  document.documentElement.style.overflow = "";
  const img = document.getElementById("imageUpload");
  if (img) img.value = "";
}

// Close modal when clicking overlay
document.getElementById("modalOverlay").addEventListener("click", closeModal);

// Image preview
function previewNewImage(input) {
  const newImageContainer = document.getElementById("newImageContainer");
  const newImagePreview = document.getElementById("newImagePreview");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      newImagePreview.src = e.target.result;
      newImageContainer.classList.remove("hidden");
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    newImageContainer.classList.add("hidden");
  }
}

// Delete confirmation
function confirmDelete(title) {
  event.preventDefault();
  const url = event.currentTarget.href;

  Swal.fire({
    title: "Delete Slide?",
    html: `Are you sure you want to delete <strong>"${title}"</strong>?<br><br>This action cannot be undone.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Yes, delete it!",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });

  return false;
}

// Edit confirmation (open editor after confirmation)
function confirmEditSlide(
  id,
  title,
  description,
  linkUrl,
  buttonText,
  displayOrder,
  isActive,
  imageUrl
) {
  Swal.fire({
    title: "Edit slide?",
    html: `Open editor for <strong>"${title}"</strong>.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#6d28d9",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      openEditModal(
        id,
        title,
        description,
        linkUrl,
        buttonText,
        displayOrder,
        isActive,
        imageUrl
      );
    }
  });
}

// Form validation
document.getElementById("slideForm").addEventListener("submit", function (e) {
  const title = document.getElementById("modalTitleInput").value.trim();
  const image = document.getElementById("imageUpload").files.length;
  const slideId = document.getElementById("slideId").value;
  const isEditMode = slideId !== "0";

  if (!title) {
    e.preventDefault();
    Swal.fire({
      title: "Missing Title",
      text: "Please enter a slide title",
      icon: "warning",
      confirmButtonColor: "#3b82f6",
    });
    return false;
  }

  if (!isEditMode && !image) {
    e.preventDefault();
    Swal.fire({
      title: "Missing Image",
      text: "Please upload an image for the slide",
      icon: "warning",
      confirmButtonColor: "#3b82f6",
    });
    return false;
  }
});

// Small UI helpers used by refresh and other actions
function showLoading(message = "Loading...") {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      title: message,
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });
  } else {
    console.log("Loading:", message);
  }
}

function showError(message) {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: message,
      confirmButtonColor: "#3b82f6",
    });
  } else {
    console.error(message);
  }
}

function showSuccess(message) {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: "success",
      title: message,
      timer: 1200,
      showConfirmButton: false,
    });
  } else {
    console.log("Success:", message);
  }
}

function showToast(message, icon = "success") {
  if (typeof Swal !== "undefined") {
    const Toast = Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });
    Toast.fire({
      icon: icon,
      title: message,
    });
  } else {
    console.log(message);
  }
}
// Export alert helpers to global scope so server-side flash can call them
window.showLoading = showLoading;
window.showError = showError;
window.showSuccess = showSuccess;
window.showToast = showToast;
