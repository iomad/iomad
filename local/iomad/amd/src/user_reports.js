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
 * Local IOMAD common report user modal prompts
 *
 * @module     local_iomad
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import notification from 'core/notification';
import {add as toastAdd} from 'core/toast';

const selectors = {
    showClearCourseuserprompt: '[data-action="show-clearcourseuserprompt"]',
    showPurgeCourseuserprompt: '[data-action="show-purgecourseuserprompt"]',
    showRegenCertuserprompt: '[data-action="show-regencertuserprompt"]',
    showResetCourseuserprompt: '[data-action="show-resetcourseuserprompt"]',
    showRevokeLicenseuserprompt: '[data-action="show-revokelicenseuserprompt"]',
};

export const init = () => {
    const showClearCourseuserprompt = document.querySelectorAll(selectors.showClearCourseuserprompt);
    const showPurgeCourseuserprompt = document.querySelectorAll(selectors.showPurgeCourseuserprompt);
    const showRegenCertuserprompt = document.querySelectorAll(selectors.showRegenCertuserprompt);
    const showResetCourseuserprompt = document.querySelectorAll(selectors.showResetCourseuserprompt);
    const showRevokeLicenseuserprompt = document.querySelectorAll(selectors.showRevokeLicenseuserprompt);

    // Don't do anything if there is nothing to be done.
    if (showClearCourseuserprompt === null &&
        showPurgeCourseuserprompt === null &&
        showRegenCertuserprompt === null &&
        showResetCourseuserprompt === null &&
        showRevokeLicenseuserprompt === null) {
        return;
    }

    // Add clear user modal prompt.
    for (let i = 0; i < showClearCourseuserprompt.length; i++) {
        showClearCourseuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userName = showClearCourseuserprompt[i].getAttribute('data-username');
            var courseName = showClearCourseuserprompt[i].getAttribute('data-coursename');
            var userid = showClearCourseuserprompt[i].getAttribute('data-userid');
            var courseid = showClearCourseuserprompt[i].getAttribute('data-courseid');
            var trackid = showClearCourseuserprompt[i].getAttribute('data-trackid');
            var companyid = showClearCourseuserprompt[i].getAttribute('data-companyid');
            var tableRow = $(showClearCourseuserprompt[i]).closest('tr');

            getStrings([
                { key: 'clearcourse', component: 'local_iomad' },
                {
                    key: 'clearcourseconfirm',
                    component: 'local_iomad',
                    param:
                    {
                        username: userName,
                        coursename: courseName,
                    }
                },
                { key: 'yes' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_iomad_clear_user_course',
                        args: {
                            companyid: companyid,
                            userid: userid,
                            courseid: courseid,
                            trackid: trackid,
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

                                    // Remove the row.
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

    // Add purge user modal prompt.
    for (let i = 0; i < showPurgeCourseuserprompt.length; i++) {
        showPurgeCourseuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userName = showPurgeCourseuserprompt[i].getAttribute('data-username');
            var courseName = showPurgeCourseuserprompt[i].getAttribute('data-coursename');
            var userid = showPurgeCourseuserprompt[i].getAttribute('data-userid');
            var courseid = showPurgeCourseuserprompt[i].getAttribute('data-courseid');
            var trackid = showPurgeCourseuserprompt[i].getAttribute('data-trackid');
            var companyid = showPurgeCourseuserprompt[i].getAttribute('data-companyid');
            var tableRow = $(showPurgeCourseuserprompt[i]).closest('tr');

            getStrings([
                { key: 'purgerecord', component: 'local_iomad' },
                {
                    key: 'purgerecordconfirm',
                    component: 'local_iomad',
                    param:
                    {
                        username: userName,
                        coursename: courseName,
                    }
                },
                { key: 'yes' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_iomad_purge_user_course',
                        args: {
                            companyid: companyid,
                            userid: userid,
                            courseid: courseid,
                            trackid: trackid,
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

                                    // Remove the row.
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

    // Add regen user cert modal prompt.
    for (let i = 0; i < showRegenCertuserprompt.length; i++) {
        showRegenCertuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userName = showRegenCertuserprompt[i].getAttribute('data-username');
            var courseName = showRegenCertuserprompt[i].getAttribute('data-coursename');
            var userid = showRegenCertuserprompt[i].getAttribute('data-userid');
            var courseid = showRegenCertuserprompt[i].getAttribute('data-courseid');
            var trackid = showRegenCertuserprompt[i].getAttribute('data-trackid');
            var companyid = showRegenCertuserprompt[i].getAttribute('data-companyid');

            getStrings([
                { key: 'redocert', component: 'local_iomad' },
                {
                    key: 'redocertconfirm',
                    component: 'local_iomad',
                    param:
                    {
                        username: userName,
                        coursename: courseName,
                    }
                },
                { key: 'yes' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_iomad_regencert_user_course',
                        args: {
                            companyid: companyid,
                            userid: userid,
                            courseid: courseid,
                            trackid: trackid,
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

    // Add reset user modal prompt.
    for (let i = 0; i < showResetCourseuserprompt.length; i++) {
        showResetCourseuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userName = showResetCourseuserprompt[i].getAttribute('data-username');
            var courseName = showResetCourseuserprompt[i].getAttribute('data-coursename');
            var userid = showResetCourseuserprompt[i].getAttribute('data-userid');
            var courseid = showResetCourseuserprompt[i].getAttribute('data-courseid');
            var trackid = showResetCourseuserprompt[i].getAttribute('data-trackid');
            var companyid = showResetCourseuserprompt[i].getAttribute('data-companyid');

            getStrings([
                { key: 'resetcourse', component: 'local_iomad' },
                {
                    key: 'resetcourseconfirm',
                    component: 'local_iomad',
                    param:
                    {
                        username: userName,
                        coursename: courseName,
                    }
                },
                { key: 'yes' },
                { key: 'purgerecord', component: 'local_iomad' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_iomad_reset_user_course',
                        args: {
                            companyid: companyid,
                            userid: userid,
                            courseid: courseid,
                            trackid: trackid,
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

    // Add revoke license user modal prompt.
    for (let i = 0; i < showRevokeLicenseuserprompt.length; i++) {
        showRevokeLicenseuserprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var userName = showRevokeLicenseuserprompt[i].getAttribute('data-username');
            var courseName = showRevokeLicenseuserprompt[i].getAttribute('data-coursename');
            var userid = showRevokeLicenseuserprompt[i].getAttribute('data-userid');
            var courseid = showRevokeLicenseuserprompt[i].getAttribute('data-courseid');
            var trackid = showRevokeLicenseuserprompt[i].getAttribute('data-trackid');
            var licenseid = showRevokeLicenseuserprompt[i].getAttribute('data-licenseid');
            var companyid = showRevokeLicenseuserprompt[i].getAttribute('data-companyid');
            var tableRow = $(showRevokeLicenseuserprompt[i]).closest('tr');

            getStrings([
                { key: 'revokelicense', component: 'local_iomad' },
                {
                    key: 'revokelicenseconfirm',
                    component: 'local_iomad',
                    param:
                    {
                        username: userName,
                        coursename: courseName,
                    }
                },
                { key: 'yes' },
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'local_iomad_licenserevoke_user_course',
                        args: {
                            companyid: companyid,
                            userid: userid,
                            courseid: courseid,
                            trackid: trackid,
                            licenseid: licenseid,
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

                                    // Remove the button.
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
};
