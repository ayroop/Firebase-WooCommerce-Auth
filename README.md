# Firebase WooCommerce Auth

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)
[![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-Compatible-green.svg)](https://woocommerce.com/)
[![Firebase Auth](https://img.shields.io/badge/Firebase-Auth-yellow.svg)](https://firebase.google.com/products/auth)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

_Author: Ayrop_

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Demo / Screenshots](#demo--screenshots)
- [Installation](#installation)
- [Firebase Setup](#firebase-setup)
- [Plugin Configuration](#plugin-configuration)
- [Usage](#usage)
- [Sign-In Methods Supported](#sign-in-methods-supported)
- [Developer Notes](#developer-notes)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgments](#acknowledgments)

---

## Overview

**Firebase WooCommerce Auth** enables users to log into your WooCommerce store using Firebase's powerful Authentication methods—including Google, Email/Password, Phone, GitHub, Twitter, Microsoft, and more. Users are seamlessly logged into WooCommerce, and new accounts are created dynamically with synced profile data.

---

## Features

- 🔒 **Secure WooCommerce Login via Firebase**
- 🪪 Supports modern social providers (Google, GitHub, Microsoft, Twitter, etc.)
- 📱 Phone authentication with dynamic user creation
- 📧 Email/Password & Email Link authentication
- 🚦 Automatic population of WooCommerce billing fields
- ⚙️ Admin settings page for easy Firebase config and sign-in methods
- 📜 Customizable Terms of Service & Privacy Policy links in the auth flow
- 🛒 WooCommerce session integration
- 👤 User prompts when email update is needed (e.g., for phone-only sign-ins)
- ⚡ Ready to use—just install, configure Firebase, and you're good to go!

---

## Demo / Screenshots

> _Add screenshots or a GIF here showcasing the login or admin settings page_

---

## Installation

**Minimum Requirements:**
- WordPress 5.2+
- WooCommerce 4.0+
- PHP 7.2+
- [Firebase Project](https://console.firebase.google.com/)
- A Google Cloud project with Authentication enabled

**1. Download and Install**
- From GitHub release or source:  
  Download and extract the plugin to your WordPress plugins directory:
  ```
  wp-content/plugins/firebase-woo-auth/
  ```
- Or via the WordPress Admin dashboard:
  - Upload the `.zip` file via **Plugins > Add New > Upload Plugin**.

**2. Activate**
- In your WordPress Admin, go to **Plugins** and activate **Firebase WooCommerce Auth**.

---

## Firebase Setup

1. Visit the [Firebase Console](https://console.firebase.google.com/) and create a new project.
2. Under **Build > Authentication > Get Started**, enable your preferred Sign-in Methods.
3. Go to **Project Settings > General > Your apps** and register your web app.  
4. Copy your Firebase config—the API Key, Auth Domain, etc.
5. Set up OAuth credentials for each provider (Google, GitHub, etc.) as needed.

---

## Plugin Configuration

Once activated:

1. Go to **Settings > Firebase Woo Auth** in your WordPress Admin area.
2. Paste your Firebase configuration values (API Key, Auth Domain, etc.).
3. Enable the authentication providers you want to offer.
4. (Optional) Set **Terms of Service** and **Privacy Policy** URLs for legal compliance.
5. Save changes.

---

## Usage

**Shortcode**

Add the Firebase Authentication UI anywhere (e.g., a login/registration page):

```
[firebase_auth_ui]
```

This renders the Firebase Auth UI widget, letting users sign in via the enabled methods.

**Automatic Sync**

- New WooCommerce users are created if not already registered.
- Billing fields (name, email, phone) are auto-populated from Firebase profile.
- If no email (e.g., phone auth), a placeholder is used and the user is prompted to update it.

---

## Sign-In Methods Supported

- [x] Google
- [x] Email/Password
- [x] Email Link (Magic Link)
- [x] Phone via SMS
- [x] GitHub
- [x] Twitter
- [x] Microsoft

---

## Developer Notes

- Uses the latest Firebase JS SDK and FirebaseUI.
- Extensible: WordPress actions/filters available after billing update (`firebase_woo_auth_after_billing_update`).
- Session & cache headers are managed for My Account/Checkout pages.
- Secure, JWT Firebase ID token verification with caching of Google public keys.

---

## Contributing

Contributions, issues, and feature requests are welcome!  
Please open a [GitHub issue](https://github.com/ayroop/Go-Telegram-Bot-To-Binance/issues) to discuss major changes.

**To contribute:**
- Fork the repository
- Create a feature branch (`git checkout -b feature/my-feature`)
- Commit your changes
- Open a Pull Request

---

## License

Distributed under the [MIT License](LICENSE).

---

## Acknowledgments

- [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- [WooCommerce Developer Docs](https://developer.woocommerce.com/)
- Inspired by the open source WordPress & Firebase community

---

_Developed with ❤️ by Ayrop_