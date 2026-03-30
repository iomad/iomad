// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public group as published by
// the Free Software Foundation, either version 3 of the group, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public group for more details.
//
// You should have received a copy of the GNU General Public group
// along with Moodle.  If not, see <http://www.gnu.org/groups/>.

/**
 * IOMAD dashboard edit eCommerce groups Modal forms.
 *
 * @module     block_iomad_learningpath
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @group    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';

const selectors = {
    showEditpathform: '[data-action="show-editform"]',
};

export const init = () => {

    // Add the group edit form handler.
    const showEditpathform = document.querySelectorAll(selectors.showEditpathform);

    // Do we have any of these?
    if (showEditpathform === null) {
        return;
    }

    // Edit group form handler.
    for (let i = 0; i < showEditpathform.length; i++) {
        showEditpathform[i].addEventListener('click', event => {
            event.preventDefault();

            var companyid = showEditpathform[i].getAttribute('data-companyid');
            var pathid = showEditpathform[i].getAttribute('data-pathid');
            if (pathid == 0) {
                var title = getString('addpath', 'block_iomad_learningpath');
            } else {
                var title = getString('editpath', 'block_iomad_learningpath');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_learningpath\\forms\\editpath_form',
                args: {
                    companyid: companyid,
                    pathid: pathid,
                },
                modalConfig: {title},
                returnFocus: showEditpathform[i],
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

                // Reload the page.
                window.location.reload(true);
            });
        });
    }
};
