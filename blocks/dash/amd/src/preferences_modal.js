/**
 * Add a create new group modal to the page.
 *
 * @module     core_group/newgroup
 * @class      PreferencesModal
 * @copyright  2017 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
        'core/fragment', 'core/ajax', 'block_dash/select2', 'core/notification'],
    function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Select2, Notification) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     * @param {Function} onCloseCallback
     *
     * Each call to init gets it's own instance of this class.
     */
    var PreferencesModal = function(selector, contextid, onCloseCallback) {
        this.contextid = contextid;
        this.onCloseCallback = onCloseCallback;
        this.tab = "";
        this.init(selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    PreferencesModal.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    PreferencesModal.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    PreferencesModal.prototype.init = function(selector) {
        var triggers = $(selector);
        // Fetch the title string.
        return Str.get_string('editpreferences', 'block_dash').then(function(title) {
            // Create the modal.
            return ModalFactory.create({
                type: ModalFactory.types.DEFAULT,
                title: title,
                body: this.getBody()
            }, triggers);
        }.bind(this)).then(function(modal) {
            // Keep a reference to the modal.
            this.modal = modal;

            // Forms are big, we want a big modal.
            this.modal.setLarge();

            // We want to reset the form every time it is opened.
            this.modal.getRoot().on(ModalEvents.shown, function() {
                this.modal.setBody(this.getBody());
            }.bind(this));

            this.modal.getRoot().on('change', '#id_config_preferences_layout', this.submitFormAjax.bind(this, false));

            this.modal.getRoot().on('click', '[data-action=cancel]', () => {
                this.modal.hide();
            });

            // We catch the modal save event, and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
            // We also catch the form submit event and use it to submit the form with ajax.
            this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this, true));

            this.modal.getRoot().on(ModalEvents.bodyRendered, function() {
                $("#fgroup_id_available_fields .felement > fieldset > div").sortable({
                    items: ".form-check-inline.fitem",
                    handle: ".drag-handle",
                    axis: "y"
                });

                this.initSelect2();
            }.bind(this));

            this.modal.getRoot().on(ModalEvents.hidden, function(e) {
                // Prevent "changes may be lost" popup.
                window.onbeforeunload = null;
                if (this.onCloseCallback) {
                    this.onCloseCallback(e);
                }
            }.bind(this));

            this.modal.getRoot().on('click', '[data-action=change-tab]', (e) => {
                this.changeTab($(e.target).data('tab'));
            });

            return this.modal;
        }.bind(this));
    };

    PreferencesModal.prototype.changeTab = function(tab) {
        this.tab = tab;
        this.submitFormAjax(false);
    };

    /**
     * Get modal body content.
     * @method getBody
     * @param {Object} formdata
     * @private
     * @return {Promise}
     */
    PreferencesModal.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        // Get the content of the modal.
        var params = {
            jsonformdata: JSON.stringify(formdata),
            tab: this.tab
        };
        return Fragment.loadFragment('block_dash', 'block_preferences_form', this.contextid, params);
    };

    /**
     * Form submission handler.
     * @method handleFormSubmissionResponse
     * @param {Object} formData
     * @param {Boolean} closeWhenDone
     * @param {Object} response
     * @private
     */
    PreferencesModal.prototype.handleFormSubmissionResponse = function(formData, closeWhenDone, response) {
        if (response.validationerrors || !closeWhenDone) {
            this.modal.setBody(this.getBody(formData));
        } else if (closeWhenDone) {
            this.modal.hide();
        }
    };

    /**
     * @method handleFormSubmissionFailure
     * @param {Object} data
     * @private
     */
    PreferencesModal.prototype.handleFormSubmissionFailure = function(data) {
        // Oh noes! Epic fail :(
        // Ah wait - this is normal. We need to re-display the form with errors!
        this.modal.setBody(this.getBody(data));
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {boolean} closeWhenDone If true modal will close after successful submission.
     * @param {Event} e Form submission event.
     */
    PreferencesModal.prototype.submitFormAjax = function(closeWhenDone, e) {
        // We don't want to do a real form submission.
        if (e) {
            e.preventDefault();
        }

        // Now the change events have run, see if there are any "invalid" form fields.
        var invalid = $.merge(
            this.modal.getRoot().find('[aria-invalid="true"]'),
            this.modal.getRoot().find('.error')
        );

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return;
        }

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Now we can continue...
        Ajax.call([{
            methodname: 'block_dash_submit_preferences_form',
            args: {
                contextid: this.contextid,
                jsonformdata: JSON.stringify(formData)
            },
            done: this.handleFormSubmissionResponse.bind(this, formData, closeWhenDone),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }])[0].fail(Notification.exception);
    };

    PreferencesModal.prototype.getModal = function() {
        return this.modal;
    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    PreferencesModal.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };

    PreferencesModal.prototype.initSelect2 = function() {
        this.modal.getRoot().find('.select2-form select').each(function(index, element) {
            let placeholder = null;
            if ($(element).find("option[value='-1']")) {
                placeholder = {
                    id: '-1', // The value of the option
                    text: $(element).find("option[value='-1']").text()
                };
            }
            $(element).select2({
                dropdownParent: $(this).parent(),
                allowClear: true,
                theme: 'bootstrap4',
                placeholder: placeholder
            }).on('select2:unselecting', function() {
                $(this).data('unselecting', true);
            }).on('select2:opening', function(e) {
                if ($(this).data('unselecting')) {
                    $(this).removeData('unselecting');
                    e.preventDefault();
                }
            });
        });
    };

    return PreferencesModal;
});
