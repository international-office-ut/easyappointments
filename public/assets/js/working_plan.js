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
     * Class WorkingPlan
     *
     * Contains the working plan functionality. The working plan DOM elements must be same
     * in every page this class is used.
     *
     * @class WorkingPlan
     */
    var WorkingPlan = function (config) {
        config = config || {};

        /**
         * Whether the day rows include the appointment type override select. Only the provider
         * working plan needs it; the system settings plan has no provider context.
         *
         * @type {Boolean}
         */
        this.weekdayModes = Boolean(config.weekdayModes);

        /**
         * Context node every selector runs against. Pages may host more than one working plan
         * markup (e.g. the settings page renders the company plan and, for providers, the own
         * plan), so a captured root keeps setup, save and break management from bleeding into
         * the other set.
         *
         * @type {jQuery}
         */
        this.$root = config.root ? $(config.root) : $(document.body);

        /**
         * This flag is used when trying to cancel row editing. It is
         * true only whenever the user presses the cancel button.
         *
         * @type {Boolean}
         */
        this.enableCancel = false;

        /**
         * This flag determines whether the jeditables are allowed to submit. It is
         * true only whenever the user presses the save button.
         *
         * @type {Boolean}
         */
        this.enableSubmit = false;
    };

    /**
     * Setup the dom elements of a given working plan.
     *
     * @param {Object} workingPlan Contains the working hours and breaks for each day of the week.
     */
    WorkingPlan.prototype.setup = function (workingPlan) {
        var self = this;
        var weekDayId = GeneralFunctions.getWeekDayId(GlobalVariables.firstWeekday);
        var workingPlanSorted = GeneralFunctions.sortWeekDictionary(workingPlan, weekDayId);

        this.$root.find('.working-plan tbody').empty();
        this.$root.find('.breaks tbody').empty();

        // Build working plan day list starting with the first weekday as set in the General settings
        var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';

        $.each(workingPlanSorted, function (index, workingDay) {
            var day = this.convertValueToDay(index);

            var dayDisplayName = GeneralFunctions.upperCaseFirstLetter(day);

            var $dayRow = $('<tr/>', {
                'html': [
                    $('<td/>', {
                        'html': [
                            $('<div/>', {
                                'class': 'checkbox form-check',
                                'html': [
                                    $('<input/>', {
                                        'class': 'form-check-input',
                                        'type': 'checkbox',
                                        'id': index
                                    }),
                                    $('<label/>', {
                                        'class': 'form-check-label',
                                        'text': dayDisplayName,
                                        'for': index
                                    }),

                                ]
                            })
                        ]
                    }),
                    $('<td/>', {
                        'html': [
                            $('<input/>', {
                                'id': index + '-start',
                                'class': 'work-start form-control form-control-sm'
                            })
                        ]
                    }),
                    $('<td/>', {
                        'html': [
                            $('<input/>', {
                                'id': index + '-end',
                                'class': 'work-start form-control form-control-sm'
                            })
                        ]
                    })
                ]
            });

            if (this.weekdayModes) {
                $dayRow.append($('<td/>', {
                    'html': [
                        $('<select/>', {
                            'id': index + '-mode',
                            'class': 'weekday-mode form-control form-control-sm',
                            'data-weekday': GeneralFunctions.getWeekDayId(dayDisplayName),
                            'html': [
                                new Option(EALang.mode_provider_default || 'Provider Default', ''),
                                new Option(EALang.mode_online || 'Online', 'ONLINE'),
                                new Option(EALang.mode_on_premise || 'On Site', 'ON_PREMISE'),
                                new Option(EALang.mode_both || 'BOTH - Online or On Site', 'BOTH')
                            ]
                        })
                    ]
                }));
            }

            $dayRow
                .appendTo(this.$root.find('.working-plan tbody'));

            if (workingDay) {
                $('#' + index, this.$root).prop('checked', true);
                $('#' + index + '-start', this.$root).val(Date.parse(workingDay.start).toString(timeFormat).toLowerCase());
                $('#' + index + '-end', this.$root).val(Date.parse(workingDay.end).toString(timeFormat).toLowerCase());
                $('#' + index + '-mode', this.$root).prop('disabled', false);

                // Sort day's breaks according to the starting hour
                workingDay.breaks.sort(function (break1, break2) {
                    // We can do a direct string comparison since we have time based on 24 hours clock.
                    return (break1.start).localeCompare(break2.start);
                });

                workingDay.breaks.forEach(function (workingDayBreak) {
                    $('<tr/>', {                        'html': [
                            $('<td/>', {
                                'class': 'break-day editable',
                                'text': dayDisplayName
                            }),
                            $('<td/>', {
                                'class': 'break-start editable',
                                'text': Date.parse(workingDayBreak.start).toString(timeFormat).toLowerCase()
                            }),
                            $('<td/>', {
                                'class': 'break-end editable',
                                'text': Date.parse(workingDayBreak.end).toString(timeFormat).toLowerCase()
                            }),
                            $('<td/>', {
                                'html': [
                                    $('<button/>', {
                                        'type': 'button',
                                        'class': 'btn btn-outline-secondary btn-sm edit-break',
                                        'title': EALang.edit,
                                        'html': [
                                            $('<span/>', {
                                                'class': 'fas fa-edit'
                                            })
                                        ]
                                    }),
                                    $('<button/>', {
                                        'type': 'button',
                                        'class': 'btn btn-outline-secondary btn-sm delete-break',
                                        'title': EALang.delete,
                                        'html': [
                                            $('<span/>', {
                                                'class': 'fas fa-trash-alt'
                                            })
                                        ]
                                    }),
                                    $('<button/>', {
                                        'type': 'button',
                                        'class': 'btn btn-outline-secondary btn-sm save-break d-none',
                                        'title': EALang.save,
                                        'html': [
                                            $('<span/>', {
                                                'class': 'fas fa-check-circle'
                                            })
                                        ]
                                    }),
                                    $('<button/>', {
                                        'type': 'button',
                                        'class': 'btn btn-outline-secondary btn-sm cancel-break d-none',
                                        'title': EALang.cancel,
                                        'html': [
                                            $('<span/>', {
                                                'class': 'fas fa-ban'
                                            })
                                        ]
                                    })
                                ]
                            })
                        ]
                    })
                        .appendTo(self.$root.find('.breaks tbody'));
                });
            } else {
                $('#' + index, self.$root).prop('checked', false);
                $('#' + index + '-start', self.$root).prop('disabled', true);
                $('#' + index + '-end', self.$root).prop('disabled', true);
                $('#' + index + '-mode', self.$root).prop('disabled', true);
            }
        }.bind(this));

        // Make break cells editable.
        this.editableDayCell(this.$root.find('.breaks .break-day'));
        this.editableTimeCell(this.$root.find('.breaks').find('.break-start, .break-end'));
    };

    /**
     * Setup the dom elements of a given working plan exception.
     *
     * @param {Object} workingPlanExceptions Contains the working plan exception.
     */
    WorkingPlan.prototype.setupWorkingPlanExceptions = function (workingPlanExceptions) {
        for (var date in workingPlanExceptions) {
            var workingPlanException = workingPlanExceptions[date];

            this
                .renderWorkingPlanExceptionRow(date, workingPlanException)
                .appendTo(this.$root.find('.working-plan-exceptions tbody'));
        }
    };

    /**
     * Enable editable break day.
     *
     * This method makes editable the break day cells.
     *
    /**
     * Active working days of the current plan context.
     *
     * Breaks may only cover days with working hours, so the day select of break rows and the
     * save validation both derive from this map. The list follows the "First day of week"
     * setting always: it walks a rotated weekday id list instead of the table markup, so the
     * browser state (stale table rows, unrelated panes) can never reorder the dropdown.
     *
     * @return {Object} Returns the week day display names of the days with working hours.
     */
    WorkingPlan.prototype.getActiveDays = function () {
        var instance = this;
        var activeDays = {};

        this.getWeekDayIds().forEach(function (dayId) {
            var $checkbox = $('#' + dayId, instance.$root);

            if ($checkbox.length && $checkbox.prop('checked')) {
                var dayName = EALang[dayId];

                if (dayName) {
                    activeDays[dayName] = dayName;
                }
            }
        });

        return activeDays;
    };

    /**
     * Weekday ids of the plan, ordered by the "First day of week" setting.
     *
     * @return {Array} Returns the weekday ids ("monday" first when monday is the start day).
     */
    WorkingPlan.prototype.getWeekDayIds = function () {
        var weekDayIds = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var startId = GeneralFunctions.getWeekDayId(GlobalVariables.firstWeekday);

        if (typeof startId !== 'number' || startId < 0 || startId > 6) {
            return weekDayIds;
        }

        return weekDayIds.slice(startId).concat(weekDayIds.slice(0, startId));
    };

    /**
     * Days that carry break rows while their weekday has no working hours.
     *
     * Such rows are never dropped silently: the day select cannot create them anymore, but
     * they can still emerge when a working day gets unchecked afterwards or through manual
     * requests. The save flows block on this list and ask the user to resolve it.
     *
     * @return {Array} Returns the week day display names of the diverging breaks.
     */
    WorkingPlan.prototype.getOrphanBreakDays = function () {
        var instance = this;
        var activeDays = this.getActiveDays();
        var orphans = [];

        this.$root.find('.breaks tbody tr').each(function (index, row) {
            var dayName = $(row).find('.break-day').text();

            // An open row editor whose select is being built keeps the concatenated option
            // labels in the cell: only real day names count for the validation.
            if (dayName && !instance.convertDayToValue(dayName)) {
                return;
            }

            if (dayName && activeDays[dayName] === undefined && orphans.indexOf(dayName) === -1) {
                orphans.push(dayName);
            }
        });

        return orphans;
    };

    /**
     * Make break cells editable.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    WorkingPlan.prototype.editableDayCell = function ($selector) {
        var instance = this;

        $selector.editable(function (value, settings) {
            return value;
        }, {
            type: 'select',
            // Re-evaluated on every edit: the list follows the checked working
            // days, so breaks can never be attached to an inactive day.
            data: function () {
                return instance.getActiveDays();
            },
            event: 'edit',
            height: '30px',
            submit: '<button type="button" class="d-none submit-editable">Submit</button>',
            cancel: '<button type="button" class="d-none cancel-editable">Cancel</button>',
            onblur: 'ignore',
            onreset: function (settings, td) {
                if (!this.enableCancel) {
                    return false; // disable ESC button
                }
            }.bind(this),
            onsubmit: function (settings, td) {
                if (!this.enableSubmit) {
                    return false; // disable Enter button
                }
            }.bind(this)
        });
    };

    /**
     * Enable editable break time.
     *
     * This method makes editable the break time cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    WorkingPlan.prototype.editableTimeCell = function ($selector) {
        $selector.editable(function (value, settings) {
            // Do not return the value because the user needs to press the "Save" button.
            return value;
        }, {
            event: 'edit',
            height: '30px',
            submit: $('<button/>', {
                'type': 'button',
                'class': 'd-none submit-editable',
                'text': EALang.save
            })
                .get(0)
                .outerHTML,
            cancel: $('<button/>', {
                'type': 'button',
                'class': 'd-none cancel-editable',
                'text': EALang.cancel
            })
                .get(0)
                .outerHTML,
            onblur: 'ignore',
            onreset: function (settings, td) {
                if (!this.enableCancel) {
                    return false; // disable ESC button
                }
            }.bind(this),
            onsubmit: function (settings, td) {
                if (!this.enableSubmit) {
                    return false; // disable Enter button
                }
            }.bind(this)
        });
    };

    /**
     * Enable editable break time.
     *
     * This method makes editable the break time cells.
     *
     * @param {String} date In "Y-m-d" format.
     * @param {Object} workingPlanException Contains exception information.
     */
    WorkingPlan.prototype.renderWorkingPlanExceptionRow = function (date, workingPlanException) {
        var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';

        return $('<tr/>', {
            'data': {
                'date': date,
                'workingPlanException': workingPlanException
            },
            'html': [
                $('<td/>', {
                    'class': 'working-plan-exception-date',
                    'text': GeneralFunctions.formatDate(date, GlobalVariables.dateFormat, false)
                }),
                $('<td/>', {
                    'class': 'working-plan-exception--start',
                    'text': Date.parse(workingPlanException.start).toString(timeFormat).toLowerCase()
                }),
                $('<td/>', {
                    'class': 'working-plan-exception--end',
                    'text': Date.parse(workingPlanException.end).toString(timeFormat).toLowerCase()
                }),
                $('<td/>', {
                    'class': 'working-plan-exception--mode',
                    'text': workingPlanException.mode
                        ? EALang[{
                            ONLINE: 'mode_online',
                            ON_PREMISE: 'mode_on_premise',
                            BOTH: 'mode_both'
                        }[workingPlanException.mode]] || workingPlanException.mode
                        : EALang.mode_follow_defaults
                }),
                $('<td/>', {
                    'html': [
                        $('<button/>', {
                            'type': 'button',
                            'class': 'btn btn-outline-secondary btn-sm edit-working-plan-exception',
                            'title': EALang.edit,
                            'html': [
                                $('<span/>', {
                                    'class': 'fas fa-edit'
                                })
                            ]
                        }),
                        $('<button/>', {
                            'type': 'button',
                            'class': 'btn btn-outline-secondary btn-sm delete-working-plan-exception',
                            'title': EALang.delete,
                            'html': [
                                $('<span/>', {
                                    'class': 'fas fa-trash-alt'
                                })
                            ]
                        }),
                    ]
                })
            ]
        });
    };

    /**
     * Read the weekday appointment mode overrides of the editor.
     *
     * Days without working hours cannot have an appointment mode override, so their select
     * fields are disabled.
     *
     * @return {Object|null} Returns the weekday modes map or NULL when no override is set.
     */
    WorkingPlan.prototype.getWeekdayModes = function () {
        var weekdayModes = {};

        this.$root.find('.working-plan .weekday-mode').each(function (index, select) {
            var weekday = $(select).attr('data-weekday');
            var mode = $(select).val();
            var dayId = $(select).attr('id') ? $(select).attr('id').replace('-mode', '') : null;

            if (weekday && mode && (!dayId || $('#' + dayId, this.$root).prop('checked'))) {
                weekdayModes[weekday] = mode;
            }
        }.bind(this));

        return Object.keys(weekdayModes).length ? weekdayModes : null;
    };

    /**
     * Apply weekday appointment mode overrides to the editor.
     *
     * @param {String|null} weekdayModes JSON encoded weekday modes map of the record settings.
     */
    WorkingPlan.prototype.displayWeekdayModes = function (weekdayModes) {
        this.$root.find('.working-plan .weekday-mode').val('');

        try {
            var modes = weekdayModes ? JSON.parse(weekdayModes) : {};
        } catch (error) {
            return;
        }

        if (typeof modes !== 'object' || modes === null) {
            return;
        }

        Object.keys(modes).forEach(function (weekday) {
            this.$root
                .find('.working-plan .weekday-mode[data-weekday="' + weekday + '"]')
                .val(modes[weekday]);
        }.bind(this));
    };

    /**
     * Sync the weekday-override select states with the working days of the plan.
     *
     * Days without working hours cannot have an appointment mode override, so their select
     * fields are disabled.
     */
    WorkingPlan.prototype.syncWeekdayModeStates = function () {
        this.$root.find('.working-plan .form-check-input').each(function (index, checkbox) {
            var dayId = $(checkbox).attr('id');

            $('#' + dayId + '-mode', this.$root).prop('disabled', !$(checkbox).prop('checked'));
        }.bind(this));
    };

    /**
     * Binds the event handlers for the working plan dom elements.
     */
    WorkingPlan.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Day Checkbox "Click"
         *
         * Enable or disable the time selection for each day.
         */
        this.$root.on('click', '.working-plan tbody input:checkbox', function () {
            var id = $(this).attr('id');

            if ($(this).prop('checked') === true) {
                $('#' + id + '-start', instance.$root).prop('disabled', false).val('09:00');
                $('#' + id + '-end', instance.$root).prop('disabled', false).val('18:00');
            } else {
                $('#' + id + '-start', instance.$root).prop('disabled', true).val('');
                $('#' + id + '-end', instance.$root).prop('disabled', true).val('');
            }
        });

        /**
         * Event: Add Break Button "Click"
         *
         * A new row is added on the table and the user can enter the new break
         * data. After that he can either press the save or cancel button.
         */
        this.$root.on('click', '.add-break', function () {
            var activeDays = instance.getActiveDays();

            if ($.isEmptyObject(activeDays)) {
                Backend.displayNotification(EALang.no_active_working_days);
                return;
            }

            // The new break starts on the first working day of the week as configured by the
            // "First day of week" setting, instead of the hardcoded Sunday.
            var defaultDay = Object.keys(activeDays)[0];

            var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';

            var $newBreak = $('<tr/>', {
                'html': [
                    $('<td/>', {
                        'class': 'break-day editable',
                        'text': defaultDay
                    }),
                    $('<td/>', {
                        'class': 'break-start editable',
                        'text': Date.parse('12:00:00').toString(timeFormat).toLowerCase()
                    }),
                    $('<td/>', {
                        'class': 'break-end editable',
                        'text': Date.parse('14:00:00').toString(timeFormat).toLowerCase()
                    }),
                    $('<td/>', {
                        'html': [
                            $('<button/>', {
                                'type': 'button',
                                'class': 'btn btn-outline-secondary btn-sm edit-break',
                                'title': EALang.edit,
                                'html': [
                                    $('<span/>', {
                                        'class': 'fas fa-edit'
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'type': 'button',
                                'class': 'btn btn-outline-secondary btn-sm delete-break',
                                'title': EALang.delete,
                                'html': [
                                    $('<span/>', {
                                        'class': 'fas fa-trash-alt'
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'type': 'button',
                                'class': 'btn btn-outline-secondary btn-sm save-break d-none',
                                'title': EALang.save,
                                'html': [
                                    $('<span/>', {
                                        'class': 'fas fa-check-circle'
                                    })
                                ]
                            }),
                            $('<button/>', {
                                'type': 'button',
                                'class': 'btn btn-outline-secondary btn-sm cancel-break d-none',
                                'title': EALang.cancel,
                                'html': [
                                    $('<span/>', {
                                        'class': 'fas fa-ban'
                                    })
                                ]
                            })
                        ]
                    })
                ]
            })
                .appendTo(instance.$root.find('.breaks tbody'));

            // Bind editable and event handlers.
            instance.editableDayCell($newBreak.find('.break-day'));
            instance.editableTimeCell($newBreak.find('.break-start, .break-end'));
            $newBreak.find('.edit-break').trigger('click');
        });

        /**
         * Event: Edit Break Button "Click"
         *
         * Enables the row editing for the "Breaks" table rows.
         */
        $(document).on('click', '.edit-break', function () {
            // Reset previous editable table cells.
            var $previousEdits = $(this).closest('table').find('.editable');

            $previousEdits.each(function (index, editable) {
                if (editable.reset) {
                    editable.reset();
                }
            });

            // Make all cells in current row editable.
            $(this).parent().parent().children().trigger('edit');
            $(this).parent().parent().find('.break-start input, .break-end input').timepicker({
                timeFormat: GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm',
                currentText: EALang.now,
                closeText: EALang.close,
                timeOnlyTitle: EALang.select_time,
                timeText: EALang.time,
                hourText: EALang.hour,
                minuteText: EALang.minutes
            });
            $(this).parent().parent().find('.break-day select').focus();

            // Show save - cancel buttons.
            var $tr = $(this).closest('tr');
            $tr.find('.edit-break, .delete-break').addClass('d-none');
            $tr.find('.save-break, .cancel-break').removeClass('d-none');
            $tr.find('select,input:text').addClass('form-control form-control-sm')
        });

        /**
         * Event: Delete Break Button "Click"
         *
         * Removes the current line from the "Breaks" table.
         */
        $(document).on('click', '.delete-break', function () {
            $(this).parent().parent().remove();
        });

        /**
         * Event: Cancel Break Button "Click"
         *
         * Bring the ".breaks" table back to its initial state.
         *
         * @param {jQuery.Event} event
         */
        $(document).on('click', '.cancel-break', function (event) {
            var element = event.target;
            var $modifiedRow = $(element).closest('tr');
            this.enableCancel = true;
            $modifiedRow.find('.cancel-editable').trigger('click');
            this.enableCancel = false;

            $modifiedRow.find('.edit-break, .delete-break').removeClass('d-none');
            $modifiedRow.find('.save-break, .cancel-break').addClass('d-none');
        }.bind(this));

        /**
         * Event: Save Break Button "Click"
         *
         * Save the editable values and restore the table to its initial state.
         *
         * @param {jQuery.Event} e
         */
        $(document).on('click', '.save-break', function (event) {
            // Break's start time must always be prior to break's end.
            var element = event.target;
            var $modifiedRow = $(element).closest('tr');
            var start = Date.parse($modifiedRow.find('.break-start input').val());
            var end = Date.parse($modifiedRow.find('.break-end input').val());

            if (start > end) {
                $modifiedRow.find('.break-end input').val(start.addHours(1).toString(GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm').toLowerCase());
            }

            this.enableSubmit = true;
            $modifiedRow.find('.editable .submit-editable').trigger('click');
            this.enableSubmit = false;

            $modifiedRow.find('.save-break, .cancel-break').addClass('d-none');
            $modifiedRow.find('.edit-break, .delete-break').removeClass('d-none');
        }.bind(this));

        /**
         * Event: Add Working Plan Exception Button "Click"
         *
         * A new row is added on the table and the user can enter the new working plan exception.
         */
        this.$root.on('click', '.add-working-plan-exception', function () {
            WorkingPlanExceptionsModal
                .add()
                .done(function (date, workingPlanException) {
                    var $tr = null;

                    instance.$root.find('.working-plan-exceptions tbody tr').each(function (index, tr) {
                        if (date === $(tr).data('date')) {
                            $tr = $(tr);
                            return false;
                        }
                    });

                    var $newTr = instance.renderWorkingPlanExceptionRow(date, workingPlanException);

                    if ($tr) {
                        $tr.replaceWith($newTr);
                    } else {
                        $newTr.appendTo(instance.$root.find('.working-plan-exceptions tbody'));
                    }
                });
        });

        /**
         * Event: Edit working plan exception Button "Click"
         *
         * Enables the row editing for the "working plan exception" table rows.
         *
         * @param {jQuery.Event} event
         */
        $(document).on('click', '.edit-working-plan-exception', function (event) {
            var $tr = $(event.target).closest('tr');
            var date = $tr.data('date');
            var workingPlanException = $tr.data('workingPlanException');

            WorkingPlanExceptionsModal
                .edit(date, workingPlanException)
                .done(function (date, workingPlanException) {
                    $tr.replaceWith(
                        this.renderWorkingPlanExceptionRow(date, workingPlanException)
                    );
                }.bind(this));
        }.bind(this));

        /**
         * Event: Delete working plan exception Button "Click"
         *
         * Removes the current line from the "working plan exceptions" table.
         */
        $(document).on('click', '.delete-working-plan-exception', function () {
            $(this).closest('tr').remove();
        });
    };

    /**
     * Get the working plan settings.
     *
     * @return {Object} Returns the working plan settings object.
     */
    WorkingPlan.prototype.get = function () {
        var instance = this;
        var workingPlan = {};

        // Walk the "First day of week" rotated weekday list so the returned plan keeps one
        // stable ordering scheme, independent of the table markup.
        this.getWeekDayIds().forEach(function (id) {
            var $checkbox = $('#' + id, instance.$root);

            if ($checkbox.length === 0) {
                return;
            }

            if ($checkbox.prop('checked') === true) {
                workingPlan[id] = {
                    start: Date.parse($('#' + id + '-start', instance.$root).val()).toString('HH:mm'),
                    end: Date.parse($('#' + id + '-end', instance.$root).val()).toString('HH:mm'),
                    breaks: []
                };

                instance.$root.find('.breaks tr').each(function (index, tr) {
                    var day = instance.convertDayToValue($(tr).find('.break-day').text());

                    if (day === id) {
                        var start = $(tr).find('.break-start').text();
                        var end = $(tr).find('.break-end').text();

                        workingPlan[id].breaks.push({
                            start: Date.parse(start).toString('HH:mm'),
                            end: Date.parse(end).toString('HH:mm')
                        });
                    }
                });

                // Sort breaks increasingly by hour within day
                workingPlan[id].breaks.sort(function (break1, break2) {
                    // We can do a direct string comparison since we have time based on 24 hours clock.
                    return (break1.start).localeCompare(break2.start);
                });
            } else {
                workingPlan[id] = null;
            }
        });

        return workingPlan;
    };

    /**
     * Get the working plan exceptions settings.
     *
     * @return {Object} Returns the working plan exceptions settings object.
     */
    WorkingPlan.prototype.getWorkingPlanExceptions = function () {
        var workingPlanExceptions = {};

        this.$root.find('.working-plan-exceptions tbody tr').each(function (index, tr) {
            var $tr = $(tr);
            var date = $tr.data('date');
            workingPlanExceptions[date] = $tr.data('workingPlanException');
        });

        return workingPlanExceptions;
    };

    /**
     * Enables or disables the timepicker functionality from the working plan input text fields.
     *
     * @param {Boolean} [disabled] If true then the timepickers will be disabled.
     */
    WorkingPlan.prototype.timepickers = function (disabled) {
        disabled = disabled || false;

        if (disabled === false) {
            // Set timepickers where needed.
            this.$root.find('.working-plan input:text').timepicker({
                timeFormat: GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm',
                currentText: EALang.now,
                closeText: EALang.close,
                timeOnlyTitle: EALang.select_time,
                timeText: EALang.time,
                hourText: EALang.hour,
                minuteText: EALang.minutes,

                onSelect: function (datetime, inst) {
                    // Start time must be earlier than end time.
                    var start = Date.parse($(this).parent().parent().find('.work-start').val()),
                        end = Date.parse($(this).parent().parent().find('.work-end').val());

                    if (start > end) {
                        $(this).parent().parent().find('.work-end').val(start.addHours(1).toString(GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm').toLowerCase());
                    }
                }
            });
        } else {
            this.$root.find('.working-plan input').timepicker('destroy');
        }
    };

    /**
     * Reset the current plan back to the company's default working plan.
     */
    WorkingPlan.prototype.reset = function () {

    };

    /**
     * This is necessary for translated days.
     *
     * @param {String} value Day value could be like "monday", "tuesday" etc.
     */
    WorkingPlan.prototype.convertValueToDay = function (value) {
        switch (value) {
            case 'sunday':
                return EALang.sunday;
            case 'monday':
                return EALang.monday;
            case 'tuesday':
                return EALang.tuesday;
            case 'wednesday':
                return EALang.wednesday;
            case 'thursday':
                return EALang.thursday;
            case 'friday':
                return EALang.friday;
            case 'saturday':
                return EALang.saturday;
        }
    };

    /**
     * This is necessary for translated days.
     *
     * @param {String} day Day value could be like "Monday", "Tuesday" etc.
     */
    WorkingPlan.prototype.convertDayToValue = function (day) {
        switch (day) {
            case EALang.sunday:
                return 'sunday';
            case EALang.monday:
                return 'monday';
            case EALang.tuesday:
                return 'tuesday';
            case EALang.wednesday:
                return 'wednesday';
            case EALang.thursday:
                return 'thursday';
            case EALang.friday:
                return 'friday';
            case EALang.saturday:
                return 'saturday';
        }
    };

    window.WorkingPlan = WorkingPlan;

})();
