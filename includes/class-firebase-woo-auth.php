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
        add_action('wp_ajax_nopriv_firebase_auth_popup', array($this, 'render_auth_popup'));
        add_action('wp_ajax_firebase_auth_popup', array($this, 'render_auth_popup'));

        // Disable cache for specific pages
        add_action('template_redirect', array($this, 'disable_cache_for_specific_pages'));

        // Add notice to prompt users to update their email
        add_action('woocommerce_before_my_account', array($this, 'prompt_update_email'));

        // Ensure phone number appears in the WooCommerce billing fields
        add_filter('woocommerce_checkout_fields', array($this, 'add_phone_to_billing_fields'));
        
        // Add hook for customizing WooCommerce billing fields
        add_filter('woocommerce_checkout_fields', array($this, 'customize_woocommerce_billing_fields'));
    }

	// Function to customize WooCommerce billing fields
    public function customize_woocommerce_billing_fields($fields) {
        if (!is_user_logged_in() || !class_exists('WooCommerce')) {
            return $fields;
        }

        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return $fields;
        }

        // Enhanced field mapping with fallbacks
        $fields['billing']['billing_first_name']['default'] = get_user_meta($user_id, 'billing_first_name', true) ?: $user->first_name;
        $fields['billing']['billing_last_name']['default'] = get_user_meta($user_id, 'billing_last_name', true) ?: $user->last_name;
        $fields['billing']['billing_email']['default'] = $user->user_email;
        $fields['billing']['billing_phone']['default'] = get_user_meta($user_id, 'billing_phone', true);
        
        // Add address fields if available
        $address_fields = array(
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country'
        );
        
        foreach ($address_fields as $field) {
            $value = get_user_meta($user_id, $field, true);
            if (!empty($value)) {
                $fields['billing'][$field]['default'] = $value;
            }
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
            'privacy_policy_url' => isset($options['privacy_policy_url']) ? esc_url($options['privacy_policy_url']) : '',
            'nonce' => wp_create_nonce('firebase_woo_auth_nonce')
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
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'firebase_woo_auth_nonce')) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            $id_token = isset($_POST['id_token']) ? sanitize_text_field($_POST['id_token']) : '';

            if (empty($id_token)) {
                wp_send_json_error('No ID token provided');
                return;
            }

            // Verify the ID token
            $verified_token = $this->verify_id_token($id_token);
            if (!$verified_token) {
                wp_send_json_error('Invalid ID token');
                return;
            }

            // Extract user data
            $uid = $verified_token['sub'];
            $email = isset($verified_token['email']) ? $verified_token['email'] : '';
            $displayName = isset($verified_token['name']) ? $verified_token['name'] : '';
            $email_verified = isset($verified_token['email_verified']) ? $verified_token['email_verified'] : false;
            $phoneNumber = isset($verified_token['phone_number']) ? $verified_token['phone_number'] : '';
            $provider = $verified_token['firebase']['sign_in_provider'];

            // Handle missing email
            if (empty($email)) {
                if (!empty($phoneNumber)) {
                    $site_domain = parse_url(home_url(), PHP_URL_HOST);
                    $email = preg_replace('/[^a-zA-Z0-9]/', '', $phoneNumber) . '@' . $site_domain;
                    $username = sanitize_user(str_replace('+', '', $phoneNumber));
                } else {
                    wp_send_json_error('Neither email nor phone number available');
                    return;
                }
                $need_email_update = true;
            } else {
                $username = sanitize_user(current(explode('@', $email)), true);
                $need_email_update = false;
            }

            // Ensure unique username
            if (username_exists($username)) {
                $username .= '_' . wp_generate_password(4, false, false);
            }

            // Check if user exists
            $user = get_user_by('email', $email);

            if (!$user) {
                // Create new user
                $random_password = wp_generate_password(12, false);
                $user_id = wp_create_user($username, $random_password, $email);

                if (is_wp_error($user_id)) {
                    wp_send_json_error($user_id->get_error_message());
                    return;
                }

                // Update user data
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $displayName,
                    'first_name' => isset($verified_token['given_name']) ? $verified_token['given_name'] : '',
                    'last_name' => isset($verified_token['family_name']) ? $verified_token['family_name'] : '',
                    'nickname' => $displayName,
                ));

                // Update user meta
                if ($need_email_update) {
                    update_user_meta($user_id, 'need_email_update', true);
                }

                $user = get_user_by('id', $user_id);
            }

            // Log the user in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            
            // Set WooCommerce session
            if (class_exists('WooCommerce')) {
                WC()->session->set_customer_session_cookie(true);
            }

            // Get redirect URL
            $redirect_url = get_transient('firebase_auth_redirect_url');
            if (!$redirect_url) {
                $redirect_url = wc_get_checkout_url();
            }
            delete_transient('firebase_auth_redirect_url');

            wp_send_json_success(array('redirect_url' => $redirect_url));

        } catch (Exception $e) {
            $this->log_error('Authentication error: ' . $e->getMessage());
            wp_send_json_error('Server error: ' . $e->getMessage());
        }
    }

    private function verify_id_token($id_token) {
        if (empty($id_token)) {
            $this->log_error('Empty ID token provided');
            return false;
        }

        try {
            // Use Firebase Admin SDK for proper token verification
            if (!class_exists('Firebase\JWT\JWT')) {
                require_once FIREBASE_WOO_AUTH_PATH . 'vendor/autoload.php';
            }

            $project_id = $this->firebaseConfig['projectId'];
            if (empty($project_id)) {
                $this->log_error('Firebase Project ID is not configured');
                return false;
            }

            // Fetch JWKS with retry mechanism
            $jwks_url = "https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com";
            $jwks = $this->get_firebase_jwks_with_retry($jwks_url);
            
            if (!$jwks) {
                $this->log_error('Failed to fetch JWKS after retries');
                return false;
            }

            // Parse and validate token structure
            $segments = explode('.', $id_token);
            if (count($segments) !== 3) {
                $this->log_error('Invalid token structure');
                return false;
            }

            list($header64, $payload64, $signature64) = $segments;
            
            // Decode and validate header
            $header = json_decode($this->urlsafe_b64decode($header64), true);
            if (!$header || !isset($header['kid'])) {
                $this->log_error('Invalid token header or missing KID');
                return false;
            }

            $kid = $header['kid'];
            if (!isset($jwks[$kid])) {
                $this->log_error('Invalid KID in token');
                return false;
            }

            // Get public key and verify signature
            $public_key = $this->get_public_key_from_jwk($jwks[$kid]);
            if (!$public_key) {
                $this->log_error('Failed to generate public key from JWK');
                return false;
            }

            // Verify token signature and decode payload
            $decoded = $this->decode_jwt($id_token, $public_key);
            if (!$decoded) {
                $this->log_error('Token signature verification failed');
                return false;
            }

            // Validate token claims
            if (!$this->validate_token_claims($decoded, $project_id)) {
                return false;
            }

            return $decoded;
        } catch (Exception $e) {
            $this->log_error('Token verification failed: ' . $e->getMessage());
            return false;
        }
    }

    private function get_firebase_jwks_with_retry($jwks_url, $max_retries = 3) {
        $retry_count = 0;
        $last_error = null;

        while ($retry_count < $max_retries) {
            try {
                $response = wp_remote_get($jwks_url, array(
                    'timeout' => 10,
                    'sslverify' => true
                ));

                if (is_wp_error($response)) {
                    $last_error = $response->get_error_message();
                    $retry_count++;
                    sleep(1); // Wait before retry
                    continue;
                }

                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code !== 200) {
                    $last_error = "HTTP {$status_code}";
                    $retry_count++;
                    sleep(1);
                    continue;
                }

                $body = wp_remote_retrieve_body($response);
                $jwks = json_decode($body, true);

                if (!$jwks) {
                    $last_error = 'Invalid JWKS response';
                    $retry_count++;
                    sleep(1);
                    continue;
                }

                // Cache successful response
                set_transient('firebase_public_keys', $jwks, HOUR_IN_SECONDS);
                return $jwks;

            } catch (Exception $e) {
                $last_error = $e->getMessage();
                $retry_count++;
                sleep(1);
            }
        }

        $this->log_error("Failed to fetch JWKS after {$max_retries} attempts. Last error: {$last_error}");
        return false;
    }

    private function validate_token_claims($decoded, $project_id) {
        $now = time();
        
        // Check if token is expired
        if (!isset($decoded->exp) || $decoded->exp < $now) {
            $this->log_error('Token has expired');
            return false;
        }

        // Check if token is not yet valid
        if (!isset($decoded->iat) || $decoded->iat > $now) {
            $this->log_error('Token is not yet valid');
            return false;
        }

        // Verify audience
        if (!isset($decoded->aud) || $decoded->aud !== $project_id) {
            $this->log_error('Invalid audience');
            return false;
        }

        // Verify issuer
        $expected_issuer = "https://securetoken.google.com/{$project_id}";
        if (!isset($decoded->iss) || $decoded->iss !== $expected_issuer) {
            $this->log_error('Invalid issuer');
            return false;
        }

        // Verify subject (user ID)
        if (!isset($decoded->sub) || empty($decoded->sub)) {
            $this->log_error('Missing or invalid subject');
            return false;
        }

        // Verify auth_time if present
        if (isset($decoded->auth_time) && $decoded->auth_time > $now) {
            $this->log_error('Invalid auth_time');
            return false;
        }

        return true;
    }

    private function decode_jwt($jwt, $public_key) {
        try {
            $segments = explode('.', $jwt);
            if (count($segments) !== 3) {
                return false;
            }

            list($header64, $payload64, $signature64) = $segments;
            $data = "$header64.$payload64";
            $signature = $this->urlsafe_b64decode($signature64);

            // Verify signature
            $success = openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA256);
            if ($success !== 1) {
                return false;
            }

            // Decode payload
            $payload = json_decode($this->urlsafe_b64decode($payload64));
            if (!$payload) {
                return false;
            }

            return $payload;
        } catch (Exception $e) {
            $this->log_error('JWT decoding failed: ' . $e->getMessage());
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

    public function render_auth_popup() {
        // Set proper headers for popup
        header('X-Frame-Options: SAMEORIGIN');
        header('Cross-Origin-Opener-Policy: unsafe-none');
        header('Cross-Origin-Embedder-Policy: unsafe-none');
        header('Access-Control-Allow-Origin: ' . home_url());
        header('Access-Control-Allow-Credentials: true');
        header('Permissions-Policy: storage-access=*');
        
        // Get provider from request
        $provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
        
        // Render popup content
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php _e('Authentication', 'firebase-woo-auth'); ?></title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Lexend Deca', sans-serif;
                }
            </style>
            <?php wp_head(); ?>
        </head>
        <body>
            <div id="firebaseui-auth-container"></div>
            <script>
                // Initialize Firebase UI in popup
                window.addEventListener('load', function() {
                    try {
                        // Initialize Firebase if not already initialized
                        if (!firebase.apps.length) {
                            firebase.initializeApp(<?php echo json_encode($this->firebaseConfig); ?>);
                        }

                        // Configure Firebase UI
                        const ui = new firebaseui.auth.AuthUI(firebase.auth());
                        const uiConfig = {
                            callbacks: {
                                signInSuccessWithAuthResult: function(authResult) {
                                    // Send result back to opener
                                    if (window.opener) {
                                        window.opener.postMessage({
                                            type: 'firebase_auth_complete',
                                            id_token: authResult.credential.idToken
                                        }, window.opener.location.origin);
                                    }
                                    window.close();
                                    return false;
                                },
                                signInFailure: function(error) {
                                    if (window.opener) {
                                        window.opener.postMessage({
                                            type: 'firebase_auth_error',
                                            error: error.message
                                        }, window.opener.location.origin);
                                    }
                                    window.close();
                                    return false;
                                }
                            },
                            signInFlow: 'popup',
                            signInOptions: [
                                <?php echo $this->get_provider_config($provider); ?>
                            ],
                            tosUrl: '<?php echo esc_url(home_url('/terms')); ?>',
                            privacyPolicyUrl: '<?php echo esc_url(home_url('/privacy')); ?>'
                        };

                        // Start Firebase UI
                        ui.start('#firebaseui-auth-container', uiConfig);
                    } catch (error) {
                        console.error('Firebase UI initialization error:', error);
                        if (window.opener) {
                            window.opener.postMessage({
                                type: 'firebase_auth_error',
                                error: error.message
                            }, window.opener.location.origin);
                        }
                        window.close();
                    }
                });
            </script>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    private function get_provider_config($provider) {
        $options = get_option('firebase_woo_auth_options');
        $config = '';

        switch ($provider) {
            case 'google':
                if (!empty($options['enable_google'])) {
                    $config = 'firebase.auth.GoogleAuthProvider.PROVIDER_ID';
                }
                break;
            case 'github':
                if (!empty($options['enable_github'])) {
                    $config = 'firebase.auth.GithubAuthProvider.PROVIDER_ID';
                }
                break;
            case 'twitter':
                if (!empty($options['enable_twitter'])) {
                    $config = 'firebase.auth.TwitterAuthProvider.PROVIDER_ID';
                }
                break;
            case 'microsoft':
                if (!empty($options['enable_microsoft'])) {
                    $config = 'firebase.auth.OAuthProvider("microsoft.com").PROVIDER_ID';
                }
                break;
            case 'phone':
                if (!empty($options['enable_phone'])) {
                    $config = 'firebase.auth.PhoneAuthProvider.PROVIDER_ID';
                }
                break;
            default:
                if (!empty($options['enable_email_password'])) {
                    $config = '{
                        provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                        requireDisplayName: true
                    }';
                }
        }

        return $config;
    }
}
