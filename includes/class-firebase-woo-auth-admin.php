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
            __('Firebase Woo Auth Settings', 'firebase-woo-auth'),
            __('Firebase Woo Auth', 'firebase-woo-auth'),
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
            <h1><?php esc_html_e('Firebase WooCommerce Authentication Settings', 'firebase-woo-auth'); ?></h1>
            </div>
            
            <div class="firebase-woo-auth-notice-container"></div>
            
            <div class="firebase-woo-auth-tabs">
                <button class="firebase-woo-auth-tab active" data-tab="firebase-config"><?php esc_html_e('Firebase Config', 'firebase-woo-auth'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="sign-in-methods"><?php esc_html_e('Sign-In Methods', 'firebase-woo-auth'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="legal-links"><?php esc_html_e('Legal Links', 'firebase-woo-auth'); ?></button>
                <button class="firebase-woo-auth-tab" data-tab="help"><?php esc_html_e('Help', 'firebase-woo-auth'); ?></button>
            </div>

            <form id="firebase-woo-auth-form" method="post">
                <?php settings_fields('firebase_woo_auth_options_group'); ?>

                <div id="firebase-config" class="firebase-woo-auth-tab-content active">
                    <h2><?php esc_html_e('Firebase Configuration', 'firebase-woo-auth'); ?></h2>
                    <?php
                    $this->render_text_field_with_tooltip('firebase_api_key', __('API Key', 'firebase-woo-auth'), __('Your Firebase API key. You can find this in your Firebase Console under Project Settings > General.', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_auth_domain', __('Auth Domain', 'firebase-woo-auth'), __('Your Firebase Auth domain. Usually in the format: your-project.firebaseapp.com', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_project_id', __('Project ID', 'firebase-woo-auth'), __('Your Firebase Project ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_storage_bucket', __('Storage Bucket', 'firebase-woo-auth'), __('Your Firebase Storage bucket. Usually in the format: your-project.appspot.com', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_messaging_sender_id', __('Messaging Sender ID', 'firebase-woo-auth'), __('Your Firebase Messaging Sender ID. You can find this in your Firebase Console under Project Settings > Cloud Messaging.', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_app_id', __('App ID', 'firebase-woo-auth'), __('Your Firebase App ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('firebase_measurement_id', __('Measurement ID', 'firebase-woo-auth'), __('Your Firebase Measurement ID. You can find this in your Firebase Console under Project Settings > General.', 'firebase-woo-auth'));
                    ?>
                </div>

                <div id="sign-in-methods" class="firebase-woo-auth-tab-content">
                    <h2><?php esc_html_e('Sign-In Methods', 'firebase-woo-auth'); ?></h2>
                    <?php
                    $this->render_checkbox_with_tooltip('enable_phone', __('Enable Phone Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using their phone number.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_google', __('Enable Google Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using their Google account.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_github', __('Enable GitHub Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using their GitHub account.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_twitter', __('Enable Twitter Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using their Twitter account.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_email_password', __('Enable Email/Password Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using email and password.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_email_link', __('Enable Email Link Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using email magic links.', 'firebase-woo-auth'));
                    $this->render_checkbox_with_tooltip('enable_microsoft', __('Enable Microsoft Sign-In', 'firebase-woo-auth'), __('Allow users to sign in using their Microsoft account.', 'firebase-woo-auth'));
                    ?>
                </div>

                <div id="legal-links" class="firebase-woo-auth-tab-content">
                    <h2><?php esc_html_e('Legal Links', 'firebase-woo-auth'); ?></h2>
                <?php
                    $this->render_text_field_with_tooltip('terms_of_service_url', __('Terms of Service URL', 'firebase-woo-auth'), __('URL to your Terms of Service page.', 'firebase-woo-auth'));
                    $this->render_text_field_with_tooltip('privacy_policy_url', __('Privacy Policy URL', 'firebase-woo-auth'), __('URL to your Privacy Policy page.', 'firebase-woo-auth'));
                    ?>
                </div>

                <div id="help" class="firebase-woo-auth-tab-content">
                    <div class="firebase-woo-auth-help-section">
                        <h3><?php esc_html_e('Getting Started', 'firebase-woo-auth'); ?></h3>
                        <p><?php esc_html_e('This plugin integrates Firebase Authentication with your WooCommerce store, providing secure and modern authentication methods for your customers.', 'firebase-woo-auth'); ?></p>

                        <div class="firebase-woo-auth-shortcode-section">
                            <h4><?php esc_html_e('Shortcode Usage', 'firebase-woo-auth'); ?></h4>
                            <p><?php esc_html_e('Add the Firebase Authentication UI anywhere on your site using this shortcode:', 'firebase-woo-auth'); ?></p>
                            <div class="firebase-woo-auth-shortcode-container">
                                <code id="firebase-auth-shortcode">[firebase_auth_ui]</code>
                                <button class="firebase-woo-auth-copy-button" data-copy="[firebase_auth_ui]">
                                    <?php esc_html_e('Copy Shortcode', 'firebase-woo-auth'); ?>
                                </button>
                            </div>
                            <div class="firebase-woo-auth-shortcode-usage">
                                <h5><?php esc_html_e('Where to Use the Shortcode', 'firebase-woo-auth'); ?></h5>
                                <ul>
                                    <li><?php esc_html_e('Pages: Add to any page using the WordPress editor', 'firebase-woo-auth'); ?></li>
                                    <li><?php esc_html_e('Widgets: Use in text widgets or custom HTML widgets', 'firebase-woo-auth'); ?></li>
                                    <li><?php esc_html_e('Templates: Add directly to your theme files using do_shortcode()', 'firebase-woo-auth'); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="firebase-woo-auth-woocommerce-section">
                            <h4><?php esc_html_e('WooCommerce Integration', 'firebase-woo-auth'); ?></h4>
                            <p><?php esc_html_e('The Firebase Authentication UI is automatically integrated with WooCommerce checkout:', 'firebase-woo-auth'); ?></p>
                            <ul>
                                <li><?php esc_html_e('The sign-in options will appear in the checkout process if enabled in WooCommerce settings', 'firebase-woo-auth'); ?></li>
                                <li><?php esc_html_e('Users can authenticate during checkout without leaving the page', 'firebase-woo-auth'); ?></li>
                                <li><?php esc_html_e('Authentication status is maintained throughout the checkout process', 'firebase-woo-auth'); ?></li>
                            </ul>
                            <div class="firebase-woo-auth-note">
                                <strong><?php esc_html_e('Note:', 'firebase-woo-auth'); ?></strong>
                                <p><?php esc_html_e('To enable authentication during checkout, go to WooCommerce > Settings > Accounts & Privacy and enable "Allow customers to log into an existing account during checkout".', 'firebase-woo-auth'); ?></p>
                            </div>
                        </div>

                        <h4><?php esc_html_e('Setup Guide', 'firebase-woo-auth'); ?></h4>
                        <ol>
                            <li>
                                <strong><?php esc_html_e('Create a Firebase Project', 'firebase-woo-auth'); ?></strong>
                                <p><?php esc_html_e('Go to the Firebase Console and create a new project or select an existing one.', 'firebase-woo-auth'); ?></p>
                                <a href="https://console.firebase.google.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Firebase Console', 'firebase-woo-auth'); ?></a>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Enable Authentication Methods', 'firebase-woo-auth'); ?></strong>
                                <p><?php esc_html_e('In your Firebase Console, navigate to Authentication > Sign-in method and enable the methods you want to use:', 'firebase-woo-auth'); ?></p>
                                <ul class="firebase-woo-auth-methods">
                                    <li>
                                        <strong><?php esc_html_e('Email/Password', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Traditional email and password authentication. Users can create accounts with their email addresses.', 'firebase-woo-auth'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/password-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-woo-auth'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Google Sign-In', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Allow users to sign in with their Google accounts. Requires OAuth 2.0 client ID setup.', 'firebase-woo-auth'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/google-signin" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-woo-auth'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('GitHub Sign-In', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Enable GitHub authentication for developers and tech-savvy users.', 'firebase-woo-auth'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/github-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-woo-auth'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Phone Authentication', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Allow users to sign in using their phone numbers. Requires Firebase Blaze plan.', 'firebase-woo-auth'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/phone-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-woo-auth'); ?></a>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Email Link Authentication', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Passwordless authentication using magic links sent via email.', 'firebase-woo-auth'); ?></p>
                                        <a href="https://firebase.google.com/docs/auth/web/email-link-auth" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'firebase-woo-auth'); ?></a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Configure Firebase Project', 'firebase-woo-auth'); ?></strong>
                                <p><?php esc_html_e('Get your Firebase configuration values from Project Settings > General > Your apps > Web app.', 'firebase-woo-auth'); ?></p>
                            </li>
                            <li>
                                <strong><?php esc_html_e('Authorize Your Domain', 'firebase-woo-auth'); ?></strong>
                                <p><?php esc_html_e('Add your website domain to the authorized domains list in Firebase Console > Authentication > Settings > Authorized domains.', 'firebase-woo-auth'); ?></p>
                            </li>
                        </ol>

                        <h4><?php esc_html_e('Troubleshooting', 'firebase-woo-auth'); ?></h4>
                        <div class="firebase-woo-auth-troubleshooting">
                            <div class="firebase-woo-auth-issue">
                                <h5><?php esc_html_e('Authentication Not Working', 'firebase-woo-auth'); ?></h5>
                                <ul>
                                    <li><?php esc_html_e('Verify all Firebase configuration values are correct', 'firebase-woo-auth'); ?></li>
                                    <li><?php esc_html_e('Check that the required authentication methods are enabled in Firebase Console', 'firebase-woo-auth'); ?></li>
                                    <li><?php esc_html_e('Ensure your domain is authorized in Firebase Console', 'firebase-woo-auth'); ?></li>
                                </ul>
                            </div>
                            <div class="firebase-woo-auth-issue">
                                <h5><?php esc_html_e('Common Errors', 'firebase-woo-auth'); ?></h5>
                                <ul>
                                    <li>
                                        <strong><?php esc_html_e('Invalid API Key', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Check that you\'ve copied the correct API key from Firebase Console.', 'firebase-woo-auth'); ?></p>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Domain Not Authorized', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('Add your domain to the authorized domains list in Firebase Console.', 'firebase-woo-auth'); ?></p>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('OAuth Configuration Missing', 'firebase-woo-auth'); ?></strong>
                                        <p><?php esc_html_e('For social sign-in methods, ensure you\'ve configured the OAuth credentials in Firebase Console.', 'firebase-woo-auth'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="firebase-woo-auth-support">
                            <h4><?php esc_html_e('Need More Help?', 'firebase-woo-auth'); ?></h4>
                            <p><?php esc_html_e('If you\'re still experiencing issues, please:', 'firebase-woo-auth'); ?></p>
                            <ul>
                                <li><?php esc_html_e('Check the browser console for error messages', 'firebase-woo-auth'); ?></li>
                                <li><?php esc_html_e('Review the Firebase documentation for your specific authentication method', 'firebase-woo-auth'); ?></li>
                                <li><?php esc_html_e('Contact our support team at support@ayrop.com', 'firebase-woo-auth'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="submit">
                    <button type="submit" class="firebase-woo-auth-save-button button button-primary" disabled>
                        <?php esc_html_e('Save Changes', 'firebase-woo-auth'); ?>
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
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'firebase-woo-auth')));
        }

        if (!isset($_POST['firebase_woo_auth_options'])) {
            wp_send_json_error(array('message' => __('No settings data received.', 'firebase-woo-auth')));
        }

        $options = $_POST['firebase_woo_auth_options'];
        $sanitized_options = $this->sanitize_settings($options);

        // Log the data being saved
        error_log('Saving Firebase Woo Auth settings: ' . print_r($sanitized_options, true));

        // Update the options
        $updated = update_option($this->option_name, $sanitized_options);

        if ($updated) {
            wp_send_json_success(array('message' => __('Settings saved successfully.', 'firebase-woo-auth')));
        } else {
            // Check if the values are actually different
            $current_options = get_option($this->option_name, array());
            if ($current_options === $sanitized_options) {
                wp_send_json_success(array('message' => __('Settings are already up to date.', 'firebase-woo-auth')));
            } else {
                wp_send_json_error(array('message' => __('Failed to save settings.', 'firebase-woo-auth')));
            }
        }
    }

    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return array();
        }

        $output = array();
        foreach ($input as $key => $value) {
            $output[$key] = sanitize_text_field($value);
        }
        return $output;
    }
}
