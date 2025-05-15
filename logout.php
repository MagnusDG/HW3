<?php
// logout.php
require_once __DIR__ . '/config/db.php'; // To ensure session_start() is called

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to main page (index.php)
// You can add a success message if desired, perhaps via a query parameter
// or by setting a temporary cookie if you don't want to use sessions for this.
// For simplicity, just redirecting.
header("Location: index.php");
exit;
?>
