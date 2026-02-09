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
 * Theme Space - JS code back to top button
 *
 * @module     theme_iomadmoon/backtotopbutton
 * @copyright  2023 Marcin Czaja
 * @copyright  based on code from theme_iomadmoon_campus by Kathrin Osswald.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification'], function($, str, Notification) {
    "use strict";

    // Remember if the back to top button is shown currently.
    let buttonShown = false;

    /**
     * Initializing.
     */
    function initBackToTop() {
        // Get the string backtotop from language file.
        let stringsPromise = str.get_string('backtotopbutton', 'theme_iomadmoon');

        // If the string has arrived, add backtotop button to DOM and add scroll and click handlers.
        $.when(stringsPromise).then(function(string) {
            // Add a fontawesome icon after the footer as the back to top button.
            $('#s-page-footer').after('<button id="back-to-top" ' +
                    'class="btn btn-icon icon-no-margin d-print-none"' +
                    'aria-label="' + string + '">' +
                    '<svg width="24" height="24" stroke-width="2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor"><path d="M6 20h12M12 16V4m0 0l3.5 3.5M12 4L8.5 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>');

            // This function fades the button in when the page is scrolled down or fades it out
            // if the user is at the top of the page again.
            // Please note that Boost in Moodle 4.0 does not scroll the window object / whole body tag anymore,
            // it scrolls the #page element instead.
            $('#page').on('scroll', function() {
                if ($('#page').scrollTop() > 1210) {
                    checkAndShow();
                    $('body').addClass('scrolled');
                } else {
                    checkAndHide();
                    $('body').removeClass('scrolled');
                }
            });

            // This function scrolls the page to top with a duration of 500ms.
            $('#back-to-top').on('click', function(event) {
                event.preventDefault();
                $('body, html').animate({scrollTop: 0}, 500);
                $('#back-to-top').blur();
            });

            return true;
        }).fail(Notification.exception);
    }

    /**
     * Helper function to handle the button visibility when the page is scrolling up.
     */
    function checkAndHide() {
        // Check if the button is still shown.
        if (buttonShown === true) {
            // Fade it out and remember the status in the end.
            // To be precise, the faceOut() function will be called multiple times as buttonShown is not set until the button is
            // really faded out. However, as soon as it is faded out, it won't be called until the button is shown again.
            $('#back-to-top').fadeOut(100, function() {
                buttonShown = false;
            });
        }
    }

    /**
     * Helper function to handle the button visibility when the page is scrolling down.
     */
    function checkAndShow() {
        // Check if the button is not yet shown.
        if (buttonShown === false) {
            // Fade it in and remember the status in the end.
            $('#back-to-top').fadeIn(300, function() {
                buttonShown = true;
            });
        }
    }

    return {
        init: function() {
            initBackToTop();
        }
    };
});
