<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FirebaseWooAuthAdmin {

    private $options;
    private $option_name = 'firebase_woo_auth_options';

    public function __construct() {
        $this->options = get_option($this->option_name, array());
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_firebase_woo_auth_save_settings', array($this, 'ajax_save_settings'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Add debug log page
        add_action('admin_menu', array($this, 'add_debug_log_page'));
    }

    public function enqueue_admin_assets($hook) {
        if ('settings_page_firebase_woo_auth' !== $hook) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style('firebase-woo-auth-admin', FIREBASE_WOO_AUTH_URL . 'assets/css/admin.css', array(), '1.0.0');

        // Enqueue scripts
        wp_enqueue_script('firebase-woo-auth-admin', FIREBASE_WOO_AUTH_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);

        // Localize script
        wp_localize_script('firebase-woo-auth-admin', 'firebaseWooAuthAdmin', array(
            'nonce' => wp_create_nonce('firebase_woo_auth_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'logo_url' => FIREBASE_WOO_AUTH_URL . 'assets/images/developer-logo/ayrop-com.svg'
        ));
    }

    public function add_settings_page() {
        add_options_page(
            __('Firebase Woo Commerce Auth Settings', 'firebase-authentication-for-woocommerce'),
            __('Firebase Woo Commerce Auth', 'firebase-authentication-for-woocommerce'),
            'manage_options',
            'firebase_woo_auth',
            array($this, 'create_settings_page')
        );
    }

    public function create_settings_page() {
        // Refresh options
        $this->options = get_option($this->option_name, array());
        ?>
        <div class="wrap firebase-woo-auth-admin">
            <div class="firebase-woo-auth-header">
                <div class="firebase-woo-auth-logo-container">
                    <div class="firebase-woo-auth-logo">
                        <img src="<?php echo esc_url(FIREBASE_WOO_AUTH_URL . 'assets/images/developer-logo/ayrop-com.svg'); ?>" alt="Ayrop.com Logo">
                    </div>
                    <a href="https://ayrop.com" target="_blank" rel="noopener noreferrer" class="firebase-woo-auth-website">ayrop.com</a>
                </div>
            <h1><?php esc_html_e('Firebase WooCommerce Authentication Settings', 'firebase-authentication-for-woocommerce'); ?></h1>
            </div>
            
            <div class="firebase-woo-auth-notice-container"></div>
            
            <div class="firebase-woo-auth-tabs">
                <button class="firebase-woo-auth-tab active" data-tab="firebase-config"><?php esc_html_e('Firebase Config', 'firebase-authentication-for-woocommerce'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="sign-in-methods"><?php esc_html_e('Sign-In Methods', 'firebase-authentication-for-woocommerce'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="legal-links"><?php esc_html_e('Legal Links', 'firebase-authentication-for-woocommerce'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="help"><?php esc_html_e('Help', 'firebase-authentication-for-woocommerce'); ?></button>
            </div>

            <form id="firebase-woo-auth-form" method="post">
                <?php settings_fields('firebase_woo_auth_options_group'); ?>

                <div id="firebase-config" class="firebase-woo-auth-tab-content active">
                    <h2><?php esc_html_e('Firebase Configuration', 'firebase-authentication-for-woocommerce'); ?></h2>
                    <?php
                    $this->render_text_field_with_tooltip('firebase_api_key', __('Firebase API Key', 'firebase-authentication-for-woocommerce'), __('Your Firebase API key. You can find this in your Firebase Console under Project Settings > General.', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_auth_domain', __('Firebase Auth Domain', 'firebase-authentication-for-woocommerce'), __('Your Firebase Auth domain. Usually in the format: your-project.firebaseapp.com', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_project_id', __('Firebase Project ID', 'firebase-authentication-for-woocommerce'), __('Your Firebase Project ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_storage_bucket', __('Firebase Storage Bucket', 'firebase-authentication-for-woocommerce'), __('Your Firebase Storage bucket. Usually in the format: your-project.appspot.com', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_messaging_sender_id', __('Firebase Messaging Sender ID', 'firebase-authentication-for-woocommerce'), __('Your Firebase Messaging Sender ID. You can find this in your Firebase Console under Project Settings > Cloud Messaging.', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_app_id', __('Firebase App ID', 'firebase-authentication-for-woocommerce'), __('Your Firebase App ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('firebase_measurement_id', __('Firebase Measurement ID', 'firebase-authentication-for-woocommerce'), __('Your Firebase Measurement ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-authentication-for-woocommerce'));
                    ?>
                </div>

                <div id="sign-in-methods" class="firebase-woo-auth-tab-content">
                    <h2><?php esc_html_e('Sign-In Methods', 'firebase-authentication-for-woocommerce'); ?></h2>
                    <?php
                    $this->render_checkbox_with_tooltip('enable_phone', __('Enable Phone Number', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using their phone number.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_google', __('Enable Google', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using their Google account.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_github', __('Enable GitHub', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using their GitHub account.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_twitter', __('Enable Twitter', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using their Twitter account.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_email_password', __('Enable Email/Password', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using email and password.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_email_link', __('Enable Email Link Sign-In', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using email magic links.', 'firebase-authentication-for-woocommerce'));
                    $this->render_checkbox_with_tooltip('enable_microsoft', __('Enable Microsoft', 'firebase-authentication-for-woocommerce'), __('Allow users to sign in using their Microsoft account.', 'firebase-authentication-for-woocommerce'));
                    ?>
                </div>

                <div id="legal-links" class="firebase-woo-auth-tab-content">
                    <h2><?php esc_html_e('Legal Links', 'firebase-authentication-for-woocommerce'); ?></h2>
                <?php
                    $this->render_text_field_with_tooltip('terms_of_service_url', __('Terms of Service URL', 'firebase-authentication-for-woocommerce'), __('URL to your Terms of Service page.', 'firebase-authentication-for-woocommerce'));
                    $this->render_text_field_with_tooltip('privacy_policy_url', __('Privacy Policy URL', 'firebase-authentication-for-woocommerce'), __('URL to your Privacy Policy page.', 'firebase-authentication-for-woocommerce'));
                    ?>
                </div>

                <div id="help" class="firebase-woo-auth-tab-content">
                    <div class="firebase-woo-auth-help-section">
                        <h3><?php esc_html_e('Getting Started', 'firebase-authentication-for-woocommerce'); ?></h3>
                        <p><?php esc_html_e('This plugin integrates Firebase Authentication with your WooCommerce store, providing secure and modern authentication methods for your customers.', 'firebase-authentication-for-woocommerce'); ?></p>

                        <div class="firebase-woo-auth-shortcode-section">
                            <h4><?php esc_html_e('Shortcode Usage', 'firebase-authentication-for-woocommerce'); ?></h4>
                            <p><?php esc_html_e('Add the Firebase Authentication UI anywhere on your site using this shortcode:', 'firebase-authentication-for-woocommerce'); ?></p>
                            <div class="firebase-woo-auth-shortcode-container">
                                <code id="firebase-auth-shortcode">[firebase_auth_ui]</code>
                                <button class="firebase-woo-auth-copy-button" data-copy="[firebase_auth_ui]">
                                    <?php esc_html_e('Copy Shortcode', 'firebase-authentication-for-woocommerce'); ?>
                                </button>
                            </div>
                            <div class="firebase-woo-auth-shortcode-usage">
                                <h5><?php esc_html_e('Where to Use the Shortcode', 'firebase-authentication-for-woocommerce'); ?></h5>
                                <ul>
                                    <li><?php esc_html_e('Pages: Add to any page using the WordPress editor', 'firebase-authentication-for-woocommerce'); ?></li>
                                    <li><?php esc_html_e('Widgets: Use in text widgets or custom HTML widgets', 'firebase-authentication-for-woocommerce'); ?></li>
                                    <li><?php esc_html_e('Templates: Add directly to your theme files using do_shortcode()', 'firebase-authentication-for-woocommerce'); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="firebase-woo-auth-woocommerce-section">
                            <h4><?php esc_html_e('WooCommerce Integration', 'firebase-authentication-for-woocommerce'); ?></h4>
                            <p><?php esc_html_e('The Firebase Authentication UI is automatically integrated with WooCommerce checkout:', 'firebase-authentication-for-woocommerce'); ?></p>
                            <ul>
                                <li><?php esc_html_e('The sign-in options will appear in the checkout process if enabled in WooCommerce settings', 'firebase-authentication-for-woocommerce'); ?></li>
                                <li><?php esc_html_e('Users can authenticate during checkout without leaving the page', 'firebase-authentication-for-woocommerce'); ?></li>
                                <li><?php esc_html_e('Authentication status is maintained throughout the checkout process', 'firebase-authentication-for-woocommerce'); ?></li>
                            </ul>
                            <div class="firebase-woo-auth-note">
                                <strong><?php esc_html_e('Note:', 'firebase-authentication-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('To enable authentication during checkout, go to WooCommerce > Settings > Accounts & Privacy and enable "Allow customers to log into an existing account during checkout".', 'firebase-authentication-for-woocommerce'); ?></p>
                            </div>
                        </div>

                        <h4><?php esc_html_e('Setup Guide', 'firebase-authentication-for-woocommerce'); ?></h4>
                        <ol>
                            <li>
                                <strong><?php esc_html_e('Create a Firebase Project', 'firebase-authentication-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Go to the Firebase Console and create a new project or select an existing one.', 'firebase-authentication-for-woocommerce'); ?></p>
                                <a href="https://console.firebase.google.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Firebase Console', 'firebase-authentication-for-woocommerce'); ?></a>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Enable Authentication Methods', 'firebase-authentication-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('In your Firebase Console, navigate to Authentication > Sign-in method and enable the methods you want to use:', 'firebase-authentication-for-woocommerce'); ?></p>
                                <ul class="firebase-woo-auth-methods">
                                    <li>
                                        <strong><?php esc_html_e('Email/Password', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Traditional email and password authentication. Users can create accounts with their email addresses.', 'firebase-authentication-for-woocommerce'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/password-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-authentication-for-woocommerce'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Google Sign-In', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Allow users to sign in with their Google accounts. Requires OAuth 2.0 client ID setup.', 'firebase-authentication-for-woocommerce'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/google-signin" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-authentication-for-woocommerce'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('GitHub Sign-In', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Enable GitHub authentication for developers and tech-savvy users.', 'firebase-authentication-for-woocommerce'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/github-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-authentication-for-woocommerce'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Phone Authentication', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Allow users to sign in using their phone numbers. Requires Firebase Blaze plan.', 'firebase-authentication-for-woocommerce'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/phone-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-authentication-for-woocommerce'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Email Link Authentication', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Passwordless authentication using magic links sent via email.', 'firebase-authentication-for-woocommerce'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/email-link-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-authentication-for-woocommerce'); ?></a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Configure Firebase Project', 'firebase-authentication-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Get your Firebase configuration values from Project Settings > General > Your apps > Web app.', 'firebase-authentication-for-woocommerce'); ?></p>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Authorize Your Domain', 'firebase-authentication-for-woocommerce'); ?></strong>
                                <p><?php esc_html_e('Add your website domain to the authorized domains list in Firebase Console > Authentication > Settings > Authorized domains.', 'firebase-authentication-for-woocommerce'); ?></p>
                            </li>
                        </ol>

                        <h4><?php esc_html_e('Troubleshooting', 'firebase-authentication-for-woocommerce'); ?></h4>
                        <div class="firebase-woo-auth-troubleshooting">
                            <div class="firebase-woo-auth-issue">
                                <h5><?php esc_html_e('Authentication Not Working', 'firebase-authentication-for-woocommerce'); ?></h5>
                                <ul>
                                    <li><?php esc_html_e('Verify all Firebase configuration values are correct', 'firebase-authentication-for-woocommerce'); ?></li>
                                    <li><?php esc_html_e('Check that the required authentication methods are enabled in Firebase Console', 'firebase-authentication-for-woocommerce'); ?></li>
                                    <li><?php esc_html_e('Ensure your domain is authorized in Firebase Console', 'firebase-authentication-for-woocommerce'); ?></li>
                                </ul>
                            </div>
                            <div class="firebase-woo-auth-issue">
                                <h5><?php esc_html_e('Common Errors', 'firebase-authentication-for-woocommerce'); ?></h5>
                                <ul>
                                    <li>
                                        <strong><?php esc_html_e('Invalid API Key', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Check that you\'ve copied the correct API key from Firebase Console.', 'firebase-authentication-for-woocommerce'); ?></p>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Domain Not Authorized', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('Add your domain to the authorized domains list in Firebase Console.', 'firebase-authentication-for-woocommerce'); ?></p>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('OAuth Configuration Missing', 'firebase-authentication-for-woocommerce'); ?></strong>
                                        <p><?php esc_html_e('For social sign-in methods, ensure you\'ve configured the OAuth credentials in Firebase Console.', 'firebase-authentication-for-woocommerce'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="firebase-woo-auth-support">
                            <h4><?php esc_html_e('Need More Help?', 'firebase-authentication-for-woocommerce'); ?></h4>
                            <p><?php esc_html_e('If you\'re still experiencing issues, please:', 'firebase-authentication-for-woocommerce'); ?></p>
                            <ul>
                                <li><?php esc_html_e('Check the browser console for error messages', 'firebase-authentication-for-woocommerce'); ?></li>
                                <li><?php esc_html_e('Review the Firebase documentation for your specific authentication method', 'firebase-authentication-for-woocommerce'); ?></li>
                                <li><?php esc_html_e('Contact our support team at support@ayrop.com', 'firebase-authentication-for-woocommerce'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="submit">
                    <button type="submit" class="firebase-woo-auth-save-button button button-primary" disabled>
                        <?php esc_html_e('Save Changes', 'firebase-authentication-for-woocommerce'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('firebase_woo_auth_options_group', $this->option_name, array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        add_settings_section(
            'firebase_woo_auth_main',
            __('Firebase Configuration', 'firebase-authentication-for-woocommerce'),
            array($this, 'render_section_info'),
            'firebase-woo-auth'
        );

        // Add debug log setting
        add_settings_field(
            'enable_debug_log',
            __('Enable Debug Logging', 'firebase-authentication-for-woocommerce'),
            array($this, 'render_checkbox_field'),
            'firebase-woo-auth',
            'firebase_woo_auth_main',
            array(
                'name' => 'enable_debug_log',
                'description' => __('Enable detailed logging of authentication issues.', 'firebase-authentication-for-woocommerce')
            )
        );
    }

    public function render_text_field_with_tooltip($id, $label, $tooltip) {
        ?>
        <div class="firebase-woo-auth-form-group">
            <label for="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($label); ?>
                <span class="firebase-woo-auth-tooltip" data-tooltip="<?php echo esc_attr($tooltip); ?>"></span>
            </label>
            <input type="text" id="<?php echo esc_attr($id); ?>" name="firebase_woo_auth_options[<?php echo esc_attr($id); ?>]" value="<?php echo isset($this->options[$id]) ? esc_attr($this->options[$id]) : ''; ?>" class="regular-text">
        </div>
        <?php
    }

    public function render_checkbox_with_tooltip($id, $label, $tooltip) {
        ?>
        <div class="firebase-woo-auth-form-group">
            <label>
                <input type="checkbox" name="firebase_woo_auth_options[<?php echo esc_attr($id); ?>]" value="1" <?php checked(isset($this->options[$id]) ? $this->options[$id] : '', '1'); ?>>
                <?php echo esc_html($label); ?>
                <span class="firebase-woo-auth-tooltip" data-tooltip="<?php echo esc_attr($tooltip); ?>"></span>
            </label>
        </div>
        <?php
    }

    public function ajax_save_settings() {
        check_ajax_referer('firebase_woo_auth_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'firebase-authentication-for-woocommerce')));
        }

        if (!isset($_POST['firebase_woo_auth_options'])) {
            wp_send_json_error(array('message' => __('No settings data received.', 'firebase-authentication-for-woocommerce')));
        }

        $options = $_POST['firebase_woo_auth_options'];
        $sanitized_options = $this->sanitize_settings($options);

        // Log the data being saved
        error_log('Saving Firebase Woo Auth settings: ' . print_r($sanitized_options, true));

        // Update the options
        $updated = update_option($this->option_name, $sanitized_options);

        if ($updated) {
            wp_send_json_success(array('message' => __('Settings saved successfully.', 'firebase-authentication-for-woocommerce')));
        } else {
            // Check if the values are actually different
            $current_options = get_option($this->option_name, array());
            if ($current_options === $sanitized_options) {
                wp_send_json_success(array('message' => __('Settings are already up to date.', 'firebase-authentication-for-woocommerce')));
            } else {
                wp_send_json_error(array('message' => __('Failed to save settings.', 'firebase-authentication-for-woocommerce')));
            }
        }
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize all text fields
        $text_fields = array(
            'firebase_api_key',
            'firebase_auth_domain',
            'firebase_project_id',
            'firebase_storage_bucket',
            'firebase_messaging_sender_id',
            'firebase_app_id',
            'firebase_measurement_id',
            'terms_of_service_url',
            'privacy_policy_url'
        );
        
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        // Sanitize boolean fields
        $boolean_fields = array(
            'enable_phone',
            'enable_google',
            'enable_github',
            'enable_twitter',
            'enable_email_password',
            'enable_email_link',
            'enable_microsoft',
            'enable_debug_log'
        );
        
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? (bool) $input[$field] : false;
        }
        
        return $sanitized;
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Firebase Woo Commerce Auth', 'firebase-authentication-for-woocommerce'),
            __('Firebase Woo Commerce Auth', 'firebase-authentication-for-woocommerce'),
            'manage_options',
            'firebase-woo-auth',
            array($this, 'render_admin_page'),
            'dashicons-admin-site',
            6
        );
    }

    public function render_admin_page() {
        // Implementation of render_admin_page method
    }

    public function add_debug_log_page() {
        add_submenu_page(
            'firebase-woo-auth',
            __('Debug Log', 'firebase-authentication-for-woocommerce'),
            __('Debug Log', 'firebase-authentication-for-woocommerce'),
            'manage_options',
            'firebase-woo-auth-debug',
            array($this, 'render_debug_log_page')
        );
    }

    public function render_debug_log_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'firebase-authentication-for-woocommerce'));
        }

        // Handle log clearing
        if (isset($_GET['action']) && $_GET['action'] === 'clear_log') {
            check_admin_referer('clear_debug_log');
            $this->clear_debug_log();
            wp_redirect(add_query_arg('cleared', '1', remove_query_arg('action')));
            exit;
        }

        // Handle log rotation
        $this->rotate_debug_log();

        $log_file = WP_CONTENT_DIR . '/firebase-auth-logs/debug.log';
        $log_content = '';
        
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Firebase WooCommerce Auth Debug Log', 'firebase-authentication-for-woocommerce'); ?></h1>
            
            <?php if (isset($_GET['cleared'])): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('Debug log has been cleared.', 'firebase-authentication-for-woocommerce'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php esc_html_e('Debug Log', 'firebase-authentication-for-woocommerce'); ?></h2>
                <p><?php esc_html_e('This page shows the debug log for Firebase authentication issues.', 'firebase-authentication-for-woocommerce'); ?></p>
                
                <div class="debug-log-container">
                    <?php
                    if (empty($log_content)) {
                        echo '<p>' . esc_html__('No debug log found.', 'firebase-authentication-for-woocommerce') . '</p>';
                    } else {
                        echo '<pre>' . esc_html($log_content) . '</pre>';
                    }
                    ?>
                </div>
                
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'clear_log'), 'clear_debug_log')); ?>" class="button">
                        <?php esc_html_e('Clear Log', 'firebase-authentication-for-woocommerce'); ?>
                    </a>
                </p>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Security Notice', 'firebase-authentication-for-woocommerce'); ?></h2>
                <p><?php esc_html_e('For security reasons, please ensure the debug.log file is not accessible via web. Add the following to your .htaccess file:', 'firebase-authentication-for-woocommerce'); ?></p>
                <pre><code># Block access to debug.log
&lt;Files "debug.log"&gt;
    Order allow,deny
    Deny from all
&lt;/Files&gt;</code></pre>
                <p><?php esc_html_e('Or if you\'re using Nginx, add this to your server configuration:', 'firebase-authentication-for-woocommerce'); ?></p>
                <pre><code># Block access to debug.log
location ~* /debug\.log$ {
    deny all;
    return 403;
}</code></pre>
            </div>
        </div>
        
        <style>
            .debug-log-container {
                background: #f1f1f1;
                padding: 10px;
                max-height: 500px;
                overflow-y: auto;
                border: 1px solid #ddd;
            }
            .debug-log-container pre {
                margin: 0;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin: 20px 0;
                padding: 20px;
            }
            pre code {
                background: #f1f1f1;
                padding: 10px;
                display: block;
            }
        </style>
        <?php
    }

    private function clear_debug_log() {
        $log_file = WP_CONTENT_DIR . '/firebase-auth-logs/debug.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
    }

    private function rotate_debug_log() {
        $log_file = WP_CONTENT_DIR . '/firebase-auth-logs/debug.log';
        $max_size = 5 * 1024 * 1024; // 5MB
        $backup_count = 5;

        if (file_exists($log_file) && filesize($log_file) > $max_size) {
            // Create backup directory if it doesn't exist
            $backup_dir = WP_CONTENT_DIR . '/firebase-auth-logs/';
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            // Rotate existing backups
            for ($i = $backup_count - 1; $i >= 0; $i--) {
                $old_file = $backup_dir . 'debug.log.' . $i;
                $new_file = $backup_dir . 'debug.log.' . ($i + 1);
                
                if (file_exists($old_file)) {
                    if ($i === $backup_count - 1) {
                        unlink($old_file);
                    } else {
                        rename($old_file, $new_file);
                    }
                }
            }

            // Move current log to backup
            rename($log_file, $backup_dir . 'debug.log.0');
            
            // Create new empty log file
            touch($log_file);
            chmod($log_file, 0644);
        }
    }

    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Firebase WooCommerce Auth: ' . $message);
        }
        
        // Log to admin debug log if enabled
        $options = get_option('firebase_woo_auth_options');
        if (!empty($options['enable_debug_log'])) {
            $log_file = WP_CONTENT_DIR . '/firebase-auth-logs/debug.log';
            $timestamp = current_time('mysql');
            $log_message = "[{$timestamp}] {$message}\n";
            
            // Ensure log directory exists and is writable
            if (!file_exists(dirname($log_file))) {
                mkdir(dirname($log_file), 0755, true);
            }
            
            // Write to log file with proper permissions
            if (file_put_contents($log_file, $log_message, FILE_APPEND)) {
                chmod($log_file, 0644);
            }
        }
    }

    public function display_admin_notices() {
        $options = get_option('firebase_woo_auth_options');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Firebase WooCommerce Auth requires WooCommerce to be installed and activated.', 'firebase-authentication-for-woocommerce'); ?></p>
            </div>
            <?php
        }
        
        // Check if Firebase configuration is complete
        if (empty($options['firebase_api_key']) || empty($options['firebase_auth_domain'])) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('Firebase WooCommerce Auth is not fully configured. Please complete the Firebase configuration.', 'firebase-authentication-for-woocommerce'); ?></p>
            </div>
            <?php
        }
    }
}
