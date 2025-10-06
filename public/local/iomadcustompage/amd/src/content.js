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
 * IOMAD Custom page content editor
 *
 * @module      local_iomadcustompage/content
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

import 'core/inplace_editable';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';
import * as pageSelectors from 'local_iomadcustompage/local/selectors';
import {createPageModal} from 'local_iomadcustompage/local/repository/modals';

let initialized = false;

/**
 * Initialise editor and all it's modules
 */
export const init = () => {
  // Ensure we only add our listeners once (can be called multiple times by mustache template).
  if (initialized) {
    return;
  }

  // Add event handlers to generic report editor elements.
  document.addEventListener('click', event => {

    // Edit report details modal.
    const pageEdit = event.target.closest(pageSelectors.actions.pageEdit);
    if (pageEdit) {
      event.preventDefault();

      const pageModal = createPageModal(event.target, getString('editpagedetails', 'local_iomadcustompage'),
        pageEdit.dataset.pageId);
      pageModal.addEventListener(pageModal.events.FORM_SUBMITTED, () => {
        getString('pageupdated', 'local_iomadcustompage')
          .then(addToast)
          .then(() => {
            return window.location.reload();
          })
          .catch(Notification.exception);
      });
      pageModal.show();
    }
  });

  initialized = true;
};
