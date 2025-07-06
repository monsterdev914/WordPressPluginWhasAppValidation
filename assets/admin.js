jQuery(document).ready(function($) {
    'use strict';
    
    // Form Builder functionality
    var FormBuilder = {
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initColorPickers();
        },
        
        bindEvents: function() {
            // Add field buttons
            $(document).on('click', '.cfwv-add-field', this.addField);
            
            // Edit field buttons
            $(document).on('click', '.cfwv-edit-field', this.editField);
            
            // Delete field buttons
            $(document).on('click', '.cfwv-delete-field', this.deleteField);
            
            // Save field form
            $(document).on('click', '.cfwv-save-field', this.saveField);
            
            // Cancel field form
            $(document).on('click', '.cfwv-cancel-field', this.cancelField);
            
            // Field type change
            $(document).on('change', 'select[name="field_type"]', this.fieldTypeChange);
            
            // Save form
            $(document).on('submit', '#cfwv-form-builder', this.saveForm);
            
            // Copy shortcode
            $(document).on('click', '.cfwv-copy-shortcode', this.copyShortcode);
            
            // Modal close
            $(document).on('click', '.cfwv-modal-close', this.closeModal);
            
            // Test API
            $(document).on('click', '#cfwv-test-api', this.testAPI);
        },
        
        initSortable: function() {
            if ($.fn.sortable) {
                $('.cfwv-fields-list').sortable({
                    handle: '.cfwv-field-drag',
                    placeholder: 'cfwv-field-placeholder',
                    update: function(event, ui) {
                        FormBuilder.updateFieldOrder();
                    }
                });
            }
        },
        
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('input[type="color"]').wpColorPicker();
            }
        },
        
        addField: function(e) {
            e.preventDefault();
            
            var fieldType = $(this).data('type');
            var formId = $('input[name="form_id"]').val();
            
            if (!formId) {
                alert('Please save the form first before adding fields.');
                return;
            }
            
            FormBuilder.openFieldModal(fieldType, 0, formId);
        },
        
        editField: function(e) {
            e.preventDefault();
            
            var fieldItem = $(this).closest('.cfwv-field-item');
            var fieldId = fieldItem.data('field-id');
            var formId = $('input[name="form_id"]').val();
            
            // Show field details
            fieldItem.find('.cfwv-field-details').slideToggle();
        },
        
        deleteField: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this field?')) {
                return;
            }
            
            var fieldItem = $(this).closest('.cfwv-field-item');
            var fieldId = fieldItem.data('field-id');
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_delete_field',
                    field_id: fieldId,
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        fieldItem.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error deleting field. Please try again.');
                }
            });
        },
        
        saveField: function(e) {
            e.preventDefault();
            
            var form = $(this).closest('form');
            var formData = form.serialize();
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=cfwv_save_field&nonce=' + cfwv_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error saving field. Please try again.');
                }
            });
        },
        
        cancelField: function(e) {
            e.preventDefault();
            
            var fieldItem = $(this).closest('.cfwv-field-item');
            fieldItem.find('.cfwv-field-details').slideUp();
        },
        
        fieldTypeChange: function() {
            var fieldType = $(this).val();
            var optionsRow = $(this).closest('table').find('.cfwv-field-options-row');
            
            if (fieldType === 'select') {
                optionsRow.show();
            } else {
                optionsRow.hide();
            }
        },
        
        saveForm: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = form.serialize();
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=cfwv_save_form&nonce=' + cfwv_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        if (response.data.form_id && !$('input[name="form_id"]').val()) {
                            $('input[name="form_id"]').val(response.data.form_id);
                            
                            // Update URL to include form_id
                            var url = new URL(window.location);
                            url.searchParams.set('form_id', response.data.form_id);
                            window.history.replaceState({}, '', url);
                        }
                        
                        FormBuilder.showNotice('Form saved successfully!', 'success');
                    } else {
                        FormBuilder.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    FormBuilder.showNotice('Error saving form. Please try again.', 'error');
                }
            });
        },
        
        copyShortcode: function(e) {
            e.preventDefault();
            
            var shortcode = $(this).data('shortcode');
            var textArea = document.createElement('textarea');
            textArea.value = shortcode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            $(this).text('Copied!');
            setTimeout(function() {
                $(e.target).text('Copy');
            }, 2000);
        },
        
        openFieldModal: function(fieldType, fieldId, formId) {
            var modal = $('#cfwv-field-modal');
            var title = fieldId ? 'Edit Field' : 'Add Field';
            
            modal.find('#cfwv-modal-title').text(title);
            modal.find('#cfwv-field-form-content').html('<p>Loading...</p>');
            modal.show();
            
            // Load field form via AJAX
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_get_field_form',
                    field_type: fieldType,
                    field_id: fieldId,
                    form_id: formId,
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modal.find('#cfwv-field-form-content').html(response.data);
                        FormBuilder.fieldTypeChange.call(modal.find('select[name="field_type"]'));
                    } else {
                        modal.find('#cfwv-field-form-content').html('<p>Error loading field form</p>');
                    }
                },
                error: function() {
                    modal.find('#cfwv-field-form-content').html('<p>Error loading field form</p>');
                }
            });
        },
        
        closeModal: function() {
            $('.cfwv-modal').hide();
        },
        
        updateFieldOrder: function() {
            var order = [];
            $('.cfwv-field-item').each(function(index) {
                order.push({
                    id: $(this).data('field-id'),
                    order: index + 1
                });
            });
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_update_field_order',
                    order: order,
                    nonce: cfwv_ajax.nonce
                }
            });
        },
        
        testAPI: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var resultDiv = $('#cfwv-api-test-result');
            
            button.prop('disabled', true).text('Testing...');
            resultDiv.html('<p>Testing API connection...</p>');
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_test_api',
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    var className = response.success ? 'notice-success' : 'notice-error';
                    resultDiv.html('<div class="notice ' + className + '"><p>' + response.message + '</p></div>');
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p>Error testing API connection</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test API Connection');
                }
            });
        },
        
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            setTimeout(function() {
                notice.fadeOut();
            }, 5000);
        }
    };
    
    // Submissions functionality
    var Submissions = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // View submission
            $(document).on('click', '.cfwv-view-submission', this.viewSubmission);
            
            // Delete submission
            $(document).on('click', '.cfwv-delete-submission', this.deleteSubmission);
            
            // Export submissions
            $(document).on('click', '.cfwv-export-submissions', this.exportSubmissions);
            
            // Modal close
            $(document).on('click', '.cfwv-modal-close', this.closeModal);
        },
        
        viewSubmission: function(e) {
            e.preventDefault();
            
            var submissionId = $(this).data('id');
            var modal = $('#cfwv-submission-modal');
            
            modal.find('#cfwv-submission-details').html('<p>Loading...</p>');
            modal.show();
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_get_submission',
                    submission_id: submissionId,
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modal.find('#cfwv-submission-details').html(response.data);
                    } else {
                        modal.find('#cfwv-submission-details').html('<p>Error loading submission</p>');
                    }
                },
                error: function() {
                    modal.find('#cfwv-submission-details').html('<p>Error loading submission</p>');
                }
            });
        },
        
        deleteSubmission: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this submission?')) {
                return;
            }
            
            var submissionId = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_delete_submission',
                    submission_id: submissionId,
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error deleting submission. Please try again.');
                }
            });
        },
        
        exportSubmissions: function(e) {
            e.preventDefault();
            
            var formId = $(this).data('form-id') || 0;
            var url = cfwv_ajax.ajax_url + '?action=cfwv_export_submissions&form_id=' + formId + '&nonce=' + cfwv_ajax.nonce;
            
            window.open(url, '_blank');
        },
        
        closeModal: function() {
            $('.cfwv-modal').hide();
        }
    };
    
    // Initialize based on current page
    if ($('.cfwv-form-builder').length) {
        FormBuilder.init();
    }
    
    if ($('.cfwv-submissions').length) {
        Submissions.init();
    }
    
    // General admin functionality
    FormBuilder.init();
    Submissions.init();
    
    // Form preview functionality
    $(document).on('click', '.cfwv-preview-form', function(e) {
        e.preventDefault();
        
        var formId = $(this).data('form-id');
        var previewUrl = $(this).data('preview-url') || (window.location.origin + '/?cfwv_preview=' + formId);
        
        window.open(previewUrl, '_blank');
    });
    
    // Auto-save functionality for form builder
    var autoSaveTimeout;
    $(document).on('input', '#cfwv-form-builder input, #cfwv-form-builder select, #cfwv-form-builder textarea', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            if ($('input[name="form_id"]').val()) {
                $('#cfwv-form-builder').trigger('submit');
            }
        }, 2000);
    });
    
    // Form validation
    $(document).on('submit', '#cfwv-form-builder', function(e) {
        var formName = $('input[name="form_name"]').val();
        if (!formName.trim()) {
            e.preventDefault();
            alert('Please enter a form name.');
            $('input[name="form_name"]').focus();
            return false;
        }
        
        var apiToken = $('input[name="wassenger_api_token"]').val();
        if (!apiToken && $('.cfwv-settings-page').length) {
            e.preventDefault();
            alert('Please enter your Wassenger API token.');
            $('input[name="wassenger_api_token"]').focus();
            return false;
        }
    });
    
    // Real-time form styling preview
    $(document).on('change', 'input[type="color"]', function() {
        var property = $(this).attr('name');
        var value = $(this).val();
        
        // Update preview if exists
        if ($('.cfwv-form-preview').length) {
            var cssProperty = property.replace('_', '-');
            $('.cfwv-form-preview').css(cssProperty, value);
        }
    });
    
    // Tooltips
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }
    
    // Confirm dialogs
    $(document).on('click', '.cfwv-confirm', function(e) {
        var message = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Tab functionality
    $(document).on('click', '.cfwv-tab', function(e) {
        e.preventDefault();
        
        var target = $(this).data('target');
        
        // Update active tab
        $('.cfwv-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        $('.cfwv-tab-content').hide();
        $(target).show();
    });
    
    // Search functionality
    $(document).on('input', '.cfwv-search', function() {
        var query = $(this).val().toLowerCase();
        var target = $(this).data('target');
        
        $(target).each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    });
    
    // Loading states
    $(document).on('click', '.cfwv-loading-btn', function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Loading...');
        
        setTimeout(function() {
            button.prop('disabled', false).text(originalText);
        }, 3000);
    });
}); 