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

const selectors = {
    selectCompany: '[data-selector="companyselect"]',
    selectCourse: '[data-selector="courseselect"]',
    selectUser: '[data-selector="userselect"]',
};

export const init = () => {

    const selectCompany = document.querySelector(selectors.selectCompany);
    const selectCourse = document.querySelector(selectors.selectCourse);
    const selectUser = document.querySelector(selectors.selectUser);
    if (selectCompany === null &&
        selectCourse === null &&
        selectUser === null
    ) {
        return;
    }

    // We want this to happen as soon as the page finishes loading.
    $(window).on('load', function() {

        // Deal with the user select.
        const selectUserContainer = $('#fitem_id_user');
        const selectUserSelect = selectUserContainer.find('select');
        const selectUserUL = selectUserContainer.find("ul");
        const companyId = selectUser.getAttribute('data-companyid');

        // Get the users.
        ajax.call([{
            methodname: 'local_iomad_get_users_menu',
            args: {
                companyid: companyId,
            },
            done: function (e) {
                e.users.forEach(user => {
                    $(selectUserUL).append(
                        '<li role="option" data-value="' + user.id + '" class>' + user.fullname + '</li>'
                    );
                    $(selectUserSelect).append(
                        '<option value="' + user.id + '">' + user.fullname + '</option>'
                    );
                });
            }
        }]);

        // Deal with the company select.
        const selectCompanyContainer = $('#fitem_id_company');
        const selectCompanySelect = selectCompanyContainer.find('select');
        const selectCompanyUL = selectCompanyContainer.find("ul");

        // Get the companies.
        ajax.call([{
            methodname: 'local_iomad_get_companies_menu',
            args: {
                showsuspended: true,
            },
            done: function (e) {
                e.companies.forEach(company => {
                    $(selectCompanyUL).append(
                        '<li role="option" data-value="' +
                         company.id + '" class>' + company.name + '</li>'
                    );
                    $(selectCompanySelect).append(
                        '<option value="' + company.id + '">'
                         + company.name + '</option>'
                    );
                });
            }
        }]);

        // Deal with the course select.
        const selectCourseContainer = $('#fitem_id_course');
        const selectCourseSelect = selectCourseContainer.find('select');
        const selectCourseUL = selectCourseContainer.find("ul");

        // Get the courses.
        ajax.call([{
            methodname: 'local_iomad_get_courses_menu',
            args: {
                shared: true,
            },
            done: function (e) {
                e.courses.forEach(course => {
                    $(selectCourseUL).append(
                        '<li role="option" data-value="' + course.id + '" class>' + course.fullname + '</li>'
                    );
                    $(selectCourseSelect).append(
                        '<option value="' + course.id + '">' + course.fullname + '</option>'
                    );
                });
            }
        }]);
    });
};
