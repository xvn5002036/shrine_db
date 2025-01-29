# 宮廟管理系統 (Temple Management System)

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)](https://www.mysql.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 專案簡介

宮廟管理系統是一套專為臺灣宮廟設計的全方位管理解決方案。本系統提供完整的宮廟行政管理、祈福服務、活動管理等功能，協助宮廟工作人員更有效率地處理日常事務。

### 主要特點

- 🏛️ 完整的宮廟管理功能
- 🙏 線上祈福服務系統
- 📅 活動管理與報名
- 📸 活動花絮圖片管理
- 📊 後台數據統計分析
- 🔒 安全的用戶權限管理
- 💡 直覺的操作介面

## 系統需求

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx 網頁伺服器
- PHP 擴充模組：
  - PDO PHP Extension
  - PDO MySQL PHP Extension
  - GD PHP Extension
  - JSON PHP Extension
  - OpenSSL PHP Extension

## 安裝指南

1. **下載專案**

   ```bash
   git clone https://github.com/yourusername/temple-management.git
   cd temple-management
   ```

2. **設定檔案權限**

   ```bash
   chmod 755 -R ./
   chmod 777 -R ./uploads
   chmod 777 -R ./cache
   chmod 777 -R ./backups
   chmod 777 -R ./config
   ```

3. **建立資料庫**

   - 在 MySQL 中建立新的資料庫
   - 編碼設定為 `utf8mb4_unicode_ci`

4. **執行安裝程序**

   - 訪問 `http://您的網域/install/`
   - 依照安裝精靈的指示完成設定

5. **安裝完成後**
   - 刪除 `install` 目錄
   - 確保 `config/installed.php` 檔案存在

## 目錄結構

```
├── admin/              # 後台管理介面
├── assets/            # 靜態資源文件
│   ├── css/          # CSS 樣式表
│   ├── js/           # JavaScript 文件
│   └── images/       # 圖片資源
├── config/            # 設定檔案
├── includes/          # PHP 函式庫
├── sql/              # SQL 檔案
├── uploads/          # 上傳檔案目錄
├── backups/          # 備份檔案目錄
└── cache/            # 快取目錄
```

## 主要功能

### 前台功能

- 宮廟資訊展示
- 最新消息發布
- 活動資訊查詢
- 線上祈福服務
- 活動花絮瀏覽
- 聯絡表單

### 後台功能

- 儀表板總覽
- 用戶管理
- 最新消息管理
- 活動管理
- 祈福服務管理
- 圖片管理
- 系統設定

## 安全性建議

1. 定期更新系統密碼
2. 啟用 SSL 憑證（HTTPS）
3. 定期備份資料庫
4. 設定適當的檔案權限
5. 移除不必要的測試檔案

## 常見問題

**Q: 如何重設管理員密碼？**
A: 請聯繫系統管理員或參考管理手冊的密碼重設程序。

**Q: 如何備份資料庫？**
A: 可透過後台的備份功能或使用 phpMyAdmin 進行備份。

## 版本資訊

目前版本：1.0.0
發布日期：2024-01-01

### 版本更新記錄

- v1.0.0 (2024-01-01)
  - 初始版本發布
  - 基本功能完成
  - 使用者介面優化

## 授權資訊

本專案採用 MIT 授權條款。詳細內容請參閱 [LICENSE](LICENSE) 檔案。

## 開發團隊

- 主要開發者：[您的名字]
- 設計師：[設計師名字]
- 測試團隊：[測試團隊]

## 特別感謝

感謝所有為本專案提供協助與建議的貢獻者。

---

© 2025 宮廟管理系統 版權所有
