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
 * Add policy consent modal to the page
 *
 * @module     tool_policy/acceptmodal
 * @class      AcceptOnBehalf
 * @package    tool_policy
 * @copyright  2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/notification', 'core/fragment',
        'core/ajax', 'core/yui'],
    function($, Str, ModalFactory, ModalEvents, Notification, Fragment, Ajax, Y) {

        "use strict";

        /**
         * Constructor
         *
         * @param {int} contextid
         *
         * Each call to init gets it's own instance of this class.
         */
        var AcceptOnBehalf = function(contextid) {
            this.contextid = contextid;
            this.init();
        };

        /**
         * @var {Modal} modal
         * @private
         */
        AcceptOnBehalf.prototype.modal = null;

        /**
         * @var {int} contextid
         * @private
         */
        AcceptOnBehalf.prototype.contextid = -1;

        /**
         * @var {Array} strings
         * @private
         */
        AcceptOnBehalf.prototype.stringKeys = [
            {
                key: 'consentdetails',
                component: 'tool_policy'
            },
            {
                key: 'iagreetothepolicy',
                component: 'tool_policy'
            },
            {
                key: 'selectusersforconsent',
                component: 'tool_policy'
            },
            {
                key: 'ok'
            },
            {
                key: 'revokedetails',
                component: 'tool_policy'
            },
            {
                key: 'irevokethepolicy',
                component: 'tool_policy'
            }
        ];

        /**
         * Initialise the class.
         *
         * @private
         */
        AcceptOnBehalf.prototype.init = function() {
            // Initialise for links accepting policies for individual users.
            var triggers = $('a[data-action=acceptmodal]');
            triggers.on('click', function(e) {
                e.preventDefault();
                var href = $(e.currentTarget).attr('href'),
                    formData = href.slice(href.indexOf('?') + 1);
                this.showFormModal(formData);
            }.bind(this));

            // Initialise for multiple users acceptance form.
            triggers = $('form[data-action=acceptmodal]');
            triggers.on('submit', function(e) {
                e.preventDefault();
                if ($(e.currentTarget).find('input[type=checkbox][name="userids[]"]:checked').length) {
                    var formData = $(e.currentTarget).serialize();
                    this.showFormModal(formData, triggers);
                } else {
                    Str.get_strings(this.stringKeys).done(function(strings) {
                        Notification.alert('', strings[2], strings[3]);
                    });
                }
            }.bind(this));
        };

        /**
         * Show modal with a form
         *
         * @param {String} formData
         * @param {object} triggerElement The trigger HTML jQuery object
         */
        AcceptOnBehalf.prototype.showFormModal = function(formData, triggerElement) {
            var action;
            var params = formData.split('&');
            for (var i = 0; i < params.length; i++) {
                var pair = params[i].split('=');
                if (pair[0] == 'action') {
                    action = pair[1];
                }
            }
            // Fetch the title string.
            Str.get_strings(this.stringKeys).done(function(strings) {
                var title;
                var saveText;
                if (action == 'revoke') {
                    title = strings[4];
                    saveText = strings[5];
                } else {
                    title = strings[0];
                    saveText = strings[1];
                }
                // Create the modal.
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: ''
                }, triggerElement).done(function(modal) {
                    this.modal = modal;
                    this.setupFormModal(formData, saveText);
                }.bind(this));
            }.bind(this))
                .fail(Notification.exception);
        };

        /**
         * Setup form inside a modal
         *
         * @param {String} formData
         * @param {String} saveText
         */
        AcceptOnBehalf.prototype.setupFormModal = function(formData, saveText) {
            var modal = this.modal;

            modal.setLarge();

            modal.setSaveButtonText(saveText);

            // We want to reset the form every time it is opened.
            modal.getRoot().on(ModalEvents.hidden, this.destroy.bind(this));

            modal.setBody(this.getBody(formData));

            // We catch the modal save event, and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
            // We also catch the form submit event and use it to submit the form with ajax.
            modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

            modal.show();
        };

        /**
         * Load the body of the modal (contains the form)
         *
         * @method getBody
         * @private
         * @param {String} formData
         * @return {Promise}
         */
        AcceptOnBehalf.prototype.getBody = function(formData) {
            if (typeof formData === "undefined") {
                formData = {};
            }
            // Get the content of the modal.
            var params = {jsonformdata: JSON.stringify(formData)};
            return Fragment.loadFragment('tool_policy', 'accept_on_behalf', this.contextid, params);
        };

        /**
         * Submit the form inside the modal via AJAX request
         *
         * @method submitFormAjax
         * @private
         * @param {Event} e Form submission event.
         */
        AcceptOnBehalf.prototype.submitFormAjax = function(e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            // Convert all the form elements values to a serialised string.
            var formData = this.modal.getRoot().find('form').serialize();

            var requests = Ajax.call([{
                methodname: 'tool_policy_submit_accept_on_behalf',
                args: {jsonformdata: JSON.stringify(formData)}
            }]);
            requests[0].done(function(data) {
                if (data.validationerrors) {
                    this.modal.setBody(this.getBody(formData));
                } else {
                    this.close();
                }
            }.bind(this)).fail(Notification.exception);
        };

        /**
         * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
         *
         * @method submitForm
         * @param {Event} e Form submission event.
         * @private
         */
        AcceptOnBehalf.prototype.submitForm = function(e) {
            e.preventDefault();
            this.modal.getRoot().find('form').submit();
        };

        /**
         * Close the modal
         */
        AcceptOnBehalf.prototype.close = function() {
            this.destroy();
            document.location.reload();
        };

        /**
         * Destroy the modal
         */
        AcceptOnBehalf.prototype.destroy = function() {
            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });
            this.modal.destroy();
        };

        return /** @alias module:tool_policy/acceptmodal */ {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {int} contextid The contextid for the course.
             * @return {AcceptOnBehalf}
             */
            getInstance: function(contextid) {
                return new AcceptOnBehalf(contextid);
            }
        };
    });
