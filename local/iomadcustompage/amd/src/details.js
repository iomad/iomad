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
 * IOMAD Custom page details editor
 *
 * @module      local_iomadcustompage/details
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from 'core/pending';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString} from 'core/str';
import DynamicForm from 'core_form/dynamicform';
import {add as addToast} from 'core/toast';
import * as pageSelectors from 'local_iomadcustompage/local/selectors';
import Notification from 'core/notification';


let pageId = 0;
let contextId = 0;

export const init = (id, contextid) => {

    pageId = id;
    contextId = contextid;

  // Lets get the form and add into proper container
    editDetailsCard(pageId, contextId);
};


const editDetailsCard = (pageid, contextid) => {
    const pendingPromise = new Pending('local_iomadcustompage/details:edit');

    // Load audience form with data for editing, then toggle visible controls in the card.
    const detailsForm = initDetailsCardForm();
    detailsForm.load({'id': pageid, 'needactionbuttons': 1})
        .then(() => {
            return pendingPromise.resolve();
        })
        .catch(Notification.exception);
};

const initDetailsCardForm = () => {
    const detailsFormContainer = document.querySelector(pageSelectors.regions.detailsFormContainer);
    const detailsForm = new DynamicForm(detailsFormContainer, '\\local_iomadcustompage\\form\\page');

    // After submitting the form, update the card instance and description properties.
    detailsForm.addEventListener(detailsForm.events.FORM_SUBMITTED, data => {

        return getString('detailssaved', 'local_iomadcustompage')
            .then(addToast).then(() => {
              return window.location.reload();
            });
    });

    return detailsForm;
};

