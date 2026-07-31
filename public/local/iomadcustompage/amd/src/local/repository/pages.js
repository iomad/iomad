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
 * Module to handle report AJAX requests
 *
 * @module      local_iomadcustompage/local/repository/reports
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Delete given page
 *
 * @param {Number} pageId
 * @return {Promise}
 */
export const deletePage = pageId => {
    const request = {
        methodname: 'local_iomadcustompage_page_delete',
        args: {pageid: pageId}
    };

    return Ajax.call([request])[0];
};

/**
 * Move page up in sort order
 *
 * @param {Number} pageId
 * @return {Promise}
 */
export const movePageUp = pageId => {
    const request = {
        methodname: 'local_iomadcustompage_page_move_up',
        args: {pageid: pageId}
    };

    return Ajax.call([request])[0];
};

/**
 * Move page down in sort order
 *
 * @param {Number} pageId
 * @return {Promise}
 */
export const movePageDown = pageId => {
    const request = {
        methodname: 'local_iomadcustompage_page_move_down',
        args: {pageid: pageId}
    };

    return Ajax.call([request])[0];
};

/**
 * Update sort order of pages
 *
 * @param {Array} pageIds Array of page IDs in desired order
 * @param {Number} parentId Parent page ID (0 for root pages)
 * @return {Promise}
 */
export const updateSortOrder = (pageIds, parentId = 0) => {
    const request = {
        methodname: 'local_iomadcustompage_page_update_sort_order',
        args: {
            pageids: pageIds,
            parentid: parentId
        }
    };

    return Ajax.call([request])[0];
};
