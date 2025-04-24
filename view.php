<?php
/**
 * Display published content
 */

// Include shared functions
require_once 'functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.html');
    exit;
}

// Get URL ID
$urlId = sanitizeInput($_GET['id']);

// Get post from database
$post = getPostByUrlId($urlId);

// If post not found or expired, redirect to home
if (!$post) {
    header('Location: index.html');
    exit;
}

// Increment view count
incrementViewCount($urlId);

// Format dates
$publishedDate = formatArabicDate($post['created_at']);
$expiresDate = $post['expires_at'] ? formatArabicDate($post['expires_at']) : 'بلا انتهاء';

// Check if edit is still allowed
$canEdit = false;
$editUrl = '';

if (isset($_SESSION['edit_tokens'][$urlId])) {
    $tokenData = $_SESSION['edit_tokens'][$urlId];
    if (time() < $tokenData['expires']) {
        $canEdit = true;
        $editUrl = 'edit.php?id=' . $urlId . '&token=' . $tokenData['token'];
    }
}

// Format content
$formattedContent = nl2brCustom($post['content']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($post['title']) ? htmlspecialchars($post['title']) : 'منصة النشر العربية'; ?></title>
    <!-- IBM Plex Sans Arabic font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            line-height: 1.6;
            color: #000;
            background-color: #fff;
            padding: 0;
            margin: 0 auto;
            max-width: 800px;
        }
        
        header {
            text-align: center;
            padding: 2rem 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .content-meta {
            color: #666;
            font-size: 0.95rem;
            margin: 1.5rem 0;
        }
        
        .container {
            padding: 1rem 2rem;
        }
        
        .content-body {
            font-size: 1.1rem;
            line-height: 1.8;
            margin: 2rem 0;
        }
        
        .content-body p {
            margin-bottom: 1.5rem;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn {
            display: inline-block;
            background-color: #000;
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 0.9rem;
            font-weight: 400;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #333;
        }
        
        .btn-secondary {
            background-color: transparent;
            color: #000;
            border: 1px solid #000;
        }
        
        .btn-secondary:hover {
            background-color: #f5f5f5;
        }
        
        .success-copy {
            color: #000;
            margin-left: 1rem;
            font-size: 0.9rem;
            display: none;
        }
        
        footer {
            text-align: center;
            padding: 2rem 1rem;
            color: #999;
            font-size: 0.8rem;
            border-top: 1px solid #f0f0f0;
            margin-top: 3rem;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <?php if (!empty($post['title'])): ?>
                <h1 class="title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <?php endif; ?>
            
            <?php if (!empty($post['author'])): ?>
                <div class="author"><?php echo htmlspecialchars($post['author']); ?></div>
            <?php endif; ?>
        </header>
        
        <div class="content-meta">
            <div>
                <span>نُشر في: <?php echo $publishedDate; ?></span>
                <?php if ($post['expires_at']): ?>
                    <span> • ينتهي في: <?php echo $expiresDate; ?></span>
                <?php endif; ?>
                <span> • المشاهدات: <?php echo $post['views']; ?></span>
            </div>
        </div>
        
        <div class="content-body">
            <?php echo $formattedContent; ?>
        </div>
        
        <div class="actions">
            <div>
                <button class="btn btn-secondary" id="copy-btn">نسخ الرابط</button>
                <span class="success-copy" id="copy-success">تم نسخ الرابط!</span>
            </div>
            
            <div>
                <?php if ($canEdit): ?>
                    <a href="<?php echo $editUrl; ?>" class="btn">تعديل</a>
                <?php endif; ?>
                <a href="index.html" class="btn btn-secondary">نص جديد</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>منصة النشر العربية</p>
    </footer>
    
    <script>
        // Copy link functionality
        document.getElementById('copy-btn').addEventListener('click', function() {
            const currentUrl = window.location.href;
            
            navigator.clipboard.writeText(currentUrl).then(() => {
                const copySuccess = document.getElementById('copy-success');
                copySuccess.style.display = 'inline';
                
                setTimeout(() => {
                    copySuccess.style.display = 'none';
                }, 3000);
            });
        });
    </script>
</body>
</html>