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
 * IOMAD Custom pages sidebar component
 *
 * @module      local_iomadcustompage/sidebar
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from 'core/pending';
import {debounce} from 'core/utils';
import * as pageSelectors from 'local_iomadcustompage/local/selectors';

const DEBOUNCE_TIMER = 250;

const CLASSES = {
    EXPANDED: 'show',
    COLLAPSED: 'collapsed',
    HIDE: 'd-none',
};

/**
 * Initialise module
 *
 * @param {Event} event
 * @param {Element} sidebarMenu
 */
const sidebarCardFilter = (event, sidebarMenu) => {
    const pendingPromise = new Pending('local_iomadcustompage/sidebar:cardFilter');

    const sidebarCards = sidebarMenu.querySelectorAll(pageSelectors.regions.sidebarCard);
    const sidebarItems = sidebarMenu.querySelectorAll(pageSelectors.regions.sidebarItem);
    const searchTerm = event.target.value.toLowerCase();

    // Toggle items according to match against search term.
    sidebarItems.forEach(item => {
        const itemContent = item.textContent.toLowerCase();
        item.classList.toggle(CLASSES.HIDE, !itemContent.includes(searchTerm));
    });

    // Toggle cards according to whether they have any visible items.
    sidebarCards.forEach(card => {
        const visibleItems = card.querySelectorAll(`${pageSelectors.regions.sidebarItem}:not(.${CLASSES.HIDE})`);
        card.classList.toggle(CLASSES.HIDE, !visibleItems.length);

        expandCard(card);
    });

    pendingPromise.resolve();
};

/**
 * Show a collapsed card.
 * This function simulates the behaviour of JQuery show method on a collapsible element.
 *
 * @param {Element} card
 */
const expandCard = (card) => {
    let cardButton = card.querySelector('[data-toggle="collapse"]');
    if (cardButton.classList.contains(CLASSES.COLLAPSED)) {
        cardButton.classList.remove(CLASSES.COLLAPSED);
        cardButton.setAttribute('aria-expanded', "true");
        let cardContent = card.querySelector(cardButton.dataset.target);
        cardContent.classList.add(CLASSES.EXPANDED);
    }
};

/**
 * Initialise module
 *
 * @param {string} selectorId
 */
export const init = (selectorId) => {
    const sidebarMenu = document.querySelector(selectorId + pageSelectors.regions.sidebarMenu);
    const sidebarSearch = sidebarMenu.querySelector(pageSelectors.actions.sidebarSearch);

    // Debounce the event listener to allow the user to finish typing.
    const sidebarSearchDebounce = debounce(sidebarCardFilter, DEBOUNCE_TIMER);
    sidebarSearch.addEventListener('keyup', event => {
        const pendingPromise = new Pending('local_iomadcustompage/sidebar:keyup');

        sidebarSearchDebounce(event, sidebarMenu);
        setTimeout(() => {
            pendingPromise.resolve();
        }, DEBOUNCE_TIMER);
    });
};
