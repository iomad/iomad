// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module is the highest level module for the calendar. It is
 * responsible for initialising all of the components required for
 * the calendar to run. It also coordinates the interaction between
 * components by listening for and responding to different events
 * triggered within the calendar UI.
 *
 * @module     core_calendar/calendar
 * @package    core_calendar
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
            'jquery',
            'core/ajax',
            'core/str',
            'core/templates',
            'core/notification',
            'core/custom_interaction_events',
            'core/modal_events',
            'core/modal_factory',
            'core_calendar/modal_event_form',
            'core_calendar/summary_modal',
            'core_calendar/repository',
            'core_calendar/events',
            'core_calendar/view_manager',
            'core_calendar/crud',
            'core_calendar/selectors',
        ],
        function(
            $,
            Ajax,
            Str,
            Templates,
            Notification,
            CustomEvents,
            ModalEvents,
            ModalFactory,
            ModalEventForm,
            SummaryModal,
            CalendarRepository,
            CalendarEvents,
            CalendarViewManager,
            CalendarCrud,
            CalendarSelectors
        ) {

    var SELECTORS = {
        ROOT: "[data-region='calendar']",
        DAY: "[data-region='day']",
        NEW_EVENT_BUTTON: "[data-action='new-event-button']",
        DAY_CONTENT: "[data-region='day-content']",
        LOADING_ICON: '.loading-icon',
        VIEW_DAY_LINK: "[data-action='view-day-link']",
        CALENDAR_MONTH_WRAPPER: ".calendarwrapper",
        TODAY: '.today',
    };

    /**
     * Handler for the drag and drop move event. Provides a loading indicator
     * while the request is sent to the server to update the event start date.
     *
     * Triggers a eventMoved calendar javascript event if the event was successfully
     * updated.
     *
     * @param {event} e The calendar move event
     * @param {int} eventId The event id being moved
     * @param {object|null} originElement The jQuery element for where the event is moving from
     * @param {object} destinationElement The jQuery element for where the event is moving to
     */
    var handleMoveEvent = function(e, eventId, originElement, destinationElement) {
        var originTimestamp = null;
        var destinationTimestamp = destinationElement.attr('data-day-timestamp');

        if (originElement) {
            originTimestamp = originElement.attr('data-day-timestamp');
        }

        // If the event has actually changed day.
        if (!originElement || originTimestamp != destinationTimestamp) {
            Templates.render('core/loading', {})
                .then(function(html, js) {
                    // First we show some loading icons in each of the days being affected.
                    destinationElement.find(SELECTORS.DAY_CONTENT).addClass('hidden');
                    Templates.appendNodeContents(destinationElement, html, js);

                    if (originElement) {
                        originElement.find(SELECTORS.DAY_CONTENT).addClass('hidden');
                        Templates.appendNodeContents(originElement, html, js);
                    }
                    return;
                })
                .then(function() {
                    // Send a request to the server to make the change.
                    return CalendarRepository.updateEventStartDay(eventId, destinationTimestamp);
                })
                .then(function() {
                    // If the update was successful then broadcast an event letting the calendar
                    // know that an event has been moved.
                    $('body').trigger(CalendarEvents.eventMoved, [eventId, originElement, destinationElement]);
                    return;
                })
                .always(function() {
                    // Always remove the loading icons regardless of whether the update
                    // request was successful or not.
                    var destinationLoadingElement = destinationElement.find(SELECTORS.LOADING_ICON);
                    destinationElement.find(SELECTORS.DAY_CONTENT).removeClass('hidden');
                    Templates.replaceNode(destinationLoadingElement, '', '');

                    if (originElement) {
                        var originLoadingElement = originElement.find(SELECTORS.LOADING_ICON);
                        originElement.find(SELECTORS.DAY_CONTENT).removeClass('hidden');
                        Templates.replaceNode(originLoadingElement, '', '');
                    }
                    return;
                })
                .fail(Notification.exception);
        }
    };

    /**
     * Listen to and handle any calendar events fired by the calendar UI.
     *
     * @method registerCalendarEventListeners
     * @param {object} root The calendar root element
     * @param {object} eventFormModalPromise A promise reolved with the event form modal
     */
    var registerCalendarEventListeners = function(root, eventFormModalPromise) {
        var body = $('body');

        body.on(CalendarEvents.created, function() {
            CalendarViewManager.reloadCurrentMonth(root);
        });
        body.on(CalendarEvents.deleted, function() {
            CalendarViewManager.reloadCurrentMonth(root);
        });
        body.on(CalendarEvents.updated, function() {
            CalendarViewManager.reloadCurrentMonth(root);
        });
        body.on(CalendarEvents.editActionEvent, function(e, url) {
            // Action events needs to be edit directly on the course module.
            window.location.assign(url);
        });
        // Handle the event fired by the drag and drop code.
        body.on(CalendarEvents.moveEvent, handleMoveEvent);
        // When an event is successfully moved we should updated the UI.
        body.on(CalendarEvents.eventMoved, function() {
            CalendarViewManager.reloadCurrentMonth(root);
        });

        CalendarCrud.registerEditListeners(root, eventFormModalPromise);
    };

    /**
     * Register event listeners for the module.
     *
     * @param {object} root The calendar root element
     */
    var registerEventListeners = function(root) {
        root.on('change', CalendarSelectors.elements.courseSelector, function() {
            var selectElement = $(this);
            var courseId = selectElement.val();
            CalendarViewManager.reloadCurrentMonth(root, courseId, null)
                .then(function() {
                    // We need to get the selector again because the content has changed.
                    return root.find(CalendarSelectors.elements.courseSelector).val(courseId);
                })
                .fail(Notification.exception);
        });

        var eventFormPromise = CalendarCrud.registerEventFormModal(root),
            contextId = $(SELECTORS.CALENDAR_MONTH_WRAPPER).data('context-id');
        registerCalendarEventListeners(root, eventFormPromise);

        if (contextId) {
            // Bind click events to calendar days.
            root.on('click', SELECTORS.DAY, function (e) {

                var target = $(e.target);

                if (!target.is(SELECTORS.VIEW_DAY_LINK)) {
                    var startTime = $(this).attr('data-new-event-timestamp');
                    eventFormPromise.then(function (modal) {
                        var wrapper = target.closest(CalendarSelectors.wrapper);
                        modal.setCourseId(wrapper.data('courseid'));

                        var categoryId = wrapper.data('categoryid');
                        if (typeof categoryId !== 'undefined') {
                            modal.setCategoryId(categoryId);
                        }

                        modal.setContextId(wrapper.data('contextId'));
                        modal.setStartTime(startTime);
                        modal.show();
                        return;
                    })
                    .fail(Notification.exception);

                    e.preventDefault();
                }
            });
        }
    };

    return {
        init: function(root) {
            root = $(root);
            CalendarViewManager.init(root);
            registerEventListeners(root);
        }
    };
});
