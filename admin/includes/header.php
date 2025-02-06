<?php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAdminLogin();

// 獲取當前管理員信息
$admin_name = $_SESSION['admin_name'] ?? '管理員';
?>

                <header class="admin-header">
                    <div class="header-left">
                        <button class="sidebar-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    
                    <div class="header-right">
                        <div class="admin-user">
                            <span class="welcome-text">歡迎，</span>
                            <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                            <a href="/admin/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                登出
                            </a>
                        </div>
                    </div>
                </header>

                <!-- 添加 JavaScript -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // 側邊欄切換
                    const sidebarToggle = document.querySelector('.sidebar-toggle');
                    const adminSidebar = document.querySelector('.admin-sidebar');
                    const adminMain = document.querySelector('.admin-main');
                    
                    if (sidebarToggle) {
                        sidebarToggle.addEventListener('click', function() {
                            adminSidebar.classList.toggle('show');
                            adminMain.classList.toggle('sidebar-hidden');
                        });
                    }
                    
                    // 響應式處理
                    function handleResize() {
                        if (window.innerWidth <= 992) {
                            adminSidebar.classList.remove('show');
                            adminMain.classList.add('sidebar-hidden');
                        } else {
                            adminSidebar.classList.remove('show');
                            adminMain.classList.remove('sidebar-hidden');
                        }
                    }
                    
                    window.addEventListener('resize', handleResize);
                    handleResize();
                });
                </script>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>