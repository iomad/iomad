/**
 * JavaScript for form editing language conditions.
 *
 * @module moodle-availability_language-form
 */
M.availability_language = M.availability_language || {};

// Class M.availability_language.form @extends M.core_availability.plugin.
M.availability_language.form = Y.Object(M.core_availability.plugin);

// Languages available for selection.
M.availability_language.form.languages = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} languages Array of objects containing languageid => name
 */
M.availability_language.form.initInner = function(languages) {
    this.languages = languages;
};

M.availability_language.form.getNode = function(json) {
    // Create HTML structure.
    var tit = M.util.get_string('title', 'availability_language');
    var html = '<label class="form-group"><span class="p-r-1">' + tit + '</span>';
    html += '<span class="availability-language"><select class="custom-select" name="id" title=' + tit + '>';
    html += '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.languages.length; i++) {
        var language = this.languages[i];
        html += '<option value="' + language.id + '">' + language.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined && node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'choose');
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_language.form.addedEvents) {
        M.availability_language.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_language select');
    }

    return node;
};

M.availability_language.form.focusAfterAdd = function(node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        // Make default hidden if no value chosen.
        var eyenode = node.ancestor().one('.availability-eye');
        eyenode.simulate('click');
    }
    var target = node.one('input:not([disabled]),select:not([disabled])');
    target.focus();
};

M.availability_language.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = '';
    } else {
        value.id = selected;
    }
};

M.availability_language.form.fillErrors = function(errors, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        errors.push('availability_language:missing');
    }
};
