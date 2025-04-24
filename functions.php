<?php
/**
 * Shared utility functions for Arabic Content Publishing Platform
 */

// Include configuration file
require_once 'config.php';

/**
 * Create a database connection using PDO
 * @return PDO Database connection object
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log error but don't expose details to users
        error_log('Database connection error: ' . $e->getMessage());
        die($GLOBALS['error_messages']['database_error']);
    }
}

/**
 * Generate a random URL ID
 * @param int $length Length of the ID
 * @return string Random URL ID
 */
function generateUrlId($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $id = '';
    
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    
    // Check if ID already exists in database
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM content WHERE url_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetchColumn() > 0) {
        // ID already exists, generate a new one
        return generateUrlId($length);
    }
    
    return $id;
}

/**
 * Generate a random token for editing
 * @return string Random token
 */
function generateEditToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Sanitize user input
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if the title is valid
 * @param string $title Post title
 * @return bool|string True if valid, error message if invalid
 */
function validateTitle($title) {
    if (mb_strlen($title, 'UTF-8') > MAX_TITLE_LENGTH) {
        return $GLOBALS['error_messages']['title_too_long'];
    }
    
    return true;
}

/**
 * Check if the author name is valid
 * @param string $author Author name
 * @return bool|string True if valid, error message if invalid
 */
function validateAuthor($author) {
    if (mb_strlen($author, 'UTF-8') > MAX_AUTHOR_LENGTH) {
        return $GLOBALS['error_messages']['author_too_long'];
    }
    
    return true;
}

/**
 * Check if the content is valid
 * @param string $content Post content
 * @return bool|string True if valid, error message if invalid
 */
function validateContent($content) {
    $contentLength = mb_strlen($content, 'UTF-8');
    
    if (empty($content)) {
        return $GLOBALS['error_messages']['content_required'];
    }
    
    if ($contentLength < MIN_CONTENT_LENGTH) {
        return $GLOBALS['error_messages']['content_too_short'];
    }
    
    if ($contentLength > MAX_CONTENT_LENGTH) {
        return $GLOBALS['error_messages']['content_too_long'];
    }
    
    return true;
}

/**
 * Check if the expiration option is valid
 * @param string $expiration Expiration option
 * @return bool|string True if valid, error message if invalid
 */
function validateExpiration($expiration) {
    global $expiration_options;
    
    if (!array_key_exists($expiration, $expiration_options)) {
        return $GLOBALS['error_messages']['invalid_expiration'];
    }
    
    return true;
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Format date in Arabic
 * @param string $timestamp Timestamp
 * @return string Formatted date
 */
 /*
function formatArabicDate($timestamp) {
    // Set locale to Arabic
    setlocale(LC_TIME, 'ar_SA.UTF-8');
    
    // Convert timestamp to DateTime
    $date = new DateTime($timestamp);
    
    // Format date
    return strftime('%d %B %Y %H:%M', $date->getTimestamp());
}
*/


/**
 * Format date in Arabic
 * @param string $timestamp Timestamp
 * @return string Formatted date
 */
function formatArabicDate($timestamp) {
    // Convert timestamp to DateTime
    $date = new DateTime($timestamp);
    
    // Define Arabic month names
    $arabicMonths = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
    
    // Format date using date parts
    $day = $date->format('d');
    $month = $arabicMonths[(int)$date->format('n')];
    $year = $date->format('Y');
    $time = $date->format('H:i');
    
    // Return formatted date string
    return $day . ' ' . $month . ' ' . $year . ' ' . $time;
}


/**
 * Check if the current IP is banned
 * @param string $ip IP address
 * @return bool True if banned
 */
function isIpBanned($ip) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?");
    $stmt->execute([$ip]);
    
    return ($stmt->fetchColumn() > 0);
}

/**
 * Get the client's IP address
 * @return string IP address
 */
function getClientIp() {
    // Check for proxy forwarded IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, get the first one
        $ip_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_array[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP format
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    // Default to localhost if no valid IP found
    return '127.0.0.1';
}

/**
 * Check if an IP is from Saudi Arabia based on prefixes
 * @param string $ip IP address
 * @return bool True if from Saudi Arabia
 */
function isSaudiIp($ip) {
    global $saudi_prefixes;
    
    foreach ($saudi_prefixes as $prefix) {
        if (strpos($ip, $prefix) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Increment post view count
 * @param string $urlId URL ID
 */
function incrementViewCount($urlId) {
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE content SET views = views + 1 WHERE url_id = ?");
    $stmt->execute([$urlId]);
}

/**
 * Get post by URL ID
 * @param string $urlId URL ID
 * @return array|false Post data or false if not found
 */
function getPostByUrlId($urlId) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM content WHERE url_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$urlId]);
    
    return $stmt->fetch();
}

/**
 * Convert newlines to <br> tags
 * @param string $text Text to convert
 * @return string Converted text
 */
function nl2brCustom($text) {
    return str_replace(["\r\n", "\r", "\n"], "<br />", $text);
}

/**
 * Log admin action
 * @param string $username Admin username
 * @param string $action Action performed
 * @param string $details Action details
 */
function logAdminAction($username, $action, $details = null) {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO admin_logs (username, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $action, $details, getClientIp()]);
}

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

// Start session for all pages that include functions.php
startSession();
?>