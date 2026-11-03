/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

/**
 * Backend Calendar
 *
 * This module implements the default calendar view of backend.
 *
 * @module BackendCalendarDefaultView
 */
window.BackendCalendarDefaultView = window.BackendCalendarDefaultView || {};

(function (exports) {
    'use strict';

    // Constants
    var FILTER_TYPE_PROVIDER = 'provider';
    var FILTER_TYPE_SERVICE = 'service';

    // Variables
    var lastFocusedEventData; // Contains event data for later use.
    var calendar; // FullCalendar v6 instance.

    /**
     * Bind event handlers for the calendar view.
     */
    function bindEventHandlers() {
        var $calendarPage = $('#calendar-page');

        /**
         * Event: Reload Button "Click"
         *
         * When the user clicks the reload button an the calendar items need to be refreshed.
         */
        $('#reload-appointments').on('click', function () {
            refreshCalendarAppointments();
        });

        /**
         * Event: Popover Close Button "Click"
         *
         * Hides the open popover element.
         */
        $calendarPage.on('click', '.close-popover', function () {
            $(this).parents('.popover').popover('dispose');
        });

        /**
         * Event: Popover Edit Button "Click"
         *
         * Enables the edit dialog of the selected calendar event.
         */
        $calendarPage.on('click', '.edit-popover', function () {
            $(this).parents('.popover').popover('dispose');

            var $dialog;
            var startDatetime;
            var endDatetime;

            if (lastFocusedEventData.workingPlanException) {
                var date = lastFocusedEventData.date;
                var workingPlanException = lastFocusedEventData.workingPlanException;
                var provider = lastFocusedEventData.provider;

                WorkingPlanExceptionsModal
                    .edit(date, workingPlanException)
                    .done(function (date, workingPlanException) {
                        var successCallback = function () {
                            Backend.displayNotification(EALang.working_plan_exception_saved);

                            var workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions) || {};

                            workingPlanExceptions[date] = workingPlanException;

                            for (var index in GlobalVariables.availableProviders) {
                                var availableProvider = GlobalVariables.availableProviders[index];

                                if (Number(availableProvider.id) === Number(provider.id)) {
                                    availableProvider.settings.working_plan_exceptions = JSON.stringify(workingPlanExceptions);
                                    break;
                                }
                            }

                            $('#select-filter-item').trigger('change'); // Update the calendar.
                        };

                        BackendCalendarApi.saveWorkingPlanException(date, workingPlanException, provider.id, successCallback, null);
                    });
            } else if (lastFocusedEventData.is_unavailable === '0') {
                var appointment = lastFocusedEventData;
                $dialog = $('#manage-appointment');

                BackendCalendarAppointmentsModal.resetAppointmentDialog();

                // Apply appointment data and show modal dialog.
                $dialog.find('.modal-header h3').text(EALang.edit_appointment_title);
                $dialog.find('#appointment-id').val(appointment.id);
                $dialog.find('#select-service').val(appointment.id_services).trigger('change');
                $dialog.find('#select-provider').val(appointment.id_users_provider);

                // Set the start and end datetime of the appointment.
                startDatetime = Date.parseExact(appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss');
                $dialog.find('#start-datetime').datetimepicker('setDate', startDatetime);

                endDatetime = Date.parseExact(appointment.end_datetime, 'yyyy-MM-dd HH:mm:ss');
                $dialog.find('#end-datetime').datetimepicker('setDate', endDatetime);

                var customer = appointment.customer;
                $dialog.find('#customer-id').val(appointment.id_users_customer);
                $dialog.find('#first-name').val(customer.first_name);
                $dialog.find('#last-name').val(customer.last_name);
                $dialog.find('#email').val(customer.email);
                $dialog.find('#phone-number').val(customer.phone_number);
                $dialog.find('#address').val(customer.address);
                $dialog.find('#city').val(customer.city);
                $dialog.find('#zip-code').val(customer.zip_code);
                GeneralFunctions.applyAppointmentLocation($dialog, appointment.location);
                $dialog.find('#appointment-notes').val(appointment.notes);
                $dialog.find('#customer-notes').val(customer.notes);
                $dialog.modal('show');
            } else {
                var unavailable = lastFocusedEventData;

                // Replace string date values with actual date objects.
                unavailable.start_datetime = moment(lastFocusedEventData.start).format('YYYY-MM-DD HH:mm:ss');
                startDatetime = Date.parseExact(unavailable.start_datetime, 'yyyy-MM-dd HH:mm:ss');
                unavailable.end_datetime = moment(lastFocusedEventData.end).format('YYYY-MM-DD HH:mm:ss');
                endDatetime = Date.parseExact(unavailable.end_datetime, 'yyyy-MM-dd HH:mm:ss');

                $dialog = $('#manage-unavailable');
                BackendCalendarUnavailabilityEventsModal.resetUnavailableDialog();

                // Apply unavailable data to dialog.
                $dialog.find('.modal-header h3').text(EALang.edit_unavailable_title);
                $dialog.find('#unavailable-start').datetimepicker('setDate', startDatetime);
                $dialog.find('#unavailable-id').val(unavailable.id);
                $dialog.find('#unavailable-provider').val(unavailable.id_users_provider);
                $dialog.find('#unavailable-end').datetimepicker('setDate', endDatetime);
                $dialog.find('#unavailable-notes').val(unavailable.notes);
                $dialog.modal('show');
            }
        });

        /**
         * Event: Popover Delete Button "Click"
         *
         * Displays a prompt on whether the user wants the appointment to be deleted. If he confirms the
         * deletion then an AJAX call is made to the server and deletes the appointment from the database.
         */
        $calendarPage.on('click', '.delete-popover', function () {
            $(this).parents('.popover').popover('dispose');

            var url;
            var data;

            if (lastFocusedEventData.workingPlanException) {
                var providerId = $('#select-filter-item').val();

                var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
                    return Number(availableProvider.id) === Number(providerId);
                });

                if (!provider) {
                    throw new Error('Provider could not be found: ' + providerId);
                }

                var successCallback = function () {
                    Backend.displayNotification(EALang.working_plan_exception_deleted);

                    var workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions) || {};
                    delete workingPlanExceptions[date];

                    for (var index in GlobalVariables.availableProviders) {
                        var availableProvider = GlobalVariables.availableProviders[index];

                        if (Number(availableProvider.id) === Number(providerId)) {
                            availableProvider.settings.working_plan_exceptions = JSON.stringify(workingPlanExceptions);
                            break;
                        }
                    }

                    $('#select-filter-item').trigger('change'); // Update the calendar.
                };

                var date = moment(lastFocusedEventData.start).format('YYYY-MM-DD');

                BackendCalendarApi.deleteWorkingPlanException(date, providerId, successCallback);
            } else if (lastFocusedEventData.is_unavailable === '0') {
                var buttons = [
                    {
                        text: EALang.cancel,
                        click: function () {
                            $('#message-box').dialog('close');
                        }
                    },
                    {
                        text: EALang.cancel_2,
                        click: function () {
                            url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_appointment';

                            data = {
                                csrfToken: GlobalVariables.csrfToken,
                                appointment_id: lastFocusedEventData.id,
                                delete_reason: $('#delete-reason').val()
                            };

                            $.post(url, data)
                                .done(function () {
                                    $('#message-box').dialog('close');

                                    // Refresh calendar event items.
                                    $('#select-filter-item').trigger('change');
                                });
                        }
                    }
                ];

                GeneralFunctions.displayMessageBox(EALang.delete_appointment_title,
                    EALang.write_appointment_removal_reason, buttons);

                $('<textarea/>', {
                    'class': 'form-control w-100',
                    'id': 'delete-reason',
                    'rows': '3'
                })
                    .appendTo('#message-box');
            } else {
                // Do not display confirmation prompt.
                url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_unavailable';

                data = {
                    csrfToken: GlobalVariables.csrfToken,
                    unavailable_id: lastFocusedEventData.id
                };

                $.post(url, data)
                    .done(function () {
                        $('#message-box').dialog('close');

                        // Refresh calendar event items.
                        $('#select-filter-item').trigger('change');
                    });
            }
        });

        /**
         * Event: Calendar Filter Item "Change"
         *
         * Load the appointments that correspond to the select filter item and display them on the calendar.
         */
        $('#select-filter-item').on('change', function () {
            // If current value is service, then the add buttons must be disabled.
            if ($('#select-filter-item option:selected').attr('type') === FILTER_TYPE_SERVICE) {
                $('#insert-appointment, #insert-dropdown').prop('disabled', true);
                calendar.setOption('selectable', false);
                calendar.setOption('editable', false);
            } else {
                $('#insert-appointment, #insert-dropdown').prop('disabled', false);
                calendar.setOption('selectable', true);
                calendar.setOption('editable', true);

                var providerId = $('#select-filter-item').val();

                var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
                    return Number(availableProvider.id) === Number(providerId);
                });

                if (provider && provider.timezone) {
                    $('.provider-timezone').text(GlobalVariables.timezones[provider.timezone]);
                }
            }

            // FullCalendar refetches its events automatically on option changes.
            calendar.refetchEvents();
        });
    }

    /**
     * Get Calendar Component Height
     *
     * This method calculates the proper calendar height, in order to be displayed correctly, even when the
     * browser window is resizing.
     *
     * @return {Number} Returns the calendar element height in pixels.
     */
    function getCalendarHeight() {
        var result = window.innerHeight - $('#footer').outerHeight() - $('#header').outerHeight()
            - $('#calendar-toolbar').outerHeight() - 60; // 60 for fine tuning
        return (result > 500) ? result : 500; // Minimum height is 500px
    }

    /**
     * Get the event notes for the popup widget.
     *
     * @param {Event} event
     */
    function getEventNotes(event) {
        if (!event.extendedProps.data || !event.extendedProps.data.notes) {
            return '-';
        }

        var notes = event.extendedProps.data.notes;

        return notes.length > 100 ? notes.substring(0, 100) + '...' : notes;
    }

    /**
     * Calendar Event "Click" Callback
     *
     * When the user clicks on an appointment object on the calendar, then a data preview popover is display
     * above the calendar item.
     */
    function calendarEventClick(info) {
        $('.popover').popover('dispose'); // Close all open popovers.

        var event = info.event;

        var $html;
        var displayEdit;
        var displayDelete;

        var isUnavailableEvent = event.extendedProps.data
            && event.extendedProps.data.is_unavailable === '1';
        var isWorkingPlanExceptionEvent = event.extendedProps.data
            && Boolean(event.extendedProps.data.workingPlanException);

        var jsEvent = info.jsEvent;
        var $parent = $(jsEvent.target.offsetParent);
        var $altParent = $(jsEvent.target).parents().eq(1);

        if (isUnavailableEvent) {
            displayEdit = (isUnavailableEvent
                && GlobalVariables.user.privileges.appointments.edit === true)
                ? 'mr-2' : 'd-none';
            displayDelete = (isUnavailableEvent
                && GlobalVariables.user.privileges.appointments.delete === true)
                ? 'mr-2' : 'd-none'; // Same value at the time.

            $html = $('<div/>', {
                'html': [
                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.start
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(moment(event.start).format('YYYY-MM-DD HH:mm:ss'), GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.end
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(moment(event.end).format('YYYY-MM-DD HH:mm:ss'), GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'text': EALang.notes
                    }),
                    $('<span/>', {
                        'text': getEventNotes(event)
                    }),
                    $('<br/>'),

                    $('<hr/>'),

                    $('<div/>', {
                        'class': 'd-flex justify-content-center',
                        'html': [
                            $('<button/>', {
                                'class': 'close-popover btn btn-outline-secondary mr-2',
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-ban mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.close
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'delete-popover btn btn-outline-secondary ' + displayDelete,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-trash-alt mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.delete
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'edit-popover btn btn-primary ' + displayEdit,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-edit mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.edit
                                    })
                                ]
                            })
                        ]
                    })
                ]
            });
        } else if (isWorkingPlanExceptionEvent) {
            displayEdit = (isWorkingPlanExceptionEvent
                && GlobalVariables.user.privileges.appointments.edit === true)
                ? 'mr-2' : 'd-none';
            displayDelete = (isWorkingPlanExceptionEvent
                && GlobalVariables.user.privileges.appointments.delete === true)
                ? 'mr-2' : 'd-none'; // Same value at the time.

            $html = $('<div/>', {
                'html': [
                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.provider
                    }),
                    $('<span/>', {
                        'text': event.extendedProps.data ? event.extendedProps.data.provider.first_name + ' ' + event.extendedProps.data.provider.last_name : '-'
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.start
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(event.extendedProps.data.date + ' ' + event.extendedProps.data.workingPlanException.start, GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.end
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(event.extendedProps.data.date + ' ' + event.extendedProps.data.workingPlanException.end, GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.timezone
                    }),
                    $('<span/>', {
                        'text': GlobalVariables.timezones[event.extendedProps.data.provider.timezone]
                    }),
                    $('<br/>'),

                    $('<hr/>'),

                    $('<div/>', {
                        'class': 'd-flex justify-content-between',
                        'html': [
                            $('<button/>', {
                                'class': 'close-popover btn btn-outline-secondary mr-2',
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-ban mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.close
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'delete-popover btn btn-outline-secondary ' + displayDelete,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-trash-alt mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.delete
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'edit-popover btn btn-primary ' + displayEdit,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-edit mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.edit
                                    })
                                ]
                            }),
                        ]
                    })
                ]
            });
        } else {
            displayEdit = (GlobalVariables.user.privileges.appointments.edit === true)
                ? 'mr-2' : 'd-none';
            displayDelete = (GlobalVariables.user.privileges.appointments.delete === true)
                ? 'mr-2' : 'd-none';

            $html = $('<div/>', {
                'html': [
                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.start
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(moment(event.start).format('YYYY-MM-DD HH:mm:ss'), GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.end
                    }),
                    $('<span/>', {
                        'text': GeneralFunctions.formatDate(moment(event.end).format('YYYY-MM-DD HH:mm:ss'), GlobalVariables.dateFormat, true)
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.timezone
                    }),
                    $('<span/>', {
                        'text': GlobalVariables.timezones[event.extendedProps.data.provider.timezone]
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.service
                    }),
                    $('<span/>', {
                        'text': event.extendedProps.data.service.name
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.provider
                    }),
                    GeneralFunctions.renderMapIcon(event.extendedProps.data.provider),
                    $('<span/>', {
                        'text': event.extendedProps.data.provider.first_name + ' ' + event.extendedProps.data.provider.last_name
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.customer
                    }),
                    GeneralFunctions.renderMapIcon(event.extendedProps.data.customer),
                    $('<span/>', {
                        'class': 'd-inline-block ml-1',
                        'text': event.extendedProps.data.customer.first_name + ' ' + event.extendedProps.data.customer.last_name
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.email
                    }),
                    GeneralFunctions.renderMailIcon(event.extendedProps.data.customer.email),
                    $('<span/>', {
                        'class': 'd-inline-block ml-1',
                        'text': event.extendedProps.data.customer.email
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.phone
                    }),
                    GeneralFunctions.renderPhoneIcon(event.extendedProps.data.customer.phone_number),
                    $('<span/>', {
                        'class': 'd-inline-block ml-1',
                        'text': event.extendedProps.data.customer.phone_number
                    }),
                    $('<br/>'),

                    $('<strong/>', {
                        'class': 'd-inline-block mr-2',
                        'text': EALang.notes
                    }),
                    $('<span/>', {
                        'class': 'd-inline-block ml-1',
                        'text': getEventNotes(event)
                    }),
                    $('<br/>'),

                    $('<hr/>'),

                    $('<div/>', {
                        'class': 'd-flex justify-content-center',
                        'html': [
                            $('<button/>', {
                                'class': 'close-popover btn btn-outline-secondary mr-2',
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-ban mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.close
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'delete-popover btn btn-outline-secondary ' + displayDelete,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-trash-alt mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.cancel_2
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'class': 'edit-popover btn btn-primary ' + displayEdit,
                                'html': [
                                    $('<i/>', {
                                        'class': 'fas fa-edit mr-2'
                                    }),
                                    $('<span/>', {
                                        'text': EALang.edit
                                    })
                                ]
                            })
                        ]
                    })
                ]
            });
        }

        $(jsEvent.target).popover({
            placement: 'right',
            title: GeneralFunctions.escapeHtml(event.title),
            content: $html,
            html: true,
            container: '#calendar',
            trigger: 'manual'
        });

        lastFocusedEventData = event.extendedProps.data;

        $(jsEvent.target).popover('toggle');

    }

    /**
     * Calendar Event "Resize" Callback
     *
     * The user can change the duration of an event by resizing an appointment object on the calendar. This
     * change needs to be stored to the database too and this is done via an ajax call.
     *
     * @see updateAppointmentData()
     */
    function calendarEventResize(info) {
        var event = info.event;
        var oldEvent = info.oldEvent;
        var delta = {
            days: moment(event.end).diff(moment(info.oldEvent.end), 'days'),
            hours: moment(event.end).diff(moment(oldEvent.end), 'hours'),
            minutes: moment(event.end).diff(moment(oldEvent.end), 'minutes')
        };
        var revertFunc = info.revert;

        if (GlobalVariables.user.privileges.appointments.edit === false) {
            revertFunc();
            Backend.displayNotification(EALang.no_privileges_edit_appointments);
            return;
        }

        var $calendar = $('#calendar');
        var successCallback;

        if ($('#notification').is(':visible')) {
            $('#notification').hide('bind');
        }

        if (Boolean(Number(event.extendedProps.data.is_unavailable)) === false) {
            // Prepare appointment data.
            event.extendedProps.data.end_datetime = Date.parseExact(
                event.extendedProps.data.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                .add({days: delta.days(), hours: delta.hours(), minutes: delta.minutes()})
                .toString('yyyy-MM-dd HH:mm:ss');

            var appointment = GeneralFunctions.clone(event.extendedProps.data);

            // Must delete the following because only appointment data should be provided to the AJAX call.
            delete appointment.customer;
            delete appointment.provider;
            delete appointment.service;

            // Success callback
            successCallback = function () {
                // Display success notification to user.
                var undoFunction = function () {
                    appointment.end_datetime = event.extendedProps.data.end_datetime = Date.parseExact(
                        appointment.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({days: -delta.days(), hours: -delta.hours(), minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_appointment';

                    var data = {
                        csrfToken: GlobalVariables.csrfToken,
                        appointment_data: JSON.stringify(appointment)
                    };

                    $.post(url, data)
                        .done(function () {
                            $('#notification').hide('blind');
                        });

                    revertFunc();
                };

                Backend.displayNotification(EALang.appointment_updated, [
                    {
                        'label': EALang.undo,
                        'function': undoFunction
                    }
                ]);
                $('#footer').css('position', 'static'); // Footer position fix.

            };

            // Update appointment data.
            BackendCalendarApi.saveAppointment(appointment, null, successCallback);
        } else {
            // Update unavailable time period.
            var unavailable = {
                id: event.extendedProps.data.id,
                start_datetime: event.start.format('YYYY-MM-DD HH:mm:ss'),
                end_datetime: event.end.format('YYYY-MM-DD HH:mm:ss'),
                id_users_provider: event.extendedProps.data.id_users_provider
            };

            event.extendedProps.data.end_datetime = unavailable.end_datetime;

            // Define success callback function.
            successCallback = function () {
                // Display success notification to user.
                var undoFunction = function () {
                    unavailable.end_datetime = event.extendedProps.data.end_datetime = Date.parseExact(
                        unavailable.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_unavailable';

                    var data = {
                        csrfToken: GlobalVariables.csrfToken,
                        unavailable: JSON.stringify(unavailable)
                    };

                    $.post(url, data)
                        .done(function () {
                            $('#notification').hide('blind');
                        });

                    revertFunc();
                };

                Backend.displayNotification(EALang.unavailable_updated, [
                    {
                        'label': EALang.undo,
                        'function': undoFunction
                    }
                ]);

                $('#footer').css('position', 'static'); // Footer position fix.

            };

            BackendCalendarApi.saveUnavailable(unavailable, successCallback, null);
        }
    }

    /**
     * Calendar Window "Resize" Callback
     *
     * The calendar element needs to be re-sized too in order to fit into the window. Nevertheless, if the window
     * becomes very small the the calendar won't shrink anymore.
     *
     * @see getCalendarHeight()
     */
    function calendarWindowResize() {
        calendar.setOption('height', getCalendarHeight());
    }

    /**
     * Calendar Day "Click" Callback
     *
     * When the user clicks on a day square on the calendar, then he will automatically be transferred to that
     * day view calendar.
     *
     * @param {Date} date
     */
    function calendarDateClick(info) {
        if ( ! info.allDay) {
            calendar.changeView('timeGridDay');
            calendar.gotoDate(info.date);
        }
    }

    /**
     * Calendar Event "Drop" Callback
     *
     * This event handler is triggered whenever the user drags and drops an event into a different position
     * on the calendar. We need to update the database with this change. This is done via an ajax call.
     *
     * @param {object} event
     * @param {object} delta
     * @param {function} revertFunc
     */
    function calendarEventDrop(info) {
        $('.popover').popover('dispose'); // Close all open popovers.

        var event = info.event;
        var delta = {
            days: moment(event.start).diff(moment(info.oldEvent.start), 'days'),
            hours: moment(event.start).diff(moment(info.oldEvent.start), 'hours'),
            minutes: moment(event.start).diff(moment(info.oldEvent.start), 'minutes')
        };
        var revertFunc = info.revert;

        if (GlobalVariables.user.privileges.appointments.edit === false) {
            revertFunc();
            Backend.displayNotification(EALang.no_privileges_edit_appointments);
            return;
        }

        if ($('#notification').is(':visible')) {
            $('#notification').hide('bind');
        }

        var successCallback;

        if (event.extendedProps.data.is_unavailable === '0') {
            // Prepare appointment data.
            var appointment = GeneralFunctions.clone(event.extendedProps.data);

            // Must delete the following because only appointment data should be provided to the ajax call.
            delete appointment.customer;
            delete appointment.provider;
            delete appointment.service;

            appointment.start_datetime = Date.parseExact(
                appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss')
                .add({days: delta.days(), hours: delta.hours(), minutes: delta.minutes()})
                .toString('yyyy-MM-dd HH:mm:ss');

            appointment.end_datetime = Date.parseExact(
                appointment.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                .add({days: delta.days(), hours: delta.hours(), minutes: delta.minutes()})
                .toString('yyyy-MM-dd HH:mm:ss');

            event.extendedProps.data.start_datetime = appointment.start_datetime;
            event.extendedProps.data.end_datetime = appointment.end_datetime;

            // Define success callback function.
            successCallback = function () {
                // Define the undo function, if the user needs to reset the last change.
                var undoFunction = function () {
                    appointment.start_datetime = Date.parseExact(
                        appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({days: -delta.days(), hours: -delta.hours(), minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    appointment.end_datetime = Date.parseExact(
                        appointment.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({days: -delta.days(), hours: -delta.hours(), minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    event.extendedProps.data.start_datetime = appointment.start_datetime;
                    event.extendedProps.data.end_datetime = appointment.end_datetime;

                    var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_appointment';

                    var data = {
                        csrfToken: GlobalVariables.csrfToken,
                        appointment_data: JSON.stringify(appointment)
                    };

                    $.post(url, data)
                        .done(function () {
                            $('#notification').hide('blind');
                        });

                    revertFunc();
                };

                Backend.displayNotification(EALang.appointment_updated, [
                    {
                        'label': EALang.undo,
                        'function': undoFunction
                    }
                ]);

                $('#footer').css('position', 'static'); // Footer position fix.
            };

            // Update appointment data.
            BackendCalendarApi.saveAppointment(appointment, null, successCallback);
        } else {
            // Update unavailable time period.
            var unavailable = {
                id: event.extendedProps.data.id,
                start_datetime: event.start.format('YYYY-MM-DD HH:mm:ss'),
                end_datetime: event.end.format('YYYY-MM-DD HH:mm:ss'),
                id_users_provider: event.extendedProps.data.id_users_provider
            };

            successCallback = function () {
                var undoFunction = function () {
                    unavailable.start_datetime = Date.parseExact(
                        unavailable.start_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({days: -delta.days(), minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    unavailable.end_datetime = Date.parseExact(
                        unavailable.end_datetime, 'yyyy-MM-dd HH:mm:ss')
                        .add({days: -delta.days(), minutes: -delta.minutes()})
                        .toString('yyyy-MM-dd HH:mm:ss');

                    event.extendedProps.data.start_datetime = unavailable.start_datetime;
                    event.extendedProps.data.end_datetime = unavailable.end_datetime;

                    var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_unavailable';
                    var data = {
                        csrfToken: GlobalVariables.csrfToken,
                        unavailable: JSON.stringify(unavailable)
                    };

                    $.post(url, data)
                        .done(function () {
                            $('#notification').hide('blind');
                        });

                    revertFunc();
                };

                Backend.displayNotification(EALang.unavailable_updated, [
                    {
                        label: EALang.undo,
                        function: undoFunction
                    }
                ]);

                $('#footer').css('position', 'static'); // Footer position fix.
            };

            BackendCalendarApi.saveUnavailable(unavailable, successCallback);
        }
    }

    /**
     * Calendar "View Render" Callback
     *
     * Whenever the calendar changes or refreshes its view certain actions need to be made, in order to
     * display proper information to the user.
     */
    function calendarDatesSet(info) {
        if ($('#select-filter-item').val() === null) {
            return;
        }


        $(window).trigger('resize'); // Places the footer on the bottom.

        // Remove all open popovers.
        $('.close-popover').each(function (index, closePopoverButton) {
            $(closePopoverButton).parents('.popover').popover('dispose');
        });

        // TODO: Try to fix the repositioning of the new popovers
        // Add new pop overs.
        /*
        $('.fv-events').each(function (index, eventElement) {
            $(eventElement).popover();
        });
        */
    }

    /**
     * Refresh Calendar Appointments
     *
     * This method reloads the registered appointments for the selected date period and filter type.
     *
     * @param {Object} $calendar The calendar jQuery object.
     * @param {Number} recordId The selected record id.
     * @param {String} filterType The filter type, could be either FILTER_TYPE_PROVIDER or FILTER_TYPE_SERVICE.
     * @param {Date} startDate Visible start date of the calendar.
     * @param {Date} endDate Visible end date of the calendar.
     */
    /**
     * Build the complete event source of the calendar (appointments, unavailable periods,
     * provider working plan entries: breaks, non-working hours and exceptions).
     *
     * @param {Object} response The fetch response.
     * @param {String} recordId The selected record id.
     * @param {String} filterType The selected filter type.
     * @param {Object} periodStart Visible start of the calendar (moment).
     * @param {Object} periodEnd Visible end of the calendar (moment).
     * @param {String} viewName The current FullCalendar view name.
     *
     * @return {Object[]}
     */
    /**
     * Fetch the calendar rows for the selected date period and record.
     *
     * @param {String} recordId The selected record id.
     * @param {String} filterType The selected filter type.
     * @param {Object} periodStart Visible start of the calendar (moment).
     * @param {Object} periodEnd Visible end of the calendar (moment).
     *
     * @return {jqXHR}
     */
    function fetchCalendarEvents(recordId, filterType, periodStart, periodEnd) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_get_calendar_appointments';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            record_id: recordId,
            start_date: periodStart.format('YYYY-MM-DD'),
            end_date: periodEnd.format('YYYY-MM-DD'),
            filter_type: filterType
        };

        return $.post(url, data);
    }

    function buildEventSource(response, recordId, filterType, periodStart, periodEnd, viewName) {
        var calendarEventSource = [];

response.appointments.forEach(function (appointment) {
                    var appointmentEvent = {
                        id: appointment.id,
                        title: appointment.service.name + ' - '
                            + appointment.customer.first_name + ' '
                            + appointment.customer.last_name,
                        start: moment(appointment.start_datetime).toDate(),
                        end: moment(appointment.end_datetime).toDate(),
                        allDay: false,
                        extendedProps: {data: appointment} // Store appointment data for later use.
                    };

                    calendarEventSource.push(appointmentEvent);
                });

                // Add custom unavailable periods (they are always displayed on the calendar, even if the provider won't
                // work on that day).

        (response.unavailables || []).forEach(function (unavailable) {
                    var notes = unavailable.notes ? ' - ' + unavailable.notes : '';

                    if (unavailable.notes && unavailable.notes.length > 30) {
                        notes = unavailable.notes.substring(0, 30) + '...';
                    }

                    var unavailabilityEvent = {
                        title: EALang.unavailable + notes,
                        start: moment(unavailable.start_datetime).toDate(),
                        end: moment(unavailable.end_datetime).toDate(),
                        allDay: false,
                        color: '#879DB4',
                        editable: true,
                        classNames: ['fc-unavailable', 'fc-custom'],
                        extendedProps: {data: unavailable}
                    };

                    calendarEventSource.push(unavailabilityEvent);
                });

        var calendarView = {
            name: viewName,
            start: periodStart,
            end: periodEnd
        };

        if (filterType === FILTER_TYPE_PROVIDER && viewName !== 'dayGridMonth') {
            var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
                return Number(availableProvider.id) === Number(recordId);
            });

            if (!provider) {
                throw new Error('Provider was not found.');
            }

            var workingPlan = JSON.parse(provider.settings.working_plan);
            var workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions);
            var unavailabilityEvent;
            var viewStart;
            var viewEnd;
            var breakStart;
            var breakEnd;
            var workingPlanExceptionStart;
            var workingPlanExceptionEnd;
            var weekdayNumber;
            var weekdayName;
            var weekdayDate;
            var workingPlanExceptionEvent;
            var startHour;
            var endHour;
            var workDateStart;
            var workDateEnd;
            var calendarDate;

            // Sort the working plan starting with the first day as set in General settings to
            // correctly align breaks in the calendar display.
            var firstWeekdayNumber = GeneralFunctions.getWeekDayId(GlobalVariables.firstWeekday);
            var sortedWorkingPlan = GeneralFunctions.sortWeekDictionary(workingPlan, firstWeekdayNumber);

            switch (viewName) {
                case 'timeGridDay':
                    weekdayNumber = parseInt(periodStart.format('d'));
                    weekdayName = GeneralFunctions.getWeekdayName(weekdayNumber);
                    weekdayDate = periodStart.clone().format('YYYY-MM-DD');

                    // Add working plan exception.
                    if (workingPlanExceptions && workingPlanExceptions[weekdayDate]) {
                        sortedWorkingPlan[weekdayName] = workingPlanExceptions[weekdayDate];
                        workingPlanExceptionStart = weekdayDate + ' ' + sortedWorkingPlan[weekdayName].start;
                        workingPlanExceptionEnd = weekdayDate + ' ' + sortedWorkingPlan[weekdayName].end;

                        workingPlanExceptionEvent = {
                            title: EALang.working_plan_exception,
                            start: moment(workingPlanExceptionStart, 'YYYY-MM-DD HH:mm', true).toDate(),
                            end: moment(workingPlanExceptionEnd, 'YYYY-MM-DD HH:mm', true).add(1, 'day').toDate(),
                            allDay: true,
                            color: '#879DB4',
                            editable: false,
                            classNames: ['fc-working-plan-exception', 'fc-custom'],
                            extendedProps: {
                                data: {
                                    date: weekdayDate,
                                    workingPlanException: workingPlanExceptions[weekdayDate],
                                    provider: provider
                                }
                            }
                        };

                        calendarEventSource.push(workingPlanExceptionEvent);
                    }

                    // Non-working day.
                    if (sortedWorkingPlan[weekdayName] === null) {
                        var fullUnavailabilityEvent = {
                            title: EALang.not_working,
                            start: periodStart.toDate(),
                            end: periodEnd.toDate(),
                            allDay: false,
                            color: '#BEBEBE',
                            editable: false,
                            classNames: ['fc-unavailable']
                        };

                        calendarEventSource.push(fullUnavailabilityEvent);

                        break;
                    }

                    // Add unavailable period before work starts.
                    var startHour = sortedWorkingPlan[weekdayName].start.split(':');
                    var workDateStart = periodStart.clone().startOf('day')
                        .hour(parseInt(startHour[0]))
                        .minute(parseInt(startHour[1]));

                    if (periodStart < workDateStart) {
                        var unavailablePeriodBeforeWorkStarts = {
                            title: EALang.not_working,
                            start: periodStart.toDate(),
                            end: workDateStart.toDate(),
                            allDay: false,
                            color: '#BEBEBE',
                            editable: false,
                            classNames: ['fc-unavailable']
                        };

                        calendarEventSource.push(unavailablePeriodBeforeWorkStarts);
                    }

                    // Add unavailable period after work ends.
                    var endHour = sortedWorkingPlan[weekdayName].end.split(':');
                    var workDateEnd = periodStart.clone().startOf('day')
                        .hour(parseInt(endHour[0]))
                        .minute(parseInt(endHour[1]));

                    if (periodEnd > workDateEnd) {
                        var unavailablePeriodAfterWorkEnds = {
                            title: EALang.not_working,
                            start: workDateEnd.toDate(),
                            end: periodEnd.toDate(),
                            allDay: false,
                            color: '#BEBEBE',
                            editable: false,
                            classNames: ['fc-unavailable']
                        };

                        calendarEventSource.push(unavailablePeriodAfterWorkEnds);
                    }

                    // Add unavailable periods for breaks.
                    sortedWorkingPlan[weekdayName].breaks.forEach(function (breakPeriod) {
                        var breakStartString = breakPeriod.start.split(':');
                        var breakStartTime = periodStart.clone().startOf('day')
                            .hour(parseInt(breakStartString[0]))
                            .minute(parseInt(breakStartString[1]));

                        var breakEndString = breakPeriod.end.split(':');
                        var breakEndTime = periodStart.clone().startOf('day')
                            .hour(parseInt(breakEndString[0]))
                            .minute(parseInt(breakEndString[1]));

                        var unavailablePeriod = {
                            title: EALang.break,
                            start: breakStartTime.toDate(),
                            end: breakEndTime.toDate(),
                            allDay: false,
                            color: '#BEBEBE',
                            editable: false,
                            classNames: ['fc-unavailable', 'fc-break']
                        };

                        calendarEventSource.push(unavailablePeriod);
                    });

                    break;

                case 'timeGridWeek':
                    var calendarDate = periodStart.clone();

                    while (calendarDate < periodEnd) {
                        weekdayNumber = parseInt(calendarDate.format('d'));
                        weekdayName = GeneralFunctions.getWeekdayName(weekdayNumber);
                        weekdayDate = calendarDate.format('YYYY-MM-DD');

                        // Add working plan exception event.
                        if (workingPlanExceptions && workingPlanExceptions[weekdayDate]) {
                            sortedWorkingPlan[weekdayName] = workingPlanExceptions[weekdayDate];

                            workingPlanExceptionStart = weekdayDate + ' '
                                + sortedWorkingPlan[weekdayName].start;
                            workingPlanExceptionEnd = weekdayDate + ' '
                                + sortedWorkingPlan[weekdayName].end;

                            workingPlanExceptionEvent = {
                                title: EALang.working_plan_exception,
                                start: moment(workingPlanExceptionStart, 'YYYY-MM-DD HH:mm', true).toDate(),
                                end: moment(workingPlanExceptionEnd, 'YYYY-MM-DD HH:mm', true).add(1, 'day').toDate(),
                                allDay: true,
                                color: '#879DB4',
                                editable: false,
                                classNames: ['fc-working-plan-exception', 'fc-custom'],
                                extendedProps: {
                                    data: {
                                        date: weekdayDate,
                                        workingPlanException: workingPlanExceptions[weekdayDate],
                                        provider: provider
                                    }
                                }
                            };

                            calendarEventSource.push(workingPlanExceptionEvent);
                        }

                        // Non-working day.

                        var calendarDateStart = calendarDate.clone();
                        var calendarDateEnd = periodEnd.clone();

                        if (sortedWorkingPlan[weekdayName] === null) {
                            // Add a full day unavailable event.
                            unavailabilityEvent = {
                                title: EALang.not_working,
                                start: calendarDate.toDate(),
                                end: calendarDate.clone().add(1, 'day').toDate(),
                                allDay: false,
                                color: '#BEBEBE',
                                editable: false,
                                classNames: ['fc-unavailable']
                            };

                            calendarEventSource.push(unavailabilityEvent);

                            calendarDate.add(1, 'day');

                            continue; // Go to the next loop.
                        }

                        // Add unavailable period before work starts.
                        startHour = sortedWorkingPlan[weekdayName].start.split(':');
                        workDateStart = calendarDate.clone()
                            .hour(parseInt(startHour[0]))
                            .minute(parseInt(startHour[1]));

                        if (calendarDate < workDateStart) {
                            unavailabilityEvent = {
                                title: EALang.not_working,
                                start: calendarDate.toDate(),
                                end: moment(calendarDate.format('YYYY-MM-DD') + ' ' + sortedWorkingPlan[weekdayName].start + ':00').toDate(),
                                allDay: false,
                                color: '#BEBEBE',
                                editable: false,
                                classNames: ['fc-unavailable']
                            };

                            calendarEventSource.push(unavailabilityEvent);
                        }

                        // Add unavailable period after work ends.
                        endHour = sortedWorkingPlan[weekdayName].end.split(':');
                        workDateEnd = calendarDate.clone()
                            .hour(parseInt(endHour[0]))
                            .minute(parseInt(endHour[1]));

                        if ((periodEnd) > workDateEnd) {
                            unavailabilityEvent = {
                                title: EALang.not_working,
                                start: moment(calendarDate.format('YYYY-MM-DD') + ' ' + sortedWorkingPlan[weekdayName].end + ':00').toDate(),
                                end: calendarDate.clone().add(1, 'day').toDate(),
                                allDay: false,
                                color: '#BEBEBE',
                                editable: false,
                                classNames: ['fc-unavailable']
                            };

                            calendarEventSource.push(unavailabilityEvent);
                        }

                        // Add unavailable periods during day breaks.
                        sortedWorkingPlan[weekdayName].breaks.forEach(function (breakPeriod) {
                            var breakStartString = breakPeriod.start.split(':');
                            breakStart = calendarDate.clone()
                                .hour(parseInt(breakStartString[0]))
                                .minute(parseInt(breakStartString[1]));

                            var breakEndString = breakPeriod.end.split(':');
                            breakEnd = calendarDate.clone()
                                .hour(parseInt(breakEndString[0]))
                                .minute(parseInt(breakEndString[1]));

                            var unavailabilityPeriod = {
                                title: EALang.break,
                                start: moment(calendarDate.format('YYYY-MM-DD') + ' ' + breakPeriod.start).toDate(),
                                end: moment(calendarDate.format('YYYY-MM-DD') + ' ' + breakPeriod.end).toDate(),
                                allDay: false,
                                color: '#BEBEBE',
                                editable: false,
                                classNames: ['fc-unavailable', 'fc-break']
                            };

                            calendarEventSource.push(unavailabilityPeriod);
                        });

                        calendarDate.add(1, 'day');
                    }

                    break;
                }
            }

            return calendarEventSource;
        }

    function refreshCalendarAppointments() {
        var recordId = $("#select-filter-item").val();
        var filterType = $("#select-filter-item option:selected").attr("type");

        if ( ! recordId || ! filterType) {
            return; // Nothing selected (the flow happens after the filter is made).
        }

        fetchCalendarEvents(
            recordId,
            filterType,
            moment(calendar.view.activeStart),
            moment(calendar.view.activeEnd))
            .done(function (response) {
                var events = buildEventSource(
                    response,
                    recordId,
                    filterType,
                    moment(calendar.view.activeStart),
                    moment(calendar.view.activeEnd),
                    calendar.view.type);

                calendar.getEvents().forEach(function (event) {
                    event.remove();
                });

                calendar.batchRendering(function () {
                    events.forEach(function (event) {
                        calendar.addEvent(event);
                    });
                });
            });
    }


    exports.initialize = function () {
        // Dynamic date formats.
        var columnFormat = {};

        switch (GlobalVariables.dateFormat) {
            case 'DMY':
                columnFormat = 'ddd D/M';
                break;

            case 'DDMY':
                columnFormat = 'dddd, D.M.';
                break;

            case 'MDY':
            case 'YMD':
                columnFormat = 'ddd M/D';
                break;

            default:
                throw new Error('Invalid date format setting provided!', GlobalVariables.dateFormat);
        }

        // Time formats
        var timeFormat = '';
        var slotTimeFormat = '';

        switch (GlobalVariables.timeFormat) {
            case 'military':
                timeFormat = 'H:mm';
                slotTimeFormat = 'H(:mm)';
                break;
            case 'regular':
                timeFormat = 'h:mm a';
                slotTimeFormat = 'h(:mm) a';
                break;
            default:
                throw new Error('Invalid time format setting provided!', GlobalVariables.timeFormat);
        }

        var defaultView = window.innerWidth < 468 ? 'timeGridDay' : 'timeGridWeek';

        var firstWeekday = GlobalVariables.firstWeekday;
        var firstWeekdayNumber = GeneralFunctions.getWeekDayId(firstWeekday);

        var currentTime = moment().format("HH") + ":00:00";

        // Initialize page calendar (FullCalendar v6).
        var eaLocale = (GlobalVariables.language === 'german') ? 'de' : 'en';

        calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: defaultView,
            height: getCalendarHeight(),
            editable: true,
            firstDay: firstWeekdayNumber,
            fixedWeekCount: false,
            //hiddenDays: [ 0 ], // hide sundays
            // The working plan events depend on the current view, so the events
            // hook has to run on every view switch (the cached range reuse would
            // carry the wrong view's working plan over to other views).
            lazyFetching: false,
            slotDuration: '00:15:00',
            snapDuration: '00:15:00',
            slotLabelInterval: '01:00',
            scrollTime: currentTime,
            eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: GlobalVariables.timeFormat !== 'military'},
            showNonCurrentDates: false,
            slotLabelFormat: [{hour: 'numeric', minute: '2-digit', omitZeroMinute: true, hour12: GlobalVariables.timeFormat !== 'military'}, {hour: '2-digit', minute: '2-digit'}, {}],
            views: {
                timeGridDay: {
                    titleFormat: {day: 'numeric', month: 'long', year: 'numeric'},
                    dayHeaderFormat: {weekday: 'long'}
                },
                timeGridWeek: {
                    titleFormat: {day: 'numeric', month: 'long', year: 'numeric'},
                    dayHeaderFormat: function (info) {
                        return moment(info.date).format(columnFormat);
                    }
                },
                dayGridMonth: {
                    dayHeaderFormat: {weekday: 'long'}
                }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridDay,timeGridWeek,dayGridMonth'
            },

            // Selectable
            selectable: true,
            select: function (info) {
                if (info.allDay) {
                    return;
                }

                var start = info.start;
                var end = info.end;

                $('#insert-appointment').trigger('click');

                // Preselect service & provider.
                var service;

                if ($('#select-filter-item option:selected').attr('type') === FILTER_TYPE_SERVICE) {
                    service = GlobalVariables.availableServices.find(function (service) {
                        return Number(service.id) === Number($('#select-filter-item').val());
                    });
                    $('#select-service').val(service.id).trigger('change');

                } else {
                    var provider = GlobalVariables.availableProviders.find(function (provider) {
                        return Number(provider.id) === Number($('#select-filter-item').val());
                    });

                    service = GlobalVariables.availableServices.find(function (service) {
                        return provider.services.indexOf(service.id) !== -1
                    });

                    if (service) {
                        $('#select-service').val(service.id);
                    }

                    if (!$('#select-service').val()) {
                        $('#select-service option:first').prop('selected', true);
                    }

                    $('#select-service').trigger('change');

                    if (provider) {
                        $('#select-provider').val(provider.id);
                    }

                    if (!$('#select-provider').val()) {
                        $('#select-provider option:first').prop('selected', true);
                    }

                    $('#select-provider').trigger('change');
                }

                // Preselect time
                $('#start-datetime').datepicker('setDate', moment(start).toDate());
                $('#end-datetime').datepicker('setDate', moment(end).toDate());

                info.jsEvent.preventDefault();
            },

            buttonText: {
                today: EALang.today,
                day: EALang.day,
                week: EALang.week,
                month: EALang.month
            },

            allDayText: EALang.all_day,
            locale: eaLocale,
            events: function (fetchInfo, successCallback, failureCallback) {
                var recordId = $("#select-filter-item").val();
                var filterType = $("#select-filter-item option:selected").attr("type");

                if ( ! recordId || ! filterType)
                {
                    // The initial render happens before the filter control is populated.
                    successCallback([]);

                    return;
                }

                fetchCalendarEvents(
                    recordId,
                    filterType,
                    moment(fetchInfo.start),
                    moment(fetchInfo.end))
                    .done(function (response) {
                        successCallback(buildEventSource(
                            response,
                            recordId,
                            filterType,
                            moment(fetchInfo.start),
                            moment(fetchInfo.end),
                            calendar.view.type));
                    }).fail(failureCallback);
            },

            windowResize: calendarWindowResize,
            datesSet: calendarDatesSet,
            dateClick: calendarDateClick,
            eventClick: calendarEventClick,
            eventResize: calendarEventResize,
            eventDrop: calendarEventDrop
        });

        calendar.render();

        // Trigger once to set the proper footer position after calendar initialization.
        calendarWindowResize();

        // Fill the select list boxes of the page.
        if (GlobalVariables.availableProviders.length > 0) {
            $('<optgroup/>', {
                'label': EALang.providers,
                'type': 'providers-group',
                'html': GlobalVariables.availableProviders.map(function (availableProvider) {
                    return $('<option/>', {
                        'value': availableProvider.id,
                        'type': FILTER_TYPE_PROVIDER,
                        'text': availableProvider.first_name + ' ' + availableProvider.last_name
                    })
                })
            })
                .appendTo('#select-filter-item');
        }

        if (GlobalVariables.availableServices.length > 0) {
            $('<optgroup/>', {
                'label': EALang.services,
                'type': 'services-group',
                'html': GlobalVariables.availableServices.map(function (availableService) {
                    return $('<option/>', {
                        'value': availableService.id,
                        'type': FILTER_TYPE_SERVICE,
                        'text': availableService.name
                    })
                })
            })
                .appendTo('#select-filter-item');
        }

        // Check permissions.
        if (GlobalVariables.user.role_slug === Backend.DB_SLUG_PROVIDER) {
            $('#select-filter-item optgroup:eq(0)')
                .find('option[value="' + GlobalVariables.user.id + '"]')
                .prop('selected', true);
            $('#select-filter-item').prop('disabled', true);
        }

        if (GlobalVariables.user.role_slug === Backend.DB_SLUG_SECRETARY) {
            // Remove the providers that are not connected to the secretary.
            $('#select-filter-item optgroup:eq(1)').remove();

            $('#select-filter-item option[type="provider"]').each(function (index, option) {
                var provider = GlobalVariables.secretaryProviders.find(function (secretaryProviderId) {
                    return Number($(option).val()) === Number(secretaryProviderId);
                });

                if (!provider) {
                    $(option).remove();
                }
            });

            if (!$('#select-filter-item option[type="provider"]').length) {
                $('#select-filter-item optgroup[type="providers-group"]').remove();
            }
        }

        // Bind the default event handlers.
        bindEventHandlers();

        $('#select-filter-item').trigger('change');

        // Display the edit dialog if an appointment hash is provided.
        if (GlobalVariables.editAppointment) {
            var $dialog = $('#manage-appointment');
            var appointment = GlobalVariables.editAppointment;
            BackendCalendarAppointmentsModal.resetAppointmentDialog();

            $dialog.find('.modal-header h3').text(EALang.edit_appointment_title);
            $dialog.find('#appointment-id').val(appointment.id);
            $dialog.find('#select-service').val(appointment.id_services).trigger('change');
            $dialog.find('#select-provider').val(appointment.id_users_provider);

            // Set the start and end datetime of the appointment.
            var startDatetime = Date.parseExact(appointment.start_datetime, 'yyyy-MM-dd HH:mm:ss');
            $dialog.find('#start-datetime').datetimepicker('setDate', startDatetime);

            var endDatetime = Date.parseExact(appointment.end_datetime, 'yyyy-MM-dd HH:mm:ss');
            $dialog.find('#end-datetime').datetimepicker('setDate', endDatetime);

            var customer = appointment.customer;
            $dialog.find('#customer-id').val(appointment.id_users_customer);
            $dialog.find('#first-name').val(customer.first_name);
            $dialog.find('#last-name').val(customer.last_name);
            $dialog.find('#email').val(customer.email);
            $dialog.find('#phone-number').val(customer.phone_number);
            $dialog.find('#address').val(customer.address);
            $dialog.find('#city').val(customer.city);
            $dialog.find('#zip-code').val(customer.zip_code);
            GeneralFunctions.applyAppointmentLocation($dialog, appointment.location);
            $dialog.find('#appointment-notes').val(appointment.notes);
            $dialog.find('#customer-notes').val(customer.notes);

            $dialog.modal('show');

            calendar.gotoDate(moment(appointment.start_datetime).toDate());
        }

        if (!$('#select-filter-item option').length) {
            $('#calendar-actions button').prop('disabled', true);
        }

        // Fine tune the footer's position only for this page.
        if (window.innerHeight < 700) {
            $('#footer').css('position', 'static');
        }

        // Automatically refresh the calendar page every 5 Minutes (without loading animation).
        // Popovers are closed since they get not reposisioned correctly.
        // TODO: Try to fix the repositioning
        var $selectFilterItem = $('#select-filter-item');

        setInterval(function () {
            $('.popover').popover('dispose');
            calendar.refetchEvents();
        }, 300000);
    };

})(window.BackendCalendarDefaultView);
