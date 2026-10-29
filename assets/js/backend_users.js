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

window.BackendUsers = window.BackendUsers || {};

/**
 * Backend Users
 *
 * This module handles the js functionality of the users backend page. It uses three other
 * classes (defined below) in order to handle the admin, provider and secretary record types.
 *
 * @module BackendUsers
 */
(function (exports) {

    'use strict';

    /**
     * Minimum Password Length
     *
     * @type {Number}
     */
    exports.MIN_PASSWORD_LENGTH = 7;

    /**
     * Identity attach state per user tab. Values: 'unknown' (nothing typed), 'pending'
     * (identity lookup request in flight), 'sibling' (email matches an existing identity in a
     * different role) or 'new' (genuinely new person, password required).
     *
     * @type {Object}
     */
    exports.identityAttach = {
        admin: 'unknown',
        provider: 'unknown',
        secretary: 'unknown'
    };

    /**
     * Number of silent AJAX requests in flight (e.g. the identity attach lookups). While it
     * is greater than zero the global loading spinner is suppressed.
     *
     * @type {Number}
     */
    exports.silentAjaxCount = 0;
    exports.silentAjaxBegin = function () {
        exports.silentAjaxCount++;
    };
    exports.silentAjaxEnd = function () {
        if (exports.silentAjaxCount > 0)
        {
            exports.silentAjaxCount--;
        }
    };

    /**
     * Contains the current tab record methods for the page.
     *
     * @type {AdminsHelper|ProvidersHelper|SecretariesHelper}
     */
    var helper = {};

    /**
     * Use this class instance for performing actions on the working plan.
     *
     * @type {WorkingPlan}
     */
    exports.wp = {};

    /**
     * Initialize the backend users page.
     *
     * @param {Boolean} defaultEventHandlers (OPTIONAL) Whether to bind the default event handlers.
     */
    exports.initialize = function (defaultEventHandlers) {
        defaultEventHandlers = defaultEventHandlers || true;

        exports.wp = new WorkingPlan({weekdayModes: true});
        exports.wp.bindEventHandlers();

        // Instantiate default helper object (admin).
        helper = new ProvidersHelper();
        helper.resetForm();
        helper.filter('');
        helper.bindEventHandlers();

        // Fill the services and providers list boxes.
        GlobalVariables.services.forEach(function (service) {
            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': service.id,
                                'prop': {
                                    'disabled': true
                                }
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': service.name,
                                'for': service.id
                            })
                        ]
                    })
                ]
            })
                .appendTo('#provider-services');
        });

        GlobalVariables.providers.forEach(function (provider) {
            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': provider.id
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': provider.first_name + ' ' + provider.last_name,
                                'for': provider.id
                            })
                        ]
                    })
                ]
            })
                .appendTo('#secretary-providers');
        });

        // Bind event handlers.
        if (defaultEventHandlers) {
            bindEventHandlers();
        }
    };

    /**
     * Binds the default backend users event handlers. Do not use this method on a different
     * page because it needs the backend users page DOM.
     */
    function bindEventHandlers() {
        /**
         * Event: Page Tab Button "Click"
         *
         * Changes the displayed tab.
         */
        $('#users-page > .nav-pills a[data-toggle="tab"]').on('shown.bs.tab', function () {
            if ($(this).parents('.switch-view').length) {
                return; // Do not proceed if this was the sub navigation.
            }

            if (helper) {
                helper.unbindEventHandlers();
            }

            if ($(this).attr('href') === '#admins') {
                helper = new AdminsHelper();
            } else if ($(this).attr('href') === '#providers') {
                helper = new ProvidersHelper();
            } else if ($(this).attr('href') === '#secretaries') {
                helper = new SecretariesHelper();

                // Update the list with the all the available providers.
                var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_providers';

                var data = {
                    csrfToken: GlobalVariables.csrfToken,
                    key: ''
                };

                $.post(url, data)
                    .done(function (response) {
                        GlobalVariables.providers = response;

                        $('#secretary-providers').empty();

                        GlobalVariables.providers.forEach(function (provider) {
                            $('<div/>', {
                                'class': 'checkbox',
                                'html': [
                                    $('<div/>', {
                                        'class': 'checkbox form-check',
                                        'html': [
                                            $('<input/>', {
                                                'class': 'form-check-input',
                                                'type': 'checkbox',
                                                'data-id': provider.id,
                                                'prop': {
                                                    'disabled': true
                                                }
                                            }),
                                            $('<label/>', {
                                                'class': 'form-check-label',
                                                'text': provider.first_name + ' ' + provider.last_name,
                                                'for': provider.id
                                            }),
                                        ]
                                    })
                                ]
                            })
                                .appendTo('#secretary-providers');
                        });
                    });
            }

            helper.resetForm();
            helper.filter('');
            helper.bindEventHandlers();
            $('.filter-key').val('');
            Backend.placeFooterToBottom();
        });

        /**
         * Event: Email "Input"/"Focusout" — Attach Existing Identity
         *
         * When adding a new role row for a person that already exists under a different role
         * (same email), the common fields of that identity get pre-filled and the login name
         * is inherited, so the attach flow does not need password or duplicate data entries.
         *
         * The identity state is tracked in BackendUsers.identityAttach (input events run an
         * early lookup with a debounce, focusout triggers a final lookup), so that the save
         * validators always see the resolved state without a race condition.
         */
        function checkIdentityAttach(prefix) {
            var $email = $('#' + prefix + '-email');
            var $passwordFields = $('#' + prefix + '-password, #' + prefix + '-password-confirm');

            if (BackendUsers.identityAttach[prefix] === 'sibling') {
                $passwordFields.removeClass('required').val('');
            } else if (BackendUsers.identityAttach[prefix] === 'new') {
                $passwordFields.addClass('required');
            }
        }

        function requestIdentityInfo(prefix) {
            var $email = $('#' + prefix + '-email');
            var $parentForm = $email.closest('#' + prefix + '-page, #users-page')
                .closest('.record-details');
            var userId = $('#' + prefix + '-id').val();
            var slugMap = {
                'admin': Backend.DB_SLUG_ADMIN,
                'provider': Backend.DB_SLUG_PROVIDER,
                'secretary': Backend.DB_SLUG_SECRETARY
            };

            if ($email.val() === '' || userId
                || BackendUsers.identityAttach[prefix] === 'pending')
            {
                return;
            }

            BackendUsers.identityAttach[prefix] = 'pending';

            BackendUsers.silentAjaxBegin();

            $.post(GlobalVariables.baseUrl + '/index.php/backend_api/ajax_get_identity_info', {
                csrfToken: GlobalVariables.csrfToken,
                email: $email.val(),
                role_slug: slugMap[prefix]
            })
                .always(function () {
                    BackendUsers.silentAjaxEnd();
                })
                .done(function (response) {
                var $passwordFields = $('#' + prefix + '-password, #' + prefix + '-password-confirm');

                if (!response || !response.exists) {
                    // New email → a password is required for a genuinely new identity.
                    BackendUsers.identityAttach[prefix] = 'new';
                    $passwordFields.addClass('required');
                    return;
                }

                BackendUsers.identityAttach[prefix] = 'sibling';

                var person = response.user;

                var fieldPrefixes = {
                    'first_name': prefix + '-first-name',
                    'last_name': prefix + '-last-name',
                    'phone_number': prefix + '-phone-number',
                    'mobile_number': prefix + '-mobile-number',
                    'address': prefix + '-address',
                    'city': prefix + '-city',
                    'state': prefix + '-state',
                    'zip_code': prefix + '-zip-code',
                    'timezone': prefix + '-timezone',
                    'notes': prefix + '-notes'
                };

                Object.keys(fieldPrefixes).forEach(function (fieldName) {
                    var $field = $('#' + fieldPrefixes[fieldName]);

                    // Only fill empty inputs, so that already typed values are not lost.
                    if ($field.length > 0 && ($field.val() === '' || $field.val() === null)) {
                        $field.val(person[fieldName]);
                    }
                });

                var $usernameField = $('#' + prefix + '-username');

                if ($usernameField.val() === '' && person.login_name) {
                    $usernameField.val(person.login_name);
                }

                checkIdentityAttach(prefix);

                var roleLabel = EALang[prefix + 's'] || person.role_slug;
                var fullName = (person.first_name || '') + ' ' + (person.last_name || '');

                var $formMessage = $('#' + prefix + '-email').closest('.record-details')
                    .find('.form-message');

                $formMessage
                    .text(EALang.identity_attach_hint
                        .replace('{$role}', roleLabel)
                        .replace('{$name}', fullName.trim()))
                    .addClass('alert-success')
                    .show();
            });
        }

        var identityLookupTimers = {admin: null, provider: null, secretary: null};

        $('#admin-email, #provider-email, #secretary-email').on('input', function () {
            var prefix = String(this.id).replace('-email', '');

            if (identityLookupTimers[prefix]) {
                clearTimeout(identityLookupTimers[prefix]);
            }

            identityLookupTimers[prefix] = setTimeout(function () {
                requestIdentityInfo(prefix);
            }, 400);
        });

        $('#admin-email, #provider-email, #secretary-email').focusout(function () {
            var prefix = String(this.id).replace('-email', '');

            if (identityLookupTimers[prefix]) {
                clearTimeout(identityLookupTimers[prefix]);
                identityLookupTimers[prefix] = null;
            }

            requestIdentityInfo(prefix);
        });

        /**
         * Event: Admin, Provider, Secretary Username "Focusout"
         *
         * When the user leaves the username input field we will need to check if the username
         * is not taken by another record in the system. Usernames must be unique.
         */
        $('#admin-username, #provider-username, #secretary-username').focusout(function () {
            var $input = $(this);

            if ($input.prop('readonly') === true || $input.val() === '') {
                return;
            }

            var userId = $input.parents().eq(2).find('.record-id').val();

            if (!userId) {
                return;
            }

            var $parentTab = $input.parents().eq(3);
            var email = $parentTab.find('#provider-email, #admin-email, #secretary-email').val() || '';

            var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_validate_username';

            var data = {
                csrfToken: GlobalVariables.csrfToken,
                username: $input.val(),
                email: email,
                user_id: userId
            };

            $.post(url, data)
                .done(function (response) {
                    if (response === 'false') {
                        $input.closest('.form-group').addClass('has-error');
                        $input.attr('already-exists', 'true');
                        $input.parents().eq(3).find('.form-message').text(EALang.username_already_exists);
                        $input.parents().eq(3).find('.form-message').show();
                    } else {
                        $input.closest('.form-group').removeClass('has-error');
                        $input.attr('already-exists', 'false');
                        if ($input.parents().eq(3).find('.form-message').text() === EALang.username_already_exists) {
                            $input.parents().eq(3).find('.form-message').hide();
                        }
                    }
                });
        });
    }

})(window.BackendUsers);
