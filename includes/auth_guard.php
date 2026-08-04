<?php
// Include this at the top of every protected page, after config/database.php
// and includes/functions.php. Redirects to login if no user is signed in.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    redirect(base_url('auth/login.php'));
}
