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
 * IOMAD dashboard edit eCommerce products Modal forms.
 *
 * @module     block_iomad_commerce
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
    showEditorderform: '[data-action="show-ordereditform"]',
};

export const init = () => {

    // Add the product edit form handler.
    const showEditorderform = document.querySelectorAll(selectors.showEditorderform);

    // Do we have any of these?
    if (showEditorderform === null) {
        return;
    }

    for (let i = 0; i < showEditorderform.length; i++) {
        showEditorderform[i].addEventListener('click', event => {
            event.preventDefault();

            var orderid = showEditorderform[i].getAttribute('data-orderid');
            var companyid = showEditorderform[i].getAttribute('data-companyid');
            var title = getString('viewinvoice', 'block_iomad_commerce');
            const form = new ModalForm({
                formClass: 'block_iomad_commerce\\forms\\order_edit_form',
                args: {
                    companyid: companyid,
                    orderid: orderid,
                },
                modalConfig: {title},
                returnFocus: showEditorderform[i],
                saveButtonText: '',
                saveButtonClasses: 'hidden',
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
                toastAdd(e.returnmessage,
                    {
                        type: 'success',
                        autohide: true,
                        closeButton: true,
                    });
            });
        });
    }
};
