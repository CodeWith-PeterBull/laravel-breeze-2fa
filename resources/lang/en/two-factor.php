<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during two-factor authentication
    | for various messages that we need to display to the user. You are free
    | to modify these language lines according to your application's requirements.
    |
    */

    // General messages
    'enabled' => 'Two-factor authentication has been enabled successfully.',
    'disabled' => 'Two-factor authentication has been disabled.',
    'confirmed' => 'Two-factor authentication setup completed successfully.',
    'failed' => 'Two-factor authentication verification failed.',
    'required' => 'Two-factor authentication is required for your account.',
    'not_enabled' => 'Two-factor authentication is not enabled for your account.',
    'already_enabled' => 'Two-factor authentication is already enabled.',

    // Setup and configuration
    'setup' => [
        'title' => 'Set Up Two-Factor Authentication',
        'description' => 'Add an extra layer of security to your account.',
        'choose_method' => 'Choose Your Authentication Method',
        'scan_qr_code' => 'Scan this QR code with your authenticator app',
        'manual_entry' => 'Can\'t scan? Enter code manually',
        'email_sent' => 'A verification code has been sent to your email address.',
        'sms_sent' => 'A verification code has been sent to your phone.',
        'confirm_setup' => 'Confirm your setup by entering a verification code',
    ],

    // Challenge and verification
    'challenge' => [
        'title' => 'Two-Factor Authentication',
        'description' => 'Please confirm access to your account by entering the authentication code.',
        'enter_code' => 'Enter your verification code',
        'remember_device' => 'Remember this device for :days days',
        'use_recovery_code' => 'Use a recovery code instead',
        'resend_code' => 'Resend verification code',
        'back_to_login' => 'Back to Login',
    ],

    // Methods
    'methods' => [
        'totp' => [
            'name' => 'Authenticator App',
            'description' => 'Use an authenticator app like Google Authenticator or Authy',
            'instructions' => 'Scan the QR code with your authenticator app and enter the generated code.',
        ],
        'email' => [
            'name' => 'Email',
            'description' => 'Receive verification codes via email',
            'instructions' => 'A verification code will be sent to your email address.',
        ],
        'sms' => [
            'name' => 'SMS',
            'description' => 'Receive verification codes via text message',
            'instructions' => 'A verification code will be sent to your phone number.',
        ],
    ],

    // Recovery codes
    'recovery' => [
        'title' => 'Recovery Codes',
        'description' => 'Store these recovery codes in a secure location. They can be used to access your account if you lose your two-factor authentication device.',
        'download' => 'Download Recovery Codes',
        'print' => 'Print Recovery Codes',
        'generate_new' => 'Generate New Recovery Codes',
        'codes_remaining' => '{0}No recovery codes remaining|{1}:count recovery code remaining|[2,*]:count recovery codes remaining',
        'warning' => 'Each recovery code can only be used once.',
        'generated' => 'New recovery codes have been generated.',
        'used' => 'Recovery code used successfully.',
    ],

    // Device management
    'devices' => [
        'title' => 'Trusted Devices',
        'description' => 'Manage devices that you\'ve chosen to remember for two-factor authentication.',
        'current_device' => 'Current Device',
        'forget_device' => 'Forget Device',
        'forget_all' => 'Forget All Devices',
        'last_used' => 'Last used :time',
        'expires' => 'Expires :time',
        'no_devices' => 'You haven\'t trusted any devices yet.',
        'device_forgotten' => 'Device has been forgotten.',
        'all_devices_forgotten' => 'All devices have been forgotten.',
    ],

    // Validation and errors
    'validation' => [
        'code_required' => 'Please enter your verification code.',
        'code_invalid' => 'The verification code is invalid.',
        'code_expired' => 'The verification code has expired.',
        'code_format' => 'Please enter a valid verification code.',
        'method_required' => 'Please select a two-factor authentication method.',
        'method_invalid' => 'The selected authentication method is not valid.',
        'phone_required' => 'Phone number is required for SMS authentication.',
        'phone_invalid' => 'Please enter a valid phone number.',
        'email_required' => 'Email address is required for email authentication.',
        'email_invalid' => 'Please enter a valid email address.',
        'password_required' => 'Password confirmation is required.',
        'password_incorrect' => 'The provided password is incorrect.',
    ],

    // Rate limiting
    'rate_limit' => [
        'exceeded' => 'Too many verification attempts. Please try again in :minutes minutes.',
        'max_attempts' => 'Maximum attempts reached. Please wait before trying again.',
        'email_limit' => 'Too many email requests. Please wait before requesting another code.',
        'sms_limit' => 'Too many SMS requests. Please wait before requesting another code.',
    ],

    // Email notifications
    'email' => [
        'subject' => 'Your :app verification code',
        'greeting' => 'Hello :name,',
        'line_1' => 'You requested a verification code for two-factor authentication.',
        'line_2' => 'Your verification code is:',
        'line_3' => 'This code will expire in :minutes minutes.',
        'line_4' => 'If you did not request this code, please ignore this email.',
        'action' => 'Verify Now',
        'salutation' => 'Regards,<br>:app Team',
    ],

    // SMS notifications
    'sms' => [
        'message' => 'Your :app verification code is: :code',
        'expires' => 'Expires in :minutes minutes.',
    ],

    // Admin messages
    'admin' => [
        'user_disabled' => 'Two-factor authentication has been disabled for the user.',
        'user_reset' => 'Two-factor authentication has been reset for the user.',
        'bulk_disabled' => 'Two-factor authentication has been disabled for :count users.',
        'statistics_updated' => 'Two-factor authentication statistics have been updated.',
    ],

    // Status messages
    'status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'confirmed' => 'Confirmed',
        'pending' => 'Pending Confirmation',
        'expired' => 'Expired',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // Security notices
    'security' => [
        'notice' => 'Never share your verification codes with anyone.',
        'warning' => 'If you didn\'t request this code, your account may be compromised.',
        'recommendation' => 'We recommend enabling two-factor authentication for enhanced security.',
        'backup_codes' => 'Keep your recovery codes in a safe place. Each code can only be used once.',
    ],

    // Success messages
    'success' => [
        'setup_complete' => 'Two-factor authentication setup is complete.',
        'code_sent' => 'Verification code sent successfully.',
        'verified' => 'Code verified successfully.',
        'device_remembered' => 'This device will be remembered for :days days.',
        'settings_updated' => 'Two-factor authentication settings updated.',
    ],

    // Error messages
    'errors' => [
        'setup_failed' => 'Failed to set up two-factor authentication.',
        'verification_failed' => 'Verification failed. Please try again.',
        'code_send_failed' => 'Failed to send verification code.',
        'method_not_available' => 'This authentication method is not available.',
        'configuration_error' => 'Two-factor authentication is not properly configured.',
        'provider_error' => 'SMS provider error. Please try again later.',
        'session_expired' => 'Your session has expired. Please start over.',
        'already_used' => 'This recovery code has already been used.',
        'no_codes_remaining' => 'No recovery codes remaining. Please generate new ones.',
    ],

    // Buttons and actions
    'actions' => [
        'enable' => 'Enable Two-Factor Authentication',
        'disable' => 'Disable Two-Factor Authentication',
        'confirm' => 'Confirm Setup',
        'verify' => 'Verify Code',
        'resend' => 'Resend Code',
        'cancel' => 'Cancel',
        'continue' => 'Continue',
        'skip' => 'Skip for Now',
        'back' => 'Back',
        'next' => 'Next',
        'save' => 'Save Settings',
        'generate' => 'Generate Codes',
        'download' => 'Download',
        'print' => 'Print',
        'copy' => 'Copy to Clipboard',
        'forget' => 'Forget Device',
        'refresh' => 'Refresh',
    ],

    // Help and support
    'help' => [
        'what_is_2fa' => 'What is two-factor authentication?',
        'how_to_setup' => 'How do I set up two-factor authentication?',
        'lost_device' => 'I lost my authenticator device',
        'recovery_codes' => 'How do I use recovery codes?',
        'troubleshooting' => 'Troubleshooting',
        'contact_support' => 'Contact Support',
        'faq' => 'Frequently Asked Questions',
    ],

];
