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
 * IOMAD Custom pages management
 *
 * @module      local_iomadcustompage/pages_list
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

import {dispatchEvent} from 'core/event_dispatcher';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';
import * as reportEvents from 'core_reportbuilder/local/events';
import * as pageSelectors from 'local_iomadcustompage/local/selectors';
import {deletePage} from 'local_iomadcustompage/local/repository/pages';
import {createPageModal} from 'local_iomadcustompage/local/repository/modals';

/**
 * Initialise module
 */
export const init = () => {
    prefetchStrings('local_iomadcustompage', [
        'deletepage',
        'deletepageconfirm',
        'editpagedetails',
        'newpage',
        'pagedeleted',
        'pageupdated',
    ]);

    prefetchStrings('core', [
        'delete',
    ]);

    document.addEventListener('click', event => {
        const pageCreate = event.target.closest(pageSelectors.actions.pageCreate);
        if (pageCreate) {
            event.preventDefault();

            // Redirect user to editing interface for the page after submission.
            const pageModal = createPageModal(event.target, getString('newpage', 'local_iomadcustompage'));
            pageModal.addEventListener(pageModal.events.FORM_SUBMITTED, event => {
                window.location.href = event.detail;
            });

            pageModal.show();
        }

        const pageEdit = event.target.closest(pageSelectors.actions.pageEdit);
        if (pageEdit) {
            event.preventDefault();

            // Reload current report page after submission.
            // Use triggerElement to return focus to the action menu toggle.
            const triggerElement = pageEdit.closest('.dropdown').querySelector('.dropdown-toggle');
            const pageModal = createPageModal(triggerElement, getString('editpagedetails', 'local_iomadcustompage'),
                pageEdit.dataset.pageId);
            pageModal.addEventListener(pageModal.events.FORM_SUBMITTED, () => {

                let tableElement = window.document.querySelector('div.reportbuilder-report');

                getString('pageupdated', 'local_iomadcustompage')
                    .then(addToast)
                    // eslint-disable-next-line promise/always-return
                    .then(() => {
                        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, tableElement);
                    })
                    .catch(Notification.exception);
            });

            pageModal.show();
        }

        const pageDelete = event.target.closest(pageSelectors.actions.pageDelete);
        if (pageDelete) {
            event.preventDefault();

            // Use triggerElement to return focus to the action menu toggle.
            const triggerElement = pageDelete.closest('.dropdown').querySelector('.dropdown-toggle');
            Notification.saveCancelPromise(
                getString('deletepage', 'local_iomadcustompage'),
                getString('deletepageconfirm', 'local_iomadcustompage', pageDelete.dataset.pageName),
                getString('delete', 'core'),
                {triggerElement}
            ).then(() => {
                const pendingPromise = new Pending('local_iomadcustompage/pages:delete');
                let tableElement = window.document.querySelector('div.reportbuilder-report');

                // eslint-disable-next-line promise/no-nesting
                return deletePage(pageDelete.dataset.pageId)
                    .then(() => addToast(getString('pagedeleted', 'local_iomadcustompage')))
                    .then(() => {
                        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, tableElement);
                        return pendingPromise.resolve();
                    })
                    .catch(Notification.exception);
            }).catch(Notification.exception);
        }
    });
};
