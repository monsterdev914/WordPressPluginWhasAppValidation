<?php
/**
 * Frontend class for Contact Form WhatsApp Validation plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_Frontend {
    
    private $database;
    private $form_builder;
    private $whatsapp_validator;
    
    public function __construct() {
        $this->init_components();
    }
    
    private function init_components() {
        $this->database = new CFWV_Database();
        $this->form_builder = new CFWV_FormBuilder();
        $this->whatsapp_validator = new CFWV_WhatsAppValidator();
    }
    
    /**
     * Render form
     */
    public function render_form($form_id, $style = 'default') {
        $form = $this->database->get_form($form_id);
        
        if (!$form || $form->status !== 'active') {
            return '<p>' . __('Form not found or inactive', 'contact-form-whatsapp') . '</p>';
        }
        
        // Check if form has required fields
        if (!$this->form_builder->has_required_fields($form_id)) {
            return '<p>' . __('Form is missing required fields (Name, Email, WhatsApp)', 'contact-form-whatsapp') . '</p>';
        }
        
        // Generate and return form HTML
        return $this->form_builder->generate_form_html($form_id);
    }
    
    /**
     * Submit form
     */
    public function submit_form($form_data) {
        // Validate nonce
        if (!isset($form_data['nonce']) || !wp_verify_nonce($form_data['nonce'], 'cfwv_nonce')) {
            return array(
                'success' => false,
                'message' => __('Security check failed', 'contact-form-whatsapp')
            );
        }
        
        $form_id = intval($form_data['form_id']);
        $form = $this->database->get_form($form_id);
        
        if (!$form || $form->status !== 'active') {
            return array(
                'success' => false,
                'message' => __('Form not found or inactive', 'contact-form-whatsapp')
            );
        }
        
        // Get form fields
        $fields = $this->database->get_form_fields($form_id);
        
        // Validate form data
        $validation_result = $this->validate_form_data($form_data, $fields);
        
        if (!$validation_result['success']) {
            return $validation_result;
        }
        
        // Extract WhatsApp number for validation
        $whatsapp_number = '';
        $whatsapp_validated = false;
        
        foreach ($fields as $field) {
            if ($field->field_type === 'whatsapp' && isset($form_data[$field->field_name])) {
                $whatsapp_number = $form_data[$field->field_name];
                break;
            }
        }
        
        // Validate WhatsApp number
        if (!empty($whatsapp_number)) {
            $validation_result = $this->whatsapp_validator->validate_number($whatsapp_number);
            
            if (!$validation_result['success']) {
                return array(
                    'success' => false,
                    'message' => $validation_result['message']
                );
            }
            
            $whatsapp_validated = $validation_result['valid'];
            
            if (!$whatsapp_validated) {
                return array(
                    'success' => false,
                    'message' => __('WhatsApp number is not valid', 'contact-form-whatsapp')
                );
            }
            
            // Use formatted number if available
            if (isset($validation_result['formatted_number'])) {
                $whatsapp_number = $validation_result['formatted_number'];
            }
        }
        
        // Prepare form data for database
        $clean_form_data = array();
        foreach ($fields as $field) {
            if (isset($form_data[$field->field_name])) {
                $clean_form_data[$field->field_name] = $this->sanitize_field_value($form_data[$field->field_name], $field->field_type);
            }
        }
        
        // Save submission to database
        $submission_id = $this->database->save_submission($form_id, $whatsapp_number, $whatsapp_validated, $clean_form_data);
        
        if (!$submission_id) {
            return array(
                'success' => false,
                'message' => __('Failed to save submission. Please try again.', 'contact-form-whatsapp')
            );
        }
        
        // Send notification emails (if configured)
        $this->send_notification_emails($form, $clean_form_data, $submission_id);
        
        // Prepare response
        $response = array(
            'success' => true,
            'message' => __('Form submitted successfully!', 'contact-form-whatsapp'),
            'submission_id' => $submission_id
        );
        
        // Add redirect URL if configured
        if (!empty($form->redirect_url)) {
            $response['redirect_url'] = $form->redirect_url;
        }
        
        return $response;
    }
    
    /**
     * Validate form data
     */
    private function validate_form_data($form_data, $fields) {
        $errors = array();
        
        foreach ($fields as $field) {
            $field_name = $field->field_name;
            $field_value = isset($form_data[$field_name]) ? $form_data[$field_name] : '';
            
            // Check required fields
            if ($field->is_required && empty($field_value)) {
                $errors[$field_name] = sprintf(__('%s is required', 'contact-form-whatsapp'), $field->field_label);
                continue;
            }
            
            // Skip validation for empty optional fields
            if (empty($field_value)) {
                continue;
            }
            
            // Validate field type
            switch ($field->field_type) {
                case 'email':
                    if (!is_email($field_value)) {
                        $errors[$field_name] = sprintf(__('%s must be a valid email address', 'contact-form-whatsapp'), $field->field_label);
                    }
                    break;
                    
                case 'tel':
                case 'whatsapp':
                    if (!$this->validate_phone_number($field_value)) {
                        $errors[$field_name] = sprintf(__('%s must be a valid phone number', 'contact-form-whatsapp'), $field->field_label);
                    }
                    break;
                    
                case 'select':
                    $options = $this->form_builder->parse_select_options($field->field_options);
                    if (!array_key_exists($field_value, $options)) {
                        $errors[$field_name] = sprintf(__('%s contains an invalid selection', 'contact-form-whatsapp'), $field->field_label);
                    }
                    break;
                    
                case 'text':
                case 'textarea':
                    // Basic length validation
                    if (strlen($field_value) > 5000) {
                        $errors[$field_name] = sprintf(__('%s is too long (maximum 5000 characters)', 'contact-form-whatsapp'), $field->field_label);
                    }
                    break;
            }
        }
        
        // Check for required core fields
        $has_name = false;
        $has_email = false;
        $has_whatsapp = false;
        
        foreach ($fields as $field) {
            if (($field->field_name === 'name' || $field->field_type === 'text') && !empty($form_data[$field->field_name])) {
                $has_name = true;
            }
            if (($field->field_name === 'email' || $field->field_type === 'email') && !empty($form_data[$field->field_name])) {
                $has_email = true;
            }
            if (($field->field_name === 'whatsapp' || $field->field_type === 'whatsapp') && !empty($form_data[$field->field_name])) {
                $has_whatsapp = true;
            }
        }
        
        if (!$has_name) {
            $errors['_general'] = __('Name is required', 'contact-form-whatsapp');
        }
        if (!$has_email) {
            $errors['_general'] = __('Email is required', 'contact-form-whatsapp');
        }
        if (!$has_whatsapp) {
            $errors['_general'] = __('WhatsApp number is required', 'contact-form-whatsapp');
        }
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => __('Please fix the following errors:', 'contact-form-whatsapp'),
                'errors' => $errors
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * Validate phone number
     */
    private function validate_phone_number($phone) {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Check if it's a valid international format
        if (preg_match('/^\+[1-9]\d{1,14}$/', $cleaned)) {
            return true;
        }
        
        // Check if it's a valid US format without +
        if (preg_match('/^[1-9]\d{9}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Sanitize field value
     */
    private function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'email':
                return sanitize_email($value);
                
            case 'tel':
            case 'whatsapp':
                return sanitize_text_field($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'select':
                return sanitize_text_field($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Send notification emails
     */
    private function send_notification_emails($form, $form_data, $submission_id) {
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Prepare email content
        $subject = sprintf(__('New form submission from %s', 'contact-form-whatsapp'), get_bloginfo('name'));
        
        $message = sprintf(__('A new form submission has been received from %s.', 'contact-form-whatsapp'), $form->name) . "\n\n";
        $message .= __('Submission Details:', 'contact-form-whatsapp') . "\n";
        $message .= str_repeat('-', 30) . "\n";
        
        foreach ($form_data as $field_name => $field_value) {
            $message .= ucfirst(str_replace('_', ' ', $field_name)) . ": " . $field_value . "\n";
        }
        
        $message .= "\n" . __('Submission ID:', 'contact-form-whatsapp') . " " . $submission_id . "\n";
        $message .= __('Submitted on:', 'contact-form-whatsapp') . " " . date('Y-m-d H:i:s') . "\n";
        $message .= __('View all submissions:', 'contact-form-whatsapp') . " " . admin_url('admin.php?page=cfwv-submissions') . "\n";
        
        // Send email to admin
        wp_mail($admin_email, $subject, $message);
        
        // Send auto-reply email to user (if email field exists)
        if (isset($form_data['email'])) {
            $user_email = $form_data['email'];
            $user_name = isset($form_data['name']) ? $form_data['name'] : '';
            
            $user_subject = sprintf(__('Thank you for contacting %s', 'contact-form-whatsapp'), get_bloginfo('name'));
            
            $user_message = sprintf(__('Dear %s,', 'contact-form-whatsapp'), $user_name) . "\n\n";
            $user_message .= __('Thank you for your message. We have received your inquiry and will get back to you soon.', 'contact-form-whatsapp') . "\n\n";
            $user_message .= __('Your submission details:', 'contact-form-whatsapp') . "\n";
            $user_message .= str_repeat('-', 30) . "\n";
            
            foreach ($form_data as $field_name => $field_value) {
                $user_message .= ucfirst(str_replace('_', ' ', $field_name)) . ": " . $field_value . "\n";
            }
            
            $user_message .= "\n" . __('Best regards,', 'contact-form-whatsapp') . "\n";
            $user_message .= get_bloginfo('name') . "\n";
            
            wp_mail($user_email, $user_subject, $user_message);
        }
    }
    
    /**
     * Get form data for AJAX
     */
    public function get_form_data($form_id) {
        $form = $this->database->get_form($form_id);
        
        if (!$form) {
            return array(
                'success' => false,
                'message' => __('Form not found', 'contact-form-whatsapp')
            );
        }
        
        $fields = $this->database->get_form_fields($form_id);
        
        return array(
            'success' => true,
            'form' => $form,
            'fields' => $fields
        );
    }
    
    /**
     * Handle form preview
     */
    public function preview_form($form_id) {
        $form = $this->database->get_form($form_id);
        
        if (!$form) {
            return '<p>' . __('Form not found', 'contact-form-whatsapp') . '</p>';
        }
        
        // Add preview indicator
        $preview_html = '<div class="cfwv-form-preview-notice">';
        $preview_html .= '<strong>' . __('Form Preview', 'contact-form-whatsapp') . '</strong> - ';
        $preview_html .= __('This is a preview of your form. Submissions will not be saved.', 'contact-form-whatsapp');
        $preview_html .= '</div>';
        
        return $preview_html . $this->form_builder->generate_form_html($form_id);
    }
    
    /**
     * Get form statistics
     */
    public function get_form_stats($form_id) {
        $total_submissions = $this->database->get_submissions_count($form_id);
        $recent_submissions = $this->database->get_submissions($form_id, 5);
        
        // Calculate validation stats
        $validated_count = 0;
        $all_submissions = $this->database->get_submissions($form_id, 1000); // Get larger sample
        
        foreach ($all_submissions as $submission) {
            if ($submission->whatsapp_validated) {
                $validated_count++;
            }
        }
        
        $validation_rate = $total_submissions > 0 ? ($validated_count / $total_submissions) * 100 : 0;
        
        return array(
            'total_submissions' => $total_submissions,
            'validated_submissions' => $validated_count,
            'validation_rate' => round($validation_rate, 2),
            'recent_submissions' => $recent_submissions
        );
    }
    
    /**
     * Export single submission
     */
    public function export_submission($submission_id) {
        $submission = $this->database->get_submission($submission_id);
        
        if (!$submission) {
            return array(
                'success' => false,
                'message' => __('Submission not found', 'contact-form-whatsapp')
            );
        }
        
        return array(
            'success' => true,
            'submission' => $submission
        );
    }
    
    /**
     * Get form shortcode
     */
    public function get_form_shortcode($form_id) {
        $form = $this->database->get_form($form_id);
        
        if (!$form) {
            return null;
        }
        
        return '[cfwv_form id="' . $form_id . '"]';
    }
    
    /**
     * Validate form configuration
     */
    public function validate_form_configuration($form_id) {
        $form = $this->database->get_form($form_id);
        
        if (!$form) {
            return array(
                'valid' => false,
                'message' => __('Form not found', 'contact-form-whatsapp')
            );
        }
        
        $fields = $this->database->get_form_fields($form_id);
        
        if (empty($fields)) {
            return array(
                'valid' => false,
                'message' => __('Form has no fields', 'contact-form-whatsapp')
            );
        }
        
        // Check for required fields
        if (!$this->form_builder->has_required_fields($form_id)) {
            return array(
                'valid' => false,
                'message' => __('Form is missing required fields (Name, Email, WhatsApp)', 'contact-form-whatsapp')
            );
        }
        
        // Check API configuration
        $api_token = get_option('cfwv_wassenger_api_token');
        if (empty($api_token)) {
            return array(
                'valid' => false,
                'message' => __('WhatsApp API token is not configured', 'contact-form-whatsapp')
            );
        }
        
        return array(
            'valid' => true,
            'message' => __('Form configuration is valid', 'contact-form-whatsapp')
        );
    }
} 