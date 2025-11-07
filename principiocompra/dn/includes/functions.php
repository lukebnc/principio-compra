<?php
// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in (for admin)
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

// Fetch settings from the database
function getSetting($key_name) {
    global $conn; // Ensure the database connection is available
    $stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['value'] : null;
}