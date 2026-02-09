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
 * Implemented designer format js.
 *
 * @module     format_designer/designer_section
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 define(['jquery', 'core/fragment', 'core/templates', 'core/loadingicon', 'core/ajax',
    'core_course/actions', 'core_message/toggle_contact_button', 'theme_boost/popover', 'core/notification',],
 function($, Fragment, Templates, Loadingicon, Ajax, Actions, Contact, Notification) {

    var SELECTOR = {
        ACTIVITYLI: 'li.activity',
        SECTIONLI: 'li.section',
        ACTIVITYACTION: 'a.cm-edit-action',
        SECTIONACTIONMENU: '.section_action_menu.designer-menu',
    };

    Y.use('moodle-course-coursebase', function() {
        var courseformatselector = M.course.format.get_section_selector();
        if (courseformatselector) {
            SELECTOR.SECTIONLI = courseformatselector;
        }
    });


    /**
     * Control designer format action
     * @param {int} courseId
     * @param {int} contextId
     * @param {array} popupActivities
     * @param {bool} videoTime
     * @param {bool} issubpanel
     * @param {int} sectionreturn
     */
    let DesignerSection = function(courseId, contextId, popupActivities, videoTime, issubpanel, sectionreturn) {
        var self = this;
        self.courseId = courseId;
        self.contextId = contextId;
        self.popupActivities = popupActivities;
        self.videoTime = videoTime;
        self.isSubpanel = issubpanel;
        self.sectionReturn = sectionreturn;

        $(".course-info-block .carousel .carousel-item:nth-child(1)").addClass('active');
        $(".course-info-block #courseStaffinfoControls.carousel").addClass('active');

        $('body').delegate(self.SectionController, 'click', self.sectionLayoutaction.bind(this));
        $('body').delegate(self.SectionSubmenuSwitcher, 'click', self.sectionLayoutaction.bind(this));

        $("body").delegate(self.RestrictInfo, "click", self.moduleHandler.bind(this));
        $("body").delegate(self.sectionRestricted, "click", this.sectionRestrictHandler.bind(this));
        $('body').delegate(self.fullDescription, "click", self.fullmodcontentHandler.bind(this));
        $('body').delegate(self.trimDescription, "click", self.trimmodcontentHandler.bind(this));
        $('body').delegate(self.goToURL, "click", self.redirectToModule.bind(this));
        $('body').delegate(self.goToSectionURL, "click", self.redirectToSection.bind(this));
        window.onhashchange = function() {
            self.expandSection();
        };
        this.expandSection();

        var contactModal = document.getElementsByClassName('toggle-contact-button');
        Array.from(contactModal).forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                if (e.currentTarget.dataset.userid != undefined) {
                    Contact.enhance(e.currentTarget);
                }
            });
        });

        $('.progress .progress-bar[data-toggle="popover"]').popover();

    };

    /**
     * Selector section controller.
     */
    DesignerSection.prototype.goToURL = '.designer [data-action="go-to-url"]';

    DesignerSection.prototype.goToSectionURL = '.designer [data-action="go-to-section-url"]';

    DesignerSection.prototype.SectionController = ".designer #section-designer-action .dropdown-menu a";

    DesignerSection.prototype.SectionSubmenuSwitcher
        = ".designer .section_action_menu .dropdown-subpanel a[data-value=section-designer-action] + .dropdown-menu a";

    DesignerSection.prototype.RestrictInfo = ".designer .designer-section-content .call-action-block";

    DesignerSection.prototype.moduleBlock = ".designer .designer-section-content li.activity";

    DesignerSection.prototype.loadingElement = ".icon-loader-block";

    DesignerSection.prototype.sectionRestricted = ".designer .availability-section-block .section-restricted-action";

    DesignerSection.prototype.fullDescription = ".designer-section-content li .fullcontent-summary .mod-description-action";

    DesignerSection.prototype.trimDescription = ".designer-section-content li .trim-summary .mod-description-action";

    DesignerSection.prototype.modules = null;

    DesignerSection.prototype.redirectToModule = function(event) {
        let nodeName = event.target.nodeName;
        let preventionNodes = ['a', 'button', 'form'];
        let iscircle = event.target.closest('li.activity').classList.contains('circle-layout');
        let isDescription = event.target.classList.contains('mod-description-action');
        let isPadlock = event.target.classList.contains('fa-lock');
        let ispopupModule = event.target.closest('li.activity').classList.contains('popmodule');
        let isModHasURL = event.target.closest('li.activity div[data-action="go-to-url"]').getAttribute('data-url');
        let isCompletionButton = event.target.closest('button[data-action="toggle-manual-completion"]');
        if ((nodeName in preventionNodes)
            || document.body.classList.contains('editing') || iscircle || isDescription || isPadlock || ispopupModule
            || isModHasURL == '' || isCompletionButton) {
            if (ispopupModule && !document.body.classList.contains('editing')) {
                if (event.target.closest("button[data-action='toggle-manual-completion']") === null &&
                    event.target.closest(".mod-description-action") === null) {
                    var li = event.target.closest('li.activity');
                    li.querySelector('a[href]').click();
                }
            }
            return null;
        }
        var card = event.target.closest("[data-action=go-to-url]");
        let modurl = card.getAttribute('data-url');
        window.location.href = modurl;
        return true;
    };

    DesignerSection.prototype.redirectToSection = function(event) {
        let isPadlock = event.target.classList.contains('fa-lock');
        if (document.body.classList.contains('editing') || isPadlock) {
            return null;
        }
        var singlesection = event.target.closest("[data-action=go-to-section-url]");
        let sectionurl = singlesection.getAttribute('data-url');
        let sectiontarget =  "_self";
        let target = singlesection.getAttribute('data-target');
        if (target) {
            sectiontarget = target;
        }
        window.open(sectionurl, sectiontarget);
        return true;
    };

    DesignerSection.prototype.expandSection = () => {
        var sectionID = window.location.hash;
        if (sectionID) {
            var id = sectionID.substring(1);
            var section = document.getElementById(id);
            if (section) {
                var title = section.querySelector('.section-header-content');
                if (title) {
                    title.classList.remove('collapsed');
                    title.setAttribute('aria-expanded', true);
                }
                var content = section.querySelector('.content');
                if (content) {
                    content.classList.add('show');
                }
                if (document.getElementById('section-course-accordion') !== null) {
                    document.getElementById('section-head-0').classList.add('collapsed');
                    document.getElementById('section-content-0').classList.remove('show');
                }
                section.scrollIntoView();
            }
        }
    };

    DesignerSection.prototype.fullmodcontentHandler = function(event) {
        var THIS = $(event.currentTarget);
        let fullContent = $(THIS).closest('li.activity').find('.fullcontent-summary');
        let trimcontent = $(THIS).closest('li.activity').find('.trim-summary');
        if (trimcontent.hasClass('summary-hide')) {
            trimcontent.removeClass('summary-hide');
            fullContent.addClass('summary-hide');
        }
    };

    DesignerSection.prototype.trimmodcontentHandler = function(event) {
        var THIS = $(event.currentTarget);
        let fullContent = $(THIS).closest('li.activity').find('.fullcontent-summary');
        let trimcontent = $(THIS).closest('li.activity').find('.trim-summary');
        if (fullContent.hasClass('summary-hide')) {
            fullContent.removeClass('summary-hide');
            trimcontent.addClass('summary-hide');
        }
    };

    DesignerSection.prototype.sectionRestrictHandler = function(event) {
        var sectionRestrictInfo = $(event.currentTarget).prev();
        if (sectionRestrictInfo) {
            if (!sectionRestrictInfo.hasClass('show')) {
                sectionRestrictInfo.addClass('show');
            } else {
                sectionRestrictInfo.removeClass('show');
            }
        }
    };

    DesignerSection.prototype.moduleHandler = function(event) {
        event.preventDefault();
        var restrictBlock = $(event.currentTarget).parents('.restrict-block');
        if (restrictBlock.length) {
            if (!restrictBlock.hasClass('show')) {
                restrictBlock.addClass('show');
            } else {
                restrictBlock.removeClass('show');
            }
        }
    };

    DesignerSection.prototype.updateVideoTimeInstance = function(sectionId) {
        var section = "#" + sectionId;
        var sectionVideotimes = "body "+ section + " .activity.videotime";
        if ($(sectionVideotimes).length == 0) {
            return;
        }
        $(sectionVideotimes).each(function(index, module) {
            this.CreateInstance(module);
        }.bind(this));
    };

    DesignerSection.prototype.CreateInstance = function (module) {
        if (
            $(module).find('.instancename').length
            && ($(module).find('.vimeo-embed').length
            || $(module).find('.video-js').length)
        ) {
            var cmId = module.getAttribute("data-id");
            var args = {cmid : cmId};
            // Get module instance.
            var promises = Ajax.call([{
                methodname: 'format_designer_get_videotime_instace',
                args: args
            }], true);
            promises[0].then(function(data) {
                var template = JSON.parse(data);
                if (template.playertype == 'videojs') {
                    var uniqueid =  $(module).find('.video-js').first().attr('id').replace('vimeo-embed-', '');
                } else {
                    var uniqueid =  $(module).find('.vimeo-embed').first().attr('id').replace('vimeo-embed-', '');
                }
                template.uniqueid = uniqueid;
                Templates.render(template.templatename, template).then(function(html, js) {
                    Templates.runTemplateJS(js);
                    return true;
                }).fail(Notification.exception);
            });
        }
    };

    /**
     * Implementaion swith the section layout.
     * @param {object} event
     */
    DesignerSection.prototype.sectionLayoutaction = function(event) {
        event.preventDefault();
        var self = this;
        let sectionId = event.target.closest('li.section').getAttribute('id');
        var sectionid = event.target.closest('li.section').getAttribute('data-id');
        var sectionitem = document.getElementById(sectionId);
        var iconBlock = "#" + sectionId + " " + self.loadingElement;
        var layout = $(event.currentTarget).data('value');
        var layouttext = $(event.currentTarget).text();
        $(event.target).parents(".dropdown").find(".btn").html(layouttext);
        $(event.target).parents(".dropdown").find(".btn").val(layout);
        $(event.target).parent().find("a.dropdown-item").each(function() {
            $(this).removeClass('active');
        });
        $(event.target).addClass('active');
        let dataid = event.target.closest('li.section').getAttribute('data-id');
        var args = {
            courseid: self.courseId,
            sectionid: dataid,
            options: [{name: $(event.currentTarget).data('option'), value: layout}]
        };
        var promises = Ajax.call([{
                methodname: 'format_designer_set_section_options',
                args: args
            }], true);
            $.when.apply($, promises)
            .done(function() {
                if (self.isSubpanel) {
                    const promise = Fragment.loadFragment(
                        'core_courseformat',
                        'section',
                        self.contextId,
                        {
                            id: sectionid,
                            courseid: self.courseId,
                            sr: self.sectionReturn,
                        }
                    );
                    promise.then((html, js) => {
                        Templates.replaceNode(sectionitem, html, js);
                    }).catch();
                } else {
                    const sectionpromise = Actions.refreshSection('#' + sectionId, dataid, 0);
                    sectionpromise.then(() => {
                        return '';
                    }).catch();
                }
            });
        Loadingicon.addIconToContainerRemoveOnCompletion(iconBlock, promises);
        // If videotime exist update the module.
        setTimeout(function() {
            if (self.videoTime) {
                self.updateVideoTimeInstance(sectionId);
            }
        }, 2000);
    };

    return {
        init: function(courseId, contextId, popupActivities, videoTime, issubpanel, sectionreturn) {
            return new DesignerSection(courseId, contextId, popupActivities, videoTime, issubpanel, sectionreturn);
        }
    };
});
