<?php
/**
 * WhatsApp Validator class for Contact Form WhatsApp Validation plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_WhatsAppValidator {
    
    private $api_url = 'https://api.wassenger.com/v1/numbers/exists';
    private $api_token;
    
    public function __construct() {
        $this->api_token = get_option('cfwv_wassenger_api_token');
    }
    
    /**
     * Validate WhatsApp number
     */
    public function validate_number($phone_number) {
        if (empty($this->api_token)) {
            return array(
                'success' => false,
                'message' => __('WhatsApp API token not configured', 'contact-form-whatsapp'),
                'valid' => false
            );
        }
        
        // Clean phone number
        $phone_number = $this->clean_phone_number($phone_number);
        
        if (empty($phone_number)) {
            return array(
                'success' => false,
                'message' => __('Invalid phone number format', 'contact-form-whatsapp'),
                'valid' => false
            );
        }
        
        // Make API request
        $response = $this->make_api_request($phone_number);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'valid' => false
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => __('Invalid API response', 'contact-form-whatsapp'),
                'valid' => false
            );
        }
        
        // Check if the number exists on WhatsApp
        if (isset($data['exists']) && $data['exists'] === true) {
            return array(
                'success' => true,
                'message' => __('WhatsApp number is valid', 'contact-form-whatsapp'),
                'valid' => true,
                'formatted_number' => isset($data['formatted']) ? $data['formatted'] : $phone_number
            );
        } else {
            return array(
                'success' => true,
                'message' => __('This number is not registered on WhatsApp', 'contact-form-whatsapp'),
                'valid' => false
            );
        }
    }
    
    /**
     * Clean phone number
     */
    private function clean_phone_number($phone_number) {
        // Remove all non-digit characters except +
        $phone_number = preg_replace('/[^\d+]/', '', $phone_number);
        
        // If number doesn't start with +, add it
        if (!empty($phone_number) && substr($phone_number, 0, 1) !== '+') {
            $phone_number = '+' . $phone_number;
        }
        
        return $phone_number;
    }
    
    /**
     * Make API request to Wassenger
     */
    private function make_api_request($phone_number) {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Token' => $this->api_token
            ),
            'body' => json_encode(array(
                'phone' => $phone_number
            )),
            'timeout' => 30
        );
        
        $response = wp_remote_request($this->api_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            
            if (isset($error_data['message'])) {
                return new WP_Error('api_error', $error_data['message']);
            } else {
                return new WP_Error('api_error', sprintf(__('API request failed with status code: %d', 'contact-form-whatsapp'), $response_code));
            }
        }
        
        return $response;
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        if (empty($this->api_token)) {
            return array(
                'success' => false,
                'message' => __('API token is required', 'contact-form-whatsapp')
            );
        }
        
        // Test with a known WhatsApp number (you can use your own)
        $test_number = '+1234567890'; // This should be replaced with a real test number
        
        $response = $this->make_api_request($test_number);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('API connection successful', 'contact-form-whatsapp')
        );
    }
    
    /**
     * Format phone number for display
     */
    public function format_phone_number($phone_number) {
        $cleaned = $this->clean_phone_number($phone_number);
        
        // Basic formatting for common country codes
        if (preg_match('/^\+1(\d{3})(\d{3})(\d{4})$/', $cleaned, $matches)) {
            // US/Canada format
            return "+1 ({$matches[1]}) {$matches[2]}-{$matches[3]}";
        } elseif (preg_match('/^\+44(\d{4})(\d{6})$/', $cleaned, $matches)) {
            // UK format
            return "+44 {$matches[1]} {$matches[2]}";
        } elseif (preg_match('/^\+(\d{1,3})(\d+)$/', $cleaned, $matches)) {
            // Generic international format
            return "+{$matches[1]} {$matches[2]}";
        }
        
        return $cleaned;
    }
    
    /**
     * Get supported country codes
     */
    public function get_supported_countries() {
        return array(
            '+1' => 'United States/Canada',
            '+44' => 'United Kingdom',
            '+33' => 'France',
            '+49' => 'Germany',
            '+34' => 'Spain',
            '+39' => 'Italy',
            '+31' => 'Netherlands',
            '+32' => 'Belgium',
            '+41' => 'Switzerland',
            '+43' => 'Austria',
            '+45' => 'Denmark',
            '+46' => 'Sweden',
            '+47' => 'Norway',
            '+358' => 'Finland',
            '+91' => 'India',
            '+86' => 'China',
            '+81' => 'Japan',
            '+82' => 'South Korea',
            '+61' => 'Australia',
            '+64' => 'New Zealand',
            '+55' => 'Brazil',
            '+52' => 'Mexico',
            '+54' => 'Argentina',
            '+56' => 'Chile',
            '+57' => 'Colombia',
            '+58' => 'Venezuela',
            '+51' => 'Peru',
            '+593' => 'Ecuador',
            '+595' => 'Paraguay',
            '+598' => 'Uruguay',
            '+27' => 'South Africa',
            '+20' => 'Egypt',
            '+212' => 'Morocco',
            '+213' => 'Algeria',
            '+216' => 'Tunisia',
            '+218' => 'Libya',
            '+966' => 'Saudi Arabia',
            '+971' => 'UAE',
            '+965' => 'Kuwait',
            '+973' => 'Bahrain',
            '+974' => 'Qatar',
            '+968' => 'Oman',
            '+962' => 'Jordan',
            '+961' => 'Lebanon',
            '+90' => 'Turkey',
            '+98' => 'Iran',
            '+92' => 'Pakistan',
            '+880' => 'Bangladesh',
            '+94' => 'Sri Lanka',
            '+95' => 'Myanmar',
            '+60' => 'Malaysia',
            '+65' => 'Singapore',
            '+66' => 'Thailand',
            '+84' => 'Vietnam',
            '+63' => 'Philippines',
            '+62' => 'Indonesia'
        );
    }
} 