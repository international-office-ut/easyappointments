$(function () {
    'use strict';

    var $loginForm = $('#login-form');
    var $rolePickerButtons = $('#role-picker-buttons');

    /**
     * Login Button "Click"
     *
     * Make an ajax call to the server and check whether the user's credentials are right.
     * If yes then redirect him to his desired page, otherwise display a message.
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
        var $rolePicker = $('#role-picker');
        var $loginFrame = $('#login-frame');

        $alert.addClass('d-none');

        $.post(url, data)
            .done(function (response) {
                if (response && response.multi) {
                    // The identity holds multiple roles, let the user pick one to continue with.
                    var roleLabels = {
                        'admin': EALang.admins || 'Administrator',
                        'provider': EALang.providers || 'Provider',
                        'secretary': EALang.secretaries || 'Secretary'
                    };

                    $rolePickerButtons.empty();

                    response.candidates.forEach(function (candidate) {
                        var label = roleLabels[candidate.role_slug] || candidate.role_slug;

                        $rolePickerButtons.append(
                            $('<button/>', {
                                'type': 'button',
                                'class': 'btn btn-outline-secondary btn-block mt-2',
                                'data-user_id': candidate.user_id,
                                'text': label
                            }).on('click', function () {
                                $.post(GlobalVariables.baseUrl + '/index.php/user/ajax_select_login_role', {
                                    'csrfToken': GlobalVariables.csrfToken,
                                    'user_id': $(this).attr('data-user_id')
                                }).done(function (roleResponse) {
                                    if (roleResponse === GlobalVariables.AJAX_SUCCESS) {
                                        window.location.href = GlobalVariables.destUrl;
                                    }
                                });
                            })
                        );
                    });

                    $loginFrame.addClass('d-none');
                    $rolePicker.removeClass('d-none');
                } else if (response === GlobalVariables.AJAX_SUCCESS) {
                    window.location.href = GlobalVariables.destUrl;
                } else {
                    $alert.text(EALang['login_failed']);
                    $alert
                        .removeClass('d-none alert-danger alert-success')
                        .addClass('alert-danger');
                }
            });
    }

    $loginForm.on('submit', onLoginFormSubmit);
});
