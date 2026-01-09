<?php
// 掃描 Laravel 專案
function scanLaravelProjects($baseDir = '/var/www/html') {
    $projects = [];
    $items = scandir($baseDir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.DS_Store') continue;
        
        $path = $baseDir . '/' . $item;
        if (!is_dir($path)) continue;
        
        // 檢查是否為 Laravel 專案（存在 artisan 和 public/index.php）
        if (file_exists($path . '/artisan') && file_exists($path . '/public/index.php')) {
            $composerJson = $path . '/composer.json';
            $version = 'Unknown';
            $description = '';
            
            if (file_exists($composerJson)) {
                $composer = json_decode(file_get_contents($composerJson), true);
                if (isset($composer['require']['laravel/framework'])) {
                    $version = str_replace(['^', '~'], '', $composer['require']['laravel/framework']);
                }
                if (isset($composer['description'])) {
                    $description = $composer['description'];
                }
            }
            
            // 獲取最後修改時間
            $lastModified = filemtime($path . '/composer.json');
            
            $projects[] = [
                'name' => $item,
                'path' => $path,
                'version' => $version,
                'description' => $description,
                'url' => '/' . $item . '/',
                'lastModified' => $lastModified
            ];
        }
    }
    
    // 按最後修改時間排序（最新的在前）
    usort($projects, function($a, $b) {
        return $b['lastModified'] - $a['lastModified'];
    });
    
    return $projects;
}

// 測試資料庫連接
$services = [
    'mariadb' => ['name' => 'MariaDB', 'status' => false, 'icon' => 'fa-database', 'info' => ''],
    'mongodb' => ['name' => 'MongoDB', 'status' => false, 'icon' => 'fa-leaf', 'info' => ''],
    'php' => ['name' => 'PHP', 'status' => true, 'icon' => 'fa-code', 'info' => PHP_VERSION],
    'composer' => ['name' => 'Composer', 'status' => true, 'icon' => 'fa-box', 'info' => '']
];

try {
    $pdo = new PDO(
        "mysql:host=mariadb;dbname=app_db;charset=utf8mb4",
        "app_user",
        "secret"
    );
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $services['mariadb']['status'] = true;
    $services['mariadb']['info'] = explode('-', $version)[0];
} catch (Exception $e) {
    $services['mariadb']['status'] = false;
}

try {
    $manager = new MongoDB\Driver\Manager("mongodb://root:root@mongodb:27017");
    $command = new MongoDB\Driver\Command(['buildInfo' => 1]);
    $cursor = $manager->executeCommand('admin', $command);
    $buildInfo = current($cursor->toArray());
    $services['mongodb']['status'] = true;
    $services['mongodb']['info'] = $buildInfo->version ?? '';
} catch (Exception $e) {
    $services['mongodb']['status'] = false;
}

$projects = scanLaravelProjects();
$projectCount = count($projects);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker PHP Stack - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title h1 {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .header-title p {
            opacity: 0.9;
            font-size: 0.95em;
        }
        
        /* Services Status Bar */
        .services-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .service-status {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .service-status:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        
        .service-status i {
            font-size: 1.1em;
        }
        
        .service-status.active {
            border-left: 3px solid #10b981;
        }
        
        .service-status.inactive {
            border-left: 3px solid #ef4444;
            opacity: 0.7;
        }
        
        .service-name {
            font-weight: 500;
        }
        
        .service-version {
            font-size: 0.85em;
            opacity: 0.8;
            margin-left: 5px;
        }
        
        /* Main Content */
        .main-content {
            padding: 30px 0;
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .section-header h2 {
            font-size: 1.5em;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-header h2 i {
            color: #667eea;
        }
        
        .project-count {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        /* Search & Filter */
        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .project-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .project-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3em;
            flex-shrink: 0;
        }
        
        .project-info {
            flex: 1;
            margin-left: 15px;
        }
        
        .project-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .project-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85em;
            color: #6b7280;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meta-item i {
            color: #9ca3af;
        }
        
        .project-description {
            color: #6b7280;
            font-size: 0.9em;
            line-height: 1.5;
            margin-top: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .project-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85em;
            color: #9ca3af;
        }
        
        .view-link {
            color: #667eea;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }
        
        .empty-state i {
            font-size: 4em;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.3em;
            color: #4b5563;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .empty-state code {
            display: block;
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            color: #667eea;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
            overflow-x: auto;
        }
        
        /* Quick Links */
        .quick-links {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-top: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .quick-links h3 {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .quick-link {
            padding: 12px 15px;
            background: #f9fafb;
            border-radius: 8px;
            text-decoration: none;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .quick-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
        
        .quick-link i {
            width: 20px;
            text-align: center;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .no-results i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-title">
                    <h1>Docker PHP Stack</h1>
                    <p>Development Environment Dashboard</p>
                </div>
                <div class="services-bar">
                    <?php foreach ($services as $key => $service): ?>
                        <div class="service-status <?= $service['status'] ? 'active' : 'inactive' ?>">
                            <i class="fas <?= $service['icon'] ?>"></i>
                            <span class="service-name"><?= $service['name'] ?></span>
                            <?php if ($service['info']): ?>
                                <span class="service-version"><?= htmlspecialchars($service['info']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Projects Section -->
            <div class="section-header">
                <h2>
                    <i class="fas fa-rocket"></i>
                    Laravel Projects
                </h2>
                <?php if ($projectCount > 0): ?>
                    <span class="project-count"><?= $projectCount ?> Project<?= $projectCount > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($projectCount > 0): ?>
                <!-- Search & Filter -->
                <div class="controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search projects...">
                    </div>
                </div>
                
                <!-- Projects Grid -->
                <div class="projects-grid" id="projectsGrid">
                    <?php foreach ($projects as $project): ?>
                        <a href="<?= htmlspecialchars($project['url']) ?>" class="project-card" data-name="<?= htmlspecialchars(strtolower($project['name'])) ?>">
                            <div class="project-header">
                                <div class="project-icon">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <div class="project-info">
                                    <div class="project-name"><?= htmlspecialchars($project['name']) ?></div>
                                    <div class="project-meta">
                                        <span class="meta-item">
                                            <i class="fab fa-laravel"></i>
                                            <?= htmlspecialchars($project['version']) ?>
                                        </span>
                                        <span class="meta-item">
                                            <i class="far fa-clock"></i>
                                            <?= date('Y-m-d', $project['lastModified']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($project['description']): ?>
                                <div class="project-description"><?= htmlspecialchars($project['description']) ?></div>
                            <?php endif; ?>
                            <div class="project-footer">
                                <span>Click to open</span>
                                <span class="view-link">
                                    View <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="no-results" id="noResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <p>No projects found matching your search.</p>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Projects Yet</h3>
                    <p>Create your first Laravel project to get started:</p>
                    <code>docker exec -it php composer create-project laravel/laravel project_name</code>
                </div>
            <?php endif; ?>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <h3>Quick Links</h3>
                <div class="links-grid">
                    <a href="/info.php" class="quick-link">
                        <i class="fas fa-info-circle"></i>
                        PHP Info
                    </a>
                    <a href="/test_mongo.php" class="quick-link">
                        <i class="fas fa-leaf"></i>
                        MongoDB Test
                    </a>
                    <a href="http://localhost:3306" class="quick-link">
                        <i class="fas fa-database"></i>
                        MariaDB (3306)
                    </a>
                    <a href="http://localhost:27017" class="quick-link">
                        <i class="fas fa-server"></i>
                        MongoDB (27017)
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const projectCards = document.querySelectorAll('.project-card');
        const noResults = document.getElementById('noResults');
        const projectsGrid = document.getElementById('projectsGrid');
        
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                let visibleCount = 0;
                
                projectCards.forEach(card => {
                    const projectName = card.getAttribute('data-name');
                    if (projectName.includes(searchTerm)) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                if (visibleCount === 0) {
                    noResults.style.display = 'block';
                    projectsGrid.style.display = 'none';
                } else {
                    noResults.style.display = 'none';
                    projectsGrid.style.display = 'grid';
                }
            });
        }
    </script>
</body>
</html>
