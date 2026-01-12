# 即時功能配置指南

## 新增的服務

### 1. Redis
- **用途**: 緩存、會話管理、消息隊列
- **端口**: 6379
- **連線**: `redis://redis:6379`

### 2. Laravel Reverb (WebSocket 服務)
- **用途**: 即時通訊、多畫面同步
- **端口**: 8081
- **連線**: `ws://localhost:8081`

### 3. Queue Worker
- **用途**: 處理後台任務、非同步處理
- **自動重啟**: 是

## Laravel 專案配置

### 1. 安裝依賴

在您的 Laravel 專案中執行：

```bash
# 進入專案目錄
docker exec -it php bash
cd /var/www/html/broadcast_app

# 安裝 Laravel Reverb
composer require laravel/reverb

# 發布配置
php artisan reverb:install

# 安裝前端依賴
npm install --save-dev laravel-echo pusher-js
```

### 2. 環境變數配置 (.env)

```env
# 廣播設定
BROADCAST_CONNECTION=reverb

# Redis 設定
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Reverb 設定
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

### 3. 前端配置 (resources/js/bootstrap.js)

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

## POS 系統常用功能範例

### 1. 訂單即時通知

```php
// app/Events/OrderCreated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
            new Channel('kitchen'),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
```

### 2. 前端監聽

```javascript
// 監聽訂單頻道
Echo.channel('orders')
    .listen('.order.created', (e) => {
        console.log('新訂單:', e.order);
        // 更新畫面、播放音效等
    });

// 監聽廚房頻道
Echo.channel('kitchen')
    .listen('.order.created', (e) => {
        // 廚房顯示畫面更新
    });
```

### 3. 多終端同步範例

```javascript
// 庫存更新
Echo.channel('inventory')
    .listen('.stock.updated', (e) => {
        // 所有 POS 終端同步庫存
    });

// 價格變更
Echo.channel('prices')
    .listen('.price.changed', (e) => {
        // 所有終端同步價格
    });

// 桌位狀態
Echo.channel('tables')
    .listen('.table.updated', (e) => {
        // 更新桌位狀態
    });
```

## 啟動服務

```bash
# 啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps

# 查看 Reverb 日誌
docker logs -f reverb

# 查看 Queue Worker 日誌
docker logs -f queue-worker
```

## 測試連線

1. 確認 Redis 連線：
```bash
docker exec -it php bash
redis-cli -h redis ping
# 應該返回 PONG
```

2. 測試 WebSocket：
```bash
# 訪問 Reverb 健康檢查
curl http://localhost:8081/health
```

## 效能優化建議

1. **Redis 持久化**: 已啟用 AOF (Append Only File)
2. **Queue Worker**: 使用 Supervisor 管理多個 worker (生產環境)
3. **連線池**: 配置適當的 Redis 連線池大小
4. **消息分類**: 不同類型的消息使用不同的 Queue

## 故障排除

### Reverb 無法啟動
1. 確認 Redis 服務正常運行
2. 檢查端口 8081 是否被占用
3. 查看日誌：`docker logs reverb`

### Queue 任務不執行
1. 確認 QUEUE_CONNECTION 設定為 redis
2. 檢查 worker 是否運行：`docker ps | grep queue-worker`
3. 查看日誌：`docker logs queue-worker`

### 前端無法連線
1. 確認 VITE_REVERB_HOST 和 PORT 設定正確
2. 檢查瀏覽器控制台錯誤
3. 確認防火牆未阻擋 8081 端口
