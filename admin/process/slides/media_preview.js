// Replace any <img> elements whose src points to .mp4 with a video preview
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var imgs = document.querySelectorAll('img');
        imgs.forEach(function (img) {
            try {
                var src = img.getAttribute('src') || '';
                if (typeof src === 'string' && src.toLowerCase().endsWith('.mp4')) {
                    var video = document.createElement('video');
                    video.src = src;
                    video.autoplay = true;
                    video.muted = true;
                    video.loop = true;
                    video.playsInline = true;
                    video.style.width = img.style.width || img.width + 'px';
                    video.style.height = img.style.height || img.height + 'px';
                    video.className = img.className || '';
                    video.setAttribute('aria-label', img.getAttribute('alt') || 'Slide video');

                    // Replace image with video
                    img.parentNode.replaceChild(video, img);
                }
            } catch (e) { /* ignore */ }
        });
    });
})();
