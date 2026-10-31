$(function () {
    'use strict';

    var $loginForm = $('#login-form');
    var $roleWrapper = $('#login-role-wrapper');
    var $roleSelect = $('#login-role');
    var $loginButton = $('#login');

    /**
     * Hide the role dropdown and restore the plain login state.
     */
    function hideRoleWrapper() {
        $roleWrapper.hide();
        $roleSelect.empty();
        $loginButton.prop('disabled', false).show();
    }

    /**
     * Login Form "Submit"
     *
     * Make an ajax call to the server and check whether the user's credentials are right.
     * If they match multiple roles, the inline role dropdown appears on the same screen,
     * otherwise the login proceeds (or an error message appears).
     */
    function onLoginFormSubmit(event) {
        event.preventDefault();

        var url = GlobalVariables.baseUrl + '/index.php/user/ajax_check_login';

        var data = {
            'csrfToken': GlobalVariables.csrfToken,
            'username': $('#username').val(),
            'password': $('#password').val()
        };

        var $alert = $('.alert');

        $alert.addClass('d-none');

        $.post(url, data)
            .done(function (response) {
                if (response && response.multi) {
                    // The identity holds multiple roles: show the inline role dropdown, so the
                    // user can continue with the desired role from the same screen.
                    var roleLabels = {
                        'admin': EALang.admins || 'Administrator',
                        'provider': EALang.providers || 'Provider',
                        'secretary': EALang.secretaries || 'Secretary'
                    };

                    $roleSelect.empty();

                    response.candidates.forEach(function (candidate) {
                        $roleSelect.append(new Option(
                            roleLabels[candidate.role_slug] || candidate.role_slug,
                            candidate.user_id
                        ));
                    });

                    $roleWrapper.show();
                    $loginButton.prop('disabled', true).hide();
                } else if (response === GlobalVariables.AJAX_SUCCESS) {
                    hideRoleWrapper();
                    window.location.href = GlobalVariables.destUrl;
                } else {
                    hideRoleWrapper();

                    $alert.text(EALang['login_failed']);
                    $alert
                        .removeClass('d-none alert-danger alert-success')
                        .addClass('alert-danger');
                }
            })
            .fail(function (jqXHR) {
                hideRoleWrapper();

                $alert.text(jqXHR.responseJSON && jqXHR.responseJSON.message
                    ? jqXHR.responseJSON.message
                    : EALang['login_failed']);
                $alert
                    .removeClass('d-none alert-danger alert-success')
                    .addClass('alert-danger');
            });
    }

    /**
     * Role "Continue" Button "Click"
     *
     * Completes the login with the selected role from the verified candidates.
     */
    $('#login-continue').on('click', function () {
        var user_id = $roleSelect.val();

        if (!user_id) {
            return;
        }

        $.post(GlobalVariables.baseUrl + '/index.php/user/ajax_select_login_role', {
            'csrfToken': GlobalVariables.csrfToken,
            'user_id': user_id
        })
            .done(function (response) {
                if (response === GlobalVariables.AJAX_SUCCESS) {
                    window.location.href = GlobalVariables.destUrl;
                }
            })
            .fail(function (jqXHR) {
                var $alert = $('.alert');

                $alert.text(jqXHR.responseJSON && jqXHR.responseJSON.message
                    ? jqXHR.responseJSON.message
                    : EALang['login_failed']);
                $alert
                    .removeClass('d-none alert-danger alert-success')
                    .addClass('alert-danger');
            });
    });

    /**
     * Username / Password "Input" — reset the role selection when credentials change.
     */
    $('#username, #password').on('input', function () {
        if ($roleWrapper.is(':visible')) {
            hideRoleWrapper();
        }
    });

    /**
     * Admin Access Request Button "Click" (SSO-only deployments)
     *
     * Requests a single-use access link for the administrator account. The request never
     * reveals whether the username and email match an account.
     */
    $('#admin-access-button').on('click', function () {
        var url = GlobalVariables.baseUrl + '/index.php/user/ajax_forgot_password';
        var $alert = $('.alert');

        $alert.addClass('d-none');
        $(this).prop('disabled', true);

        $.post(url, {
            'csrfToken': GlobalVariables.csrfToken,
            'username': $('#admin-access-username').val(),
            'email': $('#admin-access-email').val()
        })
            .done(function () {
                $alert
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success');
                $alert.text(EALang['account_recovery_email_sent']);
                $('#admin-access-button').prop('disabled', false);
            })
            .fail(function (jqXHR) {
                $alert.text(jqXHR.responseJSON && jqXHR.responseJSON.message
                    ? jqXHR.responseJSON.message
                    : EALang['admin_access']);
                $alert
                    .removeClass('d-none alert-success')
                    .addClass('alert-danger');
                $('#admin-access-button').prop('disabled', false);
            });
    });

    $loginForm.on('submit', onLoginFormSubmit);
});
