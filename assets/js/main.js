document.addEventListener('DOMContentLoaded', function() {
    // 初始化輪播圖
    initSlider();
    
    // 初始化回到頂部按鈕
    initBackToTop();
    
    // 初始化表單驗證
    initFormValidation();
    
    // 初始化燈箱效果
    initLightbox();
    
    // 圖片延遲載入
    initLazyLoading();
    
    // 電子報訂閱處理
    initNewsletterSubscription();
    
    // 平滑滾動
    initSmoothScroll();
    
    // 動態載入更多內容
    initLoadMore();
});

// 輪播圖功能
function initSlider() {
    const slider = document.querySelector('.slider');
    if (!slider) return;

    let currentSlide = 0;
    const slides = slider.querySelectorAll('img');
    const totalSlides = slides.length;

    // 如果沒有投影片，不執行
    if (totalSlides === 0) return;

    // 建立導航點
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'slider-dots';
    slides.forEach((_, index) => {
        const dot = document.createElement('span');
        dot.className = 'dot';
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
    slider.appendChild(dotsContainer);

    // 顯示第一張投影片
    showSlide(0);

    // 自動播放
    setInterval(() => {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }, 5000);

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.display = i === index ? 'block' : 'none';
        });
        
        // 更新導航點
        const dots = dotsContainer.querySelectorAll('.dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function goToSlide(index) {
        currentSlide = index;
        showSlide(currentSlide);
    }
}

// 回到頂部按鈕
function initBackToTop() {
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'back-to-top';
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 100) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// 表單驗證
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showError(field, '此欄位為必填');
                } else {
                    removeError(field);
                    
                    // 電子郵件格式驗證
                    if (field.type === 'email' && !isValidEmail(field.value)) {
                        isValid = false;
                        showError(field, '請輸入有效的電子郵件地址');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

// 顯示錯誤訊息
function showError(field, message) {
    removeError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    field.classList.add('error');
    field.parentNode.appendChild(errorDiv);
}

// 移除錯誤訊息
function removeError(field) {
    const errorDiv = field.parentNode.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
        field.classList.remove('error');
    }
}

// 驗證電子郵件格式
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// 燈箱效果
function initLightbox() {
    const images = document.querySelectorAll('.gallery-image');
    if (images.length === 0) return;

    // 建立燈箱元素
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <img src="" alt="放大圖片">
            <button class="lightbox-close">&times;</button>
        </div>
    `;
    document.body.appendChild(lightbox);

    // 綁定點擊事件
    images.forEach(image => {
        image.addEventListener('click', () => {
            const lightboxImg = lightbox.querySelector('img');
            lightboxImg.src = image.src;
            lightbox.classList.add('show');
        });
    });

    // 關閉燈箱
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox || e.target.classList.contains('lightbox-close')) {
            lightbox.classList.remove('show');
        }
    });
}

// 圖片延遲載入
function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
}

// 電子報訂閱處理
function initNewsletterSubscription() {
    const form = document.querySelector('.newsletter-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = form.querySelector('input[type="email"]');
            
            if (isValidEmail(emailInput.value)) {
                // 使用 AJAX 發送訂閱請求
                fetch('subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(emailInput.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('感謝您的訂閱！', 'success');
                        form.reset();
                    } else {
                        showMessage(data.message || '訂閱失敗，請稍後再試。', 'error');
                    }
                })
                .catch(() => {
                    showMessage('發生錯誤，請稍後再試。', 'error');
                });
            } else {
                showError(emailInput, '請輸入有效的電子郵件地址');
            }
        });
    }
}

// 顯示訊息提示
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    
    document.body.appendChild(messageDiv);
    
    // 自動消失
    setTimeout(() => {
        messageDiv.classList.add('fade-out');
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

// 平滑滾動
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// 動態載入更多內容
function initLoadMore() {
    const loadMoreBtns = document.querySelectorAll('.load-more');
    loadMoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const container = document.querySelector(this.dataset.container);
            const page = parseInt(this.dataset.page) || 1;
            
            fetch(`${this.dataset.url}?page=${page + 1}`)
                .then(response => response.text())
                .then(html => {
                    container.insertAdjacentHTML('beforeend', html);
                    this.dataset.page = page + 1;
                    
                    // 檢查是否還有更多內容
                    if (this.dataset.maxPages && page + 1 >= parseInt(this.dataset.maxPages)) {
                        this.style.display = 'none';
                    }
                })
                .catch(() => {
                    showMessage('載入失敗，請稍後再試。', 'error');
                });
        });
    });
}

// AJAX 請求輔助函數
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(JSON.parse(xhr.response));
            } else {
                reject(xhr.statusText);
            }
        };
        
        xhr.onerror = function() {
            reject(xhr.statusText);
        };
        
        xhr.send(data ? JSON.stringify(data) : null);
    });
} 




