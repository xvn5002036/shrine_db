<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = '使用條款';
include 'templates/header.php';
?>

<div class="content-wrapper">
    <div class="container">
        <div class="content-header">
            <h1>使用條款</h1>
            <div class="breadcrumb">
                <a href="index.php">首頁</a>
                <i class="fas fa-angle-right"></i>
                <span>使用條款</span>
            </div>
        </div>

        <div class="content-body">
            <div class="terms-content">
                <section class="terms-section">
                    <h2>1. 總則</h2>
                    <p>歡迎使用<?php echo SITE_NAME; ?>網站（以下簡稱「本網站」）。請您在使用本網站服務前，詳細閱讀本使用條款。使用本網站即表示您同意接受以下條款的約束。</p>
                </section>

                <section class="terms-section">
                    <h2>2. 服務內容</h2>
                    <p>本網站提供宮廟相關資訊、活動報名、祈福服務預約等功能。我們保留隨時修改或中止服務的權利，且無需事先通知。</p>
                </section>

                <section class="terms-section">
                    <h2>3. 用戶義務</h2>
                    <p>用戶在使用本網站時應遵守以下規定：</p>
                    <ul>
                        <li>提供真實、準確的個人資料</li>
                        <li>遵守中華民國相關法律法規</li>
                        <li>尊重宗教信仰及文化傳統</li>
                        <li>不得進行任何違法或不當行為</li>
                    </ul>
                </section>

                <section class="terms-section">
                    <h2>4. 隱私保護</h2>
                    <p>我們重視您的隱私權，相關隱私保護政策請參閱本網站的「隱私權政策」頁面。</p>
                </section>

                <section class="terms-section">
                    <h2>5. 智慧財產權</h2>
                    <p>本網站所有內容，包括但不限於文字、圖片、影音、標誌等，均受中華民國著作權法及國際著作權條約的保護。未經本網站書面許可，不得以任何方式重製、傳播、改作、編輯或為其他利用。</p>
                </section>

                <section class="terms-section">
                    <h2>6. 免責聲明</h2>
                    <p>本網站不擔保服務一定能滿足用戶的所有需求，也不擔保服務不會中斷。對於因網路狀況、通訊線路等不可抗力因素造成的服務中斷或資料喪失，本網站不承擔任何責任。</p>
                </section>

                <section class="terms-section">
                    <h2>7. 條款修改</h2>
                    <p>本網站保留隨時修改本使用條款的權利，修改後的條款將公布於網站上。如您繼續使用本網站，即表示您同意接受修改後的條款。</p>
                </section>

                <section class="terms-section">
                    <h2>8. 準據法與管轄法院</h2>
                    <p>本使用條款之解釋與適用，以及與本使用條款有關的爭議，均應依照中華民國法律予以處理，並以臺灣臺北地方法院為第一審管轄法院。</p>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.terms-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.terms-section {
    margin-bottom: 30px;
}

.terms-section:last-child {
    margin-bottom: 0;
}

.terms-section h2 {
    color: #333;
    font-size: 1.5em;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.terms-section p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.terms-section ul {
    list-style-type: disc;
    margin-left: 20px;
    color: #666;
}

.terms-section ul li {
    margin-bottom: 10px;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .terms-content {
        padding: 20px;
    }

    .terms-section h2 {
        font-size: 1.3em;
    }
}
</style>

<?php include 'templates/footer.php'; ?> 