# 此目錄存放各專案的 VirtualHost 配置檔案
# 每個專案一個獨立的 .conf 檔案

## 使用方式

1. 複製範例檔案：
   ```
   cp example.conf your-project.conf
   ```

2. 編輯配置檔案，修改以下內容：
   - ServerName
   - DocumentRoot
   - Directory 路徑

3. 更新 /etc/hosts：
   ```
   sudo sh -c 'echo "127.0.0.1 your-project.localhost" >> /etc/hosts'
   ```

4. 重啟 Apache：
   ```
   docker-compose restart apache
   ```

## 或使用自動化腳本

```bash
./add-project.sh your-project
```
