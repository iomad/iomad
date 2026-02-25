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
 * IOMAD dashboard delete company Modal form.
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
    showSuspendcompanyform: '[data-action="show-suspendcompanyform"]',
};

export const init = () => {
    const showSuspendcompanyform = document.querySelectorAll(selectors.showSuspendcompanyform);
    if (showSuspendcompanyform === null) {
        return;
    }

    for (let i = 0; i < showSuspendcompanyform.length; i++) {
        showSuspendcompanyform[i].addEventListener('click', event => {
            event.preventDefault();

            // What title are we showing?
            var suspended = showSuspendcompanyform[i].getAttribute('data-suspended');
            if (suspended == 0) {
                var title = getString('suspendcompany', 'block_iomad_company_admin');
            } else {
                var title = getString('unsuspendcompany', 'block_iomad_company_admin');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_company_admin\\forms\\company_suspend_form',
                args: {
                    companyid: showSuspendcompanyform[i].getAttribute('data-companyid'),
                    companyname: showSuspendcompanyform[i].getAttribute('data-companyname'),
                    suspended: showSuspendcompanyform[i].getAttribute('data-suspended'),
                },
                modalConfig: {title},
                returnFocus: showSuspendcompanyform[i],
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
                window.location.reload(true);
            });
        });
    }
};
