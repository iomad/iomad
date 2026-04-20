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
 * Custom auto-complete adapter to load users from the assignment list_participants webservice.
 *
 * @module     mod_assign/participant_selector
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/templates'], function(ajax, templates) {


    return /** @alias module:mod_assign/participants_selector */ {

        // Public variables and functions.
        /**
         * Process the results returned from transport (convert to value + label)
         *
         * @method processResults
         * @param {String} selector
         * @param {Array} data
         * @return {Array}
         */
        processResults: function(selector, data) {
            return data;
        },

        /**
         * Fetch results based on the current query. This also renders each result from a template before returning them.
         *
         * @method transport
         * @param {String} selector Selector for the original select element
         * @param {String} query Current search string
         * @param {Function} success Success handler
         * @param {Function} failure Failure handler
         */
        transport: function(selector, query, success, failure) {
            const element = document.querySelector(selector);
            var assignmentid = element.getAttribute('data-assignmentid');
            var groupid = element.getAttribute('data-groupid');
            var filters = document.querySelectorAll('[data-region="configure-filters"] input[type="checkbox"]');
            var filterstrings = [];

            filters.forEach((e) => {
                let filterelement = document.querySelector(e);
                filterstrings[filterelement.getAttribute('name')] = filterelement.checked;
            });

            var marking = element.getAttribute('data-ismarking');

            ajax.call([{
                methodname: 'mod_assign_list_participants',
                args: {
                    assignid: assignmentid,
                    groupid: groupid,
                    filter: query,
                    limit: 30,
                    includeenrolments: false,
                    tablesort: true,
                    marking: marking,
                }
            }])[0].then(function(results) {
                var promises = [];
                var identityfields = document.querySelector('[data-showuseridentity]').dataset.showuseridentity.split(',');

                // We got the results, now we loop over them and render each one from a template.
                results.forEach((user) => {
                    var ctx = user,
                        identity = [],
                        show = true;

                    if (filterstrings.filter_submitted && !user.submitted) {
                        show = false;
                    }
                    if (filterstrings.filter_notsubmitted && user.submitted) {
                        show = false;
                    }
                    if (filterstrings.filter_requiregrading && !user.requiregrading) {
                        show = false;
                    }
                    if (filterstrings.filter_grantedextension && !user.grantedextension) {
                        show = false;
                    }
                    if (show) {
                        identityfields.forEach((k) => {
                            if (typeof user[k] !== 'undefined' && user[k] !== '') {
                                ctx.hasidentity = true;
                                identity.push(user[k]);
                            }
                        });
                        ctx.identity = identity.join(', ');
                        promises.push(templates.render('mod_assign/list_participant_user_summary', ctx).then(function(html) {
                            return {value: user.id, label: html};
                        }));
                    }
                });
                return Promise.all(promises);
            }).then(function(users) {
                success(users);
                return;
            }).catch(failure);
        }
    };
});
