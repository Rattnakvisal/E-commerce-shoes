// Inject a media type selector before the file input named 'image'
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var fileInput = document.querySelector('input[type=file][name="image"]');
        if (!fileInput) return;

        // Create selector
        var label = document.createElement('label');
        label.setAttribute('for', 'media_type');
        label.className = 'form-label';
        label.textContent = 'Media Type';

        var select = document.createElement('select');
        select.name = 'media_type';
        select.id = 'media_type';
        select.className = 'form-select mb-2';

        var optImage = document.createElement('option'); optImage.value = 'image'; optImage.text = 'Image';
        var optVideo = document.createElement('option'); optVideo.value = 'video'; optVideo.text = 'Video (MP4)';
        select.appendChild(optImage); select.appendChild(optVideo);

        // Insert before file input
        fileInput.parentNode.insertBefore(label, fileInput);
        fileInput.parentNode.insertBefore(select, fileInput);

        // Update accept based on selection
        function updateAccept() {
            if (select.value === 'video') {
                fileInput.accept = 'video/mp4';
            } else {
                fileInput.accept = 'image/*';
            }
        }
        select.addEventListener('change', updateAccept);
        updateAccept();
    });
})();
