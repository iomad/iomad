/**
 * User tour control library.
 *
 * @module     tool_usertours/usertours
 * @class      usertours
 * @package    tool_usertours
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 */
define(
['core/ajax', 'tool_usertours/tour', 'jquery', 'core/templates', 'core/str', 'core/log', 'core/notification'],
function(ajax, BootstrapTour, $, templates, str, log, notification) {
    var usertours = {
        tourId: null,

        currentTour: null,

        context: null,

        /**
         * Initialise the user tour for the current page.
         *
         * @method  init
         * @param   {Number}    tourId      The ID of the tour to start.
         * @param   {Bool}      startTour   Attempt to start the tour now.
         * @param   {Number}    context     The context of the current page.
         */
        init: function(tourId, startTour, context) {
            // Only one tour per page is allowed.
            usertours.tourId = tourId;

            usertours.context = context;

            if (typeof startTour === 'undefined') {
                startTour = true;
            }

            if (startTour) {
                // Fetch the tour configuration.
                usertours.fetchTour(tourId);
            }

            usertours.addResetLink();
            // Watch for the reset link.
            $('body').on('click', '[data-action="tool_usertours/resetpagetour"]', function(e) {
                e.preventDefault();
                usertours.resetTourState(usertours.tourId);
            });
        },

        /**
         * Fetch the configuration specified tour, and start the tour when it has been fetched.
         *
         * @method  fetchTour
         * @param   {Number}    tourId      The ID of the tour to start.
         */
        fetchTour: function(tourId) {
            M.util.js_pending('admin_usertour_fetchTour' + tourId);
            $.when(
                ajax.call([
                    {
                        methodname: 'tool_usertours_fetch_and_start_tour',
                        args: {
                            tourid:     tourId,
                            context:    usertours.context,
                            pageurl:    window.location.href,
                        }
                    }
                ])[0],
                templates.render('tool_usertours/tourstep', {})
            ).then(function(response, template) {
                return usertours.startBootstrapTour(tourId, template[0], response.tourconfig);
            }).always(function() {
                M.util.js_complete('admin_usertour_fetchTour' + tourId);

                return;
            }).fail(notification.exception);
        },

        /**
         * Add a reset link to the page.
         *
         * @method  addResetLink
         */
        addResetLink: function() {
            var ele;
            M.util.js_pending('admin_usertour_addResetLink');

            // Append the link to the most suitable place on the page
            // with fallback to legacy selectors and finally the body
            // if there is no better place.
            if ($('.tool_usertours-resettourcontainer').length) {
                ele = $('.tool_usertours-resettourcontainer');
            } else if ($('.logininfo').length) {
                ele = $('.logininfo');
            } else if ($('footer').length) {
                ele = $('footer');
            } else {
                ele = $('body');
            }
            templates.render('tool_usertours/resettour', {})
                .done(function(html, js) {
                    templates.appendNodeContents(ele, html, js);

                    return;
                }).always(function() {
                    M.util.js_complete('admin_usertour_addResetLink');

                    return;
                }).fail();
        },

        /**
         * Start the specified tour.
         *
         * @method  startBootstrapTour
         * @param   {Number}    tourId      The ID of the tour to start.
         * @param   {String}    template    The template to use.
         * @param   {Object}    tourConfig  The tour configuration.
         * @return  {Object}
         */
        startBootstrapTour: function(tourId, template, tourConfig) {
            if (usertours.currentTour) {
                // End the current tour, but disable end tour handler.
                tourConfig.onEnd = null;
                usertours.currentTour.endTour();
                delete usertours.currentTour;
            }

            // Normalize for the new library.
            tourConfig.eventHandlers = {
                afterEnd: [usertours.markTourComplete],
                afterRender: [usertours.markStepShown],
            };

            // Sort out the tour name.
            tourConfig.tourName = tourConfig.name;
            delete tourConfig.name;

            // Add the template to the configuration.
            // This enables translations of the buttons.
            tourConfig.template = template;

            tourConfig.steps = tourConfig.steps.map(function(step) {
                if (typeof step.element !== 'undefined') {
                    step.target = step.element;
                    delete step.element;
                }

                if (typeof step.reflex !== 'undefined') {
                    step.moveOnClick = !!step.reflex;
                    delete step.reflex;
                }

                if (typeof step.content !== 'undefined') {
                    step.body = step.content;
                    delete step.content;
                }

                return step;
            });

            usertours.currentTour = new BootstrapTour(tourConfig);
            return usertours.currentTour.startTour();
        },

        /**
         * Mark the specified step as being shownd by the user.
         *
         * @method  markStepShown
         */
        markStepShown: function() {
            var stepConfig = this.getStepConfig(this.getCurrentStepNumber());
            $.when(
                ajax.call([
                    {
                        methodname: 'tool_usertours_step_shown',
                        args: {
                            tourid:     usertours.tourId,
                            context:    usertours.context,
                            pageurl:    window.location.href,
                            stepid:     stepConfig.stepid,
                            stepindex:  this.getCurrentStepNumber(),
                        }
                    }
                ])[0]
            ).fail(log.error);
        },

        /**
         * Mark the specified tour as being completed by the user.
         *
         * @method  markTourComplete
         */
        markTourComplete: function() {
            var stepConfig = this.getStepConfig(this.getCurrentStepNumber());
            $.when(
                ajax.call([
                    {
                        methodname: 'tool_usertours_complete_tour',
                        args: {
                            tourid:     usertours.tourId,
                            context:    usertours.context,
                            pageurl:    window.location.href,
                            stepid:     stepConfig.stepid,
                            stepindex:  this.getCurrentStepNumber(),
                        }
                    }
                ])[0]
            ).fail(log.error);
        },

        /**
         * Reset the state, and restart the the tour on the current page.
         *
         * @method  resetTourState
         * @param   {Number}    tourId      The ID of the tour to start.
         */
        resetTourState: function(tourId) {
            $.when(
                ajax.call([
                    {
                        methodname: 'tool_usertours_reset_tour',
                        args: {
                            tourid:     tourId,
                            context:    usertours.context,
                            pageurl:    window.location.href,
                        }
                    }
                ])[0]
            ).then(function(response) {
                if (response.startTour) {
                    usertours.fetchTour(response.startTour);
                }
                return;
            }).fail(notification.exception);
        }
    };

    return /** @alias module:tool_usertours/usertours */ {
        /**
         * Initialise the user tour for the current page.
         *
         * @method  init
         * @param   {Number}    tourId      The ID of the tour to start.
         * @param   {Bool}      startTour   Attempt to start the tour now.
         */
        init: usertours.init,

        /**
         * Reset the state, and restart the the tour on the current page.
         *
         * @method  resetTourState
         * @param   {Number}    tourId      The ID of the tour to restart.
         */
        resetTourState: usertours.resetTourState
    };
});
