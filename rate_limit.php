<?php
/**
 * Rate limiting implementation for Arabic Content Publishing Platform
 */

// Include shared functions
require_once 'functions.php';

/**
 * Check if the current IP has exceeded its rate limits
 * @return bool|string True if allowed, error message if rate limited
 */
function checkRateLimit() {
    $ip = getClientIp();
    
    // If IP is banned, return error
    if (isIpBanned($ip)) {
        return $GLOBALS['error_messages']['ip_banned'];
    }
    
    $db = getDbConnection();
    $isSaudi = isSaudiIp($ip);
    
    // Get current rate limit record
    $stmt = $db->prepare("SELECT * FROM rate_limits WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $rateLimit = $stmt->fetch();
    
    // Current time
    $now = new DateTime();
    
    // If no record exists, create one
    if (!$rateLimit) {
        $stmt = $db->prepare("INSERT INTO rate_limits (ip_address, is_saudi, hourly_count, daily_count, hourly_reset_at, daily_reset_at) VALUES (?, ?, 1, 1, DATE_ADD(NOW(), INTERVAL 1 HOUR), DATE_ADD(NOW(), INTERVAL 1 DAY))");
        $stmt->execute([$ip, $isSaudi ? 1 : 0]);
        return true;
    }
    
    // Check if hourly reset time has passed
    $hourlyResetAt = new DateTime($rateLimit['hourly_reset_at']);
    if ($now > $hourlyResetAt) {
        // Reset hourly count
        $rateLimit['hourly_count'] = 0;
        $hourlyResetAt = clone $now;
        $hourlyResetAt->modify('+1 hour');
        
        $stmt = $db->prepare("UPDATE rate_limits SET hourly_count = 0, hourly_reset_at = ? WHERE ip_address = ?");
        $stmt->execute([$hourlyResetAt->format('Y-m-d H:i:s'), $ip]);
    }
    
    // Check if daily reset time has passed
    $dailyResetAt = new DateTime($rateLimit['daily_reset_at']);
    if ($now > $dailyResetAt) {
        // Reset daily count
        $rateLimit['daily_count'] = 0;
        $dailyResetAt = clone $now;
        $dailyResetAt->modify('+1 day');
        
        $stmt = $db->prepare("UPDATE rate_limits SET daily_count = 0, daily_reset_at = ? WHERE ip_address = ?");
        $stmt->execute([$dailyResetAt->format('Y-m-d H:i:s'), $ip]);
    }
    
    // Set limits based on whether the IP is from Saudi Arabia
    $hourlyLimit = $isSaudi ? SAUDI_HOURLY_LIMIT : GENERAL_HOURLY_LIMIT;
    $dailyLimit = $isSaudi ? SAUDI_DAILY_LIMIT : GENERAL_DAILY_LIMIT;
    
    // Check if hourly limit has been reached
    if ($rateLimit['hourly_count'] >= $hourlyLimit) {
        return $GLOBALS['error_messages']['rate_limit_hour'];
    }
    
    // Check if daily limit has been reached
    if ($rateLimit['daily_count'] >= $dailyLimit) {
        return $GLOBALS['error_messages']['rate_limit_day'];
    }
    
    // Increment counters
    $stmt = $db->prepare("UPDATE rate_limits SET hourly_count = hourly_count + 1, daily_count = daily_count + 1, is_saudi = ? WHERE ip_address = ?");
    $stmt->execute([$isSaudi ? 1 : 0, $ip]);
    
    return true;
}

/**
 * Ban an IP address
 * @param string $ip IP address to ban
 * @param string $reason Reason for banning
 * @param string $admin Admin username
 * @return bool True if successfully banned
 */
function banIp($ip, $reason, $admin) {
    $db = getDbConnection();
    
    // Check if IP is already banned
    $stmt = $db->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?");
    $stmt->execute([$ip]);
    
    if ($stmt->fetchColumn() > 0) {
        return false; // Already banned
    }
    
    // Ban IP
    $stmt = $db->prepare("INSERT INTO banned_ips (ip_address, banned_by, reason) VALUES (?, ?, ?)");
    $result = $stmt->execute([$ip, $admin, $reason]);
    
    if ($result) {
        // Log admin action
        logAdminAction($admin, 'ban_ip', "Banned IP: $ip - Reason: $reason");
    }
    
    return $result;
}

/**
 * Unban an IP address
 * @param string $ip IP address to unban
 * @param string $admin Admin username
 * @return bool True if successfully unbanned
 */
function unbanIp($ip, $admin) {
    $db = getDbConnection();
    
    // Unban IP
    $stmt = $db->prepare("DELETE FROM banned_ips WHERE ip_address = ?");
    $result = $stmt->execute([$ip]);
    
    if ($result) {
        // Log admin action
        logAdminAction($admin, 'unban_ip', "Unbanned IP: $ip");
    }
    
    return $result;
}

/**
 * Get all banned IPs
 * @return array List of banned IPs
 */
function getBannedIps() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM banned_ips ORDER BY banned_at DESC");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get rate limit statistics for an IP
 * @param string $ip IP address
 * @return array|false Rate limit statistics or false if not found
 */
function getRateLimitStats($ip) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM rate_limits WHERE ip_address = ?");
    $stmt->execute([$ip]);
    
    return $stmt->fetch();
}
?>