# Contact Form with WhatsApp Validation Plugin

A comprehensive WordPress plugin for creating contact forms with real-time WhatsApp number validation using the Wassenger API.

## Features

- **Custom Form Builder**: Create unlimited forms with drag-and-drop field management
- **WhatsApp Validation**: Real-time validation using Wassenger API
- **Required Fields**: Name, Email, and WhatsApp number are mandatory
- **Field Types**: Text, Email, Phone, Textarea, Dropdown, WhatsApp
- **Form Customization**: Customize colors, styles, and appearance
- **Submissions Dashboard**: View, manage, and export form submissions
- **CSV Export**: Export submissions to CSV files
- **Redirect Support**: Custom redirect URLs after successful submission
- **Email Notifications**: Automatic email notifications for admin and users
- **Responsive Design**: Mobile-friendly forms
- **Security**: Built-in nonce verification and data sanitization

## Installation

1. Upload the plugin files to `/wp-content/plugins/ContactFormWithWhatsAppValidation/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Wassenger API token in the settings page
4. Create your first form using the Form Builder
5. Add the form to your pages using the provided shortcode

## Configuration

### Wassenger API Setup

1. Sign up for a Wassenger account at [https://wassenger.com](https://wassenger.com)
2. Get your API token from the Wassenger dashboard
3. Go to WordPress Admin > Contact Form WhatsApp > Settings
4. Enter your API token and test the connection

### Creating Forms

1. Go to WordPress Admin > Contact Form WhatsApp > Form Builder
2. Create a new form or edit an existing one
3. Add/remove fields as needed (Name, Email, WhatsApp are required)
4. Customize the form appearance and colors
5. Set a redirect URL (optional)
6. Save the form and copy the shortcode

### Using Forms

Add the form to any page or post using the shortcode:
```
[cfwv_form id="1"]
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
- `wp_cfwv_submissions` - Form submissions
- `wp_cfwv_submission_data` - Submission field data

## API Integration

This plugin integrates with the Wassenger API for WhatsApp number validation:
- **Endpoint**: `https://api.wassenger.com/v1/numbers/exists`
- **Method**: POST
- **Authentication**: Token-based

## Security

- All user inputs are sanitized and validated
- CSRF protection using WordPress nonces
- SQL injection prevention using prepared statements
- XSS protection with proper output escaping

## License

This plugin is released under the GPL v2 or later license.

## Changelog

### Version 1.0.0
- Initial release
- Form builder functionality
- WhatsApp validation
- Submissions dashboard
- CSV export
- Email notifications 