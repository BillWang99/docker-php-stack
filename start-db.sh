#!/usr/bin/env bash

set -e  # 有錯就中斷

echo "👉 切換到 docker 專案目錄"
cd ~/docker-php-stack

echo "🐬 啟動 MariaDB..."
docker compose up -d mariadb

echo "📦 啟動 Redis..."
docker compose up -d redis

echo "✅ 所有服務已啟動完成"
