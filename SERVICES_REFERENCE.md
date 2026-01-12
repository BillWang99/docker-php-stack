# Docker 服務參考

## 服務清單

| 服務 | 容器名稱 | 端口映射 | 用途 |
|------|---------|---------|------|
| Apache | apache | 8080:80 | Web 伺服器 |
| PHP-FPM | php | 6001:6001, 9000 | PHP 處理器 |
| MariaDB | mariadb | 3306:3306 | MySQL 資料庫 |
| MongoDB | mongodb | 27017:27017 | NoSQL 資料庫 |
| Redis | redis | 6379:6379 | 緩存/隊列 |
| Reverb | reverb | 8081:8080 | WebSocket 服務 |
| Queue Worker | queue-worker | - | 後台任務處理 |

## 連線資訊

### MariaDB
```
Host: localhost (外部) / mariadb (容器內)
Port: 3306
User: root
Password: root
Database: app_db
```

### MongoDB
```
Host: localhost (外部) / mongodb (容器內)
Port: 27017
User: root
Password: root
Connection String: mongodb://root:root@mongodb:27017
```

### Redis
```
Host: localhost (外部) / redis (容器內)
Port: 6379
Connection: redis://redis:6379
```

### Reverb (WebSocket)
```
外部訪問: ws://localhost:8081
容器內部: ws://reverb:8080
```

## 常用命令

### 容器管理
```bash
# 啟動所有服務
docker-compose up -d

# 停止所有服務
docker-compose down

# 重啟特定服務
docker-compose restart [service-name]

# 查看服務狀態
docker-compose ps

# 查看服務日誌
docker-compose logs -f [service-name]
```

### 進入容器
```bash
# 進入 PHP 容器
docker exec -it php bash

# 進入 MariaDB
docker exec -it mariadb mysql -uroot -proot

# 進入 MongoDB
docker exec -it mongodb mongosh -u root -p root

# 進入 Redis
docker exec -it redis redis-cli
```

### Laravel 相關
```bash
# 執行 Artisan 命令
docker exec -it php php /var/www/html/broadcast_app/artisan [command]

# 安裝 Composer 依賴
docker exec -it php composer install -d /var/www/html/broadcast_app

# 運行資料庫遷移
docker exec -it php php /var/www/html/broadcast_app/artisan migrate

# 清除緩存
docker exec -it php php /var/www/html/broadcast_app/artisan cache:clear
docker exec -it php php /var/www/html/broadcast_app/artisan config:clear
docker exec -it php php /var/www/html/broadcast_app/artisan route:clear
```

### 即時功能測試
```bash
# 測試 Redis 連線
docker exec -it php redis-cli -h redis ping

# 查看 Reverb 日誌
docker logs -f reverb

# 查看 Queue Worker 日誌
docker logs -f queue-worker

# 手動處理隊列
docker exec -it php php /var/www/html/broadcast_app/artisan queue:work
```

## 資料持久化

所有資料都儲存在 `data/` 目錄：
- `data/mariadb/` - MariaDB 資料
- `data/mongodb/` - MongoDB 資料
- `data/redis/` - Redis 資料

## 新專案快速設置

1. 添加虛擬主機配置：
```bash
./add-project.sh your-project-name
```

2. 配置 .env 檔案：
```env
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=root

REDIS_HOST=redis
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

3. 重啟 Apache：
```bash
docker-compose restart apache
```

## POS 系統架構建議

### 前台 (收銀端)
- URL: http://pos.localhost:8080
- 功能: 點餐、結帳、會員管理
- 即時更新: 庫存、價格、促銷

### 後台 (管理端)
- URL: http://pos-backend.localhost:8080
- 功能: 訂單管理、報表、設定
- 即時通知: 新訂單、異常提醒

### 廚房顯示系統 (KDS)
- URL: http://kitchen.localhost:8080
- 功能: 訂單接收、製作進度
- 即時更新: 新訂單、狀態變更

### 廣播頻道設計
```javascript
// 全局頻道
Echo.channel('pos.global')  // 系統通知
Echo.channel('pos.orders')  // 訂單更新
Echo.channel('pos.kitchen') // 廚房訂單

// 私有頻道
Echo.private('pos.user.' + userId)      // 用戶專用
Echo.private('pos.terminal.' + terminalId) // 終端專用
```

## 效能監控

### 查看資源使用
```bash
docker stats
```

### Redis 監控
```bash
docker exec -it redis redis-cli INFO stats
docker exec -it redis redis-cli MONITOR
```

### MySQL 連線數
```bash
docker exec -it mariadb mysql -uroot -proot -e "SHOW PROCESSLIST;"
```
