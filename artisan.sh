#!/bin/bash
# 通用 artisan 指令執行工具

PROJECT_DIR="/var/www/html"
LOCAL_SRC="./src"

# 列出所有 Laravel 專案（包含 artisan 檔案的目錄）
list_projects() {
    echo "可用的專案："
    for dir in "$LOCAL_SRC"/*/ ; do
        if [ -f "${dir}artisan" ]; then
            basename "$dir"
        fi
    done
}

# 顯示使用說明
show_usage() {
    echo "用法："
    echo "  ./artisan.sh <專案名稱> [artisan指令]"
    echo "  ./artisan.sh list                      - 列出所有可用專案"
    echo ""
    echo "範例："
    echo "  ./artisan.sh oppa_pos migrate"
    echo "  ./artisan.sh test-mongo cache:clear"
    echo "  ./artisan.sh oppa_pos make:controller UserController"
    echo ""
    list_projects
}

# 檢查參數
if [ $# -eq 0 ]; then
    show_usage
    exit 0
fi

# 列出專案
if [ "$1" = "list" ]; then
    list_projects
    exit 0
fi

PROJECT_NAME="$1"
shift

# 檢查專案是否存在
if [ ! -f "$LOCAL_SRC/$PROJECT_NAME/artisan" ]; then
    echo "錯誤：找不到專案 '$PROJECT_NAME' 或該專案沒有 artisan 檔案"
    echo ""
    show_usage
    exit 1
fi

# 執行 artisan 指令
docker exec -it php php "$PROJECT_DIR/$PROJECT_NAME/artisan" "$@"
