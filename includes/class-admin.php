<?php
/**
 * Admin class for Contact Form WhatsApp Validation plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFWV_Admin {
    
    private $database;
    private $form_builder;
    private $whatsapp_validator;
    
    public function __construct() {
        // Initialize components
        $this->init_components();
        
        // Add AJAX handlers
        add_action('wp_ajax_cfwv_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_cfwv_delete_form', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_cfwv_save_field', array($this, 'ajax_save_field'));
        add_action('wp_ajax_cfwv_delete_field', array($this, 'ajax_delete_field'));
        add_action('wp_ajax_cfwv_get_field_form', array($this, 'ajax_get_field_form'));
        add_action('wp_ajax_cfwv_update_field_order', array($this, 'ajax_update_field_order'));
        add_action('wp_ajax_cfwv_get_submission', array($this, 'ajax_get_submission'));
        add_action('wp_ajax_cfwv_delete_submission', array($this, 'ajax_delete_submission'));
        add_action('wp_ajax_cfwv_export_submissions', array($this, 'ajax_export_submissions'));
        add_action('wp_ajax_cfwv_test_api', array($this, 'ajax_test_api'));
    }
    
    private function init_components() {
        $this->database = new CFWV_Database();
        $this->form_builder = new CFWV_FormBuilder();
        $this->whatsapp_validator = new CFWV_WhatsAppValidator();
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $forms = $this->database->get_forms();
        $total_submissions = $this->database->get_submissions_count();
        $recent_submissions = $this->database->get_submissions(null, 5);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Contact Form WhatsApp Dashboard', 'contact-form-whatsapp'); ?></h1>
            
            <div class="cfwv-dashboard-stats">
                <div class="cfwv-stat-box">
                    <h3><?php echo count($forms); ?></h3>
                    <p><?php _e('Total Forms', 'contact-form-whatsapp'); ?></p>
                </div>
                <div class="cfwv-stat-box">
                    <h3><?php echo $total_submissions; ?></h3>
                    <p><?php _e('Total Submissions', 'contact-form-whatsapp'); ?></p>
                </div>
                <div class="cfwv-stat-box">
                    <h3><?php echo count($recent_submissions); ?></h3>
                    <p><?php _e('Recent Submissions', 'contact-form-whatsapp'); ?></p>
                </div>
            </div>
            
            <div class="cfwv-dashboard-content">
                <div class="cfwv-dashboard-left">
                    <div class="cfwv-panel">
                        <h2><?php _e('Your Forms', 'contact-form-whatsapp'); ?></h2>
                        <?php if (empty($forms)): ?>
                            <p><?php _e('No forms found. Create your first form!', 'contact-form-whatsapp'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=cfwv-form-builder'); ?>" class="button button-primary">
                                <?php _e('Create Form', 'contact-form-whatsapp'); ?>
                            </a>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Form Name', 'contact-form-whatsapp'); ?></th>
                                        <th><?php _e('Status', 'contact-form-whatsapp'); ?></th>
                                        <th><?php _e('Shortcode', 'contact-form-whatsapp'); ?></th>
                                        <th><?php _e('Actions', 'contact-form-whatsapp'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($form->name); ?></strong></td>
                                        <td>
                                            <span class="cfwv-status cfwv-status-<?php echo $form->status; ?>">
                                                <?php echo ucfirst($form->status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code>[cfwv_form id="<?php echo $form->id; ?>"]</code>
                                            <button class="button button-small cfwv-copy-shortcode" data-shortcode="[cfwv_form id=&quot;<?php echo $form->id; ?>&quot;]">
                                                <?php _e('Copy', 'contact-form-whatsapp'); ?>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=cfwv-form-builder&form_id=' . $form->id); ?>" class="button button-small">
                                                <?php _e('Edit', 'contact-form-whatsapp'); ?>
                                            </a>
                                            <a href="<?php echo admin_url('admin.php?page=cfwv-submissions&form_id=' . $form->id); ?>" class="button button-small">
                                                <?php _e('View Submissions', 'contact-form-whatsapp'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="cfwv-dashboard-right">
                    <div class="cfwv-panel">
                        <h2><?php _e('Recent Submissions', 'contact-form-whatsapp'); ?></h2>
                        <?php if (empty($recent_submissions)): ?>
                            <p><?php _e('No submissions yet.', 'contact-form-whatsapp'); ?></p>
                        <?php else: ?>
                            <ul class="cfwv-recent-submissions">
                                <?php foreach ($recent_submissions as $submission): ?>
                                <li>
                                    <strong><?php echo isset($submission->data['name']) ? esc_html($submission->data['name']) : 'N/A'; ?></strong>
                                    <br>
                                    <small><?php echo date('Y-m-d H:i', strtotime($submission->submitted_at)); ?></small>
                                    <br>
                                    <span class="cfwv-whatsapp-status <?php echo $submission->whatsapp_validated ? 'valid' : 'invalid'; ?>">
                                        <?php echo $submission->whatsapp_validated ? __('WhatsApp Validated', 'contact-form-whatsapp') : __('WhatsApp Not Validated', 'contact-form-whatsapp'); ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?php echo admin_url('admin.php?page=cfwv-submissions'); ?>" class="button">
                                <?php _e('View All Submissions', 'contact-form-whatsapp'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cfwv-panel">
                        <h2><?php _e('Quick Actions', 'contact-form-whatsapp'); ?></h2>
                        <div class="cfwv-quick-actions">
                            <a href="<?php echo admin_url('admin.php?page=cfwv-form-builder'); ?>" class="button button-primary">
                                <?php _e('Create New Form', 'contact-form-whatsapp'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=cfwv-settings'); ?>" class="button">
                                <?php _e('Configure Settings', 'contact-form-whatsapp'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Form builder page
     */
    public function form_builder_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $form = $form_id ? $this->database->get_form($form_id) : null;
        $fields = $form_id ? $this->database->get_form_fields($form_id) : array();
        $field_types = $this->form_builder->get_field_types();
        
        ?>
        <div class="wrap cfwv-form-builder">
            <h1><?php echo $form ? __('Edit Form', 'contact-form-whatsapp') : __('Create New Form', 'contact-form-whatsapp'); ?></h1>
            
            <form id="cfwv-form-builder" method="post">
                <?php wp_nonce_field('cfwv_save_form', 'cfwv_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                
                <div class="cfwv-form-builder-content">
                    <div class="cfwv-form-settings">
                        <h2><?php _e('Form Settings', 'contact-form-whatsapp'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="form_name"><?php _e('Form Name', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="text" id="form_name" name="form_name" value="<?php echo $form ? esc_attr($form->name) : ''; ?>" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="form_description"><?php _e('Description', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <textarea id="form_description" name="form_description" rows="3" class="large-text"><?php echo $form ? esc_textarea($form->description) : ''; ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="redirect_url"><?php _e('Redirect URL', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="url" id="redirect_url" name="redirect_url" value="<?php echo $form ? esc_attr($form->redirect_url) : ''; ?>" class="regular-text">
                                    <p class="description"><?php _e('URL to redirect users after successful form submission (optional)', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="form_status"><?php _e('Status', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <select id="form_status" name="form_status">
                                        <option value="active" <?php echo ($form && $form->status === 'active') ? 'selected' : ''; ?>><?php _e('Active', 'contact-form-whatsapp'); ?></option>
                                        <option value="inactive" <?php echo ($form && $form->status === 'inactive') ? 'selected' : ''; ?>><?php _e('Inactive', 'contact-form-whatsapp'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('Form Styling', 'contact-form-whatsapp'); ?></h3>
                        <?php
                        $styles = $form && $form->form_styles ? json_decode($form->form_styles, true) : array();
                        $default_styles = array(
                            'background_color' => '#ffffff',
                            'text_color' => '#333333',
                            'border_color' => '#cccccc',
                            'button_color' => '#007cba',
                            'button_text_color' => '#ffffff'
                        );
                        $styles = array_merge($default_styles, $styles);
                        ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="background_color"><?php _e('Background Color', 'contact-form-whatsapp'); ?></label></th>
                                <td><input type="color" id="background_color" name="background_color" value="<?php echo esc_attr($styles['background_color']); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="text_color"><?php _e('Text Color', 'contact-form-whatsapp'); ?></label></th>
                                <td><input type="color" id="text_color" name="text_color" value="<?php echo esc_attr($styles['text_color']); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="border_color"><?php _e('Border Color', 'contact-form-whatsapp'); ?></label></th>
                                <td><input type="color" id="border_color" name="border_color" value="<?php echo esc_attr($styles['border_color']); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="button_color"><?php _e('Button Color', 'contact-form-whatsapp'); ?></label></th>
                                <td><input type="color" id="button_color" name="button_color" value="<?php echo esc_attr($styles['button_color']); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="button_text_color"><?php _e('Button Text Color', 'contact-form-whatsapp'); ?></label></th>
                                <td><input type="color" id="button_text_color" name="button_text_color" value="<?php echo esc_attr($styles['button_text_color']); ?>"></td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="save_form" class="button button-primary" value="<?php _e('Save Form', 'contact-form-whatsapp'); ?>">
                        </p>
                    </div>
                    
                    <div class="cfwv-form-fields">
                        <h2><?php _e('Form Fields', 'contact-form-whatsapp'); ?></h2>
                        
                        <div class="cfwv-field-types">
                            <h3><?php _e('Add Field', 'contact-form-whatsapp'); ?></h3>
                            <div class="cfwv-field-type-buttons">
                                <?php foreach ($field_types as $type => $info): ?>
                                <button type="button" class="button cfwv-add-field" data-type="<?php echo $type; ?>">
                                    <span class="dashicons <?php echo $info['icon']; ?>"></span>
                                    <?php echo $info['label']; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="cfwv-fields-list">
                            <?php foreach ($fields as $field): ?>
                            <div class="cfwv-field-item" data-field-id="<?php echo $field->id; ?>">
                                <div class="cfwv-field-header">
                                    <span class="cfwv-field-drag">⋮⋮</span>
                                    <strong><?php echo esc_html($field->field_label); ?></strong>
                                    <span class="cfwv-field-type">(<?php echo $field->field_type; ?>)</span>
                                    <div class="cfwv-field-actions">
                                        <button type="button" class="button button-small cfwv-edit-field"><?php _e('Edit', 'contact-form-whatsapp'); ?></button>
                                        <button type="button" class="button button-small cfwv-delete-field"><?php _e('Delete', 'contact-form-whatsapp'); ?></button>
                                    </div>
                                </div>
                                <div class="cfwv-field-details" style="display: none;">
                                    <?php $this->render_field_form($field); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Field Form Modal -->
        <div id="cfwv-field-modal" class="cfwv-modal" style="display: none;">
            <div class="cfwv-modal-content">
                <span class="cfwv-modal-close">&times;</span>
                <h2 id="cfwv-modal-title"><?php _e('Add Field', 'contact-form-whatsapp'); ?></h2>
                <form id="cfwv-field-form">
                    <div id="cfwv-field-form-content"></div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Submissions page
     */
    public function submissions_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $submissions = $this->database->get_submissions($form_id, $per_page, $offset);
        $total_submissions = $this->database->get_submissions_count($form_id);
        $total_pages = ceil($total_submissions / $per_page);
        
        $forms = $this->database->get_forms();
        
        ?>
        <div class="wrap cfwv-submissions">
            <h1><?php _e('Form Submissions', 'contact-form-whatsapp'); ?></h1>
            
            <div class="cfwv-submissions-filters">
                <form method="get">
                    <input type="hidden" name="page" value="cfwv-submissions">
                    <select name="form_id" onchange="this.form.submit()">
                        <option value=""><?php _e('All Forms', 'contact-form-whatsapp'); ?></option>
                        <?php foreach ($forms as $form): ?>
                        <option value="<?php echo $form->id; ?>" <?php selected($form_id, $form->id); ?>>
                            <?php echo esc_html($form->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e('Filter', 'contact-form-whatsapp'); ?>">
                </form>
                
                <div class="cfwv-submissions-actions">
                    <button type="button" class="button cfwv-export-submissions" data-form-id="<?php echo $form_id; ?>">
                        <?php _e('Export CSV', 'contact-form-whatsapp'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($submissions)): ?>
                <p><?php _e('No submissions found.', 'contact-form-whatsapp'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Form', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Name', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Email', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('WhatsApp', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Status', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Actions', 'contact-form-whatsapp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($submission->submitted_at)); ?></td>
                            <td><?php echo esc_html($submission->form_name); ?></td>
                            <td><?php echo isset($submission->data['name']) ? esc_html($submission->data['name']) : 'N/A'; ?></td>
                            <td><?php echo isset($submission->data['email']) ? esc_html($submission->data['email']) : 'N/A'; ?></td>
                            <td><?php echo esc_html($submission->whatsapp_number); ?></td>
                            <td>
                                <span class="cfwv-whatsapp-status <?php echo $submission->whatsapp_validated ? 'valid' : 'invalid'; ?>">
                                    <?php echo $submission->whatsapp_validated ? __('Validated', 'contact-form-whatsapp') : __('Not Validated', 'contact-form-whatsapp'); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small cfwv-view-submission" data-id="<?php echo $submission->id; ?>">
                                    <?php _e('View', 'contact-form-whatsapp'); ?>
                                </button>
                                <button type="button" class="button button-small cfwv-delete-submission" data-id="<?php echo $submission->id; ?>">
                                    <?php _e('Delete', 'contact-form-whatsapp'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                <div class="cfwv-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page,
                        'total' => $total_pages,
                        'prev_text' => __('« Previous', 'contact-form-whatsapp'),
                        'next_text' => __('Next »', 'contact-form-whatsapp')
                    ));
                    ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Submission Details Modal -->
        <div id="cfwv-submission-modal" class="cfwv-modal" style="display: none;">
            <div class="cfwv-modal-content">
                <span class="cfwv-modal-close">&times;</span>
                <h2><?php _e('Submission Details', 'contact-form-whatsapp'); ?></h2>
                <div id="cfwv-submission-details"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('cfwv_settings', 'cfwv_nonce');
            
            $api_token = sanitize_text_field($_POST['wassenger_api_token']);
            update_option('cfwv_wassenger_api_token', $api_token);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'contact-form-whatsapp') . '</p></div>';
        }
        
        $api_token = get_option('cfwv_wassenger_api_token', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Contact Form WhatsApp Settings', 'contact-form-whatsapp'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('cfwv_settings', 'cfwv_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wassenger_api_token"><?php _e('Wassenger API Token', 'contact-form-whatsapp'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wassenger_api_token" name="wassenger_api_token" value="<?php echo esc_attr($api_token); ?>" class="regular-text" required>
                            <p class="description">
                                <?php _e('Enter your Wassenger API token. You can get this from your Wassenger dashboard.', 'contact-form-whatsapp'); ?>
                                <br>
                                <a href="https://api.wassenger.com" target="_blank"><?php _e('Get API Token', 'contact-form-whatsapp'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'contact-form-whatsapp'); ?>">
                    <button type="button" id="cfwv-test-api" class="button"><?php _e('Test API Connection', 'contact-form-whatsapp'); ?></button>
                </p>
            </form>
            
            <div id="cfwv-api-test-result"></div>
            
            <div class="cfwv-settings-info">
                <h2><?php _e('How to Use', 'contact-form-whatsapp'); ?></h2>
                <ol>
                    <li><?php _e('Configure your Wassenger API token above', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Create a form using the Form Builder', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Add the form to your page using the shortcode', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('View submissions in the Submissions page', 'contact-form-whatsapp'); ?></li>
                </ol>
                
                <h3><?php _e('Required Fields', 'contact-form-whatsapp'); ?></h3>
                <p><?php _e('Every form must have these required fields:', 'contact-form-whatsapp'); ?></p>
                <ul>
                    <li><?php _e('Name (text field)', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Email (email field)', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('WhatsApp Number (whatsapp field)', 'contact-form-whatsapp'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render field form
     */
    private function render_field_form($field = null) {
        $field_types = $this->form_builder->get_field_types();
        ?>
        <table class="form-table">
            <tr>
                <th><label for="field_name"><?php _e('Field Name', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <input type="text" name="field_name" value="<?php echo $field ? esc_attr($field->field_name) : ''; ?>" class="regular-text" required>
                    <p class="description"><?php _e('Used internally (letters, numbers, underscore only)', 'contact-form-whatsapp'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="field_label"><?php _e('Field Label', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <input type="text" name="field_label" value="<?php echo $field ? esc_attr($field->field_label) : ''; ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="field_type"><?php _e('Field Type', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <select name="field_type" required>
                        <?php foreach ($field_types as $type => $info): ?>
                        <option value="<?php echo $type; ?>" <?php echo ($field && $field->field_type === $type) ? 'selected' : ''; ?>>
                            <?php echo $info['label']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="field_placeholder"><?php _e('Placeholder', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <input type="text" name="field_placeholder" value="<?php echo $field ? esc_attr($field->field_placeholder) : ''; ?>" class="regular-text">
                </td>
            </tr>
            <tr class="cfwv-field-options-row" style="display: none;">
                <th><label for="field_options"><?php _e('Options', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <textarea name="field_options" rows="4" class="large-text"><?php echo $field ? esc_textarea($field->field_options) : ''; ?></textarea>
                    <p class="description"><?php _e('For dropdown fields. One option per line. Format: value|label', 'contact-form-whatsapp'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="is_required"><?php _e('Required', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <input type="checkbox" name="is_required" value="1" <?php echo ($field && $field->is_required) ? 'checked' : ''; ?>>
                    <?php _e('This field is required', 'contact-form-whatsapp'); ?>
                </td>
            </tr>
            <tr>
                <th><label for="field_class"><?php _e('CSS Class', 'contact-form-whatsapp'); ?></label></th>
                <td>
                    <input type="text" name="field_class" value="<?php echo $field ? esc_attr($field->field_class) : ''; ?>" class="regular-text">
                    <p class="description"><?php _e('Additional CSS classes for styling', 'contact-form-whatsapp'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_field" class="button button-primary" value="<?php _e('Save Field', 'contact-form-whatsapp'); ?>">
            <button type="button" class="button cfwv-cancel-field"><?php _e('Cancel', 'contact-form-whatsapp'); ?></button>
        </p>
        <?php
    }
    
    // AJAX Handlers
    public function ajax_save_form() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $form_data = array(
            'id' => intval($_POST['form_id']),
            'name' => sanitize_text_field($_POST['form_name']),
            'description' => sanitize_textarea_field($_POST['form_description']),
            'redirect_url' => esc_url_raw($_POST['redirect_url']),
            'status' => sanitize_text_field($_POST['form_status']),
            'form_styles' => json_encode(array(
                'background_color' => sanitize_hex_color($_POST['background_color']),
                'text_color' => sanitize_hex_color($_POST['text_color']),
                'border_color' => sanitize_hex_color($_POST['border_color']),
                'button_color' => sanitize_hex_color($_POST['button_color']),
                'button_text_color' => sanitize_hex_color($_POST['button_text_color'])
            ))
        );
        
        $result = $this->database->save_form($form_data);
        
        if ($result) {
            wp_send_json_success(array('form_id' => $result));
        } else {
            wp_send_json_error(__('Failed to save form', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_delete_form() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $form_id = intval($_POST['form_id']);
        $result = $this->database->delete_form($form_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete form', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_save_field() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $field_data = array(
            'id' => intval($_POST['field_id']),
            'form_id' => intval($_POST['form_id']),
            'field_name' => sanitize_text_field($_POST['field_name']),
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_type' => sanitize_text_field($_POST['field_type']),
            'field_options' => sanitize_textarea_field($_POST['field_options']),
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'field_order' => intval($_POST['field_order']),
            'field_placeholder' => sanitize_text_field($_POST['field_placeholder']),
            'field_class' => sanitize_text_field($_POST['field_class'])
        );
        
        // Validate field data
        $errors = $this->form_builder->validate_field_data($field_data);
        
        if (!empty($errors)) {
            wp_send_json_error(implode(', ', $errors));
        }
        
        $result = $this->database->save_form_field($field_data);
        
        if ($result) {
            wp_send_json_success(array('field_id' => $result));
        } else {
            wp_send_json_error(__('Failed to save field', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_delete_field() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $field_id = intval($_POST['field_id']);
        $result = $this->database->delete_form_field($field_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete field', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_get_field_form() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $field_type = sanitize_text_field($_POST['field_type']);
        $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
        $form_id = intval($_POST['form_id']);
        
        // Get field data if editing
        $field = null;
        if ($field_id > 0) {
            $fields = $this->database->get_form_fields($form_id);
            foreach ($fields as $f) {
                if ($f->id == $field_id) {
                    $field = $f;
                    break;
                }
            }
        }
        
        // Generate field form HTML
        ob_start();
        ?>
        <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" name="field_order" value="<?php echo $field ? $field->field_order : 999; ?>">
        
        <?php $this->render_field_form($field); ?>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    public function ajax_update_field_order() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $order = $_POST['order'];
        
        if (is_array($order)) {
            foreach ($order as $item) {
                $field_id = intval($item['id']);
                $field_order = intval($item['order']);
                
                global $wpdb;
                $table = $wpdb->prefix . 'cfwv_form_fields';
                
                $wpdb->update(
                    $table,
                    array('field_order' => $field_order),
                    array('id' => $field_id),
                    array('%d'),
                    array('%d')
                );
            }
            
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Invalid order data', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_get_submission() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $submission_id = intval($_POST['submission_id']);
        $submission = $this->database->get_submission($submission_id);
        
        if (!$submission) {
            wp_send_json_error(__('Submission not found', 'contact-form-whatsapp'));
        }
        
        // Get form details
        $form = $this->database->get_form($submission->form_id);
        
        // Generate HTML for submission details
        $html = '<div class="cfwv-submission-details">';
        $html .= '<h3>' . __('Submission Details', 'contact-form-whatsapp') . '</h3>';
        
        $html .= '<table class="cfwv-table">';
        $html .= '<tr><th>' . __('Submission ID', 'contact-form-whatsapp') . '</th><td>' . $submission->id . '</td></tr>';
        $html .= '<tr><th>' . __('Form', 'contact-form-whatsapp') . '</th><td>' . esc_html($form ? $form->name : 'Unknown') . '</td></tr>';
        $html .= '<tr><th>' . __('Submitted Date', 'contact-form-whatsapp') . '</th><td>' . date('Y-m-d H:i:s', strtotime($submission->submitted_at)) . '</td></tr>';
        $html .= '<tr><th>' . __('WhatsApp Number', 'contact-form-whatsapp') . '</th><td>' . esc_html($submission->whatsapp_number) . '</td></tr>';
        $html .= '<tr><th>' . __('WhatsApp Status', 'contact-form-whatsapp') . '</th><td>';
        if ($submission->whatsapp_validated) {
            $html .= '<span class="cfwv-whatsapp-status valid">' . __('Validated', 'contact-form-whatsapp') . '</span>';
        } else {
            $html .= '<span class="cfwv-whatsapp-status invalid">' . __('Not Validated', 'contact-form-whatsapp') . '</span>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><th>' . __('IP Address', 'contact-form-whatsapp') . '</th><td>' . esc_html($submission->submission_ip) . '</td></tr>';
        $html .= '<tr><th>' . __('User Agent', 'contact-form-whatsapp') . '</th><td>' . esc_html($submission->user_agent) . '</td></tr>';
        $html .= '</table>';
        
        // Display form data
        if (!empty($submission->data)) {
            $html .= '<h4>' . __('Form Data', 'contact-form-whatsapp') . '</h4>';
            $html .= '<table class="cfwv-table">';
            foreach ($submission->data as $field_name => $field_value) {
                $field_label = ucfirst(str_replace('_', ' ', $field_name));
                $html .= '<tr>';
                $html .= '<th>' . esc_html($field_label) . '</th>';
                $html .= '<td>' . esc_html($field_value) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    public function ajax_delete_submission() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $submission_id = intval($_POST['submission_id']);
        $result = $this->database->delete_submission($submission_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete submission', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_export_submissions() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $submissions = $this->database->get_submissions_for_export($form_id);
        
        if (empty($submissions)) {
            wp_send_json_error(__('No submissions to export', 'contact-form-whatsapp'));
        }
        
        // Generate CSV
        $csv_data = array();
        $headers = array('Date', 'Form', 'WhatsApp Number', 'WhatsApp Validated', 'IP Address');
        
        // Get all possible field names
        $field_names = array();
        foreach ($submissions as $submission) {
            $field_names = array_merge($field_names, array_keys($submission->data));
        }
        $field_names = array_unique($field_names);
        $headers = array_merge($headers, $field_names);
        
        $csv_data[] = $headers;
        
        foreach ($submissions as $submission) {
            $row = array(
                $submission->submitted_at,
                $submission->form_name,
                $submission->whatsapp_number,
                $submission->whatsapp_validated ? 'Yes' : 'No',
                $submission->submission_ip
            );
            
            foreach ($field_names as $field_name) {
                $row[] = isset($submission->data[$field_name]) ? $submission->data[$field_name] : '';
            }
            
            $csv_data[] = $row;
        }
        
        // Create CSV file
        $filename = 'submissions_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        
        exit;
    }
    
    public function ajax_test_api() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        $result = $this->whatsapp_validator->test_api_connection();
        wp_send_json($result);
    }
} 