<?php
/**
 * Plugin Name: Contact Form with WhatsApp Validation
 * Description: A WordPress plugin for contact forms with WhatsApp number validation using Wassenger API
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: contact-form-whatsapp
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
        
        // Add actions and filters
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_ajax_cfwv_validate_whatsapp', array($this, 'ajax_validate_whatsapp'));
        add_action('wp_ajax_nopriv_cfwv_validate_whatsapp', array($this, 'ajax_validate_whatsapp'));
        add_action('wp_ajax_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_shortcode('cfwv_form', array($this, 'shortcode_form'));
    }
    
    private function includes() {
        require_once CFWV_PLUGIN_PATH . 'includes/class-database.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-form-builder.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-whatsapp-validator.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-admin.php';
        require_once CFWV_PLUGIN_PATH . 'includes/class-frontend.php';
    }
    
    private function init_components() {
        try {
            $this->database = new CFWV_Database();
            $this->form_builder = new CFWV_FormBuilder();
            $this->whatsapp_validator = new CFWV_WhatsAppValidator();
            $this->admin = new CFWV_Admin();
            $this->frontend = new CFWV_Frontend();
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
            
            // Initialize database and form builder for activation
            $database = new CFWV_Database();
            $form_builder = new CFWV_FormBuilder();
            
            // Create database tables
            $database->create_tables();
            
            // Create default form fields
            $form_builder->create_default_fields();
            
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
        // Flush rewrite rules
        flush_rewrite_rules();
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
}

// Initialize the plugin
new ContactFormWhatsAppValidation(); 