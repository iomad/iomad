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
 * IOMAD dashboard edit eCommerce licenses Modal forms.
 *
 * @module     block_iomad_company_admin
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as toastAdd, addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
import notification from 'core/notification';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

const selectors = {
    showEditlicenseform: '[data-action="show-licenseeditform"]',
    showSplitlicenseform: '[data-action="show-licensesplitform"]',
    showDeletelicenseprompt: '[data-action="show-deletelicenseconfirm"]',
};

export const init = () => {

    // Add the license edit form handler.
    const showEditlicenseform = document.querySelectorAll(selectors.showEditlicenseform);
    const showSplitlicenseform = document.querySelectorAll(selectors.showSplitlicenseform);
    const showDeletelicenseprompt = document.querySelectorAll(selectors.showDeletelicenseprompt);

    // Do we have any of these?
    if (showEditlicenseform === null &&
        showSplitlicenseform === null &&
        showDeletelicenseprompt === null
    ) {
        return;
    }

    // Edit license form handler.
    for (let i = 0; i < showEditlicenseform.length; i++) {
        showEditlicenseform[i].addEventListener('click', event => {
            event.preventDefault();

            var existing = showEditlicenseform[i].getAttribute('data-licenseid');
            var companyid = showEditlicenseform[i].getAttribute('data-companyid');
            if (existing == 0) {
                var title = getString('createlicense', 'block_iomad_company_admin');
            } else {
                var title = getString('edit_licenses_title', 'block_iomad_company_admin');
            }
            const form = new ModalForm({
                formClass: 'block_iomad_company_admin\\forms\\company_license_form',
                args: {
                    companyid: companyid,
                    licenseid: existing,
                },
                modalConfig: {title},
                returnFocus: showEditlicenseform[i],
            });
            form.show().then(() => {
                addToastRegion(form.modal.getRoot()[0]);
                return true;
            }).catch(displayException);
            form.addEventListener(form.events.FORM_SUBMITTED, (e) => {

                // Remove toast region as if not it will be displayed on the closed modal.
                const modalElement = form.modal.getRoot()[0];
                const regions = modalElement.querySelectorAll('.toast-wrapper');
                regions.forEach((reg) => reg.remove());

                // Only reload on successfull add of new license.
                if (e.dorefresh == false) {
                    if (e.result == true) {
                        var resultType = 'success';
                    } else {
                        var resultType = 'error';
                    }
                    toastAdd(e.returnmessage,
                        {
                            type: resultType,
                            autohide: true,
                            closeButton: true,
                        });
                } else {
                    window.location.reload(true);
                }
            });
        });
    }

    // Split license form handler.
    for (let i = 0; i < showSplitlicenseform.length; i++) {
        showSplitlicenseform[i].addEventListener('click', event => {
            event.preventDefault();

            var parentid = showSplitlicenseform[i].getAttribute('data-parentid');
            var companyid = showSplitlicenseform[i].getAttribute('data-companyid');
            var title = getString('split_licenses', 'block_iomad_company_admin');
            const form = new ModalForm({
                formClass: 'block_iomad_company_admin\\forms\\company_license_form',
                args: {
                    companyid: companyid,
                    parentid: parentid,
                },
                modalConfig: {title},
                returnFocus: showSplitlicenseform[i],
            });
            form.show().then(() => {
                addToastRegion(form.modal.getRoot()[0]);
                return true;
            }).catch(displayException);
            form.addEventListener(form.events.FORM_SUBMITTED, (e) => {

                // Remove toast region as if not it will be displayed on the closed modal.
                const modalElement = form.modal.getRoot()[0];
                const regions = modalElement.querySelectorAll('.toast-wrapper');
                regions.forEach((reg) => reg.remove());

                // Only reload on successfull add of new license.
                if (e.dorefresh == false) {
                    if (e.result == true) {
                        var resultType = 'success';
                    } else {
                        var resultType = 'error';
                    }
                    toastAdd(e.returnmessage,
                        {
                            type: resultType,
                            autohide: true,
                            closeButton: true,
                        });
                } else {
                    window.location.reload(true);
                }
            });
        });
    }

    // Add the license delete prompt handler.
    for (let i = 0; i < showDeletelicenseprompt.length; i++) {
        showDeletelicenseprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var licenseid = showDeletelicenseprompt[i].getAttribute('data-licenseid');
            var licenseName = showDeletelicenseprompt[i].getAttribute('data-licensename');
            var companyid = showDeletelicenseprompt[i].getAttribute('data-companyid');
            var inUse = showDeletelicenseprompt[i].getAttribute('data-inuse');
            var tableRow = $(showDeletelicenseprompt[i]).closest('tr');
            getStrings([
                { key: 'deletelicense', component: 'block_iomad_company_admin' },
                { key: 'companydeletelicensecheckfull', component: 'block_iomad_company_admin', param: licenseName },
                { key: 'yes' },
                { key: 'licenseinuse', component: 'block_iomad_company_admin' },
                { key: 'ok' },
            ]).done(function (s) {
                if (inUse > 0) {
                    notification.alert(s[0], s[3], s[4]);
                } else {
                    notification.deleteCancel(s[0], s[1], s[2], function () {
                        ajax.call([{
                            methodname: 'block_iomad_company_admin_delete_license',
                            args: {
                                licenseid: licenseid,
                                companyid: companyid,
                            },
                            done: function (e) {
                                toastAdd(e.returnmessage,
                                    {
                                        type: 'success',
                                        autohide: true,
                                        closeButton: true,
                                    });
                                tableRow.remove();
                            },
                            fail: notification.exception,
                        }]);
                    });
                }
            });
        });
    }
};
