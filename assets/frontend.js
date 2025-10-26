jQuery(document).ready(function ($) {
    'use strict';

    // Contact Form functionality
    var ContactForm = {
        init: function () {
            this.bindEvents();
            this.initValidation();
        },

        bindEvents: function () {
            // Form submission
            $(document).on('submit', '.cfwv-form', this.submitForm);

            // WhatsApp validation removed

            // Country code selection
            $(document).on('change', '.cfwv-country-code-selector', this.handleCountryCodeChange);

            // Real-time validation
            $(document).on('blur', '.cfwv-field', this.validateField);

            // Clear errors on input
            $(document).on('input', '.cfwv-field', this.clearFieldError);

            // File upload handling
            $(document).on('change', '.cfwv-file', this.handleFileUpload);
            $(document).on('click', '.cfwv-file', function () {
                console.log('File input clicked');
            });
            $(document).on('click', '.file-remove', this.removeFile);

            // Mark fields as user-interacted
            $(document).on('focus input keydown', '.cfwv-field', function () {
                var field = $(this);
                field.data('user-interacted', true);

                // Clear the user-interacted flag after 2 seconds of inactivity
                clearTimeout(field.data('interaction-timeout'));
                field.data('interaction-timeout', setTimeout(function () {
                    field.data('user-interacted', false);
                }, 2000));
            });
        },

        initValidation: function () {
            // Initialize any third-party validation libraries if needed
        },

        handleCountryCodeChange: function () {
            var selector = $(this);
            var wrapper = selector.closest('.cfwv-whatsapp-field-wrapper');
            var phoneField = wrapper.find('.cfwv-whatsapp-field');
            var displayElement = wrapper.find('.cfwv-country-code-display');
            var selectedCountryCode = selector.val();
            var selectedOption = selector.find('option:selected');
            var displayText = selectedOption.data('display') || selectedCountryCode;

            // Update the data-country-code attribute
            phoneField.attr('data-country-code', selectedCountryCode);

            // Update the display text to show only the country code
            displayElement.text(displayText);
        },

        submitForm: function (e) {
            e.preventDefault();

            var form = $(this);
            var formData = ContactForm.getFormData(form);

            console.log('Form submission started', formData);
            console.log('Form ID from data attribute:', form.data('form-id'));
            console.log('Form ID from FormData:', formData.get('form_id'));

            // Validate form
            if (!ContactForm.validateForm(form)) {
                console.log('Form validation failed');
                return false;
            }

            console.log('Form validation passed, proceeding with submission');

            // Show loading state
            ContactForm.showLoading(form);

            // Disable submit button
            var submitBtn = form.find('.cfwv-submit-btn');
            submitBtn.prop('disabled', true).text('Submitting...');

            // Submit form via AJAX
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log('Form submission response:', response); // Debug log

                    if (response.success) {
                        if (response.data.otp_verification) {
                            console.log('OTP verification required, redirecting to:', response.verification_url); // Debug log

                            // Show OTP verification message with manual redirect option
                            ContactForm.showOTPSuccess(form, response.data.message, response.data.verification_url);

                            // Redirect to OTP verification page with a short delay to ensure message is shown
                            console.log('Redirecting to verification page...'); // Debug log

                            if (response.data.verification_url) {
                                // Show message for 1 second, then redirect
                                setTimeout(function () {
                                    console.log('Executing redirect to:', response.data.verification_url);

                                    // Try multiple redirect methods
                                    try {
                                        // Method 1: Direct assignment
                                        window.location.href = response.data.verification_url;

                                        // Method 2: Fallback after 500ms
                                        setTimeout(function () {
                                            if (window.location.href.indexOf('cfwv_otp_verify') === -1) {
                                                console.log('Fallback redirect triggered');
                                                window.location.replace(response.data.verification_url);
                                            }
                                        }, 500);

                                        // Method 3: Final fallback after 1 second
                                        setTimeout(function () {
                                            if (window.location.href.indexOf('cfwv_otp_verify') === -1) {
                                                console.log('Final fallback redirect triggered');
                                                document.location.href = response.data.verification_url;
                                            }
                                        }, 1000);

                                    } catch (error) {
                                        console.error('Redirect error:', error);
                                        // Show manual redirect option
                                        ContactForm.showOTPSuccess(form, response.data.message + ' Please click the link below to continue.', response.verification_url);
                                    }
                                }, 1000);
                            } else {
                                console.error('No verification URL provided in response');
                                ContactForm.showError(form, 'No verification URL provided. Please contact support.');
                            }
                        } else {
                            ContactForm.showSuccess(form, response.data.message);

                            // Reset form
                            form[0].reset();

                            // Redirect if URL provided
                            if (response.data.redirect_url) {
                                setTimeout(function () {
                                    window.location.href = response.data.redirect_url;
                                }, 2000);
                            }
                        }
                    } else {
                        var errorMessage = 'An error occurred';
                        if (response.data) {
                            errorMessage = response.data;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        console.error('Form submission failed:', errorMessage);
                        ContactForm.showError(form, errorMessage, response.data ? response.data.errors : null);
                    }
                },
                error: function (xhr, status, error) {
                    ContactForm.showError(form, 'An error occurred. Please try again.');
                },
                complete: function () {
                    ContactForm.hideLoading(form);
                    submitBtn.prop('disabled', false).text('Submit');
                }
            });
        },

        // validateWhatsApp method removed

        // delayedWhatsAppValidation method removed


        validateField: function () {
            var field = $(this);
            var fieldType = field.attr('type') || 'text';
            var fieldName = field.attr('name');
            var fieldValue = field.val();
            var isRequired = field.prop('required');
            var errorDiv = field.siblings('.cfwv-field-error');

            // Clear previous errors
            errorDiv.text('');
            field.removeClass('error');

            // Check required fields only if user has interacted with the field
            if (isRequired && !fieldValue && field.data('user-interacted')) {
                ContactForm.showFieldError(field, 'This field is required');
                return false;
            }

            // Skip validation for empty optional fields
            if (!fieldValue) {
                return true;
            }

            // Type-specific validation
            switch (fieldType) {
                case 'email':
                    if (!ContactForm.isValidEmail(fieldValue)) {
                        ContactForm.showFieldError(field, 'Please enter a valid email address');
                        return false;
                    }
                    break;

                case 'tel':
                    if (!ContactForm.isValidPhone(fieldValue)) {
                        ContactForm.showFieldError(field, 'Please enter a valid phone number');
                        return false;
                    }
                    break;

                case 'url':
                    if (!ContactForm.isValidUrl(fieldValue)) {
                        ContactForm.showFieldError(field, 'Please enter a valid URL');
                        return false;
                    }
                    break;
            }

            // WhatsApp field validation removed

            // Length validation
            if (fieldValue.length > 5000) {
                ContactForm.showFieldError(field, 'Text is too long (maximum 5000 characters)');
                return false;
            }

            return true;
        },

        validateForm: function (form) {
            var isValid = true;
            var firstErrorField = null;

            console.log('Starting form validation');

            // Validate all fields
            form.find('.cfwv-field').each(function () {
                var fieldValid = ContactForm.validateField.call(this);
                console.log('Field validation result:', $(this).attr('name'), fieldValid);
                if (!fieldValid) {
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = $(this);
                    }
                }
            });

            // WhatsApp validation removed - no longer blocking form submission

            // Focus on first error field
            if (firstErrorField) {
                firstErrorField.focus();
            }

            return isValid;
        },

        getFormData: function (form) {
            var formData = new FormData();

            form.find('.cfwv-field').each(function () {
                var field = $(this);
                var name = field.attr('name');
                var value = field.val();

                if (name) {
                    // Handle file uploads
                    if (field.hasClass('cfwv-file')) {
                        var file = field[0].files[0];
                        if (file) {
                            formData.append(name, file);
                        }
                    }
                    // Handle WhatsApp fields specially to combine country code
                    else if (field.hasClass('cfwv-whatsapp-field')) {
                        var wrapper = field.closest('.cfwv-whatsapp-field-wrapper');
                        var countryCodeSelector = wrapper.find('.cfwv-country-code-selector');
                        var countryCode = countryCodeSelector.length ? countryCodeSelector.val() : '+1';
                        var phoneNumber = value;

                        // If phone number doesn't start with +, prepend country code
                        if (phoneNumber && !phoneNumber.startsWith('+')) {
                            // Remove any leading zeros or special characters
                            phoneNumber = phoneNumber.replace(/^[0\s\-\(\)]+/, '');
                            value = countryCode + phoneNumber;
                        }
                        formData.append(name, value);
                    }
                    // Handle other fields
                    else {
                        formData.append(name, value);
                    }
                }
            });

            // Add form ID, nonce, and action
            formData.append('form_id', form.data('form-id'));
            formData.append('nonce', form.find('input[name="nonce"]').val());
            formData.append('action', 'cfwv_submit_form');

            return formData;
        },

        showFieldError: function (field, message) {
            var errorDiv;

            // For file inputs, place error after the upload wrapper
            if (field.hasClass('cfwv-file')) {
                var uploadWrapper = field.closest('.cfwv-file-upload-wrapper');
                errorDiv = uploadWrapper.siblings('.cfwv-field-error');
                if (errorDiv.length === 0) {
                    errorDiv = $('<div class="cfwv-field-error"></div>');
                    uploadWrapper.after(errorDiv);
                }
                field.siblings('.cfwv-file-label').addClass('error');
            } else {
                errorDiv = field.siblings('.cfwv-field-error');
                field.addClass('error');
            }

            errorDiv.text(message);
        },

        clearFieldError: function () {
            var field = $(this);
            var errorDiv;

            // For file inputs, find error after the upload wrapper
            if (field.hasClass('cfwv-file')) {
                var uploadWrapper = field.closest('.cfwv-file-upload-wrapper');
                errorDiv = uploadWrapper.siblings('.cfwv-field-error');
                field.siblings('.cfwv-file-label').removeClass('error');
            } else {
                errorDiv = field.siblings('.cfwv-field-error');
                field.removeClass('error');
            }

            errorDiv.text('');
        },

        handleFileUpload: function () {
            console.log('File upload triggered');
            var field = $(this);
            var file = field[0].files[0];
            var fieldWrapper = field.closest('.cfwv-field-wrapper') || field.parent();
            var fileText = fieldWrapper.find('.cfwv-file-text');

            console.log('File selected:', file);

            // Clear any existing errors
            ContactForm.clearFieldError.call(field);

            // Remove existing preview
            fieldWrapper.find('.cfwv-file-preview').remove();

            if (file) {
                // Update the file text
                fileText.text(file.name);

                // Validate file
                var validation = ContactForm.validateFile(file);
                if (!validation.valid) {
                    ContactForm.showFieldError(field, validation.message);
                    field.val(''); // Clear the file input
                    fileText.text('No file chosen');
                    return;
                }

                // Show file preview
                ContactForm.showFilePreview(field, file);
            } else {
                // Reset text if no file selected
                fileText.text('No file chosen');
            }
        },

        validateFile: function (file) {
            var maxSize = 5 * 1024 * 1024; // 5MB
            var allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            var allowedExtensions = ['.pdf', '.doc', '.docx'];

            // Check file size
            if (file.size > maxSize) {
                return {
                    valid: false,
                    message: 'File size must be less than 5MB'
                };
            }

            // Check file type
            if (!allowedTypes.includes(file.type) && !allowedExtensions.some(ext => file.name.toLowerCase().endsWith(ext))) {
                return {
                    valid: false,
                    message: 'Only PDF, DOC, and DOCX files are allowed'
                };
            }

            return { valid: true };
        },

        showFilePreview: function (field, file) {
            var fieldWrapper = field.closest('.cfwv-field-wrapper') || field.parent();
            var fileSize = ContactForm.formatFileSize(file.size);

            var preview = $('<div class="cfwv-file-preview">' +
                '<span class="file-name">' + file.name + '</span> ' +
                '<span class="file-size">(' + fileSize + ')</span> ' +
                '<a href="#" class="file-remove">Remove</a>' +
                '</div>');

            fieldWrapper.append(preview);
        },

        removeFile: function (e) {
            e.preventDefault();
            var field = $(this).closest('.cfwv-field-wrapper').find('.cfwv-file') ||
                $(this).closest('.cfwv-form').find('.cfwv-file');
            var fieldWrapper = field.closest('.cfwv-field-wrapper');
            var fileText = fieldWrapper.find('.cfwv-file-text');

            field.val('');
            fileText.text('No file chosen');
            $(this).closest('.cfwv-file-preview').remove();
        },

        formatFileSize: function (bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        showLoading: function (form) {
            form.find('.cfwv-loading').show();
        },

        hideLoading: function (form) {
            form.find('.cfwv-loading').hide();
        },

        showSuccess: function (form, message) {
            var messagesDiv = form.find('.cfwv-messages');
            messagesDiv.html('<div class="cfwv-message success">' + message + '</div>');

            // Scroll to message
            $('html, body').animate({
                scrollTop: messagesDiv.offset().top - 50
            }, 500);
        },

        showOTPSuccess: function (form, message, verificationUrl) {
            var messagesDiv = form.find('.cfwv-messages');
            var successHtml = '<div class="cfwv-message success">' + message + '</div>';
            successHtml += '<div class="cfwv-redirect-info">';
            successHtml += '<p>You will be redirected to the verification page automatically...</p>';
            successHtml += '<p>If you are not redirected, <a href="' + verificationUrl + '" class="cfwv-manual-redirect">click here to verify your code</a></p>';
            successHtml += '</div>';
            messagesDiv.html(successHtml);

            // Scroll to message
            $('html, body').animate({
                scrollTop: messagesDiv.offset().top - 50
            }, 500);
        },

        showError: function (form, message, errors) {
            var messagesDiv = form.find('.cfwv-messages');
            var errorHtml = '<div class="cfwv-message error">' + message + '</div>';

            // Show field-specific errors
            if (errors) {
                $.each(errors, function (fieldName, errorMessage) {
                    var field = form.find('[name="' + fieldName + '"]');
                    if (field.length) {
                        ContactForm.showFieldError(field, errorMessage);
                    }
                });
            }

            messagesDiv.html(errorHtml);

            // Scroll to message
            $('html, body').animate({
                scrollTop: messagesDiv.offset().top - 50
            }, 500);
        },

        // Validation helper functions
        isValidEmail: function (email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        isValidPhone: function (phone) {
            // Remove all non-digit characters except +
            var cleanPhone = phone.replace(/[^\d+]/g, '');

            // Check if the cleaned phone contains only digits and +
            var validRegex = /^[\d+]+$/;
            return validRegex.test(cleanPhone);
        },

        isValidUrl: function (url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }
    };

    // Phone number formatting
    var PhoneFormatter = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('input', '.cfwv-whatsapp-field, input[type="tel"]', this.formatPhone);
        },

        formatPhone: function () {
            var field = $(this);
            var value = field.val();
            var formatted = PhoneFormatter.formatPhoneNumber(value);

            if (formatted !== value) {
                field.val(formatted);
            }
        },

        formatPhoneNumber: function (phone) {
            // Remove all non-digit characters except +
            var cleaned = phone.replace(/[^\d+]/g, '');

            // Don't format if it starts with + (international format)
            if (cleaned.startsWith('+')) {
                return cleaned;
            }

            // Format US numbers
            if (cleaned.length === 10) {
                return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (cleaned.length === 11 && cleaned.startsWith('1')) {
                return cleaned.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '$1 ($2) $3-$4');
            }

            return cleaned;
        }
    };

    // Character counter
    var CharacterCounter = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('input', 'textarea.cfwv-field', this.updateCounter);
        },

        updateCounter: function () {
            var field = $(this);
            var maxLength = field.attr('maxlength') || 5000;
            var currentLength = field.val().length;
            var counter = field.siblings('.cfwv-char-counter');

            if (!counter.length) {
                counter = $('<div class="cfwv-char-counter"></div>');
                field.after(counter);
            }

            counter.text(currentLength + ' / ' + maxLength);

            if (currentLength > maxLength * 0.9) {
                counter.addClass('warning');
            } else {
                counter.removeClass('warning');
            }
        }
    };

    // File upload handling (if needed in future)
    var FileUpload = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('change', 'input[type="file"].cfwv-field', this.handleFileUpload);
        },

        handleFileUpload: function () {
            var field = $(this);
            var files = field[0].files;
            var maxSize = 5 * 1024 * 1024; // 5MB
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            for (var i = 0; i < files.length; i++) {
                var file = files[i];

                if (file.size > maxSize) {
                    ContactForm.showFieldError(field, 'File size must be less than 5MB');
                    field.val('');
                    return;
                }

                if (allowedTypes.indexOf(file.type) === -1) {
                    ContactForm.showFieldError(field, 'File type not allowed');
                    field.val('');
                    return;
                }
            }

            ContactForm.clearFieldError.call(field);
        }
    };

    // Initialize all components
    ContactForm.init();
    PhoneFormatter.init();
    CharacterCounter.init();
    FileUpload.init();

    // Auto-focus first field

    // Prevent double submission
    $(document).on('submit', '.cfwv-form', function () {
        var form = $(this);
        if (form.data('submitting')) {
            return false;
        }
        form.data('submitting', true);

        setTimeout(function () {
            form.data('submitting', false);
        }, 5000);
    });

    // Accessibility improvements
    $(document).on('focus', '.cfwv-field', function () {
        $(this).closest('.cfwv-field-wrapper').addClass('focused');
    });

    $(document).on('blur', '.cfwv-field', function () {
        $(this).closest('.cfwv-field-wrapper').removeClass('focused');
    });

    // Keyboard navigation
    $(document).on('keydown', '.cfwv-field', function (e) {
        if (e.key === 'Enter' && !$(this).is('textarea')) {
            e.preventDefault();
            var fields = $(this).closest('.cfwv-form').find('.cfwv-field');
            var currentIndex = fields.index(this);
            var nextField = fields.eq(currentIndex + 1);

            if (nextField.length) {
                nextField.focus();
            } else {
                $(this).closest('.cfwv-form').find('.cfwv-submit-btn').focus();
            }
        }
    });

    // Form analytics (if needed)
    var FormAnalytics = {
        init: function () {
            this.trackFormView();
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('focus', '.cfwv-field', this.trackFieldFocus);
            $(document).on('submit', '.cfwv-form', this.trackFormSubmit);
        },

        trackFormView: function () {
            $('.cfwv-form').each(function () {
                var formId = $(this).data('form-id');
                // Track form view (implement as needed)
            });
        },

        trackFieldFocus: function () {
            var field = $(this);
            var fieldName = field.attr('name');
            // Track field focus (implement as needed)
        },

        trackFormSubmit: function () {
            var form = $(this);
            var formId = form.data('form-id');
            // Track form submission (implement as needed)
        }
    };

    // Initialize analytics if needed
    // FormAnalytics.init();

    // Progressive enhancement
    $('.cfwv-form').addClass('js-enabled');

    // Add smooth scroll for better UX
    if (window.location.hash) {
        $('html, body').animate({
            scrollTop: $(window.location.hash).offset().top - 50
        }, 500);
    }
}); 