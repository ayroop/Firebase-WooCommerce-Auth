jQuery(document).ready(function($) {
    // Ensure preloader is hidden initially
    $('#loader').hide(); // Hide the preloader initially

    // Check if the firebase auth container exists on the page
    if ($('#firebaseui-auth-container').length > 0) {
        // Initialize Firebase if it's not already initialized
        if (!firebase.apps.length) {
            firebase.initializeApp(FirebaseWooAuth.firebaseConfig);
        }

        // Initialize FirebaseUI Widget using Firebase
        var ui = new firebaseui.auth.AuthUI(firebase.auth());

        // FirebaseUI config
        var uiConfig = {
            callbacks: {
                // When the user successfully signs in
                signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                    var user = authResult.user;

                    // Display preloader during redirection
                    $('#firebaseui-auth-container').fadeOut(); // Hide Firebase UI container
                    $('#loader').fadeIn(); // Show preloader

                    // Ensure email is verified before proceeding (for email/password sign-ins)
                    if (authResult.additionalUserInfo.providerId === 'password' && !user.emailVerified) {
                        alert('Please verify your email before signing in.');
                        user.sendEmailVerification().then(function() {
                            alert('Verification email sent. Please check your inbox.');
                        }).catch(function(error) {
                            console.error('Error sending email verification:', error);
                            alert('Error sending email verification: ' + error.message);
                        });
                        // Sign out the user until verification is done
                        firebase.auth().signOut();
                        $('#loader').fadeOut(); // Hide preloader
                        $('#firebaseui-auth-container').fadeIn(); // Show Firebase UI again
                        return false; // Do not proceed with sign-in
                    }

                    // Obtain ID Token and send it to the server for verification and WooCommerce login
                    user.getIdToken().then(function(idToken) {
                        $.post(FirebaseWooAuth.ajax_url, {
                            action: 'firebase_authenticate',
                            id_token: idToken
                        }, function(response) {
                            if (response.success) {
                                // Redirect to the specified URL after authentication
                                window.location.href = response.data.redirect_url;
                            } else {
                                $('#loader').fadeOut(); // Hide preloader on error
                                alert('Authentication failed: ' + response.data);
                            }
                        });
                    });

                    return false; // Prevent FirebaseUI from redirecting
                },

                // If there's a sign-in failure
                signInFailure: function(error) {
                    // Handle sign-in errors
                    console.error('Sign-in error:', error);
                    $('#loader').fadeOut(); // Hide preloader on error
                    alert('Sign-in error: ' + error.message);
                },

                // When Firebase UI is fully shown (after loading)
                uiShown: function() {
                    // Ensure the container is visible and then hide the preloader when Firebase UI is ready
                    $('#firebaseui-auth-container').fadeIn(); // Ensure the container is visible
                    $('#loader').fadeOut(); // Hide the preloader
                }
            },

            // Configuration for sign-in methods
            signInFlow: 'popup', // Popup flow instead of redirect
            signInOptions: getSignInOptions(), // Get sign-in options from admin settings

            // Terms of Service and Privacy Policy URLs
            tosUrl: FirebaseWooAuth.terms_of_service_url,
            privacyPolicyUrl: FirebaseWooAuth.privacy_policy_url
        };

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
                options.push(firebase.auth.EmailAuthProvider.PROVIDER_ID);
            }
            if (FirebaseWooAuth.enable_email_link) {
                options.push({
                    provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                    signInMethod: firebase.auth.EmailAuthProvider.EMAIL_LINK_SIGN_IN_METHOD
                });
            }
            // Add Microsoft sign-in option
            if (FirebaseWooAuth.enable_microsoft) {
                options.push('microsoft.com');
            }
            return options;
        }

        // Start the FirebaseUI authentication widget
        ui.start('#firebaseui-auth-container', uiConfig);

        // Handle email link sign-in for passwordless email authentication
        if (firebase.auth().isSignInWithEmailLink(window.location.href)) {
            var email = window.localStorage.getItem('emailForSignIn'); // Get the email from local storage
            if (!email) {
                // If no email available in local storage, prompt the user to input it
                email = window.prompt('Please provide your email for confirmation');
            }

            // Complete the sign-in process with the email link
            firebase.auth().signInWithEmailLink(email, window.location.href)
                .then(function(result) {
                    // Clear the email from local storage
                    window.localStorage.removeItem('emailForSignIn');

                    // User successfully signed in
                    result.user.getIdToken().then(function(idToken) {
                        // Show preloader while redirecting
                        $('#loader').fadeIn(); 
                        // Send the ID token to the server for WooCommerce login
                        $.post(FirebaseWooAuth.ajax_url, {
                            action: 'firebase_authenticate',
                            id_token: idToken
                        }, function(response) {
                            if (response.success) {
                                // Redirect to the desired page
                                window.location.href = response.data.redirect_url;
                            } else {
                                $('#loader').fadeOut(); // Hide preloader on error
                                alert('Authentication failed: ' + response.data);
                            }
                        });
                    });
                })
                .catch(function(error) {
                    $('#loader').fadeOut(); // Hide preloader on error
                    console.error('Error signing in with email link:', error);
                    alert('Error signing in: ' + error.message);
                });
        }
    } else {
        // Only log in development (localhost or with a specific flag)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log("FirebaseUI auth container not found on this page.");
        }
    }
});
