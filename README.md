# Contact Form with WhatsApp Validation Plugin

A comprehensive WordPress plugin for creating contact forms with real-time WhatsApp number validation, OTP verification, and advanced form management using the Wassenger API.

## Features

### 🚀 Core Features
- **Custom Form Builder**: Create unlimited forms with drag-and-drop field management
- **WhatsApp Validation**: Real-time validation using Wassenger API
- **OTP Verification**: SMS-based phone number verification with 6-digit codes
- **Required Fields**: Name, Email, and WhatsApp number are mandatory
- **Field Types**: Text, Email, Phone, Textarea, Dropdown, WhatsApp
- **Form Customization**: Customize colors, styles, and appearance

### 📊 Management & Analytics
- **Submissions Dashboard**: View, manage, and export form submissions
- **CSV Export**: Export submissions to CSV files
- **Phone Number Uniqueness**: Prevent duplicate submissions per form
- **Background Processing**: Automated health checks and maintenance
- **Real-time Logs**: Monitor API health and background processes

### 🔐 Security & Verification
- **OTP Verification Flow**: Complete phone number verification process
- **Session Management**: Secure OTP sessions with expiration
- **Duplicate Prevention**: Unique phone numbers per form
- **Security**: Built-in nonce verification and data sanitization
- **XSS Protection**: Proper output escaping and input sanitization

### 🎯 User Experience
- **Redirect Support**: Custom redirect URLs after successful submission
- **Dashboard Integration**: Redirect to dashboard after OTP verification
- **Responsive Design**: Mobile-friendly forms
- **Error Handling**: Comprehensive error messages and fallbacks
- **Manual Fallbacks**: Manual redirect options if automatic redirect fails

### ⚙️ Advanced Configuration
- **Multiple Wassenger Accounts**: Load balancing and failover support
- **Account Management**: Add, edit, and manage multiple API accounts
- **Legacy Migration**: Migrate from old settings to new database system
- **Global Settings**: Default dashboard URLs and configuration options

## Installation

1. Upload the plugin files to `/wp-content/plugins/ContactFormWithWhatsAppValidation/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Wassenger API accounts in the settings page
4. Create your first form using the Form Builder
5. Add the form to your pages using the provided shortcode

## Configuration

### Wassenger API Setup

1. **Sign up for Wassenger**: Create an account at [https://wassenger.com](https://wassenger.com)
2. **Get API Credentials**: Obtain your API token and Number ID from the Wassenger dashboard
3. **Add Accounts**: Go to WordPress Admin > Contact Form WhatsApp > Settings
4. **Multiple Accounts**: Add multiple Wassenger accounts for load balancing and failover
5. **Test Connection**: Use the "Test API Connection" button to verify setup

### Creating Forms

1. **Form Builder**: Go to WordPress Admin > Contact Form WhatsApp > Form Builder
2. **Create Form**: Create a new form or edit an existing one
3. **Required Fields**: Name, Email, and WhatsApp number are mandatory
4. **Customization**: Customize form appearance, colors, and styles
5. **Redirect URLs**: Set custom redirect URLs for after verification
6. **Save & Deploy**: Save the form and copy the shortcode

### OTP Verification Flow

1. **Form Submission**: User submits form with WhatsApp number
2. **WhatsApp Validation**: Real-time validation of phone number
3. **OTP Generation**: 6-digit verification code generated
4. **SMS Delivery**: Code sent via WhatsApp to user's phone
5. **Verification Page**: User redirected to verification page
6. **Code Entry**: User enters the 6-digit code
7. **Verification**: System validates the code
8. **Success Redirect**: User redirected to dashboard or success page

### Using Forms

Add the form to any page or post using the shortcode:
```
[cfwv_form id="1"]
```

**OTP Verification Page**: The system automatically creates verification pages with the shortcode:
```
[cfwv_otp_verification session_token="abc123" redirect_url="https://yoursite.com/dashboard"]
```

## Troubleshooting

### Plugin Activation Error

If you get a fatal error during activation, follow these steps:

1. **Run the Debug Script**:
   - Access `yoursite.com/wp-content/plugins/ContactFormWithWhatsAppValidation/debug.php`
   - Check for any red error messages
   - This will help identify the specific issue

2. **Common Issues**:
   - **Missing files**: Ensure all plugin files are uploaded correctly
   - **PHP version**: Requires PHP 7.0 or higher
   - **Database permissions**: Ensure WordPress can create database tables
   - **Memory limit**: Increase PHP memory limit if needed

3. **Check WordPress Error Logs**:
   - Look for detailed error messages in `/wp-content/debug.log`
   - Enable WordPress debugging by adding to `wp-config.php`:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     ```

4. **File Permissions**:
   - Ensure proper file permissions (644 for files, 755 for directories)
   - Check that the web server can read the plugin files

### Common Solutions

1. **Deactivate and Reactivate**:
   - Sometimes a simple reactivation resolves issues
   - Clear any caching plugins before reactivating

2. **Check Plugin Conflicts**:
   - Deactivate other plugins temporarily
   - Test if the issue persists with a default theme

3. **Database Issues**:
   - Check if WordPress can create new database tables
   - Verify database user has CREATE and ALTER privileges

4. **PHP Errors**:
   - Check for syntax errors in the error log
   - Ensure your hosting supports required PHP functions

### Support

If you continue to experience issues:

1. Check the WordPress error logs for detailed error messages
2. Run the debug script and note any red error messages
3. Verify your hosting meets the requirements:
   - PHP 7.0+
   - WordPress 5.0+
   - MySQL 5.6+

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.0 or higher
- **MySQL**: 5.6 or higher
- **Wassenger API Token**: Required for WhatsApp validation

## File Structure

```
ContactFormWithWhatsAppValidation/
├── contact-form-whatsapp-validation.php (Main plugin file)
├── includes/
│   ├── class-database.php
│   ├── class-form-builder.php
│   ├── class-whatsapp-validator.php
│   ├── class-admin.php
│   └── class-frontend.php
├── assets/
│   ├── admin.js
│   ├── frontend.js
│   ├── admin.css
│   └── frontend.css
├── debug.php (Troubleshooting script)
└── README.md
```

## Shortcode Parameters

```
[cfwv_form id="1" style="default" show_title="true"]
```

Parameters:
- `id`: Form ID (required)
- `style`: Form style (optional, default: "default") 
- `show_title`: Show form title and description (optional, default: "true")

Examples:
- `[cfwv_form id="1"]` - Display form with title
- `[cfwv_form id="1" show_title="false"]` - Display form without title
- `[cfwv_form id="2" style="minimal" show_title="true"]` - Custom style with title

## Database Tables

The plugin creates these database tables:
- `wp_cfwv_forms` - Form configurations
- `wp_cfwv_form_fields` - Form field definitions
- `wp_cfwv_submissions` - Form submissions (with unique phone numbers per form)
- `wp_cfwv_submission_data` - Submission field data
- `wp_cfwv_otp_sessions` - OTP verification sessions
- `wp_cfwv_wassenger_accounts` - Wassenger API account management

## API Integration

This plugin integrates with the Wassenger API for WhatsApp services:

### WhatsApp Number Validation
- **Endpoint**: `https://api.wassenger.com/v1/numbers/exists`
- **Method**: POST
- **Authentication**: Token-based

### WhatsApp Message Sending
- **Endpoint**: `https://api.wassenger.com/v1/messages`
- **Method**: POST
- **Authentication**: Token-based
- **Purpose**: Send OTP verification codes

### Session Synchronization
- **Endpoint**: `https://api.wassenger.com/v1/devices/{numberId}/sync`
- **Method**: GET
- **Authentication**: Token-based
- **Purpose**: Maintain WhatsApp session health

## Security

- **Input Sanitization**: All user inputs are sanitized and validated
- **CSRF Protection**: WordPress nonces for all AJAX requests
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Proper output escaping and input sanitization
- **Session Security**: Secure OTP sessions with expiration and attempt limits
- **Phone Number Uniqueness**: Database-level constraints prevent duplicate submissions

## Advanced Features

### Multiple Wassenger Accounts
- **Load Balancing**: Distribute API calls across multiple accounts
- **Failover Support**: Automatic switching if one account fails
- **Usage Tracking**: Monitor daily usage per account
- **Account Management**: Add, edit, and delete accounts via admin interface

### OTP Verification System
- **6-Digit Codes**: Secure random OTP generation
- **Session Management**: 10-minute expiration with secure tokens
- **Resend Functionality**: 60-second cooldown between resend attempts
- **Attempt Limiting**: Prevent brute force attacks
- **Automatic Cleanup**: Expired sessions are automatically removed

### Phone Number Uniqueness
- **Per-Form Uniqueness**: Each form maintains its own unique phone number list
- **Database Constraints**: Unique key constraint at database level
- **Smart Handling**: Updates existing unverified submissions
- **Error Messages**: Clear feedback for duplicate attempts

## License

This plugin is released under the GPL v2 or later license.

## Changelog

### Version 2.0.0 (Current)
- **OTP Verification System**: Complete phone number verification flow
- **Multiple Wassenger Accounts**: Load balancing and failover support
- **Phone Number Uniqueness**: Prevent duplicate submissions per form
- **Dashboard Integration**: Redirect to dashboard after verification
- **Background Processing**: Automated health checks and maintenance
- **Legacy Migration**: Migrate from old settings to new database system
- **Enhanced Security**: Session management and attempt limiting
- **Real-time Logs**: Monitor API health and background processes

### Version 1.0.0
- Initial release
- Form builder functionality
- WhatsApp validation
- Submissions dashboard
- CSV export
- Email notifications

## Current Project Status

### ✅ Recently Implemented Features

1. **Complete OTP Verification Flow**
   - 6-digit code generation and validation
   - WhatsApp message delivery via Wassenger API
   - Secure session management with 10-minute expiration
   - Automatic redirect to verification page after form submission

2. **Multiple Wassenger Account Management**
   - Database-driven account storage
   - Load balancing across multiple accounts
   - Automatic failover if one account reaches limits
   - Admin interface for account management

3. **Phone Number Uniqueness System**
   - Database-level unique constraints per form
   - Smart handling of duplicate submissions
   - Clear error messages for users
   - Updates existing unverified submissions

4. **Dashboard Integration**
   - Global default dashboard URL setting
   - Per-form redirect URL override
   - Automatic redirect after OTP verification
   - Manual fallback options for failed redirects

5. **Enhanced Security & Error Handling**
   - Comprehensive input sanitization
   - Session-based OTP verification
   - Attempt limiting and brute force protection
   - Detailed error logging and debugging

### 🔧 Technical Improvements

- **Database Migration**: Automatic migration from legacy settings
- **Background Processing**: Health checks and maintenance tasks
- **Real-time Logs**: Monitor API health and system status
- **Enhanced Debugging**: Comprehensive logging for troubleshooting
- **Responsive Design**: Mobile-friendly verification pages

### 🚀 Ready for Production

The plugin is now feature-complete and ready for production use with:
- Full OTP verification workflow
- Multiple Wassenger account support
- Phone number uniqueness enforcement
- Dashboard integration
- Comprehensive security measures
- Detailed documentation and troubleshooting guides 