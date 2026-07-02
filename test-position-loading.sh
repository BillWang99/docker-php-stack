#!/bin/bash

# 職稱連動功能測試腳本

echo "==================================="
echo "職稱連動功能測試"
echo "==================================="
echo ""

# 1. 檢查路由是否註冊
echo "1. 檢查路由註冊..."
./artisan.sh hcm_hr route:list | grep "positions/by-department"

if [ $? -eq 0 ]; then
    echo "✅ 路由已正確註冊"
else
    echo "❌ 路由未找到"
    exit 1
fi

echo ""

# 2. 顯示完整路由信息
echo "2. 完整路由信息："
./artisan.sh hcm_hr route:list | grep "positions/by-department" | head -1

echo ""
echo "==================================="
echo "測試完成！"
echo "==================================="
echo ""
echo "請執行以下步驟完成測試："
echo "1. 重新整理瀏覽器頁面（Ctrl+F5 或 Cmd+Shift+R）"
echo "2. 打開員工新增/編輯表單"
echo "3. 選擇一個部門"
echo "4. 檢查職稱下拉選單是否正確更新"
echo ""
echo "如果仍有問題，請檢查："
echo "- 瀏覽器控制台（F12）是否有錯誤"
echo "- Network 標籤中請求的 URL 是否正確"
echo "- Laravel 日誌: src/hcm_hr/storage/logs/laravel.log"
echo ""
