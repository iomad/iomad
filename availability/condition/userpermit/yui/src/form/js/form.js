
  M.availability_userpermit = M.availability_userpermit || {};
  M.availability_userpermit.form = Y.Object(M.core_availability.plugin);
  M.availability_userpermit.form.initInner = function(phpdata) {};
  M.availability_userpermit.form.getNode = function(json) {
    var title = M.util.get_string('title', 'availability_userpermit');
    var description = M.util.get_string('form_description', 'availability_userpermit');
    var html = '<label class="form-group">'
    + '<span class="p-r-1">' + title + '</span>'
    + '<input type="checkbox">'
    + '</label>'
    + '<div class="clearfix m-t-1"></div>'
    + '<p>' + description + '</p>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');
    if (json.creating === true) {
      // initial value
      node.one('input').set('checked', true);
    }else if(json.checkpermit != undefined){
      // saved value
      node.one('input').set('checked', json.checkpermit);
    }
    if (!M.availability_userpermit.form.addedEvents) {
        M.availability_userpermit.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_userpermit input');
    }
    return node;
  };

  M.availability_userpermit.form.fillValue = function(value, node) {
    var checkbox = node.one('input');
    value.checkpermit = checkbox.get('checked') ? 1 : 0;
  };

  M.availability_userpermit.form.fillErrors = function(errors, node) {
    // can report invalid submissions
    // errors.push('availability_userpermit:some_string_id');
  };
  