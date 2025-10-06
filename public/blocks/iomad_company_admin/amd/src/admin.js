/*
 * @package    block_iomad_company_admin
 * @copyright  2019 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module block_iomad_company_admin/admin
  */

define(['jquery', 'theme_boost/bootstrap/tab'], function($) {
    return {
        init: function(){
            // Store ID of clicked tab on Dashboard
            $(".iomad_company_admin").children(".nav-tabs").children(".nav-item").on("click", function() {
                var id = $(this).find("a").attr("href");
                try {
                    //Attempt to set the item in session storage
                    sessionStorage.setItem("iomad-dashboard-tab", id);
                } catch (e) {
                    return;
                }
            });

            // Recover clicked tab on Dashboard
            try {
                setTimeout(async function(){
                    var tabs = $(".iomad_company_admin").children(".nav-tabs").children(".nav-item");
                    var id = sessionStorage.getItem("iomad-dashboard-tab");
                    //Check if the id is not equal to null
                    if(id !== null){
                        //Check if the variable id doesn't include the string 'http' - Remove once the function for the click is fixed
                        var hasTab = false;
                        tabs.each(function(index, element){
                            if($(element).find("a").attr("href") == id){
                                hasTab = true;
                            }
                        });
                        //Exit the function if the tab stored is session storage is not in the page
                        if(hasTab === false){
                            return;
                        }
                        //Loop through each .nav-item element
                        tabs.each(function(index, element){
                            //Get the <a> element
                            var element = $(element).find("a");
                            //Set the attribute "aria-selected" to false
                            element.attr("aria-selected", false);
                            //Remove the class "active" if it exists
                            element.removeClass("active");
                            $(element.attr("href")).removeClass("active");
                            //Check if the attribute "href" is the same value stored in the id variable
                            if(element.attr("href") === id){
                                //Set the attribute "aria-selected" to true
                                element.attr("aria-selected", true);
                                //Add the class "active" to the element
                                element.addClass("active");
                                $(element.attr("href")).addClass("active");
                            }
                        });
                    }
                    //Set a function to run after 500ms to validate whether a company is selected and if not then reset the tabs to the first tab
                    setTimeout(async function(){
                        if($(".form-autocomplete-selection").length === 0){
                            return;
                        }
                        //Proceed with the if statement if a company is not selected
                        if($(".form-autocomplete-selection").find("span").text().replace(/\s+/g, '').includes($('#id_company_label').text().replace(/\s+/g, ''))){
                            var tabs = $(".iomad_company_admin").children(".nav-tabs").children(".nav-item");
                            //Loop through each .nav-item element
                            tabs.each(function(index, element){
                                //Get the <a> element
                                var element = $(element).find("a");
                                //Set the attribute "aria-selected" to false
                                element.attr("aria-selected", false);
                                //Remove the class "active" if it exists
                                element.removeClass("active");
                                $(element.attr("href")).removeClass("active");
                            });
                            //Set the first tab as active and visible
                            var element = $(tabs[0]).find("a");
                            element.attr("aria-selected", false);
                            element.addClass("active");
                            $(element.attr("href")).addClass("active");
                            try{
                                sessionStorage.removeItem("iomad-dashboard-tab", id);
                            } catch(e){}
                        }
                    }, 750);
                }, 0);
            } catch (e) {
                return;
            }
        }
    };
});