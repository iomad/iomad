<?php
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
 * prints the form to edit a dedicated item
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");

feedback_init_feedback_session();

$itemid = optional_param('id', false, PARAM_INT);
if (!$itemid) {
    $cmid = required_param('cmid', PARAM_INT);
    $typ = required_param('typ', PARAM_ALPHA);
}

if ($itemid) {
    $item = $DB->get_record('feedback_item', array('id' => $itemid), '*', MUST_EXIST);
    list($course, $cm) = get_course_and_cm_from_instance($item->feedback, 'feedback');
    $url = new moodle_url('/mod/feedback/edit_item.php', array('id' => $itemid));
    $typ = $item->typ;
} else {
    $item = null;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'feedback');
    $url = new moodle_url('/mod/feedback/edit_item.php', array('cmid' => $cm->id, 'typ' => $typ));
    $item = (object)['id' => null, 'position' => -1, 'typ' => $typ, 'options' => ''];
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/feedback:edititems', $context);
$feedback = $PAGE->activityrecord;

$editurl = new moodle_url('/mod/feedback/edit.php', array('id' => $cm->id));

$PAGE->set_url($url);

// If the typ is pagebreak so the item will be saved directly.
if (!$item->id && $typ === 'pagebreak') {
    require_sesskey();
    feedback_create_pagebreak($feedback->id);
    redirect($editurl->out(false));
    exit;
}

//get the existing item or create it
// $formdata->itemid = isset($formdata->itemid) ? $formdata->itemid : NULL;
if (!$typ || !file_exists($CFG->dirroot.'/mod/feedback/item/'.$typ.'/lib.php')) {
    print_error('typemissing', 'feedback', $editurl->out(false));
}

require_once($CFG->dirroot.'/mod/feedback/item/'.$typ.'/lib.php');

$itemobj = feedback_get_item_class($typ);

$itemobj->build_editform($item, $feedback, $cm);

if ($itemobj->is_cancelled()) {
    redirect($editurl);
    exit;
}
if ($itemobj->get_data()) {
    if ($item = $itemobj->save_item()) {
        feedback_move_item($item, $item->position);
        redirect($editurl);
    }
}

////////////////////////////////////////////////////////////////////////////////////
/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

navigation_node::override_active_url(new moodle_url('/mod/feedback/edit.php',
        array('id' => $cm->id, 'do_show' => 'edit')));
if ($item->id) {
    $PAGE->navbar->add(get_string('edit_item', 'feedback'));
} else {
    $PAGE->navbar->add(get_string('add_item', 'feedback'));
}
$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();

// Print the main part of the page.
echo $OUTPUT->heading(format_string($feedback->name));

/// print the tabs
$current_tab = 'edit';
$id = $cm->id;
require('tabs.php');

//print errormsg
if (isset($error)) {
    echo $error;
}
$itemobj->show_editform();

/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();
