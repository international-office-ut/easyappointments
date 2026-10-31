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

(function () {

    'use strict';

    /**
     * ServicesHelper
     *
     * This class contains the methods that will be used by the "Services" tab of the page.
     *
     * @class ServicesHelper
     */
    function ServicesHelper() {
        this.filterResults = {};
        this.filterLimit = 20;
    }

    ServicesHelper.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Filter Services Form "Submit"
         *
         * @param {jQuery.Event} event
         */
        $('#services').on('submit', '#filter-services form', function (event) {
            event.preventDefault();
            var key = $('#filter-services .key').val();
            $('#filter-services .selected').removeClass('selected');
            instance.resetForm();
            instance.filter(key);
        });

        /**
         * Event: Filter Service Cancel Button "Click"
         */
        $('#services').on('click', '#filter-services .clear', function () {
            $('#filter-services .key').val('');
            instance.filter('');
            instance.resetForm();
        });

        /**
         * Event: Filter Service Row "Click"
         *
         * Display the selected service data to the user.
         */
        $('#services').on('click', '.service-row', function () {
            if ($('#filter-services .filter').prop('disabled')) {
                $('#filter-services .results').css('color', '#AAA');
                return; // exit because we are on edit mode
            }

            var serviceId = $(this).attr('data-id');

            var service = instance.filterResults.find(function (filterResult) {
                return Number(filterResult.id) === Number(serviceId);
            });

            // Add dedicated provider link.
            var dedicatedUrl = GlobalVariables.baseUrl + '/index.php?service=' + encodeURIComponent(service.id);
            var $link = $('<a/>', {
                'href': dedicatedUrl,
                'html': [
                    $('<span/>', {
                        'class': 'fas fa-link'
                    })
                ]
            });

            $('#services .record-details #service-details h3')
                .find('a')
                .remove()
                .end()
                .append($link);

            instance.display(service);
            $('#filter-services .selected').removeClass('selected');
            $(this).addClass('selected');
            $('#edit-service, #delete-service').prop('disabled', false);
        });

        /**
         * Event: Add New Service Button "Click"
         */
        $('#services').on('click', '#add-service', function () {
            instance.resetForm();
            $('#services .add-edit-delete-group').hide();
            $('#services .save-cancel-group').show();
            $('#services .record-details')
                .find('input, select, textarea')
                .prop('disabled', false);
            $('#filter-services button').prop('disabled', true);
            $('#filter-services .results').css('color', '#AAA');

            // Default values
            $('#service-name').val('Service');
            $('#service-duration').val('30');
            $('#service-price').val('0');
            $('#service-currency').val('');
            $('#service-category').val('null');
            $('#service-availabilities-type').val('fixed');
            $('#service-attendants-number').val('1');
            $('#service-appointment-mode').val('');
            $('.service-weekday-modes .weekday-mode').val('');
        });

        /**
         * Event: Cancel Service Button "Click"
         *
         * Cancel add or edit of a service record.
         */
        $('#services').on('click', '#cancel-service', function () {
            var id = $('#service-id').val();
            instance.resetForm();
            if (id !== '') {
                instance.select(id, true);
            }
        });

        /**
         * Event: Save Service Button "Click"
         */
        $('#services').on('click', '#save-service', function () {
            var service = {
                name: $('#service-name').val(),
                duration: $('#service-duration').val(),
                price: $('#service-price').val(),
                currency: $('#service-currency').val(),
                description: $('#service-description').val(),
                location: $('#service-location').val(),
                appointment_mode: $('#service-appointment-mode').val() || null,
                weekday_modes: {},
                location_weekday_modes: JSON.stringify(
                    ServicesHelper.collectWeekdayLocationWindows()) || null,
                location_date_overrides: JSON.stringify(
                    ServicesHelper.collectDateLocationWindows()) || null,
                availabilities_type: $('#service-availabilities-type').val(),
                attendants_number: $('#service-attendants-number').val()
            };

            $('.service-weekday-modes .weekday-mode').each(function (index, select) {
                var weekday = $(select).attr('data-weekday');

                if ($(select).val()) {
                    service.weekday_modes[weekday] = $(select).val();
                }
            });

            if ($('#service-category').val() !== 'null') {
                service.id_service_categories = $('#service-category').val();
            } else {
                service.id_service_categories = null;
            }

            if ($('#service-id').val() !== '') {
                service.id = $('#service-id').val();
            }

            if (!instance.validate()) {
                return;
            }

            instance.save(service);
        });

        /**
         * Event: Service Add-Location-Window "Click" (weekday windows table)
         */
        $('#services').on('click', '.add-location-window', function (event) {
            event.preventDefault();

            var $row = ServicesHelper.buildLocationWindowRow('', '', '');

            $('#services .service-location-weekdays tbody').append($row);

            ServicesHelper.attachLocationWindowPickers($row);
        });

        /**
         * Event: Service Add-Location-Date "Click" (date overrides table)
         */
        $('#services').on('click', '.add-location-date', function (event) {
            event.preventDefault();

            var $row = ServicesHelper.buildLocationWindowRow('', '', '', '');

            $('#services .service-location-date-overrides tbody').append($row);

            ServicesHelper.attachLocationWindowPickers($row);
        });

        /**
         * Event: Service Remove-Location-Window "Click"
         */
        $('#services').on('click', '.remove-location-window', function (event) {
            event.preventDefault();

            $(event.currentTarget).closest('tr').remove();
        });

        /**
         * Event: Edit Service Button "Click"
         */
        $('#services').on('click', '#edit-service', function () {
            $('#services .add-edit-delete-group').hide();
            $('#services .save-cancel-group').show();
            $('#services .record-details')
                .find('input, select, textarea')
                .prop('disabled', false);
            $('#filter-services button').prop('disabled', true);
            $('#filter-services .results').css('color', '#AAA');
        });

        /**
         * Event: Delete Service Button "Click"
         */
        $('#services').on('click', '#delete-service', function () {
            var serviceId = $('#service-id').val();
            var buttons = [
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message-box').dialog('close');
                    }
                },
                {
                    text: EALang.delete,
                    click: function () {
                        instance.delete(serviceId);
                        $('#message-box').dialog('close');
                    }
                }
            ];

            GeneralFunctions.displayMessageBox(EALang.delete_service,
                EALang.delete_record_prompt, buttons);
        });
    };

    /**
     * Remove the previously registered event handlers.
     */
    ServicesHelper.prototype.unbindEventHandlers = function () {
        $('#services')
            .off('submit', '#filter-services form')
            .off('click', '#filter-services .clear')
            .off('click', '.service-row')
            .off('click', '#add-service')
            .off('click', '#cancel-service')
            .off('click', '#save-service')
            .off('click', '#edit-service')
            .off('click', '#delete-service')
            .off('click', '.add-location-window')
            .off('click', '.add-location-date')
            .off('click', '.remove-location-window');
    };

    /**
     * Save service record to database.
     *
     * @param {Object} service Contains the service record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    ServicesHelper.prototype.save = function (service) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_service';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            service: JSON.stringify(service)
        };

        $.post(url, data)
            .done(function (response) {
                Backend.displayNotification(EALang.service_saved);
                this.resetForm();
                $('#filter-services .key').val('');
                this.filter('', response.id, true);
            }.bind(this));
    };

    /**
     * Delete a service record from database.
     *
     * @param {Number} id Record ID to be deleted.
     */
    ServicesHelper.prototype.delete = function (id) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_service';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: id
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.service_deleted);

                this.resetForm();
                this.filter($('#filter-services .key').val());
            }.bind(this));
    };

    /**
     * Validates a service record.
     *
     * @return {Boolean} Returns the validation result.
     */
    ServicesHelper.prototype.validate = function () {
        $('#services .has-error').removeClass('has-error');
        $('#services .location-window-row.has-error').removeClass('has-error');
        $('#services .location-window-row input').removeClass('is-invalid');
        $('#services .form-message')
            .removeClass('alert-danger')
            .hide();

        try {
            // Validate required fields.
            var missingRequired = false;

            $('#services .required').each(function (index, requiredField) {
                if (!$(requiredField).val()) {
                    $(requiredField).closest('.form-group').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(EALang.fields_are_required);
            }

            // Validate the duration.
            if (Number($('#service-duration').val()) < 5) {
                $('#service-duration').closest('.form-group').addClass('has-error');
                throw new Error(EALang.invalid_duration);
            }

            // Validate the location override windows: from < to on every row and no overlaps
            // inside a weekday or date layer (first-match-wins resolution is unverifiable when
            // borders overlap). Offending rows get highlighted in place.
            ServicesHelper.validateLocationWindows();

            return true;
        } catch (error) {
            $('#services .form-message')
                .addClass('alert-danger')
                .text(error.message)
                .show();
            return false;
        }
    };

    /**
     * Resets the service tab form back to its initial state.
     */
    ServicesHelper.prototype.resetForm = function () {
        $('#filter-services .selected').removeClass('selected');
        $('#filter-services button').prop('disabled', false);
        $('#filter-services .results').css('color', '');

        $('#services .record-details')
            .find('input, select, textarea')
            .val('')
            .prop('disabled', true);
        $('#services .service-location-weekdays tbody').empty();
        $('#services .service-location-date-overrides tbody').empty();
        $('#services .record-details h3 a').remove();

        $('#services .add-edit-delete-group').show();
        $('#services .save-cancel-group').hide();
        $('#edit-service, #delete-service').prop('disabled', true);

        $('#services .record-details .has-error').removeClass('has-error');
        $('#services .record-details .form-message').hide();

        // Reset the internal record tabs so that a new selection opens with Details active.
        $('#services .record-details .switch-view a[href="#service-details"]').tab('show');
    };

    /**
     * Display a service record into the service form.
     *
     * @param {Object} service Contains the service record data.
     */
    ServicesHelper.prototype.display = function (service) {
        $('#service-id').val(service.id);
        $('#service-name').val(service.name);
        $('#service-duration').val(service.duration);
        $('#service-price').val(service.price);
        $('#service-currency').val(service.currency);
        $('#service-description').val(service.description);
        $('#service-location').val(service.location);
        $('#service-appointment-mode').val(service.appointment_mode || '');
        $('.service-weekday-modes .weekday-mode').val('');
        var weekdayModes = service.weekday_modes || {};

        Object.keys(weekdayModes).forEach(function (weekday) {
            $('.service-weekday-modes .weekday-mode[data-weekday="' + weekday + '"]').val(weekdayModes[weekday]);
        });

        ServicesHelper.renderLocationWeekdayWindows(service.location_weekday_modes);
        ServicesHelper.renderLocationDateWindows(service.location_date_overrides);

        $('#service-availabilities-type').val(service.availabilities_type);
        $('#service-attendants-number').val(service.attendants_number);

        var categoryId = (service.id_service_categories !== null) ? service.id_service_categories : 'null';
        $('#service-category').val(categoryId);
    };

    /**
     * Build one location window editor row (from-time, to-time, location, remove button).
     *
     * @param {String} from Window start ("HH:MM").
     * @param {String} to Window end ("HH:MM").
     * @param {String} location Location value for the window.
     * @param {String} date Optional display-formatted date when the row belongs to a date override.
     * @return {jQuery} The row element.
     */
    ServicesHelper.buildLocationWindowRow = function (from, to, location, date) {
        var hasDate = date !== undefined;

        var $row = $('<tr/>', {
            'class': 'location-window-row',
            'data-date': hasDate ? date : ''
        });

        if (hasDate) {
            $row.append($('<td/>').append($('<input/>', {
                'type': 'text',
                'class': 'location-window-date form-control form-control-sm',
                'value': date
            })));
        } else {
            var $daySelect = $('<select/>', {'class': 'location-window-day form-control form-control-sm'});

            // Monday first, the option values keep the real weekday index (0 = Sunday ... 6 = Saturday).
            [1, 2, 3, 4, 5, 6, 0].forEach(function (weekday) {
                $daySelect.append($('<option/>', {
                    'value': weekday,
                    'text': EALang[['sunday', 'monday', 'tuesday', 'wednesday',
                        'thursday', 'friday', 'saturday'][weekday]]
                }));
            });

            $row.append($('<td/>').append($daySelect));
        }

        $row
            .append($('<td/>').append($('<input/>', {
                'type': 'text',
                'class': 'location-window-from form-control form-control-sm',
                'value': from
            })))
            .append($('<td/>').append($('<input/>', {
                'type': 'text',
                'class': 'location-window-to form-control form-control-sm',
                'value': to
            })))
            .append($('<td/>').append($('<input/>', {
                'type': 'text',
                'class': 'location-window-location form-control form-control-sm',
                'placeholder': EALang.location,
                'value': location
            })))
            .append($('<td/>').append($('<button/>', {
                'type': 'button',
                'class': 'remove-location-window btn btn-outline-danger btn-sm',
                'title': EALang.remove_location_window,
                'html': $('<i/>', {'class': 'fas fa-trash'})
            })));

        return $row;
    };

    /**
     * Bind the date and time pickers to a location window row (idempotent).
     *
     * The time fields use a 24-hour picker, as the stored windows and the resolvers compare
     * "HH:MM" strings. The date field follows the display format of the installation.
     *
     * @param {jQuery} $row The row element (or any element containing the inputs).
     */
    ServicesHelper.attachLocationWindowPickers = function ($row) {
        var dateFormat;

        switch (GlobalVariables.dateFormat) {
            case 'DMY':
                dateFormat = 'dd/mm/yy';
                break;
            case 'DDMY':
                dateFormat = 'dd.mm.yy';
                break;
            case 'MDY':
                dateFormat = 'mm/dd/yy';
                break;
            case 'YMD':
                dateFormat = 'yy/mm/dd';
                break;
            default:
                throw new Error('Invalid GlobalVariables.dateFormat value.');
        }

        $row.find('.location-window-from, .location-window-to').each(function (index, input) {
            var $input = $(input);

            if ($input.data('timepicker-attached')) {
                return;
            }

            $input.data('timepicker-attached', true).timepicker({
                timeFormat: 'HH:mm',

                // Translation
                currentText: EALang.now,
                closeText: EALang.close,
                timeOnlyTitle: EALang.select_time,
                timeText: EALang.time,
                hourText: EALang.hour,
                minuteText: EALang.minutes
            });
        });

        $row.find('.location-window-date').each(function (index, input) {
            var $input = $(input);

            if ($input.data('datepicker-attached')) {
                return;
            }

            $input.data('datepicker-attached', true).datepicker({
                dateFormat: dateFormat,

                // Translation
                dayNames: [EALang.sunday, EALang.monday, EALang.tuesday, EALang.wednesday,
                    EALang.thursday, EALang.friday, EALang.saturday],
                dayNamesShort: [EALang.sunday.substr(0, 3), EALang.monday.substr(0, 3),
                    EALang.tuesday.substr(0, 3), EALang.wednesday.substr(0, 3),
                    EALang.thursday.substr(0, 3), EALang.friday.substr(0, 3),
                    EALang.saturday.substr(0, 3)],
                dayNamesMin: [EALang.sunday.substr(0, 2), EALang.monday.substr(0, 2),
                    EALang.tuesday.substr(0, 2), EALang.wednesday.substr(0, 2),
                    EALang.thursday.substr(0, 2), EALang.friday.substr(0, 2),
                    EALang.saturday.substr(0, 2)],
                monthNames: [EALang.january, EALang.february, EALang.march, EALang.april,
                    EALang.may, EALang.june, EALang.july, EALang.august,
                    EALang.september, EALang.october, EALang.november, EALang.december],
                prevText: EALang.previous,
                nextText: EALang.next,
                currentText: EALang.now,
                closeText: EALang.close,
                firstDay: GlobalVariables.firstWeekday
                    ? GeneralFunctions.getWeekDayId(GlobalVariables.firstWeekday)
                    : 0
            });
        });
    };

    /**
     * Parse a displayed date of a location window row into an ISO date.
     *
     * @param {String} value The displayed date value.
     * @return {String|null} Returns the ISO date ("YYYY-MM-DD") or NULL when unparseable.
     */
    ServicesHelper.parseLocationWindowDate = function (value) {
        if (!value) {
            return null;
        }

        var separators = {DMY: '/', DDMY: '.', MDY: '/', YMD: '/'};
        var order = {DMY: ['dd', 'mm', 'yyyy'], DDMY: ['dd', 'mm', 'yyyy'],
            MDY: ['mm', 'dd', 'yyyy'], YMD: ['yyyy', 'mm', 'dd']};
        var format = GlobalVariables.dateFormat;

        if (!order[format]) {
            return null;
        }

        var parts = value.split(separators[format]);

        if (parts.length !== 3) {
            return null;
        }

        var positions = {};
        order[format].forEach(function (token, index) {
            positions[token] = parts[index].length === 2 && token === 'yyyy'
                ? '20' + parts[index]
                : parts[index];
        });

        if (!positions.yyyy || !positions.mm || !positions.dd
            || positions.mm < 1 || positions.mm > 12 || positions.dd < 1 || positions.dd > 31) {
            return null;
        }

        return positions.yyyy + '-' + ('0' + positions.mm).slice(-2) + '-'
            + ('0' + positions.dd).slice(-2);
    };

    /**
     * Validate the location override rows and highlight offending rows in place.
     *
     * Every row checks `from < to`; the weekday rows are compared inside their selected day and
     * the date rows inside their ISO date (first-match-wins resolution is unverifiable when
     * the borders overlap). Errors are thrown as rollback, but the row also stays marked, so
     * the staff can find it without searching.
     *
     * @throws {Error} When a row has an invalid time range or overlapping windows.
     */
    ServicesHelper.validateLocationWindows = function () {
        var reportError = function ($row, message) {
            $row.addClass('has-error');
            $row.find('input, select').addClass('is-invalid');
            throw new Error(message);
        };

        var checkRows = function ($rows, message) {
            var previous = null;

            $rows
                .sort(function (first, second) {
                    return $(first).find('.location-window-from').val()
                        < $(second).find('.location-window-from').val() ? -1 : 1;
                })
                .each(function (index, row) {
                    var $row = $(row);
                    var from = $row.find('.location-window-from').val();
                    var to = $row.find('.location-window-to').val();
                    var location = $row.find('.location-window-location').val();

                    if (!from || !to || !location) {
                        return;
                    }

                    var fromParts = from.split(':');
                    var toParts = to.split(':');
                    var fromMinutes = Number(fromParts[0]) * 60 + Number(fromParts[1]);
                    var toMinutes = Number(toParts[0]) * 60 + Number(toParts[1]);

                    if (fromMinutes >= toMinutes) {
                        reportError($row, EALang.location_window_invalid);
                    }

                    if (previous !== null && previous.to > from) {
                        reportError($row, EALang.location_window_overlap);
                    }

                    previous = {from: from, to: to};
                });
        };

        // The weekday rows are grouped by their selected day: each day is its own layer and
        // its windows are compared with each other only.
        var rowsByWeekday = {};

        $('#services .service-location-weekdays .location-window-row').each(function (index, row) {
            var $row = $(row);
            var weekday = $row.find('.location-window-day').val();

            rowsByWeekday[weekday] = rowsByWeekday[weekday] || [];
            rowsByWeekday[weekday].push($row);
        });

        Object.keys(rowsByWeekday).forEach(function (weekday) {
            checkRows(rowsByWeekday[weekday]);
        });

        // The date layer groups its rows by the entered date: each date is its own layer
        // and its windows are compared with each other only.
        var rowsByDate = {};

        $('#services .service-location-date-overrides .location-window-row').each(function (index, row) {
            var $row = $(row);
            var date = ServicesHelper.parseLocationWindowDate(
                $row.find('.location-window-date').val());

            if (!date) {
                reportError($row, EALang.location_window_invalid);
            }

            rowsByDate[date] = rowsByDate[date] || [];
            rowsByDate[date].push($row);
        });

        Object.keys(rowsByDate).forEach(function (date) {
            checkRows(rowsByDate[date]);
        });
    };

    /**
     * Read the weekday location windows of the editor into the JSON-layer shape.
     *
     * @return {Object} Map of weekday index to its non-empty window rows.
     */
    ServicesHelper.collectWeekdayLocationWindows = function () {
        var weekdayWindows = {};

        $('#services .service-location-weekdays .location-window-row').each(function (index, row) {
            var $row = $(row);
            var weekday = $row.find('.location-window-day').val();
            var from = $row.find('.location-window-from').val();
            var to = $row.find('.location-window-to').val();
            var location = $row.find('.location-window-location').val();

            if (!weekday || !from || !to || !location) {
                return;
            }

            weekdayWindows[weekday] = weekdayWindows[weekday] || [];
            weekdayWindows[weekday].push({from: from, to: to, location: location});
        });

        return weekdayWindows;
    };

    /**
     * Read the date location windows of the editor into the JSON-layer shape.
     *
     * @return {Object} Map of ISO date to its non-empty window rows.
     */
    ServicesHelper.collectDateLocationWindows = function () {
        var dateWindows = {};

        $('#services .service-location-date-overrides .location-window-row').each(function (index, row) {
            var $row = $(row);
            var date = ServicesHelper.parseLocationWindowDate(
                $row.find('.location-window-date').val());
            var from = $row.find('.location-window-from').val();
            var to = $row.find('.location-window-to').val();
            var location = $row.find('.location-window-location').val();

            if (!date || !from || !to || !location) {
                return;
            }

            dateWindows[date] = dateWindows[date] || [];
            dateWindows[date].push({from: from, to: to, location: location});
        });

        return dateWindows;
    };

    /**
     * Render the weekday location windows of a service into the editor.
     *
     * @param {String|Object|null} raw JSON string or object of the `location_weekday_modes` layer.
     */
    ServicesHelper.renderLocationWeekdayWindows = function (raw) {
        var layers = {};

        try {
            layers = typeof raw === 'string' ? JSON.parse(raw || 'null') || {} : (raw || {});
        } catch (exception) {
            layers = {};
        }

        $('#services .service-location-weekdays tbody').empty();

        var $tbody = $('#services .service-location-weekdays tbody');

        Object.keys(layers).forEach(function (weekday) {
            if (!Array.isArray(layers[weekday])) {
                return;
            }

            layers[weekday].forEach(function (window) {
                var $row = ServicesHelper.buildLocationWindowRow(
                    window.from || '',
                    window.to || '',
                    window.location || ''
                );

                $row.find('.location-window-day').val(weekday);

                $tbody.append($row);
            });
        });

        ServicesHelper.attachLocationWindowPickers($tbody);
    };

    /**
     * Render the date location windows of a service into the editor.
     *
     * @param {String|Object|null} raw JSON string or object of the `location_date_overrides` layer.
     */
    ServicesHelper.renderLocationDateWindows = function (raw) {
        var layers = {};

        try {
            layers = typeof raw === 'string' ? JSON.parse(raw || 'null') || {} : (raw || {});
        } catch (exception) {
            layers = {};
        }

        var $tbody = $('#services .service-location-date-overrides tbody');

        $tbody.empty();

        Object.keys(layers).forEach(function (date) {
            if (!Array.isArray(layers[date])) {
                return;
            }

            layers[date].forEach(function (window) {
                $tbody.append(ServicesHelper.buildLocationWindowRow(
                    window.from || '',
                    window.to || '',
                    window.location || '',
                    GeneralFunctions.formatDate(date, GlobalVariables.dateFormat)
                ));
            });
        });

        ServicesHelper.attachLocationWindowPickers($tbody);
    };

    /**
     * Filters service records depending a string key.
     *
     * @param {String} key This is used to filter the service records of the database.
     * @param {Number} selectId Optional, if set then after the filter operation the record with this
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    ServicesHelper.prototype.filter = function (key, selectId, display) {
        display = display || false;

        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_services';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            key: key,
            limit: this.filterLimit
        };

        $.post(url, data)
            .done(function (response) {
                this.filterResults = response;

                $('#filter-services .results').empty();

                response.forEach(function (service, index) {
                    $('#filter-services .results')
                        .append(ServicesHelper.prototype.getFilterHtml(service))
                        .append($('<hr/>'))
                });

                if (response.length === 0) {
                    $('#filter-services .results').append(
                        $('<em/>', {
                            'text': EALang.no_records_found
                        })
                    );
                } else if (response.length === this.filterLimit) {
                    $('<button/>', {
                        'type': 'button',
                        'class': 'btn btn-block btn-outline-secondary load-more text-center',
                        'text': EALang.load_more,
                        'click': function () {
                            this.filterLimit += 20;
                            this.filter(key, selectId, display);
                        }.bind(this)
                    })
                        .appendTo('#filter-services .results');
                }

                if (selectId) {
                    this.select(selectId, display);
                }
            }.bind(this));
    };

    /**
     * Get Filter HTML
     *
     * Get a service row HTML code that is going to be displayed on the filter results list.
     *
     * @param {Object} service Contains the service record data.
     *
     * @return {String} The HTML code that represents the record on the filter results list.
     */
    ServicesHelper.prototype.getFilterHtml = function (service) {
        var name = service.name;

        //var info = service.duration + ' min - ' + service.price + ' ' + service.currency;
        var info = service.duration + ' min';

        return $('<div/>', {
            'class': 'service-row entry',
            'data-id': service.id,
            'html': [
                $('<strong/>', {
                    'text': name
                }),
                $('<br/>'),
                $('<span/>', {
                    'text': info
                }),
                $('<br/>')
            ]
        });
    };

    /**
     * Select a specific record from the current filter results. If the service id does not exist
     * in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true then the method will display the record on the form.
     */
    ServicesHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-services .selected').removeClass('selected');

        $('#filter-services .service-row[data-id="' + id + '"]').addClass('selected');

        if (display) {
            var service = this.filterResults.find(function (filterResult) {
                return Number(filterResult.id) === Number(id);
            });

            this.display(service);

            $('#edit-service, #delete-service').prop('disabled', false);
        }
    };

    window.ServicesHelper = ServicesHelper;
})();
