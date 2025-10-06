<?php
/**
 * Plugin Name: Contact Form with WhatsApp Validation
 * Description: A WordPress plugin for contact forms with WhatsApp number validation using Wassenger API
 * Version: 1.0.0
 * Author: Han Sheng
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: contact-form-whatsapp
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFWV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFWV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CFWV_VERSION', '1.0.0');

// Main plugin class
class ContactFormWhatsAppValidation {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('contact-form-whatsapp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Check for database updates
        $this->check_database_updates();
        
        // Add actions and filters
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_ajax_cfwv_validate_whatsapp', array($this, 'ajax_validate_whatsapp'));
        add_action('wp_ajax_nopriv_cfwv_validate_whatsapp', array($this, 'ajax_validate_whatsapp'));
        add_action('wp_ajax_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_cfwv_initialize_tables', array($this, 'ajax_initialize_tables'));
        add_action('wp_ajax_cfwv_clear_logs', array($this->admin, 'ajax_clear_logs'));
        add_action('wp_ajax_cfwv_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_nopriv_cfwv_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_cfwv_resend_otp', array($this, 'ajax_resend_otp'));
        add_action('wp_ajax_nopriv_cfwv_resend_otp', array($this, 'ajax_resend_otp'));
        add_action('template_redirect', array($this, 'handle_otp_verification_page'));
        add_shortcode('cfwv_form', array($this, 'shortcode_form'));
        add_shortcode('cfwv_otp_verification', array($this, 'shortcode_otp_verification'));
        
    }
    
    private function includes() {
        require_once CFWV_PLUGIN_PATH . 'includes/class-database.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-form-builder.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-whatsapp-validator.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-admin.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-background-processor.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-otp-handler.php';
    }
    
    private function init_components() {
        try {
            $this->database = new CFWV_Database();
            $this->form_builder = new CFWV_FormBuilder();
            $this->whatsapp_validator = new CFWV_WhatsAppValidator();
            $this->admin = new CFWV_Admin();
            $this->frontend = new CFWV_Frontend();
            $this->background_processor = new CFWV_BackgroundProcessor();
            $this->otp_handler = new CFWV_OTPHandler();
        } catch (Exception $e) {
            // Log error and show admin notice
            error_log('CFWV Plugin Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p><strong>Contact Form WhatsApp Plugin Error:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
    
    public function activate() {
        try {
            // Include required files for activation
            require_once CFWV_PLUGIN_PATH . 'includes/class-database.php';
            require_once CFWV_PLUGIN_PATH . 'includes/class-form-builder.php';
            require_once CFWV_PLUGIN_PATH . 'includes/class-whatsapp-validator.php';
            require_once CFWV_PLUGIN_PATH . 'includes/class-background-processor.php';
            
            // Initialize database and form builder for activation
            $database = new CFWV_Database();
            $form_builder = new CFWV_FormBuilder();
            
            // Create database tables
            $database->create_tables();
            
            // Add unique constraint for phone numbers
            $database->add_phone_uniqueness_constraint();
            
            // Create default form fields
            $form_builder->create_default_fields();
            
            // Schedule background processor (uses static method to avoid dependency issues)
            CFWV_BackgroundProcessor::activate_background_processor();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Set activation flag
            update_option('cfwv_activated', true);
            
        } catch (Exception $e) {
            // Log the error
            error_log('CFWV Activation Error: ' . $e->getMessage());
            
            // Deactivate the plugin
            deactivate_plugins(plugin_basename(__FILE__));
            
            // Show error message
            wp_die(
                'Plugin activation failed: ' . $e->getMessage(),
                'Plugin Activation Error',
                array('back_link' => true)
            );
        }
    }
    
    public function deactivate() {
        // Include background processor for deactivation
        require_once CFWV_PLUGIN_PATH . 'includes/class-background-processor.php';
        
        // Unschedule background processor (uses static method to avoid dependency issues)
        CFWV_BackgroundProcessor::deactivate_background_processor();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function check_database_updates() {
        // Add unique constraint for phone numbers if not exists
        $this->database->add_phone_uniqueness_constraint();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Contact Form WhatsApp', 'contact-form-whatsapp'),
            __('Contact Form WhatsApp', 'contact-form-whatsapp'),
            'manage_options',
            'cfwv-dashboard',
            array($this->admin, 'dashboard_page'),
            'dashicons-phone',
            30
        );
        
        add_submenu_page(
            'cfwv-dashboard',
            __('Form Builder', 'contact-form-whatsapp'),
            __('Form Builder', 'contact-form-whatsapp'),
            'manage_options',
            'cfwv-form-builder',
            array($this->admin, 'form_builder_page')
        );
        
        add_submenu_page(
            'cfwv-dashboard',
            __('Submissions', 'contact-form-whatsapp'),
            __('Submissions', 'contact-form-whatsapp'),
            'manage_options',
            'cfwv-submissions',
            array($this->admin, 'submissions_page')
        );
        
        add_submenu_page(
            'cfwv-dashboard',
            __('Settings', 'contact-form-whatsapp'),
            __('Settings', 'contact-form-whatsapp'),
            'manage_options',
            'cfwv-settings',
            array($this->admin, 'settings_page')
        );
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'cfwv-') !== false) {
            wp_enqueue_script('cfwv-admin-js', CFWV_PLUGIN_URL . 'assets/admin.js', array('jquery'), CFWV_VERSION, true);
            wp_enqueue_style('cfwv-admin-css', CFWV_PLUGIN_URL . 'assets/admin.css', array(), CFWV_VERSION);
            wp_localize_script('cfwv-admin-js', 'cfwv_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cfwv_nonce')
            ));
        }
    }
    
    public function frontend_enqueue_scripts() {
        wp_enqueue_script('cfwv-frontend-js', CFWV_PLUGIN_URL . 'assets/frontend.js', array('jquery'), CFWV_VERSION, true);
        wp_enqueue_style('cfwv-frontend-css', CFWV_PLUGIN_URL . 'assets/frontend.css', array(), CFWV_VERSION);
        wp_localize_script('cfwv-frontend-js', 'cfwv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfwv_nonce')
        ));
    }
    
    public function ajax_validate_whatsapp() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone']);
        $result = $this->whatsapp_validator->validate_number($phone);
        
        wp_send_json($result);
    }
    
    public function ajax_submit_form() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $form_data = $_POST['form_data'];
        $result = $this->frontend->submit_form($form_data);
        
        wp_send_json($result);
    }
    
    public function shortcode_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 1,
            'style' => 'default',
            'show_title' => 'true'
        ), $atts);
        
        $show_title = ($atts['show_title'] === 'true' || $atts['show_title'] === '1');
        
        return $this->frontend->render_form($atts['id'], $atts['style'], $show_title);
    }
    
    public function ajax_initialize_tables() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        try {
            // Include required files
            require_once CFWV_PLUGIN_PATH . 'includes/class-database.php';
            require_once CFWV_PLUGIN_PATH . 'includes/class-form-builder.php';
            
            // Initialize components
            $database = new CFWV_Database();
            $form_builder = new CFWV_FormBuilder();
            
            // Log start of process
            error_log('CFWV: Starting table initialization...');
            
            // Drop and recreate database tables (EMPTY)
            $database->reset_tables();
            error_log('CFWV: Database tables reset to empty state');
            
            // Check if tables exist
            global $wpdb;
            $tables_created = array();
            $table_names = array(
                'forms' => $wpdb->prefix . 'cfwv_forms',
                'form_fields' => $wpdb->prefix . 'cfwv_form_fields', 
                'submissions' => $wpdb->prefix . 'cfwv_submissions',
                'submission_data' => $wpdb->prefix . 'cfwv_submission_data'
            );
            
            foreach ($table_names as $key => $table_name) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                if ($table_exists) {
                    $tables_created[] = $key;
                }
            }
            
            // Force create default form (even if forms exist)
            $form_id = $this->force_create_default_form($database, $form_builder);
            
            $message = sprintf(
                __('Tables initialized successfully! Reset tables to EMPTY state: %s. New default form created with ID: %s.', 'contact-form-whatsapp'),
                implode(', ', $tables_created),
                $form_id
            );
            
            error_log('CFWV: ' . $message);
            wp_send_json_success($message);
            
        } catch (Exception $e) {
            error_log('CFWV Initialize Tables Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to initialize tables: ', 'contact-form-whatsapp') . $e->getMessage());
        }
    }
    
    private function force_create_default_form($database, $form_builder) {
        // Create a new default form regardless of existing forms
        $form_data = array(
            'name' => 'Default Contact Form - ' . date('Y-m-d H:i:s'),
            'description' => 'Default contact form with WhatsApp validation (Auto-created)',
            'redirect_url' => '',
            'form_styles' => json_encode(array(
                'background_color' => '#ffffff',
                'text_color' => '#333333',
                'border_color' => '#cccccc',
                'button_color' => '#007cba',
                'button_text_color' => '#ffffff'
            )),
            'status' => 'active'
        );
        
        $form_id = $database->save_form($form_data);
        
        if ($form_id) {
            // Create default fields
            $default_fields = array(
                array(
                    'form_id' => $form_id,
                    'field_name' => 'name',
                    'field_label' => 'Full Name',
                    'field_type' => 'text',
                    'field_options' => '',
                    'is_required' => 1,
                    'field_order' => 1,
                    'field_placeholder' => 'Enter your full name',
                    'field_class' => 'cfwv-field-name'
                ),
                array(
                    'form_id' => $form_id,
                    'field_name' => 'email',
                    'field_label' => 'Email Address',
                    'field_type' => 'email',
                    'field_options' => '',
                    'is_required' => 1,
                    'field_order' => 2,
                    'field_placeholder' => 'Enter your email address',
                    'field_class' => 'cfwv-field-email'
                ),
                array(
                    'form_id' => $form_id,
                    'field_name' => 'whatsapp',
                    'field_label' => 'WhatsApp Number',
                    'field_type' => 'whatsapp',
                    'field_options' => '',
                    'is_required' => 1,
                    'field_order' => 3,
                    'field_placeholder' => '+1234567890',
                    'field_class' => 'cfwv-field-whatsapp'
                ),
                array(
                    'form_id' => $form_id,
                    'field_name' => 'message',
                    'field_label' => 'Message',
                    'field_type' => 'textarea',
                    'field_options' => '',
                    'is_required' => 0,
                    'field_order' => 4,
                    'field_placeholder' => 'Enter your message (optional)',
                    'field_class' => 'cfwv-field-message'
                )
            );
            
            foreach ($default_fields as $field) {
                $database->save_form_field($field);
            }
            
            error_log('CFWV: Default form created with ID: ' . $form_id);
        }
        
        return $form_id;
    }
    
    /**
     * AJAX handler for OTP verification
     */
    public function ajax_verify_otp() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $session_token = sanitize_text_field($_POST['session_token']);
        $otp_code = sanitize_text_field($_POST['otp_code']);
        
        $result = $this->otp_handler->verify_otp($session_token, $otp_code);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for resending OTP
     */
    public function ajax_resend_otp() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $session_token = sanitize_text_field($_POST['session_token']);
        
        $result = $this->otp_handler->resend_otp($session_token);
        
        wp_send_json($result);
    }
    
    /**
     * OTP verification shortcode
     */
    public function shortcode_otp_verification($atts) {
        $atts = shortcode_atts(array(
            'session_token' => '',
            'redirect_url' => ''
        ), $atts);
        
        if (empty($atts['session_token'])) {
            return '<p>Invalid verification link.</p>';
        }
        
        // Get session info
        $session = $this->otp_handler->get_otp_session($atts['session_token']);
        
        if (!$session) {
            return '<p>Verification session expired or invalid.</p>';
        }
        
        // Generate OTP verification form HTML
        ob_start();
        ?>
        <div class="cfwv-otp-verification-container">
            <div class="cfwv-otp-header">
                <h2>Verify Your Phone Number</h2>
                <p>We've sent a verification code to <strong><?php echo esc_html($session->phone_number); ?></strong></p>
            </div>
            
            <form id="cfwv-otp-form" class="cfwv-otp-form">
                <input type="hidden" name="session_token" value="<?php echo esc_attr($atts['session_token']); ?>">
                <input type="hidden" name="redirect_url" value="<?php echo esc_attr($atts['redirect_url']); ?>">
                
                <div class="cfwv-field-wrapper">
                    <label for="otp_code">Enter Verification Code</label>
                    <input type="text" id="otp_code" name="otp_code" class="cfwv-field" 
                           placeholder="Enter 6-digit code" maxlength="6" required>
                    <div class="cfwv-field-error"></div>
                </div>
                
                <div class="cfwv-submit-wrapper">
                    <button type="submit" class="cfwv-submit-btn">Verify Code</button>
                </div>
                
                <div class="cfwv-otp-actions">
                    <button type="button" id="cfwv-resend-otp" class="cfwv-resend-btn">Resend Code</button>
                    <span class="cfwv-timer" id="cfwv-timer">Resend available in <span id="countdown">60</span>s</span>
                </div>
                
                <div class="cfwv-loading" style="display: none;">
                    <span class="cfwv-spinner"></span> Verifying...
                </div>
                
                <div class="cfwv-messages"></div>
            </form>
        </div>
        
        <style>
        .cfwv-otp-verification-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cfwv-otp-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .cfwv-otp-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .cfwv-otp-form .cfwv-field {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
        }
        
        .cfwv-otp-form .cfwv-field:focus {
            border-color: #007cba;
            outline: none;
        }
        
        .cfwv-otp-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .cfwv-resend-btn {
            background: none;
            border: none;
            color: #007cba;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .cfwv-resend-btn:disabled {
            color: #999;
            cursor: not-allowed;
        }
        
        .cfwv-timer {
            color: #666;
            font-size: 14px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let countdown = 60;
            let timer = setInterval(function() {
                countdown--;
                $('#countdown').text(countdown);
                if (countdown <= 0) {
                    clearInterval(timer);
                    $('#cfwv-resend-otp').prop('disabled', false);
                    $('.cfwv-timer').hide();
                }
            }, 1000);
            
            $('#cfwv-otp-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submitBtn = form.find('.cfwv-submit-btn');
                var loading = form.find('.cfwv-loading');
                
                submitBtn.prop('disabled', true);
                loading.show();
                
                $.ajax({
                    url: cfwv_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cfwv_verify_otp',
                        session_token: $('input[name="session_token"]').val(),
                        otp_code: $('input[name="otp_code"]').val(),
                        nonce: cfwv_ajax.nonce
                    },
                    success: function(response) {
                        console.log('OTP verification response:', response); // Debug log
                        
                        if (response.success) {
                            var redirectUrl = $('input[name="redirect_url"]').val();
                            console.log('OTP verification successful, redirect URL:', redirectUrl); // Debug log
                            
                            if (redirectUrl) {
                                console.log('Redirecting to:', redirectUrl); // Debug log
                                window.location.href = redirectUrl;
                            } else {
                                $('.cfwv-messages').html('<div class="cfwv-message success">Verification successful!</div>');
                            }
                        } else {
                            console.log('OTP verification failed:', response.message); // Debug log
                            $('.cfwv-messages').html('<div class="cfwv-message error">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('OTP verification AJAX error:', error); // Debug log
                        $('.cfwv-messages').html('<div class="cfwv-message error">Verification failed. Please try again.</div>');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        loading.hide();
                    }
                });
            });
            
            $('#cfwv-resend-otp').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.ajax({
                    url: cfwv_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cfwv_resend_otp',
                        session_token: $('input[name="session_token"]').val(),
                        nonce: cfwv_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.cfwv-messages').html('<div class="cfwv-message success">Code resent successfully!</div>');
                            // Reset timer
                            countdown = 60;
                            timer = setInterval(function() {
                                countdown--;
                                $('#countdown').text(countdown);
                                if (countdown <= 0) {
                                    clearInterval(timer);
                                    btn.prop('disabled', false);
                                    $('.cfwv-timer').hide();
                                }
                            }, 1000);
                            $('.cfwv-timer').show();
                        } else {
                            $('.cfwv-messages').html('<div class="cfwv-message error">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('.cfwv-messages').html('<div class="cfwv-message error">Failed to resend code. Please try again.</div>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle OTP verification page
     */
    public function handle_otp_verification_page() {
        if (isset($_GET['cfwv_otp_verify']) && $_GET['cfwv_otp_verify'] == '1') {
            $session_token = sanitize_text_field($_GET['token']);
            $redirect_url = isset($_GET['redirect_url']) ? sanitize_url($_GET['redirect_url']) : '';
            
            if (empty($session_token)) {
                wp_die('Invalid verification link.');
            }
            
            // Get session info
            $session = $this->otp_handler->get_otp_session($session_token);
            
            if (!$session) {
                wp_die('Verification session expired or invalid.');
            }
            
            // Display OTP verification page
            $this->display_otp_verification_page($session_token, $redirect_url);
            exit;
        }
    }
    
    /**
     * Display OTP verification page
     */
    private function display_otp_verification_page($session_token, $redirect_url = '') {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Verify Your Phone Number</title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div class="cfwv-otp-page">
                <?php echo $this->shortcode_otp_verification(array(
                    'session_token' => $session_token,
                    'redirect_url' => $redirect_url
                )); ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

}

// Initialize the plugin
new ContactFormWhatsAppValidation(); 