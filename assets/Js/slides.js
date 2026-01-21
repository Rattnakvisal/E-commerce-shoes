/* =====================================================
   CONFIG
===================================================== */
const RELOAD_DELAY = 700;

/* =====================================================
   SWEETALERT HELPERS (GLOBAL — NO OK)
===================================================== */
const SwalHelper = {
  loading(title = "Processing...") {
    Swal.fire({
      title,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading(),
    });
  },

  success(title, text = "") {
    return Swal.fire({
      icon: "success",
      title,
      text: text || undefined,
      showConfirmButton: false,
      timer: 1200,
      timerProgressBar: true,
    });
  },

  error(text) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text,
      showConfirmButton: false,
      timer: 2200,
      timerProgressBar: true,
    });
  },

  confirmEdit(title, text) {
    return Swal.fire({
      icon: "question",
      title,
      html: `<p class="text-gray-600 mt-2">${text}</p>`,
      showCancelButton: true,
      confirmButtonText: "Edit",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#6d28d9",
      cancelButtonColor: "#6b7280",
    });
  },

  confirmDelete(title, text) {
    return Swal.fire({
      icon: "warning",
      title,
      html: `<p class="text-gray-600 mt-2">${text}</p>`,
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#ef4444",
      cancelButtonColor: "#6b7280",
    });
  },
};

/* =====================================================
   MODAL HELPERS
===================================================== */
function openModal() {
  document.getElementById("slideModal")?.classList.remove("hidden");
  document.getElementById("slideModal")?.classList.add("flex");
  document.getElementById("modalOverlay")?.classList.remove("hidden");
  document.documentElement.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("slideModal")?.classList.add("hidden");
  document.getElementById("slideModal")?.classList.remove("flex");
  document.getElementById("modalOverlay")?.classList.add("hidden");
  document.documentElement.style.overflow = "";
  document.getElementById("imageUpload")?.value = "";
}

/* =====================================================
   OPEN ADD SLIDE
===================================================== */
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
    document.getElementById("modalDisplayOrder").value || 1;
  document.getElementById("modalIsActive").value = "1";

  document.getElementById("currentImageContainer").classList.add("hidden");
  document.getElementById("newImageContainer").classList.add("hidden");

  openModal();
}

/* =====================================================
   OPEN EDIT SLIDE
===================================================== */
function openEditModal(
  id,
  title,
  description,
  linkUrl,
  buttonText,
  displayOrder,
  isActive,
  imageUrl,
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
  document.getElementById("modalIsActive").value = isActive == 1 ? "1" : "0";

  const img = document.getElementById("currentImagePreview");
  const video = document.getElementById("currentVideoPreview");

  if (imageUrl && /\.mp4(\?.*)?$/i.test(imageUrl)) {
    video.src = imageUrl;
    video.classList.remove("hidden");
    img.classList.add("hidden");
  } else if (imageUrl) {
    img.src = imageUrl;
    img.classList.remove("hidden");
    video.classList.add("hidden");
  }

  document.getElementById("currentImageContainer").classList.remove("hidden");
  document.getElementById("newImageContainer").classList.add("hidden");

  openModal();
}

/* =====================================================
   CONFIRM EDIT / DELETE (MATCH USERS & PRODUCTS)
===================================================== */
function confirmEditSlide(
  id,
  title,
  description,
  linkUrl,
  buttonText,
  displayOrder,
  isActive,
  imageUrl,
) {
  SwalHelper.confirmEdit(
    "Edit slide?",
    `You can update the slide title, image, content, link, or display order for
     <b>${title}</b>.`,
  ).then((res) => {
    if (res.isConfirmed) {
      openEditModal(
        id,
        title,
        description,
        linkUrl,
        buttonText,
        displayOrder,
        isActive,
        imageUrl,
      );
    }
  });
}

function confirmDelete(title, url) {
  SwalHelper.confirmDelete(
    "Delete slide?",
    `Are you sure you want to permanently delete
     <b>${title}</b>? This action cannot be undone.`,
  ).then((res) => {
    if (res.isConfirmed) {
      SwalHelper.loading("Deleting slide...");
      window.location.href = url;
    }
  });
}

/* =====================================================
   IMAGE PREVIEW
===================================================== */
function previewNewImage(input) {
  const container = document.getElementById("newImageContainer");
  const preview = document.getElementById("newImagePreview");

  if (!input.files?.length) {
    container.classList.add("hidden");
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    preview.src = e.target.result;
    container.classList.remove("hidden");
  };
  reader.readAsDataURL(input.files[0]);
}

/* =====================================================
   FORM VALIDATION (CONSISTENT TEXT)
===================================================== */
document.getElementById("slideForm")?.addEventListener("submit", (e) => {
  const title = document.getElementById("modalTitleInput").value.trim();
  const image = document.getElementById("imageUpload").files.length;
  const isEdit = document.getElementById("slideId").value !== "0";

  if (!title) {
    e.preventDefault();
    SwalHelper.error("Please enter a slide title.");
    return;
  }

  if (!isEdit && !image) {
    e.preventDefault();
    SwalHelper.error("Please upload an image for this slide.");
  }
});

/* =====================================================
   CLOSE EVENTS
===================================================== */
document.getElementById("modalOverlay")?.addEventListener("click", closeModal);
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  openAddModal,
  confirmEditSlide,
  confirmDelete,
  previewNewImage,
  closeModal,
});
