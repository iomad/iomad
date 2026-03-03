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
 * IOMAD dashboard delete company Modal form.
 *
 * @module     block_iomad_company_admin
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import notification from 'core/notification';

const selectors = {
    showDeleteclassroomform: '[data-action="show-deleteclassroomform"]',
};

export const init = () => {
    const showDeleteclassroomform = document.querySelectorAll(selectors.showDeleteclassroomform);
    if (showDeleteclassroomform === null) {
        return;
    }

    for (let i = 0; i < showDeleteclassroomform.length; i++) {
        showDeleteclassroomform[i].addEventListener('click', event => {
            event.preventDefault();

            var classroomid = showDeleteclassroomform[i].getAttribute('data-classroomid');
            var classroomname = showDeleteclassroomform[i].getAttribute('data-classroomname');
            var companyid = showDeleteclassroomform[i].getAttribute('data-companyid');
            getStrings([
                { key: 'classroom_delete', component: 'block_iomad_company_admin' },
                { key: 'classroom_delete_checkfull', component: 'block_iomad_company_admin', param: classroomname },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_delete_training_location',
                        args: {
                            companyid: companyid,
                            classroomid: classroomid,
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
