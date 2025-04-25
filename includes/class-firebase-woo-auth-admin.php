<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FirebaseWooAuthAdmin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
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
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Firebase WooCommerce Authentication Settings', 'firebase-woo-auth'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('firebase_woo_auth_options_group');
                do_settings_sections('firebase_woo_auth');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('firebase_woo_auth_options_group', 'firebase_woo_auth_options', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        // Firebase configuration section
        add_settings_section('firebase_woo_auth_config_section', __('Firebase Configuration', 'firebase-woo-auth'), null, 'firebase_woo_auth');

        // Firebase configuration fields
        $this->add_settings_field('firebase_api_key', __('API Key', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_auth_domain', __('Auth Domain', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_project_id', __('Project ID', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_storage_bucket', __('Storage Bucket', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_messaging_sender_id', __('Messaging Sender ID', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_app_id', __('App ID', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');
        $this->add_settings_field('firebase_measurement_id', __('Measurement ID', 'firebase-woo-auth'), 'firebase_woo_auth_config_section', 'render_text_field');

        // Sign-In methods section (render as checkboxes)
        add_settings_section('firebase_woo_auth_main_section', __('Sign-In Methods', 'firebase-woo-auth'), null, 'firebase_woo_auth');

        // Sign-In methods fields as checkboxes
        $this->add_settings_field('enable_phone', __('Enable Phone Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_google', __('Enable Google Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_github', __('Enable GitHub Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_twitter', __('Enable Twitter Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_email_password', __('Enable Email/Password Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_email_link', __('Enable Email Link Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field');
        $this->add_settings_field('enable_microsoft', __('Enable Microsoft Sign-In', 'firebase-woo-auth'), 'firebase_woo_auth_main_section', 'render_checkbox_field'); // Added Microsoft option

        // Terms of Service and Privacy Policy section
        add_settings_section('firebase_woo_auth_links_section', __('Legal Links', 'firebase-woo-auth'), null, 'firebase_woo_auth');

        // Add fields for Terms of Service and Privacy Policy URLs
        $this->add_settings_field('terms_of_service_url', __('Terms of Service URL', 'firebase-woo-auth'), 'firebase_woo_auth_links_section', 'render_text_field');
        $this->add_settings_field('privacy_policy_url', __('Privacy Policy URL', 'firebase-woo-auth'), 'firebase_woo_auth_links_section', 'render_text_field');
    }

    public function add_settings_field($id, $label, $section, $render_method) {
        add_settings_field($id, $label, array($this, $render_method), 'firebase_woo_auth', $section, array('id' => $id));
    }

    // Renders text fields for Firebase configuration and URLs
    public function render_text_field($args) {
        $options = get_option('firebase_woo_auth_options');
        ?>
        <input type="text" name="firebase_woo_auth_options[<?php echo esc_attr($args['id']); ?>]" value="<?php echo isset($options[$args['id']]) ? esc_attr($options[$args['id']]) : ''; ?>" class="regular-text">
        <?php
    }

    // Renders checkboxes for enabling/disabling sign-in methods
    public function render_checkbox_field($args) {
        $options = get_option('firebase_woo_auth_options');
        $checked = isset($options[$args['id']]) ? $options[$args['id']] : '';  // Prevent undefined array key error
        ?>
        <input type="checkbox" name="firebase_woo_auth_options[<?php echo esc_attr($args['id']); ?>]" value="1" <?php checked($checked, '1'); ?>>
        <?php
    }

    public function sanitize_settings($input) {
        $output = array();
        foreach ($input as $key => $value) {
            $output[$key] = sanitize_text_field($value);
        }
        return $output;
    }
}
