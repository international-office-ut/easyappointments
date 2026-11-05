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
     * "User Settings" Tab Helper Class
     *
     * @class UserSettings
     */
    var UserSettings = function () {
    };

    /**
     * Get the settings data for the user settings.
     *
     * @return {Object} Returns the user settings array.
     */
    UserSettings.prototype.get = function () {
        var user = {
            id: $('#user-id').val(),
            first_name: $('#first-name').val(),
            last_name: $('#last-name').val(),
            email: $('#email').val(),
            mobile_number: $('#mobile-number').val(),
            phone_number: $('#phone-number').val(),
            address: $('#address').val(),
            city: $('#city').val(),
            state: $('#state').val(),
            zip_code: $('#zip-code').val(),
            notes: $('#notes').val(),
            timezone: $('#timezone').val(),
            settings: {
                username: $('#username').val(),
                notifications: $('#user-notifications').prop('checked'),
                calendar_view: $('#calendar-view').val()
            }
        };

        // Providers maintain their own availability settings on the settings page: the work
        // plan editor state rides the ordinary user settings save (the self-only row check
        // inside the endpoint applies the same as always).
        if (GlobalVariables.userSlug === 'provider') {
            user.settings.working_plan = JSON.stringify(BackendSettings.wp.get());
            user.settings.working_plan_exceptions =
                JSON.stringify(BackendSettings.wp.getWorkingPlanExceptions());

            var weekdayModes = BackendSettings.wp.getWeekdayModes();

            if (weekdayModes) {
                user.settings.weekday_modes = JSON.stringify(weekdayModes);
            }

            user.settings.appointment_mode = $('#provider-appointment-mode').val() || null;
            user.settings.zoom_link = $('#provider-zoom-link').val();
        }

        if ($('#password').val()) {
            user.settings.password = $('#password').val();
        }

        return user;
    };

    /**
     * Store the user settings into the database.
     *
     * @param {Array} settings Contains the user settings.
     */
    UserSettings.prototype.save = function (settings) {
        if (!this.validate(settings)) {
            Backend.displayNotification(EALang.user_settings_are_invalid);
            return; // Validation failed, do not proceed.
        }

        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_settings';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            type: BackendSettings.SETTINGS_USER,
            settings: JSON.stringify(settings)
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.settings_saved);

                // Update footer greetings.
                $('#footer-user-display-name').text('Hello, ' + $('#first-name').val() + ' ' + $('#last-name').val() + '!');

                // Keep the saved payload as the revert anchor for the cancel path.
                GlobalVariables.settings.user = settings;

                // The edit-save-abort workflow locks the pane again after the successful save.
                BackendSettings.lockSettingsPane('#current-user', 'user_settings');
            });
    };

    /**
     * Validate the settings data.
     *
     * If the validation fails then display a message to the user.
     *
     * @return {Boolean} Returns the validation result.
     */
    UserSettings.prototype.validate = function () {
        $('#current-user .has-error').removeClass('has-error');

        try {
            // Validate required fields.
            var missingRequired = false;
            $('#current-user .required').each(function (index, requiredField) {
                if (!$(requiredField).val()) {
                    $(requiredField).closest('.form-group').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(EALang.fields_are_required);
            }

            // Validate passwords (if provided).
            if ($('#password').val() !== $('#retype-password').val()) {
                $('#password, #retype-password').closest('.form-group').addClass('has-error');
                throw new Error(EALang.passwords_mismatch);
            }

            // Validate user email.
            if (!GeneralFunctions.validateEmail($('#email').val())) {
                $('#email').closest('.form-group').addClass('has-error');
                throw new Error(EALang.invalid_email);
            }

            if ($('#username').attr('already-exists') === 'true') {
                $('#username').closest('.form-group').addClass('has-error');
                throw new Error(EALang.username_already_exists);
            }

            // Provider record rules mirror the users-page validation: the online mode and
            // its weekday overrides only work with a configured zoom link.
            if (GlobalVariables.userSlug === 'provider') {
                var providerFieldsInvalid = false;
                var weekdayModes = BackendSettings.wp.getWeekdayModes() || {};

                var orphanBreakDays = BackendSettings.wp.getOrphanBreakDays();

                if (orphanBreakDays.length) {
                    throw new Error(
                        EALang.breaks_require_active_days.replace('{$days}', orphanBreakDays.join(', ')));
                }
                var weekdayValues = Object.keys(weekdayModes).map(function (weekday) {
                    return weekdayModes[weekday];
                });

                if ($('#provider-appointment-mode').val() === 'ONLINE' && !$('#provider-zoom-link').val()) {
                    providerFieldsInvalid = true;
                }

                if (weekdayValues.indexOf('ONLINE') !== -1 && !$('#provider-zoom-link').val()) {
                    providerFieldsInvalid = true;
                }

                if (providerFieldsInvalid) {
                    $('#provider-zoom-link').closest('.form-group').addClass('has-error');
                    throw new Error(EALang.zoom_link_required);
                }
            }

            return true;
        } catch (error) {
            Backend.displayNotification(error.message);
            return false;
        }
    };

    window.UserSettings = UserSettings;

    /**
     * Bind the calendar feed widget handlers of the user tab (visible for providers only).
     */
    $(function () {
        if (GlobalVariables.userSlug !== 'provider') {
            return;
        }

        function feedState() {
            var providerId = GlobalVariables.settings.user && GlobalVariables.settings.user.id
                ? GlobalVariables.settings.user.id
                : $('#user-id').val();

            if (!providerId) {
                return;
            }

            $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_ical_feed_state', {
                csrfToken: GlobalVariables.csrfToken,
                provider_id: providerId
            })
                .done(function (response) {
                    $('#feed-state').text(response.active
                        ? EALang.feed_active
                        : EALang.feed_inactive);
                })
                .fail(function () {
                    $('#feed-state').text('');
                });
        }

        feedState();

        $('#feed-generate').on('click', function () {
            var providerId = $('#user-id').val();

            if (!providerId) {
                return;
            }

            $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_generate_ical_feed', {
                csrfToken: GlobalVariables.csrfToken,
                provider_id: providerId
            })
                .done(function (response) {
                    $('#feed-url').val(response.feed_url);
                    $('#feed-state').text(EALang.feed_active);
                    $('#feed-url').get(0).select();
                })
                .fail(function (jqXHR) {
                    Backend.displayNotification(jqXHR.responseJSON && jqXHR.responseJSON.message
                        ? jqXHR.responseJSON.message
                        : EALang.oops_something_went_wrong);
                });
        });

        $('#feed-revoke').on('click', function () {
            var providerId = $('#user-id').val();

            if (!providerId) {
                return;
            }

            $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_revoke_ical_feed', {
                csrfToken: GlobalVariables.csrfToken,
                provider_id: providerId
            })
                .done(function () {
                    $('#feed-url').val('');
                    $('#feed-state').text(EALang.feed_inactive);
                    Backend.displayNotification(EALang.feed_revoked);
                })
                .fail(function (jqXHR) {
                    Backend.displayNotification(jqXHR.responseJSON && jqXHR.responseJSON.message
                        ? jqXHR.responseJSON.message
                        : EALang.oops_something_went_wrong);
                });
        });

        $('#feed-copy').on('click', function () {
            var url = $('#feed-url').val();

            if (!url) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url);
            } else {
                $('#feed-url').get(0).select();
                document.execCommand('copy');
            }

            Backend.displayNotification(EALang.feed_copied);
        });
    });

    /**
     * Unavailability period management of the provider settings tab: the listing mirrors
     * the users-page work plan editor and reuses the shared calendar API and dialog.
     */
    var currentPeriods = [];

    $('#settings-page').on('click', '.edit-settings', function () {
        if (GlobalVariables.userSlug === 'provider') {
            window.renderUnavailabilityPeriods($('#provider-id').val());
        }
    });

    window.renderUnavailabilityPeriods = function (providerId) {
        if (!providerId || GlobalVariables.userSlug !== 'provider') {
            return;
        }

        var $table = $('.unavailability-periods tbody');

        BackendCalendarApi.getProviderUnavailabilityPeriods(providerId)
            .done(function (periods) {
                $table.empty();

                currentPeriods = periods || [];

                currentPeriods.forEach(function (period) {
                    var $row = $('<tr/>');

                    $row.append(
                        $('<td/>', {text: GeneralFunctions.formatDate(
                            period.start_datetime, GlobalVariables.dateFormat, true)}),
                        $('<td/>', {text: GeneralFunctions.formatDate(
                            period.end_datetime, GlobalVariables.dateFormat, true)}),
                        $('<td/>', {text: period.notes || '-'}),
                        $('<td/>', {'data-id': period.id})
                    );

                    var $editButton = $('<button/>', {
                        'type': 'button',
                        'class': 'btn btn-outline-secondary btn-sm edit-period',
                        'title': EALang.edit,
                        'html': [$('<span/>', {'class': 'fas fa-edit'})]
                    });

                    var $deleteButton = $('<button/>', {
                        'type': 'button',
                        'class': 'btn btn-outline-secondary btn-sm delete-period',
                        'title': EALang.delete,
                        'html': [$('<span/>', {'class': 'fas fa-trash-alt'})]
                    });

                    $row.find('td:last').append($editButton, $deleteButton);

                    $table.append($row);
                });
            });
    };

    $(document).on('click', '.edit-period', function () {
        var periodId = $(this).closest('td').data('id');
        var period = currentPeriods.find(function (candidate) {
            return Number(candidate.id) === Number(periodId);
        });

        if (!period) {
            return;
        }

        BackendCalendarUnavailabilityEventsModal.resetUnavailableDialog();

        var $dialog = $('#manage-unavailable');

        $dialog.find('#unavailable-id').val(period.id);
        $dialog.find('#unavailable-provider').val(period.id_users_provider);
        $dialog.find('#unavailable-start').datetimepicker(
            'setDate', Date.parseExact(period.start_datetime, 'yyyy-MM-dd HH:mm:ss'));
        $dialog.find('#unavailable-end').datetimepicker(
            'setDate', Date.parseExact(period.end_datetime, 'yyyy-MM-dd HH:mm:ss'));
        $dialog.find('#unavailable-notes').val(period.notes || '');
        $dialog.find('.modal-header h3').text(EALang.edit_unavailable_title);
        $dialog.modal('show');
    });

    $(document).on('click', '.delete-period', function () {
        var providerId = $('#provider-id').val();
        var periodId = $(this).closest('td').data('id');
        var $row = $(this).closest('tr');

        $row.css('opacity', '0.5');

        BackendCalendarApi.deleteUnavailable(periodId, function () {
            Backend.displayNotification(EALang.delete_unavailable);
            window.renderUnavailabilityPeriods(providerId);
        }, function () {
            $row.css('opacity', '');
            Backend.displayNotification(EALang.unexpected_issues_message);
        });
    });
})();
