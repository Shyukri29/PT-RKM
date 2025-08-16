<?php
// Ubah sesuai lingkungan Anda
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hrm_app');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/hrm-app/public'); // sesuaikan path public

// Session & CSRF
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();