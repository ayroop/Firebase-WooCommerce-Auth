/* globals firebase, firebaseui, FirebaseWooAuth, jQuery */

/**
 * Firebase WooCommerce Authentication
 * 
 * Production-ready singleton implementation of FirebaseUI for WordPress/WooCommerce
 * Handles authentication flow, error display, and user experience
 */
(function(window, document, $) {
    'use strict';

    // UI Helper functions
    function hideLoader() { $('#loader').fadeOut(); }
    function showLoader() { $('#loader').fadeIn(); }
    function showError(message) {
        $('.woocommerce-error').remove();
        $('<div class="woocommerce-error"></div>').text(message).insertBefore('#firebaseui-auth-container');
    }
    function hideError() { $('.woocommerce-error').remove(); }

    // Only initialize Firebase *once* globally
    function ensureFirebaseInitialized() {
        if (!firebase.apps.length) {
            firebase.initializeApp(FirebaseWooAuth.firebaseConfig);
        }
        return firebase;
    }

    // Get singleton FirebaseUI instance
    function getFirebaseUIInstance() {
        let ui = firebaseui.auth.AuthUI.getInstance();
        if (!ui) {
            ui = new firebaseui.auth.AuthUI(firebase.auth());
        }
        return ui;
    }

    // Helper function to get sign-in options based on admin settings
    function getSignInOptions() {
        var options = [];
        
        if (FirebaseWooAuth.enable_phone) {
            options.push({
                provider: firebase.auth.PhoneAuthProvider.PROVIDER_ID,
                defaultCountry: 'US'
            });
        }
        
        if (FirebaseWooAuth.enable_google) {
            options.push(firebase.auth.GoogleAuthProvider.PROVIDER_ID);
        }
        
        if (FirebaseWooAuth.enable_github) {
            options.push(firebase.auth.GithubAuthProvider.PROVIDER_ID);
        }
        
        if (FirebaseWooAuth.enable_twitter) {
            options.push(firebase.auth.TwitterAuthProvider.PROVIDER_ID);
        }
        
        if (FirebaseWooAuth.enable_email_password) {
            options.push({
                provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                requireDisplayName: true
            });
        }
        
        if (FirebaseWooAuth.enable_email_link) {
            options.push({
                provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                signInMethod: firebase.auth.EmailAuthProvider.EMAIL_LINK_SIGN_IN_METHOD
            });
        }
        
        if (FirebaseWooAuth.enable_microsoft) {
            options.push('microsoft.com');
        }
        
        return options;
    }

    // Process authentication with backend
    function processAuthentication(user) {
        return new Promise((resolve, reject) => {
            showLoader();
            
            user.getIdToken().then(function(idToken) {
                $.ajax({
                    url: FirebaseWooAuth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'firebase_authenticate',
                        id_token: idToken,
                        nonce: FirebaseWooAuth.nonce || ''
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.data && response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                            resolve(response);
                        } else {
                            hideLoader();
                            reject(new Error(response.data || 'Authentication failed'));
                        }
                    },
                    error: function(xhr, status, error) {
                        hideLoader();
                        let errorMessage = 'Server error: ';
                        try {
                            // Improved error handling
                            if (xhr.status === 500) {
                                errorMessage += 'Internal server error. Please contact site administrator.';
                                console.error('Server returned 500 error. Response:', xhr.responseText);
                            } else if (xhr.responseText) {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage += response.data || error || 'Unknown error';
                            } else {
                                errorMessage += error || 'Unknown error';
                            }
                        } catch (e) {
                            errorMessage += error || 'Unknown error';
                            console.error('Error parsing server response:', e);
                        }
                        reject(new Error(errorMessage));
                    },
                    timeout: 30000 // 30 second timeout
                });
            }).catch(function(error) {
                hideLoader();
                reject(error);
            });
        });
    }

    // Main function to launch FirebaseUI widget
    function launchFirebaseUI() {
        hideError();
        
        // Check if the container exists
        if (!$('#firebaseui-auth-container').length) {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log("FirebaseUI auth container not found on this page.");
            }
            return;
        }
        
        showLoader();
        
        try {
            ensureFirebaseInitialized();
            
            // Get the singleton instance
            var ui = getFirebaseUIInstance();
            
            // Reset the UI to ensure clean state
            ui.reset();
            
            var uiConfig = {
                callbacks: {
                    signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                        var user = authResult.user;
                        
                        $('#firebaseui-auth-container').fadeOut();
                        showLoader();
                        
                        // For email/password, require verification
                        if (authResult.additionalUserInfo.providerId === 'password' && !user.emailVerified) {
                            user.sendEmailVerification().then(function() {
                                hideLoader();
                                showError('Please verify your email before signing in. Verification email sent.');
                            }).catch(function(error) {
                                hideLoader();
                                showError('Error sending email verification: ' + error.message);
                            });
                            
                            firebase.auth().signOut();
                            $('#firebaseui-auth-container').fadeIn();
                            return false;
                        }
                        
                        // Process authentication with backend
                        processAuthentication(user).catch(function(error) {
                            hideLoader();
                            $('#firebaseui-auth-container').fadeIn();
                            showError('Authentication failed: ' + error.message);
                        });
                        
                        return false; // prevent default redirect
                    },
                    
                    signInFailure: function(error) {
                        hideLoader();
                        showError('Sign-in error: ' + error.message);
                        return Promise.resolve();
                    },
                    
                    uiShown: function() {
                        hideLoader();
                        hideError();
                        $('#firebaseui-auth-container').fadeIn();
                    }
                },
                signInFlow: 'popup',
                signInOptions: getSignInOptions(),
                tosUrl: FirebaseWooAuth.terms_of_service_url,
                privacyPolicyUrl: FirebaseWooAuth.privacy_policy_url
            };
            
            // Start the UI with the container
            ui.start('#firebaseui-auth-container', uiConfig);
        } catch (error) {
            hideLoader();
            showError('Authentication system initialization failed: ' + error.message);
            console.error('Firebase UI initialization error:', error);
        }
    }

    // Handle social auth in a popup window - REVISED for COOP compatibility
    function handleSocialAuth(provider) {
        return new Promise((resolve, reject) => {
            try {
                // Modern approach with COOP and COEP compatibility
                // Use postMessage for communication instead of direct window access
                
                // Generate a unique ID for this authentication attempt
                const authSessionId = 'auth_' + Math.random().toString(36).substring(2, 15);
                
                // Create popup with proper settings
                // Note: Not specifying crossorigin settings in the window.open 
                // as they're controlled by the response headers from server
                const popup = window.open(
                    'about:blank',
                    'FirebaseAuthPopup',
                    'width=600,height=600,left=100,top=100,resizable=yes,scrollbars=yes'
                );

                if (!popup) {
                    reject(new Error('Popup blocked. Please allow popups for this site and try again.'));
                    return;
                }

                // Store session ID in localStorage for the popup to retrieve
                window.localStorage.setItem('firebase_auth_session', authSessionId);
                
                // Set up message listener for secure communication
                const messageListener = function(event) {
                    // Enhanced origin verification
                    if (event.origin !== window.location.origin) {
                        console.debug('Ignoring message from unknown origin:', event.origin);
                        return;
                    }

                    // Listen only for messages with our session ID
                    if (event.data && event.data.authSessionId === authSessionId) {
                        if (event.data.type === 'firebase_auth_complete') {
                            window.removeEventListener('message', messageListener);
                            window.localStorage.removeItem('firebase_auth_session');
                            resolve(event.data);
                        } else if (event.data.type === 'firebase_auth_error') {
                            window.removeEventListener('message', messageListener);
                            window.localStorage.removeItem('firebase_auth_session');
                            reject(new Error(event.data.error));
                        }
                    }
                };

                window.addEventListener('message', messageListener);

                // Use a closure-based approach that doesn't rely on accessing popup.closed
                let isPopupClosed = false;
                const popupCheckInterval = 1000; // Check every second
                const popupMaxWaitTime = 300000; // 5 minutes max wait
                let elapsedTime = 0;
                
                const checkPopupStatus = function() {
                    if (isPopupClosed) return;
                    
                    elapsedTime += popupCheckInterval;
                    
                    // Try-catch to handle COOP restrictions
                    try {
                        // This might throw if popup has COOP restrictions
                        if (popup.closed) {
                            isPopupClosed = true;
                            clearInterval(popupCheckId);
                            window.removeEventListener('message', messageListener);
                            window.localStorage.removeItem('firebase_auth_session');
                            reject(new Error('Authentication window was closed.'));
                        }
                    } catch (e) {
                        // Silently ignore COOP errors
                        console.debug('Cannot access popup due to COOP policy. Continuing to wait for message.', e);
                    }
                    
                    // Timeout after max wait time
                    if (elapsedTime >= popupMaxWaitTime) {
                        isPopupClosed = true;
                        clearInterval(popupCheckId);
                        window.removeEventListener('message', messageListener);
                        window.localStorage.removeItem('firebase_auth_session');
                        
                        try {
                            // Try to close the popup
                            popup.close();
                        } catch (e) {
                            // Ignore COOP errors
                        }
                        
                        reject(new Error('Authentication timed out. Please try again.'));
                    }
                };
                
                const popupCheckId = setInterval(checkPopupStatus, popupCheckInterval);

                // Construct auth URL with session ID
                const authUrl = `${window.location.origin}/wp-admin/admin-ajax.php?action=firebase_auth_popup&provider=${provider}&session=${authSessionId}`;
                
                // Navigate popup to auth URL
                popup.location.href = authUrl;

            } catch (error) {
                console.error('Social auth error:', error);
                reject(new Error(`Authentication failed: ${error.message}`));
            }
        });
    }

    // Handles email link (passwordless) sign-in on page load
    function handleEmailLinkSignIn() {
        if (!firebase.auth().isSignInWithEmailLink(window.location.href)) {
            return;
        }
        
        var email = window.localStorage.getItem('emailForSignIn');
        if (!email) {
            email = window.prompt('Please provide your email for confirmation');
            if (!email) return;
        }
        
        showLoader();
        
        firebase.auth().signInWithEmailLink(email, window.location.href)
            .then(function(result) {
                window.localStorage.removeItem('emailForSignIn');
                
                processAuthentication(result.user).catch(function(error) {
                    hideLoader();
                    showError('Authentication failed: ' + error.message);
                });
            })
            .catch(function(error) {
                hideLoader();
                showError('Error signing in with email link: ' + error.message);
            });
    }

    // Document ready handler
    $(function() {
        hideLoader(); // Hide loader at start

        if ($('#firebaseui-auth-container').length) {
            // Wait for Firebase to load, then launch
            var initInterval = setInterval(function() {
                if (typeof firebase !== 'undefined' && firebase.auth) {
                    clearInterval(initInterval);
                    try {
                        ensureFirebaseInitialized();
                        handleEmailLinkSignIn();
                        launchFirebaseUI();
                    } catch (error) {
                        hideLoader();
                        showError('Authentication system initialization failed. Please try again later.');
                        console.error('Firebase UI initialization error:', error);
                    }
                }
            }, 100);
            
            // Auto-cancel if Firebase never loads
            setTimeout(function() { 
                clearInterval(initInterval); 
                if (typeof firebase === 'undefined' || !firebase.auth) {
                    showError('Firebase failed to load. Please check your internet connection and try again.');
                }
            }, 5000);
        }
    });

    // Expose public methods for SPA or dynamic content scenarios
    window.FirebaseWooAuth = window.FirebaseWooAuth || {};
    window.FirebaseWooAuth.launchUI = launchFirebaseUI;
    window.FirebaseWooAuth.handleSocialAuth = handleSocialAuth;
    
})(window, document, jQuery);
