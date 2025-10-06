<?php
/**
 * Form Builder class for Contact Form WhatsApp Validation plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_FormBuilder {
    
    private $database;
    
    public function __construct() {
        $this->database = new CFWV_Database();
    }
    
    /**
     * Create default form fields
     */
    public function create_default_fields() {
        // Check if default form already exists
        $forms = $this->database->get_forms();
        if (!empty($forms)) {
            return;
        }
        
        // Create default form
        $form_data = array(
            'name' => 'Contact Form',
            'description' => 'Default contact form with WhatsApp validation',
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
        
        $form_id = $this->database->save_form($form_data);
        
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
            $this->database->save_form_field($field);
        }
    }
    
    /**
     * Get available field types
     */
    public function get_field_types() {
        return array(
            'text' => array(
                'label' => __('Text Input', 'contact-form-whatsapp'),
                'icon' => 'dashicons-editor-textcolor',
                'description' => __('Single line text input', 'contact-form-whatsapp')
            ),
            'email' => array(
                'label' => __('Email', 'contact-form-whatsapp'),
                'icon' => 'dashicons-email',
                'description' => __('Email address input with validation', 'contact-form-whatsapp')
            ),
            'tel' => array(
                'label' => __('Phone', 'contact-form-whatsapp'),
                'icon' => 'dashicons-phone',
                'description' => __('Phone number input', 'contact-form-whatsapp')
            ),
            'textarea' => array(
                'label' => __('Textarea', 'contact-form-whatsapp'),
                'icon' => 'dashicons-editor-alignleft',
                'description' => __('Multi-line text input', 'contact-form-whatsapp')
            ),
            'select' => array(
                'label' => __('Dropdown', 'contact-form-whatsapp'),
                'icon' => 'dashicons-arrow-down',
                'description' => __('Dropdown selection', 'contact-form-whatsapp')
            ),
            'whatsapp' => array(
                'label' => __('WhatsApp Number', 'contact-form-whatsapp'),
                'icon' => 'dashicons-phone',
                'description' => __('WhatsApp number with validation', 'contact-form-whatsapp')
            )
        );
    }
    
    /**
     * Validate field data
     */
    public function validate_field_data($field_data) {
        $errors = array();
        
        // Check required fields
        if (empty($field_data['field_name'])) {
            $errors[] = __('Field name is required', 'contact-form-whatsapp');
        }
        
        if (empty($field_data['field_label'])) {
            $errors[] = __('Field label is required', 'contact-form-whatsapp');
        }
        
        if (empty($field_data['field_type'])) {
            $errors[] = __('Field type is required', 'contact-form-whatsapp');
        }
        
        // Validate field name (only letters, numbers, underscore)
        if (!empty($field_data['field_name']) && !preg_match('/^[a-zA-Z0-9_]+$/', $field_data['field_name'])) {
            $errors[] = __('Field name can only contain letters, numbers, and underscores', 'contact-form-whatsapp');
        }
        
        // Check for duplicate field names (only for new fields or when changing field name)
        if (!empty($field_data['field_name']) && !empty($field_data['form_id'])) {
            $existing_fields = $this->database->get_form_fields($field_data['form_id']);
            
            foreach ($existing_fields as $existing_field) {
                // Skip if this is the same field being updated
                if (isset($field_data['id']) && $field_data['id'] == $existing_field->id) {
                    continue;
                }
                
                if ($existing_field->field_name === $field_data['field_name']) {
                    $errors[] = __('Field name already exists. Please use a different name.', 'contact-form-whatsapp');
                    break;
                }
            }
        }
        
        // Validate field type
        $valid_types = array_keys($this->get_field_types());
        if (!empty($field_data['field_type']) && !in_array($field_data['field_type'], $valid_types)) {
            $errors[] = __('Invalid field type', 'contact-form-whatsapp');
        }
        
        // Validate select options
        if ($field_data['field_type'] === 'select') {
            if (empty($field_data['field_options'])) {
                $errors[] = __('Select field must have options', 'contact-form-whatsapp');
            } else {
                $options = $this->parse_select_options($field_data['field_options']);
                if (empty($options)) {
                    $errors[] = __('Select field options are invalid', 'contact-form-whatsapp');
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Parse select options
     */
    public function parse_select_options($options_string) {
        if (empty($options_string)) {
            return array();
        }
        
        $options = array();
        $lines = explode("\n", trim($options_string));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Check if line has value|label format
            if (strpos($line, '|') !== false) {
                list($value, $label) = explode('|', $line, 2);
                $options[trim($value)] = trim($label);
            } else {
                $options[trim($line)] = trim($line);
            }
        }
        
        return $options;
    }
    
    /**
     * Generate form HTML
     */
    public function generate_form_html($form_id, $custom_styles = array(), $show_title = true) {
        $form = $this->database->get_form($form_id);
        if (!$form) {
            return '<p>' . __('Form not found', 'contact-form-whatsapp') . '</p>';
        }
        
        $fields = $this->database->get_form_fields($form_id);
        if (empty($fields)) {
            return '<p>' . __('No form fields found', 'contact-form-whatsapp') . '</p>';
        }
        
        // Parse form styles
        $form_styles = !empty($form->form_styles) ? json_decode($form->form_styles, true) : array();
        $form_styles = array_merge($form_styles, $custom_styles);
        
        // Generate CSS
        $css = $this->generate_form_css($form_id, $form_styles);
        
        // Generate HTML
        $html = '<div class="cfwv-form-container" id="cfwv-form-' . $form_id . '">';
        $html .= '<style>' . $css . '</style>';
        
        // Add form title
        if ($show_title && !empty($form->name)) {
            $html .= '<div class="cfwv-form-header">';
            $html .= '<h2 class="cfwv-form-title">' . esc_html($form->name) . '</h2>';
            if (!empty($form->description)) {
                $html .= '<p class="cfwv-form-description">' . esc_html($form->description) . '</p>';
            }
            $html .= '</div>';
        }
        
        $html .= '<form class="cfwv-form" data-form-id="' . $form_id . '">';
        
        foreach ($fields as $field) {
            $html .= $this->generate_field_html($field);
        }
        
        // Add submit button
        $html .= '<div class="cfwv-field-wrapper cfwv-submit-wrapper">';
        $html .= '<button type="submit" class="cfwv-submit-btn">' . __('Submit', 'contact-form-whatsapp') . '</button>';
        $html .= '</div>';
        
        // Add loading indicator
        $html .= '<div class="cfwv-loading" style="display: none;">';
        $html .= '<span class="cfwv-spinner"></span> ' . __('Processing...', 'contact-form-whatsapp');
        $html .= '</div>';
        
        // Add messages container
        $html .= '<div class="cfwv-messages"></div>';
        
        $html .= '</form>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate field HTML
     */
    private function generate_field_html($field) {
        $field_id = 'cfwv-field-' . $field->field_name;
        $required_attr = $field->is_required ? 'required' : '';
        $required_mark = $field->is_required ? '<span class="cfwv-required">*</span>' : '';
        
        $html = '<div class="cfwv-field-wrapper cfwv-field-' . $field->field_type . '">';
        $html .= '<label for="' . $field_id . '" class="cfwv-field-label">';
        $html .= esc_html($field->field_label) . $required_mark;
        $html .= '</label>';
        
        switch ($field->field_type) {
            case 'text':
            case 'email':
            case 'tel':
                $html .= '<input type="' . $field->field_type . '" ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-input ' . $field->field_class . '" ';
                $html .= 'placeholder="' . esc_attr($field->field_placeholder) . '" ';
                $html .= $required_attr . ' />';
                break;
                
            case 'whatsapp':
                $html .= '<input type="tel" ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-input cfwv-whatsapp-field ' . $field->field_class . '" ';
                $html .= 'placeholder="' . esc_attr($field->field_placeholder) . '" ';
                $html .= $required_attr . ' />';
                $html .= '<div class="cfwv-whatsapp-validation"></div>';
                break;
                
            case 'textarea':
                $html .= '<textarea ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-textarea ' . $field->field_class . '" ';
                $html .= 'placeholder="' . esc_attr($field->field_placeholder) . '" ';
                $html .= $required_attr . ' rows="4"></textarea>';
                break;
                
            case 'select':
                $options = $this->parse_select_options($field->field_options);
                $html .= '<select ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-select ' . $field->field_class . '" ';
                $html .= $required_attr . '>';
                
                if (!$field->is_required) {
                    $html .= '<option value="">' . __('Select an option', 'contact-form-whatsapp') . '</option>';
                }
                
                foreach ($options as $value => $label) {
                    $html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }
                
                $html .= '</select>';
                break;
        }
        
        $html .= '<div class="cfwv-field-error"></div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate form CSS
     */
    private function generate_form_css($form_id, $styles) {
        $css = "
        #cfwv-form-{$form_id} {
            background-color: {$styles['background_color']};
            color: {$styles['text_color']};
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        #cfwv-form-{$form_id} .cfwv-form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid {$styles['border_color']};
        }
        
        #cfwv-form-{$form_id} .cfwv-form-title {
            margin: 0 0 10px 0;
            color: {$styles['text_color']};
            font-size: 28px;
            font-weight: bold;
            line-height: 1.2;
        }
        
        #cfwv-form-{$form_id} .cfwv-form-description {
            margin: 0;
            color: {$styles['text_color']};
            font-size: 16px;
            line-height: 1.5;
            opacity: 0.8;
        }
        
        #cfwv-form-{$form_id} .cfwv-field-wrapper {
            margin-bottom: 20px;
        }
        
        #cfwv-form-{$form_id} .cfwv-field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: {$styles['text_color']};
        }
        
        #cfwv-form-{$form_id} .cfwv-required {
            color: #e74c3c;
        }
        
        #cfwv-form-{$form_id} .cfwv-field {
            width: 100%;
            padding: 12px;
            border: 2px solid {$styles['border_color']};
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        #cfwv-form-{$form_id} .cfwv-field:focus {
            outline: none;
            border-color: {$styles['button_color']};
        }
        
        #cfwv-form-{$form_id} .cfwv-submit-btn {
            background-color: {$styles['button_color']};
            color: {$styles['button_text_color']};
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        
        #cfwv-form-{$form_id} .cfwv-submit-btn:hover {
            opacity: 0.9;
        }
        
        #cfwv-form-{$form_id} .cfwv-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #cfwv-form-{$form_id} .cfwv-field-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        #cfwv-form-{$form_id} .cfwv-whatsapp-validation {
            margin-top: 5px;
            font-size: 14px;
        }
        
        #cfwv-form-{$form_id} .cfwv-whatsapp-validation.valid {
            color: #27ae60;
        }
        
        #cfwv-form-{$form_id} .cfwv-whatsapp-validation.invalid {
            color: #e74c3c;
        }
        
        #cfwv-form-{$form_id} .cfwv-loading {
            text-align: center;
            margin-top: 20px;
        }
        
        #cfwv-form-{$form_id} .cfwv-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid {$styles['button_color']};
            border-radius: 50%;
            animation: cfwv-spin 1s linear infinite;
        }
        
        @keyframes cfwv-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #cfwv-form-{$form_id} .cfwv-messages {
            margin-top: 20px;
        }
        
        #cfwv-form-{$form_id} .cfwv-message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        #cfwv-form-{$form_id} .cfwv-message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        #cfwv-form-{$form_id} .cfwv-message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        ";
        
        return $css;
    }
    
    /**
     * Check if form has required fields
     */
    public function has_required_fields($form_id) {
        $fields = $this->database->get_form_fields($form_id);
        
        $has_name = false;
        $has_email = false;
        $has_whatsapp = false;
        
        foreach ($fields as $field) {
            if ($field->field_name === 'name' || $field->field_type === 'text') {
                $has_name = true;
            }
            if ($field->field_name === 'email' || $field->field_type === 'email') {
                $has_email = true;
            }
            if ($field->field_name === 'whatsapp' || $field->field_type === 'whatsapp') {
                $has_whatsapp = true;
            }
        }
        
        return $has_name && $has_email && $has_whatsapp;
    }
} 