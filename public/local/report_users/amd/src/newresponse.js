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
 * @module     local_report_users
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
const selectors = {
    showNewentryform: '[data-action="show-newentryform"]',
};

export const init = () => {
    const showNewentryform = document.querySelectorAll(selectors.showNewentryform);
    if (showNewentryform === null) {
        return;
    }

    for (let i = 0; i < showNewentryform.length; i++) {
        showNewentryform[i].addEventListener('click', event => {
            event.preventDefault();

            var userid = showNewentryform[i].getAttribute('data-userid');
            var companyid = showNewentryform[i].getAttribute('data-companyid');
            var userName = showNewentryform[i].getAttribute('data-username');
            var title = getString('createentry', 'local_report_users', userName);
            const form = new ModalForm({
                formClass: 'local_report_users\\forms\\add_entry_form',
                args: {
                    companyid: companyid,
                    userid: userid,
                },
                modalConfig: {title},
                returnFocus: showNewentryform[i],
            });
            form.show().then(() => {
                addToastRegion(form.modal.getRoot()[0]);
                return true;
            }).catch(displayException);
            form.addEventListener(form.events.FORM_SUBMITTED, () => {

                // Remove toast region as if not it will be displayed on the closed modal.
                const modalElement = form.modal.getRoot()[0];
                const regions = modalElement.querySelectorAll('.toast-wrapper');
                regions.forEach((reg) => reg.remove());
                location.reload();
            });
        });
    }
};
