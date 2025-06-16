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

// Delete debug logs
$upload_dir = wp_upload_dir();
$debug_log_path = $upload_dir['basedir'] . '/firebase-woo-auth-debug.log';
if (file_exists($debug_log_path)) {
    unlink($debug_log_path);
}

// Clear any cached data
wp_cache_flush(); 