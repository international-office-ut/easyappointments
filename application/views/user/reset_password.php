<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#A51E37">

    <title><?= lang('set_new_password') ?> | Easy!Appointments</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/forgot_password.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">
</head>
<body>
<div id="forgot-password-frame" class="frame-container">
    <h2><?= lang('set_new_password') ?></h2>
    <hr>
    <?php if ( ! empty($invalid)): ?>
        <div class="alert alert-danger"><?= lang('password_reset_error') ?></div>
    <?php endif; ?>
    <form method="post" action="<?= site_url('user/ajax_reset_password') ?>">
        <input type="hidden" name="csrfToken" value="<?= $this->security->get_csrf_hash() ?>"/>
        <input type="hidden" name="token" value="<?= html_escape($token) ?>"/>

        <div class="form-group">
            <label for="password"><?= lang('password') ?></label>
            <input type="password" id="password" name="password" class="form-control" required/>
        </div>
        <div class="form-group">
            <label for="password-confirm"><?= lang('retype_password') ?></label>
            <input type="password" id="password-confirm" name="password_confirm" class="form-control" required/>
        </div>

        <br>

        <button type="submit" class="btn btn-primary btn-large">
            <i class="fas fa-key mr-2"></i>
            <?= lang('save') ?>
        </button>

        <a href="<?= site_url('user/login') ?>" class="user-login">
            <?= lang('go_to_login') ?></a>
    </form>

    <div class="mt-4">
        <small>
            Powered by
            <a href="https://easyappointments.org">Easy!Appointments</a>
        </small>
    </div>
</div>
<script src="<?= asset_url('assets/ext/fontawesome/js/fontawesome.min.js') ?>"></script>
<script src="<?= asset_url('assets/ext/fontawesome/js/solid.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/polyfill.js') ?>"></script>
</body>
</html>
