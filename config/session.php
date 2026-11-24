<?php
// config/session.php - Safe cross-platform session initialization for Vercel Serverless and Localhost

if (session_status() === PHP_SESSION_NONE) {
    // 1. Point session storage to a writable temporary directory
    $tmpDir = sys_get_temp_dir();
    $sessDir = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'restaurant_sessions';

    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0777, true);
    }

    if (is_dir($sessDir) && is_writable($sessDir)) {
        @session_save_path($sessDir);
    } else if (is_writable($tmpDir)) {
        @session_save_path($tmpDir);
    }

    // 2. Prevent unhandled session warnings from breaking JSON responses
    $prevErrors = error_reporting();
    error_reporting($prevErrors & ~E_WARNING & ~E_NOTICE);
    @ini_set('display_errors', '0');
    @session_start();
    error_reporting($prevErrors);
}
