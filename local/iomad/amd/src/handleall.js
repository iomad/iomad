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
 * IOMAD report users checkbox change handler
 * @module    local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
    ],
    function (
        $
    ) {

        return {

            /**
             * Function to update individual checkbox values on
             * change of main checkbox value.
             *
             **/
            init: function () {

                // Handle checkbox change all.
                $(".checkbox").change(function () {
                    if (this.checked) {
                        if (this.classList.contains("enableallcertificates")) {
                            $(".enablecertificates").prop("checked", this.checked);
                        }
                        if (this.classList.contains("enableallentries")) {
                            $(".enableentries").prop("checked", this.checked);
                        }
                    } else {
                        if (this.classList.contains("enableallcertificates")) {
                            $(".enablecertificates").prop("checked", '');
                        }
                        if (this.classList.contains("enableallentries")) {
                            $(".enableentries").prop("checked", '');
                        }
                    }
                });
            }
        };
    });
