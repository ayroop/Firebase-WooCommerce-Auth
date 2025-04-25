<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FirebaseWooAuth {

    private $firebaseConfig;

    public function __construct() {
        $this->firebaseConfig = $this->get_firebase_config();
        $this->init_hooks();
    }

    private function init_hooks() {
        add_shortcode('firebase_auth_ui', array($this, 'render_auth_ui'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_nopriv_firebase_authenticate', array($this, 'firebase_authenticate'));
        add_action('wp_ajax_firebase_authenticate', array($this, 'firebase_authenticate'));

        // Disable cache for specific pages
        add_action('template_redirect', array($this, 'disable_cache_for_specific_pages'));

        // Add notice to prompt users to update their email
        add_action('woocommerce_before_my_account', array($this, 'prompt_update_email'));

        // Ensure phone number appears in the WooCommerce billing fields
        add_filter('woocommerce_checkout_fields', array($this, 'add_phone_to_billing_fields'));
    }

	// Function to customize WooCommerce billing fields
    public function customize_woocommerce_billing_fields($fields) {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Fetch billing data from user meta
            $fields['billing']['billing_first_name']['default'] = get_user_meta($user_id, 'billing_first_name', true);
            $fields['billing']['billing_last_name']['default'] = get_user_meta($user_id, 'billing_last_name', true);
            $fields['billing']['billing_email']['default'] = wp_get_current_user()->user_email;
            $fields['billing']['billing_phone']['default'] = get_user_meta($user_id, 'billing_phone', true);
        }
        
        return $fields;
    }
	
    private function get_firebase_config() {
        // Retrieve the options from the WordPress database
        $options = get_option('firebase_woo_auth_options');

        return [
            'apiKey' => isset($options['firebase_api_key']) ? $options['firebase_api_key'] : '',
            'authDomain' => isset($options['firebase_auth_domain']) ? $options['firebase_auth_domain'] : '',
            'projectId' => isset($options['firebase_project_id']) ? $options['firebase_project_id'] : '',
            'storageBucket' => isset($options['firebase_storage_bucket']) ? $options['firebase_storage_bucket'] : '',
            'messagingSenderId' => isset($options['firebase_messaging_sender_id']) ? $options['firebase_messaging_sender_id'] : '',
            'appId' => isset($options['firebase_app_id']) ? $options['firebase_app_id'] : '',
            'measurementId' => isset($options['firebase_measurement_id']) ? $options['firebase_measurement_id'] : ''
        ];
    }

    public function enqueue_scripts() {
        $options = get_option('firebase_woo_auth_options');

		// Firebase SDK scripts
		wp_enqueue_script('firebase-app', 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js', array(), null, true);
		wp_enqueue_script('firebase-auth', 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js', array('firebase-app'), null, true);
		wp_enqueue_script('firebase-ui', 'https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js', array('firebase-auth'), null, true);
		wp_enqueue_style('firebaseui-css', 'https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css');

        // Custom script
        wp_enqueue_script('firebase-woo-auth', FIREBASE_WOO_AUTH_URL . 'assets/js/firebase-woo-auth.js', array('jquery', 'firebase-app', 'firebase-auth', 'firebase-ui'), '1.1', true);

        // Pass localized data to the script
        wp_localize_script('firebase-woo-auth', 'FirebaseWooAuth', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'firebaseConfig' => $this->firebaseConfig,
            'enable_phone' => !empty($options['enable_phone']),
            'enable_google' => !empty($options['enable_google']),
            'enable_github' => !empty($options['enable_github']),
            'enable_twitter' => !empty($options['enable_twitter']),
            'enable_email_password' => !empty($options['enable_email_password']),
            'enable_email_link' => !empty($options['enable_email_link']),
            'enable_microsoft' => !empty($options['enable_microsoft']),
            'terms_of_service_url' => isset($options['terms_of_service_url']) ? esc_url($options['terms_of_service_url']) : '',
            'privacy_policy_url' => isset($options['privacy_policy_url']) ? esc_url($options['privacy_policy_url']) : ''
        ));
    }

    public function render_auth_ui() {
    // Check if the user is already logged in
    if (is_user_logged_in()) {
        return; // Do not render FirebaseUI if the user is logged in
    }

    // Store the current page URL in the session or a transient
    $current_page_url = home_url( add_query_arg( null, null ) );
    set_transient( 'firebase_auth_redirect_url', $current_page_url, 60 * 60 ); // Store for 1 hour

    // Render FirebaseUI if the user is not logged in
    ob_start();
    ?>
    <div id="loader">
        <img src="<?php echo FIREBASE_WOO_AUTH_URL . 'assets/images/ripples.svg'; ?>" alt="Loading...">
    </div>
    <div id="firebaseui-auth-container"></div>
    <?php
    return ob_get_clean();
}

    public function firebase_authenticate() {
    $id_token = isset($_POST['id_token']) ? sanitize_text_field($_POST['id_token']) : '';

    if (empty($id_token)) {
        wp_send_json_error('No ID token provided.');
    }

    $verified_token = $this->verify_id_token($id_token);

    if (!$verified_token) {
        wp_send_json_error('Invalid ID token.');
    }

    // Extract user data from Firebase token
    $uid = $verified_token['sub'];
    $email = isset($verified_token['email']) ? $verified_token['email'] : '';
    $displayName = isset($verified_token['name']) ? $verified_token['name'] : '';
    $email_verified = isset($verified_token['email_verified']) ? $verified_token['email_verified'] : false;
    $phoneNumber = isset($verified_token['phone_number']) ? $verified_token['phone_number'] : '';
    $provider = $verified_token['firebase']['sign_in_provider']; // Identify provider (phone, GitHub, Google, etc.)

    // Handle the case where no email is provided, e.g., phone auth
    if (empty($email)) {
        if (!empty($phoneNumber)) {
            // Generate placeholder email based on phone number
            $site_domain = parse_url(home_url(), PHP_URL_HOST);
            $email = preg_replace('/[^a-zA-Z0-9]/', '', $phoneNumber) . '@' . $site_domain;
            $username = sanitize_user(str_replace('+', '', $phoneNumber));
        } else {
            wp_send_json_error('Neither email nor phone number available. Cannot create account.');
        }
        $need_email_update = true; // Mark that email needs to be updated later
    } else {
        // Create a username from email if available
        $username = sanitize_user(current(explode('@', $email)), true);
        $need_email_update = false;
    }

    // Ensure unique username
    if (username_exists($username)) {
        $username .= '_' . wp_generate_password(4, false, false); // Make unique
    }

    // Check if the user already exists by email
    $user = get_user_by('email', $email);

    if (!$user) {
        // Create a new WooCommerce user if not found
        $random_password = wp_generate_password(12, false);
        $user_id = wp_create_user($username, $random_password, $email);

        // Update WooCommerce fields with Firebase data
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $displayName,
            'first_name' => isset($verified_token['given_name']) ? $verified_token['given_name'] : '',
            'last_name' => isset($verified_token['family_name']) ? $verified_token['family_name'] : '',
            'nickname' => $displayName,
        ));

        // Populate WooCommerce billing fields
        $this->populate_woocommerce_billing_info($user_id, $verified_token);

        // If phone auth is used, mark that the email needs to be updated
        if ($need_email_update) {
            update_user_meta($user_id, 'need_email_update', true);
        }

        // Re-fetch the newly created user
        $user = get_user_by('id', $user_id);
    } else {
        // Update existing WooCommerce user with the latest Firebase data
        wp_update_user(array(
            'ID' => $user->ID,
            'display_name' => $displayName,
            'first_name' => isset($verified_token['given_name']) ? $verified_token['given_name'] : '',
            'last_name' => isset($verified_token['family_name']) ? $verified_token['family_name'] : '',
            'nickname' => $displayName,
        ));

        // Update WooCommerce billing info for existing user
        $this->populate_woocommerce_billing_info($user->ID, $verified_token);
    }

    // Log the user in and maintain the WooCommerce session
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    WC()->session->set_customer_session_cookie(true);

    // Redirect the user to the correct page (e.g., checkout or account)
    $redirect_url = get_transient('firebase_auth_redirect_url');
    if (!$redirect_url) {
        $redirect_url = wc_get_checkout_url(); // Default to checkout page if no redirect is set
    }
    delete_transient('firebase_auth_redirect_url'); // Clean up transient

    wp_send_json_success(array('redirect_url' => $redirect_url));

    wp_die();
}

// Helper function to populate WooCommerce billing fields
public function populate_woocommerce_billing_info($user_id, $verified_token) {
    $billing_data = array(
        'first_name' => isset($verified_token['given_name']) ? $verified_token['given_name'] : '',
        'last_name' => isset($verified_token['family_name']) ? $verified_token['family_name'] : '',
        'email' => isset($verified_token['email']) ? $verified_token['email'] : '',
        'phone' => isset($verified_token['phone_number']) ? $verified_token['phone_number'] : '',
    );
	//Update Woo Billing Information
    foreach ($billing_data as $field => $value) {
        if (!empty($value)) {
            update_user_meta($user_id, "billing_{$field}", sanitize_text_field($value));
        }
    }

    // Allow other plugins or custom code to modify billing fields
    do_action('firebase_woo_auth_after_billing_update', $user_id, $billing_data);
}


    private function verify_id_token($id_token) {
        $jwks = $this->get_firebase_jwks();
        if (!$jwks) {
            return false;
        }

        list($header64, $payload64, $signature64) = explode('.', $id_token);
        $header = json_decode($this->urlsafe_b64decode($header64), true);

        if (!isset($header['kid'])) {
            return false;
        }

        $kid = $header['kid'];

        if (!isset($jwks[$kid])) {
            return false;
        }

        $cert = $jwks[$kid];
        $public_key = openssl_pkey_get_public($cert);
        if (!$public_key) {
            return false;
        }

        $verified_token = $this->decode_jwt($id_token, $public_key);

        //openssl_free_key($public_key);

        return $verified_token;
    }

    private function get_firebase_jwks() {
        $keys = get_transient('firebase_public_keys');

        if (!$keys) {
            $response = wp_remote_get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

            if (is_wp_error($response)) {
                return false;
            }

            $keys = json_decode($response['body'], true);

            if (!$keys) {
                return false;
            }

            set_transient('firebase_public_keys', $keys, HOUR_IN_SECONDS);
        }

        return $keys;
    }

    private function decode_jwt($jwt, $public_key) {
        $segments = explode('.', $jwt);
        if (count($segments) != 3) {
            return false;
        }

        list($header64, $payload64, $signature64) = $segments;

        $header = json_decode($this->urlsafe_b64decode($header64), true);
        $payload = json_decode($this->urlsafe_b64decode($payload64), true);
        $signature = $this->urlsafe_b64decode($signature64);

        $data = "$header64.$payload64";

        $success = openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA256);

        if ($success === 1) {
            $project_id = $this->firebaseConfig['projectId'];
            if ($payload['iss'] !== "https://securetoken.google.com/$project_id" || $payload['aud'] !== $project_id) {
                return false;
            }

            $current_time = time();
            if ($payload['exp'] < $current_time || $payload['iat'] > $current_time) {
                return false;
            }

            return $payload;
        } else {
            return false;
        }
    }

    private function urlsafe_b64decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function prompt_update_email() {
        $user_id = get_current_user_id();
        if ($user_id && get_user_meta($user_id, 'need_email_update', true)) {
            echo '<div class="woocommerce-info">Please update your email address to complete your account setup.</div>';
        }
    }

    public function disable_cache_for_specific_pages() {
        if (is_page('my-account') || is_page('checkout')) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
        }
    }

    // Add phone number to WooCommerce billing fields
    public function add_phone_to_billing_fields($fields) {
        $fields['billing']['billing_phone'] = array(
            'label'       => __('Phone', 'woocommerce'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'clear'       => true,
            'priority'    => 20,
        );
        return $fields;
    }
}
