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

            // WhatsApp validation
            $(document).on('blur', '.cfwv-whatsapp-field', this.validateWhatsApp);
            $(document).on('input', '.cfwv-whatsapp-field', this.delayedWhatsAppValidation);

            // Real-time validation
            $(document).on('blur', '.cfwv-field', this.validateField);

            // Clear errors on input
            $(document).on('input', '.cfwv-field', this.clearFieldError);

            // Mark fields as user-interacted
            $(document).on('focus input keydown', '.cfwv-field', function () {
                $(this).data('user-interacted', true);
            });
        },

        initValidation: function () {
            // Initialize any third-party validation libraries if needed
        },

        submitForm: function (e) {
            e.preventDefault();

            var form = $(this);
            var formData = ContactForm.getFormData(form);

            // Validate form
            if (!ContactForm.validateForm(form)) {
                return false;
            }

            // Show loading state
            ContactForm.showLoading(form);

            // Disable submit button
            var submitBtn = form.find('.cfwv-submit-btn');
            submitBtn.prop('disabled', true).text('Submitting...');

            // Submit form via AJAX
            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_submit_form',
                    form_data: formData,
                    nonce: cfwv_ajax.nonce
                },
                success: function (response) {
                    console.log('Form submission response:', response); // Debug log

                    if (response.success) {
                        if (response.otp_verification) {
                            console.log('OTP verification required, redirecting to:', response.verification_url); // Debug log

                            // Show OTP verification message with manual redirect option
                            ContactForm.showOTPSuccess(form, response.message, response.verification_url);

                            // Redirect to OTP verification page with a short delay to ensure message is shown
                            console.log('Redirecting to verification page...'); // Debug log

                            if (response.verification_url) {
                                // Show message for 1 second, then redirect
                                setTimeout(function () {
                                    console.log('Executing redirect to:', response.verification_url);

                                    // Try multiple redirect methods
                                    try {
                                        // Method 1: Direct assignment
                                        window.location.href = response.verification_url;

                                        // Method 2: Fallback after 500ms
                                        setTimeout(function () {
                                            if (window.location.href.indexOf('cfwv_otp_verify') === -1) {
                                                console.log('Fallback redirect triggered');
                                                window.location.replace(response.verification_url);
                                            }
                                        }, 500);

                                        // Method 3: Final fallback after 1 second
                                        setTimeout(function () {
                                            if (window.location.href.indexOf('cfwv_otp_verify') === -1) {
                                                console.log('Final fallback redirect triggered');
                                                document.location.href = response.verification_url;
                                            }
                                        }, 1000);

                                    } catch (error) {
                                        console.error('Redirect error:', error);
                                        // Show manual redirect option
                                        ContactForm.showOTPSuccess(form, response.message + ' Please click the link below to continue.', response.verification_url);
                                    }
                                }, 1000);
                            } else {
                                console.error('No verification URL provided in response');
                                ContactForm.showError(form, 'No verification URL provided. Please contact support.');
                            }
                        } else {
                            ContactForm.showSuccess(form, response.message);

                            // Reset form
                            form[0].reset();

                            // Redirect if URL provided
                            if (response.redirect_url) {
                                setTimeout(function () {
                                    window.location.href = response.redirect_url;
                                }, 2000);
                            }
                        }
                    } else {
                        ContactForm.showError(form, response.message, response.errors);
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

        validateWhatsApp: function () {
            var field = $(this);
            var phoneNumber = field.val();
            var validationDiv = field.siblings('.cfwv-whatsapp-validation');

            if (!phoneNumber) {
                validationDiv.removeClass('valid invalid').text('');
                return;
            }

            // Show validating state
            validationDiv.removeClass('valid invalid').text('Validating...');

            $.ajax({
                url: cfwv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cfwv_validate_whatsapp',
                    phone: phoneNumber,
                    nonce: cfwv_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        if (response.valid) {
                            validationDiv.addClass('valid').removeClass('invalid').text('✓ Valid WhatsApp number');
                            field.removeClass('error').addClass('valid');
                        } else {
                            validationDiv.addClass('invalid').removeClass('valid').text('✗ Not a valid WhatsApp number');
                            field.removeClass('valid').addClass('error');
                        }
                    } else {
                        validationDiv.addClass('invalid').removeClass('valid').text('✗ ' + response.message);
                        field.removeClass('valid').addClass('error');
                    }
                },
                error: function () {
                    validationDiv.addClass('invalid').removeClass('valid').text('✗ Validation failed');
                    field.removeClass('valid').addClass('error');
                }
            });
        },

        delayedWhatsAppValidation: function () {
            var field = $(this);

            clearTimeout(field.data('validation-timeout'));

            field.data('validation-timeout', setTimeout(function () {
                ContactForm.validateWhatsApp.call(field);
            }, 1000));
        },

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

            // WhatsApp field validation
            if (field.hasClass('cfwv-whatsapp-field')) {
                if (!ContactForm.isValidPhone(fieldValue)) {
                    ContactForm.showFieldError(field, 'Please enter a valid WhatsApp number');
                    return false;
                }
            }

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

            // Validate all fields
            form.find('.cfwv-field').each(function () {
                if (!ContactForm.validateField.call(this)) {
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = $(this);
                    }
                }
            });

            // Check WhatsApp validation specifically
            var whatsappField = form.find('.cfwv-whatsapp-field');
            if (whatsappField.length) {
                var validationDiv = whatsappField.siblings('.cfwv-whatsapp-validation');
                if (!validationDiv.hasClass('valid') && whatsappField.val()) {
                    ContactForm.showFieldError(whatsappField, 'Please wait for WhatsApp validation to complete');
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = whatsappField;
                    }
                }
            }

            // Focus on first error field
            if (firstErrorField) {
                firstErrorField.focus();
            }

            return isValid;
        },

        getFormData: function (form) {
            var formData = {};

            form.find('.cfwv-field').each(function () {
                var field = $(this);
                var name = field.attr('name');
                var value = field.val();

                if (name) {
                    formData[name] = value;
                }
            });

            // Add form ID
            formData.form_id = form.data('form-id');
            formData.nonce = cfwv_ajax.nonce;

            return formData;
        },

        showFieldError: function (field, message) {
            var errorDiv = field.siblings('.cfwv-field-error');
            errorDiv.text(message);
            field.addClass('error');
        },

        clearFieldError: function () {
            var field = $(this);
            var errorDiv = field.siblings('.cfwv-field-error');
            errorDiv.text('');
            field.removeClass('error');
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

            // Check if it's a valid international format
            var intlRegex = /^\+[1-9]\d{1,14}$/;
            if (intlRegex.test(cleanPhone)) {
                return true;
            }

            // Check if it's a valid US format without +
            var usRegex = /^[1-9]\d{9}$/;
            if (usRegex.test(cleanPhone)) {
                return true;
            }

            return false;
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