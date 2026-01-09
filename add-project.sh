#!/bin/bash

# 快速新增 Laravel 專案的腳本
# 使用方式: ./add-project.sh project_name

if [ -z "$1" ]; then
    echo "使用方式: ./add-project.sh project_name"
    echo "範例: ./add-project.sh my-api"
    exit 1
fi

PROJECT_NAME="$1"
HOSTNAME=$(echo "$PROJECT_NAME" | tr '_' '-')

echo "📦 正在設定專案: $PROJECT_NAME"
echo "🌐 域名: ${HOSTNAME}.localhost:8080"
echo ""

# 1. 建立 VirtualHost 配置
echo "✓ 建立 Apache 配置檔案..."
cat > "apache/sites/${HOSTNAME}.conf" << EOF
# ${PROJECT_NAME} 專案虛擬主機
<VirtualHost *:80>
    ServerName ${HOSTNAME}.localhost
    ServerAlias ${HOSTNAME}.local
    DocumentRoot "/var/www/html/${PROJECT_NAME}/public"
    DirectoryIndex index.php
    
    <Directory "/var/www/html/${PROJECT_NAME}/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
        
        # Laravel 路由
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>

    # PHP-FPM 代理設定
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://php:9000"
    </FilesMatch>

    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 common
</VirtualHost>
EOF

# 2. 加入 hosts 檔案
echo "✓ 更新 /etc/hosts..."
if ! grep -q "${HOSTNAME}.localhost" /etc/hosts; then
    sudo sh -c "echo '127.0.0.1 ${HOSTNAME}.localhost' >> /etc/hosts"
    echo "  已加入: ${HOSTNAME}.localhost"
else
    echo "  已存在: ${HOSTNAME}.localhost"
fi

# 3. 重啟 Apache
echo "✓ 重啟 Apache..."
docker-compose restart apache > /dev/null 2>&1

echo ""
echo "✅ 設定完成！"
echo ""
echo "🚀 後續步驟："
echo "   1. 建立或放置 Laravel 專案到 src/${PROJECT_NAME}/"
echo "   2. 設定 .env 檔案:"
echo "      APP_URL=http://${HOSTNAME}.localhost:8080"
echo "      DB_HOST=mariadb"
echo "   3. 執行: ./artisan.sh ${PROJECT_NAME} key:generate"
echo "   4. 執行: ./artisan.sh ${PROJECT_NAME} migrate"
echo ""
echo "🌐 存取網址: http://${HOSTNAME}.localhost:8080"
