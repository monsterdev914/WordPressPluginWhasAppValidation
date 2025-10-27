<?php
/**
 * Background Processor class for Contact Form WhatsApp Validation plugin
 * Handles automatic background tasks via WordPress cron
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_BackgroundProcessor {
    
    private $database;
    private $whatsapp_validator;
    
    public function __construct() {
        // Register cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_intervals'));
        
        // Register cron hooks
        add_action('cfwv_check_api_health', array($this, 'check_api_health'));
        
        // Register admin notice hook to show cron status
        add_action('admin_notices', array($this, 'show_status_notice'));
        
        // Initialize immediately since we're already in the init phase
        $this->init();
    }
    
    public function init() {
        // $this->log_process('🎯 Background processor init() CALLED - Fixed timing issue!');
        
        $this->database = new CFWV_Database();
        $this->whatsapp_validator = new CFWV_WhatsAppValidator();
        
        // $this->log_process('✅ Background processor initialization completed');
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_intervals($schedules) {
        // Get custom intervals definition
        $custom_intervals = self::get_custom_intervals_definition();
        
        // Add custom intervals to the schedules
        foreach ($custom_intervals as $key => $interval) {
            $schedules[$key] = $interval;
        }
        
        return $schedules;
    }
    
    /**
     * Get the definition of custom intervals
     * Centralized place for custom interval definitions
     */
    public static function get_custom_intervals_definition() {
        return array(
            'every30seconds' => array(
                'interval' => 30,
                'display'  => __('Every 30 Seconds', 'contact-form-whatsapp')
            ),
            'every1minutes' => array(
                'interval' => 1 * 60,
                'display'  => __('Every 1 Minutes', 'contact-form-whatsapp')
            ),
            'every5minutes' => array(
                'interval' => 5 * 60,
                'display'  => __('Every 5 Minutes', 'contact-form-whatsapp')
            ),
            'every10minutes' => array(
                'interval' => 10 * 60,
                'display'  => __('Every 10 Minutes', 'contact-form-whatsapp')
            ),
            'every15minutes' => array(
                'interval' => 15 * 60,
                'display'  => __('Every 15 Minutes', 'contact-form-whatsapp')
            ),
            'every30minutes' => array(
                'interval' => 30 * 60,
                'display'  => __('Every 30 Minutes', 'contact-form-whatsapp')
            )
        );
    }
    
    /**
     * Schedule cron job for API health monitoring
     */
    public function schedule_cron_jobs() {
        $this->log_process('🔧 Attempting to schedule cron jobs...');
        
        // Check API health - you can change the interval here:
        // Options: 'every30seconds', 'every1minutes', 'every5minutes', 'every10minutes', 'every15minutes', 'every30minutes', 'hourly', 'twicedaily', 'daily'
        $interval = 'every30seconds'; // ← Change this to your preferred interval
        
        // Check if custom intervals are available
        $schedules = wp_get_schedules();
        if (!isset($schedules[$interval])) {
            $this->log_process('❌ ERROR: Custom interval "' . $interval . '" not registered! Using hourly instead.');
            $interval = 'hourly'; // Use WordPress built-in interval as fallback
        } else {
            $this->log_process('✅ Custom interval "' . $interval . '" is available');
        }
        
        if (!wp_next_scheduled('cfwv_check_api_health')) {
            $result = wp_schedule_event(time(), $interval, 'cfwv_check_api_health');
            if ($result === false) {
                $this->log_process('❌ ERROR: Failed to schedule cron job!');
            } else {
                $next_run = wp_next_scheduled('cfwv_check_api_health');
                $this->log_process('✅ API health monitoring scheduled successfully (' . $interval . ') - Next run: ' . date('Y-m-d H:i:s', $next_run));
            }
        } else {
            $next_run = wp_next_scheduled('cfwv_check_api_health');
            $this->log_process('ℹ️ API health monitoring already scheduled - Next run: ' . date('Y-m-d H:i:s', $next_run));
        }
    }
    
    /**
     * Static method for scheduling cron jobs during plugin activation
     * WordPress activation hooks ensure this only runs once
     */
    public static function activate_background_processor() {
        self::log_process_static('🔧 Scheduling background processor for plugin activation...');
        
        // Preferred interval - try custom first, fallback to built-in WordPress intervals
        $preferred_interval = 'every30seconds';
        
        // Get available schedules and manually add our custom intervals if not present
        $schedules = wp_get_schedules();
        
        // If custom intervals aren't available, add them manually for activation
        if (!isset($schedules[$preferred_interval])) {
            // Manually register custom intervals during activation
            self::register_custom_intervals_during_activation();
            
            // Re-get schedules after adding custom intervals
            $schedules = wp_get_schedules();
            
            if (!isset($schedules[$preferred_interval])) {
                self::log_process_static('❌ Custom intervals still not available. Using built-in WordPress interval.');
                $preferred_interval = 'hourly'; // Safe fallback to WordPress built-in
            } else {
                self::log_process_static('✅ Custom intervals manually registered for activation');
            }
        }
        
        // Schedule the API health check (WordPress handles the "only once" logic)
        if (!wp_next_scheduled('cfwv_check_api_health')) {
            $result = wp_schedule_event(time(), $preferred_interval, 'cfwv_check_api_health');
            if ($result === false) {
                self::log_process_static('❌ ERROR: Failed to schedule cron job during activation!');
            } else {
                $next_run = wp_next_scheduled('cfwv_check_api_health');
                self::log_process_static('✅ Background processor scheduled during activation (' . $preferred_interval . ') - Next run: ' . date('Y-m-d H:i:s', $next_run));
            }
        } else {
            $next_run = wp_next_scheduled('cfwv_check_api_health');
            self::log_process_static('ℹ️ Background processor already scheduled - Next run: ' . date('Y-m-d H:i:s', $next_run));
        }
    }
    
    /**
     * Manually register custom intervals during activation when filters might not be available
     */
    private static function register_custom_intervals_during_activation() {
        // Get our custom intervals definition
        $custom_intervals = self::get_custom_intervals_definition();
        
        // Apply the schedules filter manually with high priority
        add_filter('cron_schedules', function($existing_schedules) use ($custom_intervals) {
            $merged = array_merge($existing_schedules, $custom_intervals);
            self::log_process_static('📅 Custom intervals merged: ' . implode(', ', array_keys($custom_intervals)));
            return $merged;
        }, 10);
        
        self::log_process_static('📅 Custom intervals filter registered during activation');
    }
    
    /**
     * Auto-schedule cron jobs if not already scheduled
     */
    public function auto_schedule_cron_jobs() {
        $this->schedule_cron_jobs();
    }
    
    /**
     * Unschedule API health monitoring cron job
     */
    public function unschedule_cron_jobs() {
        // Clear API health monitoring cron job
        $timestamp = wp_next_scheduled('cfwv_check_api_health');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cfwv_check_api_health');
        }
        
        $this->log_process('API health monitoring unscheduled');
    }
    
    /**
     * Static method for unscheduling cron jobs during plugin deactivation
     * This doesn't require dependencies to be loaded
     */
    public static function deactivate_background_processor() {
        // Clear API health monitoring cron job
        $timestamp = wp_next_scheduled('cfwv_check_api_health');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cfwv_check_api_health');
            self::log_process_static('✅ Background processor unscheduled during deactivation');
        } else {
            self::log_process_static('ℹ️ No scheduled background processor found during deactivation');
        }
    }
    

    
    /**
     * Check API health and connectivity
     */
    public function check_api_health() {
        $this->log_process('Starting API health check');
        
        try {
            // Ensure dependencies are available
            if (!$this->whatsapp_validator) {
                $this->log_process('⚠️ WhatsApp validator not initialized, attempting to initialize...');
                $this->init();
            }
            
            if (!$this->whatsapp_validator) {
                $this->log_process('❌ ERROR: WhatsApp validator still not available, skipping API health check');
                return;
            }
            
            // Get active Wassenger account
            if (!$this->database) {
                $this->log_process('⚠️ Database not initialized, attempting to initialize...');
                $this->init();
            }
            
            $account = $this->database->get_active_wassenger_account();
            
            if (!$account) {
                $this->log_process('❌ No active Wassenger account found. Please add an account in settings.');
                return;
            }
            
            // Test API connection with the account
            $api_test = $this->whatsapp_validator->sync_session_for_wassenger($account);
            $this->log_process('API health check result: ' . print_r($api_test, true));
            if (!$api_test['success']) {
                $this->log_process('API connection failed: ' . $api_test['message']);
                
                // Send admin notification about API failure
                $this->send_admin_notification('WhatsApp Session Sync Failed', $api_test['message']);
            } else {
                $this->log_process('API connection is healthy');
            }
            
            // Track daily usage (simple counter)
            $today = date('Y-m-d');
            $usage_key = 'cfwv_daily_api_usage_' . $today;
            $daily_usage = get_option($usage_key, 0);
            
            // Increment usage counter
            update_option($usage_key, $daily_usage + 1);
            
            // Warn if usage is high (assuming 1000 daily limit)
            if ($daily_usage > 800) {
                $message = "High API usage detected: {$daily_usage} calls today";
                $this->log_process($message);
                $this->send_admin_notification('High WhatsApp API Usage Warning', $message);
            }
            
        } catch (Exception $e) {
            $this->log_process('Error checking API health: ' . $e->getMessage());
        }
    }
    
    /**
     * Send admin notification email
     */
    private function send_admin_notification($subject, $message) {
        $admin_email = get_option('admin_email');
        $site_name = get_option('blogname');
        
        $email_subject = "[{$site_name}] WhatsApp Plugin: {$subject}";
        $email_message = "Hello,\n\n";
        $email_message .= "This is an automated notification from your Contact Form WhatsApp Validation plugin.\n\n";
        $email_message .= "Issue: {$subject}\n";
        $email_message .= "Details: {$message}\n\n";
        $email_message .= "Time: " . current_time('mysql') . "\n\n";
        $email_message .= "Please check your plugin settings.\n\n";
        $email_message .= "Best regards,\n";
        $email_message .= "WhatsApp Validation Plugin";
        
        wp_mail($admin_email, $email_subject, $email_message);
    }
    
    /**
     * Log background process activity
     */
    private function log_process($message) {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] CFWV Background Process: {$message}";
        
        // Log to WordPress error log
        error_log($log_entry);
        
        // Optional: Store in database for debugging (only keep last 100 entries)
        $logs = get_option('cfwv_background_logs', array());
        array_unshift($logs, array(
            'timestamp' => $timestamp,
            'message' => $message
        ));
        $logs = array_slice($logs, 0, 100); // Keep only last 100 entries
        update_option('cfwv_background_logs', $logs);
    }
    
    /**
     * Static log method for use during activation/deactivation
     */
    private static function log_process_static($message) {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] CFWV Background Process (Static): {$message}";
        
        // Log to WordPress error log
        error_log($log_entry);
        
        // Optional: Store in database for debugging (only keep last 100 entries)
        $logs = get_option('cfwv_background_logs', array());
        array_unshift($logs, array(
            'timestamp' => $timestamp,
            'message' => $message
        ));
        $logs = array_slice($logs, 0, 100); // Keep only last 100 entries
        update_option('cfwv_background_logs', $logs);
    }
    
    /**
     * Get background process status (for debugging)
     */
    public function get_process_status() {
        $cron_jobs = array(
            'cfwv_check_api_health' => wp_next_scheduled('cfwv_check_api_health')
        );
        
        return $cron_jobs;
    }
    

    
    /**
     * Manual trigger for testing (can be called from other functions)
     */
    public function run_process_now($process_name = 'check_api') {
        $this->log_process('🧪 Manual trigger requested: ' . $process_name);
        
        switch ($process_name) {
            case 'check_api':
                $this->check_api_health();
                break;
            case 'debug_schedules':
                $this->debug_available_schedules();
                break;
            default:
                $this->log_process('Unknown process name: ' . $process_name . '. Supported: "check_api", "debug_schedules".');
        }
    }
    
    /**
     * Debug method to show available cron schedules
     */
    public function debug_available_schedules() {
        $schedules = wp_get_schedules();
        $custom_intervals = self::get_custom_intervals_definition();
        
        $this->log_process('🔍 Available cron schedules:');
        foreach ($schedules as $key => $schedule) {
            $interval_mins = $schedule['interval'] / 60;
            $is_custom = array_key_exists($key, $custom_intervals) ? ' (CUSTOM)' : '';
            $this->log_process("   - {$key}: {$interval_mins} minutes - {$schedule['display']}{$is_custom}");
        }
        
        $this->log_process('📊 Custom intervals status:');
        foreach ($custom_intervals as $key => $interval) {
            $status = isset($schedules[$key]) ? '✅ AVAILABLE' : '❌ MISSING';
            $this->log_process("   - {$key}: {$status}");
        }
    }
    
    /**
     * Show background processor status on admin dashboard
     */
    public function show_status_notice() {
        // Only show on plugin admin pages or dashboard
        $screen = get_current_screen();
        if (!$screen || (!strpos($screen->id, 'cfwv') && $screen->id !== 'dashboard')) {
            return;
        }
        
        // Get cron status
        $next_run = wp_next_scheduled('cfwv_check_api_health');
        $is_scheduled = $next_run !== false;
        
        // Get last execution status
        $logs = get_option('cfwv_background_logs', array());
        $last_log = !empty($logs) ? $logs[0] : null;
        
        // Check if Wassenger accounts are configured (database or legacy)
        $has_accounts = false;
        
        // Check database accounts (new system)
        if (isset($this->database)) {
            $accounts = $this->database->get_wassenger_accounts();
            $has_accounts = !empty($accounts);
        }
        
        // Also check legacy options (old system)
        if (!$has_accounts) {
            $api_token = get_option('cfwv_wassenger_api_token', '');
            $number_id = get_option('cfwv_wassenger_number_id', '');
            $has_accounts = !empty($api_token) && !empty($number_id);
        }
        
        $status_class = 'notice notice-info';
        $status_message = '';
        
        if (!$has_accounts) {
            $status_class = 'notice notice-warning';
            $status_message = '<strong>⚠️ WhatsApp Session Monitor:</strong> ';
            $status_message .= 'Wassenger account not configured. ';
            $status_message .= '<a href="' . admin_url('admin.php?page=cfwv-settings') . '">Configure Settings</a>';
        } elseif (!$is_scheduled) {
            $status_class = 'notice notice-error';
            $status_message = '<strong>❌ WhatsApp Session Monitor:</strong> Background monitoring not scheduled. Please check plugin configuration.';
        } else {
            $next_run_time = date('Y-m-d H:i:s', $next_run);
            $time_until = human_time_diff(time(), $next_run);
            
            // Get the current schedule interval for display
            $schedules = wp_get_schedules();
            $current_schedule = 'Unknown';
            $events = _get_cron_array();
            foreach ($events as $timestamp => $cron) {
                if (isset($cron['cfwv_check_api_health'])) {
                    foreach ($cron['cfwv_check_api_health'] as $event) {
                        if (isset($event['schedule']) && isset($schedules[$event['schedule']])) {
                            $current_schedule = $schedules[$event['schedule']]['display'];
                            break 2;
                        }
                    }
                }
            }
            
            $status_class = 'notice notice-success';
            $status_message = '<strong>✅ WhatsApp Session Monitor:</strong> Active (' . $current_schedule . ') - Next check in ' . $time_until . ' (' . $next_run_time . ')';
            
            // Add last execution status if available
            if ($last_log) {
                $last_time = human_time_diff(strtotime($last_log['timestamp']), time());
                $status_message .= ' | Last: ' . $last_time . ' ago';
                
                if (strpos($last_log['message'], 'failed') !== false || strpos($last_log['message'], 'Error') !== false) {
                    $status_class = 'notice notice-warning';
                    $status_message = str_replace('✅', '⚠️', $status_message);
                    $status_message .= ' (Last check had issues)';
                }
            }
        }
        
        echo '<div class="' . esc_attr($status_class) . '"><p>' . $status_message . '</p></div>';
    }
}
