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
 * Contain the logic for a drawer.
 *
 * @package    theme_boost
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/custom_interaction_events', 'core/log'],
     function($, CustomEvents, Log) {

    var SELECTORS = {
        TOGGLE_REGION: '[data-region="drawer-toggle"]',
        TOGGLE_ACTION: '[data-action="toggle-drawer"]',
        TOGGLE_TARGET: 'aria-controls',
        TOGGLE_SIDE: 'left',
        BODY: 'body',
        SECTION: '.list-group-item[href*="#section-"]'
    };

    /**
     * Constructor for the Drawer.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var Drawer = function() {

        if (!$(SELECTORS.TOGGLE_REGION).length) {
            Log.debug('Page is missing a drawer region');
        }
        if (!$(SELECTORS.TOGGLE_ACTION).length) {
            Log.debug('Page is missing a drawer toggle link');
        }
        $(SELECTORS.TOGGLE_REGION).each(function(index, ele) {
            var trigger = $(ele).find(SELECTORS.TOGGLE_ACTION);
            var drawerid = trigger.attr('aria-controls');
            var drawer = $(document.getElementById(drawerid));
            var hidden = trigger.attr('aria-expanded') == 'false';
            var side = trigger.attr('data-side');
            var body = $(SELECTORS.BODY);

            drawer.on('mousewheel DOMMouseScroll', this.preventPageScroll);

            if (!hidden) {
                body.addClass('drawer-open-' + side);
                trigger.attr('aria-expanded', 'true');
            } else {
                trigger.attr('aria-expanded', 'false');
            }
        }.bind(this));

        this.registerEventListeners();
        var small = $(document).width() < 768;
        if (small) {
            this.closeAll();
        }
    };

    Drawer.prototype.closeAll = function() {
        $(SELECTORS.TOGGLE_REGION).each(function(index, ele) {
            var trigger = $(ele).find(SELECTORS.TOGGLE_ACTION);
            var side = trigger.attr('data-side');
            var body = $(SELECTORS.BODY);
            var drawerid = trigger.attr('aria-controls');
            var drawer = $(document.getElementById(drawerid));
            var preference = trigger.attr('data-preference');

            trigger.attr('aria-expanded', 'false');
            body.removeClass('drawer-open-' + side);
            drawer.attr('aria-hidden', 'true');
            drawer.addClass('closed');
            M.util.set_user_preference(preference, 'false');
        });
    };

    /**
     * Open / close the blocks drawer.
     *
     * @method toggleDrawer
     * @param {Event} e
     */
    Drawer.prototype.toggleDrawer = function(e) {
        var trigger = $(e.target).closest('[data-action=toggle-drawer]');
        var drawerid = trigger.attr('aria-controls');
        var drawer = $(document.getElementById(drawerid));
        var body = $(SELECTORS.BODY);
        var side = trigger.attr('data-side');
        var preference = trigger.attr('data-preference');

        body.addClass('drawer-ease');
        var open = trigger.attr('aria-expanded') == 'true';
        if (!open) {
            // Open.
            trigger.attr('aria-expanded', 'true');
            drawer.attr('aria-hidden', 'false');
            drawer.focus();
            body.addClass('drawer-open-' + side);
            drawer.removeClass('closed');
            M.util.set_user_preference(preference, 'true');
        } else {
            // Close.
            body.removeClass('drawer-open-' + side);
            trigger.attr('aria-expanded', 'false');
            drawer.attr('aria-hidden', 'true');
            drawer.addClass('closed');
            M.util.set_user_preference(preference, 'false');
        }
    };

    /**
     * Prevent the page from scrolling when the drawer is at max scroll.
     *
     * @method preventPageScroll
     * @param  {Event} e
     */
    Drawer.prototype.preventPageScroll = function(e) {
        var delta = e.wheelDelta || (e.originalEvent && e.originalEvent.wheelDelta) || -e.originalEvent.detail,
            bottomOverflow = (this.scrollTop + $(this).outerHeight() - this.scrollHeight) >= 0,
            topOverflow = this.scrollTop <= 0;

        if ((delta < 0 && bottomOverflow) || (delta > 0 && topOverflow)) {
            e.preventDefault();
        }
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    Drawer.prototype.registerEventListeners = function() {

        $(SELECTORS.TOGGLE_ACTION).each(function(index, element) {
            CustomEvents.define($(element), [CustomEvents.events.activate]);
            $(element).on(CustomEvents.events.activate, function(e, data) {
                this.toggleDrawer(data.originalEvent);
                data.originalEvent.preventDefault();
            }.bind(this));
        }.bind(this));

        $(SELECTORS.SECTION).click(function() {
            var small = $(document).width() < 768;
            if (small) {
                this.closeAll();
            }
        }.bind(this));
    };

    return {
        'init': function() {
            return new Drawer();
        }
    };
});
