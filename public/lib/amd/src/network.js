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
 * Poll the server to keep the session alive.
 *
 * @module     core/network
 * @copyright  2019 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/ajax',
    'core/config',
    'core/notification',
    'core/str',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/network_events',
],
function(
    $,
    Ajax,
    Config,
    Notification,
    Str,
    SaveCancelModal,
    ModalEvents,
    NetworkEvents,
) {

    var started = false;
    var warningDisplayed = false;
    var keepAliveFrequency = 0;
    var requestTimeout = 0;
    var keepAliveMessage = false;
    var sessionTimeout = false;
    // 1/10 of session timeout, max of 10 minutes.
    var checkFrequency = Math.min((Config.sessiontimeout / 10), 600) * 1000;
    // Check if sessiontimeoutwarning is set or double the checkFrequency.
    var warningLimit = (Config.sessiontimeoutwarning > 0) ? (Config.sessiontimeoutwarning * 1000) : (checkFrequency * 2);
    // First wait is minimum of remaining time or half of the session timeout.
    var firstWait = (Config.sessiontimeoutwarning > 0) ?
        Math.min((Config.sessiontimeout - Config.sessiontimeoutwarning) * 1000, checkFrequency * 5) : checkFrequency * 5;
    // Modal to display when the connection is unstable.
    var alertModal = null;
    // URL to redirect to if network is unstable.
    var redirectUrl = null;
    // Number of times we have tried to touch the session after a network request failure.
    var connectionRetries = 0;
    /**
     * The session time has expired - we can't extend it now.
     * @param {Modal} modal
     */
    var timeoutSessionExpired = function(modal) {
        sessionTimeout = true;
        warningDisplayed = false;
        closeModal(modal);
        displaySessionExpired();
    };

    /**
     * Close modal - this relies on modal object passed from Notification.confirm.
     *
     * @param {Modal} modal
     */
    var closeModal = function(modal) {
        modal.destroy();
    };

    /**
     * The session time has expired - we can't extend it now.
     * @return {Promise}
     */
    var displaySessionExpired = function() {
        // Check again if its already extended before displaying session expired popup in case multiple tabs are open.
        var request = {
            methodname: 'core_session_time_remaining',
            args: { }
        };

        return Ajax.call([request], true, true, true)[0].then(function(args) {
            if (args.timeremaining * 1000 > warningLimit) {
                return false;
            } else {
                return Str.get_strings([
                    {key: 'sessionexpired', component: 'error'},
                    {key: 'sessionerroruser', component: 'error'},
                    {key: 'loginagain', component: 'moodle'},
                    {key: 'cancel', component: 'moodle'}
                ]).then(function(strings) {
                    Notification.confirm(
                        strings[0], // Title.
                        strings[1], // Message.
                        strings[2], // Login Again.
                        strings[3], // Cancel.
                        function() {
                            location.reload();
                            return true;
                        }
                    );
                    return true;
                }).catch(Notification.exception);
            }
        });
    };

    /**
     * Ping the server to keep the session alive.
     *
     * @return {Promise}
     */
    var touchSession = function() {
        var request = {
            methodname: 'core_session_touch',
            args: { }
        };

        if (sessionTimeout) {
            // We timed out before we extended the session.
            return displaySessionExpired();
        } else {
            return Ajax.call([request], true, true, false, requestTimeout)[0].then(function() {
                window.dispatchEvent(new CustomEvent(NetworkEvents.sessionTouched));
                return true;
            }).catch(function() {
                window.dispatchEvent(new CustomEvent(NetworkEvents.unstableConnection));
            });
        }
    };

    /**
     * Ask the server how much time is remaining in this session and
     * show confirm/cancel notifications if the session is about to run out.
     *
     * @return {Promise}
     */
    var checkSession = function() {
        var request = {
            methodname: 'core_session_time_remaining',
            args: { }
        };
        sessionTimeout = false;
        return Ajax.call([request], true, true, true)[0].then(function(args) {
            if (args.userid <= 0) {
                return false;
            }
            if (args.timeremaining <= 0) {
                return displaySessionExpired();
            } else if (args.timeremaining * 1000 <= warningLimit && !warningDisplayed) {
                warningDisplayed = true;
                Str.get_strings([
                    {key: 'norecentactivity', component: 'moodle'},
                    {key: 'sessiontimeoutsoon', component: 'moodle'},
                    {key: 'extendsession', component: 'moodle'},
                    {key: 'cancel', component: 'moodle'}
                ]).then(function(strings) {
                     return Notification.confirm(
                        strings[0], // Title.
                        strings[1], // Message.
                        strings[2], // Extend session.
                        strings[3], // Cancel.
                        function() {
                            touchSession();
                            warningDisplayed = false;
                            // First wait is minimum of remaining time or half of the session timeout.
                            setTimeout(checkSession, firstWait);
                            return true;
                        },
                        function() {
                            // User has cancelled notification.
                            setTimeout(checkSession, checkFrequency);
                        }
                    );
                }).then(modal => {
                    // If we don't extend the session before the timeout - warn.
                    setTimeout(timeoutSessionExpired, args.timeremaining * 1000, modal);
                    return;
                }).catch(Notification.exception);
            } else {
                setTimeout(checkSession, checkFrequency);
            }
            return true;
        });
        // We do not catch the fails from the above ajax call because they will fail when
        // we are not logged in - we don't need to take any action then.
    };

    /**
     * Start calling a function to check if the session is still alive.
     */
    var start = function() {
        if (keepAliveFrequency > 0) {
            setTimeout(touchSession, keepAliveFrequency);
        } else {
            // First wait is minimum of remaining time or half of the session timeout.
            setTimeout(checkSession, firstWait);
        }
    };

    /**
     * Are we in an iframe and the parent page is from the same Moodle site?
     *
     * @return {boolean} true if we are in an iframe in a page from this Moodle site.
     */
    const isMoodleIframe = function() {
        if (window.parent === window) {
            // Not in an iframe.
            return false;
        }

        // We are in an iframe. Is the parent from the same Moodle site?
        let parentUrl;
        try {
            parentUrl = window.parent.location.href;
        } catch (e) {
            // If we cannot access the URL of the parent page, it must be another site.
            return false;
        }

        return parentUrl.startsWith(M.cfg.wwwroot);
    };

    /**
     * Don't allow more than one of these polling loops in a single page.
     */
    var init = function() {
        // We only allow one concurrent instance of this checker.
        if (started) {
            return;
        }
        started = true;

        if (isMoodleIframe()) {
            window.console.log('Not starting Moodle session timeout warning in this iframe.');
            return;
        }

        window.console.log('Starting Moodle session timeout warning.');

        start();
    };

    /**
     * Leave the page, either by redirecting or reloading.
     */
    var leavePage = function() {
        if (typeof redirectUrl === 'string') {
            window.location.replace(redirectUrl);
            return;
        }
        window.location.reload();
    };

    /**
     * When the network connection is unstable show the user an alert.
     * If the unstable connection persists, force the user to exit the
     * current page.
     */
    var handleUnstableConnection = function() {
        // Show the alert modal warning the user that their
        // connection is unstable but allow them to continue
        // interacting with the page if they choose.
        if (connectionRetries < 1) {
            alertModal.show();
            setTimeout(touchSession, keepAliveFrequency);
            connectionRetries++;
            return;
        }

        // If we have failed to touch the session twice in a row,
        // show the alert modal (if not already visible) but remove
        // any option for the user to continue on this page.
        if (alertModal !== null) {
            alertModal.getRoot().find('[data-action="cancel"]').hide();
            alertModal.show().then(function() {
                alertModal.getRoot().on(ModalEvents.hidden, leavePage);
                return true;
            }).catch(Notification.exception);
        }
    };

    /**
     * When the session is touched, set a timeout to touch it again
     * and reset the number of failed connection retries.
     */
    var handleSessionTouched = function() {
        if (keepAliveFrequency > 0) {
            setTimeout(touchSession, keepAliveFrequency);
            connectionRetries = 0;
        }
    };

    /**
     * Start polling with more specific values for the frequency, timeout and message.
     *
     * @param {number} freq How ofter to poll the server.
     * @param {number} timeout The time to wait for each request to the server.
     * @param {string} identifier The string identifier for the message to show if session is going to time out.
     * @param {string} component The string component for the message to show if session is going to time out.
     * @param {string|null} redirect A url to redirect to in case of a lost connection to the server.
     */
    var keepalive = async function(freq, timeout, identifier, component, redirect = null) {
        // We only allow one concurrent instance of this checker.
        if (started) {
            window.console.warn('Ignoring session keep-alive. The core/network module was already initialised.');
            return;
        }
        started = true;

        if (isMoodleIframe()) {
            window.console.warn('Ignoring session keep-alive in this iframe inside another Moodle page.');
            return;
        }

        window.console.log('Starting Moodle session keep-alive.');

        keepAliveFrequency = freq * 1000;
        keepAliveMessage = await Str.get_string(identifier, component);
        requestTimeout = timeout * 1000;
        redirectUrl = redirect;

        var strings = await Str.get_strings([
            {key: 'unstablenetwork', component: 'mod_scorm'},
            {key: 'leavepage', component: 'mod_scorm'},
            {key: 'refresh', component: 'moodle'},
        ]);

        alertModal = await SaveCancelModal.create({
            title: strings[0],
            body: keepAliveMessage,
            buttons: {
                save: typeof redirectUrl === 'string' ? strings[1] : strings[2],
            },
            removeOnClose: false,
            show: false
        });

        // The save button is renamed and will redirect the user or
        // reload the page when clicked.
        alertModal.getRoot().on(ModalEvents.save, leavePage);

        window.addEventListener(NetworkEvents.sessionTouched, handleSessionTouched);
        window.addEventListener(NetworkEvents.unstableConnection, handleUnstableConnection);

        start();
    };

    return {
        keepalive: keepalive,
        init: init
    };
});
