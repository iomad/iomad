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
import notification from 'core/notification';
import ajax from 'core/ajax';
import Templates from 'core/templates';

const selectors = {
    doTagSearch: '[data-action="do-tagsearch"]',
};

export const init = () => {

    // Add the product add to cart handler.
    const doTagSearch = document.querySelectorAll(selectors.doTagSearch);

    // Do we have any of these?
    if (doTagSearch === null) {
        return;
    }

    // Add the tag handlers.
    for (let i = 0; i < doTagSearch.length; i++) {
        doTagSearch[i].addEventListener('click', event => {
            event.preventDefault();

            var tagName = doTagSearch[i].getAttribute('data-tagname');
            var companyID = doTagSearch[i].getAttribute('data-companyid');
            var searchText = doTagSearch[i].getAttribute('data-searchtext');
            var page = doTagSearch[i].getAttribute('data-page');
            var perpage = doTagSearch[i].getAttribute('data-perpage');
            ajax.call([{
                methodname: 'block_iomad_commerce_get_products',
                args: {
                    companyid: companyID,
                    page: page,
                    perpage: perpage,
                    passedshoptag: tagName,
                    passedshopsearch: searchText,
                },
                done: function (e) {
                    Templates.renderForPromise('block_iomad_commerce/shop', e).then(({html, js}) => {
                        Templates.replaceNodeContents($('#id_shopcontents'), html, js);
                        $('[data-action="shopbutton"]').removeClass("d-none");
                        init();
                    });
                },
                fail: notification.exception,
            }]);
        });
    }

    // Add the search form handler.
    $('[data-action="do-formsearch"]').on("submit", function(event) {
        event.preventDefault();
        var searchText = $('#id_productsearchinput');
        var currentTag = $('#id_searchtag');
        var companyID = $('#id_companyid');
        ajax.call([{
            methodname: 'block_iomad_commerce_get_products',
            args: {
                companyid: companyID.val(),
                page: 0,
                perpage: 0,
                passedshoptag: currentTag.val(),
                passedshopsearch: searchText.val(),
            },
            done: function (e) {
                Templates.renderForPromise('block_iomad_commerce/shop', e).then(({html, js}) => {
                    Templates.replaceNodeContents($('#id_shopcontents'), html, js);
                    $('[data-action="shopbutton"]').removeClass("d-none");
                    init();
                });
            },
            fail: notification.exception,
        }]);
    });

    // Add the reset button handler.
    $('[data-action="shopbutton"]').on('click', function(event) {
        event.preventDefault();
        ajax.call([{
            methodname: 'block_iomad_commerce_reset_search',
            args: {
                go: true,
            },
            done: function () {
                window.location.reload(true);
            },
            fail: notification.exception,
        }]);
    });
};
