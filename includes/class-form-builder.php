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
     * Get country codes for WhatsApp fields
     */
    private function get_country_codes() {
        $countries = array(
            '+1-US' => array('name' => 'United States', 'code' => 'US'),
            '+1-CA' => array('name' => 'Canada', 'code' => 'CA'),
            '+44' => array('name' => 'United Kingdom', 'code' => 'GB'),
            '+33' => array('name' => 'France', 'code' => 'FR'),
            '+49' => array('name' => 'Germany', 'code' => 'DE'),
            '+39' => array('name' => 'Italy', 'code' => 'IT'),
            '+34' => array('name' => 'Spain', 'code' => 'ES'),
            '+31' => array('name' => 'Netherlands', 'code' => 'NL'),
            '+32' => array('name' => 'Belgium', 'code' => 'BE'),
            '+41' => array('name' => 'Switzerland', 'code' => 'CH'),
            '+43' => array('name' => 'Austria', 'code' => 'AT'),
            '+45' => array('name' => 'Denmark', 'code' => 'DK'),
            '+46' => array('name' => 'Sweden', 'code' => 'SE'),
            '+47' => array('name' => 'Norway', 'code' => 'NO'),
            '+358' => array('name' => 'Finland', 'code' => 'FI'),
            '+48' => array('name' => 'Poland', 'code' => 'PL'),
            '+420' => array('name' => 'Czech Republic', 'code' => 'CZ'),
            '+36' => array('name' => 'Hungary', 'code' => 'HU'),
            '+40' => array('name' => 'Romania', 'code' => 'RO'),
            '+359' => array('name' => 'Bulgaria', 'code' => 'BG'),
            '+385' => array('name' => 'Croatia', 'code' => 'HR'),
            '+386' => array('name' => 'Slovenia', 'code' => 'SI'),
            '+421' => array('name' => 'Slovakia', 'code' => 'SK'),
            '+370' => array('name' => 'Lithuania', 'code' => 'LT'),
            '+371' => array('name' => 'Latvia', 'code' => 'LV'),
            '+372' => array('name' => 'Estonia', 'code' => 'EE'),
            '+353' => array('name' => 'Ireland', 'code' => 'IE'),
            '+351' => array('name' => 'Portugal', 'code' => 'PT'),
            '+30' => array('name' => 'Greece', 'code' => 'GR'),
            '+357' => array('name' => 'Cyprus', 'code' => 'CY'),
            '+356' => array('name' => 'Malta', 'code' => 'MT'),
            '+352' => array('name' => 'Luxembourg', 'code' => 'LU'),
            '+377' => array('name' => 'Monaco', 'code' => 'MC'),
            '+378' => array('name' => 'San Marino', 'code' => 'SM'),
            '+39' => array('name' => 'Vatican City', 'code' => 'VA'),
            '+376' => array('name' => 'Andorra', 'code' => 'AD'),
            '+423' => array('name' => 'Liechtenstein', 'code' => 'LI'),
            '+7' => array('name' => 'Russia', 'code' => 'RU'),
            '+380' => array('name' => 'Ukraine', 'code' => 'UA'),
            '+375' => array('name' => 'Belarus', 'code' => 'BY'),
            '+81' => array('name' => 'Japan', 'code' => 'JP'),
            '+82' => array('name' => 'South Korea', 'code' => 'KR'),
            '+86' => array('name' => 'China', 'code' => 'CN'),
            '+852' => array('name' => 'Hong Kong', 'code' => 'HK'),
            '+853' => array('name' => 'Macau', 'code' => 'MO'),
            '+886' => array('name' => 'Taiwan', 'code' => 'TW'),
            '+65' => array('name' => 'Singapore', 'code' => 'SG'),
            '+60' => array('name' => 'Malaysia', 'code' => 'MY'),
            '+66' => array('name' => 'Thailand', 'code' => 'TH'),
            '+84' => array('name' => 'Vietnam', 'code' => 'VN'),
            '+855' => array('name' => 'Cambodia', 'code' => 'KH'),
            '+856' => array('name' => 'Laos', 'code' => 'LA'),
            '+95' => array('name' => 'Myanmar', 'code' => 'MM'),
            '+63' => array('name' => 'Philippines', 'code' => 'PH'),
            '+62' => array('name' => 'Indonesia', 'code' => 'ID'),
            '+673' => array('name' => 'Brunei', 'code' => 'BN'),
            '+91' => array('name' => 'India', 'code' => 'IN'),
            '+92' => array('name' => 'Pakistan', 'code' => 'PK'),
            '+880' => array('name' => 'Bangladesh', 'code' => 'BD'),
            '+94' => array('name' => 'Sri Lanka', 'code' => 'LK'),
            '+977' => array('name' => 'Nepal', 'code' => 'NP'),
            '+975' => array('name' => 'Bhutan', 'code' => 'BT'),
            '+960' => array('name' => 'Maldives', 'code' => 'MV'),
            '+93' => array('name' => 'Afghanistan', 'code' => 'AF'),
            '+98' => array('name' => 'Iran', 'code' => 'IR'),
            '+964' => array('name' => 'Iraq', 'code' => 'IQ'),
            '+90' => array('name' => 'Turkey', 'code' => 'TR'),
            '+90' => array('name' => 'Northern Cyprus', 'code' => 'TR'),
            '+961' => array('name' => 'Lebanon', 'code' => 'LB'),
            '+963' => array('name' => 'Syria', 'code' => 'SY'),
            '+972' => array('name' => 'Israel', 'code' => 'IL'),
            '+970' => array('name' => 'Palestine', 'code' => 'PS'),
            '+962' => array('name' => 'Jordan', 'code' => 'JO'),
            '+966' => array('name' => 'Saudi Arabia', 'code' => 'SA'),
            '+965' => array('name' => 'Kuwait', 'code' => 'KW'),
            '+973' => array('name' => 'Bahrain', 'code' => 'BH'),
            '+974' => array('name' => 'Qatar', 'code' => 'QA'),
            '+971' => array('name' => 'United Arab Emirates', 'code' => 'AE'),
            '+968' => array('name' => 'Oman', 'code' => 'OM'),
            '+967' => array('name' => 'Yemen', 'code' => 'YE'),
            '+20' => array('name' => 'Egypt', 'code' => 'EG'),
            '+218' => array('name' => 'Libya', 'code' => 'LY'),
            '+216' => array('name' => 'Tunisia', 'code' => 'TN'),
            '+213' => array('name' => 'Algeria', 'code' => 'DZ'),
            '+212' => array('name' => 'Morocco', 'code' => 'MA'),
            '+222' => array('name' => 'Mauritania', 'code' => 'MR'),
            '+220' => array('name' => 'Gambia', 'code' => 'GM'),
            '+221' => array('name' => 'Senegal', 'code' => 'SN'),
            '+223' => array('name' => 'Mali', 'code' => 'ML'),
            '+224' => array('name' => 'Guinea', 'code' => 'GN'),
            '+225' => array('name' => 'Ivory Coast', 'code' => 'CI'),
            '+226' => array('name' => 'Burkina Faso', 'code' => 'BF'),
            '+227' => array('name' => 'Niger', 'code' => 'NE'),
            '+228' => array('name' => 'Togo', 'code' => 'TG'),
            '+229' => array('name' => 'Benin', 'code' => 'BJ'),
            '+230' => array('name' => 'Mauritius', 'code' => 'MU'),
            '+231' => array('name' => 'Liberia', 'code' => 'LR'),
            '+232' => array('name' => 'Sierra Leone', 'code' => 'SL'),
            '+233' => array('name' => 'Ghana', 'code' => 'GH'),
            '+234' => array('name' => 'Nigeria', 'code' => 'NG'),
            '+235' => array('name' => 'Chad', 'code' => 'TD'),
            '+236' => array('name' => 'Central African Republic', 'code' => 'CF'),
            '+237' => array('name' => 'Cameroon', 'code' => 'CM'),
            '+238' => array('name' => 'Cape Verde', 'code' => 'CV'),
            '+239' => array('name' => 'São Tomé and Príncipe', 'code' => 'ST'),
            '+240' => array('name' => 'Equatorial Guinea', 'code' => 'GQ'),
            '+241' => array('name' => 'Gabon', 'code' => 'GA'),
            '+242' => array('name' => 'Republic of the Congo', 'code' => 'CG'),
            '+243' => array('name' => 'Democratic Republic of the Congo', 'code' => 'CD'),
            '+244' => array('name' => 'Angola', 'code' => 'AO'),
            '+245' => array('name' => 'Guinea-Bissau', 'code' => 'GW'),
            '+246' => array('name' => 'British Indian Ocean Territory', 'code' => 'IO'),
            '+248' => array('name' => 'Seychelles', 'code' => 'SC'),
            '+249' => array('name' => 'Sudan', 'code' => 'SD'),
            '+250' => array('name' => 'Rwanda', 'code' => 'RW'),
            '+251' => array('name' => 'Ethiopia', 'code' => 'ET'),
            '+252' => array('name' => 'Somalia', 'code' => 'SO'),
            '+253' => array('name' => 'Djibouti', 'code' => 'DJ'),
            '+254' => array('name' => 'Kenya', 'code' => 'KE'),
            '+255' => array('name' => 'Tanzania', 'code' => 'TZ'),
            '+256' => array('name' => 'Uganda', 'code' => 'UG'),
            '+257' => array('name' => 'Burundi', 'code' => 'BI'),
            '+258' => array('name' => 'Mozambique', 'code' => 'MZ'),
            '+260' => array('name' => 'Zambia', 'code' => 'ZM'),
            '+261' => array('name' => 'Madagascar', 'code' => 'MG'),
            '+262' => array('name' => 'Réunion', 'code' => 'RE'),
            '+263' => array('name' => 'Zimbabwe', 'code' => 'ZW'),
            '+264' => array('name' => 'Namibia', 'code' => 'NA'),
            '+265' => array('name' => 'Malawi', 'code' => 'MW'),
            '+266' => array('name' => 'Lesotho', 'code' => 'LS'),
            '+267' => array('name' => 'Botswana', 'code' => 'BW'),
            '+268' => array('name' => 'Swaziland', 'code' => 'SZ'),
            '+269' => array('name' => 'Comoros', 'code' => 'KM'),
            '+290' => array('name' => 'Saint Helena', 'code' => 'SH'),
            '+291' => array('name' => 'Eritrea', 'code' => 'ER'),
            '+297' => array('name' => 'Aruba', 'code' => 'AW'),
            '+298' => array('name' => 'Faroe Islands', 'code' => 'FO'),
            '+299' => array('name' => 'Greenland', 'code' => 'GL'),
            '+350' => array('name' => 'Gibraltar', 'code' => 'GI'),
            '+351' => array('name' => 'Portugal', 'code' => 'PT'),
            '+352' => array('name' => 'Luxembourg', 'code' => 'LU'),
            '+354' => array('name' => 'Iceland', 'code' => 'IS'),
            '+355' => array('name' => 'Albania', 'code' => 'AL'),
            '+356' => array('name' => 'Malta', 'code' => 'MT'),
            '+357' => array('name' => 'Cyprus', 'code' => 'CY'),
            '+358' => array('name' => 'Finland', 'code' => 'FI'),
            '+359' => array('name' => 'Bulgaria', 'code' => 'BG'),
            '+370' => array('name' => 'Lithuania', 'code' => 'LT'),
            '+371' => array('name' => 'Latvia', 'code' => 'LV'),
            '+372' => array('name' => 'Estonia', 'code' => 'EE'),
            '+373' => array('name' => 'Moldova', 'code' => 'MD'),
            '+374' => array('name' => 'Armenia', 'code' => 'AM'),
            '+375' => array('name' => 'Belarus', 'code' => 'BY'),
            '+376' => array('name' => 'Andorra', 'code' => 'AD'),
            '+377' => array('name' => 'Monaco', 'code' => 'MC'),
            '+378' => array('name' => 'San Marino', 'code' => 'SM'),
            '+380' => array('name' => 'Ukraine', 'code' => 'UA'),
            '+381' => array('name' => 'Serbia', 'code' => 'RS'),
            '+382' => array('name' => 'Montenegro', 'code' => 'ME'),
            '+383' => array('name' => 'Kosovo', 'code' => 'XK'),
            '+385' => array('name' => 'Croatia', 'code' => 'HR'),
            '+386' => array('name' => 'Slovenia', 'code' => 'SI'),
            '+387' => array('name' => 'Bosnia and Herzegovina', 'code' => 'BA'),
            '+389' => array('name' => 'North Macedonia', 'code' => 'MK'),
            '+420' => array('name' => 'Czech Republic', 'code' => 'CZ'),
            '+421' => array('name' => 'Slovakia', 'code' => 'SK'),
            '+423' => array('name' => 'Liechtenstein', 'code' => 'LI'),
            '+500' => array('name' => 'Falkland Islands', 'code' => 'FK'),
            '+501' => array('name' => 'Belize', 'code' => 'BZ'),
            '+502' => array('name' => 'Guatemala', 'code' => 'GT'),
            '+503' => array('name' => 'El Salvador', 'code' => 'SV'),
            '+504' => array('name' => 'Honduras', 'code' => 'HN'),
            '+505' => array('name' => 'Nicaragua', 'code' => 'NI'),
            '+506' => array('name' => 'Costa Rica', 'code' => 'CR'),
            '+507' => array('name' => 'Panama', 'code' => 'PA'),
            '+508' => array('name' => 'Saint Pierre and Miquelon', 'code' => 'PM'),
            '+509' => array('name' => 'Haiti', 'code' => 'HT'),
            '+590' => array('name' => 'Guadeloupe', 'code' => 'GP'),
            '+591' => array('name' => 'Bolivia', 'code' => 'BO'),
            '+592' => array('name' => 'Guyana', 'code' => 'GY'),
            '+593' => array('name' => 'Ecuador', 'code' => 'EC'),
            '+594' => array('name' => 'French Guiana', 'code' => 'GF'),
            '+595' => array('name' => 'Paraguay', 'code' => 'PY'),
            '+596' => array('name' => 'Martinique', 'code' => 'MQ'),
            '+597' => array('name' => 'Suriname', 'code' => 'SR'),
            '+598' => array('name' => 'Uruguay', 'code' => 'UY'),
            '+599' => array('name' => 'Netherlands Antilles', 'code' => 'AN'),
            '+670' => array('name' => 'East Timor', 'code' => 'TL'),
            '+672' => array('name' => 'Antarctica', 'code' => 'AQ'),
            '+673' => array('name' => 'Brunei', 'code' => 'BN'),
            '+674' => array('name' => 'Nauru', 'code' => 'NR'),
            '+675' => array('name' => 'Papua New Guinea', 'code' => 'PG'),
            '+676' => array('name' => 'Tonga', 'code' => 'TO'),
            '+677' => array('name' => 'Solomon Islands', 'code' => 'SB'),
            '+678' => array('name' => 'Vanuatu', 'code' => 'VU'),
            '+679' => array('name' => 'Fiji', 'code' => 'FJ'),
            '+680' => array('name' => 'Palau', 'code' => 'PW'),
            '+681' => array('name' => 'Wallis and Futuna', 'code' => 'WF'),
            '+682' => array('name' => 'Cook Islands', 'code' => 'CK'),
            '+683' => array('name' => 'Niue', 'code' => 'NU'),
            '+684' => array('name' => 'American Samoa', 'code' => 'AS'),
            '+685' => array('name' => 'Samoa', 'code' => 'WS'),
            '+686' => array('name' => 'Kiribati', 'code' => 'KI'),
            '+687' => array('name' => 'New Caledonia', 'code' => 'NC'),
            '+688' => array('name' => 'Tuvalu', 'code' => 'TV'),
            '+689' => array('name' => 'French Polynesia', 'code' => 'PF'),
            '+690' => array('name' => 'Tokelau', 'code' => 'TK'),
            '+691' => array('name' => 'Micronesia', 'code' => 'FM'),
            '+692' => array('name' => 'Marshall Islands', 'code' => 'MH'),
            '+850' => array('name' => 'North Korea', 'code' => 'KP'),
            '+852' => array('name' => 'Hong Kong', 'code' => 'HK'),
            '+853' => array('name' => 'Macau', 'code' => 'MO'),
            '+855' => array('name' => 'Cambodia', 'code' => 'KH'),
            '+856' => array('name' => 'Laos', 'code' => 'LA'),
            '+880' => array('name' => 'Bangladesh', 'code' => 'BD'),
            '+886' => array('name' => 'Taiwan', 'code' => 'TW'),
            '+960' => array('name' => 'Maldives', 'code' => 'MV'),
            '+961' => array('name' => 'Lebanon', 'code' => 'LB'),
            '+962' => array('name' => 'Jordan', 'code' => 'JO'),
            '+963' => array('name' => 'Syria', 'code' => 'SY'),
            '+964' => array('name' => 'Iraq', 'code' => 'IQ'),
            '+965' => array('name' => 'Kuwait', 'code' => 'KW'),
            '+966' => array('name' => 'Saudi Arabia', 'code' => 'SA'),
            '+967' => array('name' => 'Yemen', 'code' => 'YE'),
            '+968' => array('name' => 'Oman', 'code' => 'OM'),
            '+970' => array('name' => 'Palestine', 'code' => 'PS'),
            '+971' => array('name' => 'United Arab Emirates', 'code' => 'AE'),
            '+972' => array('name' => 'Israel', 'code' => 'IL'),
            '+973' => array('name' => 'Bahrain', 'code' => 'BH'),
            '+974' => array('name' => 'Qatar', 'code' => 'QA'),
            '+975' => array('name' => 'Bhutan', 'code' => 'BT'),
            '+976' => array('name' => 'Mongolia', 'code' => 'MN'),
            '+977' => array('name' => 'Nepal', 'code' => 'NP'),
            '+992' => array('name' => 'Tajikistan', 'code' => 'TJ'),
            '+993' => array('name' => 'Turkmenistan', 'code' => 'TM'),
            '+994' => array('name' => 'Azerbaijan', 'code' => 'AZ'),
            '+995' => array('name' => 'Georgia', 'code' => 'GE'),
            '+996' => array('name' => 'Kyrgyzstan', 'code' => 'KG'),
            '+998' => array('name' => 'Uzbekistan', 'code' => 'UZ'),
        );
        
        // Sort countries alphabetically by name
        uasort($countries, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $countries;
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
            ),
            'file' => array(
                'label' => __('File Upload', 'contact-form-whatsapp'),
                'icon' => 'dashicons-upload',
                'description' => __('File upload (PDF, DOC only)', 'contact-form-whatsapp')
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
        
        // Add nonce field and hidden form_id field
        $html .= wp_nonce_field('cfwv_nonce', 'nonce', true, false);
        $html .= '<input type="hidden" name="form_id" value="' . $form_id . '">';
        
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
                $country_codes = $this->get_country_codes();
                $default_country = '+1'; // Default to US
                $html .= '<div class="cfwv-whatsapp-field-wrapper">';
                $html .= '<div class="cfwv-country-code-container">';
                $html .= '<select class="cfwv-country-code-selector" name="' . $field->field_name . '_country_code" style="color: transparent;">';
                foreach ($country_codes as $code => $country) {
                    $actual_code = strpos($code, '-') !== false ? substr($code, 0, strpos($code, '-')) : $code;
                    $selected = ($actual_code === $default_country) ? 'selected' : '';
                    $html .= '<option value="' . esc_attr($actual_code) . '" ' . $selected . ' data-display="' . esc_attr($actual_code) . '">' . esc_html($country['name'] . ' (' . $actual_code . ')') . '</option>';
                }
                $html .= '</select>';
                $html .= '<div class="cfwv-country-code-display">' . esc_html($default_country) . '</div>';
                $html .= '</div>';
                $html .= '<input type="tel" ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-input cfwv-whatsapp-field ' . $field->field_class . '" ';
                $html .= 'placeholder="' . esc_attr($field->field_placeholder) . '" ';
                $html .= 'data-country-code="' . esc_attr($default_country) . '" ';
                $html .= $required_attr . ' />';
                $html .= '</div>';
                $html .= '<div class="cfwv-whatsapp-validation"></div>';
                break;
                
            case 'file':
                $html .= '<div class="cfwv-field-wrapper">';
                $html .= '<div class="cfwv-file-upload-wrapper">';
                $html .= '<input type="file" ';
                $html .= 'id="' . $field_id . '" ';
                $html .= 'name="' . $field->field_name . '" ';
                $html .= 'class="cfwv-field cfwv-file ' . $field->field_class . '" ';
                $html .= 'accept=".pdf,.doc,.docx" ';
                $html .= $required_attr . ' />';
                $html .= '<label for="' . $field_id . '" class="cfwv-file-label">';
                $html .= '<span class="cfwv-file-button">' . __('Choose File', 'contact-form-whatsapp') . '</span>';
                $html .= '<span class="cfwv-file-text">' . __('No file chosen', 'contact-form-whatsapp') . '</span>';
                $html .= '</label>';
                $html .= '</div>';
                $html .= '<div class="cfwv-file-info">';
                $html .= '<p class="cfwv-file-description">' . __('Accepted formats: PDF, DOC, DOCX (Max size: 5MB)', 'contact-form-whatsapp') . '</p>';
                $html .= '</div>';
                $html .= '</div>';
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