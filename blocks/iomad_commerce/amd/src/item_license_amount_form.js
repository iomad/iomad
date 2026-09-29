/*
 * @package    block_iomad_commerce
 * @copyright  2025 e-Learn Design
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_iomad_commerce/item_license_amount_form
 */

define(["jquery", "core/str", "core/ajax", 'core/toast','core/notification'], function ($, str, ajax, toast, notification) {
    return {
        init: function() {
            const license_form_amount = $("#license_amount_form");
            const cartButton = $('[data-action="cartbutton"]');
            const checkoutButton = $('[data-action="checkoutbutton"]');

            if (license_form_amount !== null) {
                $("#license_amount_form").on("submit", function(e) {
                    e.preventDefault();
                    const licenses = $("#id_nlicenses");
                    const productID = $("#id_itemid").val();
                    const licenseError = $("#id_nlicenses_error");
                    var myCart = cartButton.find('span');
                    var myCheckout = checkoutButton.find('span');
                    if (parseInt(licenses.val()) > 0) {
                        licenseError.css("display", "none");
                        licenseError.text('');
                        licenses.css("border",  "");
                        ajax.call([{
                            methodname: 'block_iomad_commerce_add_to_cart',
                            args: {
                                productid: productID,
                                amount: licenses.val(),
                                singlepurchase: "0",
                            },
                            done: function (r) {
                                toast.add(r.returnmessage,
                                    {
                                        type: 'success',
                                        autohide: true,
                                        closeButton: true,
                                    });
                                myCart.removeClass("d-none");
                                myCheckout.removeClass("d-none");
                                licenses.val('');
                            },
                            fail: notification.exception,
                        }]);
                    } else {
                        licenses.css("border",  "1px solid red");
                        str.get_string("error_invalidlicenseamount", "block_iomad_commerce").then(function(string) {
                            licenseError.css("display", "block");
                            licenseError.text(string);
                        });
                    }
                });
            }
        }
    };
});