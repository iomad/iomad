YUI.add('moodle-availability_iomadcoursecompletion-form', function (Y, NAME) {

/**
 * JavaScript for form editing previously completed course conditions.
 *
 * @module moodle-availability_iomadcoursecompletion-form
 */
M.availability_iomadcoursecompletion = M.availability_iomadcoursecompletion || {};

/**
 * @class M.availability_iomadcoursecompletion.form
 * @extends M.core_availability.plugin
 */
M.availability_iomadcoursecompletion.form = Y.Object(M.core_availability.plugin);

/**
 * Groups available for selection (alphabetical order).
 *
 * @property courses
 * @type Array
 */
M.availability_iomadcoursecompletion.form.courses = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} courses Array of objects containing courseid => name
 */
M.availability_iomadcoursecompletion.form.initInner = function(courses) {
    this.courses = courses;
};

M.availability_iomadcoursecompletion.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<label><span class="pr-3">' + M.util.get_string('title', 'availability_iomadcoursecompletion') + '</span> ' +
            '<span class="availability-iomadcoursecompletion">' +
            '<select name="id" class="custom-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>' +
            '<option value="any">' + M.util.get_string('anycourse', 'availability_iomadcoursecompletion') + '</option>';
    for (var i = 0; i < this.courses.length; i++) {
        var course = this.courses[i];
        // String has already been escaped using format_string.
        html += '<option value="' + course.id + '">' + course.name + '</option>';
    }
    html += '</select></span></label> <br><span class="availability-group mb-3">' +
            '<label><input type="checkbox" class="form-check-input position-static mt-0 mx-1" name="indate"/>' +
            M.util.get_string('validoption', 'availability_iomadcoursecompletion') +
            '</label></span>';


    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined &&
                node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', '' + json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'any');
        }
        if (json.indate !== undefined &&
            json.indate == 1) {
            node.one('input[name=indate]').set('checked', true);
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_iomadcoursecompletion.form.addedEvents) {
        M.availability_iomadcoursecompletion.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_iomadcoursecompletion select');

        root.delegate('click', function() {
            M.core_availability.form.update();
        }, '.availability_iomadcoursecompletion input[type=checkbox]');

    }

    return node;
};

M.availability_iomadcoursecompletion.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = 'choose';
    } else if (selected !== 'any') {
        value.id = parseInt(selected, 10);
    }
    if (node.one('input[name=indate]').get('checked')) {
        value.indate = 1;
    } else {
        value.indate = 0;
    }
};

M.availability_iomadcoursecompletion.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check course item id.
    if (value.id && value.id === 'choose') {
        errors.push('availability_iomadcoursecompletion:error_selectcourse');
    }
};

}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
