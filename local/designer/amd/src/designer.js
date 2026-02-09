/* global lc_color_picker */
define(['local_designer/gradient'], function() {

    return {
        init: () => {

           /*  var SELECTORS = {
                fullScreen: '.course-header-full-screen',
                scrollArrowContainer: '.course-header-scroll-arrow',
                arrow: '.scroll-arrow-parent'
            };

            var courseHeader = document.querySelector(SELECTORS.fullScreen);
            // Course header is not fullscreen, no need to go further.
            if (courseHeader === null || courseHeader === undefined) {
                return;
            }

            // Scroll Arrow section.
            var downIconContainer = document.querySelector(SELECTORS.scrollArrowContainer);
            if (downIconContainer === null) {
                return;
            }
            // Show the down icon parent after 3 seconds.
            setTimeout(() => downIconContainer.classList.remove('hide'), 3000); */
            var sectionDesignerBackground = document.getElementById("id_sectiondesignerbackgradient");
            if (sectionDesignerBackground) {
                new lc_color_picker(sectionDesignerBackground, {
                    modes : ['linear-gradient'],
                });
            }

            var sectionAdminDesignerBackground = document.getElementById("id_s_format_designer_sectiondesignercustom_masksize");
            if (sectionAdminDesignerBackground) {
                new lc_color_picker(sectionAdminDesignerBackground, {
                    modes : ['linear-gradient'],
                });
            }

            var moduleDesignerBackground = document.getElementById("id_designer_backgradient");
            if (moduleDesignerBackground) {
                new lc_color_picker(moduleDesignerBackground, {
                    modes : ['linear-gradient'],
                });
            }
        }
    };
});
