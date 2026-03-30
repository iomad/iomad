// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public group as published by
// the Free Software Foundation, either version 3 of the group, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public group for more details.
//
// You should have received a copy of the GNU General Public group
// along with Moodle.  If not, see <http://www.gnu.org/groups/>.

/**
 * IOMAD dashboard edit eCommerce groups Modal forms.
 *
 * @module     block_iomad_learningpath
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @group    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
import notification from 'core/notification';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

const selectors = {
    showEditgroupform: '[data-action="show-groupeditform"]',
    showDeletegroupprompt: '[data-action="show-deletegroupconfirm"]',
};

export const init = () => {

    // Add the group edit form handler.
    const showEditgroupform = document.querySelectorAll(selectors.showEditgroupform);
    const showDeletegroupprompt = document.querySelectorAll(selectors.showDeletegroupprompt);

    // Do we have any of these?
    if (showEditgroupform === null &&
        showDeletegroupprompt === null
    ) {
        return;
    }

    // Edit group form handler.
    for (let i = 0; i < showEditgroupform.length; i++) {
        showEditgroupform[i].addEventListener('click', event => {
            event.preventDefault();

            var groupid = showEditgroupform[i].getAttribute('data-groupid');
            var companyid = showEditgroupform[i].getAttribute('data-companyid');
            var pathid = showEditgroupform[i].getAttribute('data-pathid');
            if (groupid == 0) {
                var title = getString('addgroup', 'block_iomad_learningpath');
            } else {
                var title = getString('editgroup', 'block_iomad_learningpath');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_learningpath\\forms\\editgroup_form',
                args: {
                    companyid: companyid,
                    groupid: groupid,
                    pathid: pathid,
                },
                modalConfig: {title},
                returnFocus: showEditgroupform[i],
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

                // Reload the page.
                window.location.reload(true);
            });
        });
    }

    // Add the group delete prompt handler.
    for (let i = 0; i < showDeletegroupprompt.length; i++) {
        showDeletegroupprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var groupid = showDeletegroupprompt[i].getAttribute('data-groupid');
            var pathid = showDeletegroupprompt[i].getAttribute('data-pathid');
            var groupName = showDeletegroupprompt[i].getAttribute('data-groupname');
            var companyid = showDeletegroupprompt[i].getAttribute('data-companyid');
            getStrings([
                { key: 'deletegroup', component: 'block_iomad_learningpath' },
                { key: 'deletegroupcheckfull', component: 'block_iomad_learningpath', param: groupName },
                { key: 'yes' },
                { key: 'groupinuse', component: 'block_iomad_learningpath' },
                { key: 'ok' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_learningpath_delete_group',
                        args: {
                            groupid: groupid,
                            pathid: pathid,
                            companyid: companyid,
                        },
                        done: function () {
                            window.location.reload(true);
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }
};
