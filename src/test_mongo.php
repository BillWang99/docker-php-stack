<?php
echo "<h1>MongoDB 連接測試</h1>";

try {
    // 使用 MongoDB 驅動程式
    $manager = new MongoDB\Driver\Manager("mongodb://root:root@mongodb:27017");
    
    // 列出資料庫
    $command = new MongoDB\Driver\Command(['listDatabases' => 1]);
    $cursor = $manager->executeCommand('admin', $command);
    $databases = current($cursor->toArray());
    
    echo "<h2>✅ MongoDB 連接成功</h2>";
    echo "<p><strong>MongoDB Driver 版本:</strong> " . phpversion('mongodb') . "</p>";
    echo "<p><strong>連接字串:</strong> mongodb://root:***@mongodb:27017</p>";
    
    echo "<h3>現有資料庫:</h3>";
    echo "<ul>";
    foreach ($databases->databases as $db) {
        echo "<li>{$db->name} ({$db->sizeOnDisk} bytes)</li>";
    }
    echo "</ul>";
    
    // 測試寫入
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->insert([
        'test' => 'Hello from PHP',
        'timestamp' => new MongoDB\BSON\UTCDateTime(),
        'random' => rand(1, 100)
    ]);
    
    $manager->executeBulkWrite('test_db.test_collection', $bulk);
    echo "<p>✅ 成功寫入測試資料到 test_db.test_collection</p>";
    
    // 測試讀取
    $query = new MongoDB\Driver\Query([]);
    $cursor = $manager->executeQuery('test_db.test_collection', $query);
    $count = count($cursor->toArray());
    echo "<p>✅ 成功讀取 {$count} 筆資料</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ MongoDB 連接失敗</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>MongoDB 擴充資訊:</h3>";
echo "<pre>";
$reflection = new ReflectionExtension('mongodb');
echo "版本: " . $reflection->getVersion() . "\n";
echo "</pre>";
?>
