<?php
require_once "config.php";

/* Remove all session data */
$_SESSION = [];

/* Delete the session cookie */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Destroy the session */
session_destroy();

/* Start a new session for the logout message */
session_start();

flash(
    'You have been logged out successfully.',
    'success'
);

redirect('login.php');
?>