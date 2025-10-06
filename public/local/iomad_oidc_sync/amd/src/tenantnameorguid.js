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
 * local_iomad_oidc_sync tenantnameorguid Modal form.
 *
 * @module     local_iomad_oidc_sync
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as toastAdd, addToastRegion} from 'core/toast';
import {
    exception as displayException,
} from 'core/notification';
const selectors = {
    showTenantorguidform: '[data-action="show-tenantorguidform"]',
};

export const init = () => {
    const showTenantorguidform = document.querySelectorAll(selectors.showTenantorguidform);
    if (showTenantorguidform === null) {
        return;
    }

    for (let i = 0; i < showTenantorguidform.length; i++) {
        showTenantorguidform[i].addEventListener('click', event => {
            event.preventDefault();

            const title = getString('settenantnameorguid', 'local_iomad_oidc_sync');
            const form = new ModalForm({
                formClass: 'local_iomad_oidc_sync\\form\\tenantnameorguid',
                args: {companyid: showTenantorguidform[i].getAttribute('data-companyid')},
                modalConfig: {title},
                returnFocus: showTenantorguidform[i],
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
                if (e.detail.result) {
                    if (e.detail.returnmessage != '') {
                        toastAdd(getString('tenantnameorguid_changed_warning', 'local_iomad_oidc_sync'),
                            {
                                type: 'warning',
                            }
                        );
                    } else {
                        toastAdd(getString('tenantnameorguid_changed_success', 'local_iomad_oidc_sync'),
                        {
                            type: 'success',
                        });
                    }

                    //Replace the string in the admin approve link
                    if (e.detail.tenantnameorguid != e.detail.oldname) {
                        var companyID = e.detail.companyid;
                        var approveLink = document.querySelector('[data-approvelink' +
                                                                 companyID +
                                                                 '="' +
                                                                 companyID +
                                                                 '"]');

                        // Get the new URL text.
                        var newapprovalURL = approveLink.getAttribute('href').replace(e.detail.oldname, e.detail.tenantnameorguid);

                        // Set it.
                        approveLink.setAttribute('href', newapprovalURL);

                        // Remove the style preventing clicking on it.
                        approveLink.setAttribute('style', '');

                        // Un-dim the link icon.
                        var iconLink = approveLink.innerHTML;
                        var newiconClass = iconLink.replace('dimmed_text', '');
                        approveLink.innerHTML = newiconClass;

                        // Change the tick to a cross.
                        var approveIcon = document.querySelector('[data-approveicon' +
                                                                 companyID +
                                                                 '="' +
                                                                 companyID +
                                                                 '"]');
                        var newapproveIconLink = approveIcon.getAttribute('src').replace('yes', 'no');
                        approveIcon.setAttribute('src', newapproveIconLink);

                        getString('no')
                           .then((noString) => {
                               approveIcon.setAttribute('alt', noString);
                               approveIcon.setAttribute('title', noString);
                            })
                           .catch();

                        // Change the slider to off.
                        var enableIcon = document.querySelector('[data-enableicon' +
                                                                 companyID +
                                                                 '="' +
                                                                 companyID +
                                                                 '"]');
                        enableIcon.setAttribute('class', 'icon fa fa-toggle-off fa-fw  dimmed_text');

                        getString('enable')
                           .then((enableString) => {
                               enableIcon.setAttribute('alt', enableString);
                               enableIcon.setAttribute('title', enableString);
                            })
                           .catch();

                        var enableLink = document.querySelector('[data-enablelink' +
                                                                 companyID +
                                                                 '="' +
                                                                 companyID +
                                                                 '"]');
                        enableLink.setAttribute('style', 'pointer-events:none;');

                    }
                }
            });
        });
    }
};