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

window.BackendSettings = window.BackendSettings || {};

/**
 * Backend Settings
 *
 * Contains the functionality of the backend settings page. Can either work for system or user settings,
 * but the actions allowed to the user are restricted to his role (only admin has full privileges).
 *
 * @module BackendSettings
 */
(function (exports) {

    'use strict';

    // Constants
    exports.SETTINGS_SYSTEM = 'SETTINGS_SYSTEM';
    exports.SETTINGS_USER = 'SETTINGS_USER';

    /**
     * Use this WorkingPlan class instance to perform actions on the page's working plan tables.
     *
     * @type {WorkingPlan}
     */
    exports.wp = {};

    /**
     * Tab settings object.
     *
     * @type {Object}
     */
    var settings = {};

    /**
     * Initialize Page
     *
     * @param {bool} defaultEventHandlers Optional (true), determines whether to bind the default event handlers.
     */
    exports.initialize = function (defaultEventHandlers) {
        defaultEventHandlers = defaultEventHandlers || true;

        $('#cookie-notice-content, #terms-and-conditions-content, #privacy-policy-content').trumbowyg();

        // Apply setting values from database.
        var workingPlan = {};

        GlobalVariables.settings.system.forEach(function (setting) {
            $('input[data-field="' + setting.name + '"]').val(setting.value);
            $('select[data-field="' + setting.name + '"]').val(setting.value);

            if (setting.name === 'company_working_plan') {
                workingPlan = $.parseJSON(setting.value);
            }

            if (setting.name === 'customer_notifications') {
                $('#customer-notifications').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'require_captcha') {
                $('#require-captcha').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'require_phone_number') {
                $('#require-phone-number').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'display_any_provider') {
                $('#display-any-provider').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'display_cookie_notice') {
                $('#display-cookie-notice').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'cookie_notice_content') {
                $('#cookie-notice-content').trumbowyg('html', setting.value);
            }

            if (setting.name === 'display_terms_and_conditions') {
                $('#display-terms-and-conditions').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'terms_and_conditions_content') {
                $('#terms-and-conditions-content').trumbowyg('html', setting.value);
            }

            if (setting.name === 'display_privacy_policy') {
                $('#display-privacy-policy').prop('checked', Boolean(Number(setting.value)));
            }

            if (setting.name === 'privacy_policy_content') {
                $('#privacy-policy-content').trumbowyg('html', setting.value);
            }

            if (setting.name === 'record_retention_days') {
                $('#data-retention-days').val(setting.value);
            }
        });

        exports.wp = new WorkingPlan({
            weekdayModes: GlobalVariables.userSlug === 'provider'
        });

        // The provider work plan editor shows the provider's own availability settings, the
        // general users keep the company plan context of the business logic tab.
        if (GlobalVariables.userSlug === 'provider') {
            var userPlan = $.parseJSON(GlobalVariables.settings.user.settings.working_plan || '{}') || {};
            var userPlanExceptions =
                $.parseJSON(GlobalVariables.settings.user.settings.working_plan_exceptions || '{}') || {};

            exports.wp.setup(userPlan);
            exports.wp.setupWorkingPlanExceptions(userPlanExceptions);
            exports.wp.displayWeekdayModes(GlobalVariables.settings.user.settings.weekday_modes);
            exports.wp.syncWeekdayModeStates();

            // The self service plan editor includes the unavailability period management:
            // the provider id anchors the modal and the listing to the own record.
            $('#provider-id').val(GlobalVariables.settings.user.id);
            BackendCalendarUnavailabilityEventsModal.initialize();
            $('#manage-unavailable #unavailable-provider').closest('.form-group').hide();
        } else {
            exports.wp.setup(workingPlan);
        }

        exports.wp.timepickers(false);

        // The weekday appointment mode applies only to selected working days: sync the
        // select states whenever a checkbox toggles.
        $('#settings-page').on('change', '.working-plan .form-check-input', function () {
            exports.wp.syncWeekdayModeStates();
        });

        // Load user settings into form
        $('#user-id').val(GlobalVariables.settings.user.id);
        $('#first-name').val(GlobalVariables.settings.user.first_name);
        $('#last-name').val(GlobalVariables.settings.user.last_name);
        $('#email').val(GlobalVariables.settings.user.email);
        $('#mobile-number').val(GlobalVariables.settings.user.mobile_number);
        $('#phone-number').val(GlobalVariables.settings.user.phone_number);
        $('#address').val(GlobalVariables.settings.user.address);
        $('#city').val(GlobalVariables.settings.user.city);
        $('#state').val(GlobalVariables.settings.user.state);
        $('#zip-code').val(GlobalVariables.settings.user.zip_code);
        $('#notes').val(GlobalVariables.settings.user.notes);
        $('#timezone').val(GlobalVariables.settings.user.timezone);
        $('#username').val(GlobalVariables.settings.user.settings.username);
        $('#password, #retype-password').val('');
        $('#calendar-view').val(GlobalVariables.settings.user.settings.calendar_view);
        $('#user-notifications').prop('checked', Boolean(Number(GlobalVariables.settings.user.settings.notifications)));

        // The provider record fields (mode + zoom link) only show on the provider page: the
        // users-page editor stores them as regular user_settings columns.
        if (GlobalVariables.userSlug === 'provider') {
            $('#provider-appointment-mode').val(
                GlobalVariables.settings.user.settings.appointment_mode || 'BOTH');
            $('#provider-zoom-link').val(GlobalVariables.settings.user.settings.zoom_link || '');
        }

        // Set default settings helper.
        settings = new SystemSettings();

        if (defaultEventHandlers) {
            bindEventHandlers();
            var $link = $('#settings-page .nav li').not('.d-none').first().find('a');
            $link.tab('show');
        }

        // The settings page uses the common edit-save-abort workflow: everything starts in
        // the locked read state and the Edit button of the active pane enables the widgets
        // (the Cancel button restores the stored values without a request).
        exports.lockSettingsPane('#general', 'system_settings');
        exports.lockSettingsPane('#business-logic', 'system_settings');
        exports.lockSettingsPane('#legal-contents', 'system_settings');
        exports.lockSettingsPane('#current-user', 'user_settings');

        Backend.placeFooterToBottom();
    };

    /**
     * Lock one settings pane into its read state.
     *
     * The common edit-save-abort workflow keeps every input inert until the Edit button of
     * the pane enables it. Widgets that act outside the workflow (the API token and the
     * calendar feed tooling) are excluded via their `data-keep-enabled` attribute, so the
     * generate/revoke handlers always stay available.
     *
     * @param {String} paneId Selector or id of the tab pane.
     * @param {String} privilegeArea The privilege area that unlocks the edit state.
     */
    exports.lockSettingsPane = function (paneId, privilegeArea) {
        var $pane = $(paneId);
        var $paneChildren = $pane.find('input, select, textarea, button');

        if (GlobalVariables.user.privileges[privilegeArea].edit === false) {
            $paneChildren.prop('readonly', true).prop('disabled', true);

            // Tools outside the workflow (e.g. the calendar feed and API token buttons)
            // remain available even for read-only roles.
            $pane.find('[data-keep-enabled]').prop('readonly', false).prop('disabled', false);

            $pane.find('.edit-settings').hide();
            $pane.find('.btn-group[data-display]').attr('data-display', 'none');

            return;
        }

        $paneChildren.prop('readonly', true).prop('disabled', true);

        // Sticky-locked widgets (like the calendar feed and API token tooling) stay
        // available in the read state, everything else keeps waiting for the Edit button.
        $pane.find('[data-keep-enabled]').prop('readonly', false).prop('disabled', false);

        // The workflow-own edit button stays clickable in the read state, the joint
        // save/cancel group stays hidden through its data-display state.
        $pane.find('.edit-settings').show().prop('disabled', false);
        $pane.find('.btn-group[data-display]').attr('data-display', 'none');

        exports.toggleLegalContentsEditors(paneId, false);
    };

    /**
     * Enable one settings pane for editing.
     */
    exports.editSettingsPane = function ($pane) {
        var paneId = $pane.attr('id');

        $pane.find('input, select, textarea, button')
            .not('[data-keep-enabled]')
            .prop('readonly', false).prop('disabled', false);

        // The trumbowyg editors need the vendored enable call (see the lock function).
        exports.toggleLegalContentsEditors(paneId, true);

        // The workflow toggle moves the attribute instead of inline styles: the group
        // keeps its inline-flex layout and the internal button radii work without gaps.
        $pane.find('.btn-group[data-display]').attr('data-display', 'inline-flex');
        $pane.find('.edit-settings').hide();
    };

    /**
     * Toggle the trumbowyg editors of the legal contents pane.
     *
     * The visual editors rebuild their own toolbar and editing surface, so the pane wide
     * readonly sweep cannot reach their contenteditable containers: the lock and the edit
     * state have to address the vendored API directly.
     */
    exports.toggleLegalContentsEditors = function (paneId, enabled) {
        // The lock method receives the pane selector, the edit method the pane id.
        if (String(paneId).replace('#', '') !== 'legal-contents') {
            return;
        }

        // The vendored wrapper processes exactly one element per call, so the editors
        // are addressed individually.
        ['#cookie-notice-content', '#terms-and-conditions-content', '#privacy-policy-content']
            .forEach(function (id) {
                $(id).trumbowyg(enabled ? 'enable' : 'disable');
            });
    };

    /**
     * Restore the stored values of one settings pane and lock it again (abort path).
     */
    exports.restoreSettingsPane = function ($pane) {
        var paneId = $pane.attr('id');

        switch (paneId) {
            case 'general':
                resetGeneralPane();
                break;
            case 'business-logic':
                resetBusinessLogicPane();
                break;
            case 'legal-contents':
                resetLegalContentsPane();
                break;
            case 'current-user':
                resetCurrentUserPane();
                break;
        }

        exports.lockSettingsPane('#' + paneId, paneId === 'current-user' ? 'user_settings' : 'system_settings');
    };

    /**
     * Restore the values of the general pane from the stored settings (abort path).
     */
    function resetGeneralPane() {
        GlobalVariables.settings.system.forEach(function (setting) {
            var $field = $('#general').find('input[data-field="' + setting.name + '"], '
                + 'select[data-field="' + setting.name + '"]');

            if (!$field.length) {
                return;
            }

            $field.val(setting.value);
        });
    }

    /**
     * Restore the values of the business logic pane from the stored settings (abort path).
     */
    function resetBusinessLogicPane() {
        var bookAdvanceTimeout = (GlobalVariables.settings.system.find(function (setting) {
            return setting.name === 'book_advance_timeout';
        }) || {value: ''}).value;

        $('#book-advance-timeout').val(bookAdvanceTimeout);

        if (GlobalVariables.userSlug === 'provider') {
            var userPlan = $.parseJSON(GlobalVariables.settings.user.settings.working_plan || '{}') || {};
            var userPlanExceptions =
                $.parseJSON(GlobalVariables.settings.user.settings.working_plan_exceptions || '{}') || {};

            exports.wp.setup(userPlan);
            exports.wp.setupWorkingPlanExceptions(userPlanExceptions);
            exports.wp.displayWeekdayModes(GlobalVariables.settings.user.settings.weekday_modes);
            exports.wp.syncWeekdayModeStates();

            return;
        }

        var companyPlan = GlobalVariables.settings.system.find(function (setting) {
            return setting.name === 'company_working_plan';
        });

        exports.wp.setup(companyPlan ? $.parseJSON(companyPlan.value || '{}') || {} : {});
    }

    /**
     * Restore the values of the legal contents pane from the stored settings (abort path).
     */
    function resetLegalContentsPane() {
        GlobalVariables.settings.system.forEach(function (setting) {
            switch (setting.name) {
                case 'display_cookie_notice':
                    $('#display-cookie-notice').prop('checked', Boolean(Number(setting.value)));
                    break;
                case 'cookie_notice_content':
                    $('#cookie-notice-content').trumbowyg('html', setting.value);
                    break;
                case 'display_terms_and_conditions':
                    $('#display-terms-and-conditions').prop('checked', Boolean(Number(setting.value)));
                    break;
                case 'terms_and_conditions_content':
                    $('#terms-and-conditions-content').trumbowyg('html', setting.value);
                    break;
                case 'display_privacy_policy':
                    $('#display-privacy-policy').prop('checked', Boolean(Number(setting.value)));
                    break;
                case 'privacy_policy_content':
                    $('#privacy-policy-content').trumbowyg('html', setting.value);
                    break;
                case 'record_retention_days':
                    $('#data-retention-days').val(setting.value);
                    break;
            }
        });
    }

    /**
     * Restore the values of the current user pane (abort path).
     */
    function resetCurrentUserPane() {
        resetCurrentUserFields();

        if (GlobalVariables.userSlug === 'provider') {
            var userPlan = $.parseJSON(GlobalVariables.settings.user.settings.working_plan || '{}') || {};
            var userPlanExceptions =
                $.parseJSON(GlobalVariables.settings.user.settings.working_plan_exceptions || '{}') || {};

            exports.wp.setup(userPlan);
            exports.wp.setupWorkingPlanExceptions(userPlanExceptions);
            exports.wp.displayWeekdayModes(GlobalVariables.settings.user.settings.weekday_modes);
            exports.wp.syncWeekdayModeStates();
        }
    }

    /**
     * Refill the personal and login fields of the current user pane from the stored record.
     */
    function resetCurrentUserFields() {
        $('#user-id').val(GlobalVariables.settings.user.id);
        $('#first-name').val(GlobalVariables.settings.user.first_name);
        $('#last-name').val(GlobalVariables.settings.user.last_name);
        $('#email').val(GlobalVariables.settings.user.email);
        $('#mobile-number').val(GlobalVariables.settings.user.mobile_number);
        $('#phone-number').val(GlobalVariables.settings.user.phone_number);
        $('#address').val(GlobalVariables.settings.user.address);
        $('#city').val(GlobalVariables.settings.user.city);
        $('#state').val(GlobalVariables.settings.user.state);
        $('#zip-code').val(GlobalVariables.settings.user.zip_code);
        $('#notes').val(GlobalVariables.settings.user.notes);
        $('#timezone').val(GlobalVariables.settings.user.timezone);
        $('#username').val(GlobalVariables.settings.user.settings.username);
        $('#password, #retype-password').val('');
        $('#calendar-view').val(GlobalVariables.settings.user.settings.calendar_view);
        $('#user-notifications').prop('checked',
            Boolean(Number(GlobalVariables.settings.user.settings.notifications)));

        if (GlobalVariables.userSlug === 'provider') {
            $('#provider-appointment-mode').val(
                GlobalVariables.settings.user.settings.appointment_mode || 'BOTH');
            $('#provider-zoom-link').val(GlobalVariables.settings.user.settings.zoom_link || '');
        }
    }

    /**
     * Bind the backend/settings default event handlers.
     *
     * This method depends on the backend/settings html, so do not use this method on a different page.
     */
    function bindEventHandlers() {
        exports.wp.bindEventHandlers();

        /**
         * Event: Edit Settings "Click"
         *
         * Enables the inputs of the active pane (the edit-save-abort workflow of the
         * settings page).
         */
        $('#settings-page').on('click', '.edit-settings', function () {
            exports.editSettingsPane($(this).closest('.tab-pane'));
        });

        /**
         * Event: Cancel Settings "Click"
         *
         * Restores the stored values of the active pane and locks it again.
         */
        $('#settings-page').on('click', '.cancel-settings', function () {
            exports.restoreSettingsPane($(this).closest('.tab-pane'));
        });

        /**
         * Event: Reset Working Plan "Click"
         *
         * Applies the company default plan (the same reference the users page offers) to
         * the self service plan editor; nothing posts until the user presses Save.
         */
        $('#settings-page').on('click', '#reset-working-plan', function () {
            var companyPlan = $.parseJSON(GlobalVariables.companyWorkingPlan || '{}') || {};

            exports.wp.setup(companyPlan);
            exports.wp.setupWorkingPlanExceptions({});
            exports.wp.displayWeekdayModes(null);
            exports.wp.syncWeekdayModeStates();
            exports.wp.timepickers(false);
        });

        /**
         * Event: Tab "Click"
         *
         * Change the visible tab contents.
         */
        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            var href = $(this).attr('href');

            if (href === '#general') {
                settings = new SystemSettings();
            } else if (href === '#business-logic') {
                settings = new SystemSettings();
            } else if (href === '#legal-contents') {
                settings = new SystemSettings();
            } else if (href === '#current-user') {
                settings = new UserSettings();
            }

            Backend.placeFooterToBottom();
        });

        /**
         * Event: Save Settings Button "Click"
         *
         * Store the setting changes into the database.
         */
        /**
         * Event: Retention (Legal Contents) Input "Keydown"
         *
         * Prevents the form submit (which would jump back to the general tab) and triggers
         * the save instead, so pressing enter in the retention field stores the value.
         */
        $('#legal-contents').on('keydown', 'input, textarea', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();

                if (!$(this).is('textarea')) {
                    $('.legal-contents .save-settings').trigger('click');
                }
            }
        });

        $('.save-settings').on('click', function () {
            var data = settings.get();
            settings.save(data);
        });

        /**
         * Event: Username "Focusout"
         *
         * When the user leaves the username input field we will need to check if the username
         * is not taken by another record in the system. Usernames must be unique.
         */
        $('#username').focusout(function () {
            var $input = $(this);

            if ($input.prop('readonly') === true || $input.val() === '') {
                return;
            }

            var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_validate_username';

            var data = {
                csrfToken: GlobalVariables.csrfToken,
                username: $input.val(),
                user_id: $input.parents().eq(2).find('#user-id').val()
            };

            $.post(url, data)
                .done(function (response) {
                    if (response === 'false') {
                        $input.closest('.form-group').addClass('has-error');
                        Backend.displayNotification(EALang.username_already_exists);
                        $input.attr('already-exists', 'true');
                    } else {
                        $input.closest('.form-group').removeClass('has-error');
                        $input.attr('already-exists', 'false');
                    }
                });
        });

        /**
         * Event: Apply Global Working Plan
         */
        $('#apply-global-working-plan').on('click', function () {
            var buttons = [
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message-box').dialog('close');
                    }
                },
                {
                    text: 'OK',
                    click: function () {
                        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_apply_global_working_plan';

                        var data = {
                            csrfToken: GlobalVariables.csrfToken,
                            working_plan: JSON.stringify(exports.wp.get())
                        };

                        $.post(url, data)
                            .done(function () {
                                Backend.displayNotification(EALang.working_plans_got_updated);
                            })
                            .always(function () {
                                $('#message-box').dialog('close');
                            });
                    }
                }
            ];

            GeneralFunctions.displayMessageBox(EALang.working_plan, EALang.overwrite_existing_working_plans, buttons);
        });

        /**
         * Event: Purge Records Button "Click"
         *
         * Runs the maintenance cleanup with the configured data retention period. A dry run is
         * implied by the dialog until the user confirms the purge.
         */
        $('#purge-records').on('click', function () {
            runMaintenancePurge(true);
        });
    }

    /**
     * Run the maintenance purge (post data with the requested mode).
     *
     * @param {Boolean} dryRun When TRUE nothing is deleted and only the number of affected
     * records is reported.
     */
    function runMaintenancePurge(dryRun) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_maintain';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            dry_run: dryRun ? '1' : '0'
        };

        $.post(url, data)
            .done(function (response) {
                var message = (dryRun ? EALang.maintenance_dry_run : EALang.maintenance_done)
                    + ' — '
                    + EALang.maintenance_result
                        .replace('{$appointments}', response.appointments)
                        .replace('{$provider_blocks}', response.provider_blocks)
                        .replace('{$customers}', response.customers);

                if (dryRun) {
                    // Show the dry-run numbers and ask whether the cleanup should run now.
                    GeneralFunctions.displayMessageBox(EALang.purge_records, message, [
                        {
                            text: EALang.purge_records,
                            click: function () {
                                $('#message-box').dialog('close');

                                runMaintenancePurge(false);
                            }
                        },
                        {
                            text: EALang.cancel,
                            click: function () {
                                $('#message-box').dialog('close');
                            }
                        }
                    ]);
                } else {
                    Backend.displayNotification(message);
                }
            })
            .fail(function (jqXHR) {
                Backend.displayNotification(jqXHR.responseJSON && jqXHR.responseJSON.message
                    ? jqXHR.responseJSON.message
                    : EALang.oops_something_went_wrong);
            });
    }

})(window.BackendSettings);
