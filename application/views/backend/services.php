<script src="<?= asset_url('assets/js/backend_services_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_categories_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_services.js') ?>"></script>
<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.min.js') ?>"></script>
<script>
    var GlobalVariables = {
        csrfToken: <?= json_data($this->security->get_csrf_hash()) ?>,
        baseUrl: <?= json_data($base_url) ?>,
        dateFormat: <?= json_data($date_format) ?>,
        timeFormat: <?= json_data($time_format) ?>,
        services: <?= json_data($services) ?>,
        categories: <?= json_data($categories) ?>,
        timezones: <?= json_data($timezones) ?>,
        user: {
            id: <?= $user_id ?>,
            email: <?= json_data($user_email) ?>,
            timezone: <?= json_data($timezone) ?>,
            role_slug: <?= json_data($role_slug) ?>,
            privileges: <?= json_data($privileges) ?>
        }
    };

    $(function () {
        BackendServices.initialize(true);
    });
</script>

<div id="services-page" class="container-fluid backend-page content-scroll">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link active" href="#services" data-toggle="tab">
                <?= lang('services') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#categories" data-toggle="tab">
                <?= lang('categories') ?>
            </a>
        </li>
    </ul>

    <div class="tab-content">

        <!-- SERVICES TAB -->

        <div class="tab-pane active" id="services">
            <div class="row">
                <div id="filter-services" class="filter-records col col-12 col-md-5">
                    <form class="mb-4">
                        <div class="input-group">
                            <input type="text" class="key form-control">

                            <div class="input-group-addon">
                                <div>
                                    <button class="filter btn btn-outline-secondary" type="submit"
                                            data-tippy-content="<?= lang('filter') ?>">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="clear btn btn-outline-secondary" type="button"
                                            data-tippy-content="<?= lang('clear') ?>">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <h3><?= lang('services') ?></h3>
                    <div class="results"></div>
                </div>

                <div class="record-details column col-12 col-md-5">
                    <div class="float-md-left mb-4 mr-4">
                        <div class="add-edit-delete-group btn-group">
                            <button id="add-service" class="btn btn-primary">
                                <i class="fas fa-plus-square mr-2"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-service" class="btn btn-outline-secondary" disabled="disabled">
                                <i class="fas fa-edit mr-2"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-service" class="btn btn-outline-secondary" disabled="disabled">
                                <i class="fas fa-trash-alt mr-2"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group btn-group" style="display:none;">
                            <button id="save-service" class="btn btn-primary">
                                <i class="fas fa-check-square mr-2"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-service" class="btn btn-outline-secondary">
                                <i class="fas fa-ban mr-2"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <ul class="nav nav-pills switch-view">
                        <li class="nav-item">
                            <a class="nav-link active" href="#service-details" data-toggle="tab">
                                <?= lang('details') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#service-location-overrides" data-toggle="tab">
                                <?= lang('location_overrides') ?>
                            </a>
                        </li>
                    </ul>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="service-id">

                    <div class="tab-content">
                        <div class="tab-pane fade show active clearfix" id="service-details">
                            <h3 class="pb-4"><?= lang('details') ?></h3>

                            <div class="row">
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label for="service-name">
                                            <?= lang('name') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input id="service-name" class="form-control required" maxlength="128">
                                    </div>

                                    <div class="form-group">
                                        <label for="service-duration">
                                            <?= lang('duration_minutes') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input id="service-duration" class="form-control required" type="number" min="<?= EVENT_MINIMUM_DURATION ?>">
                                    </div>

                                    <div class="form-group"  style="display: none">
                                        <label for="service-price">
                                            <?= lang('price') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input id="service-price" class="form-control required">
                                    </div>

                                    <div class="form-group"  style="display: none">
                                        <label for="service-currency">
                                            <?= lang('currency') ?>
                                        </label>
                                        <input id="service-currency" class="form-control" maxlength="32">
                                    </div>

                                    <div class="form-group">
                                        <label for="service-category">
                                            <?= lang('category') ?>
                                        </label>
                                        <select id="service-category" class="form-control"></select>
                                    </div>

                                    <div class="form-group">
                                        <label for="service-availabilities-type">
                                            <?= lang('availabilities_type') ?>
                                        </label>
                                        <select id="service-availabilities-type" class="form-control">
                                            <option value="<?= AVAILABILITIES_TYPE_FIXED ?>">
                                                <?= lang('fixed') ?>
                                            </option>
                                            <option value="<?= AVAILABILITIES_TYPE_FLEXIBLE ?>">
                                                <?= lang('flexible') ?>
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="service-attendants-number">
                                            <?= lang('attendants_number') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input id="service-attendants-number" class="form-control required" type="number" min="1">
                                    </div>

                                    <div class="form-group">
                                        <label for="service-location">
                                            <?= lang('location') ?>
                                        </label>
                                        <input id="service-location" class="form-control">
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label for="service-appointment-mode">
                                            <?= lang('appointment_mode') ?>
                                        </label>
                                        <select id="service-appointment-mode" class="form-control">
                                            <option value=""><?= lang('mode_provider_default') ?></option>
                                            <option value="<?= APPOINTMENT_MODE_ONLINE ?>"><?= lang('mode_online') ?></option>
                                            <option value="<?= APPOINTMENT_MODE_ON_PREMISE ?>"><?= lang('mode_on_premise') ?></option>
                                            <option value="<?= APPOINTMENT_MODE_BOTH ?>"><?= lang('mode_both') ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <?= lang('weekday_mode_overrides') ?>
                                        </label>
                                        <table class="service-weekday-modes table table-sm table-borderless">
                                            <tbody>
                                            <?php
                                            $weekday_names = ['sunday', 'monday', 'tuesday', 'wednesday',
                                                'thursday', 'friday', 'saturday'];

                                            foreach ([1, 2, 3, 4, 5, 6, 0] as $weekday): ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <?= lang($weekday_names[$weekday]) ?>
                                                    </td>
                                                    <td>
                                                        <select class="weekday-mode form-control" data-weekday="<?= $weekday ?>">
                                                            <option value="">—</option>
                                                            <option value="<?= APPOINTMENT_MODE_ONLINE ?>"><?= lang('mode_online') ?></option>
                                                            <option value="<?= APPOINTMENT_MODE_ON_PREMISE ?>"><?= lang('mode_on_premise') ?></option>
                                                            <option value="<?= APPOINTMENT_MODE_BOTH ?>"><?= lang('mode_both') ?></option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="form-group">
                                        <label for="service-description">
                                            <?= lang('description') ?>
                                        </label>
                                        <textarea id="service-description" rows="4" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade clearfix" id="service-location-overrides">
                            <h3 class="pb-4"><?= lang('location_overrides') ?></h3>

                            <div class="form-group service-location-overrides">
                                <label>
                                    <?= lang('location_weekday_overrides') ?>
                                </label>

                                <div>
                                    <button type="button"
                                            class="add-location-window btn btn-primary btn-sm">
                                        <i class="fas fa-plus-square mr-2"></i>
                                        <?= lang('add_location_window') ?>
                                    </button>
                                </div>

                                <table class="service-location-weekdays table table-striped table-sm mt-2">
                                    <thead>
                                    <tr>
                                        <th><?= lang('day') ?></th>
                                        <th><?= lang('start') ?></th>
                                        <th><?= lang('end') ?></th>
                                        <th><?= lang('location') ?></th>
                                        <th><?= lang('actions') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody><!-- Dynamic Content --></tbody>
                                </table>

                                <label class="mt-2">
                                    <?= lang('location_date_overrides') ?>
                                </label>

                                <div>
                                    <button type="button"
                                            class="add-location-date btn btn-primary btn-sm">
                                        <i class="fas fa-plus-square mr-2"></i>
                                        <?= lang('add_location_date') ?>
                                    </button>
                                </div>

                                <table class="service-location-date-overrides table table-striped table-sm mt-2">
                                    <thead>
                                    <tr>
                                        <th><?= lang('date') ?></th>
                                        <th><?= lang('start') ?></th>
                                        <th><?= lang('end') ?></th>
                                        <th><?= lang('location') ?></th>
                                        <th><?= lang('actions') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody><!-- Dynamic Content --></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- CATEGORIES TAB -->

        <div class="tab-pane" id="categories">
            <div class="row">
                <div id="filter-categories" class="filter-records column col-12 col-md-5">
                    <form class="input-append mb-4">
                        <div class="input-group">
                            <input type="text" class="key form-control">

                            <div class="input-group-addon">
                                <div>
                                    <button class="filter btn btn-outline-secondary" type="submit"
                                            data-tippy-content="<?= lang('filter') ?>">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="clear btn btn-outline-secondary" type="button"
                                            data-tippy-content="<?= lang('clear') ?>">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <h3><?= lang('categories') ?></h3>
                    <div class="results"></div>
                </div>

                <div class="record-details col-12 col-md-5">
                    <div class="btn-toolbar mb-4">
                        <div class="add-edit-delete-group btn-group">
                            <button id="add-category" class="btn btn-primary">
                                <i class="fas fa-plus-square mr-2"></i>
                                <?= lang('add') ?>
                            </button>
                            <button id="edit-category" class="btn btn-outline-secondary" disabled="disabled">
                                <i class="fas fa-edit mr-2"></i>
                                <?= lang('edit') ?>
                            </button>
                            <button id="delete-category" class="btn btn-outline-secondary" disabled="disabled">
                                <i class="fas fa-trash-alt mr-2"></i>
                                <?= lang('delete') ?>
                            </button>
                        </div>

                        <div class="save-cancel-group btn-group" style="display:none;">
                            <button id="save-category" class="btn btn-primary">
                                <i class="fas fa-check-square mr-2"></i>
                                <?= lang('save') ?>
                            </button>
                            <button id="cancel-category" class="btn btn-outline-secondary">
                                <i class="fas fa-ban mr-2"></i>
                                <?= lang('cancel') ?>
                            </button>
                        </div>
                    </div>

                    <h3><?= lang('details') ?></h3>

                    <div class="form-message alert" style="display:none;"></div>

                    <input type="hidden" id="category-id">

                    <div class="form-group">
                        <label for="category-name">
                            <?= lang('name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="category-name" class="form-control required">
                    </div>

                    <div class="form-group">
                        <label for="category-description">
                            <?= lang('description') ?>

                        </label>
                        <textarea id="category-description" rows="4" class="form-control"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
</div>
