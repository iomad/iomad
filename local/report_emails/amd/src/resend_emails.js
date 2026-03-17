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
 * @module     local_report_emails
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
    showConfirmresendemail: '[data-action="show-confirmresendemail"]',
    showConfirmresendallemails: '[data-action="show-confirmresendallemails"]',
};

export const init = () => {

    // Set up the page selectors.
    const showConfirmresendemail = document.querySelectorAll(selectors.showConfirmresendemail);
    const showConfirmresendallemails = document.querySelectorAll(selectors.showConfirmresendallemails);
    if (showConfirmresendemail === null && showConfirmresendallemails === null) {
        return;
    }

    // Add the resend email handler.
    for (let i = 0; i < showConfirmresendemail.length; i++) {
        showConfirmresendemail[i].addEventListener('click', event => {
            event.preventDefault();

            var emailid = showConfirmresendemail[i].getAttribute('data-emailid');
            var companyid = showConfirmresendemail[i].getAttribute('data-companyid');
            var sentpara = $(showConfirmresendemail[i]).closest('tr').find('p.lre_sent_date');
            var success = getString('emailresentsuccessfully', 'local_report_emails');
            getStrings([
                { key: 'resendemail', component: 'local_report_emails' },
                { key: 'resendemailfull', component: 'local_report_emails' },
                { key: 'resend', component: 'local_report_emails' },
                { key: 'never', component: 'moodle' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_report_emails_resend_email',
                        args: {
                            companyid: companyid,
                            emailid: emailid,
                        },
                        done: function () {
                            toastAdd(success,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                            sentpara.text(s[3]);
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add the resend all emails handler.
    for (let i = 0; i < showConfirmresendallemails.length; i++) {
        showConfirmresendallemails[i].addEventListener('click', event => {
            event.preventDefault();

            var postURL = showConfirmresendallemails[i].getAttribute('data-posturl');
            var success = getString('allemailsresentsuccessfully', 'local_report_emails');
            getStrings([
                { key: 'resendallemails', component: 'local_report_emails' },
                { key: 'resendallemailsfull', component: 'local_report_emails' },
                { key: 'resend', component: 'local_report_emails' },
                { key: 'never', component: 'moodle' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    // Post the update.
                    $.post(postURL);

                    // Set the text value for all of the sent fields.
                    $(".lre_sent_date").text(s[3]);

                    // Add a popup to tell them.
                    toastAdd(success,
                        {
                            type: 'success',
                            autohide: true,
                            closeButton: true,
                        });
                });
            });
        });
    }
};
