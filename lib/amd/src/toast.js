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
 * A system for displaying small snackbar notifications to users which disappear shortly after they are shown.
 *
 * @module     core/toast
 * @package    core
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Templates from 'core/templates';
import Notification from 'core/notification';
import Pending from 'core/pending';

/**
 * Add a new region to place toasts in, taking in a parent element.
 *
 * @param {Element} parent
 */
export const addToastRegion = async(parent) => {
    const pendingPromise = new Pending('addToastRegion');

    try {
        const {html, js} = await Templates.renderForPromise('core/local/toast/wrapper', {});
        Templates.prependNodeContents(parent, html, js);
    } catch (e) {
        Notification.exception(e);
    }

    pendingPromise.resolve();
};

/**
 * Add a new toast or snackbar notification to the page.
 *
 * @param {String} message
 * @param {Object} configuration
 * @param {String} [configuration.title]
 * @param {String} [configuration.subtitle]
 * @param {Bool} [configuration.autohide=true]
 * @param {Number} [configuration.delay=4000]
 */
export const add = async(message, configuration) => {
    const pendingPromise = new Pending('addToastRegion');
    configuration = {
        closeButton: false,
        autohide: true,
        delay: 4000,
        ...configuration,
    };

    const templateName = `core/local/toast/message`;
    try {
        const targetNode = await getTargetNode();
        const {html, js} = await Templates.renderForPromise(templateName, {
            message,
            ...configuration
        });
        Templates.prependNodeContents(targetNode, html, js);
    } catch (e) {
        Notification.exception(e);
    }

    pendingPromise.resolve();
};

const getTargetNode = async() => {
    const regions = document.querySelectorAll('.toast-wrapper');

    if (regions.length) {
        return regions[regions.length - 1];
    }

    await addToastRegion(document.body, 'fixed-bottom');
    return getTargetNode();
};
