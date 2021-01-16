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
 * AMD module for purposes actions.
 *
 * @module     tool_dataprivacy/purposesactions
 * @package    tool_dataprivacy
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str',
    'core/modal_factory',
    'core/modal_events'],
function($, Ajax, Notification, Str, ModalFactory, ModalEvents) {

    /**
     * List of action selectors.
     *
     * @type {{DELETE: string}}
     */
    var ACTIONS = {
        DELETE: '[data-action="deletepurpose"]',
    };

    /**
     * PurposesActions class.
     */
    var PurposesActions = function() {
        this.registerEvents();
    };

    /**
     * Register event listeners.
     */
    PurposesActions.prototype.registerEvents = function() {
        $(ACTIONS.DELETE).click(function(e) {
            e.preventDefault();

            var id = $(this).data('id');
            var purposename = $(this).data('name');
            var stringkeys = [
                {
                    key: 'deletepurpose',
                    component: 'tool_dataprivacy',
                    param: purposename
                },
                {
                    key: 'deletepurposetext',
                    component: 'tool_dataprivacy',
                    param: purposename
                }
            ];

            Str.get_strings(stringkeys).then(function(langStrings) {
                var title = langStrings[0];
                var confirmMessage = langStrings[1];
                return ModalFactory.create({
                    title: title,
                    body: confirmMessage,
                    type: ModalFactory.types.SAVE_CANCEL
                }).then(function(modal) {
                    modal.setSaveButtonText(title);

                    // Handle save event.
                    modal.getRoot().on(ModalEvents.save, function() {

                        var request = {
                            methodname: 'tool_dataprivacy_delete_purpose',
                            args: {'id': id}
                        };

                        Ajax.call([request])[0].done(function(data) {
                            if (data.result) {
                                $('tr[data-purposeid="' + id + '"]').remove();
                            } else {
                                Notification.addNotification({
                                    message: data.warnings[0].message,
                                    type: 'error'
                                });
                            }
                        }).fail(Notification.exception);
                    });

                    // Handle hidden event.
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        // Destroy when hidden.
                        modal.destroy();
                    });

                    return modal;
                });
            }).done(function(modal) {
                modal.show();

            }).fail(Notification.exception);
        });
    };

    return /** @alias module:tool_dataprivacy/purposesactions */ {
        // Public variables and functions.

        /**
         * Initialise the module.
         *
         * @method init
         * @return {PurposesActions}
         */
        'init': function() {
            return new PurposesActions();
        }
    };
});
