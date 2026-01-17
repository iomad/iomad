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
 * local_report_completion form options Modal form.
 *
 * @module     local_report_completion
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
    showOptionsform: '[data-action="show-Optionsform"]',
};

export const init = () => {
    const showOptionsform = document.querySelectorAll(selectors.showOptionsform);
    if (showOptionsform === null) {
        return;
    }

    for (let i = 0; i < showOptionsform.length; i++) {
        showOptionsform[i].addEventListener('click', event => {
            event.preventDefault();

            var title = getString('options', 'grades');

            const form = new ModalForm({
                formClass: 'local_report_completion_overview\\forms\\report_options_form',
                args: {
                    firstname: showOptionsform[i].getAttribute('data-firstname'),
                    lastname: showOptionsform[i].getAttribute('data-lastname'),
                    showsuspended: showOptionsform[i].getAttribute('data-showsuspended'),
                    email: showOptionsform[i].getAttribute('data-email'),
                    perpage: showOptionsform[i].getAttribute('data-perpage'),
                    search: showOptionsform[i].getAttribute('data-search'),
                    coursesearch: showOptionsform[i].getAttribute('data-coursesearch'),
                    deptid: showOptionsform[i].getAttribute('data-deptid'),
                    courses: showOptionsform[i].getAttribute('data-courses'),
                    showtext: showOptionsform[i].getAttribute('data-showtext'),
                    firstinitial: showOptionsform[i].getAttribute('data-firstinitial'),
                    lastinitial: showOptionsform[i].getAttribute('data-lastinitial'),
                    bycourse: showOptionsform[i].getAttribute('data-bycourse'),
                    showexpiryonly: showOptionsform[i].getAttribute('data-showexpiryonly'),
                    mandatoryonly: showOptionsform[i].getAttribute('data-mandatoryonly'),
                    viewchildren: showOptionsform[i].getAttribute('data-viewchildren'),
                    showenrolledonly: showOptionsform[i].getAttribute('data-showenrolledonly'),
                    usingchildren: showOptionsform[i].getAttribute('data-usingchildren'),
                    usingmandatory: showOptionsform[i].getAttribute('data-usingmandatory'),
                },
                modalConfig: {title},
                returnFocus: showOptionsform[i],
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
                   location.assign(e.detail.reloadurl);
                }
            });
        });
    }
};