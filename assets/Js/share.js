// Shipping share/copy helpers
(function () {
  function copyToClipboard(text) {
    if (!text) return Promise.reject("empty");
    if (navigator.clipboard && navigator.clipboard.writeText)
      return navigator.clipboard.writeText(text);
    return new Promise(function (resolve, reject) {
      var ta = document.createElement("textarea");
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand("copy");
        resolve();
      } catch (e) {
        reject(e);
      } finally {
        ta.remove();
      }
    });
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".share-btn");
    if (!btn) return;
    e.preventDefault();
    var address = btn.getAttribute("data-address") || "";
    var map = btn.getAttribute("data-map") || "";

    if (navigator.share && (map || address)) {
      navigator
        .share({
          title: "Shipment location",
          text: address || "Shipment location",
          url: map || undefined,
        })
        .catch(function () {
          // fallback to clipboard
          copyToClipboard(map || address).then(
            function () {
              alert("Link copied to clipboard");
            },
            function () {
              alert("Copy failed");
            },
          );
        });
      return;
    }

    // Fallback: copy map if present, otherwise copy address
    var toCopy = map || address || window.location.href;
    copyToClipboard(toCopy).then(
      function () {
        alert("Copied to clipboard");
      },
      function () {
        alert("Unable to copy");
      },
    );
  });
})();
