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
 * This module provides a wrapper to encapsulate a lot of the common combinations of
 * user interaction we use in Moodle.
 *
 * @module     core/custom_interaction_events
 * @class      custom_interaction_events
 * @package    core
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.2
 */
define(['jquery', 'core/key_codes'], function($, keyCodes) {
    // The list of events provided by this module. Namespaced to avoid clashes.
    var events = {
        activate: 'cie:activate',
        keyboardActivate: 'cie:keyboardactivate',
        escape: 'cie:escape',
        down: 'cie:down',
        up: 'cie:up',
        home: 'cie:home',
        end: 'cie:end',
        next: 'cie:next',
        previous: 'cie:previous',
        asterix: 'cie:asterix',
        scrollLock: 'cie:scrollLock',
        scrollTop: 'cie:scrollTop',
        scrollBottom: 'cie:scrollBottom',
        ctrlPageUp: 'cie:ctrlPageUp',
        ctrlPageDown: 'cie:ctrlPageDown',
        enter: 'cie:enter',
    };

    /**
     * Check if the caller has asked for the given event type to be
     * registered.
     *
     * @method shouldAddEvent
     * @private
     * @param {string} eventType name of the event (see events above)
     * @param {array} include the list of events to be added
     * @return {bool} true if the event should be added, false otherwise.
     */
    var shouldAddEvent = function(eventType, include) {
        include = include || [];

        if (include.length && include.indexOf(eventType) !== -1) {
            return true;
        }

        return false;
    };

    /**
     * Check if any of the modifier keys have been pressed on the event.
     *
     * @method isModifierPressed
     * @private
     * @param {event} e jQuery event
     * @return {bool} true if shift, meta (command on Mac), alt or ctrl are pressed
     */
    var isModifierPressed = function(e) {
        return (e.shiftKey || e.metaKey || e.altKey || e.ctrlKey);
    };

    /**
     * Register a keyboard event that ignores modifier keys.
     *
     * @method addKeyboardEvent
     * @private
     * @param {object} element A jQuery object of the element to bind events to
     * @param {string} event The custom interaction event name
     * @param {int} keyCode The key code.
     */
    var addKeyboardEvent = function(element, event, keyCode) {
        element.off('keydown.' + event).on('keydown.' + event, function(e) {
            if (!isModifierPressed(e)) {
                if (e.keyCode == keyCode) {
                    $(e.target).trigger(event, [{originalEvent: e}]);
                }
            }
        });
    };

    /**
     * Trigger the activate event on the given element if it is clicked or the enter
     * or space key are pressed without a modifier key.
     *
     * @method addActivateListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addActivateListener = function(element) {
        element.off('click.cie.activate').on('click.cie.activate', function(e) {
            $(e.target).trigger(events.activate, [{originalEvent: e}]);
        });
        element.off('keydown.cie.activate').on('keydown.cie.activate', function(e) {
            if (!isModifierPressed(e)) {
                if (e.keyCode == keyCodes.enter || e.keyCode == keyCodes.space) {
                    $(e.target).trigger(events.activate, [{originalEvent: e}]);
                }
            }
        });
    };

    /**
     * Trigger the keyboard activate event on the given element if the enter
     * or space key are pressed without a modifier key.
     *
     * @method addKeyboardActivateListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addKeyboardActivateListener = function(element) {
        element.off('keydown.cie.keyboardactivate').on('keydown.cie.keyboardactivate', function(e) {
            if (!isModifierPressed(e)) {
                if (e.keyCode == keyCodes.enter || e.keyCode == keyCodes.space) {
                    $(e.target).trigger(events.keyboardActivate, [{originalEvent: e}]);
                }
            }
        });
    };

    /**
     * Trigger the escape event on the given element if the escape key is pressed
     * without a modifier key.
     *
     * @method addEscapeListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addEscapeListener = function(element) {
        addKeyboardEvent(element, events.escape, keyCodes.escape);
    };

    /**
     * Trigger the down event on the given element if the down arrow key is pressed
     * without a modifier key.
     *
     * @method addDownListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addDownListener = function(element) {
        addKeyboardEvent(element, events.down, keyCodes.arrowDown);
    };

    /**
     * Trigger the up event on the given element if the up arrow key is pressed
     * without a modifier key.
     *
     * @method addUpListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addUpListener = function(element) {
        addKeyboardEvent(element, events.up, keyCodes.arrowUp);
    };

    /**
     * Trigger the home event on the given element if the home key is pressed
     * without a modifier key.
     *
     * @method addHomeListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addHomeListener = function(element) {
        addKeyboardEvent(element, events.home, keyCodes.home);
    };

    /**
     * Trigger the end event on the given element if the end key is pressed
     * without a modifier key.
     *
     * @method addEndListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addEndListener = function(element) {
        addKeyboardEvent(element, events.end, keyCodes.end);
    };

    /**
     * Trigger the next event on the given element if the right arrow key is pressed
     * without a modifier key in LTR mode or left arrow key in RTL mode.
     *
     * @method addNextListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addNextListener = function(element) {
        // Left and right are flipped in RTL mode.
        var keyCode = $('html').attr('dir') == "rtl" ? keyCodes.arrowLeft : keyCodes.arrowRight;

        addKeyboardEvent(element, events.next, keyCode);
    };

    /**
     * Trigger the previous event on the given element if the left arrow key is pressed
     * without a modifier key in LTR mode or right arrow key in RTL mode.
     *
     * @method addPreviousListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addPreviousListener = function(element) {
        // Left and right are flipped in RTL mode.
        var keyCode = $('html').attr('dir') == "rtl" ? keyCodes.arrowRight : keyCodes.arrowLeft;

        addKeyboardEvent(element, events.previous, keyCode);
    };

    /**
     * Trigger the asterix event on the given element if the asterix key is pressed
     * without a modifier key.
     *
     * @method addAsterixListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addAsterixListener = function(element) {
        addKeyboardEvent(element, events.asterix, keyCodes.asterix);
    };


    /**
     * Trigger the scrollTop event on the given element if the user scrolls to
     * the top of the given element.
     *
     * @method addScrollTopListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addScrollTopListener = function(element) {
        element.off('scroll.cie.scrollTop').on('scroll.cie.scrollTop', function(e) {
            var scrollTop = element.scrollTop();
            if (scrollTop === 0) {
                element.trigger(events.scrollTop, [{originalEvent: e}]);
            }
        });
    };

    /**
     * Trigger the scrollBottom event on the given element if the user scrolls to
     * the bottom of the given element.
     *
     * @method addScrollBottomListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addScrollBottomListener = function(element) {
        element.off('scroll.cie.scrollBottom').on('scroll.cie.scrollBottom', function(e) {
            var scrollTop = element.scrollTop();
            var innerHeight = element.innerHeight();
            var scrollHeight = element[0].scrollHeight;

            if (scrollTop + innerHeight >= scrollHeight) {
                element.trigger(events.scrollBottom, [{originalEvent: e}]);
            }
        });
    };

    /**
     * Trigger the scrollLock event on the given element if the user scrolls to
     * the bottom or top of the given element.
     *
     * @method addScrollLockListener
     * @private
     * @param {jQuery} element jQuery object to add event listeners to
     */
    var addScrollLockListener = function(element) {
        // Lock mousewheel scrolling within the element to stop the annoying window scroll.
        element.off('DOMMouseScroll.cie.DOMMouseScrollLock mousewheel.cie.mousewheelLock')
            .on('DOMMouseScroll.cie.DOMMouseScrollLock mousewheel.cie.mousewheelLock', function(e) {
                var scrollTop = element.scrollTop();
                var scrollHeight = element[0].scrollHeight;
                var height = element.height();
                var delta = (e.type == 'DOMMouseScroll' ?
                    e.originalEvent.detail * -40 :
                    e.originalEvent.wheelDelta);
                var up = delta > 0;

                if (!up && -delta > scrollHeight - height - scrollTop) {
                    // Scrolling down past the bottom.
                    element.scrollTop(scrollHeight);
                    e.stopPropagation();
                    e.preventDefault();
                    e.returnValue = false;
                    // Fire the scroll lock event.
                    element.trigger(events.scrollLock, [{originalEvent: e}]);

                    return false;
                } else if (up && delta > scrollTop) {
                    // Scrolling up past the top.
                    element.scrollTop(0);
                    e.stopPropagation();
                    e.preventDefault();
                    e.returnValue = false;
                    // Fire the scroll lock event.
                    element.trigger(events.scrollLock, [{originalEvent: e}]);

                    return false;
                }

                return true;
            });
    };

    /**
     * Trigger the ctrlPageUp event on the given element if the user presses the
     * control and page up key.
     *
     * @method addCtrlPageUpListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addCtrlPageUpListener = function(element) {
        element.off('keydown.cie.ctrlpageup').on('keydown.cie.ctrlpageup', function(e) {
            if (e.ctrlKey) {
                if (e.keyCode == keyCodes.pageUp) {
                    $(e.target).trigger(events.ctrlPageUp, [{originalEvent: e}]);
                }
            }
        });
    };

    /**
     * Trigger the ctrlPageDown event on the given element if the user presses the
     * control and page down key.
     *
     * @method addCtrlPageDownListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addCtrlPageDownListener = function(element) {
        element.off('keydown.cie.ctrlpagedown').on('keydown.cie.ctrlpagedown', function(e) {
            if (e.ctrlKey) {
                if (e.keyCode == keyCodes.pageDown) {
                    $(e.target).trigger(events.ctrlPageDown, [{originalEvent: e}]);
                }
            }
        });
    };

    /**
     * Trigger the enter event on the given element if the enter key is pressed
     * without a modifier key.
     *
     * @method addEnterListener
     * @private
     * @param {object} element jQuery object to add event listeners to
     */
    var addEnterListener = function(element) {
        addKeyboardEvent(element, events.enter, keyCodes.enter);
    };

    /**
     * Get the list of events and their handlers.
     *
     * @method getHandlers
     * @private
     * @return {object} object key of event names and value of handler functions
     */
    var getHandlers = function() {
        var handlers = {};

        handlers[events.activate] = addActivateListener;
        handlers[events.keyboardActivate] = addKeyboardActivateListener;
        handlers[events.escape] = addEscapeListener;
        handlers[events.down] = addDownListener;
        handlers[events.up] = addUpListener;
        handlers[events.home] = addHomeListener;
        handlers[events.end] = addEndListener;
        handlers[events.next] = addNextListener;
        handlers[events.previous] = addPreviousListener;
        handlers[events.asterix] = addAsterixListener;
        handlers[events.scrollLock] = addScrollLockListener;
        handlers[events.scrollTop] = addScrollTopListener;
        handlers[events.scrollBottom] = addScrollBottomListener;
        handlers[events.ctrlPageUp] = addCtrlPageUpListener;
        handlers[events.ctrlPageDown] = addCtrlPageDownListener;
        handlers[events.enter] = addEnterListener;

        return handlers;
    };

    /**
     * Add all of the listeners on the given element for the requested events.
     *
     * @method define
     * @public
     * @param {object} element the DOM element to register event listeners on
     * @param {array} include the array of events to be triggered
     */
    var define = function(element, include) {
        element = $(element);
        include = include || [];

        if (!element.length || !include.length) {
            return;
        }

        $.each(getHandlers(), function(eventType, handler) {
            if (shouldAddEvent(eventType, include)) {
                handler(element);
            }
        });
    };

    return /** @module core/custom_interaction_events */ {
        define: define,
        events: events,
    };
});
