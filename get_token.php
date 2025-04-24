<?php
/**
 * Generate and return CSRF token
 */

// Include shared functions
require_once 'functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Generate CSRF token
$token = generateCsrfToken();

// Return token as JSON
echo json_encode(['token' => $token]);
?>