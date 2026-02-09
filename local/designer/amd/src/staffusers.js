define(["jquery", "local_designer/slick"], (function($) {

    var element = document.querySelector('.course-header-content-block .course-info-elements #course-users .staff-users-inner');

    const staffusers = () => {
        if (element != undefined) {
            initCarousel();
        }
    };

    /**
     * Initialize the carousel for staff users in course header.
     * @returns {bool}
     */
    const initCarousel = function() {

        if ($(element).find(".staff-user-item").length <= 1) {
            return null;
        }
        var slidesToShow = $(element).find(".staff-user-item").length <= 4
            ? $(element).find(".staff-user-item").length : 5;

        $(element).slick({
            arrows: true,
            swipe: true,
            infinite: false,
            slidesToShow: slidesToShow,
            slidesToScroll: 1,
            autoplay: false,
            autoplaySpeed: 2000,
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 4,
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 3,
                    }
                },
                {
                    breakpoint: 767,
                    settings: {

                        slidesToShow: 2,
                        slidesToScroll: 2,
                    }
                },
                {
                    breakpoint: 575,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                    }
                }
            ],
        });

        window.onresize = () => $(element).slick('refresh');

        return true;
    };

    return {
        init: function() {
            staffusers();
        },
    };
}));
