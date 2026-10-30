<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#A51E37">
    <title><?= lang('login') ?> | Easy!Appointments</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/login.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">

    <script src="<?= asset_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/datejs/date.min.js') ?>"></script>

    <script>
        var GlobalVariables = {
            csrfToken: <?= json_encode($this->security->get_csrf_hash()) ?>,
            baseUrl: <?= json_encode($base_url) ?>,
            destUrl: <?= json_encode($dest_url) ?>,
            shibCandidates: <?= json_encode($shib_candidates ?? []) ?>,
            shibEnabled: <?= json_encode($shib_enabled ?? FALSE) ?>,
            passwordLogin: <?= json_encode($password_login ?? TRUE) ?>,
            AJAX_SUCCESS: 'SUCCESS',
            AJAX_FAILURE: 'FAILURE'
        };

        var EALang = <?= json_encode($this->lang->language) ?>;

        var availableLanguages = <?= json_encode(config('available_languages')) ?>;

        $(function () {
            GeneralFunctions.enableLanguageSelection($('#select-language'));
        });
    </script>
</head>
<body>
<div id="login-frame" class="frame-container">
    <h2><?= lang('backend_section') ?></h2>
    <p><?= lang('you_need_to_login') ?></p>
    <hr>
    <?php if ( ! empty($shib_error)): ?>
        <div class="alert alert-danger"><?= lang('shib_user_not_found') ?></div>
    <?php endif; ?>
    <?php if (empty($password_login)): ?>
        <div class="alert alert-info"><?= lang('sso_login_only') ?></div>
    <?php endif; ?>
    <div class="alert d-none"></div>
    <form id="login-form">
        <div class="form-group" id="login-credentials"
             style="display: <?= (empty($shib_candidates) && ! empty($password_login)) ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label for="username"><?= lang('username') ?></label>
                <input type="text" id="username"
                       placeholder="<?= lang('enter_username_here') ?>"
                       class="form-control"/>
            </div>
            <div class="form-group">
                <label for="password"><?= lang('password') ?></label>
                <input type="password" id="password"
                       placeholder="<?= lang('enter_password_here') ?>"
                       class="form-control"/>
            </div>
        </div>

        <div class="form-group" id="login-role-wrapper"
             style="display: <?= empty($shib_candidates) ? 'none' : 'block' ?>;">
            <label for="login-role"><?= lang('select_role') ?></label>
            <select id="login-role" class="form-control">
                <?php $roles_labels = ['admin' => 'admins', 'provider' => 'providers', 'secretary' => 'secretaries']; ?>
                <?php foreach ($shib_candidates ?? [] as $candidate): ?>
                    <option value="<?= (int)$candidate['user_id'] ?>">
                        <?= lang($roles_labels[$candidate['role_slug']] ?? $candidate['role_slug']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="login-continue" class="btn btn-primary btn-block mt-2">
                <i class="fas fa-sign-in-alt mr-2"></i>
                <?= lang('login') ?>
            </button>
        </div>

        <div class="form-group" id="login-submit-wrapper"
             style="display: <?= (empty($shib_candidates) && ! empty($password_login)) ? 'block' : 'none' ?>;">
            <button type="submit" id="login" class="btn btn-primary btn-block mt-2">
                <i class="fas fa-sign-in-alt mr-2"></i>
                <?= lang('login') ?>
            </button>
        </div>

        <?php if ( ! empty($shib_enabled)): ?>
            <div class="form-group text-center shibboleth-login-wrapper">
                <a href="<?= site_url('user/shib') ?>" id="shibboleth-login"
                   class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-university mr-2"></i>
                    <?= lang('login_with_shibboleth') ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ( ! empty($password_login)): ?>
            <a href="<?= site_url('user/forgot_password') ?>" class="forgot-password">
                <?= lang('forgot_your_password') ?></a>
        <?php endif; ?>
        |
        <span id="select-language" class="badge badge-success">
              <?= ucfirst(config('language')) ?>
            </span>

        <div class="mt-4">
            <small>
                Powered by
                <a href="https://easyappointments.org">Easy!Appointments</a>
            </small>
        </div>
    </form>
</div>

<script src="<?= asset_url('assets/ext/fontawesome/js/fontawesome.min.js') ?>"></script>
<script src="<?= asset_url('assets/ext/fontawesome/js/solid.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/polyfill.js') ?>"></script>
<script src="<?= asset_url('assets/js/general_functions.js') ?>"></script>
<script src="<?= asset_url('assets/js/login.js') ?>"></script>
</body>
</html>
