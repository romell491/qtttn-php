<?php
/**
 * Admin panel for content moderation
 */

// Include shared functions and rate limiting
require_once 'functions.php';
require_once 'rate_limit.php';

// Start session
startSession();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Get admin username
$adminUsername = $_SESSION['admin_username'];

// Process logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Log logout action
    logAdminAction($adminUsername, 'logout', 'Admin logout');
    
    // Destroy session
    session_destroy();
    
    // Redirect to login page
    header('Location: admin_login.php');
    exit;
}

// Process content delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $urlId = sanitizeInput($_GET['id']);
    
    // Get post details for logging
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT title FROM content WHERE url_id = ?");
    $stmt->execute([$urlId]);
    $post = $stmt->fetch();
    
    // Delete post
    $stmt = $db->prepare("DELETE FROM content WHERE url_id = ?");
    $stmt->execute([$urlId]);
    
    // Log action
    logAdminAction($adminUsername, 'delete_post', "Deleted post: " . ($post ? $post['title'] : $urlId));
    
    // Redirect back to admin panel
    header('Location: admin.php?deleted=1');
    exit;
}

// Process IP ban
if (isset($_GET['action']) && $_GET['action'] === 'ban' && isset($_GET['ip'])) {
    $ip = sanitizeInput($_GET['ip']);
    $reason = "Banned by admin";
    
    // Ban IP
    banIp($ip, $reason, $adminUsername);
    
    // Redirect back to admin panel
    header('Location: admin.php?banned=1');
    exit;
}

// Process IP unban
if (isset($_GET['action']) && $_GET['action'] === 'unban' && isset($_GET['ip'])) {
    $ip = sanitizeInput($_GET['ip']);
    
    // Unban IP
    unbanIp($ip, $adminUsername);
    
    // Redirect back to admin panel
    header('Location: admin.php?unbanned=1');
    exit;
}

// Get all posts
function getPosts($limit = 50, $offset = 0) {
    $db = getDbConnection();
    $stmt = $db->prepare("
        SELECT * FROM content 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    
    return $stmt->fetchAll();
}

// Get posts count
function getPostsCount() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM content");
    
    return $stmt->fetchColumn();
}

// Set current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$postsPerPage = 20;
$offset = ($page - 1) * $postsPerPage;

// Get posts for current page
$posts = getPosts($postsPerPage, $offset);
$totalPosts = getPostsCount();
$totalPages = ceil($totalPosts / $postsPerPage);

// Get banned IPs
$bannedIps = getBannedIps();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - منصة النشر العربية</title>
    <!-- IBM Plex Sans Arabic font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #ecf0f1;
            --accent-color: #3498db;
            --text-color: #333;
            --light-gray: #f9f9f9;
            --border-color: #ddd;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--secondary-color);
            padding: 0;
            margin: 0;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: var(--light-gray);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: var(--light-gray);
        }
        
        .truncate {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-sm {
            padding: 0.3rem 0.7rem;
            font-size: 0.8rem;
        }
        
        .btn-danger {
            background-color: var(--error-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #d5f5e3;
            color: #27ae60;
            border: 1px solid #a9dfbf;
        }
        
        .alert-warning {
            background-color: #fef9e7;
            color: #f39c12;
            border: 1px solid #fdebd0;
        }
        
        .alert-danger {
            background-color: #fadbd8;
            color: #e74c3c;
            border: 1px solid #f5b7b1;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 4px;
            background-color: white;
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .pagination a:hover {
            background-color: var(--light-gray);
        }
        
        .pagination .active {
            background-color: var(--accent-color);
            color: white;
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .tab-container {
            margin-bottom: 2rem;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: var(--accent-color);
            color: var(--accent-color);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .logout-btn {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        
        .logout-btn:hover {
            opacity: 0.8;
        }
        
        .copy-btn {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0;
            text-decoration: underline;
        }
        
        .copy-btn:hover {
            color: #2980b9;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header .btn {
                margin-top: 1rem;
            }
            
            .tab {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>لوحة الإدارة</h1>
        <a href="admin.php?action=logout" class="logout-btn">تسجيل الخروج</a>
    </header>
    
    <div class="container">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                تم حذف المنشور بنجاح.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['banned'])): ?>
            <div class="alert alert-warning">
                تم حظر عنوان IP بنجاح.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['unbanned'])): ?>
            <div class="alert alert-success">
                تم إلغاء حظر عنوان IP بنجاح.
            </div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="recent-posts">المنشورات الأخيرة</div>
                <div class="tab" data-tab="banned-ips">عناوين IP المحظورة</div>
            </div>
            
            <div class="tab-content active" id="recent-posts">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">المنشورات الأخيرة</h2>
                    </div>
                    
                    <div class="table-container">
                        <?php if (!empty($posts)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>الكاتب</th>
                                        <th>المحتوى</th>
                                        <th>عنوان IP</th>
                                        <th>تاريخ النشر</th>
                                        <th>المشاهدات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><?php echo !empty($post['title']) ? htmlspecialchars($post['title']) : '<em>بدون عنوان</em>'; ?></td>
                                            <td><?php echo !empty($post['author']) ? htmlspecialchars($post['author']) : '<em>مجهول</em>'; ?></td>
                                            <td class="truncate"><?php echo htmlspecialchars($post['content']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($post['ip_address']); ?>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo $post['ip_address']; ?>')">نسخ</button>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                                            <td><?php echo $post['views']; ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $post['url_id']; ?>" class="btn btn-sm" target="_blank">عرض</a>
                                                <a href="admin.php?action=delete&id=<?php echo $post['url_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo $ui_text['confirm_delete']; ?>')">حذف</a>
                                                <a href="admin.php?action=ban&ip=<?php echo $post['ip_address']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo $ui_text['confirm_ban']; ?>')">حظر IP</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="admin.php?page=<?php echo $page - 1; ?>">&laquo; السابق</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="active"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="admin.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="admin.php?page=<?php echo $page + 1; ?>">التالي &raquo;</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-results">
                                لا توجد منشورات.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="tab-content" id="banned-ips">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">عناوين IP المحظورة</h2>
                    </div>
                    
                    <div class="table-container">
                        <?php if (!empty($bannedIps)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>عنوان IP</th>
                                        <th>تاريخ الحظر</th>
                                        <th>بواسطة</th>
                                        <th>السبب</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bannedIps as $ip): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($ip['ip_address']); ?>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo $ip['ip_address']; ?>')">نسخ</button>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($ip['banned_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($ip['banned_by']); ?></td>
                                            <td><?php echo htmlspecialchars($ip['reason']); ?></td>
                                            <td>
                                                <a href="admin.php?action=unban&ip=<?php echo $ip['ip_address']; ?>" class="btn btn-sm btn-success">إلغاء الحظر</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-results">
                                لا توجد عناوين IP محظورة.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('تم نسخ النص: ' + text);
            });
        }
    </script>
</body>
</html>