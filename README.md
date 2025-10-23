# Contact Form with WhatsApp Validation Plugin

A comprehensive WordPress plugin for creating contact forms with real-time WhatsApp number validation, OTP verification, file uploads, and advanced form management using the Wassenger API.

## Features

### 🚀 Core Features
- **Custom Form Builder**: Create unlimited forms with drag-and-drop field management
- **WhatsApp Validation**: Real-time validation using Wassenger API
- **OTP Verification**: SMS-based phone number verification with 6-digit codes
- **Required Fields**: Name, Email, and WhatsApp number are mandatory
- **Field Types**: Text, Email, Phone, Textarea, Dropdown, WhatsApp, File Upload
- **Form Customization**: Customize colors, styles, and appearance
- **Country Code Selection**: Admin-configurable default country codes for WhatsApp fields
- **File Upload Support**: PDF and DOC file uploads with validation

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
- **File Upload Security**: File type validation and secure storage

### 🎯 User Experience
- **Modern WhatsApp Input**: Merged country code and phone number input with modern styling
- **Responsive Design**: Mobile-friendly forms with adaptive layouts
- **File Upload UI**: Custom file upload interface with drag-and-drop styling
- **Redirect Support**: Custom redirect URLs after successful submission
- **Dashboard Integration**: Redirect to dashboard after OTP verification
- **Error Handling**: Comprehensive error messages and fallbacks
- **Manual Fallbacks**: Manual redirect options if automatic redirect fails

### ⚙️ Advanced Configuration
- **Multiple Wassenger Accounts**: Load balancing and failover support
- **Account Management**: Add, edit, and manage multiple API accounts
- **Legacy Migration**: Migrate from old settings to new database system
- **Global Settings**: Default dashboard URLs and configuration options
- **Database Updates**: Automatic database schema updates with manual trigger

## Installation

1. Upload the plugin files to `/wp-content/plugins/WordPressPluginWhasAppValidation/`
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
4. **WhatsApp Fields**: Configure default country code for WhatsApp fields
5. **File Upload Fields**: Add file upload fields for PDF and DOC files
6. **Customization**: Customize form appearance, colors, and styles
7. **Redirect URLs**: Set custom redirect URLs for after verification
8. **Save & Deploy**: Save the form and copy the shortcode

### Field Types

#### WhatsApp Fields
- **Country Code Selection**: Choose default country code in admin panel
- **Modern UI**: Merged country code and phone number input
- **Real-time Validation**: Optional WhatsApp number validation
- **Responsive Design**: Adapts to mobile and desktop layouts

#### File Upload Fields
- **Supported Formats**: PDF, DOC, DOCX files only
- **File Size Limit**: 5MB maximum file size
- **Custom UI**: Modern file upload interface with drag-and-drop styling
- **Security**: File type validation and secure storage
- **Error Handling**: Clear error messages for invalid files

#### Other Field Types
- **Text Fields**: Single-line text input
- **Email Fields**: Email validation
- **Phone Fields**: Phone number formatting
- **Textarea**: Multi-line text input
- **Dropdown**: Select from predefined options

### OTP Verification Flow

1. **Form Submission**: User submits form with WhatsApp number
2. **WhatsApp Validation**: Real-time validation of phone number (optional)
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
[cfwv_otp_verify session_token="abc123" redirect_url="https://yoursite.com/dashboard"]
```

## Styling & Customization

### WhatsApp Field Styling
The WhatsApp field features a modern, merged design:
- **Country Code Display**: Shows selected country code in a styled container
- **Phone Input**: Seamlessly integrated phone number input
- **Responsive Design**: Adapts to different screen sizes
- **Focus States**: Blue focus indicators matching WordPress standards
- **Error States**: Red styling for validation errors

### File Upload Styling
Custom file upload interface with:
- **Modern Button**: Styled "Choose File" button
- **File Preview**: Shows selected file name
- **Drag & Drop**: Visual feedback for file selection
- **Error Handling**: Clear error messages and styling

### Form Styling
- **Consistent Design**: All fields follow WordPress design standards
- **Responsive Layout**: Mobile-first responsive design
- **Custom CSS**: Easy customization through CSS classes
- **Theme Integration**: Works with most WordPress themes

## Troubleshooting

### Plugin Activation Error

If you get a fatal error during activation, follow these steps:

1. **Run the Debug Script**:
   - Access `yoursite.com/wp-content/plugins/WordPressPluginWhasAppValidation/debug.php`
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

### Database Issues

1. **Update Database Schema**:
   - Go to WordPress Admin > Contact Form WhatsApp > Settings
   - Click "Update Database" button to apply schema changes
   - This ensures all new columns and tables are created

2. **WhatsApp Country Code Issues**:
   - Ensure the `whatsapp_country_code` column exists in the database
   - Use the "Update Database" button in admin settings
   - Check that the column is properly added to existing forms

### Form Submission Issues

1. **Security Check Failed**:
   - Ensure nonce fields are properly included in forms
   - Check that AJAX handlers are registered correctly
   - Verify WordPress nonce verification is working

2. **File Upload Issues**:
   - Check file size limits (5MB maximum)
   - Verify file types (PDF, DOC, DOCX only)
   - Ensure upload directory permissions are correct
   - Check PHP upload limits in server configuration

3. **WhatsApp Validation Issues**:
   - Verify Wassenger API credentials are correct
   - Check API connection in admin settings
   - Ensure phone numbers are in correct format
   - Test with different country codes

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
   - Use the "Update Database" button in admin settings

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
- **File Upload Support**: Required for file upload functionality

## File Structure

```
WordPressPluginWhasAppValidation/
├── contact-form-whatsapp-validation.php (Main plugin file)
├── includes/
│   ├── class-database.php
│   ├── class-form-builder.php
│   ├── class-whatsapp-validator.php
│   ├── class-admin.php
│   ├── class-frontend.php
│   ├── class-otp-handler.php
│   └── class-background-processor.php
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
- `wp_cfwv_form_fields` - Form field definitions (includes whatsapp_country_code column)
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
- **File Upload Security**: File type validation, size limits, and secure storage

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

### File Upload System
- **File Type Validation**: Only PDF, DOC, DOCX files allowed
- **Size Limits**: 5MB maximum file size
- **Secure Storage**: Files stored in WordPress uploads directory
- **Unique Naming**: Timestamp-based unique filenames
- **Error Handling**: Clear error messages for invalid files

## License

This plugin is released under the GPL v2 or later license.

## Changelog

### Version 2.1.0 (Current)
- **WhatsApp Field Enhancement**: Modern merged country code and phone input design
- **Country Code Selection**: Admin-configurable default country codes
- **File Upload Support**: PDF and DOC file upload functionality
- **Modern UI**: Updated styling for all form elements
- **Responsive Design**: Improved mobile and tablet layouts
- **Database Updates**: Automatic schema updates with manual trigger
- **Enhanced Security**: Improved file upload validation and security
- **Form Submission**: Streamlined form submission with better error handling

### Version 2.0.0
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

1. **Modern WhatsApp Field Design**
   - Merged country code and phone number input
   - Admin-configurable country code selection
   - Responsive design for all screen sizes
   - Consistent styling with WordPress standards

2. **File Upload Functionality**
   - PDF, DOC, DOCX file support
   - 5MB file size limit
   - Custom upload UI with modern styling
   - Secure file storage and validation

3. **Enhanced Form Builder**
   - Country code selection for WhatsApp fields
   - File upload field type
   - Improved admin interface
   - Better field editing experience

4. **Improved Form Submission**
   - Streamlined AJAX submission process
   - Better error handling and user feedback
   - File upload integration
   - Enhanced security measures

5. **Database Schema Updates**
   - Automatic database updates
   - Manual update trigger in admin
   - WhatsApp country code column
   - File upload field support

### 🔧 Technical Improvements

- **Modern CSS**: Updated styling for all form elements
- **Responsive Design**: Mobile-first approach with adaptive layouts
- **File Upload Security**: Comprehensive file validation and secure storage
- **Database Management**: Automatic schema updates with manual override
- **Enhanced Debugging**: Improved error logging and troubleshooting

### 🚀 Ready for Production

The plugin is now feature-complete and ready for production use with:
- Modern WhatsApp field design with country code selection
- File upload functionality with security validation
- Enhanced form builder with improved admin interface
- Streamlined form submission process
- Comprehensive security measures
- Detailed documentation and troubleshooting guides