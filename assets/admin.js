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
            // Unbind existing events to prevent multiple bindings
            $(document).off('click', '.cfwv-add-field');
            $(document).off('click', '.cfwv-edit-field');
            $(document).off('click', '.cfwv-delete-field');
            $(document).off('click', '.cfwv-delete-form');
            $(document).off('click', '.cfwv-save-field');
            $(document).off('click', '.cfwv-cancel-field');
            $(document).off('click', '#cfwv-add-phone');
            $(document).off('click', '.cfwv-remove-phone');
            $(document).off('change', '.cfwv-country-field');
            
            // Add field buttons
            $(document).on('click', '.cfwv-add-field', this.addField);
            
            // Edit field buttons
            $(document).on('click', '.cfwv-edit-field', this.editField);
            
            // Delete field buttons
            $(document).on('click', '.cfwv-delete-field', this.deleteField);
            
            // Delete form buttons
            $(document).on('click', '.cfwv-delete-form', this.deleteForm);
            
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
            
            // Admin phone management
            $(document).on('click', '#cfwv-add-phone', this.addPhoneNumber);
            $(document).on('click', '.cfwv-remove-phone', this.removePhoneNumber);
            
            // Country selection change
            $(document).on('change', '.cfwv-country-field', this.countrySelectionChange);
            
            // Test API
            $(document).on('click', '#cfwv-test-api', this.testAPI);
            
            // Initialize Tables
            $(document).on('click', '#cfwv-initialize-tables', this.initializeTables);
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
            
            // Check if form ID is valid (not empty, 0, or null)
            if (!formId || formId === '0' || formId === 0) {
                alert('Please save the form first before adding fields.');
                return;
            }
            
            FormBuilder.openFieldModal(fieldType, 0, formId);
        },
        
        editField: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var button = $(this);
            var fieldItem = button.closest('.cfwv-field-item');
            var fieldDetails = fieldItem.find('.cfwv-field-details');
            var fieldId = fieldItem.data('field-id');
            var formId = $('input[name="form_id"]').val();
            
            // Close all other open field details first
            $('.cfwv-field-details').not(fieldDetails).slideUp();
            
            // Toggle current field details
            fieldDetails.slideToggle(300, function() {
                // Update button text based on state
                if (fieldDetails.is(':visible')) {
                    button.text('Hide Details');
                } else {
                    button.text('Edit Field');
                }
            });
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
        
        deleteForm: function(e) {
            e.preventDefault();
            
            var formId = $(this).data('form-id');
            var formName = $(this).data('form-name');
            
            if (!formId) {
                alert('Error: No form ID found');
                return;
            }
            
            if (!confirm('Are you sure you want to delete the form "' + formName + '"? This will also delete all associated fields and submissions.')) {
                return;
            }
            
            var button = $(this);
            var originalText = button.text();
            button.text('Deleting...').prop('disabled', true);
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_delete_form',
                    form_id: formId,
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                        button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting form. Please try again.');
                    button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        saveField: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var form = button.closest('form');
            var formData = form.serialize();
            var originalText = button.text();
            
            // Prevent multiple simultaneous saves
            if (button.data('saving')) {
                return;
            }
            
            // Show loading state
            button.prop('disabled', true).text('Saving...').data('saving', true);
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=cfwv_save_field&nonce=' + cfwv_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        // Close the modal
                        $('.cfwv-modal').hide();
                        
                        // Show success message
                        FormBuilder.showNotice('Field saved successfully!', 'success');
                        
                        // Reload the page after a short delay to show the new field
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + response.data);
                        button.prop('disabled', false).text(originalText).data('saving', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error saving field. Please try again.');
                    button.prop('disabled', false).text(originalText).data('saving', false);
                }
            });
        },
        
        cancelField: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var modal = button.closest('.cfwv-modal');
            var fieldItem = button.closest('.cfwv-field-item');
            
            // If we're in a modal context, close the modal
            if (modal.length > 0) {
                modal.hide();
                return;
            }
            
            // If we're in an inline edit context, hide field details
            if (fieldItem.length > 0) {
                var editButton = fieldItem.find('.cfwv-edit-field');
                
                // Hide field details
                fieldItem.find('.cfwv-field-details').slideUp(300, function() {
                    // Reset edit button text
                    editButton.text('Edit Field');
                });
            }
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
            e.stopPropagation();
            
            var form = $(this);
            var formData = form.serialize();
            var submitButton = form.find('input[type="submit"]');
            var originalText = submitButton.val();
            
            // Prevent multiple simultaneous saves
            if (form.data('saving')) {
                return false;
            }
            
            // Show loading state
            submitButton.prop('disabled', true).val('Saving...').addClass('saving');
            form.data('saving', true);
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=cfwv_save_form&nonce=' + cfwv_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        var currentFormId = $('input[name="form_id"]').val();
                        
                        // Check if this is a new form (form_id is 0 or empty) and we got a new form_id
                        if (response.data.form_id && (!currentFormId || currentFormId === '0')) {
                            $('input[name="form_id"]').val(response.data.form_id);
                            
                            // Update URL to include form_id without refreshing
                            var url = new URL(window.location);
                            url.searchParams.set('form_id', response.data.form_id);
                            window.history.replaceState({}, '', url);
                            
                            // Update page title to show edit mode
                            $('h1').text('Edit Form');
                        }
                        
                        FormBuilder.showNotice('Form saved successfully!', 'success');
                    } else {
                        FormBuilder.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    FormBuilder.showNotice('Error saving form. Please try again.', 'error');
                },
                complete: function() {
                    // Reset button state
                    submitButton.prop('disabled', false).val(originalText).removeClass('saving');
                    form.data('saving', false);
                }
            });
            
            return false;
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
                        console.error('AJAX Error:', response.data);
                        modal.find('#cfwv-field-form-content').html('<p>Error loading field form: ' + response.data + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Request failed:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    modal.find('#cfwv-field-form-content').html('<p>Error loading field form. Please check the console for details.</p>');
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
        
        initializeTables: function(e) {
            e.preventDefault();
            
            // Confirm action
            if (!confirm('⚠️ WARNING: This will DROP all existing plugin tables and recreate them EMPTY!\n\nAll your forms, fields, and submissions will be permanently deleted.\n\nAre you absolutely sure you want to continue?')) {
                return;
            }
            
            var button = $(this);
            var resultDiv = $('#cfwv-initialize-result');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Initializing...');
            resultDiv.html('<p>Initializing database tables...</p>');
            
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_initialize_tables',
                    nonce: cfwv_ajax.nonce
                },
                success: function(response) {
                    var className = response.success ? 'notice-success' : 'notice-error';
                    resultDiv.html('<div class="notice ' + className + '"><p>' + response.data + '</p></div>');
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p>Error initializing tables. Please try again.</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
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
        },
        
        // Admin phone number management
        addPhoneNumber: function(e) {
            e.preventDefault();
            
            var container = $('#cfwv-admin-phones-container');
            var newRow = $('<div class="cfwv-admin-phone-row" style="margin-bottom: 5px;">' +
                '<input type="text" name="admin_phone_numbers[]" placeholder="+1234567890" class="regular-text" />' +
                '<button type="button" class="button cfwv-remove-phone" style="margin-left: 5px;">Remove</button>' +
                '</div>');
            
            container.append(newRow);
            newRow.find('input').focus();
        },
        
        removePhoneNumber: function(e) {
            e.preventDefault();
            
            var container = $('#cfwv-admin-phones-container');
            var phoneRows = container.find('.cfwv-admin-phone-row');
            
            // Keep at least one phone number field
            if (phoneRows.length > 1) {
                $(this).closest('.cfwv-admin-phone-row').remove();
            } else {
                // If only one left, just clear the value
                $(this).closest('.cfwv-admin-phone-row').find('input').val('');
            }
        },
        
        // Country selection change handler
        countrySelectionChange: function(e) {
            var selectedOption = $(this).find('option:selected');
            var countryCode = selectedOption.data('country-code');
            var countryName = selectedOption.text();
            var infoDiv = $(this).siblings('.cfwv-country-info');
            
            if (countryCode && countryName) {
                infoDiv.html('<strong>Country Code:</strong> ' + countryCode).show();
                
                // Auto-fill any WhatsApp fields in the same form with the country code
                var form = $(this).closest('form');
                var whatsappFields = form.find('.cfwv-whatsapp-field');
                
                whatsappFields.each(function() {
                    var currentVal = $(this).val();
                    // Only auto-fill if the field is empty or doesn't already have a country code
                    if (!currentVal || (!currentVal.startsWith('+') && !currentVal.match(/^\d+$/))) {
                        $(this).attr('placeholder', countryCode + '1234567890');
                        $(this).val(countryCode);
                        $(this).focus();
                        // Move cursor to end
                        var input = this;
                        setTimeout(function() {
                            input.setSelectionRange(input.value.length, input.value.length);
                        }, 10);
                    }
                });
            } else {
                infoDiv.hide();
            }
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
    } else {
        // Initialize only the delete form functionality for dashboard and other pages
        $(document).on('click', '.cfwv-delete-form', FormBuilder.deleteForm);
        $(document).on('click', '.cfwv-copy-shortcode', FormBuilder.copyShortcode);
        
        // Bind admin functions for settings and other pages
        $(document).on('click', '#cfwv-test-api', FormBuilder.testAPI);
        $(document).on('click', '#cfwv-initialize-tables', FormBuilder.initializeTables);
    }
    
    if ($('.cfwv-submissions').length) {
        Submissions.init();
    }
    
    // Form preview functionality
    $(document).on('click', '.cfwv-preview-form', function(e) {
        e.preventDefault();
        
        var formId = $(this).data('form-id');
        var previewUrl = $(this).data('preview-url') || (window.location.origin + '/?cfwv_preview=' + formId);
        
        window.open(previewUrl, '_blank');
    });
    

    
    // Form validation
    $(document).on('submit', '#cfwv-form-builder', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var form = $(this);
        var formName = $('input[name="form_name"]').val();
        
        // Validate form name
        if (!formName.trim()) {
            alert('Please enter a form name.');
            $('input[name="form_name"]').focus();
            return false;
        }
        
        // Check if on settings page and validate API token
        var apiToken = $('input[name="wassenger_api_token"]').val();
        if (!apiToken && $('.cfwv-settings-page').length) {
            alert('Please enter your Wassenger API token.');
            $('input[name="wassenger_api_token"]').focus();
            return false;
        }
        
        // If validation passes, call the saveForm function
        FormBuilder.saveForm.call(this, e);
        
        return false;
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
    
    // Initialize Tables button - standalone implementation
    $(document).on('click', '#cfwv-initialize-tables', function(e) {
        e.preventDefault();
        
        // Confirm action
        if (!confirm('⚠️ WARNING: This will DROP all existing plugin tables and recreate them EMPTY!\n\nAll your forms, fields, and submissions will be permanently deleted.\n\nAre you absolutely sure you want to continue?')) {
            return;
        }
        
        var button = $(this);
        var resultDiv = $('#cfwv-initialize-result');
        var originalText = button.text();
        
        button.prop('disabled', true).text('Initializing...');
        resultDiv.html('<p>Initializing database tables...</p>');
        
        $.ajax({
            url: cfwv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cfwv_initialize_tables',
                nonce: cfwv_ajax.nonce
            },
            success: function(response) {
                var className = response.success ? 'notice-success' : 'notice-error';
                resultDiv.html('<div class="notice ' + className + '"><p>' + response.data + '</p></div>');
            },
            error: function() {
                resultDiv.html('<div class="notice notice-error"><p>Error initializing tables. Please try again.</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Log Management
    $(document).on('click', '#cfwv-refresh-logs', function() {
        location.reload();
    });
    
    $(document).on('click', '#cfwv-clear-logs', function() {
        if (!confirm('Are you sure you want to clear all background process logs? This action cannot be undone.')) {
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true).text('🔄 Clearing...');
        
        $.ajax({
            url: cfwv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cfwv_clear_logs',
                nonce: cfwv_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Refresh the page to show cleared logs
                    location.reload();
                } else {
                    alert('Failed to clear logs: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error occurred while clearing logs');
            },
            complete: function() {
                $button.prop('disabled', false).text('🗑️ Clear Logs');
            }
        });
    });
    
}); 