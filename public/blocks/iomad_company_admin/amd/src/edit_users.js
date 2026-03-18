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
 * IOMAD dashboard edit users modal prompts
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
    showSuspenduserprompt: '[data-action="show-suspenduserprompt"]',
    showDeleteuserprompt: '[data-action="show-deleteuserprompt"]',
    showResetuserprompt: '[data-action="show-resetuserprompt"]',
};

export const init = () => {
    const showSuspenduserprompt = document.querySelectorAll(selectors.showSuspenduserprompt);
    const showDeleteuserprompt = document.querySelectorAll(selectors.showDeleteuserprompt);
    const showResetuserprompt = document.querySelectorAll(selectors.showResetuserprompt);
    if (showSuspenduserprompt === null &&
        showResetuserprompt === null &&
        showDeleteuserprompt === null) {
        return;
    }

    // Add suspend user modal prompt.
    for (let i = 0; i < showSuspenduserprompt.length; i++) {
        showSuspenduserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var currentvalue = showSuspenduserprompt[i].getAttribute('data-suspended');
            var userName = showSuspenduserprompt[i].getAttribute('data-username');
            var userid = showSuspenduserprompt[i].getAttribute('data-userid');
            var companyid = showSuspenduserprompt[i].getAttribute('data-companyid');
            var tableRow = $(showSuspenduserprompt[i]).closest('tr');

            if (currentvalue == 0) {
                var title = 'suspenduser';
                var checktext = 'suspendcheckfull';
                var success = getString('companyusersuspended', 'block_iomad_company_admin');
            } else {
                var title = 'unsuspenduser';
                var checktext = 'unsuspendcheckfull';
                var success = getString('companyuserunsuspended', 'block_iomad_company_admin');
            }
            getStrings([
                { key: title, component: 'block_iomad_company_admin' },
                { key: checktext, component: 'block_iomad_company_admin', param: userName },
                { key: 'yes' },
                { key: 'no' },
                { key: 'suspend', component: 'block_iomad_company_admin' },
                { key: 'unsuspend', component: 'block_iomad_company_admin' },
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_suspend_user',
                        args: {
                            companyid: companyid,
                            userid: userid,
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
                                tableRow.find('span').removeClass('dimmed_text');
                                tableRow.addClass('dimmed_text');

                                // Change current data value.
                                showSuspenduserprompt[i].setAttribute('data-suspended', '1');
                                $(showSuspenduserprompt[i]).find('.menu-action-text').text(s[5]);
                            } else {
                                tableRow.removeClass('dimmed_text');
                                tableRow.find('span').removeClass('dimmed_text');

                                // Change current data value.
                                showSuspenduserprompt[i].setAttribute('data-suspended', '0');
                                $(showSuspenduserprompt[i]).find('.menu-action-text').text(s[4]);
                            }
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add the delete user modal confirm.
    for (let i = 0; i < showDeleteuserprompt.length; i++) {
        showDeleteuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userid = showDeleteuserprompt[i].getAttribute('data-userid');
            var userName = showDeleteuserprompt[i].getAttribute('data-username');
            var companyid = showDeleteuserprompt[i].getAttribute('data-companyid');
            var tableRow = $(showDeleteuserprompt[i]).closest('tr');
            getStrings([
                { key: 'deleteuser', component: 'block_iomad_company_admin' },
                { key: 'deletecheckfull', component: 'block_iomad_company_admin', param: userName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_delete_user',
                        args: {
                            companyid: companyid,
                            userid: userid,
                        },
                        done: function (e) {
                            if (e.result) {
                                if (e.result == false) {
                                    toastAdd(e.returnmessage,
                                        {
                                            type: 'warning',
                                            autohide: true,
                                            closeButton: true,
                                        }
                                    );
                                } else {
                                    toastAdd(e.returnmessage,
                                        {
                                            type: 'success',
                                            autohide: true,
                                            closeButton: true,
                                        });
                                    tableRow.remove();
                                }
                            }
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add the reset user modal confirm.
    for (let i = 0; i < showResetuserprompt.length; i++) {
        showResetuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userid = showResetuserprompt[i].getAttribute('data-userid');
            var userName = showResetuserprompt[i].getAttribute('data-username');
            var companyid = showResetuserprompt[i].getAttribute('data-companyid');
            getStrings([
                { key: 'resetpassword', component: 'block_iomad_company_admin' },
                { key: 'resetpasswordcheckfull', component: 'block_iomad_company_admin', param: userName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_company_admin_reset_user',
                        args: {
                            companyid: companyid,
                            userid: userid,
                        },
                        done: function (e) {
                            if (e.result) {
                                if (e.result == false) {
                                    toastAdd(e.returnmessage,
                                        {
                                            type: 'warning',
                                            autohide: true,
                                            closeButton: true,
                                        }
                                    );
                                } else {
                                    toastAdd(e.returnmessage,
                                        {
                                            type: 'success',
                                            autohide: true,
                                            closeButton: true,
                                        });
                                }
                            }
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }
};
