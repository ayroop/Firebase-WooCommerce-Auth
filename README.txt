=== Firebase WooCommerce Authentication ===
Contributors: ayrop
Tags: firebase, authentication, woocommerce, login, social login, security
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Firebase Authentication with WooCommerce for secure and modern user authentication.

== Description ==

Firebase WooCommerce Authentication seamlessly integrates Firebase Authentication with your WooCommerce store, providing a secure and modern authentication system. This plugin allows your customers to sign in using various methods including email/password, phone number, and popular social providers like Google, GitHub, Twitter, and Microsoft.

= Key Features =

* Secure Firebase Authentication integration
* Multiple sign-in methods:
  * Email/Password
  * Phone Number
  * Google
  * GitHub
  * Twitter
  * Microsoft
* Seamless WooCommerce integration
* Automatic user profile synchronization
* Customizable authentication UI
* Debug logging for troubleshooting
* GDPR compliant
* Mobile-friendly design
* Secure token verification
* Automatic log rotation
* Protected debug logs

= Requirements =

* WordPress 5.0 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher
* Firebase project with Authentication enabled

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/firebase-woo-auth` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Firebase WooCommerce Auth settings page
4. Configure your Firebase project credentials
5. Enable desired authentication methods
6. Place the `[firebase_auth_ui]` shortcode where you want the authentication UI to appear

== Security ==

= Debug Log Security =

The plugin includes debug logging functionality for troubleshooting authentication issues. For security reasons, ensure the debug logs are not accessible via web:

1. Apache (.htaccess):
```apache
# Block access to debug.log
<Files "debug.log">
    Order allow,deny
    Deny from all
</Files>
```

2. Nginx:
```nginx
# Block access to debug.log
location ~* /debug\.log$ {
    deny all;
    return 403;
}
```

= Token Verification =

The plugin uses secure JWT verification for Firebase tokens:
* Implements proper signature verification
* Validates token claims (exp, iat, aud, iss)
* Uses secure public key handling
* Includes retry mechanism for JWKS fetching
* Implements proper error handling

= File Access Protection =

The plugin includes protection for sensitive directories:
* Blocks direct access to vendor directory
* Blocks direct access to includes directory
* Blocks direct access to logs directory
* Prevents directory listing

== Frequently Asked Questions ==

= What Firebase Authentication methods are supported? =

The plugin supports:
* Email/Password
* Phone Number
* Google
* GitHub
* Twitter
* Microsoft

= How do I get my Firebase credentials? =

1. Go to the Firebase Console (https://console.firebase.google.com/)
2. Create a new project or select an existing one
3. Enable Authentication in the Firebase Console
4. Go to Project Settings
5. Under "Your apps", click the web icon
6. Register your app and copy the configuration

= Can I customize the authentication UI? =

Yes, you can customize the UI through the plugin settings and by modifying the CSS file located at `assets/css/firebase-custom-ui.css`.

== Screenshots ==

1. Authentication UI with multiple sign-in options
2. Admin settings page
3. Debug log page
4. Security settings

== Changelog ==

= 1.0.0 =
* Initial release
* Secure token verification
* Debug log management
* File access protection
* Automatic log rotation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Firebase WooCommerce Authentication.

== License ==

This plugin is licensed under the GPL v2 or later.

== Credits ==

This plugin was developed by Ayrop.com.