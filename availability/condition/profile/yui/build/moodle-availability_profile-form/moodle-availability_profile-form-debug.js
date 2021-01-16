YUI.add('moodle-availability_profile-form', function (Y, NAME) {

/**
 * JavaScript for form editing profile conditions.
 *
 * @module moodle-availability_profile-form
 */
M.availability_profile = M.availability_profile || {};

/**
 * @class M.availability_profile.form
 * @extends M.core_availability.plugin
 */
M.availability_profile.form = Y.Object(M.core_availability.plugin);

/**
 * Groupings available for selection (alphabetical order).
 *
 * @property profiles
 * @type Array
 */
M.availability_profile.form.profiles = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} standardFields Array of objects with .field, .display
 * @param {Array} customFields Array of objects with .field, .display
 */
M.availability_profile.form.initInner = function(standardFields, customFields) {
    this.standardFields = standardFields;
    this.customFields = customFields;
};

M.availability_profile.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="availability-group"><label><span class="p-r-1">' +
            M.util.get_string('conditiontitle', 'availability_profile') + '</span> ' +
            '<select name="field" class="custom-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    var fieldInfo;
    for (var i = 0; i < this.standardFields.length; i++) {
        fieldInfo = this.standardFields[i];
        // String has already been escaped using format_string.
        html += '<option value="sf_' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    for (i = 0; i < this.customFields.length; i++) {
        fieldInfo = this.customFields[i];
        // String has already been escaped using format_string.
        html += '<option value="cf_' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label> <label><span class="accesshide">' + M.util.get_string('label_operator', 'availability_profile') +
            ' </span><select name="op" title="' + M.util.get_string('label_operator', 'availability_profile') + '"' +
                     ' class="custom-select">';
    var operators = ['isequalto', 'contains', 'doesnotcontain', 'startswith', 'endswith',
            'isempty', 'isnotempty'];
    for (i = 0; i < operators.length; i++) {
        html += '<option value="' + operators[i] + '">' +
                M.util.get_string('op_' + operators[i], 'availability_profile') + '</option>';
    }
    html += '</select></label> <label><span class="accesshide">' + M.util.get_string('label_value', 'availability_profile') +
            '</span><input name="value" type="text" class="form-control" style="width: 10em" title="' +
            M.util.get_string('label_value', 'availability_profile') + '"/></label></span>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values if specified.
    if (json.sf !== undefined &&
            node.one('select[name=field] > option[value=sf_' + json.sf + ']')) {
        node.one('select[name=field]').set('value', 'sf_' + json.sf);
    } else if (json.cf !== undefined &&
            node.one('select[name=field] > option[value=cf_' + json.cf + ']')) {
        node.one('select[name=field]').set('value', 'cf_' + json.cf);
    }
    if (json.op !== undefined &&
            node.one('select[name=op] > option[value=' + json.op + ']')) {
        node.one('select[name=op]').set('value', json.op);
        if (json.op === 'isempty' || json.op === 'isnotempty') {
            node.one('input[name=value]').set('disabled', true);
        }
    }
    if (json.v !== undefined) {
        node.one('input').set('value', json.v);
    }

    // Add event handlers (first time only).
    if (!M.availability_profile.form.addedEvents) {
        M.availability_profile.form.addedEvents = true;
        var updateForm = function(input) {
            var ancestorNode = input.ancestor('span.availability_profile');
            var op = ancestorNode.one('select[name=op]');
            var novalue = (op.get('value') === 'isempty' || op.get('value') === 'isnotempty');
            ancestorNode.one('input[name=value]').set('disabled', novalue);
            M.core_availability.form.update();
        };
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
             updateForm(this);
        }, '.availability_profile select');
        root.delegate('change', function() {
             updateForm(this);
        }, '.availability_profile input[name=value]');
    }

    return node;
};

M.availability_profile.form.fillValue = function(value, node) {
    // Set field.
    var field = node.one('select[name=field]').get('value');
    if (field.substr(0, 3) === 'sf_') {
        value.sf = field.substr(3);
    } else if (field.substr(0, 3) === 'cf_') {
        value.cf = field.substr(3);
    }

    // Operator and value
    value.op = node.one('select[name=op]').get('value');
    var valueNode = node.one('input[name=value]');
    if (!valueNode.get('disabled')) {
        value.v = valueNode.get('value');
    }
};

M.availability_profile.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check profile item id.
    if (value.sf === undefined && value.cf === undefined) {
        errors.push('availability_profile:error_selectfield');
    }
    if (value.v !== undefined && /^\s*$/.test(value.v)) {
        errors.push('availability_profile:error_setvalue');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
