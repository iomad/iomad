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
 * Module to add/remove contact using ajax.
 *
 * @module     core_message/toggle_contact_button
 * @class      toggle_contact_button
 * @package    message
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/custom_interaction_events'],
        function($, Ajax, Templates, Notification, CustomEvents) {

    /**
     * Check the state of the element, if the current user is a contact or not.
     *
     * @method isContact
     * @param {object} element jQuery object for the button
     * @return {bool}
     */
    var isContact = function(element) {
        return element.attr('data-is-contact') == '1';
    };

    /**
     * Record that the user is a contact.
     *
     * @method setContact
     * @param {object} element jQuery object for the button
     */
    var setContact = function(element) {
        element.attr('data-is-contact', '1');
    };

    /**
     * Record that the user is not a contact.
     *
     * @method setNotContact
     * @param {object} element jQuery object for the button
     */
    var setNotContact = function(element) {
        element.attr('data-is-contact', '0');
    };

    /**
     * Get the id for the user being viewed.
     *
     * @method getUserId
     * @param {object} element jQuery object for the button
     * @return {int}
     */
    var getUserId = function(element) {
        return element.attr('data-userid');
    };

    /**
     * Check if this element is currently loading.
     *
     * @method isLoading
     * @param {object} element jQuery object for the button
     * @return {bool}
     */
    var isLoading = function(element) {
        return element.hasClass('loading') || element.attr('disabled');
    };

    /**
     * Sends an ajax request to the server and handles the element state
     * while the request is being performed.
     *
     * @method sendRequest
     * @param {object} element jQuery object for the button
     * @param {object} request Request hash to send
     * @return {object} jQuery promise
     */
    var sendRequest = function(element, request) {
        if (isLoading(element)) {
            return $.Deferred();
        }

        element.addClass('loading');
        element.attr('disabled', 'disabled');

        return Ajax.call([request])[0]
            .fail(Notification.exception)
            .always(function() {
                element.removeClass('loading');
                element.removeAttr('disabled');
            });
    };

    /**
     * Send a request to the server to add the current user as
     * a contact. The contents of the button are changed to the
     * remove contact button upon success.
     *
     * @method addContact
     * @param {object} element jQuery object for the button
     */
    var addContact = function(element) {
        if (isLoading(element)) {
            return;
        }

        var request = {
            methodname: 'core_message_create_contacts',
            args: {
                userids: [getUserId(element)],
            }
        };
        sendRequest(element, request).done(function() {
            setContact(element);
            Templates.render('message/remove_contact_button', {}).done(function(html, js) {
                Templates.replaceNodeContents(element, html, js);
            });
        });
    };

    /**
     * Send a request to the server to remove the current user as
     * a contact. The contents of the button are changed to the
     * add contact button upon success.
     *
     * @method removeContact
     * @param {object} element jQuery object for the button
     */
    var removeContact = function(element) {
        if (isLoading(element)) {
            return;
        }

        var request = {
            methodname: 'core_message_delete_contacts',
            args: {
                userids: [getUserId(element)],
            }
        };

        sendRequest(element, request).done(function() {
            setNotContact(element);
            Templates.render('message/add_contact_button', {}).done(function(html, js) {
                Templates.replaceNodeContents(element, html, js);
            });
        });
    };

    /**
     * Enhances the given element with a loading gif and event handles to make
     * ajax requests to add or remove a contact where appropriate.
     *
     * @method enhance
     * @param {object} element jQuery object for the button
     */
    var enhance = function(element) {
        element = $(element);

        if (!element.children('.loading-icon').length) {
            // Add the loading gif if it isn't already there.
            Templates.render('core/loading', {}).done(function(html, js) {
                element.append(html, js);
            });
        }

        CustomEvents.define(element, [CustomEvents.events.activate]);

        element.on(CustomEvents.events.activate, function(e, data) {
            if (isContact(element)) {
                removeContact(element);
            } else {
                addContact(element);
            }
            e.preventDefault();
            data.originalEvent.preventDefault();
        });
    };

    return /** @alias module:message/toggle_contact_button */ {
        enhance: enhance
    };
});
