<?php
/**
 * Database class for Contact Form WhatsApp Validation plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_Database {
    
    private $wpdb;
    private $forms_table;
    private $form_fields_table;
    private $submissions_table;
    private $submission_data_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->forms_table = $wpdb->prefix . 'cfwv_forms';
        $this->form_fields_table = $wpdb->prefix . 'cfwv_form_fields';
        $this->submissions_table = $wpdb->prefix . 'cfwv_submissions';
        $this->submission_data_table = $wpdb->prefix . 'cfwv_submission_data';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Forms table
        $forms_sql = "CREATE TABLE $this->forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            redirect_url varchar(500),
            form_styles text,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Form fields table
        $form_fields_sql = "CREATE TABLE $this->form_fields_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            field_name varchar(255) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_type enum('text', 'email', 'tel', 'textarea', 'select', 'whatsapp') NOT NULL,
            field_options text,
            is_required tinyint(1) DEFAULT 0,
            field_order int DEFAULT 0,
            field_placeholder varchar(255),
            field_class varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY field_order (field_order)
        ) $charset_collate;";
        
        // Submissions table
        $submissions_sql = "CREATE TABLE $this->submissions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            whatsapp_number varchar(20),
            whatsapp_validated tinyint(1) DEFAULT 0,
            otp_code varchar(10),
            otp_verified tinyint(1) DEFAULT 0,
            otp_sent_at datetime NULL,
            otp_verified_at datetime NULL,
            otp_attempts int DEFAULT 0,
            submission_ip varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_phone_per_form (form_id, whatsapp_number),
            KEY form_id (form_id),
            KEY whatsapp_validated (whatsapp_validated),
            KEY otp_verified (otp_verified),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";
        
        // Submission data table
        $submission_data_sql = "CREATE TABLE $this->submission_data_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submission_id mediumint(9) NOT NULL,
            field_name varchar(255) NOT NULL,
            field_value text,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY field_name (field_name)
        ) $charset_collate;";
        
        // OTP verification sessions table
        $otp_sessions_sql = "CREATE TABLE {$this->wpdb->prefix}cfwv_otp_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submission_id mediumint(9) NOT NULL,
            otp_code varchar(10) NOT NULL,
            phone_number varchar(20) NOT NULL,
            session_token varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            verified tinyint(1) DEFAULT 0,
            attempts int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY session_token (session_token),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Wassenger accounts table
        $wassenger_accounts_sql = "CREATE TABLE {$this->wpdb->prefix}cfwv_wassenger_accounts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            account_name varchar(255) NOT NULL,
            api_token varchar(500) NOT NULL,
            number_id varchar(100) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            daily_limit int DEFAULT 1000,
            daily_used int DEFAULT 0,
            last_used datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY daily_used (daily_used)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($forms_sql);
        dbDelta($form_fields_sql);
        dbDelta($submissions_sql);
        dbDelta($submission_data_sql);
        dbDelta($otp_sessions_sql);
        dbDelta($wassenger_accounts_sql);
        
        // Add unique constraint for existing tables
        $this->add_phone_uniqueness_constraint();
    }
    
    /**
     * Add unique constraint for phone numbers per form
     */
    public function add_phone_uniqueness_constraint() {
        // Check if unique constraint already exists
        $constraint_exists = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM information_schema.table_constraints 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$this->submissions_table}' 
            AND constraint_name = 'unique_phone_per_form'
        ");
        
        if (!$constraint_exists) {
            // Add unique constraint
            $this->wpdb->query("
                ALTER TABLE {$this->submissions_table} 
                ADD UNIQUE KEY unique_phone_per_form (form_id, whatsapp_number)
            ");
        }
    }
    
    /**
     * Drop and recreate all tables (EMPTY)
     */
    public function reset_tables() {
        // Drop existing tables
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->wpdb->prefix}cfwv_wassenger_accounts");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->wpdb->prefix}cfwv_otp_sessions");
        $this->wpdb->query("DROP TABLE IF EXISTS $this->submission_data_table");
        $this->wpdb->query("DROP TABLE IF EXISTS $this->submissions_table");
        $this->wpdb->query("DROP TABLE IF EXISTS $this->form_fields_table");
        $this->wpdb->query("DROP TABLE IF EXISTS $this->forms_table");
        
        // Recreate tables fresh
        $this->create_tables();
    }
    
    /**
     * Clear all data from tables but keep structure
     */
    public function clear_all_data() {
        // Clear data in correct order (child tables first)
        $this->wpdb->query("DELETE FROM $this->submission_data_table");
        $this->wpdb->query("DELETE FROM $this->submissions_table");
        $this->wpdb->query("DELETE FROM $this->form_fields_table");
        $this->wpdb->query("DELETE FROM $this->forms_table");
        
        // Reset auto-increment
        $this->wpdb->query("ALTER TABLE $this->submission_data_table AUTO_INCREMENT = 1");
        $this->wpdb->query("ALTER TABLE $this->submissions_table AUTO_INCREMENT = 1");
        $this->wpdb->query("ALTER TABLE $this->form_fields_table AUTO_INCREMENT = 1");
        $this->wpdb->query("ALTER TABLE $this->forms_table AUTO_INCREMENT = 1");
    }
    
    /**
     * Get all forms
     */
    public function get_forms() {
        $results = $this->wpdb->get_results("SELECT * FROM $this->forms_table ORDER BY created_at DESC");
        return $results;
    }
    
    /**
     * Get form by ID
     */
    public function get_form($id) {
        $result = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->forms_table WHERE id = %d", $id));
        return $result;
    }
    
    /**
     * Create or update form
     */
    public function save_form($data) {
        $data['updated_at'] = current_time('mysql');
        
        if (isset($data['id']) && $data['id'] > 0) {
            $result = $this->wpdb->update($this->forms_table, $data, array('id' => $data['id']));
            return $data['id'];
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $this->wpdb->insert($this->forms_table, $data);
            return $this->wpdb->insert_id;
        }
    }
    
    /**
     * Delete form
     */
    public function delete_form($id) {
        // Delete form fields
        $this->wpdb->delete($this->form_fields_table, array('form_id' => $id));
        
        // Delete submissions and submission data
        $submission_ids = $this->wpdb->get_col($this->wpdb->prepare("SELECT id FROM $this->submissions_table WHERE form_id = %d", $id));
        if (!empty($submission_ids)) {
            $placeholders = implode(',', array_fill(0, count($submission_ids), '%d'));
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM $this->submission_data_table WHERE submission_id IN ($placeholders)", $submission_ids));
        }
        $this->wpdb->delete($this->submissions_table, array('form_id' => $id));
        
        // Delete form
        return $this->wpdb->delete($this->forms_table, array('id' => $id));
    }
    
    /**
     * Get form fields
     */
    public function get_form_fields($form_id) {
        $results = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->form_fields_table WHERE form_id = %d ORDER BY field_order ASC", $form_id));
        return $results;
    }
    
    /**
     * Save form field
     */
    public function save_form_field($data) {
        if (isset($data['id']) && $data['id'] > 0) {
            return $this->wpdb->update($this->form_fields_table, $data, array('id' => $data['id']));
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $this->wpdb->insert($this->form_fields_table, $data);
            return $this->wpdb->insert_id;
        }
    }
    
    /**
     * Delete form field
     */
    public function delete_form_field($id) {
        return $this->wpdb->delete($this->form_fields_table, array('id' => $id));
    }
    
    /**
     * Save form submission
     */
    public function save_submission($form_id, $whatsapp_number, $whatsapp_validated, $form_data) {
        // Check if phone number already exists for this form
        $existing_submission = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id, otp_verified FROM {$this->submissions_table} 
             WHERE form_id = %d AND whatsapp_number = %s",
            $form_id, $whatsapp_number
        ));
        
        if ($existing_submission) {
            // If submission exists and is verified, return error
            if ($existing_submission->otp_verified) {
                return array(
                    'error' => 'duplicate_phone',
                    'message' => 'This phone number has already been used for this form.'
                );
            }
            // If submission exists but not verified, update it
            $submission_id = $existing_submission->id;
        } else {
            // Insert new submission
            $submission_data = array(
                'form_id' => $form_id,
                'whatsapp_number' => $whatsapp_number,
                'whatsapp_validated' => $whatsapp_validated ? 1 : 0,
                'submission_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'submitted_at' => current_time('mysql')
            );
            
            $result = $this->wpdb->insert($this->submissions_table, $submission_data);
            
            if ($result === false) {
                return false;
            }
            
            $submission_id = $this->wpdb->insert_id;
        }
        
        // Clear existing form data if updating
        if ($existing_submission) {
            $this->wpdb->delete($this->submission_data_table, array('submission_id' => $submission_id));
        }
        
        // Insert form data
        foreach ($form_data as $field_name => $field_value) {
            $this->wpdb->insert($this->submission_data_table, array(
                'submission_id' => $submission_id,
                'field_name' => $field_name,
                'field_value' => is_array($field_value) ? serialize($field_value) : $field_value
            ));
        }
        
        return $submission_id;
    }
    
    /**
     * Get submissions
     */
    public function get_submissions($form_id = null, $limit = 50, $offset = 0) {
        $where = '';
        if ($form_id) {
            $where = $this->wpdb->prepare(" WHERE s.form_id = %d", $form_id);
        }
        
        $query = "SELECT s.*, f.name as form_name 
                  FROM $this->submissions_table s 
                  LEFT JOIN $this->forms_table f ON s.form_id = f.id 
                  $where 
                  ORDER BY s.submitted_at DESC 
                  LIMIT %d OFFSET %d";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($query, $limit, $offset));
        
        // Get submission data for each submission
        foreach ($results as $submission) {
            $data = $this->wpdb->get_results($this->wpdb->prepare("SELECT field_name, field_value FROM $this->submission_data_table WHERE submission_id = %d", $submission->id));
            $submission->data = array();
            foreach ($data as $field) {
                $submission->data[$field->field_name] = $field->field_value;
            }
        }
        
        return $results;
    }
    
    /**
     * Get submission by ID
     */
    public function get_submission($id) {
        $submission = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->submissions_table WHERE id = %d", $id));
        
        if ($submission) {
            $data = $this->wpdb->get_results($this->wpdb->prepare("SELECT field_name, field_value FROM $this->submission_data_table WHERE submission_id = %d", $id));
            $submission->data = array();
            foreach ($data as $field) {
                $submission->data[$field->field_name] = $field->field_value;
            }
        }
        
        return $submission;
    }
    
    /**
     * Delete submission
     */
    public function delete_submission($id) {
        $this->wpdb->delete($this->submission_data_table, array('submission_id' => $id));
        return $this->wpdb->delete($this->submissions_table, array('id' => $id));
    }
    
    /**
     * Get submissions count
     */
    public function get_submissions_count($form_id = null) {
        $where = '';
        if ($form_id) {
            $where = $this->wpdb->prepare(" WHERE form_id = %d", $form_id);
        }
        
        return $this->wpdb->get_var("SELECT COUNT(*) FROM $this->submissions_table $where");
    }
    
    /**
     * Get submissions for CSV export
     */
    public function get_submissions_for_export($form_id = null) {
        $where = '';
        if ($form_id) {
            $where = $this->wpdb->prepare(" WHERE s.form_id = %d", $form_id);
        }
        
        $query = "SELECT s.*, f.name as form_name 
                  FROM $this->submissions_table s 
                  LEFT JOIN $this->forms_table f ON s.form_id = f.id 
                  $where 
                  ORDER BY s.submitted_at DESC";
        
        $results = $this->wpdb->get_results($query);
        
        // Get submission data for each submission
        foreach ($results as $submission) {
            $data = $this->wpdb->get_results($this->wpdb->prepare("SELECT field_name, field_value FROM $this->submission_data_table WHERE submission_id = %d", $submission->id));
            $submission->data = array();
            foreach ($data as $field) {
                $submission->data[$field->field_name] = $field->field_value;
            }
        }
        
        return $results;
    }
    
    /**
     * Create OTP session
     */
    public function create_otp_session($submission_id, $phone_number, $otp_code, $session_token, $expires_at) {
        $otp_sessions_table = $this->wpdb->prefix . 'cfwv_otp_sessions';
        
        $data = array(
            'submission_id' => $submission_id,
            'otp_code' => $otp_code,
            'phone_number' => $phone_number,
            'session_token' => $session_token,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql')
        );
        
        return $this->wpdb->insert($otp_sessions_table, $data);
    }
    
    /**
     * Get OTP session by token
     */
    public function get_otp_session($session_token) {
        $otp_sessions_table = $this->wpdb->prefix . 'cfwv_otp_sessions';
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $otp_sessions_table WHERE session_token = %s AND expires_at > %s",
            $session_token,
            current_time('mysql')
        ));
    }
    
    /**
     * Verify OTP code
     */
    public function verify_otp($session_token, $otp_code) {
        $otp_sessions_table = $this->wpdb->prefix . 'cfwv_otp_sessions';
        $submissions_table = $this->submissions_table;
        
        // Get the session
        $session = $this->get_otp_session($session_token);
        
        if (!$session) {
            return array('success' => false, 'message' => 'Invalid or expired session');
        }
        
        // Check if OTP matches
        if ($session->otp_code !== $otp_code) {
            // Increment attempts
            $this->wpdb->update(
                $otp_sessions_table,
                array('attempts' => $session->attempts + 1),
                array('id' => $session->id)
            );
            
            return array('success' => false, 'message' => 'Invalid OTP code');
        }
        
        // Mark OTP as verified
        $this->wpdb->update(
            $otp_sessions_table,
            array('verified' => 1),
            array('id' => $session->id)
        );
        
        // Update submission
        $this->wpdb->update(
            $submissions_table,
            array(
                'otp_verified' => 1,
                'otp_verified_at' => current_time('mysql')
            ),
            array('id' => $session->submission_id)
        );
        
        return array('success' => true, 'message' => 'OTP verified successfully');
    }
    
    /**
     * Get active Wassenger account
     */
    public function get_active_wassenger_account() {
        $wassenger_accounts_table = $this->wpdb->prefix . 'cfwv_wassenger_accounts';
        
        // Get account with lowest daily usage that's under limit
        $account = $this->wpdb->get_row(
            "SELECT * FROM $wassenger_accounts_table 
             WHERE is_active = 1 AND daily_used < daily_limit 
             ORDER BY daily_used ASC, last_used ASC 
             LIMIT 1"
        );
        
        return $account;
    }
    
    /**
     * Update Wassenger account usage
     */
    public function update_wassenger_usage($account_id) {
        $wassenger_accounts_table = $this->wpdb->prefix . 'cfwv_wassenger_accounts';
        
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE $wassenger_accounts_table 
             SET daily_used = daily_used + 1, last_used = %s 
             WHERE id = %d",
            current_time('mysql'),
            $account_id
        ));
    }
    
    /**
     * Add Wassenger account
     */
    public function add_wassenger_account($account_name, $api_token, $number_id, $daily_limit = 1000) {
        $wassenger_accounts_table = $this->wpdb->prefix . 'cfwv_wassenger_accounts';
        
        $data = array(
            'account_name' => $account_name,
            'api_token' => $api_token,
            'number_id' => $number_id,
            'daily_limit' => $daily_limit,
            'created_at' => current_time('mysql')
        );
        
        return $this->wpdb->insert($wassenger_accounts_table, $data);
    }
    
    /**
     * Get all Wassenger accounts
     */
    public function get_wassenger_accounts() {
        $wassenger_accounts_table = $this->wpdb->prefix . 'cfwv_wassenger_accounts';
        
        return $this->wpdb->get_results("SELECT * FROM $wassenger_accounts_table ORDER BY created_at DESC");
    }
    
    /**
     * Update submission with OTP data
     */
    public function update_submission_otp($submission_id, $otp_code) {
        $data = array(
            'otp_code' => $otp_code,
            'otp_sent_at' => current_time('mysql')
        );
        
        return $this->wpdb->update($this->submissions_table, $data, array('id' => $submission_id));
    }
    
    /**
     * Migrate legacy WordPress options to database accounts
     */
    public function migrate_legacy_options() {
        $api_token = get_option('cfwv_wassenger_api_token');
        $number_id = get_option('cfwv_wassenger_number_id');
        
        // Check if we have legacy options and no database accounts
        if (!empty($api_token) && !empty($number_id)) {
            $existing_accounts = $this->get_wassenger_accounts();
            
            if (empty($existing_accounts)) {
                // Migrate legacy options to database
                $result = $this->add_wassenger_account(
                    'Migrated Account',
                    $api_token,
                    $number_id,
                    1000
                );
                
                if ($result) {
                    // Clear legacy options
                    delete_option('cfwv_wassenger_api_token');
                    delete_option('cfwv_wassenger_number_id');
                    
                    return array(
                        'success' => true,
                        'message' => 'Legacy settings migrated to database successfully.'
                    );
                }
            }
        }
        
        return array(
            'success' => false,
            'message' => 'No legacy settings found or accounts already exist.'
        );
    }
} 