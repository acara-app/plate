<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    'login' => [
        'page_title' => 'Log in to Acara Plate | AI Nutrition for Diabetes Management',
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'email' => 'Email address',
        'email_placeholder' => 'email@example.com',
        'password' => 'Password',
        'password_placeholder' => 'Password',
        'remember_me' => 'Remember me',
        'submit' => 'Log in',
        'forgot_password' => 'Forgot password?',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',
        'or' => 'Or',
    ],

    'register' => [
        'page_title' => 'Create Your Acara Plate Account | Start Your Journey',
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'email@example.com',
        'password' => 'Password',
        'password_placeholder' => 'Password',
        'password_confirmation' => 'Confirm password',
        'password_confirmation_placeholder' => 'Confirm password',
        'submit' => 'Create account',
        'already_have_account' => 'Already have an account?',
        'log_in' => 'Log in',
        'or' => 'Or',
        'terms_acceptance' => 'By signing up, I confirm that I am at least 18 years old and accept the',
        'terms_of_service' => 'Terms of Service',
        'and' => 'and',
        'privacy_policy' => 'Privacy Policy',
    ],

    'password_reset' => [
        'request_page_title' => 'Reset Your Password | Acara Plate Help',
        'reset_page_title' => 'Create New Password | Acara Plate',
        'request_title' => 'Forgot password',
        'request_description' => 'Enter your email to receive a password reset link',
        'email' => 'Email address',
        'email_placeholder' => 'email@example.com',
        'submit' => 'Email password reset link',
        'back_to_login' => 'Or, return to',
        'reset_title' => 'Reset password',
        'reset_description' => 'Please enter your new password below',
        'password' => 'Password',
        'password_placeholder' => 'Password',
        'password_confirmation' => 'Confirm password',
        'password_confirmation_placeholder' => 'Confirm password',
        'reset_submit' => 'Reset password',
    ],

    'two_factor' => [
        'title' => 'Two-factor authentication',
        'description' => 'Please confirm access to your account by entering the authentication code provided by your authenticator application.',
        'code' => 'Code',
        'code_placeholder' => 'Authentication code',
        'recovery_code' => 'Recovery code',
        'recovery_code_placeholder' => 'Recovery code',
        'use_recovery_code' => 'Use a recovery code',
        'use_authentication_code' => 'Use an authentication code',
        'submit' => 'Log in',
    ],

    'email_verification' => [
        'page_title' => 'Verify Your Email | Acara Plate Security',
        'title' => 'Verify email',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'resend' => 'Resend verification email',
        'logout' => 'Log out',
        'verification_sent' => 'A new verification link has been sent to the email address you provided during registration.',
    ],

    'password_confirmation' => [
        'title' => 'Confirm your password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'page_title' => 'Confirm password',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'confirm_button' => 'Confirm password',
    ],

    'two_factor_settings' => [
        'title' => 'Two-Factor Authentication',
        'description' => 'Manage your two-factor authentication settings',
        'enabled_badge' => 'Enabled',
        'disabled_badge' => 'Disabled',
        'enabled_description' => 'With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'disabled_description' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'enable_button' => 'Enable 2FA',
        'disable_button' => 'Disable 2FA',
        'continue_setup' => 'Continue Setup',
    ],

    'two_factor_challenge' => [
        'title' => 'Two-Factor Authentication',
        'code_title' => 'Authentication Code',
        'code_description' => 'Enter the authentication code provided by your authenticator application.',
        'recovery_title' => 'Recovery Code',
        'recovery_description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'recovery_placeholder' => 'Enter recovery code',
        'continue_button' => 'Continue',
        'or_you_can' => 'or you can',
        'use_recovery_code' => 'login using a recovery code',
        'use_auth_code' => 'login using an authentication code',
    ],

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
];
