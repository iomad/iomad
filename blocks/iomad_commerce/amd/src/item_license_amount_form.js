/*
 * @package    block_iomad_commerce
 * @copyright  2025 e-Learn Design
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_iomad_commerce/item_license_amount_form
 */

define(["jquery", "core/str"], function ($, str) {
    return {
        init: function() {
            const license_form_amount = $("#license_amount_form");
            if (license_form_amount != null){
                $("#license_amount_form").on("submit", function(e){
                    e.preventDefault();
                    const licenses = $("#id_nlicenses");
                    const licenseError = $("#id_nlicenses_error");
                    if (parseInt(licenses.val()) > 0) {
                        licenseError.css("display", "none");
                        licenseError.text('');
                        licenses.css("border",  "");
                        this.submit();
                    } else {
                        licenses.css("border",  "1px solid red");
                        str.get_string("error_invalidlicenseamount", "block_iomad_commerce").then(function(string){
                            licenseError.css("display", "block");
                            licenseError.text(string);
                        });
                    }
                });
            }
        }
    }
});