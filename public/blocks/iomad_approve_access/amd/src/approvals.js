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
import notification from 'core/notification';
import {add as toastAdd} from 'core/toast';

const selectors = {
    approveRequest: '[data-action="do-approverequest"]',
    denyRequest: '[data-action="do-denyrequest"]',
};

export const init = () => {

    // Add approve request handler.
    const approveRequest = document.querySelectorAll(selectors.approveRequest);
    const denyRequest = document.querySelectorAll(selectors.denyRequest);
    if (approveRequest === null && denyRequest === null) {
        return;
    }
    for (let i = 0; i < approveRequest.length; i++) {
        approveRequest[i].addEventListener('click', event => {
            event.preventDefault();

            var userid = approveRequest[i].getAttribute('data-userid');
            var activityid = approveRequest[i].getAttribute('data-activityid');
            var companyid = approveRequest[i].getAttribute('data-companyid');
            var courseid = approveRequest[i].getAttribute('data-courseid');
            var approvaltype = approveRequest[i].getAttribute('data-approvaltype');
            var myapprovaltype = approveRequest[i].getAttribute('data-myapprovaltype');
            var capacity = approveRequest[i].getAttribute('data-capacity');
            var tableRow = $(approveRequest[i]).closest('tr');

            ajax.call([{
                methodname: 'block_iomad_approve_access_approve',
                args: {
                    companyid: companyid,
                    userid: userid,
                    courseid: courseid,
                    activityid: activityid,
                    capacity: capacity,
                    approvaltype: approvaltype,
                    myapprovaltype: myapprovaltype,
                },
                done: function (e) {
                    if (e.result == true) {
                        toastAdd(e.returnmessage,
                        {
                            type: 'success',
                            autohide: true,
                            closeButton: true,
                        });
                        tableRow.remove();
                    } else {
                        toastAdd(e.returnmessage,
                        {
                            type: 'warning',
                            autohide: true,
                            closeButton: true,
                        });
                    }
                },
                fail: notification.exception,
            }]);
        });
    }

    // Add deny request handler.
    for (let i = 0; i < denyRequest.length; i++) {
        denyRequest[i].addEventListener('click', event => {
            event.preventDefault();

            var userid = denyRequest[i].getAttribute('data-userid');
            var activityid = denyRequest[i].getAttribute('data-activityid');
            var companyid = denyRequest[i].getAttribute('data-companyid');
            var courseid = denyRequest[i].getAttribute('data-courseid');
            var approvaltype = denyRequest[i].getAttribute('data-approvaltype');
            var myapprovaltype = denyRequest[i].getAttribute('data-myapprovaltype');
            var capacity = denyRequest[i].getAttribute('data-capacity');
            var tableRow = $(denyRequest[i]).closest('tr');

            ajax.call([{
                methodname: 'block_iomad_approve_access_deny',
                args: {
                    companyid: companyid,
                    userid: userid,
                    courseid: courseid,
                    activityid: activityid,
                    capacity: capacity,
                    approvaltype: approvaltype,
                    myapprovaltype: myapprovaltype,
                },
                done: function (e) {
                    if (e.result == true) {
                        toastAdd(e.returnmessage,
                        {
                            type: 'success',
                            autohide: true,
                            closeButton: true,
                        });
                        tableRow.remove();
                    } else {
                        toastAdd(e.returnmessage,
                        {
                            type: 'warning',
                            autohide: true,
                            closeButton: true,
                        });
                    }
                },
                fail: notification.exception,
            }]);
        });
    }
};
