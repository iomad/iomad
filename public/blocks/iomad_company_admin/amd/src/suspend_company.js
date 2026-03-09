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

import $ from 'jquery';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import notification from 'core/notification';
import {add as toastAdd} from 'core/toast';
import {get_string as getString} from 'core/str';

const selectors = {
    showSuspendcompanyprompt: '[data-action="show-suspendcompanyprompt"]',
};

export const init = () => {
    const showSuspendcompanyprompt = document.querySelectorAll(selectors.showSuspendcompanyprompt);
    if (showSuspendcompanyprompt === null) {
        return;
    }

    for (let i = 0; i < showSuspendcompanyprompt.length; i++) {
        showSuspendcompanyprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var currentvalue = showSuspendcompanyprompt[i].getAttribute('data-suspended');
            var companyName = showSuspendcompanyprompt[i].getAttribute('data-name');
            var companyid = showSuspendcompanyprompt[i].getAttribute('data-companyid');
            var tableRow = $(showSuspendcompanyprompt[i]).closest('tr');
            var myIcon = $(showSuspendcompanyprompt[i]).find('i');

            if (currentvalue == 0) {
                var title = 'suspendcompany';
                var checktext = 'suspendcompanycheckfull';
                var success = getString('companysuspended', 'block_iomad_company_admin');
            } else {
                var title = 'unsuspendcompany';
                var checktext = 'unsuspendcompanycheckfull';
                var success = getString('companyunsuspended', 'block_iomad_company_admin');
            }
            getStrings([
                { key: title, component: 'block_iomad_company_admin' },
                { key: checktext, component: 'block_iomad_company_admin', param: companyName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_suspend_company',
                        args: {
                            companyid: companyid,
                            currentvalue: currentvalue,
                        },
                        done: function () {
                            toastAdd(success,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                            if (currentvalue == 0) {
                                tableRow.removeClass('dimmed_text');
                                tableRow.addClass('dimmed_text');

                                // Change icon to closed eye.
                                myIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                                showSuspendcompanyprompt[i].setAttribute('data-suspended', '1');
                            } else {
                                tableRow.removeClass('dimmed_text');

                                // Change icon to open eye.
                                myIcon.removeClass('fa-eye');
                                myIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                                showSuspendcompanyprompt[i].setAttribute('data-suspended', '0');
                            }
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }
};
