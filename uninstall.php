<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('firebase_woo_auth_settings');
delete_option('firebase_woo_auth_api_key');
delete_option('firebase_woo_auth_auth_domain');
delete_option('firebase_woo_auth_project_id');
delete_option('firebase_woo_auth_storage_bucket');
delete_option('firebase_woo_auth_messaging_sender_id');
delete_option('firebase_woo_auth_app_id');

// Delete debug log file
$log_file = WP_CONTENT_DIR . '/firebase-auth-logs/debug.log';
if (file_exists($log_file)) {
    wp_delete_file($log_file);
}

// Delete backup log files
$backup_dir = WP_CONTENT_DIR . '/firebase-auth-logs/';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . 'debug-*.log');
    foreach ($files as $file) {
        wp_delete_file($file);
    }
    rmdir($backup_dir);
}

// Clear any cached data
wp_cache_flush(); 