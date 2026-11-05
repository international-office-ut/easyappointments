<script src="<?= asset_url('assets/js/backend_settings_system.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_settings_user.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_settings.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_calendar_api.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/backend_calendar_unavailability_events_modal.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/working_plan.js') ?>"></script>
<script src="<?= asset_url('assets/js/working_plan_exceptions_modal.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.min.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
    var GlobalVariables = {
        csrfToken: <?= json_data(csrf_hash()) ?>,
        baseUrl: <?= json_data($base_url) ?>,
        dateFormat: <?= json_data($date_format) ?>,
        firstWeekday: <?= json_data($first_weekday); ?>,
        timeFormat: <?= json_data($time_format) ?>,
        userSlug: <?= json_data($role_slug) ?>,
        timezones: <?= json_data($timezones) ?>,
        companyWorkingPlan: <?= json_data($company_working_plan) ?>,
        settings: {
            system: <?= json_data($system_settings) ?>,
            user: <?= json_data($user_settings) ?>
        },
        user: {
            id: <?= $user_id ?>,
            email: <?= json_data($user_email) ?>,
            timezone: <?= json_data($timezone) ?>,
            role_slug: <?= json_data($role_slug) ?>,
            privileges: <?= json_data($privileges) ?>
        }
    };

    $(function () {
        BackendSettings.initialize(true);
    });
</script>

<div id="settings-page" class="container-fluid backend-page content-scroll">
    <ul class="nav nav-pills">
        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE): ?>
            <li class="nav-item">
                <a class="nav-link" href="#general" data-toggle="tab"><?= lang('general') ?></a>
            </li>
        <?php endif ?>
        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE): ?>
            <li class="nav-item">
                <a class="nav-link" href="#business-logic" data-toggle="tab"><?= lang('business_logic') ?></a>
            </li>
        <?php endif ?>
        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE): ?>
            <li class="nav-item">
                <a class="nav-link" href="#legal-contents" data-toggle="tab"><?= lang('legal_contents') ?></a>
            </li>
        <?php endif ?>
        <?php if ($privileges[PRIV_USER_SETTINGS]['view'] == TRUE): ?>
            <li class="nav-item">
                <a class="nav-link" href="#current-user" data-toggle="tab"><?= lang('current_user') ?></a>
            </li>
        <?php endif ?>
        <li class="nav-item">
            <a class="nav-link" href="#about-app" data-toggle="tab"><?= lang('about_app') ?></a>
        </li>
    </ul>

    <div class="tab-content">

        <!-- GENERAL TAB -->

        <?php $hidden = ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE) ? '' : 'd-none' ?>
        <div class="tab-pane active <?= $hidden ?>" id="general">
            <form>
                <fieldset>
                    <legend class="border-bottom mb-4 settings-legend">
                        <span><?= lang('general_settings') ?></span>
                        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['edit'] == TRUE): ?>
                                                        <div class="btn-group" data-display="none"><button type="button" class="save-settings btn btn-primary"                     data-tippy-content="<?= lang('save') ?>">                   <i class="fas fa-check-square mr-2"></i>                   <?= lang('save') ?>                 </button>                 <button type="button" class="cancel-settings btn btn-outline-secondary">                   <i class="fas fa-ban mr-2"></i><?= lang('cancel') ?>                 </button>               </div>
                            <button type="button" class="edit-settings btn btn-outline-secondary">                 <i class="fas fa-edit mr-2"></i><?= lang('edit') ?>               </button>                
                            
                        <?php endif ?>
                    </legend>

                    <div class="wrapper row">
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label for="company-name"><?= lang('company_name') ?> *</label>
                                <input id="company-name" data-field="company_name" class="required form-control">
                                <span class="form-text text-muted">
                                    <?= lang('company_name_hint') ?>
                                </span>
                            </div>

                            <div class="form-group">
                                <label for="company-email"><?= lang('company_email') ?> *</label>
                                <input id="company-email" data-field="company_email" class="required form-control">
                                <span class="form-text text-muted">
                                    <?= lang('company_email_hint') ?>
                                </span>
                            </div>

                            <div class="form-group">
                                <label for="company-link"><?= lang('company_link') ?> *</label>
                                <input id="company-link" data-field="company_link" class="required form-control">
                                <span class="form-text text-muted">
                                    <?= lang('company_link_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="date-format">
                                    <?= lang('date_format') ?>
                                </label>
                                <select class="form-control" id="date-format" data-field="date_format">
                                    <option value="DMY">D/M/Y</option>
                                    <option value="DDMY">D.M.Y</option>
                                    <option value="MDY">M/D/Y</option>
                                    <option value="YMD">Y/M/D</option>
                                </select>
                                <span class="form-text text-muted">
                                    <?= lang('date_format_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="time-format">
                                    <?= lang('time_format') ?>
                                </label>
                                <select class="form-control" id="time-format" data-field="time_format">
                                    <option value="<?= TIME_FORMAT_REGULAR ?>">H:MM AM/PM</option>
                                    <option value="<?= TIME_FORMAT_MILITARY ?>">HH:MM</option>
                                </select>
                                <span class="form-text text-muted">
                                    <?= lang('time_format_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="first-weekday">
                                    <?= lang('first_weekday') ?>
                                </label>
                                <select class="form-control" id="first-weekday" data-field="first_weekday">
                                    <option value="sunday"><?= lang('sunday') ?></option>
                                    <option value="monday"><?= lang('monday') ?></option>
                                    <option value="tuesday"><?= lang('tuesday') ?></option>
                                    <option value="wednesday"><?= lang('wednesday') ?></option>
                                    <option value="thursday"><?= lang('thursday') ?></option>
                                    <option value="friday"><?= lang('friday') ?></option>
                                    <option value="saturday"><?= lang('saturday') ?></option>
                                </select>
                                <span class="form-text text-muted">
                                    <?= lang('first_weekday_hint') ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE): ?>
                                <div class="form-group">
                                    <label for="api-token-value">API Token</label>
                                    <div class="input-group">
                                        <input id="api-token-value" data-keep-enabled="true" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <button type="button" id="api-token-copy" data-keep-enabled="true" class="btn btn-outline-secondary"
                                                    title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small id="api-token-state" class="form-text text-muted"></small>
                                    <button type="button" id="api-token-generate" data-keep-enabled="true"
                                            class="btn btn-outline-secondary btn-sm mr-2">
                                        <i class="fas fa-key mr-2"></i>
                                        <?= lang('generate_api_token') ?>
                                    </button>
                                    <button type="button" id="api-token-revoke" data-keep-enabled="true" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash mr-2"></i>
                                        <?= lang('revoke_api_token') ?>
                                    </button>
                                </div>
                            <?php endif ?>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="customer-notifications">
                                    <label class="custom-control-label" for="customer-notifications">
                                        <?= lang('customer_notifications') ?>
                                    </label>
                                </div>
                                <span class="form-text text-muted">
                                    <?= lang('customer_notifications_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require-captcha">
                                    <label class="custom-control-label" for="require-captcha">
                                        CAPTCHA
                                    </label>
                                </div>
                                <span class="form-text text-muted">
                                    <?= lang('require_captcha_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require-phone-number">
                                    <label class="custom-control-label" for="require-phone-number">
                                        <?= lang('phone_number') ?>
                                    </label>
                                </div>
                                <span class="form-text text-muted">
                                    <?= lang('require_phone_number_hint') ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="display-any-provider">
                                    <label class="custom-control-label" for="display-any-provider">
                                        <?= lang('any_provider') ?>
                                    </label>
                                </div>
                                <span class="form-text text-muted">
                                    <?= lang('display_any_provider_hint') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>

        <!-- BUSINESS LOGIC TAB -->

        <?php $hidden = ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE) ? '' : 'd-none' ?>
        <div class="tab-pane <?= $hidden ?>" id="business-logic">
            <form>
                <fieldset>
                    <legend class="border-bottom mb-4 settings-legend">
                        <span><?= lang('business_logic') ?></span>
                        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['edit'] == TRUE): ?>
                                                        <div class="btn-group" data-display="none"><button type="button" class="save-settings btn btn-primary"                     data-tippy-content="<?= lang('save') ?>">                   <i class="fas fa-check-square mr-2"></i>                   <?= lang('save') ?>                 </button>                 <button type="button" class="cancel-settings btn btn-outline-secondary">                   <i class="fas fa-ban mr-2"></i><?= lang('cancel') ?>                 </button>               </div>
                            <button type="button" class="edit-settings btn btn-outline-secondary">                 <i class="fas fa-edit mr-2"></i><?= lang('edit') ?>               </button>                
                            
                        <?php endif ?>
                    </legend>

                    <div class="row">
                        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE): ?>
                            <div class="col-12 col-sm-7 working-plan-wrapper">
                            <h4><?= lang('working_plan') ?></h4>
                            <span class="form-text text-muted mb-4">
                                <?= lang('edit_working_plan_hint') ?>
                            </span>

                            <table class="working-plan table table-striped">
                                <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                </tr>
                                </thead>
                                <tbody><!-- Dynamic Content --></tbody>
                            </table>

                            <div class="text-right">
                                <button class="btn btn-outline-secondary" id="apply-global-working-plan" type="button">
                                    <i class="fas fa-check"></i>
                                    <?= lang('apply_to_all_providers') ?>
                                </button>
                            </div>

                            <br>

                            <h4><?= lang('book_advance_timeout') ?></h4>
                            <div class="form-group">
                                <label for="book-advance-timeout"
                                       class="control-label"><?= lang('timeout_minutes') ?></label>
                                <input id="book-advance-timeout" data-field="book_advance_timeout" class="form-control"
                                       type="number" min="15">
                                <p class="form-text text-muted">
                                    <?= lang('book_advance_timeout_hint') ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-sm-5 breaks-wrapper">
                            <h4><?= lang('breaks') ?></h4>

                            <span class="form-text text-muted">
                                <?= lang('edit_breaks_hint') ?>
                            </span>

                            <div class="mt-2">
                                <button type="button" class="add-break btn btn-primary">
                                    <i class="fas fa-plus-square"></i>
                                    <?= lang('add_break'); ?>
                                </button>
                            </div>

                            <br>

                            <table class="breaks table table-striped">
                                <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                    <th><?= lang('actions') ?></th>
                                </tr>
                                </thead>
                                <tbody><!-- Dynamic Content --></tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif ?>
                </fieldset>
            </form>
        </div>

        <!-- LEGAL CONTENTS TAB -->

        <?php $hidden = ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE) ? '' : 'd-none' ?>
        <div class="tab-pane <?= $hidden ?>" id="legal-contents">
            <form>
                <fieldset>
                    <legend class="border-bottom mb-4 settings-legend">
                        <span><?= lang('legal_contents') ?></span>
                        <?php if ($privileges[PRIV_SYSTEM_SETTINGS]['edit'] == TRUE): ?>
                                                        <div class="btn-group" data-display="none"><button type="button" class="save-settings btn btn-primary"                     data-tippy-content="<?= lang('save') ?>">                   <i class="fas fa-check-square mr-2"></i>                   <?= lang('save') ?>                 </button>                 <button type="button" class="cancel-settings btn btn-outline-secondary">                   <i class="fas fa-ban mr-2"></i><?= lang('cancel') ?>                 </button>               </div>
                            <button type="button" class="edit-settings btn btn-outline-secondary">                 <i class="fas fa-edit mr-2"></i><?= lang('edit') ?>               </button>                
                            
                        <?php endif ?>
                    </legend>

                    <div class="row">
                        <div class="col-12 col-md-6 col-xl-4">
                            <h4><?= lang('cookie_notice') ?></h4>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="display-cookie-notice">
                                    <label class="custom-control-label" for="display-cookie-notice">
                                        <?= lang('display_cookie_notice') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><?= lang('cookie_notice_content') ?></label>
                                <textarea id="cookie-notice-content" cols="30" rows="10" class="form-group"></textarea>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-4">
                            <h4><?= lang('terms_and_conditions') ?></h4>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox"
                                           id="display-terms-and-conditions">
                                    <label class="custom-control-label" for="display-terms-and-conditions">
                                        <?= lang('display_terms_and_conditions') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><?= lang('terms_and_conditions_content') ?></label>
                                <textarea id="terms-and-conditions-content" cols="30" rows="10"
                                          class="form-group"></textarea>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-4">
                            <h4><?= lang('privacy_policy') ?></h4>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="display-privacy-policy">
                                    <label class="custom-control-label" for="display-privacy-policy">
                                        <?= lang('display_privacy_policy') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><?= lang('privacy_policy_content') ?></label>
                                <textarea id="privacy-policy-content" cols="30" rows="10" class="form-group"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 col-md-6 col-xl-4">
                            <h4><?= lang('data_retention') ?></h4>

                            <div class="form-group">
                                <label for="data-retention-days">
                                    <?= lang('retention_days') ?>
                                </label>
                                <input id="data-retention-days" data-field="record_retention_days"
                                       class="form-control" type="number" min="0" max="3650">
                                <span class="form-text text-muted">
                                    <?= lang('data_retention_hint') ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-4">
                            <h4>Maintenance</h4>

                            <div class="form-group">
                                <button type="button" id="purge-records" class="btn btn-outline-secondary">
                                    <i class="fas fa-broom mr-2"></i>
                                    <?= lang('purge_records') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>

        <!-- CURRENT USER TAB -->

        <?php $hidden = ($privileges[PRIV_USER_SETTINGS]['view'] == TRUE) ? '' : 'd-none' ?>
        <div class="tab-pane <?= $hidden ?>" id="current-user">
            <form>
                <legend class="border-bottom mb-4 settings-legend">
                    <span><?= lang('current_user') ?></span>
                    <?php if ($privileges[PRIV_USER_SETTINGS]['edit'] == TRUE): ?>
                        <div class="btn-group" data-display="none">
                            <button type="button" class="save-settings btn btn-primary"
                                    data-tippy-content="<?= lang('save') ?>">
                                <i class="fas fa-check-square mr-2"></i>
                                <?= lang('save') ?>
                            </button>
                            <button type="button" class="cancel-settings btn btn-outline-secondary">
                                <i class="fas fa-ban mr-2"></i><?= lang('cancel') ?>
                            </button>
                        </div>
                        <button type="button" class="edit-settings btn btn-outline-secondary">
                            <i class="fas fa-edit mr-2"></i><?= lang('edit') ?>
                        </button>
                    <?php endif ?>
                </legend>

                <div class="row">
                    <fieldset class="col-12 col-md-4 personal-info-wrapper">
                                            <input type="hidden" id="user-id">

                                            <div class="form-group">
                                                <label for="first-name"><?= lang('first_name') ?> *</label>
                                                <input id="first-name" class="form-control required">
                                            </div>

                                            <div class="form-group">
                                                <label for="last-name"><?= lang('last_name') ?> *</label>
                                                <input id="last-name" class="form-control required">
                                            </div>

                                            <div class="form-group">
                                                <label for="email"><?= lang('email') ?> *</label>
                                                <input id="email" class="form-control required">
                                            </div>

                                            <div class="form-group">
                                                <label for="phone-number"><?= lang('phone_number') ?> *</label>
                                                <input id="phone-number" class="form-control required">
                                            </div>

                                            <div class="form-group">
                                                <label for="mobile-number"><?= lang('mobile_number') ?></label>
                                                <input id="mobile-number" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label for="address"><?= lang('address') ?></label>
                                                <input id="address" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label for="city"><?= lang('city') ?></label>
                                                <input id="city" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label for="state"><?= lang('state') ?></label>
                                                <input id="state" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label for="zip-code"><?= lang('zip_code') ?></label>
                                                <input id="zip-code" class="form-control">
                                            </div>

                                        </fieldset>
                    <fieldset class="col-12 col-md-4 provider-channel-wrapper">
                                            <div class="form-group">
                                                <label for="provider-appointment-mode">
                                                    <?= lang('appointment_mode') ?>
                                                </label>
                                                <select id="provider-appointment-mode" class="form-control">
                                                    <option value="<?= APPOINTMENT_MODE_BOTH ?>"><?= lang('mode_both') ?></option>
                                                    <option value="<?= APPOINTMENT_MODE_ONLINE ?>"><?= lang('mode_online') ?></option>
                                                    <option value="<?= APPOINTMENT_MODE_ON_PREMISE ?>"><?= lang('mode_on_premise') ?></option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="provider-zoom-link">
                                                    <?= lang('zoom_link') ?>
                                                </label>
                                                <input id="provider-zoom-link" class="form-control" maxlength="512"
                                                       placeholder="https://...">
                                            </div>

                                            <div class="form-group">
                                                <label for="notes"><?= lang('notes') ?></label>
                                                <textarea id="notes" class="form-control" rows="3"></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label for="calendar-view"><?= lang('calendar') ?> *</label>
                                                <select id="calendar-view" class="form-control required">
                                                    <option value="default"><?= lang('default') ?></option>
                                                    <option value="table"><?= lang('table') ?></option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="timezone"><?= lang('timezone') ?></label>
                                                <?= render_timezone_dropdown('id="timezone" class="form-control"') ?>
                                            </div>

                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="user-notifications">
                                                <label class="custom-control-label" for="user-notifications">
                                                    <?= lang('receive_notifications') ?>
                                                </label>
                                            </div>
                                            <span class="form-text text-muted mb-2">
                                                <?= lang('receive_notifications_hint') ?>
                                            </span>

                                            <div class="form-group mt-2">
                                                <label for="feed-url"><?= lang('ical_feed') ?></label>
                                                <div class="input-group">
                                                    <input id="feed-url" data-keep-enabled="true" class="form-control" readonly
                                                           placeholder="<?= lang('ical_feed_hint') ?>">
                                                    <div class="input-group-append">
                                                        <button type="button" id="feed-copy" data-keep-enabled="true" class="btn btn-outline-secondary"
                                                                title="Copy">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small id="feed-state" class="form-text text-muted"></small>
                                                <button type="button" id="feed-generate" data-keep-enabled="true"
                                                        class="btn btn-outline-secondary btn-sm mr-2">
                                                    <i class="fas fa-key mr-2"></i>
                                                    <?= lang('generate_feed') ?>
                                                </button>
                                                <button type="button" id="feed-revoke" data-keep-enabled="true" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash mr-2"></i>
                                                    <?= lang('revoke_feed') ?>
                                                </button>
                                            </div>
                                        </fieldset>
                    <fieldset class="col-12 col-md-4 miscellaneous-wrapper">
                                    <div class="form-group">
                                    <label for="username"><?= lang('username') ?> *</label>
                                    <input id="username" class="form-control required">
                                    </div>

                                    <div class="form-group">
                                    <label for="password"><?= lang('password') ?></label>
                                    <input type="password" id="password" class="form-control" autocomplete="new-password">
                                    </div>

                                    <div class="form-group">
                                    <label for="retype-password"><?= lang('retype_password') ?></label>
                                    <input type="password" id="retype-password" class="form-control"
                                    autocomplete="new-password">
                                    </div>

                                    </fieldset>
                </div>
                <div class="row">
                                                        <fieldset class="col-12 col-md-6 working-plan-column">
                                                        <h3><?= lang('working_plan') ?></h3>
                                                        <p class="form-text text-muted">
                                                            <?= lang('working_plan_description') ?>
                                                        </p>
                                                        <div class="mb-2">
                                                            <button id="reset-working-plan" class="btn btn-primary"
                                                                    data-tippy-content="<?= lang('reset_working_plan') ?>">
                                                                <i class="fas fa-redo-alt mr-2"></i>
                                                                <?= lang('reset_plan') ?>
                                                            </button>
                                                        </div>
                                                        <table class="working-plan table table-striped mt-2">
                                                            <thead>
                                                            <tr>
                                                                <th><?= lang('day') ?></th>
                                                                <th><?= lang('start') ?></th>
                                                                <th><?= lang('end') ?></th>
                                                                <th><?= lang('mode') ?></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody><!-- Dynamic Content --></tbody>
                                                        </table>
                                                    </fieldset>
                                                                    <fieldset class="col-12 col-md-6 breaks-column">
                                                        <h3><?= lang('breaks') ?></h3>
                                                        <p class="form-text text-muted">
                                                            <?= lang('add_breaks_during_each_day') ?>
                                                        </p>
                                                        <div class="mb-2">
                                                            <button type="button" class="add-break btn btn-primary">
                                                                <i class="fas fa-plus-square mr-2"></i>
                                                                <?= lang('add_break') ?>
                                                            </button>
                                                        </div>
                                                        <table class="breaks table table-striped mt-2">
                                                            <thead>
                                                            <tr>
                                                                <th><?= lang('day') ?></th>
                                                                <th><?= lang('start') ?></th>
                                                                <th><?= lang('end') ?></th>
                                                                <th><?= lang('actions') ?></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody><!-- Dynamic Content --></tbody>
                                                        </table>
                                                    </fieldset>
                                                    <fieldset class="col-12 col-md-6 unavailability-periods-column">
                                                        <h3><?= lang('unavailable_periods') ?></h3>

                                                        <p class="form-text text-muted">
                                                            <?= lang('unavailable_periods_description') ?>
                                                        </p>

                                                        <div class="mb-2">
                                                            <button type="button" id="insert-unavailable" class="btn btn-primary mr-2">
                                                                <i class="fas fa-plus-square mr-2"></i>
                                                                <?= lang('add_unavailable') ?>
                                                            </button>
                                                        </div>

                                                        <table class="unavailability-periods table table-striped mt-2">
                                                            <thead>
                                                            <tr>
                                                                <th><?= lang('start') ?></th>
                                                                <th><?= lang('end') ?></th>
                                                                <th><?= lang('notes') ?></th>
                                                                <th><?= lang('actions') ?></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody><!-- Dynamic Content --></tbody>
                                                        </table>
                                                    </fieldset>

                                                    <fieldset class="col-12 col-md-6 working-plan-exceptions-column">
                                                        <h3><?= lang('working_plan_exceptions') ?></h3>
                                                        <p class="form-text text-muted">
                                                            <?= lang('add_working_plan_exceptions_during_each_day') ?>
                                                        </p>
                                                        <div class="mb-2">
                                                            <button type="button" class="add-working-plan-exception btn btn-primary">
                                                                <i class="fas fa-plus-square mr-2"></i>
                                                                <?= lang('add_working_plan_exception') ?>
                                                            </button>
                                                        </div>
                                                        <table class="working-plan-exceptions table table-striped mt-2">
                                                            <thead>
                                                            <tr>
                                                                <th><?= lang('day') ?></th>
                                                                <th><?= lang('start') ?></th>
                                                                <th><?= lang('end') ?></th>
                                                                <th><?= lang('mode') ?></th>
                                                                <th><?= lang('actions') ?></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody><!-- Dynamic Content --></tbody>
                                                        </table>
                                                        <?php require __DIR__ . '/working_plan_exceptions_modal.php' ?>
                                                    </fieldset>
                </div>
            </form>

        <?php if ($role_slug === DB_SLUG_PROVIDER): ?>
            <input type="hidden" id="provider-id" value="1">
            <?php require __DIR__ . '/manage_unavailable_modal.php' ?>
        <?php endif ?>
        </div>

        <!-- ABOUT TAB -->
        <div class="tab-pane" id="about-app">
            <h3>Easy!Appointments</h3>

            <p>
                <?= lang('about_app_info') ?>
            </p>

            <div class="current-version card card-body bg-light border-light mb-3">
                <?= lang('current_version') ?>
                <?= ea_config('version') ?>
                <?php if (ea_config('release_label')): ?>
                    - <?= ea_config('release_label') ?>
                <?php endif ?>
            </div>

            <h3><?= lang('support') ?></h3>
            <p>
                <?= lang('about_app_support') ?>
            </p>

            <h3><?= lang('license') ?></h3>

            <p>
                <?= lang('about_app_license') ?>
                <a href="http://www.gnu.org/copyleft/gpl.html">http://www.gnu.org/copyleft/gpl.html</a>
            </p>
        </div>

    </div>
</div>
