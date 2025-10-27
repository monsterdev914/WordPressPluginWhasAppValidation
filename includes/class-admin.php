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
        add_action('wp_ajax_cfwv_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_cfwv_add_wassenger_account', array($this, 'ajax_add_wassenger_account'));
        add_action('wp_ajax_cfwv_delete_wassenger_account', array($this, 'ajax_delete_wassenger_account'));
        add_action('wp_ajax_cfwv_get_wassenger_accounts', array($this, 'ajax_get_wassenger_accounts'));
        add_action('wp_ajax_cfwv_migrate_legacy', array($this, 'ajax_migrate_legacy'));
        add_action('wp_ajax_cfwv_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_cfwv_update_database', array($this, 'ajax_update_database'));
        add_action('wp_ajax_nopriv_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_cfwv_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_cfwv_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_cfwv_upload_file', array($this, 'ajax_upload_file'));
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
                                            <button type="button" class="button button-small cfwv-delete-form" data-form-id="<?php echo $form->id; ?>" data-form-name="<?php echo esc_attr($form->name); ?>">
                                                <?php _e('Delete', 'contact-form-whatsapp'); ?>
                                            </button>
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
                                    <br>
                                    <span class="cfwv-otp-status <?php echo $submission->otp_verified ? 'verified' : 'pending'; ?>">
                                        <?php echo $submission->otp_verified ? __('OTP Verified', 'contact-form-whatsapp') : __('OTP Pending', 'contact-form-whatsapp'); ?>
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
                            <th><?php _e('WhatsApp Status', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('OTP Status', 'contact-form-whatsapp'); ?></th>
                            <th><?php _e('Sent By', 'contact-form-whatsapp'); ?></th>
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
                                <span class="cfwv-otp-status <?php echo $submission->otp_verified ? 'verified' : 'pending'; ?>">
                                    <?php if ($submission->otp_verified): ?>
                                        <?php _e('Verified', 'contact-form-whatsapp'); ?>
                                        <?php if ($submission->otp_verified_at): ?>
                                            <br><small><?php echo date('Y-m-d H:i', strtotime($submission->otp_verified_at)); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php _e('Pending', 'contact-form-whatsapp'); ?>
                                        <?php if ($submission->otp_sent_at): ?>
                                            <br><small><?php echo date('Y-m-d H:i', strtotime($submission->otp_sent_at)); ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($submission->sender)): ?>
                                    <strong><?php echo esc_html($submission->sender); ?></strong>
                                <?php else: ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
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
        ?>
        <div class="wrap">
            <h1><?php _e('Contact Form WhatsApp Settings', 'contact-form-whatsapp'); ?></h1>
            
                <div class="cfwv-settings-info">
                    <h2><?php _e('General Settings', 'contact-form-whatsapp'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="default_dashboard_url"><?php _e('Default Dashboard URL', 'contact-form-whatsapp'); ?></label></th>
                            <td>
                                <input type="url" id="default_dashboard_url" name="default_dashboard_url" 
                                       value="<?php echo esc_attr(get_option('cfwv_default_dashboard_url', '')); ?>" 
                                       class="regular-text" placeholder="https://yoursite.com/dashboard">
                                <p class="description"><?php _e('Default URL to redirect users after successful OTP verification. Can be overridden per form.', 'contact-form-whatsapp'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="cfwv-save-settings" class="button button-primary"><?php _e('Save Settings', 'contact-form-whatsapp'); ?></button>
                    </p>
                    
                    <hr>
                    
                    <h2><?php _e('Wassenger Configuration', 'contact-form-whatsapp'); ?></h2>
                    <p><?php _e('Configure your Wassenger accounts below. You can add multiple accounts for better load distribution and failover.', 'contact-form-whatsapp'); ?></p>
                
                <div class="cfwv-test-connection">
                    <button type="button" id="cfwv-test-api" class="button"><?php _e('Test API Connection', 'contact-form-whatsapp'); ?></button>
                    <button type="button" id="cfwv-migrate-legacy" class="button button-secondary"><?php _e('Migrate Legacy Settings', 'contact-form-whatsapp'); ?></button>
                    <button type="button" id="cfwv-update-database" class="button button-secondary"><?php _e('Update Database', 'contact-form-whatsapp'); ?></button>
                    <div id="cfwv-api-test-result"></div>
                </div>
            </div>
            
            <hr>
            
            <!-- Wassenger Accounts Management -->
            <div class="cfwv-wassenger-accounts">
                <h2><?php _e('Wassenger Accounts Management', 'contact-form-whatsapp'); ?></h2>
                <p><?php _e('Manage multiple Wassenger accounts for better load distribution and failover.', 'contact-form-whatsapp'); ?></p>
                
                <div class="cfwv-accounts-section">
                    <h3><?php _e('Add New Account', 'contact-form-whatsapp'); ?></h3>
                    <form id="cfwv-add-account-form">
                        <?php wp_nonce_field('cfwv_nonce', 'nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="account_name"><?php _e('Account Name', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="text" id="account_name" name="account_name" class="regular-text" required>
                                    <p class="description"><?php _e('A friendly name for this account', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="api_token"><?php _e('API Token', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="text" id="api_token" name="api_token" class="regular-text" required>
                                    <p class="description"><?php _e('Wassenger API token for this account', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="number_id"><?php _e('Number ID', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="text" id="number_id" name="number_id" class="regular-text" required>
                                    <p class="description"><?php _e('Wassenger Number ID for this account', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="whatsapp_number"><?php _e('WhatsApp Number', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="text" id="whatsapp_number" name="whatsapp_number" class="regular-text" placeholder="+1234567890">
                                    <p class="description"><?php _e('The WhatsApp number connected to this Wassenger account', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="daily_limit"><?php _e('Daily Limit', 'contact-form-whatsapp'); ?></label></th>
                                <td>
                                    <input type="number" id="daily_limit" name="daily_limit" value="1000" min="1" class="small-text">
                                    <p class="description"><?php _e('Maximum messages per day for this account', 'contact-form-whatsapp'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Add Account', 'contact-form-whatsapp'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div class="cfwv-accounts-list">
                    <h3><?php _e('Existing Accounts', 'contact-form-whatsapp'); ?></h3>
                    <div id="cfwv-accounts-table">
                        <?php $this->render_wassenger_accounts_table(); ?>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="cfwv-database-tools">
                <h2><?php _e('Database Tools', 'contact-form-whatsapp'); ?></h2>
                <p><?php _e('Use this button to reset all plugin database tables to empty state and create a fresh default form.', 'contact-form-whatsapp'); ?></p>
                <p class="description">
                    <strong style="color: #d63638;"><?php _e('⚠️ WARNING:', 'contact-form-whatsapp'); ?></strong> 
                    <?php _e('This will DROP all existing tables and recreate them EMPTY. All forms, fields, and submissions will be permanently deleted!', 'contact-form-whatsapp'); ?>
                </p>
                
                <button type="button" id="cfwv-initialize-tables" class="button button-secondary" style="background: #d63638; border-color: #d63638; color: white;">
                    <?php _e('🔄 Reset to Empty Tables', 'contact-form-whatsapp'); ?>
                </button>
                
                <div id="cfwv-initialize-result" style="margin-top: 10px;"></div>
                
                <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #00a0d2;">
                    <h4 style="margin-top: 0;"><?php _e('What this does:', 'contact-form-whatsapp'); ?></h4>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('✅ Drops all existing plugin tables', 'contact-form-whatsapp'); ?></li>
                        <li><?php _e('✅ Creates fresh empty tables', 'contact-form-whatsapp'); ?></li>
                        <li><?php _e('✅ Creates one default contact form with required fields', 'contact-form-whatsapp'); ?></li>
                        <li><?php _e('✅ Gives you a clean slate to start fresh', 'contact-form-whatsapp'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="cfwv-settings-info">
                <h2><?php _e('How to Use', 'contact-form-whatsapp'); ?></h2>
                <ol>
                    <li><?php _e('Add your Wassenger accounts using the form above', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Create a form using the Form Builder', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Add the form to your page using the shortcode', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('View submissions in the Submissions page', 'contact-form-whatsapp'); ?></li>
                </ol>
                
                <h3><?php _e('Wassenger Account Requirements', 'contact-form-whatsapp'); ?></h3>
                <p><?php _e('Each Wassenger account needs these credentials from your Wassenger dashboard:', 'contact-form-whatsapp'); ?></p>
                <ul>
                    <li><strong><?php _e('API Token:', 'contact-form-whatsapp'); ?></strong> <?php _e('Your authentication token for API access', 'contact-form-whatsapp'); ?></li>
                    <li><strong><?php _e('Number ID:', 'contact-form-whatsapp'); ?></strong> <?php _e('The device/number identifier for session sync operations', 'contact-form-whatsapp'); ?></li>
                </ul>
                
                <h3><?php _e('Benefits of Multiple Accounts', 'contact-form-whatsapp'); ?></h3>
                <ul>
                    <li><?php _e('Load balancing across multiple accounts', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Automatic failover if one account reaches its limit', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Better reliability and higher message throughput', 'contact-form-whatsapp'); ?></li>
                </ul>
                
                <h3><?php _e('Required Fields', 'contact-form-whatsapp'); ?></h3>
                <p><?php _e('Every form must have these required fields:', 'contact-form-whatsapp'); ?></p>
                <ul>
                    <li><?php _e('Name (text field)', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('Email (email field)', 'contact-form-whatsapp'); ?></li>
                    <li><?php _e('WhatsApp Number (whatsapp field)', 'contact-form-whatsapp'); ?></li>
                </ul>
            </div>
            
            <!-- Background Process Logs Section -->
            <div class="cfwv-logs-section" style="margin-top: 20px;">
                <h2><?php _e('📋 Background Process Logs', 'contact-form-whatsapp'); ?></h2>
                <p><?php _e('Monitor WhatsApp API health checks and background processes', 'contact-form-whatsapp'); ?></p>
                
                <?php $this->render_background_logs(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Wassenger accounts table
     */
    private function render_wassenger_accounts_table() {
        $accounts = $this->database->get_wassenger_accounts();
        
        if (empty($accounts)) {
            echo '<p><em>' . __('No Wassenger accounts found. Add your first account above.', 'contact-form-whatsapp') . '</em></p>';
            return;
        }
        
        // Add reset button
        echo '<div class="cfwv-accounts-actions" style="margin-bottom: 15px;">';
        echo '<button type="button" class="button cfwv-reset-session-messages">';
        echo __('Reset Session Messages', 'contact-form-whatsapp');
        echo '</button>';
        echo '<span class="cfwv-reset-info" style="margin-left: 10px; color: #666; font-size: 13px;">';
        echo __('Round-robin switches accounts after 5 messages', 'contact-form-whatsapp');
        echo '</span>';
        echo '</div>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Account Name', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('API Token', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('Number ID', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('WhatsApp Number', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('Session Messages', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('Daily Usage', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('Status', 'contact-form-whatsapp') . '</th>';
        echo '<th>' . __('Actions', 'contact-form-whatsapp') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($accounts as $account) {
            $usage_percentage = $account->daily_limit > 0 ? round(($account->daily_used / $account->daily_limit) * 100, 1) : 0;
            $status_class = $account->is_active ? 'active' : 'inactive';
            $status_text = $account->is_active ? __('Active', 'contact-form-whatsapp') : __('Inactive', 'contact-form-whatsapp');
            
            // Safely get session_messages with fallback
            $session_messages = isset($account->session_messages) ? $account->session_messages : 0;
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($account->account_name) . '</strong></td>';
            echo '<td>' . esc_html(substr($account->api_token, 0, 20) . '...') . '</td>';
            echo '<td>' . esc_html($account->number_id) . '</td>';
            echo '<td>' . esc_html($account->whatsapp_number ? $account->whatsapp_number : 'N/A') . '</td>';
            echo '<td><span class="cfwv-session-count">' . $session_messages . ' / 5</span></td>';
            echo '<td>' . $account->daily_used . ' / ' . $account->daily_limit . ' (' . $usage_percentage . '%)</td>';
            echo '<td><span class="cfwv-status cfwv-status-' . $status_class . '">' . $status_text . '</span></td>';
            echo '<td>';
            echo '<button type="button" class="button button-small cfwv-delete-account" data-account-id="' . $account->id . '" data-account-name="' . esc_attr($account->account_name) . '">';
            echo __('Delete', 'contact-form-whatsapp');
            echo '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Render background process logs
     */
    private function render_background_logs() {
        // Get logs from database
        $logs = get_option('cfwv_background_logs', array());
        
        if (empty($logs)) {
            echo '<p><em>' . __('No background process logs found. Logs will appear here after the first API health check.', 'contact-form-whatsapp') . '</em></p>';
            return;
        }
        
        // Get cron status
        $next_run = wp_next_scheduled('cfwv_check_api_health');
        $next_run_time = $next_run ? date('Y-m-d H:i:s', $next_run) : __('Not scheduled', 'contact-form-whatsapp');
        
        echo '<div class="cfwv-log-status" style="background: #f1f1f1; padding: 10px; margin-bottom: 10px;">';
        echo '<p><strong>' . __('Next Health Check:', 'contact-form-whatsapp') . '</strong> ' . esc_html($next_run_time) . '</p>';
        echo '<button type="button" class="button" id="cfwv-refresh-logs">🔄 ' . __('Refresh Logs', 'contact-form-whatsapp') . '</button>';
        echo '<button type="button" class="button" id="cfwv-clear-logs" style="margin-left: 10px;">🗑️ ' . __('Clear Logs', 'contact-form-whatsapp') . '</button>';
        echo '</div>';
        
        echo '<div class="cfwv-logs-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; font-family: monospace; font-size: 12px;">';
        
        // Display last 20 logs
        $recent_logs = array_slice($logs, 0, 20);
        foreach ($recent_logs as $log) {
            $timestamp = esc_html($log['timestamp']);
            $message = esc_html($log['message']);
            
            // Color code different types of messages
            $style = '';
            if (strpos($message, 'failed') !== false || strpos($message, 'Error') !== false) {
                $style = 'color: #d63638;'; // Red for errors
            } elseif (strpos($message, 'scheduled') !== false || strpos($message, 'healthy') !== false) {
                $style = 'color: #00a32a;'; // Green for success
            } elseif (strpos($message, 'Warning') !== false || strpos($message, 'High') !== false) {
                $style = 'color: #dba617;'; // Orange for warnings
            }
            
            echo '<div style="margin-bottom: 5px; ' . $style . '">';
            echo '<strong>' . $timestamp . '</strong> - ' . $message;
            echo '</div>';
        }
        
        echo '</div>';
        
        if (count($logs) > 20) {
            echo '<p><em>' . sprintf(__('Showing last 20 entries. Total: %d entries.', 'contact-form-whatsapp'), count($logs)) . '</em></p>';
        }
    }
    
    /**
     * Get country codes for WhatsApp fields
     */
    private function get_country_codes() {
        return array(
            '+1' => array('name' => 'United States', 'code' => 'US'),
            '+1' => array('name' => 'Canada', 'code' => 'CA'),
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
            '+370' => array('name' => 'Lithuania', 'code' => 'LT'),
            '+371' => array('name' => 'Latvia', 'code' => 'LV'),
            '+372' => array('name' => 'Estonia', 'code' => 'EE'),
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
    }

    /**
     * Render field form
     */
    private function render_field_form($field = null, $form_id = null) {
        $field_types = $this->form_builder->get_field_types();
        
        // If form_id is not provided, try to get it from the field or POST data
        if (!$form_id) {
            if ($field && isset($field->form_id)) {
                $form_id = $field->form_id;
            } elseif (isset($_POST['form_id'])) {
                $form_id = intval($_POST['form_id']);
            } else {
                $form_id = 0;
            }
        }
        ?>
        <form id="cfwv-field-form">
            <input type="hidden" name="field_id" value="<?php echo $field ? intval($field->id) : '0'; ?>">
            <input type="hidden" name="form_id" value="<?php echo intval($form_id); ?>">
            <input type="hidden" name="field_order" value="<?php echo $field ? intval($field->field_order) : '999'; ?>">
            
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
                <button type="button" name="save_field" class="button button-primary cfwv-save-field" value="<?php _e('Save Field', 'contact-form-whatsapp'); ?>"><?php _e('Save Field', 'contact-form-whatsapp'); ?></button>
                <button type="button" class="button cfwv-cancel-field"><?php _e('Cancel', 'contact-form-whatsapp'); ?></button>
            </p>
        </form>
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
        
        if (!$form_id) {
            wp_send_json_error(__('No form ID provided', 'contact-form-whatsapp'));
        }
        
        $result = $this->database->delete_form($form_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete form', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_save_field() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        try {
            $field_data = array(
                'id' => intval($_POST['field_id']),
                'form_id' => intval($_POST['form_id']),
                'field_name' => sanitize_text_field($_POST['field_name']),
                'field_label' => sanitize_text_field($_POST['field_label']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'field_options' => sanitize_textarea_field($_POST['field_options']),
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'field_order' => isset($_POST['field_order']) ? intval($_POST['field_order']) : 999,
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
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
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
        
        try {
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
            $this->render_field_form($field, $form_id);
            $html = ob_get_clean();
            
            wp_send_json_success($html);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
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
        $html .= '<tr><th>' . __('OTP Status', 'contact-form-whatsapp') . '</th><td>';
        if ($submission->otp_verified) {
            $html .= '<span class="cfwv-otp-status verified">' . __('Verified', 'contact-form-whatsapp') . '</span>';
            if ($submission->otp_verified_at) {
                $html .= '<br><small>' . __('Verified at: ', 'contact-form-whatsapp') . date('Y-m-d H:i:s', strtotime($submission->otp_verified_at)) . '</small>';
            }
        } else {
            $html .= '<span class="cfwv-otp-status pending">' . __('Pending', 'contact-form-whatsapp') . '</span>';
            if ($submission->otp_sent_at) {
                $html .= '<br><small>' . __('OTP sent at: ', 'contact-form-whatsapp') . date('Y-m-d H:i:s', strtotime($submission->otp_sent_at)) . '</small>';
            }
            if ($submission->otp_attempts > 0) {
                $html .= '<br><small>' . __('Attempts: ', 'contact-form-whatsapp') . $submission->otp_attempts . '</small>';
            }
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
                $html .= '<td>';
                
                // Check if this is a file URL
                if (filter_var($field_value, FILTER_VALIDATE_URL) && $this->is_file_url($field_value)) {
                    $file_name = basename($field_value);
                    $html .= '<a href="' . esc_url($field_value) . '" target="_blank" class="cfwv-file-link">';
                    $html .= '<span class="cfwv-file-icon">📄</span> ' . esc_html($file_name);
                    $html .= '</a>';
                } else {
                    $html .= esc_html($field_value);
                }
                
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Check if URL is a file URL
     */
    private function is_file_url($url) {
        $file_extensions = array('pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'xlsx', 'xls', 'ppt', 'pptx');
        $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
        
        if (isset($path_info['extension'])) {
            return in_array(strtolower($path_info['extension']), $file_extensions);
        }
        
        return false;
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
        $headers = array('Date', 'Form', 'WhatsApp Number', 'WhatsApp Validated', 'OTP Verified', 'OTP Verified At', 'IP Address');
        
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
                $submission->otp_verified ? 'Yes' : 'No',
                $submission->otp_verified_at ? $submission->otp_verified_at : '',
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
    
    public function ajax_clear_logs() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        // Clear the background logs
        delete_option('cfwv_background_logs');
        
        wp_send_json_success(array('message' => __('Logs cleared successfully', 'contact-form-whatsapp')));
    }
    
    public function ajax_add_wassenger_account() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        $account_name = sanitize_text_field($_POST['account_name']);
        $api_token = sanitize_text_field($_POST['api_token']);
        $number_id = sanitize_text_field($_POST['number_id']);
        $whatsapp_number = sanitize_text_field($_POST['whatsapp_number']);
        $daily_limit = intval($_POST['daily_limit']);
        
        if (empty($account_name) || empty($api_token) || empty($number_id)) {
            wp_send_json_error(__('All fields are required.', 'contact-form-whatsapp'));
        }
        
        $result = $this->database->add_wassenger_account($account_name, $api_token, $number_id, $whatsapp_number, $daily_limit);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Wassenger account added successfully.', 'contact-form-whatsapp')));
        } else {
            wp_send_json_error(__('Failed to add Wassenger account.', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_reset_session_messages() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        $this->database->reset_session_messages();
        
        wp_send_json_success(array('message' => __('Session messages reset successfully.', 'contact-form-whatsapp')));
    }
    
    public function ajax_delete_wassenger_account() {

        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        $account_id = intval($_POST['account_id']);
        
        if (!$account_id) {
            wp_send_json_error(__('Invalid account ID.', 'contact-form-whatsapp'));
        }
        
        $result = $this->database->delete_wassenger_account($account_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Wassenger account deleted successfully.', 'contact-form-whatsapp')));
        } else {
            wp_send_json_error(__('Failed to delete Wassenger account.', 'contact-form-whatsapp'));
        }
    }
    
    public function ajax_get_wassenger_accounts() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        // Generate the accounts table HTML
        ob_start();
        $this->render_wassenger_accounts_table();
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    public function ajax_migrate_legacy() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        $result = $this->database->migrate_legacy_options();
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        $default_dashboard_url = sanitize_url($_POST['default_dashboard_url']);
        
        update_option('cfwv_default_dashboard_url', $default_dashboard_url);
        
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!'
        ));
    }
    
    public function ajax_update_database() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'contact-form-whatsapp'));
        }
        
        try {
            // Update existing tables to add new columns
            $this->database->update_existing_tables();
            
            wp_send_json_success(array(
                'message' => 'Database updated successfully!'
            ));
        } catch (Exception $e) {
            wp_send_json_error('Database update failed: ' . $e->getMessage());
        }
    }
    
    public function ajax_upload_file() {
        check_ajax_referer('cfwv_nonce', 'nonce');
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
        }
        
        $file = $_FILES['file'];
        
        // Validate file type
        $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $allowed_extensions = array('.pdf', '.doc', '.docx');
        
        $file_type = $file['type'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) && !in_array('.' . $file_extension, $allowed_extensions)) {
            wp_send_json_error('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            wp_send_json_error('File size too large. Maximum size is 5MB.');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $cfwv_dir = $upload_dir['basedir'] . '/cfwv-uploads';
        
        if (!file_exists($cfwv_dir)) {
            wp_mkdir_p($cfwv_dir);
        }
        
        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $filename = time() . '_' . $filename;
        $file_path = $cfwv_dir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = $upload_dir['baseurl'] . '/cfwv-uploads/' . $filename;
            
            wp_send_json_success(array(
                'file_url' => $file_url,
                'file_name' => $file['name'],
                'file_size' => $file['size']
            ));
        } else {
            wp_send_json_error('Failed to upload file');
        }
    }
    
    public function ajax_submit_form() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cfwv_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        try {
            // Get form data
            $form_id = intval($_POST['form_id']);
            $form_data = array();
            
            // Debug logging
            error_log('CFWV Form Submission Debug - Form ID: ' . $form_id);
            error_log('CFWV Form Submission Debug - POST data: ' . print_r($_POST, true));
            
            // Process regular form fields
            foreach ($_POST as $key => $value) {
                if ($key !== 'action' && $key !== 'nonce' && $key !== 'form_id') {
                    $form_data[$key] = sanitize_text_field($value);
                }
            }
            
            // Process file uploads
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        // Validate file
                        $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                        $allowed_extensions = array('.pdf', '.doc', '.docx');
                        
                        $file_type = $file['type'];
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        if (in_array($file_type, $allowed_types) || in_array('.' . $file_extension, $allowed_extensions)) {
                            // Create upload directory
                            $upload_dir = wp_upload_dir();
                            $cfwv_dir = $upload_dir['basedir'] . '/cfwv-uploads';
                            
                            if (!file_exists($cfwv_dir)) {
                                wp_mkdir_p($cfwv_dir);
                            }
                            
                            // Generate unique filename
                            $filename = sanitize_file_name($file['name']);
                            $filename = time() . '_' . $filename;
                            $file_path = $cfwv_dir . '/' . $filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                                $file_url = $upload_dir['baseurl'] . '/cfwv-uploads/' . $filename;
                                $form_data[$key] = $file_url;
                            }
                        }
                    }
                }
            }
            
            // Add nonce and form_id to form data for frontend processing
            $form_data['nonce'] = $_POST['nonce'];
            $form_data['form_id'] = $form_id;
            
            // Process form submission using frontend class
            $frontend = new CFWV_Frontend();
            $result = $frontend->submit_form($form_data);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Form submission failed: ' . $e->getMessage());
        }
    }
} 