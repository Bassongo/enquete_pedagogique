// Simple carousel functionality

window.addEventListener('DOMContentLoaded', () => {
    const slidesContainer = document.querySelector('.carousel-slides');
    if (!slidesContainer) return;

    const slides = document.querySelectorAll('.carousel-slide');
    const nextBtn = document.querySelector('.carousel-btn.next');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    let index = 0;

    function showSlide(i) {
        slidesContainer.style.transform = `translateX(${-i * 100}%)`;
    }

    function nextSlide() {
        index = (index + 1) % slides.length;
        showSlide(index);
    }

    function prevSlide() {
        index = (index - 1 + slides.length) % slides.length;
        showSlide(index);
    }

    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    showSlide(index);
    setInterval(nextSlide, 5000);
});
