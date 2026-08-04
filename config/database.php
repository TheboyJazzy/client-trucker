<?php
// Database connection settings.
// Update these values to match your local WAMP/XAMPP MySQL setup.

define('DB_HOST', 'localhost');
define('DB_NAME', 'client_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
