<?php
/**
 * Edit published content
 */

// Include shared functions
require_once 'functions.php';

// Response array for AJAX requests
$response = [
    'success' => false,
    'errors' => [],
    'message' => ''
];

// Function to check if edit is allowed
function canEditPost($post, $token) {
   // return true;
    // Check if post exists
    if (!$post) {
        return false;
    }
    
    // Check if edit token matches
   if ($post['edit_token'] !== $token) {
       return false;
   }
 //  echo $post['expires_at'];
//   exit;
//   return true;
    
    // Check if edit period has expired
    if($post['expires_at'] != ''){
    $editExpires = new DateTime($post['expires_at']);
    $now = new DateTime();
    
    
    $editExpires = strtotime($editExpires->format('Y-m-d H:i:s'));
    $now = strtotime($now->format('Y-m-d H:i:s')); 
    
    
   if ($now > $editExpires) {
        return false;
   }
    }  
    return true;
}

// Process edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $response['message'] = $error_messages['invalid_token'];
        echo json_encode($response);
        exit;
    }
    
    // Get and sanitize input
    $urlId = isset($_POST['id']) ? sanitizeInput($_POST['id']) : '';
    $token = isset($_POST['token']) ? sanitizeInput($_POST['token']) : '';
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $author = isset($_POST['author']) ? sanitizeInput($_POST['author']) : '';
    $content = isset($_POST['content']) ? sanitizeInput($_POST['content']) : '';
    
    // Get post from database
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM content WHERE url_id = ?");
    $stmt->execute([$urlId]);
    $post = $stmt->fetch();
    
    // Check if edit is allowed
    if (!canEditPost($post, $token)) {
        $response['message'] = $error_messages['edit_expired'];
        echo json_encode($response);
        exit;
    }
    
    // Validate input
    $titleValidation = validateTitle($title);
    if ($titleValidation !== true) {
        $response['errors']['title'] = $titleValidation;
    }
    
    $authorValidation = validateAuthor($author);
    if ($authorValidation !== true) {
        $response['errors']['author'] = $authorValidation;
    }
    
    $contentValidation = validateContent($content);
    if ($contentValidation !== true) {
        $response['errors']['content'] = $contentValidation;
    }
    
    // If there are validation errors, return them
    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }
    
    try {
        // Update content in database
        $stmt = $db->prepare("
            UPDATE content 
            SET title = ?, author = ?, content = ? 
            WHERE url_id = ?
        ");
        
        $stmt->execute([
            $title,
            $author,
            $content,
            $urlId
        ]);
        
        // Build content URL
        $contentUrl = BASE_URL . 'view.php?id=' . $urlId;
        
        // Set success response
        $response['success'] = true;
        $response['url'] = $contentUrl;
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response['message'] = $error_messages['database_error'];
    }
    
    // Return response
    echo json_encode($response);
    exit;
}

// Display edit form
// Check if ID and token are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: index.html');
    exit;
}

// Get URL ID and token
$urlId = sanitizeInput($_GET['id']);
$token = sanitizeInput($_GET['token']);

// Get post from database
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM content WHERE url_id = ?");
$stmt->execute([$urlId]);
$post = $stmt->fetch();

// Check if edit is allowed
if (!canEditPost($post, $token)) {
    header('Location: view.php?id=' . $urlId);
    exit;
}

// Calculate remaining edit time
if($post['expires_at'] !=  ''){
$editExpires = new DateTime($post['expires_at']);
$now = new DateTime();
    $editExpires = strtotime($editExpires->format('Y-m-d H:i:s'));
    $now = strtotime($now->format('Y-m-d H:i:s')); 

// $timeLeft = $now->diff($editExpires);

$minutesLeft = $editExpires - $now;

$minutesLeft = round($minutesLeft / 60);

// $minutesLeft = ($timeLeft->h * 60) + $timeLeft->i;
}else
$minutesLeft = 10000000;
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المنشور - منصة النشر العربية</title>
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
        
        h1 {
            font-size: 1.2rem;
            font-weight: 400;
            color: #444;
            letter-spacing: 0.5px;
        }
        
        .container {
            padding: 1rem 2rem;
        }
        
        .time-warning {
            text-align: center;
            padding: 0.8rem;
            margin: 1rem 0;
            background-color: #f9f9f9;
            border: 1px solid #f0f0f0;
        }
        
        .time-warning strong {
            font-weight: 500;
        }
        
        .content-form {
            padding: 1rem 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 0.8rem 0;
            border: none;
            border-bottom: 1px solid #eee;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 1rem;
            background: transparent;
        }
        
        input[type="text"]::placeholder, textarea::placeholder {
            color: #aaa;
        }
        
        textarea {
            min-height: 250px;
            resize: vertical;
        }
        
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-bottom-color: #000;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 3rem;
        }
        
        .btn {
            display: inline-block;
            background-color: #000;
            color: white;
            padding: 0.7rem 1.4rem;
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
        
        .success-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #000;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 100;
        }
        
        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
    <header>
        <h1>تعديل المنشور</h1>
    </header>
    
    <div class="container">
        <div class="success-message" id="success-message">
            تم تعديل المحتوى بنجاح!
        </div>
        
        <div class="time-warning">
            <strong>تنبيه:</strong> يمكنك تعديل هذا المنشور لمدة <?php echo $minutesLeft; ?> دقيقة فقط.
        </div>
        
        <div class="content-form">
            <form id="edit-form">
                <div class="form-group">
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" placeholder="العنوان">
                    <div class="error" id="title-error"></div>
                </div>
                
                <div class="form-group">
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($post['author']); ?>" placeholder="الكاتب">
                    <div class="error" id="author-error"></div>
                </div>
                
                <div class="form-group">
                    <textarea id="content" name="content" required placeholder="اكتب محتواك هنا..."><?php echo htmlspecialchars($post['content']); ?></textarea>
                    <div class="error" id="content-error"></div>
                </div>
                
                <input type="hidden" name="csrf_token" id="csrf_token" value="">
                <input type="hidden" name="id" value="<?php echo $urlId; ?>">
                <input type="hidden" name="token" value="<?php echo $token; ?>">
                
                <div class="form-actions">
                    <a href="view.php?id=<?php echo $urlId; ?>" class="btn btn-secondary">إلغاء</a>
                    <button type="submit" class="btn">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>منصة النشر العربية</p>
    </footer>
    
    <script>
        // Get CSRF token
        fetch('get_token.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('csrf_token').value = data.token;
            });
        
        // Form submission
        document.getElementById('edit-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const title = document.getElementById('title').value;
            const author = document.getElementById('author').value;
            const content = document.getElementById('content').value;
            
            const titleError = document.getElementById('title-error');
            const authorError = document.getElementById('author-error');
            const contentError = document.getElementById('content-error');
            
            // Reset error messages
            titleError.textContent = '';
            authorError.textContent = '';
            contentError.textContent = '';
            
            // Validate content
            if (!content || content.trim().length < 10) {
                contentError.textContent = 'المحتوى قصير جدًا، يجب أن يكون 10 أحرف على الأقل';
                return;
            }
            
            if (content.length > 50000) {
                contentError.textContent = 'المحتوى طويل جدًا، يجب أن يكون أقل من 50000 حرف';
                return;
            }
            
            // Submit form
            const formData = new FormData(this);
            
            fetch('edit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successMessage = document.getElementById('success-message');
                    successMessage.style.display = 'block';
                    
                    // Scroll to top to show success message
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = data.url;
                    }, 2000);
                } else {
                    // Show error messages
                    if (data.errors.title) {
                        titleError.textContent = data.errors.title;
                    }
                    
                    if (data.errors.author) {
                        authorError.textContent = data.errors.author;
                    }
                    
                    if (data.errors.content) {
                        contentError.textContent = data.errors.content;
                    }
                    
                    if (data.message) {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تعديل المحتوى، يرجى المحاولة مرة أخرى');
            });
        });
    </script>
</body>
</html>