(function () {
  function run() {
    /* ===============================
           1. Replace IMG → VIDEO only for .mp4
        ================================ */
    document.querySelectorAll("img.thumbnail").forEach((img) => {
      const src = img.getAttribute("src");
      if (!src || !/\.mp4(\?.*)?$/i.test(src)) return;

      const video = document.createElement("video");
      video.src = src;
      video.autoplay = true;
      video.muted = true;
      video.loop = true;
      video.playsInline = true;
      video.className = img.className;
      video.style.maxWidth = "100%";
      video.style.height =
        img.style.height || img.height ? img.height + "px" : "auto";
      video.setAttribute(
        "aria-label",
        img.getAttribute("alt") || "Slide video"
      );

      img.replaceWith(video);
    });

    /* ===============================
           2. Current media preview
        ================================ */
    const currentImg = document.getElementById("currentImagePreview");
    const currentVideo = document.getElementById("currentVideoPreview");

    if (currentImg && currentVideo) {
      const updatePreview = () => {
        const src = currentImg.getAttribute("src") || currentImg.src || "";
        if (/\.mp4(\?.*)?$/i.test(src)) {
          currentVideo.src = src;
          currentVideo.classList.remove("hidden");
          currentImg.classList.add("hidden");
        } else if (src) {
          currentImg.classList.remove("hidden");
          currentVideo.classList.add("hidden");
          currentVideo.src = "";
        }
      };

      updatePreview();
      new MutationObserver(updatePreview).observe(currentImg, {
        attributes: true,
        attributeFilter: ["src"],
      });
    }

    /* ===============================
           3. New upload preview (image / video)
        ================================ */
    window.previewNewImage = function (input) {
      const img = document.getElementById("newImagePreview");
      const video = document.getElementById("newVideoPreview");
      const box = document.getElementById("newImageContainer");

      if (!input.files || !input.files[0]) {
        box.classList.add("hidden");
        return;
      }

      const file = input.files[0];
      const reader = new FileReader();

      reader.onload = (e) => {
        if (file.type.startsWith("video/")) {
          video.src = e.target.result;
          video.classList.remove("hidden");
          img.classList.add("hidden");
        } else {
          img.src = e.target.result;
          img.classList.remove("hidden");
          video.classList.add("hidden");
          video.src = "";
        }
        box.classList.remove("hidden");
      };

      reader.readAsDataURL(file);
    };
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", run);
  } else {
    run();
  }
})();
