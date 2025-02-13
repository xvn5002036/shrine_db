<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = '隱私權政策';
include 'templates/header.php';
?>

<div class="content-wrapper">
    <div class="container">
        <div class="content-header">
            <h1>隱私權政策</h1>
            <div class="breadcrumb">
                <a href="index.php">首頁</a>
                <i class="fas fa-angle-right"></i>
                <span>隱私權政策</span>
            </div>
        </div>

        <div class="content-body">
            <div class="privacy-content">
                <section class="privacy-section">
                    <h2>1. 隱私權保護政策的適用範圍</h2>
                    <p>本隱私權政策適用於您在<?php echo SITE_NAME; ?>網站（以下簡稱「本網站」）使用服務時，我們所收集到的個人資料。當您使用本網站服務時，即表示您同意本隱私權政策的所有內容。</p>
                </section>

                <section class="privacy-section">
                    <h2>2. 個人資料的收集與使用</h2>
                    <p>為了提供您最完善的服務，我們可能會收集以下個人資料：</p>
                    <ul>
                        <li>基本資料：姓名、性別、出生年月日</li>
                        <li>聯絡資料：電話號碼、電子郵件地址、通訊地址</li>
                        <li>帳戶資料：帳號、密碼</li>
                        <li>服務使用紀錄：祈福預約、活動報名等相關資訊</li>
                    </ul>
                    <p>這些資料的收集目的在於：</p>
                    <ul>
                        <li>提供及改善網站服務品質</li>
                        <li>處理您的預約和報名需求</li>
                        <li>與您聯繫及通知相關服務訊息</li>
                        <li>進行統計分析以提升服務品質</li>
                    </ul>
                </section>

                <section class="privacy-section">
                    <h2>3. Cookie 的使用</h2>
                    <p>本網站會使用 Cookie 技術，以提供更好的服務體驗。您可以透過瀏覽器設定來決定是否接受 Cookie，但若您選擇關閉 Cookie，可能會造成部分網站功能無法正常使用。</p>
                </section>

                <section class="privacy-section">
                    <h2>4. 個人資料的保護</h2>
                    <p>我們採取適當的安全措施來保護您的個人資料，包括但不限於：</p>
                    <ul>
                        <li>使用加密技術保護資料傳輸</li>
                        <li>定期更新資安防護系統</li>
                        <li>限制資料存取權限</li>
                        <li>定期進行資安稽核</li>
                    </ul>
                </section>

                <section class="privacy-section">
                    <h2>5. 個人資料的分享</h2>
                    <p>除非法律要求或經過您的同意，我們不會將您的個人資料提供給第三方。以下情況可能會分享您的資料：</p>
                    <ul>
                        <li>經過您明確同意</li>
                        <li>依法律規定必須提供</li>
                        <li>為完成您要求的服務必要程序</li>
                    </ul>
                </section>

                <section class="privacy-section">
                    <h2>6. 個人資料的保存期限</h2>
                    <p>我們會在達成收集目的所必要的期間內保存您的個人資料。當超過保存期限或原始收集目的已不存在時，我們會依法銷毀或匿名化處理您的個人資料。</p>
                </section>

                <section class="privacy-section">
                    <h2>7. 您的權利</h2>
                    <p>依據個人資料保護法，您就您的個人資料享有以下權利：</p>
                    <ul>
                        <li>查詢或請求閱覽</li>
                        <li>請求製給複製本</li>
                        <li>請求補充或更正</li>
                        <li>請求停止蒐集、處理或利用</li>
                        <li>請求刪除</li>
                    </ul>
                </section>

                <section class="privacy-section">
                    <h2>8. 隱私權政策的修改</h2>
                    <p>本網站保留隨時修改本隱私權政策的權利，修改後的內容將公布於網站上。建議您定期查看本頁面以獲得最新的隱私權保護政策訊息。</p>
                </section>

                <section class="privacy-section">
                    <h2>9. 聯絡我們</h2>
                    <p>如果您對本隱私權政策有任何疑問，或想行使您的個人資料權利，請透過以下方式與我們聯繫：</p>
                    <ul>
                        <li>電子郵件：<?php echo htmlspecialchars($settings['site_email'] ?? 'info@example.com'); ?></li>
                        <li>電話：<?php 
                            try {
                                $stmt = $pdo->query("SELECT value FROM contact_info WHERE type = 'phone' AND status = 1 LIMIT 1");
                                $phone = $stmt->fetchColumn();
                                echo htmlspecialchars($phone ?: '(02) 2345-6789');
                            } catch (PDOException $e) {
                                echo '(02) 2345-6789';
                            }
                        ?></li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.privacy-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.privacy-section {
    margin-bottom: 30px;
}

.privacy-section:last-child {
    margin-bottom: 0;
}

.privacy-section h2 {
    color: #333;
    font-size: 1.5em;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.privacy-section p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.privacy-section ul {
    list-style-type: disc;
    margin-left: 20px;
    color: #666;
    margin-bottom: 15px;
}

.privacy-section ul li {
    margin-bottom: 10px;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .privacy-content {
        padding: 20px;
    }

    .privacy-section h2 {
        font-size: 1.3em;
    }
}
</style>

<?php include 'templates/footer.php'; ?> 