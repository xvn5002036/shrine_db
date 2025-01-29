document.addEventListener('DOMContentLoaded', function () {
    const slider = document.querySelector('.slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.slide');
    const prevBtn = slider.querySelector('.slider-prev');
    const nextBtn = slider.querySelector('.slider-next');
    const dotsContainer = slider.querySelector('.slider-dots');

    let currentSlide = 0;
    let slideInterval;
    const intervalTime = 5000; // 輪播間隔時間（毫秒）

    // 初始化輪播圖
    function initSlider() {
        // 創建指示點
        slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.classList.add('slider-dot');
            if (index === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });

        // 設定初始狀態
        updateSlides();
        startSlideShow();

        // 滑鼠懸停時暫停輪播
        slider.addEventListener('mouseenter', stopSlideShow);
        slider.addEventListener('mouseleave', startSlideShow);

        // 觸控事件處理
        let touchStartX = 0;
        let touchEndX = 0;

        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            stopSlideShow();
        }, { passive: true });

        slider.addEventListener('touchmove', (e) => {
            touchEndX = e.touches[0].clientX;
        }, { passive: true });

        slider.addEventListener('touchend', () => {
            const difference = touchStartX - touchEndX;
            if (Math.abs(difference) > 50) { // 最小滑動距離
                if (difference > 0) {
                    nextSlide();
                } else {
                    prevSlide();
                }
            }
            startSlideShow();
        });
    }

    // 更新輪播圖顯示
    function updateSlides() {
        // 更新幻燈片位置
        slides.forEach((slide, index) => {
            if (index === currentSlide) {
                slide.style.opacity = '1';
                slide.style.zIndex = '1';
            } else {
                slide.style.opacity = '0';
                slide.style.zIndex = '0';
            }
        });

        // 更新指示點狀態
        const dots = dotsContainer.querySelectorAll('.slider-dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }

    // 切換到指定幻燈片
    function goToSlide(index) {
        currentSlide = index;
        updateSlides();
    }

    // 下一張幻燈片
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlides();
    }

    // 上一張幻燈片
    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlides();
    }

    // 開始自動輪播
    function startSlideShow() {
        if (slideInterval) return;
        slideInterval = setInterval(nextSlide, intervalTime);
    }

    // 停止自動輪播
    function stopSlideShow() {
        if (slideInterval) {
            clearInterval(slideInterval);
            slideInterval = null;
        }
    }

    // 綁定按鈕事件
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            prevSlide();
            stopSlideShow();
            startSlideShow();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            nextSlide();
            stopSlideShow();
            startSlideShow();
        });
    }

    // 鍵盤導航
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            stopSlideShow();
            startSlideShow();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            stopSlideShow();
            startSlideShow();
        }
    });

    // 初始化輪播圖
    initSlider();
}); 