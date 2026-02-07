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
 * @module    local_email
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'],
    function($, ajax, notification) {

    return {
        init: function(companyid, page, perpage, lang) {

            /**
             * Function which handles the enable/disable all sliders.
             */
            $(".checkbox").change(function() {
                var inputElems = document.getElementsByTagName("input");
                var template = this.value;
                var checked = this.checked;
                ajax.call([{
                    methodname : 'local_iomad_restrict_email_template',
                    args : {
                        template : template,
                        value : checked,
                        companyid : companyid,
                        page: page,
                        perpage: perpage,
                        lang: lang,
                    },
                    fail: notification.exception
                }]);
                var matched = this.value;
                if(this.checked) {
                    if(this.classList.contains("enableall")) {
                        $(".enableallall").prop("checked", this.checked);
                    }
                    if(this.classList.contains("enablemanager")) {
                        $(".enableallmanager").prop("checked", this.checked);
                    }
                    if(this.classList.contains("enablesupervisor")) {
                        $(".enableallsupervisor").prop("checked", this.checked);
                    }
                } else {
                    if(this.classList.contains("enableall")) {
                        var checked = 0;
                        for (var i=0; i<inputElems.length; i++) {
                            if (inputElems[i].type === "checkbox" && inputElems[i].classList.contains('enableall')) {
                                if (inputElems[i].checked) {
                                    checked++;
                                }
                            }
                        }
                        if (checked == 0) {
                            $(".enableallall").prop("checked", "");
                        }
                    }
                    if(this.classList.contains("enablemanager")) {
                        var checked = 0;
                        for (var i=0; i<inputElems.length; i++) {
                            if (inputElems[i].type === "checkbox" && inputElems[i].classList.contains('enablemanager')) {
                                if (inputElems[i].checked) {
                                    checked++;
                                }
                            }
                        }
                        if (checked == 0) {
                            $(".enableallmanager").prop("checked", "");
                        }
                    }
                    if(this.classList.contains("enablesupervisor")) {
                        var checked = 0;
                            for (var i=0; i<inputElems.length; i++) {
                            if (inputElems[i].type === "checkbox" && inputElems[i].classList.contains('enablesupervisor')) {
                                if (inputElems[i].checked) {
                                    checked++;
                                }
                            }
                        }
                        if (checked == 0) {
                            $(".enablesupervisorall").prop("checked", "");
                        }
                    }
                }
                if (matched.match(/\.e\.\d+$/) !== null) {
                    // Get all of the entries and change them.
                    $(".enableall").prop("checked", this.checked);
                }
                if (matched.match(/\.em\.\d+$/) !== null) {
                    // Get all of the entries and change them.
                    $(".enablemanager").prop("checked", this.checked);
                }
                if (matched.match(/\.es\.\d+$/) !== null) {
                    // Get all of the entries and change them.
                    $(".enablesupervisor").prop("checked", this.checked);
                }
            });
        }
    };
});
