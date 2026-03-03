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
 * IOMAD dashboard suspend company Modal confirm.
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
    showConfirmdeletegroup: '[data-action="show-confirmdeletegroup"]',
};

export const init = () => {
    const showConfirmdeletegroup = document.querySelectorAll(selectors.showConfirmdeletegroup);
    if (showConfirmdeletegroup === null) {
        return;
    }

    for (let i = 0; i < showConfirmdeletegroup.length; i++) {
        showConfirmdeletegroup[i].addEventListener('click', event => {
            event.preventDefault();

            var groupid = showConfirmdeletegroup[i].getAttribute('data-groupid');
            var groupName = showConfirmdeletegroup[i].getAttribute('data-groupname');
            var companyid = showConfirmdeletegroup[i].getAttribute('data-companyid');
            var courseid = showConfirmdeletegroup[i].getAttribute('data-courseid');
            getStrings([
                { key: 'deletegroup', component: 'block_iomad_company_admin' },
                { key: 'deletegroupcheckfull', component: 'block_iomad_company_admin', param: groupName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_delete_company_group',
                        args: {
                            companyid: companyid,
                            courseid: courseid,
                            groupid: groupid,
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
