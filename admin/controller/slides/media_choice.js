document.addEventListener("DOMContentLoaded", () => {
  const fileInput = document.querySelector('input[type="file"][name="image"]');
  if (!fileInput || !fileInput.parentNode) return;

  /* ===============================
     Create Media Type Selector
  ================================ */
  const label = document.createElement("label");
  label.className = "form-label";
  label.htmlFor = "media_type";
  label.textContent = "Media Type";

  const select = document.createElement("select");
  select.id = "media_type";
  select.name = "media_type";
  select.className = "form-select mb-2";
  select.innerHTML = `
    <option value="image">Image</option>
    <option value="video">Video (MP4)</option>
  `;

  /* ===============================
     Helper text
  ================================ */
  const help = document.createElement("p");
  help.className = "file-help text-xs text-gray-500 mt-1";

  /* ===============================
     Insert elements
  ================================ */
  fileInput.parentNode.insertBefore(label, fileInput);
  fileInput.parentNode.insertBefore(select, fileInput);
  fileInput.parentNode.appendChild(help);

  /* ===============================
     Constants
  ================================ */
  const MAX_IMAGE_SIZE = 20 * 1024 * 1024; // 20 MB
  const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100 MB

  /* ===============================
     Update accept & help text
  ================================ */
  const updateAccept = () => {
    if (select.value === "video") {
      fileInput.accept = "video/mp4";
      help.textContent = "Max size: 100 MB. MP4 video only.";
    } else {
      fileInput.accept = ".jpg,.jpeg,.png,.gif,.webp,AVIF,image/*";
      help.textContent = "Max size: 20 MB. JPG, PNG, GIF, WebP, AVIF.";
    }
  };

  /* ===============================
     Validate file size
  ================================ */
  fileInput.addEventListener("change", () => {
    const file = fileInput.files[0];
    if (!file) return;

    const maxSize = select.value === "video" ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;

    if (file.size > maxSize) {
      alert(
        select.value === "video"
          ? "Video file is too large. Maximum allowed size is 100 MB."
          : "Image file is too large. Maximum allowed size is 20 MB."
      );
      fileInput.value = ""; // reset file input
    }
  });

  select.addEventListener("change", updateAccept);
  updateAccept();
});
