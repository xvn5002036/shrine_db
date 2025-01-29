document.addEventListener('DOMContentLoaded', function() {
    // 初始化側邊欄切換功能
    initSidebar();
    
    // 初始化表格功能
    initTables();
    
    // 初始化批量操作功能
    initBulkActions();
    
    // 初始化刪除確認
    initDeleteConfirm();
});

// 側邊欄切換功能
function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // 點擊側邊欄外部時關閉側邊欄
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
}

// 表格功能
function initTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // 表格排序功能
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.dataset.sort;
                const order = this.dataset.order === 'asc' ? 'desc' : 'asc';
                
                // 更新排序圖示
                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                this.classList.add(`sort-${order}`);
                this.dataset.order = order;
                
                // 觸發排序事件
                const event = new CustomEvent('table:sort', {
                    detail: { column, order }
                });
                table.dispatchEvent(event);
            });
        });
        
        // 表格選擇功能
        const selectAll = table.querySelector('input[type="checkbox"][data-select-all]');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = table.querySelectorAll('input[type="checkbox"][data-select-item]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionButtons();
            });
            
            // 單個項目選擇
            table.addEventListener('change', function(e) {
                if (e.target.matches('input[type="checkbox"][data-select-item]')) {
                    updateSelectAllState();
                    updateBulkActionButtons();
                }
            });
        }
    });
}

// 批量操作功能
function initBulkActions() {
    const bulkActionButtons = document.querySelectorAll('[data-bulk-action]');
    
    bulkActionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.bulkAction;
            const selectedItems = getSelectedItems();
            
            if (selectedItems.length === 0) {
                alert('請選擇要操作的項目');
                return;
            }
            
            // 觸發批量操作事件
            const event = new CustomEvent('bulk:action', {
                detail: { action, items: selectedItems }
            });
            document.dispatchEvent(event);
        });
    });
}

// 刪除確認功能
function initDeleteConfirm() {
    document.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('[data-delete]');
        if (deleteButton) {
            e.preventDefault();
            
            if (confirm('確定要刪除這個項目嗎？')) {
                const form = deleteButton.closest('form');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = deleteButton.href;
                }
            }
        }
    });
}

// 更新全選狀態
function updateSelectAllState() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const selectAll = table.querySelector('input[type="checkbox"][data-select-all]');
        const checkboxes = table.querySelectorAll('input[type="checkbox"][data-select-item]');
        const checkedBoxes = table.querySelectorAll('input[type="checkbox"][data-select-item]:checked');
        
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
            selectAll.indeterminate = checkedBoxes.length > 0 && checkboxes.length !== checkedBoxes.length;
        }
    });
}

// 更新批量操作按鈕狀態
function updateBulkActionButtons() {
    const bulkActionButtons = document.querySelectorAll('[data-bulk-action]');
    const hasSelection = getSelectedItems().length > 0;
    
    bulkActionButtons.forEach(button => {
        button.disabled = !hasSelection;
    });
}

// 獲取已選擇的項目
function getSelectedItems() {
    const selectedCheckboxes = document.querySelectorAll('input[type="checkbox"][data-select-item]:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
}

// AJAX 表單提交
function submitForm(form, options = {}) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    
    if (submitButton) {
        submitButton.disabled = true;
    }
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (options.success) {
                options.success(data);
            }
        } else {
            if (options.error) {
                options.error(data);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (options.error) {
            options.error({ message: '發生錯誤，請稍後再試' });
        }
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
        }
    });
}

// 圖片上傳預覽
function initImageUpload(input, previewElement) {
    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewElement.src = e.target.result;
                previewElement.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
} 




