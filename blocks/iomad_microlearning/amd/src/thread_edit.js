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
 * IOMAD dashboard edit microlearning thread Modal form.
 *
 * @module     block_iomad_microlearning
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as toastAdd, addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
import notification from 'core/notification';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import {eventTypes as inplaceEditableEvents} from 'core/local/inplace_editable/events';

const selectors = {
    showEditthreadform: '[data-action="show-editthreadform"]',
    showDeletethreadprompt: '[data-action="show-deletethreadprompt"]',
    showClonethreadprompt: '[data-action="show-clonethreadprompt"]',
};

export const init = () => {

    // Add the thread edit form handler.
    const showEditthreadform = document.querySelectorAll(selectors.showEditthreadform);
    if (showEditthreadform === null) {
        return;
    }

    for (let i = 0; i < showEditthreadform.length; i++) {
        showEditthreadform[i].addEventListener('click', event => {
            event.preventDefault();

            var existing = showEditthreadform[i].getAttribute('data-threadid');
            if (existing == 0) {
                var title = getString('createthread', 'block_iomad_microlearning');
            } else {
                var title = getString('editthread', 'block_iomad_microlearning');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_microlearning\\forms\\thread_edit_form',
                args: {
                    companyid: showEditthreadform[i].getAttribute('data-companyid'),
                    threadid: showEditthreadform[i].getAttribute('data-threadid'),
                },
                modalConfig: {title},
                returnFocus: showEditthreadform[i],
            });
            form.show().then(() => {
                addToastRegion(form.modal.getRoot()[0]);
                return true;
            }).catch(displayException);
            form.addEventListener(form.events.FORM_SUBMITTED, () => {

                // Remove toast region as if not it will be displayed on the closed modal.
                const modalElement = form.modal.getRoot()[0];
                const regions = modalElement.querySelectorAll('.toast-wrapper');
                regions.forEach((reg) => reg.remove());
                window.location.reload(true);
            });
        });
    }

    // Add the thread delete prompt handler.
    const showDeletethreadprompt = document.querySelectorAll(selectors.showDeletethreadprompt);
    for (let i = 0; i < showDeletethreadprompt.length; i++) {
        showDeletethreadprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var threadid = showDeletethreadprompt[i].getAttribute('data-threadid');
            var threadName = showDeletethreadprompt[i].getAttribute('data-name');
            var companyid = showDeletethreadprompt[i].getAttribute('data-companyid');
            var tableRow = $(showDeletethreadprompt[i]).closest('tr');
            var success = getString('threaddeleted', 'block_iomad_microlearning');
            getStrings([
                { key: 'deletethread', component: 'block_iomad_microlearning' },
                { key: 'deletethreadcheckfull', component: 'block_iomad_microlearning', param: threadName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_microlearning_delete_thread',
                        args: {
                            threadid: threadid,
                            companyid: companyid,
                        },
                        done: function () {
                            toastAdd(success,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                            tableRow.remove();
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add clone thread handler.
    const showClonethreadprompt = document.querySelectorAll(selectors.showClonethreadprompt);
    for (let i = 0; i < showClonethreadprompt.length; i++) {
        showClonethreadprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var threadid = showClonethreadprompt[i].getAttribute('data-threadid');
            var threadName = showClonethreadprompt[i].getAttribute('data-name');
            var companyid = showClonethreadprompt[i].getAttribute('data-companyid');
            getStrings([
                { key: 'clonethread', component: 'block_iomad_microlearning' },
                { key: 'clonethreadcheckfull', component: 'block_iomad_microlearning', param: threadName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_microlearning_clone_thread',
                        args: {
                            threadid: threadid,
                            companyid: companyid,
                        },
                        done: function () {
                            location.reload();
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add inplace editable error handler.
    $('body').on(inplaceEditableEvents.elementUpdateFailed, '[data-inplaceeditable]', async(e) => {
        var exception = e.detail.exception; // The exception object returned by the callback.

        if (exception.errorcode !== 'nameemptyerror' &&
            exception.errorcode !== 'nameinuse'
        ) {
            return;
        }
        e.preventDefault(); // This will prevent default error dialogue.

        toastAdd(exception.message,
            {
                type: 'warning',
                autohide: true,
                closeButton: true,
            }
        );
    });
};
