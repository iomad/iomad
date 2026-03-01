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
 * IOMAD dashboard ecommerce company Modal confirm.
 *
 * @module     block_iomad_company_admin
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ajax from 'core/ajax';
import notification from 'core/notification';

const selectors = {
    showEcommercecompanyprompt: '[data-action="show-ecommercecompanyprompt"]',
};

export const init = () => {

    /**
     * Function to add the correct eye icon to the course listing.
     *
     * @param {node} icon The icon identifier
     * @param {int} state The binary state for the icon
     *
     **/
    function _redraw(icon, state) {
        icon.removeClass('fa-store fa-store-slash');
        if (state == 1) {
            icon.addClass('fa-store');
        } else {
            icon.addClass('fa-store-slash');
        }
    }


    const showEcommercecompanyprompt = document.querySelectorAll(selectors.showEcommercecompanyprompt);
    if (showEcommercecompanyprompt === null) {
        return;
    }

    for (let i = 0; i < showEcommercecompanyprompt.length; i++) {
        showEcommercecompanyprompt[i].addEventListener('click', event => {
            event.preventDefault();

            var currentvalue = showEcommercecompanyprompt[i].getAttribute('data-ecommerce');
            var companyid = showEcommercecompanyprompt[i].getAttribute('data-companyid');
            var icon = $(showEcommercecompanyprompt[i]).find('i');

            ajax.call([{
                methodname: 'block_iomad_company_admin_company_ecommerce',
                args: {
                    companyid: companyid,
                    currentvalue: currentvalue,
                },
                done: function () {
                    if (currentvalue == 0) {
                        var state = 1;
                    } else {
                        var state = 0;
                    }
                    showEcommercecompanyprompt[i].setAttribute('data-ecommerce', state);
                    _redraw(icon, state);
                },
                fail: notification.exception,
            }]);
        });
    }
};
