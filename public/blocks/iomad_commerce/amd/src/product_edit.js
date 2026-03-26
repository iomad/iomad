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
    showEditproductform: '[data-action="show-producteditform"]',
    showhideproduct: '[data-action="do-showhideproduct"]',
    showExportproductprompt: '[data-action="show-exportproductconfirm"]',
    showImportproductprompt: '[data-action="show-importproductconfirm"]',
    showDeleteproductprompt: '[data-action="show-deleteproductconfirm"]',
    showDeletetagprompt: '[data-action="show-deletetagconfirm"]',
};

export const init = () => {

    // Add the product edit form handler.
    const showEditproductform = document.querySelectorAll(selectors.showEditproductform);
    const showDeleteproductprompt = document.querySelectorAll(selectors.showDeleteproductprompt);
    const showImportproductprompt = document.querySelectorAll(selectors.showImportproductprompt);
    const showExportproductprompt = document.querySelectorAll(selectors.showExportproductprompt);
    const showhideproduct = document.querySelectorAll(selectors.showhideproduct);
    const showDeletetagprompt = document.querySelectorAll(selectors.showDeletetagprompt);

    // Do we have any of these?
    if (showEditproductform === null &&
        showhideproduct === null &&
        showExportproductprompt === null &&
        showImportproductprompt === null &&
        showDeleteproductprompt === null &&
        showDeletetagprompt === null
    ) {
        return;
    }

    for (let i = 0; i < showEditproductform.length; i++) {
        showEditproductform[i].addEventListener('click', event => {
            event.preventDefault();

            var existing = showEditproductform[i].getAttribute('data-productid');
            var companyid = showEditproductform[i].getAttribute('data-companyid');
            if (existing == 0) {
                if (companyid == 0) {
                    var title = getString('createtemplate', 'block_iomad_commerce');
                } else {
                    var title = getString('createproduct', 'block_iomad_commerce');
                }
            } else {
                if (companyid == 0) {
                    var title = getString('edittemplate', 'block_iomad_commerce');
                } else {
                    var title = getString('editproduct', 'block_iomad_commerce');
                }
            }
            const form = new ModalForm({
                formClass: 'block_iomad_commerce\\forms\\product_edit_form',
                args: {
                    mycompanyid: showEditproductform[i].getAttribute('data-mycompanyid'),
                    companyid: showEditproductform[i].getAttribute('data-companyid'),
                    productid: showEditproductform[i].getAttribute('data-productid'),
                },
                modalConfig: {title},
                returnFocus: showEditproductform[i],
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

                // Only reload on successfull add of new product.
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

    // Add the product delete prompt handler.
    for (let i = 0; i < showDeleteproductprompt.length; i++) {
        showDeleteproductprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var productid = showDeleteproductprompt[i].getAttribute('data-productid');
            var productName = showDeleteproductprompt[i].getAttribute('data-productname');
            var companyid = showDeleteproductprompt[i].getAttribute('data-companyid');
            var mycompanyid = showDeleteproductprompt[i].getAttribute('data-mycompanyid');
            var tableRow = $(showDeleteproductprompt[i]).closest('tr');
            getStrings([
                { key: 'deletecourse', component: 'block_iomad_commerce' },
                { key: 'coursedeletecheckfull', component: 'block_iomad_commerce', param: productName },
                { key: 'yes' }
            ]).done(function (s) {
                notification.deleteCancel(s[0], s[1], s[2], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_delete_product',
                        args: {
                            productid: productid,
                            companyid: companyid,
                            mycompanyid: mycompanyid,
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
            });
        });
    }

    // Add import product handler.
    for (let i = 0; i < showImportproductprompt.length; i++) {
        showImportproductprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var productid = showImportproductprompt[i].getAttribute('data-productid');
            var productName = showImportproductprompt[i].getAttribute('data-productname');
            var companyid = showImportproductprompt[i].getAttribute('data-companyid');
            var mycompanyid = showImportproductprompt[i].getAttribute('data-mycompanyid');
            getStrings([
                { key: 'importproduct', component: 'block_iomad_commerce' },
                { key: 'importproductcheckfull', component: 'block_iomad_commerce', param: productName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_import_product',
                        args: {
                            productid: productid,
                            companyid: companyid,
                            mycompanyid: mycompanyid,
                        },
                        done: function (e) {
                            toastAdd(e.returnmessage,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Add export product handler.
    for (let i = 0; i < showExportproductprompt.length; i++) {
        showExportproductprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var productid = showExportproductprompt[i].getAttribute('data-productid');
            var productName = showExportproductprompt[i].getAttribute('data-productname');
            var companyid = showExportproductprompt[i].getAttribute('data-companyid');
            var mycompanyid = showExportproductprompt[i].getAttribute('data-mycompanyid');
            getStrings([
                { key: 'exportproduct', component: 'block_iomad_commerce' },
                { key: 'exportproductcheckfull', component: 'block_iomad_commerce', param: productName },
                { key: 'yes' },
                { key: 'no' }
            ]).done(function (s) {
                notification.confirm(s[0], s[1], s[2], s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_export_product',
                        args: {
                            productid: productid,
                            companyid: companyid,
                            mycompanyid: mycompanyid,
                        },
                        done: function (e) {
                            toastAdd(e.returnmessage,
                                {
                                    type: 'success',
                                    autohide: true,
                                    closeButton: true,
                                });
                        },
                        fail: notification.exception,
                    }]);
                });
            });
        });
    }

    // Show hide product handler.
    for (let i = 0; i < showhideproduct.length; i++) {
        showhideproduct[i].addEventListener('click', event => {
            event.preventDefault();

            var currentvalue = showhideproduct[i].getAttribute('data-currentvalue');
            var productid = showhideproduct[i].getAttribute('data-productid');
            var companyid = showhideproduct[i].getAttribute('data-companyid');
            var mycompanyid = showhideproduct[i].getAttribute('data-mycompanyid');
            var tableRow = $(showhideproduct[i]).closest('tr');
            var myIcon = $(showhideproduct[i]).find('i');

            ajax.call([{
                methodname: 'block_iomad_commerce_showhide_product',
                args: {
                    mycompanyid: mycompanyid,
                    companyid: companyid,
                    productid: productid,
                    currentvalue: currentvalue,
                },
                done: function () {
                    if (currentvalue == 1) {
                        tableRow.removeClass('dimmed_text');
                        tableRow.find('span').removeClass('dimmed_text');
                        tableRow.addClass('dimmed_text');

                        // Change icon to closed eye.
                        myIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                        showhideproduct[i].setAttribute('data-currentvalue', '0');
                    } else {
                        tableRow.removeClass('dimmed_text');
                        tableRow.find('span').removeClass('dimmed_text');

                        // Change icon to open eye.
                        myIcon.removeClass('fa-eye');
                        myIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                        showhideproduct[i].setAttribute('data-currentvalue', '1');
                    }
                },
                fail: notification.exception,
            }]);
        });
    }

    // Add the tag delete prompt handler.
    for (let i = 0; i < showDeletetagprompt.length; i++) {
        showDeletetagprompt[i].addEventListener('click', event => {
            event.preventDefault();
            var tagid = showDeletetagprompt[i].getAttribute('data-tagid');
            var tagname = showDeletetagprompt[i].getAttribute('data-tagname');
            var companyid = showDeletetagprompt[i].getAttribute('data-companyid');
            var usedstring = showDeletetagprompt[i].getAttribute('data-usedstring');
            var tableRow = $(showDeletetagprompt[i]).closest('tr');
            var success = getString('courseshoptagdeleted', 'block_iomad_commerce');
            getStrings([
                { key: 'deleteshoptag', component: 'block_iomad_commerce', param: tagname },
                { key: 'deleteshoptagcheck', component: 'block_iomad_commerce', param: tagname },
                { key: 'deleteshoptagcheckused', component: 'block_iomad_commerce', param: usedstring },
                { key: 'yes' }
            ]).done(function (s) {
                if (usedstring == '') {
                    var checkstring = s[1];
                } else {
                    var checkstring = s[2];
                }
                notification.deleteCancel(s[0], checkstring, s[3], function () {
                    ajax.call([{
                        methodname: 'block_iomad_commerce_delete_shoptag',
                        args: {
                            tagid: tagid,
                            companyid: companyid,
                        },
                        done: function () {
                            toastAdd(success,
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
            });
        });
    }
};
