# Docker PHP Stack

基於 Docker 的 PHP 開發環境，包含 Apache、PHP-FPM、MariaDB 和 MongoDB。適合開發多個 Laravel 專案。

## 📋 環境需求

- Docker Desktop
- Make（macOS 已內建）
- Git

## 🚀 快速開始

### 1. 啟動環境

```bash
# 啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps

# 查看日誌
docker-compose logs -f
```

### 2. 停止環境

```bash
# 停止所有服務
docker-compose down

# 停止並刪除資料卷（注意：會刪除資料庫資料）
docker-compose down -v
```

## 🐳 Docker 容器說明

| 容器名稱 | 服務 | 連接埠 | 說明 |
|---------|------|--------|------|
| `apache` | Web Server | 8080 | Apache HTTP Server |
| `php` | PHP-FPM | 9000 | PHP 執行環境 |
| `mariadb` | 資料庫 | 3306 | MySQL 相容資料庫 |
| `mongodb` | NoSQL | 27017 | MongoDB 資料庫 |

### 資料庫連線資訊

**MariaDB**
- Host: `localhost` (本機) / `mariadb` (容器內)
- Port: `3306`
- Database: `app_db`
- Username: `app_user`
- Password: `secret`
- Root Password: `root`

**MongoDB**
- Host: `localhost` (本機) / `mongodb` (容器內)
- Port: `27017`
- Username: `root`
- Password: `root`

## 🛠️ 開發工具

本專案提供兩種工具來簡化 Laravel 專案管理：

### 方式 1：Shell Script（推薦）

更直覺、更簡潔的使用方式。

```bash
# 列出所有可用的 Laravel 專案
./artisan.sh list

# 執行 artisan 指令
./artisan.sh <專案名稱> [artisan指令]
```

**範例：**

```bash
# 查看所有專案
./artisan.sh list

# 執行 migration
./artisan.sh oppa_pos migrate

# 建立 Controller
./artisan.sh test-mongo make:controller UserController

# 清除快取
./artisan.sh oppa_pos cache:clear

# 查看路由列表
./artisan.sh test-mongo route:list
```

### 方式 2：Makefile

提供更多功能，包含 Composer 和 NPM 操作。

```bash
# 查看所有可用指令
make help

# 執行 artisan 指令
make artisan project=<專案名稱> cmd=<指令>

# 執行 composer 指令
make composer project=<專案名稱> cmd=<指令>

# 執行 npm 指令
make npm project=<專案名稱> cmd=<指令>

# 進入 PHP 容器
make php-bash
```

**範例：**

```bash
# Artisan 指令
make artisan project=oppa_pos cmd=migrate
make artisan project=test-mongo cmd="make:model Product -m"
make artisan project=oppa_pos cmd="db:seed"

# Composer 指令
make composer project=oppa_pos cmd=install
make composer project=test-mongo cmd="require laravel/sanctum"
make composer project=oppa_pos cmd=update

# NPM 指令
make npm project=oppa_pos cmd=install
make npm project=oppa_pos cmd="run dev"
make npm project=oppa_pos cmd="run build"

# 進入容器
make php-bash
```

## 📁 專案結構

```
docker-php-stack/
├── docker-compose.yml      # Docker Compose 設定檔
├── artisan.sh             # Laravel Artisan 快捷工具
├── Makefile               # Make 指令集
├── README.md              # 本說明文件
├── .gitignore             # Git 忽略檔案設定
│
├── apache/                # Apache 設定
│   ├── Dockerfile
│   └── vhost.conf
│
├── php/                   # PHP 設定
│   └── Dockerfile
│
├── data/                  # 資料庫資料（不納入版控）
│   ├── mariadb/
│   └── mongodb/
│
└── src/                   # 專案原始碼目錄
    ├── index.php
    ├── oppa_pos/          # Laravel 專案 1
    └── test-mongo/        # Laravel 專案 2
```

## 🔧 常用 Docker 指令

### 容器管理

```bash
# 查看運行中的容器
docker ps

# 查看所有容器（包含停止的）
docker ps -a

# 查看容器日誌
docker logs -f php
docker logs -f mariadb
docker logs -f mongodb

# 重啟特定容器
docker restart php
docker restart mariadb
```

### 進入容器

```bash
# 進入 PHP 容器
docker exec -it php bash

# 進入 MariaDB 容器
docker exec -it mariadb bash

# 直接連線 MariaDB
docker exec -it mariadb mysql -uroot -proot

# 進入 MongoDB Shell
docker exec -it mongodb mongosh -u root -p root
```

### 資源清理

```bash
# 停止並移除容器
docker-compose down

# 移除未使用的映像檔
docker image prune -a

# 清理所有未使用的資源
docker system prune -a
```

## 📝 新增 Laravel 專案

### 方式 1：在容器內建立新專案

```bash
# 進入 PHP 容器
docker exec -it php bash

# 建立新的 Laravel 專案
cd /var/www/html
composer create-project laravel/laravel my-new-project

# 離開容器
exit

# 現在就可以使用工具了
./artisan.sh my-new-project migrate
```

### 方式 2：複製現有專案

```bash
# 將專案放到 src/ 目錄下
cp -r /path/to/your/laravel-project src/my-project

# 安裝依賴
make composer project=my-project cmd=install

# 設定環境
cp src/my-project/.env.example src/my-project/.env
./artisan.sh my-project key:generate

# 執行 migration
./artisan.sh my-project migrate
```

## 🌐 存取應用程式

- **Web 應用**: http://localhost:8080
- **PHP Info**: http://localhost:8080/info.php
- **Laravel 專案**: http://localhost:8080/oppa_pos/public

## ⚠️ 注意事項

1. **資料庫資料不納入版控**：`data/` 目錄已加入 `.gitignore`
2. **環境變數**：記得複製 `.env.example` 為 `.env` 並設定正確的資料庫連線
3. **檔案權限**：Laravel 的 `storage/` 和 `bootstrap/cache/` 需要寫入權限
4. **Composer 依賴**：新專案記得先執行 `composer install`

## 🐛 常見問題

### artisan 指令執行失敗

```bash
# 確認專案是否已安裝依賴
make composer project=專案名稱 cmd=install

# 確認 .env 檔案是否存在
ls src/專案名稱/.env
```

### 無法連線資料庫

```bash
# 檢查容器是否運行
docker-compose ps

# 檢查資料庫日誌
docker logs mariadb

# 在 Laravel .env 中使用容器名稱作為 host
DB_HOST=mariadb
MONGODB_HOST=mongodb
```

### 檔案權限問題

```bash
# 進入容器修正權限
docker exec -it php bash
cd /var/www/html/專案名稱
chmod -R 775 storage bootstrap/cache
```

## 📚 更多資源

- [Laravel 官方文件](https://laravel.com/docs)
- [Docker 官方文件](https://docs.docker.com)
- [Docker Compose 文件](https://docs.docker.com/compose)
- [MariaDB 文件](https://mariadb.org/documentation)
- [MongoDB 文件](https://docs.mongodb.com)

## 📄 授權

MIT License

---

**最後更新**: 2026年1月9日
