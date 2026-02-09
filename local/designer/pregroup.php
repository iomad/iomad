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
 * Create group settings.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot. "/local/designer/lib.php");


// Get url variables.
$courseid = optional_param('courseid', 0, PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);
$action   = optional_param('action', '', PARAM_TEXT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);
$delete   = optional_param('delete', 0, PARAM_BOOL);
$groupprecourses = [];

if ($id) {
    if (!$group = $DB->get_record('local_designer_pregroups', ['id' => $id])) {
        throw new moodle_exception('invalidgroupid');
    }
    if (empty($courseid)) {
        $courseid = $group->courseid;

    } else if ($courseid != $group->courseid) {
        throw new moodle_exception('invalidcourseid');
    }

    if (!$course = $DB->get_record('course', ['id' => $courseid])) {
        throw new moodle_exception('invalidcourseid');
    }
    if (!empty($group)) {
        $groupprecourses = local_designer_get_pregroup_courses($group->id);
        $group->precourses = $groupprecourses;
    }
} else {
    if (!$course = $DB->get_record('course', ['id' => $courseid])) {
        throw new moodle_exception('invalidcourseid');
    }
    $group = new stdClass();
    $group->courseid = $course->id;
}

if ($id !== 0) {
    $PAGE->set_url('/local/designer/pregroup.php', ['id' => $id]);
} else {
    $PAGE->set_url('/local/designer/pregroup.php', ['courseid' => $courseid]);
}
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/designer:manageprerequisitesgroup', $context);

$strgroups = get_string('pregroups', 'format_designer');
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname . ': '.$strgroups);
$PAGE->set_pagelayout('admin');
$returnurl = $CFG->wwwroot.'/local/designer/pregrouplist.php?courseid='.$course->id;
// Prepare the description editor: We do support files for group descriptions.
$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'trust' => false,
    'context' => $context,
    'noclean' => true,
];
if (!empty($group->id)) {
    $editoroptions['subdirs'] = file_area_contains_subdirs($context, 'local_designer', 'description', $group->id);
    $group = file_prepare_standard_editor($group, 'description', $editoroptions,
        $context, 'local_designer', 'description', $group->id);
} else {
    $editoroptions['subdirs'] = false;
    $group = file_prepare_standard_editor($group, 'description', $editoroptions,
        $context, 'local_designer', 'description', null);
}


// First create the form.
$editform = new local_designer\form\pregroup_form(null, ['editoroptions' => $editoroptions,
    'groupprecourses' => $groupprecourses, ]);
$editform->set_data($group);


if ($editform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $editform->get_data()) {
    if ($data->id) {
        local_designer_update_group_courses($data, $courseid);
        local_designer_update_group($data, $editform, $editoroptions);
    } else {
        $id = local_designer_create_group($data, $editform, $editoroptions);
        $returnurl = $CFG->wwwroot.'/local/designer/pregrouplist.php?courseid='.$course->id;
    }
    if (!empty($data->precourses)) {
        local_designer_add_remove_prerequisites_courses($data->precourses, $courseid);
    }
    redirect($returnurl);
}

$strgroups = get_string('groups');
$strparticipants = get_string('participants');

if ($id) {
    $strheading = get_string('editgroupsettings', 'group');
} else {
    $strheading = get_string('creategroup', 'group');
}

$PAGE->navbar->add($strparticipants, new moodle_url('/user/index.php', ['id' => $courseid]));
$PAGE->navbar->add($strgroups, new moodle_url('/group/index.php', ['id' => $courseid]));
$PAGE->navbar->add($strheading);

// Print header.
echo $OUTPUT->header();
echo '<div id="grouppicture">';
$editform->display();
echo $OUTPUT->footer();
