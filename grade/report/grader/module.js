/**
 * Grader report namespace
 */
M.gradereport_grader = {
    /**
     * @namespace M.gradereport_grader
     * @param {Object} reports A collection of classes used by the grader report module
     */
    classes : {},
    /**
     * Instantiates a new grader report
     *
     * @function
     * @param {YUI} Y
     * @param {Object} cfg A configuration object
     * @param {Array} An array of items in the report
     * @param {Array} An array of users on the report
     * @param {Array} An array of feedback objects
     * @param {Array} An array of student grades
     */
    init_report : function(Y, cfg, items, users, feedback, grades) {
        // Create the actual report
        new this.classes.report(Y, cfg, items, users, feedback, grades);
    }
};

/**
 * Initialises the JavaScript for the gradebook grader report
 *
 * The functions fall into 3 groups:
 * M.gradereport_grader.classes.ajax Used when editing is off and fields are dynamically added and removed
 * M.gradereport_grader.classes.existingfield Used when editing is on meaning all fields are already displayed
 * M.gradereport_grader.classes.report Common to both of the above
 *
 * @class report
 * @constructor
 * @this {M.gradereport_grader}
 * @param {YUI} Y
 * @param {Object} cfg Configuration variables
 * @param {Array} items An array containing grade items
 * @param {Array} users An array containing user information
 * @param {Array} feedback An array containing feedback information
 */
M.gradereport_grader.classes.report = function(Y, cfg, items, users, feedback, grades) {
    this.Y = Y;
    this.isediting = (cfg.isediting);
    this.ajaxenabled = (cfg.ajaxenabled);
    this.items = items;
    this.users = users;
    this.feedback = feedback;
    this.table = Y.one('#user-grades');
    this.grades = grades;

    // If ajax is enabled then initialise the ajax component
    if (this.ajaxenabled) {
        this.ajax = new M.gradereport_grader.classes.ajax(this, cfg);
    }
};
/**
 * Extend the report class with the following methods and properties
 */
M.gradereport_grader.classes.report.prototype.table = null;           // YUI Node for the reports main table
M.gradereport_grader.classes.report.prototype.items = [];             // Array containing grade items
M.gradereport_grader.classes.report.prototype.users = [];             // Array containing user information
M.gradereport_grader.classes.report.prototype.feedback = [];          // Array containing feedback items
M.gradereport_grader.classes.report.prototype.ajaxenabled = false;    // True is AJAX is enabled for the report
M.gradereport_grader.classes.report.prototype.ajax = null;            // An instance of the ajax class or null
/**
 * Builds an object containing information at the relevant cell given either
 * the cell to get information for or an array containing userid and itemid
 *
 * @function
 * @this {M.gradereport_grader}
 * @param {Y.Node|Array} arg Either a YUI Node instance or an array containing
 *                           the userid and itemid to reference
 * @return {Object}
 */
M.gradereport_grader.classes.report.prototype.get_cell_info = function(arg) {

    var userid= null;
    var itemid = null;
    var feedback = ''; // Don't default feedback to null or string comparisons become error prone
    var cell = null;
    var i = null;

    if (arg instanceof this.Y.Node) {
        if (arg.get('nodeName').toUpperCase() !== 'TD') {
            arg = arg.ancestor('td.cell');
        }
        var regexp = /^u(\d+)i(\d+)$/;
        var parts = regexp.exec(arg.getAttribute('id'));
        userid = parts[1];
        itemid = parts[2];
        cell = arg;
    } else {
        userid = arg[0];
        itemid = arg[1];
        cell = this.Y.one('#u'+userid+'i'+itemid);
    }

    if (!cell) {
        return null;
    }

    for (i in this.feedback) {
        if (this.feedback[i] && this.feedback[i].user == userid && this.feedback[i].item == itemid) {
            feedback = this.feedback[i].content;
            break;
        }
    }

    return {
        id : cell.getAttribute('id'),
        userid : userid,
        username : this.users[userid],
        itemid : itemid,
        itemname : this.items[itemid].name,
        itemtype : this.items[itemid].type,
        itemscale : this.items[itemid].scale,
        itemdp : this.items[itemid].decimals,
        feedback : feedback,
        cell : cell
    };
};
/**
 * Updates or creates the feedback JS structure for the given user/item
 *
 * @function
 * @this {M.gradereport_grader}
 * @param {Int} userid
 * @param {Int} itemid
 * @param {String} newfeedback
 * @return {Bool}
 */
M.gradereport_grader.classes.report.prototype.update_feedback = function(userid, itemid, newfeedback) {
    for (var i in this.feedback) {
        if (this.feedback[i].user == userid && this.feedback[i].item == itemid) {
            this.feedback[i].content = newfeedback;
            return true;
        }
    }
    this.feedback.push({user:userid,item:itemid,content:newfeedback});
    return true;
};
/**
 * Initialises the AJAX component of this report
 * @class ajax
 * @constructor
 * @this {M.gradereport_grader.ajax}
 * @param {M.gradereport_grader.classes.report} report
 * @param {Object} cfg
 */
M.gradereport_grader.classes.ajax = function(report, cfg) {
    this.report = report;
    this.courseid = cfg.courseid || null;
    this.feedbacktrunclength = cfg.feedbacktrunclength || null;
    this.studentsperpage = cfg.studentsperpage || null;
    this.showquickfeedback = cfg.showquickfeedback || false;
    this.scales = cfg.scales || null;
    this.existingfields = [];

    if (!report.isediting) {
        report.table.all('.clickable').on('click', this.make_editable, this);
    } else {
        for (var userid in report.users) {
            if (!this.existingfields[userid]) {
                this.existingfields[userid] = [];
            }
            for (var itemid in report.items) {
                this.existingfields[userid][itemid] = new M.gradereport_grader.classes.existingfield(this, userid, itemid);
            }
        }
        // Disable the Update button as we're saving using ajax.
        submitbutton = this.report.Y.one('#gradersubmit');
        submitbutton.set('disabled', true);
    }
};
/**
 * Extend the ajax class with the following methods and properties
 */
M.gradereport_grader.classes.ajax.prototype.report = null;                  // A reference to the report class this object will use
M.gradereport_grader.classes.ajax.prototype.courseid = null;                // The id for the course being viewed
M.gradereport_grader.classes.ajax.prototype.feedbacktrunclength = null;     // The length to truncate feedback to
M.gradereport_grader.classes.ajax.prototype.studentsperpage = null;         // The number of students shown per page
M.gradereport_grader.classes.ajax.prototype.showquickfeedback = null;       // True if feedback editing should be shown
M.gradereport_grader.classes.ajax.prototype.current = null;                 // The field being currently editing
M.gradereport_grader.classes.ajax.prototype.pendingsubmissions = [];        // Array containing pending IO transactions
M.gradereport_grader.classes.ajax.prototype.scales = [];                    // An array of scales used in this report
/**
 * Makes a cell editable
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 */
M.gradereport_grader.classes.ajax.prototype.make_editable = function(e) {
    var node = e;
    if (e.halt) {
        e.halt();
        node = e.target;
    }
    if (node.get('nodeName').toUpperCase() !== 'TD') {
        node = node.ancestor('td');
    }
    this.report.Y.detach('click', this.make_editable, node);

    if (this.current) {
        // Current is already set!
        this.process_editable_field(node);
        return;
    }

    // Sort out the field type
    var fieldtype = 'value';
    if (node.hasClass('grade_type_scale')) {
        fieldtype = 'scale';
    } else if (node.hasClass('grade_type_text')) {
        fieldtype = 'text';
    }
    // Create the appropriate field widget
    switch (fieldtype) {
        case 'scale':
            this.current = new M.gradereport_grader.classes.scalefield(this.report, node);
            break;
        case 'text':
            this.current = new M.gradereport_grader.classes.feedbackfield(this.report, node);
            break;
        default:
            this.current = new M.gradereport_grader.classes.textfield(this.report, node);
            break;
    }
    this.current.replace().attach_key_events();

    // Fire the global resized event for the gradereport_grader to update the table row/column sizes.
    Y.Global.fire('moodle-gradereport_grader:resized');
};
/**
 * Callback function for the user pressing the enter key on an editable field
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Event} e
 */
M.gradereport_grader.classes.ajax.prototype.keypress_enter = function(e) {
    this.process_editable_field(null);
};
/**
 * Callback function for the user pressing Tab or Shift+Tab
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Event} e
 * @param {Bool} ignoreshift If true and shift is pressed then don't exec
 */
M.gradereport_grader.classes.ajax.prototype.keypress_tab = function(e, ignoreshift) {
    e.preventDefault();
    var next = null;
    if (e.shiftKey) {
        if (ignoreshift) {
            return;
        }
        next = this.get_above_cell();
    } else {
        next = this.get_below_cell();
    }
    this.process_editable_field(next);
};
/**
 * Callback function for the user pressing an CTRL + an arrow key
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 */
M.gradereport_grader.classes.ajax.prototype.keypress_arrows = function(e) {
    e.preventDefault();
    var next = null;
    switch (e.keyCode) {
        case 37:    // Left
            next = this.get_prev_cell();
            break;
        case 38:    // Up
            next = this.get_above_cell();
            break;
        case 39:    // Right
            next = this.get_next_cell();
            break;
        case 40:    // Down
            next = this.get_below_cell();
            break;
    }
    this.process_editable_field(next);
};
/**
 * Processes an editable field an does what ever is required to update it
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Y.Node|null} next The next node to make editable (chaining)
 */
M.gradereport_grader.classes.ajax.prototype.process_editable_field = function(next) {
    if (this.current.has_changed()) {
        var properties = this.report.get_cell_info(this.current.node);
        var values = this.current.commit();
        this.current.revert();
        this.submit(properties, values);
    } else {
        this.current.revert();
    }
    this.current = null;
    if (next) {
        this.make_editable(next, null);
    }

    // Fire the global resized event for the gradereport_grader to update the table row/column sizes.
    Y.Global.fire('moodle-gradereport_grader:resized');
};
/**
 * Gets the next cell that is editable (right)
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Y.Node} cell
 * @return {Y.Node}
 */
M.gradereport_grader.classes.ajax.prototype.get_next_cell = function(cell) {
    var n = cell || this.current.node;
    var next = n.next('td');
    var tr = null;
    if (!next && (tr = n.ancestor('tr').next('tr'))) {
        next = tr.all('.grade').item(0);
    }
    if (!next) {
        return this.current.node;
    }
    // Continue on until we find a navigable cell
    if (!next.hasClass('gbnavigable')) {
        return this.get_next_cell(next);
    }
    return next;
};
/**
 * Gets the previous cell that is editable (left)
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Y.Node} cell
 * @return {Y.Node}
 */
M.gradereport_grader.classes.ajax.prototype.get_prev_cell = function(cell) {
    var n = cell || this.current.node;
    var next = n.previous('.grade');
    var tr = null;
    if (!next && (tr = n.ancestor('tr').previous('tr'))) {
        var cells = tr.all('.grade');
        next = cells.item(cells.size()-1);
    }
    if (!next) {
        return this.current.node;
    }
    // Continue on until we find a navigable cell
    if (!next.hasClass('gbnavigable')) {
        return this.get_prev_cell(next);
    }
    return next;
};
/**
 * Gets the cell above if it is editable (up)
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Y.Node} cell
 * @return {Y.Node}
 */
M.gradereport_grader.classes.ajax.prototype.get_above_cell = function(cell) {
    var n = cell || this.current.node;
    var tr = n.ancestor('tr').previous('tr');
    var next = null;
    if (tr) {
        var column = 0;
        var ntemp = n;
        while (ntemp = ntemp.previous('td.cell')) {
            column++;
        }
        next = tr.all('td.cell').item(column);
    }
    if (!next) {
        return this.current.node;
    }
    // Continue on until we find a navigable cell
    if (!next.hasClass('gbnavigable')) {
        return this.get_above_cell(next);
    }
    return next;
};
/**
 * Gets the cell below if it is editable (down)
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Y.Node} cell
 * @return {Y.Node}
 */
M.gradereport_grader.classes.ajax.prototype.get_below_cell = function(cell) {
    var n = cell || this.current.node;
    var tr = n.ancestor('tr').next('tr');
    var next = null;
    if (tr && !tr.hasClass('avg')) {
        var column = 0;
        var ntemp = n;
        while (ntemp = ntemp.previous('td.cell')) {
            column++;
        }
        next = tr.all('td.cell').item(column);
    }
    if (!next) {
        return this.current.node;
    }
    // Continue on until we find a navigable cell
    if (!next.hasClass('gbnavigable')) {
        return this.get_below_cell(next);
    }
    return next;
};
/**
 * Submits changes for update
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Object} properties Properties of the cell being edited
 * @param {Object} values Object containing old + new values
 */
M.gradereport_grader.classes.ajax.prototype.submit = function(properties, values) {
    // Stop the IO queue so we can add to it
    this.report.Y.io.queue.stop();
    // If the grade has changed add an IO transaction to update it to the queue
    if (values.grade !== values.oldgrade) {
        this.pendingsubmissions.push({transaction:this.report.Y.io.queue(M.cfg.wwwroot+'/grade/report/grader/ajax_callbacks.php', {
            method : 'POST',
            data : 'id='+this.courseid+'&userid='+properties.userid+'&itemid='+properties.itemid+'&action=update&newvalue='+values.grade+'&type='+properties.itemtype+'&sesskey='+M.cfg.sesskey,
            on : {
                complete : this.submission_outcome
            },
            context : this,
            arguments : {
                properties : properties,
                values : values,
                type : 'grade'
            }
        }),complete:false,outcome:null});
    }
    // If feedback is editable and has changed add to the IO queue for it
    if (values.editablefeedback && values.feedback !== values.oldfeedback) {
        values.feedback = encodeURIComponent(values.feedback);
        this.pendingsubmissions.push({transaction:this.report.Y.io.queue(M.cfg.wwwroot+'/grade/report/grader/ajax_callbacks.php', {
            method : 'POST',
            data : 'id='+this.courseid+'&userid='+properties.userid+'&itemid='+properties.itemid+'&action=update&newvalue='+values.feedback+'&type=feedback&sesskey='+M.cfg.sesskey,
            on : {
                complete : this.submission_outcome
            },
            context : this,
            arguments : {
                properties : properties,
                values : values,
                type : 'feedback'
            }
        }),complete:false,outcome:null});
    }
    // Process the IO queue
    this.report.Y.io.queue.start();
};
/**
 * Callback function for IO transaction completions
 *
 * Uses a synchronous queue to ensure we maintain some sort of order
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {Int} tid Transaction ID
 * @param {Object} outcome
 * @param {Mixed} args
 */
M.gradereport_grader.classes.ajax.prototype.submission_outcome = function(tid, outcome, args) {
    // Parse the response as JSON
    try {
        outcome = this.report.Y.JSON.parse(outcome.responseText);
    } catch(e) {
        var message = M.util.get_string('ajaxfailedupdate', 'gradereport_grader');
        message = message.replace(/\[1\]/, args.type);
        message = message.replace(/\[2\]/, this.report.users[args.properties.userid]);

        this.display_submission_error(message, args.properties.cell);
        return;
    }

    // Quick reference for the grader report
    var i = null;
    // Check the outcome
    if (outcome.result == 'success') {
        // Iterate through each row in the result object
        for (i in outcome.row) {
            if (outcome.row[i] && outcome.row[i].userid && outcome.row[i].itemid) {
                // alias it, we use it quite a bit
                var r = outcome.row[i];
                // Get the cell referred to by this result object
                var info = this.report.get_cell_info([r.userid, r.itemid]);
                if (!info) {
                    continue;
                }
                // Calculate the final grade for the cell
                var finalgrade = '';
                var scalegrade = -1;
                if (!r.finalgrade) {
                    if (this.report.isediting) {
                        // In edit mode don't put hyphens in the grade text boxes
                        finalgrade = '';
                    } else {
                        // In non-edit mode put a hyphen in the grade cell
                        finalgrade = '-';
                    }
                } else {
                    if (r.scale) {
                        scalegrade = parseFloat(r.finalgrade);
                        finalgrade = this.scales[r.scale][scalegrade-1];
                    } else {
                        finalgrade = parseFloat(r.finalgrade).toFixed(info.itemdp);
                    }
                }
                if (this.report.isediting) {
                    var grade = info.cell.one('#grade_'+r.userid+'_'+r.itemid);
                    if (grade) {
                        // This means the item has a input element to update.
                        var parent = grade.ancestor('td');
                        if (parent.hasClass('grade_type_scale')) {
                            grade.all('option').each(function(option) {
                                if (option.get('value') == scalegrade) {
                                    option.setAttribute('selected', 'selected');
                                } else {
                                    option.removeAttribute('selected');
                                }
                            });
                        } else {
                            grade.set('value', finalgrade);
                        }
                    } else if (info.cell.one('.gradevalue')) {
                        // This means we are updating a value for something without editing boxed (locked, etc).
                        info.cell.one('.gradevalue').set('innerHTML', finalgrade);
                    }
                } else {
                    // If there is no currently editing field or if this cell is not being currently edited
                    if (!this.current || info.cell.get('id') != this.current.node.get('id')) {
                        // Update the value
                        var node = info.cell.one('.gradevalue');
                        var td = node.ancestor('td');
                        // Only scale and value type grades should have their content updated in this way.
                        if (td.hasClass('grade_type_value') || td.hasClass('grade_type_scale')) {
                            node.set('innerHTML', finalgrade);
                        }
                    } else if (this.current && info.cell.get('id') == this.current.node.get('id')) {
                        // If we are here the grade value of the cell currently being edited has changed !!!!!!!!!
                        // If the user has not actually changed the old value yet we will automatically correct it
                        // otherwise we will prompt the user to choose to use their value or the new value!
                        if (!this.current.has_changed() || confirm(M.util.get_string('ajaxfieldchanged', 'gradereport_grader'))) {
                            this.current.set_grade(finalgrade);
                            if (this.current.grade) {
                                this.current.grade.set('value', finalgrade);
                            }
                        }
                    }
                }
            }
        }
        // Flag the changed cell as overridden by ajax
        args.properties.cell.addClass('ajaxoverridden');
    } else {
        var p = args.properties;
        if (args.type == 'grade') {
            var oldgrade = args.values.oldgrade;
            p.cell.one('.gradevalue').set('innerHTML',oldgrade);
        } else if (args.type == 'feedback') {
            this.report.update_feedback(p.userid, p.itemid, args.values.oldfeedback);
        }
        this.display_submission_error(outcome.message, p.cell);
    }
    // Check if all IO transactions in the queue are complete yet
    var allcomplete = true;
    for (i in this.pendingsubmissions) {
        if (this.pendingsubmissions[i]) {
            if (this.pendingsubmissions[i].transaction.id == tid) {
                this.pendingsubmissions[i].complete = true;
                this.pendingsubmissions[i].outcome = outcome;
                this.report.Y.io.queue.remove(this.pendingsubmissions[i].transaction);
            }
            if (!this.pendingsubmissions[i].complete) {
                allcomplete = false;
            }
        }
    }
    if (allcomplete) {
        this.pendingsubmissions = [];
    }
};
/**
 * Displays a submission error within a overlay on the cell that failed update
 *
 * @function
 * @this {M.gradereport_grader.classes.ajax}
 * @param {String} message
 * @param {Y.Node} cell
 */
M.gradereport_grader.classes.ajax.prototype.display_submission_error = function(message, cell) {
    var erroroverlay = new this.report.Y.Overlay({
        headerContent : '<div><strong class="error">'+M.util.get_string('ajaxerror', 'gradereport_grader')+'</strong>  <em>'+M.util.get_string('ajaxclicktoclose', 'gradereport_grader')+'</em></div>',
        bodyContent : message,
        visible : false,
        zIndex : 3
    });
    erroroverlay.set('xy', [cell.getX()+10,cell.getY()+10]);
    erroroverlay.render(this.report.table.ancestor('div'));
    erroroverlay.show();
    erroroverlay.get('boundingBox').on('click', function(){
        this.get('boundingBox').setStyle('visibility', 'hidden');
        this.hide();
        this.destroy();
    }, erroroverlay);
    erroroverlay.get('boundingBox').setStyle('visibility', 'visible');
};
/**
 * A class for existing fields
 * This class is used only when the user is in editing mode
 *
 * @class existingfield
 * @constructor
 * @param {M.gradereport_grader.classes.report} report
 * @param {Int} userid
 * @param {Int} itemid
 */
M.gradereport_grader.classes.existingfield = function(ajax, userid, itemid) {
    this.report = ajax.report;
    this.userid = userid;
    this.itemid = itemid;
    this.editfeedback = ajax.showquickfeedback;
    this.grade = this.report.Y.one('#grade_'+userid+'_'+itemid);

    var i = 0;
    if (this.grade) {
        for (i = 0; i < this.report.grades.length; i++) {
            if (this.report.grades[i]['user'] == this.userid && this.report.grades[i]['item'] == this.itemid) {
                this.oldgrade = this.report.grades[i]['grade'];
            }
        }

        if (!this.oldgrade) {
            // Assigning an empty string makes determining whether the grade has been changed easier
            // This value is never sent to the server
            this.oldgrade = '';
        }

        // On blur save any changes in the grade field
        this.grade.on('blur', this.submit, this);
    }

    // Check if feedback is enabled
    if (this.editfeedback) {
        // Get the feedback fields
        this.feedback = this.report.Y.one('#feedback_'+userid+'_'+itemid);

        if (this.feedback) {
            for(i = 0; i < this.report.feedback.length; i++) {
                if (this.report.feedback[i]['user'] == this.userid && this.report.feedback[i]['item'] == this.itemid) {
                    this.oldfeedback = this.report.feedback[i]['content'];
                }
            }

            if(!this.oldfeedback) {
                // Assigning an empty string makes determining whether the feedback has been changed easier
                // This value is never sent to the server
                this.oldfeedback = '';
            }

            // On blur save any changes in the feedback field
            this.feedback.on('blur', this.submit, this);

            // Override the default tab movements when moving between cells
            // Handle Tab.
            this.keyevents.push(this.report.Y.on('key', this.keypress_tab, this.feedback, 'press:9', this, true));
            // Handle the Enter key being pressed.
            this.keyevents.push(this.report.Y.on('key', this.keypress_enter, this.feedback, 'press:13', this));
            // Handle CTRL + arrow keys.
            this.keyevents.push(this.report.Y.on('key', this.keypress_arrows, this.feedback, 'press:37,38,39,40+ctrl', this));

            if (this.grade) {
                // Override the default tab movements when moving between cells
                // Handle Shift+Tab.
                this.keyevents.push(this.report.Y.on('key', this.keypress_tab, this.grade, 'press:9+shift', this));

                // Override the default tab movements for fields in the same cell
                this.keyevents.push(this.report.Y.on('key',
                        function(e){e.preventDefault();this.grade.focus();},
                        this.feedback,
                        'press:9+shift',
                        this));
                this.keyevents.push(this.report.Y.on('key',
                        function(e){if (e.shiftKey) {return;}e.preventDefault();this.feedback.focus();},
                        this.grade,
                        'press:9',
                        this));
            }
        }
    } else if (this.grade) {
        // Handle Tab and Shift+Tab.
        this.keyevents.push(this.report.Y.on('key', this.keypress_tab, this.grade, 'down:9', this));
    }
    if (this.grade) {
        // Handle the Enter key being pressed.
        this.keyevents.push(this.report.Y.on('key', this.keypress_enter, this.grade, 'up:13', this));
        // Handle CTRL + arrow keys.
        this.keyevents.push(this.report.Y.on('key', this.keypress_arrows, this.grade, 'down:37,38,39,40+ctrl', this));
    }
};
/**
 * Attach the required properties and methods to the existing field class
 * via prototyping
 */
M.gradereport_grader.classes.existingfield.prototype.userid = null;
M.gradereport_grader.classes.existingfield.prototype.itemid = null;
M.gradereport_grader.classes.existingfield.prototype.editfeedback = false;
M.gradereport_grader.classes.existingfield.prototype.grade = null;
M.gradereport_grader.classes.existingfield.prototype.oldgrade = null;
M.gradereport_grader.classes.existingfield.prototype.keyevents = [];
/**
 * Handles saving of changed on keypress
 *
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 * @param {Event} e
 */
M.gradereport_grader.classes.existingfield.prototype.keypress_enter = function(e) {
    e.preventDefault();
    this.submit();
};
/**
 * Handles setting the correct focus if the user presses tab
 *
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 * @param {Event} e
 * @param {Bool} ignoreshift
 */
M.gradereport_grader.classes.existingfield.prototype.keypress_tab = function(e, ignoreshift) {
    e.preventDefault();
    var next = null;
    if (e.shiftKey) {
        if (ignoreshift) {
            return;
        }
        next = this.report.ajax.get_above_cell(this.grade.ancestor('td'));
    } else {
        next = this.report.ajax.get_below_cell(this.grade.ancestor('td'));
    }
    this.move_focus(next);
};
/**
 * Handles setting the correct focus when the user presses CTRL+arrow keys
 *
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 * @param {Event} e
 */
M.gradereport_grader.classes.existingfield.prototype.keypress_arrows = function(e) {
    e.preventDefault();
    var next = null;
    switch (e.keyCode) {
        case 37:    // Left
            next = this.report.ajax.get_prev_cell(this.grade.ancestor('td'));
            break;
        case 38:    // Up
            next = this.report.ajax.get_above_cell(this.grade.ancestor('td'));
            break;
        case 39:    // Right
            next = this.report.ajax.get_next_cell(this.grade.ancestor('td'));
            break;
        case 40:    // Down
            next = this.report.ajax.get_below_cell(this.grade.ancestor('td'));
            break;
    }
    this.move_focus(next);
};
/**
 * Move the focus to the node
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 * @param {Y.Node} node
 */
M.gradereport_grader.classes.existingfield.prototype.move_focus = function(node) {
    if (node) {
        var properties = this.report.get_cell_info(node);
        this.report.ajax.current = node;
        switch(properties.itemtype) {
            case 'scale':
                properties.cell.one('select.select').focus();
                break;
            case 'value':
            default:
                properties.cell.one('input.text').focus();
                break;
        }
    }
};
/**
 * Checks if the values for the field have changed
 *
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 * @return {Bool}
 */
M.gradereport_grader.classes.existingfield.prototype.has_changed = function() {
    if (this.grade) {
        if (this.grade.get('value') !== this.oldgrade) {
            return true;
        }
    }
    if (this.editfeedback && this.feedback) {
        if (this.feedback.get('value') !== this.oldfeedback) {
            return true;
        }
    }
    return false;
};
/**
 * Submits any changes and then updates the fields accordingly
 *
 * @function
 * @this {M.gradereport_grader.classes.existingfield}
 */
M.gradereport_grader.classes.existingfield.prototype.submit = function() {
    if (!this.has_changed()) {
        return;
    }

    var properties = this.report.get_cell_info([this.userid,this.itemid]);
    var values = (function(f){
        var feedback, oldfeedback, grade, oldgrade = null;
        if (f.editfeedback && f.feedback) {
            feedback = f.feedback.get('value');
            oldfeedback = f.oldfeedback;
        }
        if (f.grade) {
            grade = f.grade.get('value');
            oldgrade = f.oldgrade;
        }
        return {
            editablefeedback : f.editfeedback,
            grade : grade,
            oldgrade : oldgrade,
            feedback : feedback,
            oldfeedback : oldfeedback
        };
    })(this);

    this.oldgrade = values.grade;
    if (values.editablefeedback && values.feedback != values.oldfeedback) {
        this.report.update_feedback(this.userid, this.itemid, values.feedback);
        this.oldfeedback = values.feedback;
    }

    this.report.ajax.submit(properties, values);
};

/**
 * Textfield class
 * This classes gets used in conjunction with the report running with AJAX enabled
 * and is used to manage a cell that has a grade requiring a textfield for input
 *
 * @class textfield
 * @constructor
 * @this {M.gradereport_grader.classes.textfield}
 * @param {M.gradereport_grader.classes.report} report
 * @param {Y.Node} node
 */
M.gradereport_grader.classes.textfield = function(report, node) {
    this.report = report;
    this.node = node;
    this.gradespan = node.one('.gradevalue');
    this.inputdiv = this.report.Y.Node.create('<div></div>');
    this.editfeedback = this.report.ajax.showquickfeedback;
    this.grade = this.report.Y.Node.create('<input type="text" class="text" value="" name="ajaxgrade" />');
    this.gradetype = 'value';
    this.inputdiv.append(this.grade);
    if (this.report.ajax.showquickfeedback) {
        this.feedback = this.report.Y.Node.create('<input type="text" class="quickfeedback" value="" name="ajaxfeedback" />');
        this.inputdiv.append(this.feedback);
    }
};
/**
 * Extend the textfield class with the following methods and properties
 */
M.gradereport_grader.classes.textfield.prototype.keyevents = [];
M.gradereport_grader.classes.textfield.prototype.editable = false;
M.gradereport_grader.classes.textfield.prototype.gradetype = null;
M.gradereport_grader.classes.textfield.prototype.grade = null;
M.gradereport_grader.classes.textfield.prototype.report = null;
M.gradereport_grader.classes.textfield.prototype.node = null;
M.gradereport_grader.classes.textfield.prototype.gradespam = null;
M.gradereport_grader.classes.textfield.prototype.inputdiv = null;
M.gradereport_grader.classes.textfield.prototype.editfeedback = false;
/**
 * Replaces the cell contents with the controls to enable editing
 *
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @return {M.gradereport_grader.classes.textfield}
 */
M.gradereport_grader.classes.textfield.prototype.replace = function() {
    this.set_grade(this.get_grade());
    if (this.editfeedback) {
        this.set_feedback(this.get_feedback());
    }
    this.node.replaceChild(this.inputdiv, this.gradespan);
    if (this.grade) {
        this.grade.focus();
    } else if (this.feedback) {
        this.feedback.focus();
    }
    this.editable = true;
    return this;
};
/**
 * Commits the changes within a cell and returns a result object of new + old values
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @return {Object}
 */
M.gradereport_grader.classes.textfield.prototype.commit = function() {
    // Produce an anonymous result object contianing all values
    var result = (function(field){
        // Editable false lets us get the pre-update values.
        field.editable = false;
        var oldgrade = field.get_grade();
        if (oldgrade == '-') {
            oldgrade = '';
        }
        var feedback = null;
        var oldfeedback = null;
        if (field.editfeedback) {
            oldfeedback = field.get_feedback();
        }

        // Now back to editable gives us the values in the edit areas.
        field.editable = true;
        if (field.editfeedback) {
            feedback = field.get_feedback();
        }
        return {
            gradetype : field.gradetype,
            editablefeedback : field.editfeedback,
            grade : field.get_grade(),
            oldgrade : oldgrade,
            feedback : feedback,
            oldfeedback : oldfeedback
        };
    })(this);
    // Set the changes in stone
    this.set_grade(result.grade);
    if (this.editfeedback) {
        this.set_feedback(result.feedback);
    }
    // Return the result object
    return result;
};
/**
 * Reverts a cell back to its static contents
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 */
M.gradereport_grader.classes.textfield.prototype.revert = function() {
    this.node.replaceChild(this.gradespan, this.inputdiv);
    for (var i in this.keyevents) {
        if (this.keyevents[i]) {
            this.keyevents[i].detach();
        }
    }
    this.keyevents = [];
    this.node.on('click', this.report.ajax.make_editable, this.report.ajax);
};
/**
 * Gets the grade for current cell
 *
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @return {Mixed}
 */
M.gradereport_grader.classes.textfield.prototype.get_grade = function() {
    if (this.editable) {
        return this.grade.get('value');
    }
    return this.gradespan.get('innerHTML');
};
/**
 * Sets the grade for the current cell
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @param {Mixed} value
 */
M.gradereport_grader.classes.textfield.prototype.set_grade = function(value) {
    if (!this.editable) {
        if (value == '-') {
            value = '';
        }
        this.grade.set('value', value);
    } else {
        if (value == '') {
            value = '-';
        }
        this.gradespan.set('innerHTML', value);
    }
};
/**
 * Gets the feedback for the current cell
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @return {String}
 */
M.gradereport_grader.classes.textfield.prototype.get_feedback = function() {
    if (this.editable) {
        if (this.feedback) {
            return this.feedback.get('value');
        } else {
            return null;
        }
    }
    var properties = this.report.get_cell_info(this.node);
    if (properties) {
        return properties.feedback;
    }
    return '';
};
/**
 * Sets the feedback for the current cell
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @param {Mixed} value
 */
M.gradereport_grader.classes.textfield.prototype.set_feedback = function(value) {
    if (!this.editable) {
        if (this.feedback) {
            this.feedback.set('value', value);
        }
    } else {
        var properties = this.report.get_cell_info(this.node);
        this.report.update_feedback(properties.userid, properties.itemid, value);
    }
};
/**
 * Checks if the current cell has changed at all
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 * @return {Bool}
 */
M.gradereport_grader.classes.textfield.prototype.has_changed = function() {
    // If its not editable it has not changed
    if (!this.editable) {
        return false;
    }
    // If feedback is being edited then it has changed if either grade or feedback have changed
    if (this.editfeedback) {
        var properties = this.report.get_cell_info(this.node);
        if (this.get_feedback() != properties.feedback) {
            return true;
        }
    }

    if (this.grade) {
        return (this.get_grade() != this.gradespan.get('innerHTML'));
    } else {
        return false;
    }
};
/**
 * Attaches the key listeners for the editable fields and stored the event references
 * against the textfield
 *
 * @function
 * @this {M.gradereport_grader.classes.textfield}
 */
M.gradereport_grader.classes.textfield.prototype.attach_key_events = function() {
    var a = this.report.ajax;
    // Setup the default key events for tab and enter
    if (this.editfeedback) {
        if (this.grade) {
            // Handle Shift+Tab.
            this.keyevents.push(this.report.Y.on('key', a.keypress_tab, this.grade, 'down:9+shift', a));
        }
        // Handle Tab.
        this.keyevents.push(this.report.Y.on('key', a.keypress_tab, this.feedback, 'down:9', a, true));
        // Handle the Enter key being pressed.
        this.keyevents.push(this.report.Y.on('key', a.keypress_enter, this.feedback, 'up:13', a));
    } else {
        if (this.grade) {
            // Handle Tab and Shift+Tab.
            this.keyevents.push(this.report.Y.on('key', a.keypress_tab, this.grade, 'down:9', a));
        }
    }

    // Setup the arrow key events.
    // Handle CTRL + arrow keys.
    this.keyevents.push(this.report.Y.on('key', a.keypress_arrows, this.inputdiv.ancestor('td'), 'down:37,38,39,40+ctrl', a));

    if (this.grade) {
        // Handle the Enter key being pressed.
        this.keyevents.push(this.report.Y.on('key', a.keypress_enter, this.grade, 'up:13', a));
        // Prevent the default key action on all fields for arrow keys on all key events!
        // Note: this still does not work in FF!!!!!
        this.keyevents.push(this.report.Y.on('key', function(e){e.preventDefault();}, this.grade, 'down:37,38,39,40+ctrl'));
        this.keyevents.push(this.report.Y.on('key', function(e){e.preventDefault();}, this.grade, 'press:37,38,39,40+ctrl'));
        this.keyevents.push(this.report.Y.on('key', function(e){e.preventDefault();}, this.grade, 'up:37,38,39,40+ctrl'));
    }
};

/**
 * Feedback field class
 * This classes gets used in conjunction with the report running with AJAX enabled
 * and is used to manage a cell that no editable grade, only possibly feedback
 *
 * @class feedbackfield
 * @constructor
 * @this {M.gradereport_grader.classes.feedbackfield}
 * @param {M.gradereport_grader.classes.report} report
 * @param {Y.Node} node
 */
M.gradereport_grader.classes.feedbackfield = function(report, node) {
    this.report = report;
    this.node = node;
    this.gradespan = node.one('.gradevalue');
    this.inputdiv = this.report.Y.Node.create('<div></div>');
    this.editfeedback = this.report.ajax.showquickfeedback;
    this.gradetype = 'text';
    if (this.report.ajax.showquickfeedback) {
        this.feedback = this.report.Y.Node.create('<input type="text" class="quickfeedback" value="" name="ajaxfeedback" />');
        this.inputdiv.append(this.feedback);
    }
};

/**
 * Gets the grade for current cell (which will always be null)
 *
 * @function
 * @this {M.gradereport_grader.classes.feedbackfield}
 * @return {Mixed}
 */
M.gradereport_grader.classes.feedbackfield.prototype.get_grade = function() {
    return null;
};

/**
 * Overrides the set_grade function of textfield so that it can ignore the set-grade
 * for grade cells without grades
 *
 * @function
 * @this {M.gradereport_grader.classes.feedbackfield}
 * @param {String} value
 */
M.gradereport_grader.classes.feedbackfield.prototype.set_grade = function() {
    return;
};

/**
 * Manually extend the feedbackfield class with the properties and methods of the
 * textfield class that have not been defined
 */
for (var i in M.gradereport_grader.classes.textfield.prototype) {
    if (!M.gradereport_grader.classes.feedbackfield.prototype[i]) {
        M.gradereport_grader.classes.feedbackfield.prototype[i] = M.gradereport_grader.classes.textfield.prototype[i];
    }
}

/**
 * An editable scale field
 *
 * @class scalefield
 * @constructor
 * @inherits M.gradereport_grader.classes.textfield
 * @base M.gradereport_grader.classes.textfield
 * @this {M.gradereport_grader.classes.scalefield}
 * @param {M.gradereport_grader.classes.report} report
 * @param {Y.Node} node
 */
M.gradereport_grader.classes.scalefield = function(report, node) {
    this.report = report;
    this.node = node;
    this.gradespan = node.one('.gradevalue');
    this.inputdiv = this.report.Y.Node.create('<div></div>');
    this.editfeedback = this.report.ajax.showquickfeedback;
    this.grade = this.report.Y.Node.create('<select type="text" class="text" name="ajaxgrade" /><option value="-1">'+
            M.util.get_string('ajaxchoosescale', 'gradereport_grader')+'</option></select>');
    this.gradetype = 'scale';
    this.inputdiv.append(this.grade);
    if (this.editfeedback) {
        this.feedback = this.report.Y.Node.create('<input type="text" class="quickfeedback" value="" name="ajaxfeedback"/>');
        this.inputdiv.append(this.feedback);
    }
    var properties = this.report.get_cell_info(node);
    this.scale = this.report.ajax.scales[properties.itemscale];
    for (var i in this.scale) {
        if (this.scale[i]) {
            this.grade.append(this.report.Y.Node.create('<option value="'+(parseFloat(i)+1)+'">'+this.scale[i]+'</option>'));
        }
    }
};
/**
 * Override + extend the scalefield class with the following properties
 * and methods
 */
/**
 * @property {Array} scale
 */
M.gradereport_grader.classes.scalefield.prototype.scale = [];
/**
 * Extend the scalefield with the functions from the textfield
 */
/**
 * Overrides the get_grade function so that it can pick up the value from the
 * scales select box
 *
 * @function
 * @this {M.gradereport_grader.classes.scalefield}
 * @return {Int} the scale id
 */
M.gradereport_grader.classes.scalefield.prototype.get_grade = function(){
    if (this.editable) {
        // Return the scale value
        return this.grade.all('option').item(this.grade.get('selectedIndex')).get('value');
    } else {
        // Return the scale values id
        var value = this.gradespan.get('innerHTML');
        for (var i in this.scale) {
            if (this.scale[i] == value) {
                return parseFloat(i)+1;
            }
        }
        return -1;
    }
};
/**
 * Overrides the set_grade function of textfield so that it can set the scale
 * within the scale select box
 *
 * @function
 * @this {M.gradereport_grader.classes.scalefield}
 * @param {String} value
 */
M.gradereport_grader.classes.scalefield.prototype.set_grade = function(value) {
    if (!this.editable) {
        if (value == '-') {
            value = '-1';
        }
        this.grade.all('option').each(function(node){
            if (node.get('value') == value) {
                node.set('selected', true);
            }
        });
    } else {
        if (value == '' || value == '-1') {
            value = '-';
        } else {
            value = this.scale[parseFloat(value)-1];
        }
        this.gradespan.set('innerHTML', value);
    }
};
/**
 * Checks if the current cell has changed at all
 * @function
 * @this {M.gradereport_grader.classes.scalefield}
 * @return {Bool}
 */
M.gradereport_grader.classes.scalefield.prototype.has_changed = function() {
    if (!this.editable) {
        return false;
    }
    var gradef = this.get_grade();
    this.editable = false;
    var gradec = this.get_grade();
    this.editable = true;
    if (this.editfeedback) {
        var properties = this.report.get_cell_info(this.node);
        var feedback = properties.feedback;
        return (gradef != gradec || this.get_feedback() != feedback);
    }
    return (gradef != gradec);
};

/**
 * Manually extend the scalefield class with the properties and methods of the
 * textfield class that have not been defined
 */
for (var i in M.gradereport_grader.classes.textfield.prototype) {
    if (!M.gradereport_grader.classes.scalefield.prototype[i]) {
        M.gradereport_grader.classes.scalefield.prototype[i] = M.gradereport_grader.classes.textfield.prototype[i];
    }
}
