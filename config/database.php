<?php

define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'xpos_db');

define('BASE_PATH', '/xpos');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . BASE_PATH);

function baseUrl($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

$conn = getDbConnection();
