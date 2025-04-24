<?php
/**
 * Process content submission
 */

// Include shared functions and rate limiting
require_once 'functions.php';
require_once 'rate_limit.php';

// Set content type to JSON
header('Content-Type: application/json');

// Response array
$response = [
    'success' => false,
    'errors' => [],
    'message' => ''
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'طريقة الطلب غير صالحة';
    echo json_encode($response);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $response['message'] = $error_messages['invalid_token'];
    echo json_encode($response);
    exit;
}

// Check rate limit
$rateLimitCheck = checkRateLimit();
if ($rateLimitCheck !== true) {
    $response['message'] = $rateLimitCheck;
    echo json_encode($response);
    exit;
}

// Get and sanitize input
$title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
$author = isset($_POST['author']) ? sanitizeInput($_POST['author']) : '';
$content = isset($_POST['content']) ? sanitizeInput($_POST['content']) : '';
$expiration = isset($_POST['expiration']) ? $_POST['expiration'] : '1_month';

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

$expirationValidation = validateExpiration($expiration);
if ($expirationValidation !== true) {
    $response['errors']['expiration'] = $expirationValidation;
}

// If there are validation errors, return them
if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

// Generate URL ID and edit token
$urlId = generateUrlId();
$editToken = generateEditToken();

// Get client IP
$ip = getClientIp();

// Calculate expiration time
global $expiration_options;
$expiresAt = null;

if ($expiration !== 'forever' && isset($expiration_options[$expiration])) {
    $expiresAt = date('Y-m-d H:i:s', time() + $expiration_options[$expiration]);
}

try {
    // Get database connection
    $db = getDbConnection();
    
    // Insert content into database
    $stmt = $db->prepare("
        INSERT INTO content 
        (url_id, title, author, content, ip_address, expires_at, edit_token) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $urlId,
        $title,
        $author,
        $content,
        $ip,
        $expiresAt,
        $editToken
    ]);
    
    // Build content URL
    $contentUrl = BASE_URL . 'view.php?id=' . $urlId;
    
    // Set success response
    $response['success'] = true;
    $response['url'] = $contentUrl;
    $response['edit_url'] = BASE_URL . 'edit.php?id=' . $urlId . '&token=' . $editToken;
    
    // Store edit token in session for 1 hour
    $_SESSION['edit_tokens'][$urlId] = [
        'token' => $editToken,
        'expires' => time() + 3600 // 1 hour
    ];
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $response['message'] = $error_messages['database_error'];
}

// Return response
echo json_encode($response);
?>