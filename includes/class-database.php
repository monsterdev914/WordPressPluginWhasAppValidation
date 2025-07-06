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
            submission_ip varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY whatsapp_validated (whatsapp_validated),
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($forms_sql);
        dbDelta($form_fields_sql);
        dbDelta($submissions_sql);
        dbDelta($submission_data_sql);
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
        // Insert submission
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
} 