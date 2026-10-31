/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

window.FrontendBook = window.FrontendBook || {};

/**
 * Frontend Book
 *
 * This module contains functions that implement the book appointment page functionality. Once the
 * initialize() method is called the page is fully functional and can serve the appointment booking
 * process.
 *
 * @module FrontendBook
 */
(function (exports) {

    'use strict';

    /**
     * Contains terms and conditions consent.
     *
     * @type {Object}
     */
    var termsAndConditionsConsent;

    /**
     * Contains privacy policy consent.
     *
     * @type {Object}
     */
    var privacyPolicyConsent;

    /**
     * Determines the functionality of the page.
     *
     * @type {Boolean}
     */
    exports.manageMode = false;

    /**
     * This method initializes the book appointment page.
     *
     * @param {Boolean} defaultEventHandlers (OPTIONAL) Determines whether the default
     * event handlers will be bound to the dom elements.
     * @param {Boolean} manageMode (OPTIONAL) Determines whether the customer is going
     * to make  changes to an existing appointment rather than booking a new one.
     */
    exports.initialize = function (defaultEventHandlers, manageMode) {
        defaultEventHandlers = defaultEventHandlers || true;
        manageMode = manageMode || false;

        if (GlobalVariables.displayCookieNotice && window.cookieconsent) {
            cookieconsent.initialise({
                palette: {
                    popup: {
                        background: '#ffffffbd',
                        text: '#666666'
                    },
                    button: {
                        background: '#a51e37',
                        text: '#ffffff'
                    }
                },
                content: {
                    message: EALang.website_using_cookies_to_ensure_best_experience,
                    dismiss: 'OK'
                },
            });

            $('.cc-link').replaceWith(
                $('<a/>', {
                    'data-toggle': 'modal',
                    'data-target': '#cookie-notice-modal',
                    'href': '#',
                    'class': 'cc-link',
                    'text': $('.cc-link').text()
                })
            );
        }

        FrontendBook.manageMode = manageMode;

        // Initialize page's components (tooltips, datepickers etc).
        tippy('[data-tippy-content]');

        var weekDayId = GeneralFunctions.getWeekDayId(GlobalVariables.firstWeekday);

        var dateFormat;

        switch (GlobalVariables.dateFormat) {
            case 'DMY':
                dateFormat = 'dd/mm/yy';
                break;
            case 'DDMY':
                dateFormat = 'dd.mm.yy';
                break;
            case 'MDY':
                dateFormat = 'mm/dd/yy';
                break;
            case 'YMD':
                dateFormat = 'yy/mm/dd';
                break;
            default:
                throw new Error('Invalid GlobalVariables.dateFormat value.');
        }

        $('#select-date').datepicker({
            dateFormat: dateFormat,
            firstDay: weekDayId,
            minDate: 0,
            defaultDate: Date.today(),

            dayNames: [
                EALang.sunday, EALang.monday, EALang.tuesday, EALang.wednesday,
                EALang.thursday, EALang.friday, EALang.saturday],
            dayNamesShort: [EALang.sunday.substr(0, 3), EALang.monday.substr(0, 3),
                EALang.tuesday.substr(0, 3), EALang.wednesday.substr(0, 3),
                EALang.thursday.substr(0, 3), EALang.friday.substr(0, 3),
                EALang.saturday.substr(0, 3)],
            dayNamesMin: [EALang.sunday.substr(0, 2), EALang.monday.substr(0, 2),
                EALang.tuesday.substr(0, 2), EALang.wednesday.substr(0, 2),
                EALang.thursday.substr(0, 2), EALang.friday.substr(0, 2),
                EALang.saturday.substr(0, 2)],
            monthNames: [EALang.january, EALang.february, EALang.march, EALang.april,
                EALang.may, EALang.june, EALang.july, EALang.august, EALang.september,
                EALang.october, EALang.november, EALang.december],
            prevText: EALang.previous,
            nextText: EALang.next,
            currentText: EALang.now,
            closeText: EALang.close,

            onSelect: function (dateText, instance) {
                FrontendBookApi.getAvailableHours($(this).datepicker('getDate').toString('yyyy-MM-dd'));
                FrontendBook.updateConfirmFrame();
            },

            onChangeMonthYear: function (year, month, instance) {
                var currentDate = new Date(year, month - 1, 1);
                FrontendBookApi.getUnavailableDates($('#select-provider').val(), $('#select-service').val(),
                    currentDate.toString('yyyy-MM-dd'));
            }
        });

        //TODO: revert this back to it's original expresseion when timezone handling is fixed. Europe/Berlin hotfixes this
        $('#select-timezone').val("Europe/Berlin");

        // Bind the event handlers (might not be necessary every time we use this class).
        if (defaultEventHandlers) {
            bindEventHandlers();
        }

        // If the manage mode is true, the appointments data should be loaded by default.
        if (FrontendBook.manageMode) {
            applyAppointmentData(GlobalVariables.appointmentData,
                GlobalVariables.providerData, GlobalVariables.customerData);
        } else {
            var $selectProvider = $('#select-provider');
            var $selectService = $('#select-service');

            // Check if a specific service was selected (via URL parameter).
            var selectedServiceId = GeneralFunctions.getUrlParameter(location.href, 'service');

            if (selectedServiceId && $selectService.find('option[value="' + selectedServiceId + '"]').length > 0) {
                $selectService.val(selectedServiceId);
            }

            $selectService.trigger('change'); // Load the available hours.

            // Check if a specific provider was selected.
            var selectedProviderId = GeneralFunctions.getUrlParameter(location.href, 'provider');

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length === 0) {
                // Select a service of this provider in order to make the provider available in the select box.
                for (var index in GlobalVariables.availableProviders) {
                    var provider = GlobalVariables.availableProviders[index];

                    if (provider.id === selectedProviderId && provider.services.length > 0) {
                        $selectService
                            .val(provider.services[0])
                            .trigger('change');
                    }
                }
            }

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length > 0) {
                $selectProvider
                    .val(selectedProviderId)
                    .trigger('change');
            }

        }
    };

    /**
     * This method binds the necessary event handlers for the book appointments page.
     */
    function bindEventHandlers() {
        /**
         * Event: Timezone "Changed"
         */
        //TODO: This does not work the timezone in the dropdown and the timeslots is different
        $('#select-timezone').on('change', function () {
            var date = $('#select-date').datepicker('getDate');

            if (!date) {
                return;
            }

            FrontendBookApi.getAvailableHours(date.toString('yyyy-MM-dd'));

            FrontendBook.updateConfirmFrame();
        });

        /**
         * Event: Selected Provider "Changed"
         *
         * Whenever the provider changes the available appointment date - time periods must be updated.
         */
        $('#select-provider').on('change', function () {
            FrontendBookApi.getUnavailableDates($(this).val(), $('#select-service').val(),
                $('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));
            FrontendBook.updateConfirmFrame();
        });

        /**
         * Event: Selected Service "Changed"
         *
         * When the user clicks on a service, its available providers should
         * become visible.
         */
        $('#select-service').on('change', function () {
            var serviceId = $('#select-service').val();

            $('#select-provider').empty();

            GlobalVariables.availableProviders.forEach(function (provider) {
                // If the current provider is able to provide the selected service, add him to the list box.
                var canServeService = provider.services.filter(function (providerServiceId) {
                    return Number(providerServiceId) === Number(serviceId);
                }).length > 0;

                if (canServeService) {
                    $('#select-provider').append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));
                }
            });

            // Add the "Any Provider" entry.
            if ($('#select-provider option').length >= 1 && GlobalVariables.displayAnyProvider === '1') {
                $('#select-provider').append(new Option('- ' + EALang.any_provider + ' -', 'any-provider', true, true));
            }

            FrontendBookApi.getUnavailableDates($('#select-provider').val(), $(this).val(),
                $('#select-date').datepicker('getDate').toString('yyyy-MM-dd'));
            FrontendBook.updateConfirmFrame();
            updateServiceDescription(serviceId);
        });

        /**
         * Event: Next Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "next" button on the book wizard.
         * Some special tasks might be performed, depending the current wizard step.
         */
        $('.button-next').on('click', function () {
            // If we are on the first step and there is not provider selected do not continue with the next step.
            if ($(this).attr('data-step_index') === '1' && !$('#select-provider').val()) {
                return;
            }

            // If we are on the 2nd tab then the user should have an appointment hour selected.
            if ($(this).attr('data-step_index') === '2') {
                if (!$('.selected-hour').length) {
                    if (!$('#select-hour-prompt').length) {
                        $('<div/>', {
                            'id': 'select-hour-prompt',
                            'class': 'text-danger mb-4',
                            'text': EALang.appointment_hour_missing,
                        })
                            .prependTo('#available-hours');
                    }
                    return;
                }
            }

            // If we are on the 3rd tab then we will need to validate the user's input before proceeding to the next
            // step.
            if ($(this).attr('data-step_index') === '3') {
                if (!validateCustomerForm()) {
                    return; // Validation failed, do not continue.
                } else {
                    FrontendBook.updateConfirmFrame();

                    var $acceptToTermsAndConditions = $('#accept-to-terms-and-conditions');
                    if ($acceptToTermsAndConditions.length && $acceptToTermsAndConditions.prop('checked') === true) {
                        var newTermsAndConditionsConsent = {
                            first_name: $('#first-name').val(),
                            last_name: $('#last-name').val(),
                            email: $('#email').val(),
                            type: 'terms-and-conditions'
                        };

                        if (JSON.stringify(newTermsAndConditionsConsent) !== JSON.stringify(termsAndConditionsConsent)) {
                            termsAndConditionsConsent = newTermsAndConditionsConsent;
                            FrontendBookApi.saveConsent(termsAndConditionsConsent);
                        }
                    }

                    var $acceptToPrivacyPolicy = $('#accept-to-privacy-policy');
                    if ($acceptToPrivacyPolicy.length && $acceptToPrivacyPolicy.prop('checked') === true) {
                        var newPrivacyPolicyConsent = {
                            first_name: $('#first-name').val(),
                            last_name: $('#last-name').val(),
                            email: $('#email').val(),
                            type: 'privacy-policy'
                        };

                        if (JSON.stringify(newPrivacyPolicyConsent) !== JSON.stringify(privacyPolicyConsent)) {
                            privacyPolicyConsent = newPrivacyPolicyConsent;
                            FrontendBookApi.saveConsent(privacyPolicyConsent);
                        }
                    }
                }
            }

            // Display the next step tab (uses jquery animation effect).
            var nextTabIndex = parseInt($(this).attr('data-step_index')) + 1;

            $(this).parents().eq(1).hide('fade', function () {
                $('.active-step').removeClass('active-step');
                $('#step-' + nextTabIndex).addClass('active-step');
                $('#wizard-frame-' + nextTabIndex).show('fade');
            });
        });

        /**
         * Event: Back Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "back" button on the
         * book wizard.
         */
        $('.button-back').on('click', function () {
            var prevTabIndex = parseInt($(this).attr('data-step_index')) - 1;

            $(this).parents().eq(1).hide('fade', function () {
                $('.active-step').removeClass('active-step');
                $('#step-' + prevTabIndex).addClass('active-step');
                $('#wizard-frame-' + prevTabIndex).show('fade');
            });
        });

        /**
         * Event: Available Hour "Click"
         *
         * Triggered whenever the user clicks on an available hour
         * for his appointment.
         */
        $('#available-hours').on('click', '.available-hour', function () {
            $('.selected-hour').removeClass('selected-hour');
            $(this).addClass('selected-hour');
            FrontendBook.updateConfirmFrame();
        });

        if (FrontendBook.manageMode) {
            /**
             * Event: Cancel Appointment Button "Click"
             *
             * When the user clicks the "Cancel" button this form is going to be submitted. We need
             * the user to confirm this action because once the appointment is cancelled, it will be
             * delete from the database.
             *
             * @param {jQuery.Event} event
             */
            $('#cancel-appointment').on('click', function (event) {
                var buttons = [
                    {
                        text: EALang.cancel,
                        click: function () {
                            $('#message-box').dialog('close');
                        }
                    },
                    {
                        text: EALang.cancel_2,
                        click: function () {
                            if ($('#cancel-reason').val() === '') {
                                $('#cancel-reason').css('border', '2px solid #DC3545');
                                return;
                            }
                            $('#cancel-appointment-form textarea').val($('#cancel-reason').val());
                            $('#cancel-appointment-form').submit();
                        }
                    }
                ];

                GeneralFunctions.displayMessageBox(EALang.cancel_appointment_title,
                    EALang.write_appointment_removal_reason, buttons);

                $('<textarea/>', {
                    'class': 'form-control',
                    'id': 'cancel-reason',
                    'rows': '3',
                    'css': {
                        'width': '100%'
                    }
                })
                    .appendTo('#message-box');

                return false;
            });

            $('#delete-personal-information').on('click', function () {
                var buttons = [
                    {
                        text: EALang.cancel,
                        click: function () {
                            $('#message-box').dialog('close');
                        }
                    },
                    {
                        text: EALang.delete,
                        click: function () {
                            FrontendBookApi.deletePersonalInformation(GlobalVariables.customerToken);
                        }
                    }
                ];

                GeneralFunctions.displayMessageBox(EALang.delete_personal_information,
                    EALang.delete_personal_information_prompt, buttons);
            });

            // Hide Elements we don't need
            $('#steps').fadeOut();
            $('#wizard-frame-1').slideUp();
            $('.badge-primary').hide();
        }

        /**
         * Event: Book Appointment Form "Submit"
         *
         * Before the form is submitted to the server we need to make sure that
         * in the meantime the selected appointment date/time wasn't reserved by
         * another customer or event.
         */
        $('#book-appointment-submit').on('click', function () {
            var $appointmentModeWrapper = $('#appointment-mode-wrapper');

            if ($appointmentModeWrapper.is(':visible') && ! $('input[name="appointment_mode"]:checked').length)
            {
                displayBookingMessage(EALang.select_appointment_mode);

                return;
            }

            FrontendBookApi.registerAppointment();
        });

        /**
         * Event: Appointment Mode Radio "Changed"
         */
        $('input[name="appointment_mode"]').on('change', function () {
            // The location complaint becomes obsolete as soon as the customer picks one.
            $('#booking-message').addClass('d-none').empty();

            FrontendBook.updateConfirmFrame();
        });


        $('#select-date').on('mousedown', '.ui-datepicker-calendar td', function (event) {
            setTimeout(function () {
                FrontendBookApi.applyPreviousUnavailableDates(); // New jQuery UI version will replace the td elements.
            }, 300); // There is no draw event unfortunately.
        })
    }

    /**
     * This function validates the customer's data input. The user cannot continue
     * without passing all the validation checks.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validateCustomerForm() {
        $('#wizard-frame-3 .has-error').removeClass('has-error');
        $('#wizard-frame-3 label.text-danger').removeClass('text-danger');

        try {
            // Validate required fields.
            var missingRequiredField = false;
            $('.required').each(function (index, requiredField) {
                if (!$(requiredField).val()) {
                    $(requiredField).parents('.form-group').addClass('has-error');
                    missingRequiredField = true;
                }
            });
            if (missingRequiredField) {
                throw new Error(EALang.fields_are_required);
            }

            var $acceptToTermsAndConditions = $('#accept-to-terms-and-conditions');
            if ($acceptToTermsAndConditions.length && !$acceptToTermsAndConditions.prop('checked')) {
                $acceptToTermsAndConditions.parents('.form-check').addClass('text-danger');
                throw new Error(EALang.fields_are_required);
            }

            var $acceptToPrivacyPolicy = $('#accept-to-privacy-policy');
            if ($acceptToPrivacyPolicy.length && !$acceptToPrivacyPolicy.prop('checked')) {
                $acceptToPrivacyPolicy.parents('.form-check').addClass('text-danger');
                throw new Error(EALang.fields_are_required);
            }


            // Validate email address.
            if (!GeneralFunctions.validateEmail($('#email').val())) {
                $('#email').parents('.form-group').addClass('has-error');
                throw new Error(EALang.invalid_email);
            }

            return true;
        } catch (error) {
            $('#form-message').text(error.message);
            return false;
        }
    }

    /**
     * Display a booking message inside the wizard and fade it out afterwards.
     *
     * @param {String} message The message to be displayed.
     */
    function displayBookingMessage(message) {
        var $messageBox = $('#booking-message');

        $messageBox
            .removeClass('d-none')
            .text(message);

        setTimeout(function () {
            $messageBox.addClass('d-none');
        }, 5000);
    }

    /**
     * Exported booking message helper (e.g. used by the registration ajax flow).
     *
     * @param {String} message The message to be displayed.
     */
    exports.displayBookingMessage = function (message) {
        displayBookingMessage(message);
    };

    /**
     * Resolve the effective appointment mode of the current booking selection.
     *
     * The appointment mode is a provider decision: the per-day provider override wins over
     * the provider default. Missing values fall back to "BOTH" so that the customer
     * can choose. Manage-mode appointments with legacy free-form location values keep
     * their original value.
     *
     * @return {String} Returns "ONLINE", "ON_PREMISE", "BOTH" or a legacy raw location value.
     */
    function getEffectiveAppointmentMode() {
        var serviceId = $('#select-service').val();
        var providerId = $('#select-provider').val();
        var selectedDate = $('#select-date').datepicker('getDate');

        var service = GlobalVariables.availableServices.find(function (availableService) {
            return Number(availableService.id) === Number(serviceId);
        });

        var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
            return Number(availableProvider.id) === Number(providerId);
        });

        if (FrontendBook.manageMode && GlobalVariables.appointmentData
            && GlobalVariables.appointmentData.location
            && ['ONLINE', 'ON_PREMISE', 'Zoom'].indexOf(GlobalVariables.appointmentData.location) === -1) {
            return String(GlobalVariables.appointmentData.location);
        }

        // Legacy "Zoom" location means an online appointment (the location value was the mode
        // encoding in older Easy!Appointments versions).
        if (GlobalVariables.appointmentData && GlobalVariables.appointmentData.location === 'Zoom') {
            if ( ! provider || !GeneralFunctions.providerHasZoomLink(provider)) {
                return 'ON_PREMISE';
            }

            return 'ONLINE';
        }

        var weekday = selectedDate ? selectedDate.getDay() : null;

        var providerSettings = (provider && provider.settings) || {};
        var providerWeekdayMode = null;

        try {
            var providerWeekdayModes = providerSettings.weekday_modes
                ? JSON.parse(providerSettings.weekday_modes) : {};

            providerWeekdayMode = weekday !== null ? providerWeekdayModes[weekday] : null;
        } catch (error) {
            providerWeekdayMode = null;
        }

        var providerMode = providerSettings.appointment_mode;

        // First set value wins: the per-day provider override has precedence over the
        // provider default. Missing values everywhere fall back to "BOTH".
        var mode = providerWeekdayMode || providerMode || 'BOTH';

        // Online appointments only make sense when the selected provider has a Zoom link.
        if (mode === 'ONLINE' && provider && !GeneralFunctions.providerHasZoomLink(provider)) {
            mode = 'ON_PREMISE';
        }

        return mode;
    }

    /**
     * Resolve the display value of an appointment location.
     *
     * @param {String} mode The appointment mode value.
     * @param {String} serviceLocation The location of the selected service.
     * @param {Object} provider The selected provider record (or NULL).
     * @return {String} Returns the mode label for online appointments (the Zoom link is only
     * sent via email) and the service or provider location for on-site appointments.
     */
    function getLocationDisplayValue(mode, serviceLocation, provider) {
        if (mode === 'ONLINE') {
            return EALang.mode_online || 'Online';
        }

        if (mode === 'ON_PREMISE') {
            if (serviceLocation) {
                return serviceLocation;
            }

            if (provider) {
                return [provider.address, provider.zip_code, provider.city]
                    .filter(function (value) {
                        return value;
                    })
                    .join(' ');
            }
        }

        return mode; // Legacy free-form value.
    }

    /**
     * Every time this function is executed, it updates the confirmation page with the latest
     * customer settings and input for the appointment booking.
     */
    exports.updateConfirmFrame = function () {
        if ($('.selected-hour').text() === '') {
            return;
        }

        // Appointment Details
        var selectedDate = $('#select-date').datepicker('getDate');
        var selectedDateISO = selectedDate !== null
            ? selectedDate.toString('yyyy-MM-dd')
            : null;
        var selectedTime = $('.selected-hour').data('value') || null;

        if (selectedDate !== null) {
            selectedDate = GeneralFunctions.formatDate(selectedDate, GlobalVariables.dateFormat);
        }

        var serviceId = $('#select-service').val();
        var providerId = $('#select-provider').val();
        var servicePrice = '';
        var serviceCurrency = '';
        var serviceLocation = '';

        GlobalVariables.availableServices.forEach(function (service, index) {
            if (Number(service.id) === Number(serviceId) && Number(service.price) > 0) {
                servicePrice = service.price;
                serviceCurrency = service.currency;
                return false; // break loop
            }
        });

        GlobalVariables.availableServices.forEach(function (service, index) {
            if (Number(service.id) === Number(serviceId)) {
                serviceLocation = GeneralFunctions.resolveServiceLocation(
                    service,
                    selectedDateISO,
                    selectedTime
                );
                return false; // break loop
            }
        });

        var appointmentMode = getEffectiveAppointmentMode();
        var isHandledMode = ['ONLINE', 'ON_PREMISE', 'BOTH'].indexOf(appointmentMode) !== -1;

        var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
            return Number(availableProvider.id) === Number(providerId);
        });

        var hasZoomLink = GeneralFunctions.providerHasZoomLink(provider);

        var modePickRequired = false;

        if (isHandledMode)
        {
            $('#appointment-mode-wrapper').show();

            var canChoose = appointmentMode === 'BOTH' && hasZoomLink;

            modePickRequired = canChoose;

            // Without a Zoom link the online option is not offered.
            $('#appointment-mode-online').closest('.custom-control').toggle(hasZoomLink);

            // Radio buttons are only editable when the customer can actually decide.
            $('input[name="appointment_mode"]').prop('disabled', !canChoose);

            if ( ! canChoose)
            {
                // The mode is fixed by configuration (or by the missing Zoom link), so the customer
                // cannot change it and sees it as locked.
                var lockedMode = (appointmentMode === 'BOTH') ? 'ON_PREMISE' : appointmentMode;

                $('input[name="appointment_mode"][value="' + lockedMode + '"]')
                    .prop('checked', true);
            }
        }
        else
        {
            // Legacy free-form location value (addresses, URLs) is kept in the summary.
            $('#appointment-mode-wrapper').hide();
            $('input[name="appointment_mode"]').prop('checked', false)
                .closest('.custom-control').show();
        }

        var hasModePick = $('input[name="appointment_mode"]:checked').val();

        // While the picker is offered and untouched, the location stays blank instead of
        // displaying the internal "BOTH" label.
        var selectedMode = isHandledMode
            ? (hasModePick || (modePickRequired ? null : appointmentMode))
            : appointmentMode;

        $('#appointment-mode-hint').toggle(isHandledMode && selectedMode === 'ONLINE');

        var locationValue = getLocationDisplayValue(selectedMode, serviceLocation, provider);
        $('#appointment-details').empty();

        $('<div/>', {
            'html': [
                $('<h4/>', {
                    'text': EALang.appointment
                }),
                $('<p/>', {
                    'html': [
                        $('<span/>', {
                            'text': EALang.service + ': ' + $('#select-service option:selected').text()
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': EALang.provider + ': ' + $('#select-provider option:selected').text()
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': EALang.start + ': ' + selectedDate + ' ' + $('.selected-hour').text()
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': EALang.timezone + ': ' + $('#select-timezone option:selected').text()
                        }),
                        $('<br/>'),
                        $('<strong/>', {
                            'text': EALang.location + ': ' + locationValue,
                            'prop': {
                                'hidden': !locationValue
                            }
                        }),
                        $('<br/>', {
                            'prop': {
                                'hidden': !servicePrice
                            }
                        }),
                        $('<span/>', {
                            'text': EALang.price + ': ' + servicePrice + ' ' + serviceCurrency,
                            'prop': {
                                'hidden': !servicePrice
                            }
                        }),
                    ]
                })
            ]
        })
            .appendTo('#appointment-details');

        // Customer Details
        var firstName = GeneralFunctions.escapeHtml($('#first-name').val());
        var lastName = GeneralFunctions.escapeHtml($('#last-name').val());
        var phoneNumber = GeneralFunctions.escapeHtml($('#phone-number').val());
        var email = GeneralFunctions.escapeHtml($('#email').val());
        var address = GeneralFunctions.escapeHtml($('#address').val());
        var city = GeneralFunctions.escapeHtml($('#city').val());
        var zipCode = GeneralFunctions.escapeHtml($('#zip-code').val());
        var appointmentNotes = GeneralFunctions.escapeHtml($('#notes').val());

        $('#customer-details').empty();

        $('<div/>', {
            'html': [
                $('<h4/>)', {
                    'text': EALang.customer
                }),
                $('<p/>', {
                    'html': [
                        $('<span/>', {
                            'text': EALang.customer + ': ' + firstName + ' ' + lastName
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': EALang.email + ': ' + email
                        }),
                        $('<br/>', {
                            'prop': {
                                'hidden': !phoneNumber
                            }
                        }),
                        $('<span/>', {
                            'text': EALang.phone_number + ': ' + phoneNumber,
                            'prop': {
                                'hidden': !phoneNumber
                            }
                        }),
                        $('<br/>'),
                        $('<br/>', {
                            'prop': {
                                'hidden': !appointmentNotes
                            }
                        }),
                        $('<span/>', {
                            'text': EALang.notes + ':',
                            'prop': {
                                'hidden': !appointmentNotes
                            }
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': appointmentNotes,
                            'prop': {
                                'hidden': !appointmentNotes
                            }
                        }),
                       $('<br/>'),
                        $('<span/>', {
                            'text': address ? EALang.address + ': ' + address : ''
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': city ? EALang.city + ': ' + city : ''
                        }),
                        $('<br/>'),
                        $('<span/>', {
                            'text': zipCode ? EALang.zip_code + ': ' + zipCode : ''
                        }),
                        $('<br/>'),
                    ]
                })
            ]
        })
            .appendTo('#customer-details');


        // Update appointment form data for submission to server when the user confirms the appointment.
        var data = {};

        data.customer = {
            last_name: $('#last-name').val(),
            first_name: $('#first-name').val(),
            email: $('#email').val(),
            phone_number: $('#phone-number').val(),
            address: $('#address').val(),
            city: $('#city').val(),
            zip_code: $('#zip-code').val(),
            timezone: $('#select-timezone').val()
        };

        data.appointment = {
            start_datetime: $('#select-date').datepicker('getDate').toString('yyyy-MM-dd')
                + ' ' + Date.parse($('.selected-hour').data('value') || '').toString('HH:mm') + ':00',
            end_datetime: calculateEndDatetime(),
            notes: $('#notes').val(),
            is_unavailable: false,
            id_users_provider: $('#select-provider').val(),
            id_services: $('#select-service').val(),
            location: selectedMode
        };

        data.manage_mode = FrontendBook.manageMode;

        if (FrontendBook.manageMode) {
            data.appointment.id = GlobalVariables.appointmentData.id;
            data.customer.id = GlobalVariables.customerData.id;
        }
        $('input[name="csrfToken"]').val(GlobalVariables.csrfToken);
        $('input[name="post_data"]').val(JSON.stringify(data));
    };

    /**
     * This method calculates the end datetime of the current appointment.
     * End datetime is depending on the service and start datetime fields.
     *
     * @return {String} Returns the end datetime in string format.
     */
    function calculateEndDatetime() {
        // Find selected service duration.
        var serviceId = $('#select-service').val();

        var service = GlobalVariables.availableServices.find(function (availableService) {
            return Number(availableService.id) === Number(serviceId);
        });

        // Add the duration to the start datetime.
        var startDatetime = $('#select-date').datepicker('getDate').toString('dd-MM-yyyy')
            + ' ' + Date.parse($('.selected-hour').data('value') || '').toString('HH:mm');
        startDatetime = Date.parseExact(startDatetime, 'dd-MM-yyyy HH:mm');
        var endDatetime;

        if (service.duration && startDatetime) {
            endDatetime = startDatetime.add({'minutes': parseInt(service.duration)});
        } else {
            endDatetime = new Date();
        }

        return endDatetime.toString('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * This method applies the appointment's data to the wizard so
     * that the user can start making changes on an existing record.
     *
     * @param {Object} appointment Selected appointment's data.
     * @param {Object} provider Selected provider's data.
     * @param {Object} customer Selected customer's data.
     *
     * @return {Boolean} Returns the operation result.
     */
    function applyAppointmentData(appointment, provider, customer) {
        try {
            // Select Service & Provider
            $('#select-service').val(appointment.id_services).trigger('change');
            $('#select-provider').val(appointment.id_users_provider);

            // Set Appointment Date
            $('#select-date').datepicker('setDate',
                Date.parseExact(appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss'));
            FrontendBookApi.getAvailableHours(moment(appointment.start_datetime).format('YYYY-MM-DD'));

            // Apply Customer's Data
            $('#last-name').val(customer.last_name);
            $('#first-name').val(customer.first_name);
            $('#email').val(customer.email);
            $('#phone-number').val(customer.phone_number);
            $('#address').val(customer.address);
            $('#city').val(customer.city);
            $('#zip-code').val(customer.zip_code);
            if (customer.timezone) {
                $('#select-timezone').val(customer.timezone)
            }
            var appointmentNotes = (appointment.notes !== null)
                ? appointment.notes : '';
            $('#notes').val(appointmentNotes);

            FrontendBook.updateConfirmFrame();

            return true;
        } catch (exc) {
            return false;
        }
    }

    /**
     * This method updates a div's html content with a brief description of the
     * user selected service (only if available in db). This is useful for the
     * customers upon selecting the correct service.
     *
     * @param {Number} serviceId The selected service record id.
     */
    function updateServiceDescription(serviceId) {
        var $serviceDescription = $('#service-description');

        $serviceDescription.empty();

        var service = GlobalVariables.availableServices.find(function (availableService) {
            return Number(availableService.id) === Number(serviceId);
        });

        if (!service) {
            return;
        }

        $('<label/>', {
            'text': EALang.description,
        })
            .appendTo($serviceDescription);

        if (service.description) {
            $('<br/>')
                .appendTo($serviceDescription);

            $('<div/>', {
                'html': GeneralFunctions.escapeHtml(service.description).replaceAll('\n', '<br/>'),
                'prop': {
                    'id': 'detailed-description'
                }
            })
                .appendTo($serviceDescription);
        }

        if (service.duration || Number(service.price) > 0 || service.location) {
            $('<br/>')
                .appendTo($serviceDescription);
        }

        if (service.duration) {
            $('<span/>', {
                'text': '[' + EALang.duration + ': ' + service.duration + ' ' + EALang.minutes + ']'
            })
                .appendTo($serviceDescription);
        }

        if (Number(service.price) > 0) {
            $('<span/>', {
                'text': '[' + EALang.price + ': ' + service.price + ' ' + service.currency + ']'
            })
                .appendTo($serviceDescription);
        }

        if (service.location) {
            $('<span/>', {
                'text': '[' + EALang.location + ': ' + service.location + ']'
            })
                .appendTo($serviceDescription);
        }
    }

})(window.FrontendBook);
