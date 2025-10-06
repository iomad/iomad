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
 * @module    local_learningpath
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/config', 'core/ajax', 'core/notification', 'core/str'], function($, mdlcfg, ajax, notification, str) {

    return {

        /**
         * Function to manage actions which can be performed on the
         * lists of courses.
         *
         **/
        init: function() {

            // Enable Bootstrap tooltips
            require(['theme_boost/loader']);
            require(['theme_boost/tooltip'], function() {
                $('[data-toggle="tooltip"]').tooltip();
            });

            /**
             * Function to add the correct eye icon to the course listing.
             *
             * @param {node} icon The icon identifier
             * @param {int} state The binary state for the icon
             *
             **/
            function _redraw(icon, state) {
                icon.removeClass('fa-eye fa-eye-slash');
                if (state == 1) {
                    icon.addClass('fa-eye');
                } else {
                    icon.addClass('fa-eye-slash');
                }
            }

            /**
             * Function to handle hiding an making active courses.
             *
             **/
            $('.lp_active').click(function() {
                var icon = $(this).find('i');
                var id = $(this).data('id');
                var state = $(this).data('state');

                // flip current state
                if (state == 0) {
                    state = 1;
                } else {
                    state = 0;
                }
                $(this).data('state', state);

                // call the web service
                ajax.call([{
                    methodname: 'local_iomad_learningpath_activate',
                    args: { pathid: id, state: state },
                    done: _redraw(icon, state),
                    fail: notification.exception,
                }]);

                // false stops the normal link behaviour!
                return false;
            });

            // Handle delete button
            $('.lp_delete').click(function() {
                var id = $(this).data('id');
                str.get_strings([
                    {key: 'confirm', component: 'local_iomad_learningpath'},
                    {key: 'confirmdelete', component: 'local_iomad_learningpath'},
                    {key: 'yes'},
                    {key: 'no'}
                ]).done(function(s) {
                    notification.confirm(s[0], s[1], s[2], s[3], function() {
                        ajax.call([{
                            methodname: 'local_iomad_learningpath_deletepath',
                            args: { pathid: id },
                            done: function() {
                                location.reload();
                            },
                            fail: notification.exception,
                        }]);
                    });
                });

                // False stops normal link behaviour!!
                return false;
            });

            // Handle copy button
            $('.lp_copy').click(function() {
                var id = $(this).data('id');
                str.get_strings([
                    {key: 'confirm', component: 'local_iomad_learningpath'},
                    {key: 'confirmcopy', component: 'local_iomad_learningpath'},
                    {key: 'yes'},
                    {key: 'no'}
                ]).done(function(s) {
                    notification.confirm(s[0], s[1], s[2], s[3], function() {
                        ajax.call([{
                            methodname: 'local_iomad_learningpath_copypath',
                            args: { pathid: id },
                            done: function() {
                                location.reload();
                            },
                            fail: notification.exception,
                        }]);
                    });
                });

                // False stops normal link behaviour!!
                return false;
            });
        }
    };
});
