document.addEventListener('DOMContentLoaded', function () {
    // 獲取必要的元素
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const mobileOverlay = document.querySelector('.mobile-menu-overlay');
    const dropdowns = document.querySelectorAll('.has-dropdown');

    // 切換選單狀態
    function toggleMenu() {
        navMenu.classList.toggle('active');
        mobileOverlay.classList.toggle('active');
        document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
    }

    // 點擊漢堡選單按鈕
    menuToggle.addEventListener('click', toggleMenu);

    // 點擊遮罩層關閉選單
    mobileOverlay.addEventListener('click', toggleMenu);

    // 處理下拉選單
    dropdowns.forEach(dropdown => {
        const link = dropdown.querySelector('a');
        const submenu = dropdown.querySelector('.dropdown-menu');

        // 在手機版中，點擊父級選單項目時切換子選單
        link.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';

                // 關閉其他已打開的子選單
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        const otherSubmenu = otherDropdown.querySelector('.dropdown-menu');
                        otherSubmenu.style.display = 'none';
                    }
                });
            }
        });

        // 在桌面版中，使用滑鼠懸停來顯示/隱藏子選單
        if (window.innerWidth > 768) {
            dropdown.addEventListener('mouseenter', () => {
                submenu.style.display = 'block';
            });

            dropdown.addEventListener('mouseleave', () => {
                submenu.style.display = 'none';
            });
        }
    });

    // 監聽視窗大小變化
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            // 重置手機版選單狀態
            navMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';

            // 重置所有下拉選單的顯示狀態
            dropdowns.forEach(dropdown => {
                const submenu = dropdown.querySelector('.dropdown-menu');
                submenu.style.display = 'none';
            });
        }
    });

    // 處理返回頂部按鈕
    const backToTop = document.querySelector('.back-to-top');

    window.addEventListener('scroll', function () {
        if (window.pageYOffset > 200) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });

    backToTop.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}); 