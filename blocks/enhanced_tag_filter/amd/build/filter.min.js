define(['jquery', 'core/str', 'core/notification'], function($, str, Notification) {
    "use strict";
   
    function filter() {
        alert("Initialization complete");
    }


    return {
        init: function() {
            filter();
        }
    };
    
});
