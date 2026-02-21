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
 * IOMAD dashboard clone course Modal form.
 *
 * @module     block_iomad_company_admin
 * @copyright  2026 E-Learn Design
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
    showCopycourseform: '[data-action="show-copycourseform"]',
};

export const init = () => {
    const showCopycourseform = document.querySelectorAll(selectors.showCopycourseform);
    if (showCopycourseform === null) {
        return;
    }

    for (let i = 0; i < showCopycourseform.length; i++) {
        showCopycourseform[i].addEventListener('click', event => {
            event.preventDefault();

            var courseName = showCopycourseform[i].getAttribute('data-coursename');
            const title = getString('copycoursetitle', 'backup', courseName);
            const form = new ModalForm({
                formClass: 'block_iomad_company_admin\\forms\\course_copy_form',
                args: {
                    companyid: showCopycourseform[i].getAttribute('data-companyid'),
                    courseid: showCopycourseform[i].getAttribute('data-courseid'),
                    companycreatedcourse: showCopycourseform[i].getAttribute('data-companycreatedcourse'),
                },
                modalConfig: {title},
                returnFocus: showCopycourseform[i],
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
                    if (e.detail.result == false) {
                        toastAdd(e.detail.returnmessage,
                            {
                                type: 'warning',
                            }
                        );
                    } else {
                        toastAdd(e.detail.returnmessage,
                        {
                            type: 'success',
                        });
                    }
                }
            });
        });
    }
};
