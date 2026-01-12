# 多螢幕即時同步功能設定指南

> 本指南教您如何在新的 Laravel 專案中設定多螢幕即時同步功能（適用於 POS、訂單管理、廚房顯示等系統）

## 📋 前置準備

確認 Docker 環境已包含以下服務：
- ✅ Redis (端口 6379)
- ✅ Laravel Reverb (端口 8081)
- ✅ Queue Worker

檢查服務狀態：
```bash
docker-compose ps
```

---

## 🚀 步驟 1: 安裝 Laravel Reverb

```bash
# 進入 PHP 容器
docker exec -it php bash

# 進入您的專案目錄
cd /var/www/html/your_project

# 安裝 Reverb
composer require laravel/reverb

# 發布配置檔案
php artisan reverb:install
```

執行後會：
- 創建 `config/reverb.php`
- 在 `.env` 添加 Reverb 相關設定
- 創建廣播路由

---

## ⚙️ 步驟 2: 配置環境變數 (.env)

```env
# 廣播驅動
BROADCAST_CONNECTION=reverb

# Redis 設定
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Reverb 設定（容器內部通訊）
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=your-secret-key-here
REVERB_APP_SECRET=your-secret-here
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite 前端設定（瀏覽器訪問）
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

**重要說明：**
- `REVERB_HOST=reverb` - 用於容器內 PHP 與 Reverb 通訊
- `VITE_REVERB_HOST=localhost` - 用於瀏覽器連接 WebSocket

---

## 📦 步驟 3: 安裝前端依賴

```bash
# 在專案目錄中
npm install --save-dev laravel-echo pusher-js
```

---

## 🔧 步驟 4: 配置前端 (resources/js/bootstrap.js)

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
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

在主要的 JavaScript 檔案中引入：
```javascript
// resources/js/app.js
import './bootstrap';
```

---

## 📡 步驟 5: 創建廣播事件

### 5.1 創建事件類別

```bash
php artisan make:event OrderUpdated
```

### 5.2 編輯事件 (app/Events/OrderUpdated.php)

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * 定義廣播頻道
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),              // 公開頻道
            // new PrivateChannel('orders.'.$this->order->id), // 私有頻道
        ];
    }

    /**
     * 自訂事件名稱
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    /**
     * 自訂廣播資料
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'status' => $this->order->status,
            'total' => $this->order->total,
            'items' => $this->order->items,
        ];
    }
}
```

---

## 🎯 步驟 6: 觸發事件

在您的控制器或服務中觸發事件：

```php
use App\Events\OrderUpdated;

// 建立或更新訂單後
$order = Order::create($data);

// 觸發廣播事件
event(new OrderUpdated($order));
// 或使用 broadcast() 輔助函數
broadcast(new OrderUpdated($order));
```

---

## 🖥️ 步驟 7: 前端監聽事件

### 7.1 在 Blade 模板中添加監聽

```html
<!-- resources/views/orders/index.blade.php -->
@extends('layouts.app')

@section('content')
<div id="orders-list">
    <!-- 訂單列表 -->
</div>

@push('scripts')
<script type="module">
// 監聽公開頻道
Echo.channel('orders')
    .listen('.order.updated', (e) => {
        console.log('訂單更新:', e);
        
        // 更新 UI
        updateOrderDisplay(e);
        
        // 顯示通知
        showNotification('新訂單: #' + e.id);
        
        // 播放音效
        playSound();
    });

function updateOrderDisplay(order) {
    // 更新畫面邏輯
    const orderElement = document.getElementById('order-' + order.id);
    if (orderElement) {
        // 更新現有訂單
        orderElement.innerHTML = renderOrder(order);
    } else {
        // 添加新訂單
        document.getElementById('orders-list').insertAdjacentHTML(
            'afterbegin', 
            renderOrder(order)
        );
    }
}

function showNotification(message) {
    // 顯示通知
    if (Notification.permission === 'granted') {
        new Notification(message);
    }
}

function playSound() {
    const audio = new Audio('/sounds/notification.mp3');
    audio.play();
}
</script>
@endpush
@endsection
```

---

## 🔒 步驟 8: 使用私有頻道（選用）

如果需要用戶專屬的頻道：

### 8.1 在事件中使用 PrivateChannel

```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('user.' . $this->userId),
    ];
}
```

### 8.2 配置授權 (routes/channels.php)

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

### 8.3 前端監聽私有頻道

```javascript
Echo.private('user.' + userId)
    .listen('.order.updated', (e) => {
        console.log('您的訂單更新:', e);
    });
```

---

## �️ 多 App 與多螢幕架構設計

### 頻道命名規範

為了區分不同 app 和同 app 內的不同事件，建議使用以下命名規範：

```
{app-name}.{feature}.{sub-feature}
```

**範例：**
```javascript
// POS 系統
'pos.orders'           // POS 訂單頻道
'pos.kitchen'          // POS 廚房顯示
'pos.customer-display' // POS 客戶顯示
'pos.inventory'        // POS 庫存更新
'pos.payment'          // POS 支付狀態

// 訂單管理系統
'order-management.orders'      // 訂單管理
'order-management.notifications' // 通知
'order-management.reports'      // 報表更新

// 庫存系統
'inventory.stock'      // 庫存更新
'inventory.alerts'     // 庫存警告
'inventory.transfers'  // 調撥通知
```

### 架構方案 1: 單一 Reverb + 多頻道（推薦）

**適用場景：** 所有 app 在同一個 Laravel 專案內

```yaml
# docker-compose.yml
services:
  reverb:
    build:
      context: ./php
    container_name: reverb
    command: php /var/www/html/your_project/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8081:8080"
```

**優點：**
- 簡單易管理
- 資源利用率高
- 單一連接點

**範例實作：**

```php
// app/Events/POS/OrderCreated.php
namespace App\Events\POS;

class OrderCreated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('pos.orders'),
            new Channel('pos.kitchen'),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'order.created';
    }
}

// app/Events/POS/CustomerDisplayUpdated.php
namespace App\Events\POS;

class CustomerDisplayUpdated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('pos.customer-display.' . $this->sessionId),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'display.updated';
    }
}

// app/Events/Inventory/StockUpdated.php
namespace App\Events\Inventory;

class StockUpdated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('inventory.stock'),
            new Channel('pos.inventory'), // POS 也需要知道
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'stock.updated';
    }
}
```

**前端監聽：**

```javascript
// POS 收銀端 (resources/js/pos/cashier.js)
Echo.channel('pos.orders')
    .listen('.order.created', (e) => {
        console.log('新訂單:', e);
        updateOrderList(e.order);
    });

Echo.channel('pos.inventory')
    .listen('.stock.updated', (e) => {
        console.log('庫存更新:', e);
        updateProductStock(e.productId, e.quantity);
    });

// POS 廚房端 (resources/js/pos/kitchen.js)
Echo.channel('pos.kitchen')
    .listen('.order.created', (e) => {
        console.log('廚房新單:', e);
        addToKitchenQueue(e.order);
        playAlertSound();
    });

// POS 客戶顯示 (resources/js/pos/customer-display.js)
const sessionId = getSessionId();
Echo.channel('pos.customer-display.' + sessionId)
    .listen('.display.updated', (e) => {
        console.log('顯示更新:', e);
        updateCustomerView(e.cart);
    });

// 庫存管理端 (resources/js/inventory/dashboard.js)
Echo.channel('inventory.stock')
    .listen('.stock.updated', (e) => {
        console.log('庫存變動:', e);
        updateInventoryDisplay(e);
    });

Echo.channel('inventory.alerts')
    .listen('.low.stock', (e) => {
        console.log('低庫存警告:', e);
        showAlert(e.product);
    });
```

### 架構方案 2: 多 Reverb 實例（獨立 App）

**適用場景：** 不同的 Laravel 專案需要各自的 WebSocket 服務

```yaml
# docker-compose.yml
services:
  # POS 系統的 Reverb
  reverb-pos:
    build:
      context: ./php
    container_name: reverb-pos
    command: php /var/www/html/pos_app/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8081:8080"
    environment:
      - REVERB_APP_ID=pos-system
    restart: unless-stopped

  # 訂單管理系統的 Reverb
  reverb-order:
    build:
      context: ./php
    container_name: reverb-order
    command: php /var/www/html/order_app/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8082:8080"
    environment:
      - REVERB_APP_ID=order-system
    restart: unless-stopped

  # 庫存系統的 Reverb
  reverb-inventory:
    build:
      context: ./php
    container_name: reverb-inventory
    command: php /var/www/html/inventory_app/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8083:8080"
    environment:
      - REVERB_APP_ID=inventory-system
    restart: unless-stopped
```

**各專案的 .env 配置：**

```env
# pos_app/.env
REVERB_HOST=reverb-pos
REVERB_PORT=8080
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081

# order_app/.env
REVERB_HOST=reverb-order
REVERB_PORT=8080
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8082

# inventory_app/.env
REVERB_HOST=reverb-inventory
REVERB_PORT=8080
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8083
```

**優點：**
- 完全隔離，互不影響
- 可以獨立擴展
- 故障隔離

**缺點：**
- 資源消耗較高
- 管理複雜度增加

### 架構方案 3: 混合架構（推薦用於複雜系統）

**適用場景：** 核心 app 共用一個 Reverb，特殊需求的 app 獨立運行

```yaml
services:
  # 主要的共用 Reverb
  reverb-main:
    container_name: reverb-main
    command: php /var/www/html/main_app/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8081:8080"

  # POS 系統專用（高頻率更新）
  reverb-pos:
    container_name: reverb-pos
    command: php /var/www/html/pos_app/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8082:8080"
```

### 跨 App 通訊範例

有時候不同 app 之間需要互相通知，可以使用以下方式：

**方案 1: 事件監聽器（同一個專案內）**

```php
// 庫存更新時，自動通知 POS
// app/Listeners/NotifyPOSOfStockChange.php
class NotifyPOSOfStockChange
{
    public function handle(StockUpdated $event)
    {
        broadcast(new POSInventoryUpdated($event->product));
    }
}
```

**方案 2: API 調用（不同專案）**

```php
// 庫存系統更新後，調用 POS API
Http::post('http://pos-system/api/inventory/updated', [
    'product_id' => $product->id,
    'quantity' => $product->quantity,
]);

// POS 系統接收後廣播
// app/Http/Controllers/API/InventoryController.php
public function updated(Request $request)
{
    broadcast(new POSInventoryUpdated($request->all()));
    return response()->json(['success' => true]);
}
```

### 實際應用範例：完整的 POS 系統

**目錄結構：**
```
app/
├── Events/
│   ├── POS/
│   │   ├── OrderCreated.php          # 訂單創建
│   │   ├── OrderUpdated.php          # 訂單更新
│   │   ├── PaymentProcessed.php      # 支付處理
│   │   ├── CustomerDisplayUpdated.php # 客戶顯示
│   │   └── KitchenOrderReceived.php  # 廚房接單
│   ├── Inventory/
│   │   ├── StockUpdated.php          # 庫存更新
│   │   ├── LowStockAlert.php         # 低庫存警告
│   │   └── TransferCompleted.php     # 調撥完成
│   └── Notification/
│       ├── SystemAlert.php           # 系統警告
│       └── UserMessage.php           # 用戶訊息
resources/
├── js/
│   ├── pos/
│   │   ├── cashier.js                # 收銀端
│   │   ├── kitchen.js                # 廚房端
│   │   ├── customer-display.js       # 客戶顯示
│   │   └── manager.js                # 管理端
│   └── inventory/
│       ├── dashboard.js              # 庫存儀表板
│       └── alerts.js                 # 警告監控
```

**收銀端實作 (resources/js/pos/cashier.js)：**

```javascript
// 初始化多個頻道監聽
const terminalId = getTerminalId();

// 監聽訂單更新
Echo.channel('pos.orders')
    .listen('.order.created', handleNewOrder)
    .listen('.order.updated', handleOrderUpdate)
    .listen('.order.cancelled', handleOrderCancel);

// 監聽庫存變化
Echo.channel('pos.inventory')
    .listen('.stock.updated', (e) => {
        updateProductAvailability(e.productId, e.quantity);
        if (e.quantity === 0) {
            disableProduct(e.productId);
            showNotification('商品已售罄: ' + e.productName);
        }
    })
    .listen('.low.stock', (e) => {
        showWarning('庫存不足: ' + e.productName);
    });

// 監聽支付狀態
Echo.channel('pos.payment.' + terminalId)
    .listen('.payment.processing', handlePaymentProcessing)
    .listen('.payment.completed', handlePaymentCompleted)
    .listen('.payment.failed', handlePaymentFailed);

// 監聽系統通知
Echo.channel('pos.system')
    .listen('.alert', (e) => {
        showSystemAlert(e.message, e.level);
    });

function handleNewOrder(e) {
    console.log('新訂單:', e.order);
    addToOrderQueue(e.order);
    playNotificationSound();
}

function handleOrderUpdate(e) {
    console.log('訂單更新:', e.order);
    updateOrderDisplay(e.order);
}
```

**廚房端實作 (resources/js/pos/kitchen.js)：**

```javascript
// 廚房只監聽相關頻道
Echo.channel('pos.kitchen')
    .listen('.order.new', (e) => {
        console.log('新單:', e.order);
        addToKitchenQueue(e.order);
        playAlertSound();
        highlightNewOrder();
    })
    .listen('.order.cancelled', (e) => {
        removeFromQueue(e.orderId);
        showNotification('訂單已取消: #' + e.orderId);
    })
    .listen('.order.priority', (e) => {
        markAsPriority(e.orderId);
        playUrgentSound();
    });

// 廚房完成訂單時，通知收銀端
function completeOrder(orderId) {
    axios.post('/api/pos/orders/' + orderId + '/complete')
        .then(() => {
            // 後端會廣播到 pos.orders 頻道
            removeFromQueue(orderId);
        });
}
```

**客戶顯示端實作 (resources/js/pos/customer-display.js)：**

```javascript
// 使用 session ID 確保只接收特定收銀機的訊息
const sessionId = new URLSearchParams(window.location.search).get('session');

Echo.channel('pos.customer-display.' + sessionId)
    .listen('.cart.updated', (e) => {
        console.log('購物車更新:', e.cart);
        displayCart(e.cart);
        displayTotal(e.total);
    })
    .listen('.payment.processing', () => {
        showPaymentAnimation();
    })
    .listen('.payment.completed', (e) => {
        showThankYou(e.change);
        setTimeout(resetDisplay, 5000);
    });
```

### 測試不同頻道

```bash
# 進入 tinker
php artisan tinker

# 測試 POS 訂單事件
broadcast(new App\Events\POS\OrderCreated($order));

# 測試庫存事件
broadcast(new App\Events\Inventory\StockUpdated($product));

# 測試客戶顯示事件
broadcast(new App\Events\POS\CustomerDisplayUpdated($sessionId, $cart));
```

### 監控和除錯

**查看特定 app 的連接：**

```bash
# 查看 POS Reverb 日誌
docker logs -f reverb-pos

# 查看訂單系統 Reverb 日誌
docker logs -f reverb-order
```

**前端除錯：**

```javascript
// 啟用詳細日誌
window.Echo.connector.pusher.connection.bind('state_change', function(states) {
    console.log('連接狀態:', states.current);
});

// 監聽所有事件（除錯用）
window.Echo.channel('pos.orders')
    .listenToAll((event, data) => {
        console.log('事件:', event, '資料:', data);
    });
```

### 效能優化建議

1. **合理規劃頻道數量**
   - 避免訂閱太多頻道（建議每個頁面 < 10 個）
   - 使用 Private/Presence Channel 時注意認證開銷

2. **使用頻道群組**
   ```javascript
   // 不好的做法：訂閱多個類似頻道
   Echo.channel('pos.terminal.1');
   Echo.channel('pos.terminal.2');
   Echo.channel('pos.terminal.3');
   
   // 好的做法：使用通配符或單一頻道
   Echo.channel('pos.terminals')
       .listen('.terminal.update', (e) => {
           if (e.terminalId === myTerminalId) {
               // 處理自己的更新
           }
       });
   ```

3. **事件資料優化**
   ```php
   // 只傳送必要資料
   public function broadcastWith(): array
   {
       return [
           'id' => $this->order->id,
           'status' => $this->order->status,
           'total' => $this->order->total,
           // 避免傳送大量嵌套資料
       ];
   }
   ```

4. **使用 Queue 處理廣播**
   ```php
   // 確保事件實作 ShouldBroadcast（自動使用 queue）
   class OrderCreated implements ShouldBroadcast
   {
       use SerializesModels;
   }
   ```

---

## �🏢 常見應用場景

### 場景 1: POS 多終端同步

```javascript
// 終端 A - 收銀機
Echo.channel('pos.terminal')
    .listen('.product.scanned', (e) => {
        addProductToCart(e.product);
    })
    .listen('.cart.cleared', (e) => {
        clearLocalCart();
    });

// 終端 B - 顯示螢幕
Echo.channel('pos.terminal')
    .listen('.product.scanned', (e) => {
        showProductAnimation(e.product);
    })
    .listen('.checkout.completed', (e) => {
        showThankYouMessage();
    });
```

### 場景 2: 廚房顯示系統 (KDS)

```javascript
// 廚房螢幕
Echo.channel('kitchen')
    .listen('.order.new', (e) => {
        addToKitchenQueue(e.order);
        playAlertSound();
    })
    .listen('.order.completed', (e) => {
        removeFromQueue(e.orderId);
    });
```

### 場景 3: 庫存即時更新

```javascript
// 所有 POS 終端
Echo.channel('inventory')
    .listen('.stock.updated', (e) => {
        updateProductStock(e.productId, e.quantity);
        if (e.quantity < 10) {
            showLowStockWarning(e.productId);
        }
    });
```

### 場景 4: 客戶顯示屏

```javascript
// 客戶可見螢幕
Echo.channel('customer.display.' + sessionId)
    .listen('.cart.updated', (e) => {
        displayCart(e.items);
        displayTotal(e.total);
    })
    .listen('.payment.processing', (e) => {
        showPaymentAnimation();
    });
```

---

## 🧪 測試步驟

### 1. 確認 Reverb 運行

```bash
# 查看 Reverb 日誌
docker logs -f reverb

# 應該看到類似：
# [2026-01-10 13:00:00] Reverb server started on 0.0.0.0:8080
```

### 2. 測試 WebSocket 連線

在瀏覽器控制台執行：
```javascript
console.log(window.Echo);
// 應該看到 Echo 實例
```

### 3. 測試事件觸發

```bash
# 進入 tinker
php artisan tinker

# 觸發測試事件
broadcast(new App\Events\OrderUpdated(['id' => 1, 'status' => 'pending']));
```

在瀏覽器控制台應該看到事件訊息。

---

## 📊 docker-compose.yml 配置（參考）

確認您的 docker-compose.yml 已包含以下服務：

```yaml
  reverb:
    build:
      context: ./php
    container_name: reverb
    command: php /var/www/html/your_project/artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=reverb
    volumes:
      - ./src:/var/www/html
    ports:
      - "8081:8080"
    depends_on:
      - redis
      - mariadb
    networks:
      - backend
    restart: unless-stopped
```

**注意：** 記得將 `your_project` 改為您的實際專案名稱。

---

## 🔄 更新專案配置

每次添加新專案時：

### 1. 更新 docker-compose.yml

修改 reverb 服務的 command 路徑：
```yaml
command: php /var/www/html/new_project/artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=reverb
```

### 2. 重啟 Reverb 服務

```bash
docker-compose restart reverb
```

或為多專案支援，可以建立多個 Reverb 實例：
```yaml
  reverb-pos:
    command: php /var/www/html/pos_project/artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8081:8080"

  reverb-kitchen:
    command: php /var/www/html/kitchen_project/artisan reverb:start --host=0.0.0.0 --port=8082
    ports:
      - "8082:8082"
```

---

## 🐛 常見問題排除

### 問題 1: 無法連接 WebSocket

**檢查：**
```bash
# 確認 Reverb 運行
docker ps | grep reverb

# 查看日誌
docker logs reverb

# 測試連線
curl http://localhost:8081/health
```

### 問題 2: 事件沒有廣播

**檢查：**
1. 確認事件實作 `ShouldBroadcast` 介面
2. 確認 `.env` 中 `BROADCAST_CONNECTION=reverb`
3. 檢查 Queue Worker 是否運行：`docker logs queue-worker`
4. 清除配置緩存：`php artisan config:clear`

### 問題 3: 前端無法接收事件

**檢查：**
1. 確認已引入 `bootstrap.js`
2. 確認 Echo 實例已建立：`console.log(window.Echo)`
3. 確認事件名稱一致（注意 `.` 前綴）
4. 開啟瀏覽器開發者工具的 Network 標籤，查看 WebSocket 連線狀態

### 問題 4: Redis 連線失敗

```bash
# 測試 Redis 連線
docker exec -it php redis-cli -h redis ping
# 應返回: PONG
```

---

## 📚 進階技巧

### 1. 使用 Presence Channels（在線用戶）

```php
// 事件
public function broadcastOn(): array
{
    return [
        new PresenceChannel('pos.terminal'),
    ];
}
```

```javascript
// 前端
Echo.join('pos.terminal')
    .here((users) => {
        console.log('當前在線:', users);
    })
    .joining((user) => {
        console.log(user.name + ' 上線了');
    })
    .leaving((user) => {
        console.log(user.name + ' 離線了');
    });
```

### 2. 條件性廣播

```php
public function broadcastWhen(): bool
{
    return $this->order->status === 'pending';
}
```

### 3. 廣播到特定連線

```php
broadcast(new OrderUpdated($order))->toOthers();
```

---

## ✅ 檢查清單

設定完成後，確認：

- [ ] Redis 服務運行中
- [ ] Reverb 服務運行中
- [ ] Queue Worker 服務運行中
- [ ] `.env` 配置正確
- [ ] 前端已安裝 `laravel-echo` 和 `pusher-js`
- [ ] `bootstrap.js` 已配置並引入
- [ ] 事件類別已創建並實作 `ShouldBroadcast`
- [ ] 前端監聽器已設置
- [ ] 可以在瀏覽器控制台看到 `window.Echo`
- [ ] 觸發事件後前端能接收到訊息

---

## 📖 相關文件

- [SERVICES_REFERENCE.md](SERVICES_REFERENCE.md) - Docker 服務參考
- [REALTIME_SETUP.md](REALTIME_SETUP.md) - 即時功能詳細配置
- [Laravel Broadcasting 官方文檔](https://laravel.com/docs/broadcasting)
- [Laravel Reverb 官方文檔](https://laravel.com/docs/reverb)

---

**提示：** 建議先在測試環境完整測試所有功能，確認運作正常後再部署到生產環境。
