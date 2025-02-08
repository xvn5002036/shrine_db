<?php
// 獲取統計數據
try {
    // 獲取最新消息數量
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    $news_count = $stmt->fetchColumn();

    // 獲取活動數量
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    $events_count = $stmt->fetchColumn();

    // 獲取用戶數量
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $users_count = $stmt->fetchColumn();

    // 獲取未讀聯絡訊息數量
    $stmt = $pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'unread'");
    $unread_messages_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    $news_count = $events_count = $users_count = $unread_messages_count = 0;
}

// 獲取最新未讀訊息
try {
    $stmt = $pdo->query("
        SELECT * FROM contacts 
        WHERE status = 'unread' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recent messages: " . $e->getMessage());
    $recent_messages = [];
}
?>

<div class="dashboard">
    <!-- 歡迎訊息 -->
    <div class="welcome-section">
        <h2>歡迎回來，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></h2>
        <p>上次登入時間：<?php echo $_SESSION['last_login'] ? date('Y-m-d H:i:s', strtotime($_SESSION['last_login'])) : '首次登入'; ?></p>
    </div>

    <!-- 統計卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="stat-content">
                <h3>最新消息</h3>
                <p class="stat-number"><?php echo number_format($news_count); ?></p>
                <p class="stat-label">則貼文</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>活動管理</h3>
                <p class="stat-number"><?php echo number_format($events_count); ?></p>
                <p class="stat-label">個活動</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>用戶管理</h3>
                <p class="stat-number"><?php echo number_format($users_count); ?></p>
                <p class="stat-label">位用戶</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h3>未讀訊息</h3>
                <p class="stat-number"><?php echo number_format($unread_messages_count); ?></p>
                <p class="stat-label">則訊息</p>
            </div>
        </div>
    </div>

    <!-- 最新未讀訊息 -->
    <?php if (!empty($recent_messages)): ?>
    <div class="recent-messages">
        <h3>最新未讀訊息</h3>
        <div class="messages-grid">
            <?php foreach ($recent_messages as $message): ?>
            <div class="message-card">
                <div class="message-header">
                    <span class="sender-name"><?php echo htmlspecialchars($message['name']); ?></span>
                    <span class="message-time"><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></span>
                </div>
                <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                <div class="message-preview"><?php echo mb_substr(htmlspecialchars($message['message']), 0, 100) . '...'; ?></div>
                <div class="message-actions">
                    <a href="../contacts/view.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> 查看
                    </a>
                    <a href="../contacts/reply.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-reply"></i> 回覆
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 快速操作 -->
    <div class="quick-actions">
        <h3>快速操作</h3>
        <div class="actions-grid">
            <a href="?page=news&action=add" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>新增消息</span>
            </a>
            <a href="?page=events&action=add" class="action-card">
                <i class="fas fa-calendar-plus"></i>
                <span>新增活動</span>
            </a>
            <a href="?page=blessings&action=add" class="action-card">
                <i class="fas fa-pray"></i>
                <span>新增祈福</span>
            </a>
            <a href="?page=users&action=add" class="action-card">
                <i class="fas fa-user-plus"></i>
                <span>新增用戶</span>
            </a>
        </div>
    </div>

    <!-- 系統資訊 -->
    <div class="system-info">
        <h3>系統資訊</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>PHP 版本</label>
                <span><?php echo PHP_VERSION; ?></span>
            </div>
            <div class="info-item">
                <label>MySQL 版本</label>
                <span><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
            </div>
            <div class="info-item">
                <label>伺服器時間</label>
                <span><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div class="info-item">
                <label>系統記憶體</label>
                <span><?php echo ini_get('memory_limit'); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard {
    padding: 20px;
}

.welcome-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.welcome-section h2 {
    color: var(--primary);
    margin-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-content h3 {
    color: var(--text);
    margin-bottom: 5px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary);
    margin-bottom: 5px;
}

.stat-label {
    color: var(--text-light);
    font-size: 14px;
}

.quick-actions {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.quick-actions h3 {
    margin-bottom: 20px;
    color: var(--primary);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.action-card {
    background: var(--light);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: var(--text);
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.action-card i {
    font-size: 24px;
    margin-bottom: 10px;
    color: var(--primary);
}

.system-info {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.system-info h3 {
    margin-bottom: 20px;
    color: var(--primary);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 10px;
    background: var(--light);
    border-radius: 8px;
}

.info-item label {
    display: block;
    color: var(--text-light);
    margin-bottom: 5px;
    font-size: 14px;
}

.info-item span {
    color: var(--text);
    font-weight: 500;
}

.recent-messages {
    margin-top: 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.recent-messages h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.messages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.message-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #eee;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.sender-name {
    font-weight: bold;
    color: var(--primary-color);
}

.message-time {
    color: #666;
    font-size: 0.9rem;
}

.message-subject {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.message-preview {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.message-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style> 
