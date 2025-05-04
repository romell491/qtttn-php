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

// Increment view count (still tracking but not displaying)
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

// Check if sponsor footer should be shown
// Show only if post is older than 2 hours AND viewer is not the author
$showSponsorFooter = false;
$currentIp = getClientIp();
$postIp = $post['ip_address'];
$postTime = strtotime($post['created_at']);
$twoHoursAgo = time() - (2 * 60 * 60); // 2 hours in seconds

if ($postTime < $twoHoursAgo && $currentIp !== $postIp) {
    $showSponsorFooter = true;
}

// Format content
$formattedContent = nl2brCustom($post['content']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($post['title']) ? htmlspecialchars($post['title']) : 'منصة قطن | اكتب وانشر'; ?></title>
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
        
        .author {
            margin-bottom: 0.5rem;
        }
        
        .publish-date {
            color: #666;
            font-size: 0.95rem;
        }
        
        .content-meta {
            color: #666;
            font-size: 0.95rem;
            margin: 1.5rem 0;
            display: none; /* Hidden for now */
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
        
        .sbpc-footer-section {
            margin: 10px 0;
            padding: 0;
            text-align: center;
        }
        .sbpc-footer-section p {
            margin: 5px 0;
            line-height: 1.5;
        }
        .sbpc-footer-section a {
            color: inherit;
            text-decoration: none;
        }
        .sbpc-footer-section a:hover {
            text-decoration: underline;
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
            
            <div class="publish-date">
                نُشر في: <?php echo $publishedDate; ?>
                <?php if ($post['expires_at']): ?>
                    • ينتهي في: <?php echo $expiresDate; ?>
                <?php endif; ?>
            </div>
        </header>
        
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
    
    <?php if ($showSponsorFooter): ?>
    <div class="sbpc-footer-section">
        <p id="sponsor-footer">الأداة مجانا بالكامل وبدون إعلانات مزعجة برعاية الرائعين: <span id="sponsor-links"></span></p>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const baseSponsors = [
                { name: "زد", url: "https://zid.link/4hTPipU" },
                { name: "كناري", url: "https://knaree.com/" },
                { name: "رمز", url: "https://rmmmz.com" },
                { name: "سيارة", url: "https://syarah.gotrackier.com/click?campaign_id=1&pub_id=2362" }
            ];
            const conflictGroup = [
                { name: "وسيط شراء", url: "https://wasetshera.com?myad=56761" },
                { name: "الشاري", url: "https://alshary.com?myad=58260" }
            ];
            
            // Select one random sponsor from conflict group
            const selectedConflict = conflictGroup[Math.floor(Math.random() * conflictGroup.length)];
            
            // Shuffle and select two random sponsors from base sponsors
            const shuffledBase = baseSponsors.sort(() => 0.5 - Math.random()).slice(0, 2);
            
            // Combine and shuffle final sponsor list
            const finalSponsors = [selectedConflict, ...shuffledBase].sort(() => 0.5 - Math.random());
            
            // Create HTML for sponsor links
            const linksHtml = finalSponsors.map(s => `<a href="${s.url}" target="_blank" rel="noopener noreferrer">${s.name}</a>`).join(" + ");
            
            // Update the HTML
            document.getElementById("sponsor-links").innerHTML = linksHtml;
        });
        </script>
    </div>
    <?php endif; ?>
    
    <script>
        // Detect iOS
        function isIOS() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        }
        
        // Copy link functionality
        document.getElementById('copy-btn').addEventListener('click', function() {
            const currentUrl = window.location.href;
            
            if (isIOS()) {
                // iOS-compatible approach
                const tempInput = document.createElement('input');
                tempInput.value = currentUrl;
                tempInput.style.position = 'absolute';
                tempInput.style.left = '-9999px';
                document.body.appendChild(tempInput);
                
                // Select and copy the text
                tempInput.select();
                tempInput.setSelectionRange(0, 99999); // For mobile
                
                try {
                    // Use execCommand for iOS compatibility
                    const successful = document.execCommand('copy');
                    
                    // Show success message
                    if (successful) {
                        const copySuccess = document.getElementById('copy-success');
                        copySuccess.style.display = 'inline';
                        
                        setTimeout(() => {
                            copySuccess.style.display = 'none';
                        }, 3000);
                    } else {
                        alert('يرجى نسخ الرابط يدويًا: اضغط مطولًا على النص وحدد "نسخ"');
                    }
                } catch (err) {
                    alert('يرجى نسخ الرابط يدويًا: اضغط مطولًا على النص وحدد "نسخ"');
                }
                
                // Clean up
                document.body.removeChild(tempInput);
            } else {
                // Modern browsers
                navigator.clipboard.writeText(currentUrl)
                    .then(() => {
                        const copySuccess = document.getElementById('copy-success');
                        copySuccess.style.display = 'inline';
                        
                        setTimeout(() => {
                            copySuccess.style.display = 'none';
                        }, 3000);
                    })
                    .catch(err => {
                        console.error('Could not copy text: ', err);
                        alert('يرجى نسخ الرابط يدويًا: ' + currentUrl);
                    });
            }
        });
    </script>
</body>
</html>