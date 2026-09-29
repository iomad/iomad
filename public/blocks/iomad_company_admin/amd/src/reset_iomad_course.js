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
import notification from 'core/notification';

const selectors = {
    resetCourse: '[data-action="reset-companycompanycourse"]',
};

export const init = () => {
    const resetCourse = document.querySelectorAll(selectors.resetCourse);
    if (resetCourse === null) {
        return;
    }

    for (let i = 0; i < resetCourse.length; i++) {
        resetCourse[i].addEventListener('click', event => {
            event.preventDefault();

            var courseID = resetCourse[i].getAttribute('data-courseid');
            var fieldName = resetCourse[i].getAttribute('data-fieldname');
            var companyid = resetCourse[i].getAttribute('data-companyid');

            ajax.call([{
                methodname: 'block_iomad_company_admin_reset_course_value',
                args: {
                    companyid: companyid,
                    courseid: courseID,
                    fieldname: fieldName,
                },
                done: function () {
                    location.reload();
                },
                fail: notification.exception,
            }]);
        });
    }
};
