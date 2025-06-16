<?php
/*
Plugin Name: Firebase Authentication for WooCommerce
Plugin URI: https://ayrop.com/plugins/firebase-auth-for-woocommerce
Description: Integrate Firebase Authentication with WooCommerce for secure and modern user authentication.
Version: 1.0.1
Author: Ayrop.com
Author URI: https://ayrop.com
Text Domain: firebase-authentication-for-woocommerce
Domain Path: /languages
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('FIREBASE_WOO_AUTH_PATH', plugin_dir_path(__FILE__));
define('FIREBASE_WOO_AUTH_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once FIREBASE_WOO_AUTH_PATH . 'includes/class-firebase-woo-auth.php';
require_once FIREBASE_WOO_AUTH_PATH . 'includes/class-firebase-woo-auth-admin.php';

// Initialize the plugin
function firebase_woo_auth_init() {
    new FirebaseWooAuth();
    new FirebaseWooAuthAdmin();
}
add_action('plugins_loaded', 'firebase_woo_auth_init');

// Enqueue custom CSS for Firebase UI
function enqueue_firebase_custom_ui_css() {
    wp_enqueue_style('firebase-custom-ui', FIREBASE_WOO_AUTH_URL . 'assets/css/firebase-custom-ui.css', array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_firebase_custom_ui_css');

