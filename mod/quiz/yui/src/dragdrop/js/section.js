/**
 * Section drag and drop.
 *
 * @class M.mod_quiz.dragdrop.section
 * @constructor
 * @extends M.core.dragdrop
 */
var DRAGSECTION = function() {
    DRAGSECTION.superclass.constructor.apply(this, arguments);
};
Y.extend(DRAGSECTION, M.core.dragdrop, {
    sectionlistselector: null,

    initializer: function() {
        // Set group for parent class
        this.groups = [CSS.SECTIONDRAGGABLE];
        this.samenodeclass = 'section';
        this.parentnodeclass = 'slots';

        // Check if we are in single section mode
        if (Y.Node.one('.' + CSS.JUMPMENU)) {
            return false;
        }
        // Initialise sections dragging
        this.sectionlistselector = 'li.section';
        if (this.sectionlistselector) {
            this.sectionlistselector = '.' + CSS.COURSECONTENT + ' ' + this.sectionlistselector;

            this.setup_for_section(this.sectionlistselector);

            // Make each li element in the lists of sections draggable
            var del = new Y.DD.Delegate({
                container: '.' + CSS.COURSECONTENT,
                nodes: '.' + CSS.SECTIONDRAGGABLE,
                target: true,
                handles: ['.' + CSS.LEFT],
                dragConfig: {groups: this.groups}
            });
            del.dd.plug(Y.Plugin.DDProxy, {
                // Don't move the node at the end of the drag
                moveOnEnd: false
            });
            del.dd.plug(Y.Plugin.DDConstrained, {
                // Keep it inside the .mod-quiz-edit-content
                constrain: '#' + CSS.PAGECONTENT,
                stickY: true
            });
            del.dd.plug(Y.Plugin.DDWinScroll);
        }
    },

    /**
     * Apply dragdrop features to the specified selector or node that refers to section(s)
     *
     * @method setup_for_section
     * @param {String} baseselector The CSS selector or node to limit scope to
     */
    setup_for_section: function(baseselector) {
        Y.Node.all(baseselector).each(function(sectionnode) {
            // Determine the section ID
            var sectionid = Y.Moodle.core_course.util.section.getId(sectionnode);

            // We skip the top section as it is not draggable
            if (sectionid > 0) {
                // Remove move icons
                var movedown = sectionnode.one('.' + CSS.RIGHT + ' a.' + CSS.MOVEDOWN);
                var moveup = sectionnode.one('.' + CSS.RIGHT + ' a.' + CSS.MOVEUP);

                // Add dragger icon
                var title = M.util.get_string('movesection', 'moodle', sectionid);
                var cssleft = sectionnode.one('.' + CSS.LEFT);

                if ((movedown || moveup) && cssleft) {
                    cssleft.setStyle('cursor', 'move');
                    cssleft.appendChild(this.get_drag_handle(title, CSS.SECTIONHANDLE, 'icon', true));

                    if (moveup) {
                        moveup.remove();
                    }
                    if (movedown) {
                        movedown.remove();
                    }

                    // This section can be moved - add the class to indicate this to Y.DD.
                    sectionnode.addClass(CSS.SECTIONDRAGGABLE);
                }
            }
        }, this);
    },

    /*
     * Drag-dropping related functions
     */
    drag_start: function(e) {
        // Get our drag object
        var drag = e.target;
        // Creat a dummy structure of the outer elemnents for clean styles application
        var containernode = Y.Node.create('<ul class="slots"></ul>');
        var sectionnode = Y.Node.create('<ul class="section"></ul>');
        sectionnode.setStyle('margin', 0);
        sectionnode.setContent(drag.get('node').get('innerHTML'));
        containernode.appendChild(sectionnode);
        drag.get('dragNode').setContent(containernode);
        drag.get('dragNode').addClass(CSS.COURSECONTENT);
    },

    drag_dropmiss: function(e) {
        // Missed the target, but we assume the user intended to drop it
        // on the last last ghost node location, e.drag and e.drop should be
        // prepared by global_drag_dropmiss parent so simulate drop_hit(e).
        this.drop_hit(e);
    },

    get_section_index: function(node) {
        var sectionlistselector = '.' + CSS.COURSECONTENT + ' li.section',
            sectionList = Y.all(sectionlistselector),
            nodeIndex = sectionList.indexOf(node),
            zeroIndex = sectionList.indexOf(Y.one('#section-0'));

        return (nodeIndex - zeroIndex);
    },

    drop_hit: function(e) {
        var drag = e.drag;

        // Get references to our nodes and their IDs.
        var dragnode = drag.get('node'),
            dragnodeid = Y.Moodle.core_course.util.section.getId(dragnode),
            loopstart = dragnodeid,

            dropnodeindex = this.get_section_index(dragnode),
            loopend = dropnodeindex;

        if (dragnodeid === dropnodeindex) {
            Y.log("Skipping move - same location moving " + dragnodeid + " to " + dropnodeindex,
                  'debug', 'moodle-mod_quiz-dragdrop');
            return;
        }

        Y.log("Moving from position " + dragnodeid + " to position " + dropnodeindex, 'debug', 'moodle-mod_quiz-dragdrop');

        if (loopstart > loopend) {
            // If we're going up, we need to swap the loop order
            // because loops can't go backwards.
            loopstart = dropnodeindex;
            loopend = dragnodeid;
        }

        // Get the list of nodes.
        drag.get('dragNode').removeClass(CSS.COURSECONTENT);
        var sectionlist = Y.Node.all(this.sectionlistselector);

        // Add a lightbox if it's not there.
        var lightbox = M.util.add_lightbox(Y, dragnode);

        // Handle any variables which we must pass via AJAX.
        var params = {},
            pageparams = this.get('config').pageparams,
            varname;

        for (varname in pageparams) {
            if (!pageparams.hasOwnProperty(varname)) {
                continue;
            }
            params[varname] = pageparams[varname];
        }

        // Prepare request parameters
        params.sesskey = M.cfg.sesskey;
        params.courseid = this.get('courseid');
        params.quizid = this.get('quizid');
        params['class'] = 'section';
        params.field = 'move';
        params.id = dragnodeid;
        params.value = dropnodeindex;

        // Perform the AJAX request.
        var uri = M.cfg.wwwroot + this.get('ajaxurl');
        Y.io(uri, {
            method: 'POST',
            data: params,
            on: {
                start: function() {
                    lightbox.show();
                },
                success: function(tid, response) {
                    // Update section titles, we can't simply swap them as
                    // they might have custom title
                    try {
                        var responsetext = Y.JSON.parse(response.responseText);
                        if (responsetext.error) {
                            new M.core.ajaxException(responsetext);
                        }
                        M.mod_quiz.edit.process_sections(Y, sectionlist, responsetext, loopstart, loopend);
                    } catch (e) {
                        // Ignore.
                    }

                    // Update all of the section IDs - first unset them, then set them
                    // to avoid duplicates in the DOM.
                    var index;

                    // Classic bubble sort algorithm is applied to the section
                    // nodes between original drag node location and the new one.
                    var swapped = false;
                    do {
                        swapped = false;
                        for (index = loopstart; index <= loopend; index++) {
                            if (Y.Moodle.core_course.util.section.getId(sectionlist.item(index - 1)) >
                                        Y.Moodle.core_course.util.section.getId(sectionlist.item(index))) {
                                Y.log("Swapping " + Y.Moodle.core_course.util.section.getId(sectionlist.item(index - 1)) +
                                        " with " + Y.Moodle.core_course.util.section.getId(sectionlist.item(index)),
                                        "debug", "moodle-mod_quiz-dragdrop");
                                // Swap section id.
                                var sectionid = sectionlist.item(index - 1).get('id');
                                sectionlist.item(index - 1).set('id', sectionlist.item(index).get('id'));
                                sectionlist.item(index).set('id', sectionid);

                                // See what format needs to swap.
                                M.mod_quiz.edit.swap_sections(Y, index - 1, index);

                                // Update flag.
                                swapped = true;
                            }
                        }
                        loopend = loopend - 1;
                    } while (swapped);

                    window.setTimeout(function() {
                        lightbox.hide();
                    }, 250);
                },

                failure: function(tid, response) {
                    this.ajax_failure(response);
                    lightbox.hide();
                }
            },
            context: this
        });
    }

}, {
    NAME: 'mod_quiz-dragdrop-section',
    ATTRS: {
        courseid: {
            value: null
        },
        quizid: {
            value: null
        },
        ajaxurl: {
            value: 0
        },
        config: {
            value: 0
        }
    }
});

M.mod_quiz = M.mod_quiz || {};
M.mod_quiz.init_section_dragdrop = function(params) {
    new DRAGSECTION(params);
};
