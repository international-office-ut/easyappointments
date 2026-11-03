$(function () {
    'use strict';

    var $form = $('form');

    /**
     * Event: Login Button "Click"
     *
     * Make an HTTP request to the server and check whether the user's credentials are right. If yes then redirect the
     * user to the destination page, otherwise display an error message.
     */
    function onFormSubmit(event) {
        event.preventDefault();

        var url = GlobalVariables.baseUrl + '/index.php/user/ajax_forgot_password';

        var data = {
            'csrfToken': GlobalVariables.csrfToken,
            'username': $('#username').val(),
            'email': $('#email').val()
        };

        var $alert = $('.alert');

        $alert.addClass('d-none');
        $('#get-new-password').prop('disabled', true);

        $.post(url, data)
            .done(function (response) {
                // The server responds identically for existing and unknown accounts, so that
                // the responses cannot be used for account enumeration.
                $alert.removeClass('d-none alert-danger alert-success');
                $('#get-new-password').prop('disabled', false);
                if (response === GlobalVariables.AJAX_SUCCESS) {
                    $alert.addClass('alert-success');
                    $alert.text(EALang['account_recovery_email_sent']);
                } else {
                    $alert.addClass('alert-danger');
                    $alert.text(EALang['account_recovery_email_sent']);
                }
            });
    }


    $form.on('submit', onFormSubmit);
});
