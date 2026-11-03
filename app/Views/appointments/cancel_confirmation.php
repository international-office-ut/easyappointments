<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#A51E37">

    <title><?= lang('cancel_appointment_title') ?> | Easy!Appointments</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/frontend.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">
</head>

<body>
<div id="main" class="container">
    <div class="row wrapper">
        <div id="message-frame" class="col-12 border my-auto frame-container">
            <div>
                <img id="message-icon" src="<?= base_url('assets/img/success.png') ?>" alt="warning">
            </div>

            <h3><?= lang('cancel_appointment_title') ?></h3>

            <p><?= lang('cancel_appointment_hint') ?></p>

            <dl class="text-left">
                <dt><?= lang('service') ?></dt>
                <dd><?= esc($service_name) ?></dd>

                <dt><?= lang('provider') ?></dt>
                <dd><?= esc($provider_name) ?></dd>

                <dt><?= lang('start') ?></dt>
                <dd><?= esc($appointment_start) ?> (<?= esc($timezone_label) ?>)</dd>

                <dt><?= lang('end') ?></dt>
                <dd><?= esc($appointment_end) ?></dd>
            </dl>

            <form id="cancel-appointment-form" method="post"
                  action="<?= site_url('appointments/cancel/' . $appointment_hash) ?>">

                <input type="hidden" name="csrfToken" value="<?= csrf_hash() ?>"/>

                <div class="form-group">
                    <label for="cancel-reason"><?= lang('reason') ?></label>
                    <textarea id="cancel-reason" name="cancel_reason" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-warning"><?= lang('cancel_2') ?></button>

                <a class="btn btn-secondary"
                   href="<?= site_url('appointments/index/' . $appointment_hash) ?>"><?= lang('back') ?></a>
            </form>

            <div class="mt-2">
                <small>
                    Powered by
                    <span>Easy!Appointments</span>
                </small>
            </div>
        </div>
    </div>
</div>

</body>
</html>
