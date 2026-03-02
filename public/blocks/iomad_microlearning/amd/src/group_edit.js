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

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as toastAdd, addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
import notification from 'core/notification';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

const selectors = {
    showEditgroupform: '[data-action="show-editgroupform"]',
    showDeletegroupprompt: '[data-action="show-deletegroupprompt"]',
};

export const init = () => {
    const showEditgroupform = document.querySelectorAll(selectors.showEditgroupform);
    if (showEditgroupform === null) {
        return;
    }

    for (let i = 0; i < showEditgroupform.length; i++) {
        showEditgroupform[i].addEventListener('click', event => {
            event.preventDefault();

            var existing = showEditgroupform[i].getAttribute('data-groupid');
            if (existing == 0) {
                var title = getString('creategroup', 'block_iomad_microlearning');
            } else {
                var title = getString('editgroup', 'block_iomad_microlearning');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_microlearning\\forms\\group_edit_form',
                args: {
                    companyid: showEditgroupform[i].getAttribute('data-companyid'),
                    groupid: showEditgroupform[i].getAttribute('data-groupid'),
                },
                modalConfig: {title},
                returnFocus: showEditgroupform[i],
            });
            form.show().then(() => {
                addToastRegion(form.modal.getRoot()[0]);
                return true;
            }).catch(displayException);
            form.addEventListener(form.events.FORM_SUBMITTED, (e) => {

                // Remove toast region as if not it will be displayed on the closed modal.
                const modalElement = form.modal.getRoot()[0];
                const regions = modalElement.querySelectorAll('.toast-wrapper');
                regions.forEach((reg) => reg.remove());
                if (e.detail.result) {
                    if (e.detail.result == false) {
                        toastAdd(e.detail.returnmessage,
                            {
                                type: 'warning',
                            }
                        );
                    } else {
                        toastAdd(e.detail.returnmessage,
                        {
                            type: 'success',
                        });
                    }
                }
                window.location.reload(true);
            });
        });
    }

    const showDeletegroupprompt = document.querySelectorAll(selectors.showDeletegroupprompt);
    for (let i = 0; i < showDeletegroupprompt.length; i++) {
        showDeletegroupprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var groupid = showDeletegroupprompt[i].getAttribute('data-groupid');
            var groupName = showDeletegroupprompt[i].getAttribute('data-name');
            var companyid = showDeletegroupprompt[i].getAttribute('data-companyid');
            getStrings([
                { key: 'deletegroup', component: 'block_iomad_microlearning', param: groupName },
                { key: 'deletegroupcheckfull', component: 'block_iomad_microlearning', param: groupName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_microlearning_delete_group',
                        args: {
                            groupid: groupid,
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
};
