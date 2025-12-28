document.addEventListener('DOMContentLoaded', () => {

    const track = document.getElementById('sliderTrack');
    const slides = track.children;
    const dots = document.querySelectorAll('.dot');
    const prev = document.getElementById('prevBtn');
    const next = document.getElementById('nextBtn');
    const playPause = document.getElementById('playPause');

    let index = 0;
    let timer;
    let playing = true;
    const interval = 5000;

    function update() {
        track.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('dot-active', i === index));
    }

    function nextSlide() {
        index = (index + 1) % slides.length;
        update();
    }

    function prevSlide() {
        index = (index - 1 + slides.length) % slides.length;
        update();
    }

    function start() {
        stop();
        timer = setInterval(nextSlide, interval);
    }

    function stop() {
        clearInterval(timer);
    }

    next.addEventListener('click', () => {
        nextSlide();
        start();
    });

    prev.addEventListener('click', () => {
        prevSlide();
        start();
    });

    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            index = Number(dot.dataset.index);
            update();
            start();
        });
    });

    playPause.addEventListener('click', () => {
        playing = !playing;
        playPause.innerHTML = playing ? '<i id="playPauseIcon" class="fas fa-pause"></i>' : '<i id="playPauseIcon" class="fas fa-play"></i>';
        playing ? start() : stop();
    });

    track.parentElement.addEventListener('mouseenter', stop);
    track.parentElement.addEventListener('mouseleave', () => playing && start());

    update();
    start();
});

