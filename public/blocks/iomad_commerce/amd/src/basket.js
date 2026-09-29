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
 * IOMAD dashboard edit eCommerce products Modal forms.
 *
 * @module     block_iomad_commerce
 * @copyright  2026 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import {add as toastAdd} from 'core/toast';
import notification from 'core/notification';
import ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

const selectors = {
    showAddItemPrompt: '[data-action="additemconfirm"]',
    showDeleteItemPrompt: '[data-action="deleteitemconfirm"]',
    cartButton: '[data-action="cartbutton"]',
    checkoutButton: '[data-action="checkoutbutton"]',
};

export const init = () => {

    // Add the product add to cart handler.
    const showAddItemPrompt = document.querySelectorAll(selectors.showAddItemPrompt);
    const showDeleteItemPrompt = document.querySelectorAll(selectors.showDeleteItemPrompt);
    const cartButton = document.querySelector(selectors.cartButton);
    const checkoutButton = document.querySelector(selectors.checkoutButton);

    // Do we have any of these?
    if (showAddItemPrompt === null &&
        showDeleteItemPrompt === null
    ) {
        return;
    }

    for (let i = 0; i < showAddItemPrompt.length; i++) {
        showAddItemPrompt[i].addEventListener('click', event => {
            event.preventDefault();

            var productid = showAddItemPrompt[i].getAttribute('data-productid');
            var productName = showAddItemPrompt[i].getAttribute('data-productname');
            var amount = showAddItemPrompt[i].getAttribute('data-amount');
            var singlepurchase = showAddItemPrompt[i].getAttribute('data-singlepurchase');
            var myCart = $(cartButton).find('span');
            var myCheckout = $(checkoutButton).find('span');
            getStrings([
                { key: 'addtocart', component: 'block_iomad_commerce' },
                { key: 'addtocartcheckfull', component: 'block_iomad_commerce', param: productName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_add_to_cart',
                        args: {
                            productid: productid,
                            amount: amount,
                            singlepurchase: singlepurchase,
                        },
                        done: function (e) {
                            toastAdd(e.returnmessage,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                            myCart.removeClass("d-none");
                            myCheckout.removeClass("d-none");
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add the remove product from cart handler.
    for (let i = 0; i < showDeleteItemPrompt.length; i++) {
        showDeleteItemPrompt[i].addEventListener('click', event => {
            event.preventDefault();
            var invoiceitem = showDeleteItemPrompt[i].getAttribute('data-itemid');
            var productName = showDeleteItemPrompt[i].getAttribute('data-productname');
            var tableRow = $(showDeleteItemPrompt[i]).closest('tr');
            var myCart = $(cartButton).find('span');
            var myCheckout = $(checkoutButton).find('span');
            var invoiceTotal = document.querySelector('[data-id="invoicetotal"]');
            getStrings([
                { key: 'removefromcart', component: 'block_iomad_commerce' },
                { key: 'removefromcartcheckfull', component: 'block_iomad_commerce', param: productName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_remove_from_cart',
                        args: {
                            invoiceitem: invoiceitem,
                        },
                        done: function (e) {
                            toastAdd(e.returnmessage,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                            tableRow.remove();
                            // Do we have anything left?
                            if (e.lastitem == true) {
                                myCart.removeClass("d-none").addClass("d-none");
                                myCheckout.removeClass("d-none").addClass("d-none");
                            }
                            invoiceTotal.innerText = e.baskettotal;
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }
};
