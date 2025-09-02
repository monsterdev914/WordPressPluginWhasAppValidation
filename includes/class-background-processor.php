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
        // Initialize on WordPress init
        add_action('init', array($this, 'init'));
        
        // Register cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_intervals'));
        
        // Register cron hooks
        add_action('cfwv_check_api_health', array($this, 'check_api_health'));
        
        // Schedule cron jobs on plugin activation
        $this->schedule_cron_jobs();
        // register_activation_hook(CFWV_PLUGIN_PATH . 'contact-form-whatsapp-validation.php', array($this, 'schedule_cron_jobs'));
        register_deactivation_hook(CFWV_PLUGIN_PATH . 'contact-form-whatsapp-validation.php', array($this, 'unschedule_cron_jobs'));
    }
    
    public function init() {
        $this->database = new CFWV_Database();
        $this->whatsapp_validator = new CFWV_WhatsAppValidator();
        
        // Auto-schedule cron jobs if not already scheduled
        $this->auto_schedule_cron_jobs();
        
        // Show status on admin dashboard
        if (is_admin()) {
            add_action('admin_notices', array($this, 'show_status_notice'));
        }
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_intervals($schedules) {
        // Add custom intervals for more frequent checks
        $schedules['every5minutes'] = array(
            'interval' => 5 * 60,
            'display'  => __('Every 5 Minutes', 'contact-form-whatsapp')
        );
        
        $schedules['every10minutes'] = array(
            'interval' => 10 * 60,
            'display'  => __('Every 10 Minutes', 'contact-form-whatsapp')
        );
        
        $schedules['every15minutes'] = array(
            'interval' => 15 * 60,
            'display'  => __('Every 15 Minutes', 'contact-form-whatsapp')
        );
        
        $schedules['every30minutes'] = array(
            'interval' => 30 * 60,
            'display'  => __('Every 30 Minutes', 'contact-form-whatsapp')
        );
        
        return $schedules;
    }
    
    /**
     * Schedule cron job for API health monitoring
     */
    public function schedule_cron_jobs() {
        // Check API health - you can change the interval here:
        // Options: 'every5minutes', 'every10minutes', 'every15minutes', 'every30minutes', 'hourly', 'twicedaily', 'daily'
        $interval = 'every5minutes'; // ← Change this to your preferred interval
        
        if (!wp_next_scheduled('cfwv_check_api_health')) {
            wp_schedule_event(time(), $interval, 'cfwv_check_api_health');
            $this->log_process('API health monitoring scheduled successfully (' . $interval . ')');
        } else {
            $this->log_process('API health monitoring already scheduled');
        }
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
     * Check API health and connectivity
     */
    public function check_api_health() {
        $this->log_process('Starting API health check');
        
        try {
            // Test API connection
            $api_test = $this->whatsapp_validator->sync_session_for_wassenger();
            
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
        switch ($process_name) {
            case 'check_api':
                $this->check_api_health();
                break;
            default:
                $this->log_process('Unknown process name: ' . $process_name . '. Only "check_api" is supported.');
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
        
        // Get API token status
        $api_token = get_option('cfwv_wassenger_api_token', '');
        $number_id = get_option('cfwv_wassenger_number_id', '');
        
        $status_class = 'notice notice-info';
        $status_message = '';
        
        if (!$api_token || !$number_id) {
            $status_class = 'notice notice-warning';
            $status_message = '<strong>⚠️ WhatsApp Session Monitor:</strong> ';
            if (!$api_token && !$number_id) {
                $status_message .= 'API Token and Number ID not configured. ';
            } elseif (!$api_token) {
                $status_message .= 'API Token not configured. ';
            } else {
                $status_message .= 'Number ID not configured. ';
            }
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
