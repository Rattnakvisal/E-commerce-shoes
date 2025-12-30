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
       Update accept & help text
    ================================ */
  const updateAccept = () => {
    if (select.value === "video") {
      fileInput.accept = "video/mp4";
      help.textContent = "Max size: 20MB. MP4 video only";
    } else {
      fileInput.accept = ".jpg,.jpeg,.png,.gif,.webp,image/*";
      help.textContent = "Max size: 5MB. JPG, PNG, GIF, WebP";
    }
  };

  select.addEventListener("change", updateAccept);
  updateAccept();
});
