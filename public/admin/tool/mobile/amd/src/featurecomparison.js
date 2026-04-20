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
 * Feature comparison toggle for tool_mobile subscription page.
 *
 * @copyright  2026 Daniel Urena
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import Notification from 'core/notification';

/**
 * Initialise a single feature comparison card.
 *
 * @param {HTMLElement} card
 */
const initCard = (card) => {
    if (!card) {
        return;
    }

    const rows = Array.from(
        card.querySelectorAll('.feature-comparison-rows .feature-comparison-row')
    );
    const toggle = card.querySelector('.feature-comparison-toggle');

    if (!rows.length || !toggle) {
        return;
    }

    const textSpan = toggle.querySelector('.feature-comparison-toggle-text');
    const icon = toggle.querySelector('.feature-comparison-toggle-icon');

    let expanded = false;

    const applyState = () => {
        rows.forEach((row) => {
            row.classList.toggle('d-none', !expanded);
        });

        if (textSpan) {
            // Fire and forget: handle resolution/rejection explicitly.
            void getString(expanded ? 'showless' : 'showmore')
                .then((str) => {
                    textSpan.textContent = str;
                    return null;
                })
                .catch((error) => {
                    Notification.exception(error);
                });
        }

        if (icon) {
            icon.classList.toggle('fa-chevron-down', !expanded);
            icon.classList.toggle('fa-chevron-up', expanded);
        }

        return null;
    };

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        expanded = !expanded;
        applyState();
    });

    applyState();
};

/**
 * Initialise all feature comparison cards.
 */
const init = () => {
    document.querySelectorAll('.feature-comparison-card')
        .forEach(initCard);
};

export default {
    init,
};
