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
 * Theme Boost Union - JS code which shows all fontawesome icons in a popover.
 *
 * @module     theme_boost_union/fontawesome-popover
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @copyright  based on code from theme_boost\footer-popover by Bas Brands.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'theme_boost/popover', 'core/fragment'], function($, popover, Fragment) {

    const SELECTORS = {
        PICKERCONTAINER: '.fontawesome-iconpicker-popover',
        PICKERCONTENT: '[data-region="icons-list"]',
    };

    var contextID;

    let pickerIsShown = false;

    /**
     * Get the icon list for popover.
     *
     * @returns {String} HTML string
     * @private
     */
    const getIconList = () => {
        return Fragment.loadFragment('local_designer', 'icons_list', contextID, {});
    };

    /**
     * Filter the icons in the list with values which the user entered in the search input.
     * Given input will contain the text in both aria-value and aria-label.
     * Ex. "core:t\document" is aria-value and "fa-document" is aria-label.
     *
     * @param {Element} target
     */
    const filterIcons = (target) => {
        var filter = target.value.toLowerCase();
        target.parentNode.previousElementSibling.value = filter || 0; // Set the value to the select box.
        var ul = document.querySelector('.fontawesome-iconpicker-popover ul.fontawesome-icon-suggestions');
        if (ul === undefined || ul === null) {
            return;
        }
        var li = ul.querySelectorAll('li');

        for (var i = 0; i < li.length; i++) {
            var value = li[i].getAttribute('aria-value');
            var label = li[i].getAttribute('aria-label');
            if (!value.toLowerCase().includes(filter) && !label.toLowerCase().includes(filter)) {
                li[i].style.display = "none";
            } else {
                li[i].style.display = "inline-block";
            }
        }
    };

    /**
     * Creates input element and append the element into the target element's parent node.
     * User is able to search icons using this input field.
     *
     * @param {HTMLElement} target Element Selector.
     */
    const createElements = (target) => {

        var input = document.createElement('input');
        input.setAttribute('type', 'text');
        input.classList.add('fontawesome-autocomplete');
        input.classList.add('form-control');
        input.setAttribute('name', 'iconsearch');

        if (target.value != '') {
            input.value = target.querySelector('option[selected]') !== null
                ? target.querySelector('option[selected]').text : '';
        }

        var wrapper = document.createElement('div');
        wrapper.classList.add("fontawesome-picker-container");
        wrapper.append(input);

        target.style.display = 'none';
        target.parentNode.append(wrapper);
    };

    /**
     * Update the target with fontawesome iconpicker.
     *
     * Create picker input field for search icons insert to DOM, fetch the icons list and setup the popover with icons content.
     * Display the popover when the icon search input field is focused or clicked. This way user can view the list of icons and
     * search icons. When the icon is selected, same icon in the select element will be selected.
     *
     * @param {String} targetSelector Element Selector.
     */
    const iconPicker = (targetSelector) => {

        var targets = document.querySelectorAll(targetSelector);
        if (targets === undefined || targets.length === 0) {
            return;
        }

        // Create input element and insert for search icons and hide the current target select box.
        targets.forEach((target) => createElements(target));

        // Input element for search icons, appended in createElements method.
        const pickerInput = 'input.fontawesome-autocomplete';
        const pickerInputElement = document.querySelector(pickerInput);

        // Check the search input created and inserted in DOM.
        if (pickerInputElement === undefined || pickerInputElement === null) {
            setTimeout(() => iconPicker(targetSelector), 1000);
            return;
        }

        // Fetch the icons list and setup popover with icons list.
        getIconList().then(function(html) {

            $(pickerInput).popover({
                content: html,
                html: true,
                placement: 'bottom',
                customClass: 'fontawesome-picker',
                trigger: 'click'
            });
            return;
        }).catch();

        document.addEventListener('click', e => {
            if (pickerIsShown && !e.target.closest(SELECTORS.PICKERCONTAINER)) {
                $(pickerInput).popover('hide');
            }
        }, true);

        document.addEventListener('keydown', e => {
            if (pickerIsShown && e.key === 'Escape') {
                $(pickerInput).popover('hide');
                e.currentTarget.focus();
            }
        });
        // Hide the popover, focus on other elements.
        document.addEventListener('focus', e => {
            if (pickerIsShown && !e.target.closest(SELECTORS.PICKERCONTAINER)) {
                $(pickerInput).popover('hide');
            }
        },
        true);

        $(pickerInput).on('shown.bs.popover', (e) => {
            pickerIsShown = true;
            // Add class to selected icon, helps to differentiate.
            if (e.currentTarget.value != '') {
                var iconSuggestion = document.querySelector('.fontawesome-iconpicker-popover ul.fontawesome-icon-suggestions');
                if (iconSuggestion.querySelector('li[aria-label="' + e.currentTarget.value + '"]') !== null) {
                    // Remove selected class.
                    iconSuggestion.querySelectorAll('li').forEach((li) =>
                            li.classList.remove('selected'));
                    // Assign selected class for new.
                    iconSuggestion.querySelector('li[aria-label="' + e.currentTarget.value + '"]').classList.add('selected');
                }
            }

            // Event observer when the popover is inserted in DOM, create event listner for each icon in icons list.
            // Icon is clicked, set the icon aria-value as value for select box.
            // Set the icon label to value of autocomplete picker.
            var inputTarget = e.currentTarget;
            var ul = document.querySelector('.fontawesome-iconpicker-popover ul.fontawesome-icon-suggestions');
            ul.querySelectorAll('li').forEach((li) => {
                li.addEventListener('click', (e) => {
                    var target = e.target.closest('li');
                    var value = target.getAttribute('aria-value');
                    var label = target.getAttribute('aria-label');
                    inputTarget.value = label;
                    inputTarget.parentNode.previousElementSibling.value = value || 0;
                    $(pickerInput).popover('hide');
                });
            });
        });

        $(pickerInput).on('hide.bs.popover', () => {
            pickerIsShown = false;
        });

        // Filter icons  on search using input element.
        document.querySelectorAll(pickerInput).forEach((select) => {
            select.addEventListener('keyup', function(e) {
                filterIcons(e.target);
            });
        });

    };

    return {
        init: (target, contextid) => {
            contextID = contextid;
            iconPicker(target);
        }

    };
});
