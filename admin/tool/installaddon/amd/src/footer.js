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
 * Activity chooser footer handlers for tool_installaddon.
 *
 * @module     tool_installaddon/footer
 * @copyright  2026 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import selectors from 'core_course/local/activitychooser/selectors';

/**
 * Handle clicks in the chooser footer when installaddon is the active footer plugin.
 *
 * @param {Event} e The event being triggered
 * @param {Object} footerData The footer data generated for the chooser
 * @param {Object} modal The chooser modal
 */
export const footerClickListener = (e, footerData, modal) => {
    const closeOption = e.target.closest(selectors.actions.closeOption);

    if (!closeOption) {
        return;
    }

    const moduleName = closeOption.dataset.modname;

    if (!moduleName) {
        return;
    }

    const carousel = $(modal.getBody()[0].querySelector(selectors.regions.carousel));

    // Trigger the transition between 'pages'.
    carousel.carousel('prev');
    modal.setFooter(footerData.customfootertemplate);
    carousel.one('slid.bs.carousel', () => {
        const allModules = modal.getBody()[0].querySelector(selectors.regions.modules);
        const caller = allModules.querySelector(selectors.regions.getModuleSelector(moduleName));
        if (caller) {
            caller.focus();
        }
    });
};
