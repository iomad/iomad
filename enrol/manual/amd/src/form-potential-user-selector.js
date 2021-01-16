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
 * Potential user selector module.
 *
 * @module     enrol_manual/form-potential-user-selector
 * @class      form-potential-user-selector
 * @package    enrol_manual
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {

    return /** @alias module:enrol_manual/form-potential-user-selector */ {

        processResults: function(selector, results) {
            var users = [];
            $.each(results, function(index, user) {
                users.push({
                    value: user.id,
                    label: user._label
                });
            });
            return users;
        },

        transport: function(selector, query, success, failure) {
            var promise;
            var courseid = $(selector).attr('courseid');
            if (typeof courseid === "undefined") {
                courseid = '1';
            }
            var enrolid = $(selector).attr('enrolid');
            if (typeof enrolid === "undefined") {
                enrolid = '';
            }

            promise = Ajax.call([{
                methodname: 'core_enrol_get_potential_users',
                args: {
                    courseid: courseid,
                    enrolid: enrolid,
                    search: query,
                    searchanywhere: true,
                    page: 0,
                    perpage: 30
                }
            }]);

            promise[0].then(function(results) {
                var promises = [],
                    i = 0;

                // Render the label.
                $.each(results, function(index, user) {
                    var ctx = user,
                        identity = [];
                    $.each(['idnumber', 'email', 'phone1', 'phone2', 'department', 'institution'], function(i, k) {
                        if (typeof user[k] !== 'undefined' && user[k] !== '') {
                            ctx.hasidentity = true;
                            identity.push(user[k]);
                        }
                    });
                    ctx.identity = identity.join(', ');
                    promises.push(Templates.render('enrol_manual/form-user-selector-suggestion', ctx));
                });

                // Apply the label to the results.
                return $.when.apply($.when, promises).then(function() {
                    var args = arguments;
                    $.each(results, function(index, user) {
                        user._label = args[i];
                        i++;
                    });
                    success(results);
                    return;
                });

            }).fail(failure);
        }

    };

});
