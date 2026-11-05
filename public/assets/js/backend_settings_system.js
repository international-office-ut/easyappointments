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

(function () {

    'use strict';

    /**
     * "System Settings" Tab Helper Class
     *
     * @class SystemSettings
     */
    var SystemSettings = function () {
    };

    /**
     * Save the system settings.
     *
     * This method is run after changes are detected on the tab input fields.
     *
     * @param {Array} settings Contains the system settings data.
     */
    SystemSettings.prototype.save = function (settings, $pane) {        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_settings';

        $pane = $pane || $('#save-settings').closest('.tab-pane');

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            settings: JSON.stringify(settings),
            type: BackendSettings.SETTINGS_SYSTEM
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.settings_saved);

                // Update the logo title on the header.
                $('#header-logo span').text($('#company-name').val());

                // Update book_advance_timeout preview
                var totalMinutes = $('#book-advance-timeout').val();
                var hours = Math.floor(totalMinutes / 60);
                var minutes = totalMinutes % 60;
                $('#book-advance-timeout-helper').text(
                    EALang.book_advance_timeout_hint.replace('{$limit}', ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2))
                );

                // Update variables also used in other setting tabs
                GlobalVariables.timeFormat = $('#time-format').val();
                GlobalVariables.firstWeekday = $('#first-weekday').val();

                // We need to refresh the working plan.
                var workingPlan = BackendSettings.wp.get();
                BackendSettings.wp.setup(workingPlan);
                BackendSettings.wp.timepickers(false);

                // Keep the saved payload as the revert anchor for the cancel path.
                GlobalVariables.settings.system = settings;

                // The edit-save-abort workflow locks the pane again after the successful save.
                BackendSettings.lockSettingsPane($pane, 'system_settings');
            });
    };

    /**
     * Prepare the system settings array.
     *
     * This method uses the DOM elements of the backend/settings page, so it can't be used in another page.
     *
     * @return {Array} Returns the system settings array.
     */
    SystemSettings.prototype.get = function () {
        var settings = [];

        // General Settings Tab

        $('#general').find('input, select').not('input:checkbox').each(function (index, field) {
            // Widgets without a settings binding (e.g. the API token display) do not ride
            // the save payload.
            var dataField = $(field).attr('data-field');

            if (!dataField) {
                return;
            }

            settings.push({
                name: dataField,
                value: $(field).val()
            });
        });

        // Data retention setting (legal contents tab), stored in days.
        settings.push({
            name: 'record_retention_days',
            value: $('#data-retention-days').val() || '90'
        });

        settings.push({
            name: 'customer_notifications',
            value: $('#customer-notifications').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'require_captcha',
            value: $('#require-captcha').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'require_phone_number',
            value: $('#require-phone-number').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'display_any_provider',
            value: $('#display-any-provider').prop('checked') ? '1' : '0'
        });


        // Business Logic Tab

        settings.push({
            name: 'company_working_plan',
            value: JSON.stringify(BackendSettings.wp.get())
        });

        settings.push({
            name: 'book_advance_timeout',
            value: $('#book-advance-timeout').val()
        });

        // Legal Contents Tab

        settings.push({
            name: 'display_cookie_notice',
            value: $('#display-cookie-notice').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'cookie_notice_content',
            value: $('#cookie-notice-content').trumbowyg('html')
        });

        settings.push({
            name: 'display_terms_and_conditions',
            value: $('#display-terms-and-conditions').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'terms_and_conditions_content',
            value: $('#terms-and-conditions-content').trumbowyg('html')
        });

        settings.push({
            name: 'display_privacy_policy',
            value: $('#display-privacy-policy').prop('checked') ? '1' : '0'
        });

        settings.push({
            name: 'privacy_policy_content',
            value: $('#privacy-policy-content').trumbowyg('html')
        });

        return settings;
    };

    /**
     * Validate the settings data.
     *
     * If the validation fails then display a message to the user.
     *
     * @return {Boolean} Returns the validation result.
     */
    SystemSettings.prototype.validate = function () {
        $('#general .has-error').removeClass('has-error');

        try {
            // Validate required fields.
            var missingRequired = false;
            $('#general .required').each(function (index, requiredField) {
                if (!$(requiredField).val()) {
                    $(requiredField).closest('.form-group').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(EALang.fields_are_required);
            }

            // Validate company email address.
            if (!GeneralFunctions.validateEmail($('#company-email').val())) {
                $('#company-email').closest('.form-group').addClass('has-error');
                throw new Error(EALang.invalid_email);
            }

            return true;
        } catch (error) {
            Backend.displayNotification(error.message);
            return false;
        }
    };

    window.SystemSettings = SystemSettings;

    (function apiTokenWidget() {
        $(function () {

            if (!GlobalVariables.user.privileges.system_settings
                || !GlobalVariables.user.privileges.system_settings.view) {
                return;
            }

            function tokenState() {
                $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_api_token_state', {
                    csrfToken: GlobalVariables.csrfToken
                })
                    .done(function (response) {
                        $('#api-token-state').text(response.active
                            ? EALang.api_token_active
                            : EALang.api_token_inactive);
                    })
                    .fail(function () {
                        $('#api-token-state').text('');
                    });
            }

            tokenState();

            $('#api-token-generate').on('click', function () {
                $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_generate_api_token', {
                    csrfToken: GlobalVariables.csrfToken
                })
                    .done(function (response) {
                        $('#api-token-value').val(response.api_token);
                        $('#api-token-value').get(0).select();
                        $('#api-token-state').text(EALang.api_token_active);
                        Backend.displayNotification(EALang.api_token_generated);
                    })
                    .fail(function (jqXHR) {
                        Backend.displayNotification(jqXHR.responseJSON && jqXHR.responseJSON.message
                            ? jqXHR.responseJSON.message
                            : EALang.oops_something_went_wrong);
                    });
            });

            $('#api-token-revoke').on('click', function () {
                $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_revoke_api_token', {
                    csrfToken: GlobalVariables.csrfToken
                })
                    .done(function () {
                        $('#api-token-value').val('');
                        $('#api-token-state').text(EALang.api_token_inactive);
                        Backend.displayNotification(EALang.api_token_revoked);
                    })
                    .fail(function (jqXHR) {
                        Backend.displayNotification(jqXHR.responseJSON && jqXHR.responseJSON.message
                            ? jqXHR.responseJSON.message
                            : EALang.oops_something_went_wrong);
                    });
            });

            $('#api-token-copy').on('click', function () {
                var apiToken = $('#api-token-value').val();

                if (!apiToken) {
                    return;
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(apiToken);
                } else {
                    $('#api-token-value').get(0).select();
                    document.execCommand('copy');
                }

                Backend.displayNotification(EALang.feed_copied);
            });
    
        });
    })();

})();
