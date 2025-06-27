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
 * Page builder selectors
 *
 * @module      local_iomadcustompage/local/selectors
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Selectors for the Page builder subsystem
 *
 * @property {Object} regions
 * @property {String} regions.systemPage System page page region
 * @property {String} regions.filterButtonLabel Filters form toggle region
 * @property {String} regions.filtersForm Filters form page region
 */
const SELECTORS = {
    regions: {
        page: '[data-region="local_iomadcustompage/page"]',
        sidebarMenu: '[data-region="sidebar-menu"]',
        sidebarCard: '[data-region="sidebar-card"]',
        sidebarItem: '[data-region="sidebar-item"]',
        audiencesContainer: '[data-region="audiences"]',
        audienceFormContainer: '[data-region="audience-form-container"]',
        audienceCard: '[data-region="audience-card"]',
        audienceHeading: '[data-region="audience-heading"]',
        audienceForm: '[data-region="audience-form"]',
        audienceEmptyMessage: '[data-region=no-instances-message]',
        audienceDescription: '[data-region=audience-description]',
        audienceNotSavedLabel: '[data-region=audience-not-saved]',
        detailsFormContainer: '[data-region="details-form-container"]',
        settingsCardView: '[data-region="settings-cardview"]',
    },
    actions: {
        pageActionPopup: '[data-action="page-action-popup"]',
        pageCreate: '[data-action="page-create"]',
        pageEdit: '[data-action="page-edit"]',
        pageDelete: '[data-action="page-delete"]',
        sidebarSearch: '[data-action="sidebar-search"]',
        toggleEditPreview: '[data-action="toggle-edit-preview"]',
        audienceAdd: '[data-action="add-audience"]',
        audienceEdit: '[data-action="edit-audience"]',
        audienceDelete: '[data-action="delete-audience"]',
        toggleCardView: '[data-action="toggle-card"]',
    },
};

/**
 * Selector for given page
 *
 * @method forPage
 * @param {Number} pageId
 * @return {String}
 */
SELECTORS.forPage = pageId => `${SELECTORS.regions.page}[data-page-id="${pageId}"]`;

export default SELECTORS;
