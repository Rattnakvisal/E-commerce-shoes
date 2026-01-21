/* =====================================================
   CONFIG
===================================================== */
var RELOAD_DELAY = 700;

/* =====================================================
   SWEETALERT HELPERS (compatible style)
===================================================== */
window.SwalHelper = {};
window.SwalHelper.loading = function (title) {
  title = typeof title === "undefined" ? "Processing..." : title;
  Swal.fire({
    title: title,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: function () {
      Swal.showLoading();
    },
  });
};

window.SwalHelper.success = function (title, text) {
  title = title || "";
  text = text || "";
  return Swal.fire({
    icon: "success",
    title: title,
    text: text || undefined,
    showConfirmButton: false,
    timer: 1200,
    timerProgressBar: true,
  });
};

window.SwalHelper.error = function (text) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: text,
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
  });
};

window.SwalHelper.confirmEdit = function (title, text) {
  return Swal.fire({
    icon: "question",
    title: title,
    html: '<p class="text-gray-600 mt-2">' + text + "</p>",
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6d28d9",
    cancelButtonColor: "#6b7280",
  });
};

window.SwalHelper.confirmDelete = function (title, text) {
  return Swal.fire({
    icon: "warning",
    title: title,
    html: '<p class="text-gray-600 mt-2">' + text + "</p>",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#ef4444",
    cancelButtonColor: "#6b7280",
  });
};

/* =====================================================
   MODAL HELPERS
===================================================== */
window.openModal = function () {
  var modal = document.getElementById("slideModal");
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
  }
  var overlay = document.getElementById("modalOverlay");
  if (overlay) overlay.classList.remove("hidden");
  document.documentElement.style.overflow = "hidden";
};

window.closeModal = function () {
  var modal = document.getElementById("slideModal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
  var overlay = document.getElementById("modalOverlay");
  if (overlay) overlay.classList.add("hidden");
  document.documentElement.style.overflow = "";
  var upload = document.getElementById("imageUpload");
  if (upload) upload.value = "";
};

/* =====================================================
   OPEN ADD SLIDE (🔥 FIXED)
===================================================== */
window.openAddModal = function () {
  document.getElementById("modalTitle").innerHTML =
    '<i class="fas fa-plus mr-2 text-indigo-600"></i> Add Slide';

  document.getElementById("slideId").value = "0";
  document.getElementById("oldImage").value = "";
  document.getElementById("modalTitleInput").value = "";
  document.getElementById("modalDescription").value = "";
  document.getElementById("modalLinkUrl").value = "";
  document.getElementById("modalButtonText").value = "";
  document.getElementById("modalDisplayOrder").value = 1;
  document.getElementById("modalIsActive").value = "1";

  var curCont = document.getElementById("currentImageContainer");
  if (curCont) curCont.classList.add("hidden");
  var newCont = document.getElementById("newImageContainer");
  if (newCont) newCont.classList.add("hidden");

  openModal();
};

/* =====================================================
   OPEN EDIT SLIDE
===================================================== */
window.openEditModal = function (
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

  var curContEdit = document.getElementById("currentImageContainer");
  if (curContEdit) curContEdit.classList.remove("hidden");
  var newContEdit = document.getElementById("newImageContainer");
  if (newContEdit) newContEdit.classList.add("hidden");

  openModal();
};

/* =====================================================
   CONFIRM EDIT / DELETE
===================================================== */
window.confirmEditSlide = function (
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
    "You can update the slide <b>" + title + "</b>.",
  ).then(function (res) {
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
};

window.confirmDelete = function (title, url) {
  SwalHelper.confirmDelete(
    "Delete slide?",
    "Are you sure you want to delete <b>" + title + "</b>?",
  ).then(function (res) {
    if (res.isConfirmed) {
      SwalHelper.loading("Deleting slide...");
      window.location.href = url;
    }
  });
};

/* =====================================================
   IMAGE PREVIEW
===================================================== */
window.previewNewImage = function (input) {
  var container = document.getElementById("newImageContainer");
  var preview = document.getElementById("newImagePreview");

  if (!input.files || !input.files.length) {
    if (container) container.classList.add("hidden");
    return;
  }

  var reader = new FileReader();
  reader.onload = function (e) {
    if (preview) preview.src = e.target.result;
    if (container) container.classList.remove("hidden");
  };
  reader.readAsDataURL(input.files[0]);
};

/* =====================================================
   FORM VALIDATION
===================================================== */
var slideForm = document.getElementById("slideForm");
if (slideForm) {
  slideForm.addEventListener("submit", function (e) {
    var titleEl = document.getElementById("modalTitleInput");
    var imageEl = document.getElementById("imageUpload");
    var idEl = document.getElementById("slideId");
    var title = titleEl ? titleEl.value.trim() : "";
    var image = imageEl && imageEl.files ? imageEl.files.length : 0;
    var isEdit = idEl ? idEl.value !== "0" : false;

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
}

/* =====================================================
   CLOSE EVENTS
===================================================== */
var modalOverlayEl = document.getElementById("modalOverlay");
if (modalOverlayEl) modalOverlayEl.addEventListener("click", closeModal);
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") closeModal();
});
