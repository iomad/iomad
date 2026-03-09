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
    showEditnuggetform: '[data-action="show-editnuggetform"]',
    showDeletenuggetprompt: '[data-action="show-deletenuggetprompt"]',
    nuggetMove: '[data-action="move-nugget"]',
};

export const init = () => {

    // Add edit nugget form handler.
    const showEditnuggetform = document.querySelectorAll(selectors.showEditnuggetform);
    if (showEditnuggetform === null) {
        return;
    }

    for (let i = 0; i < showEditnuggetform.length; i++) {
        showEditnuggetform[i].addEventListener('click', event => {
            event.preventDefault();

            var existing = showEditnuggetform[i].getAttribute('data-nuggetid');
            if (existing == 0) {
                var title = getString('createnugget', 'block_iomad_microlearning');
            } else {
                var title = getString('editnugget', 'block_iomad_microlearning');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_microlearning\\forms\\nugget_edit_form',
                args: {
                    companyid: showEditnuggetform[i].getAttribute('data-companyid'),
                    nuggetid: showEditnuggetform[i].getAttribute('data-nuggetid'),
                    threadid: showEditnuggetform[i].getAttribute('data-threadid'),
                },
                modalConfig: {title},
                returnFocus: showEditnuggetform[i],
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

    // Add delete nugget handler.
    const showDeletenuggetprompt = document.querySelectorAll(selectors.showDeletenuggetprompt);
    for (let i = 0; i < showDeletenuggetprompt.length; i++) {
        showDeletenuggetprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var nuggetid = showDeletenuggetprompt[i].getAttribute('data-nuggetid');
            var nuggetName = showDeletenuggetprompt[i].getAttribute('data-name');
            var companyid = showDeletenuggetprompt[i].getAttribute('data-companyid');
            var tableRow = $(showDeletenuggetprompt[i]).closest('tr');
            var success = getString('nuggetdeleted', 'block_iomad_microlearning');
            getStrings([
                { key: 'deletenugget', component: 'block_iomad_microlearning' },
                { key: 'deletenuggetcheckfull', component: 'block_iomad_microlearning', param: nuggetName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_microlearning_delete_nugget',
                        args: {
                            nuggetid: nuggetid,
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

    // Add move nugget handler.
    const nuggetMove = document.querySelectorAll(selectors.nuggetMove);
    for (let i = 0; i < nuggetMove.length; i++) {
        nuggetMove[i].addEventListener('click', event => {
            event.preventDefault();
            var nuggetid = nuggetMove[i].getAttribute('data-nuggetid');
            var threadid = nuggetMove[i].getAttribute('data-threadid');
            var direction = nuggetMove[i].getAttribute('data-direction');
            ajax.call([{
                methodname: 'block_iomad_microlearning_move_nugget',
                args: {
                    nuggetid: nuggetid,
                    threadid: threadid,
                    direction: direction,
                },
                done: function () {
                    location.reload();
                },
                fail: notification.exception,
            }]);
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
