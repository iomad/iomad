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
 * mod_trainingevent attendance Modal form.
 *
 * @module     mod_trainingevent
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as toastAdd, addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
const selectors = {
    showAttendanceform: '[data-action="show-Attendanceform"]',
};

export const init = () => {
    const showAttendanceform = document.querySelectorAll(selectors.showAttendanceform);
    if (showAttendanceform === null) {
        return;
    }

    for (let i = 0; i < showAttendanceform.length; i++) {
        showAttendanceform[i].addEventListener('click', event => {
            event.preventDefault();

            const attendanceID = showAttendanceform[i].getAttribute('data-attendanceid');
            const waitListed = showAttendanceform[i].getAttribute('data-waitlisted');
            const approvalType = showAttendanceform[i].getAttribute('data-approvaltype');
            var title = getString('attend', 'trainingevent');
            if (attendanceID != 0) {
                if (waitListed == 0) {
                    title = getString('updateattendance', 'trainingevent');
                } else {
                    title = getString('updatewaitlist', 'trainingevent');
                }
            } else {
                if (waitListed == 0) {
                    if (approvalType == 0) {
                        title = getString('attend', 'trainingevent');
                    } else {
                        title = getString('request', 'trainingevent');
                    }
                } else {
                    title = getString('waitlist', 'trainingevent');
                }
            }
            const form = new ModalForm({
                formClass: 'mod_trainingevent\\form\\attendance',
                args: {companyid: showAttendanceform[i].getAttribute('data-companyid'),
                       trainingeventid: showAttendanceform[i].getAttribute('data-trainingeventid'),
                       cmid: showAttendanceform[i].getAttribute('data-cmid'),
                       waitlisted: waitListed,
                       attendanceid: attendanceID,
                       approvaltype: approvalType,
                       userid: showAttendanceform[i].getAttribute('data-userid'),
                       courseid: showAttendanceform[i].getAttribute('data-courseid'),
                       requesttype: showAttendanceform[i].getAttribute('data-requesttype'),
                       dorefresh: showAttendanceform[i].getAttribute('data-dorefresh'),
                       },
                modalConfig: {title},
                returnFocus: showAttendanceform[i],
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
                    if (e.detail.returnmessage != '') {
                        toastAdd(e.detail.returnmessage,
                            {
                                type: 'success',
                            }
                        );
                    }
                }
                if (e.detail.dorefresh) {
                    location.reload(false);
                }

                var userID =  e.detail.userid;
                var bookingNotes = document.querySelector("[data-bookingnotesid='" +  userID + "']");
                var currentNotes = bookingNotes.getAttribute("data-content");
                var textFrom = "<br>" + e.detail.oldnotes + "</div>";
                var textTo = "<br>" + e.detail.newnotes + "</div>";
                bookingNotes.setAttribute("data-content", currentNotes.replace(textFrom, textTo));
            });
        });
    }
};