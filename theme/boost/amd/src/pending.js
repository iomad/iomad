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
 * Add Pending JS checks to stock Bootstrap transitions.
 *
 * @module     theme_boost/pending
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import jQuery from 'jquery';
const moduleTransitions = {
    alert: [
        // Alert.
        {
            start: 'close',
            end: 'closed',
        },
    ],

    carousel: [
        {
            start: 'slide',
            end: 'slid',
        },
    ],

    collapse: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    dropdown: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    modal: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    popover: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    tab: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    toast: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],

    tooltip: [
        {
            start: 'hide',
            end: 'hidden',
        },
        {
            start: 'show',
            end: 'shown',
        },
    ],
};

export default () => {
    Object.entries(moduleTransitions).forEach(([key, pairs]) => {
        pairs.forEach(pair => {
            const eventStart = `${pair.start}.bs.${key}`;
            const eventEnd = `${pair.end}.bs.${key}`;
            jQuery(document.body).on(eventStart, e => {
                M.util.js_pending(eventEnd);
                jQuery(e.target).one(eventEnd, () => {
                    M.util.js_complete(eventEnd);
                });
            });

        });
    });
};
