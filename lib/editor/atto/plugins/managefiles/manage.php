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
 * Manage files in user draft area attached to texteditor.
 *
 * @package   atto_managefiles
 * @copyright 2014 Frédéric Massart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/manage_form.php');
require_once($CFG->libdir . '/filestorage/file_storage.php');
require_once($CFG->dirroot . '/repository/lib.php');

$itemid = required_param('itemid', PARAM_INT);
$maxbytes = optional_param('maxbytes', 0, PARAM_INT);
$subdirs = optional_param('subdirs', 0, PARAM_INT);
$accepted_types = optional_param('accepted_types', '*', PARAM_RAW); // TODO Not yet passed to this script.
$return_types = optional_param('return_types', null, PARAM_INT);
$areamaxbytes = optional_param('areamaxbytes', FILE_AREA_MAX_BYTES_UNLIMITED, PARAM_INT);
$contextid = optional_param('context', SYSCONTEXTID, PARAM_INT);
$elementid = optional_param('elementid', '', PARAM_TEXT);
$removeorphaneddrafts = optional_param('removeorphaneddrafts', 0, PARAM_INT);

$context = context::instance_by_id($contextid);
if ($context->contextlevel == CONTEXT_MODULE) {
    // Module context.
    $cm = $DB->get_record('course_modules', array('id' => $context->instanceid));
    require_login($cm->course, true, $cm);
} else if (($coursecontext = $context->get_course_context(false)) && $coursecontext->id != SITEID) {
    // Course context or block inside the course.
    require_login($coursecontext->instanceid);
    $PAGE->set_context($context);
} else {
    // Block that is not inside the course, user or system context.
    require_login();
    $PAGE->set_context($context);
}

// Guests can never manage files.
if (isguestuser()) {
    print_error('noguest');
}

$title = get_string('managefiles', 'atto_managefiles');

$PAGE->set_url('/lib/editor/atto/plugins/managefiles/manage.php');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('popup');

if ($return_types !== null) {
    // Links are allowed in textarea but never allowed in filemanager.
    $return_types = $return_types & ~FILE_EXTERNAL;
}

$options = array(
    'subdirs' => $subdirs,
    'maxbytes' => $maxbytes,
    'maxfiles' => -1,
    'accepted_types' => $accepted_types,
    'areamaxbytes' => $areamaxbytes,
    'return_types' => $return_types,
    'context' => $context
);

$usercontext = context_user::instance($USER->id);
$fs = get_file_storage();
$files = $fs->get_directory_files($usercontext->id, 'user', 'draft', $itemid, '/', !empty($subdirs), false);
$filenames = array();
foreach ($files as $file) {
    $filenames[$file->get_pathnamehash()] = ltrim($file->get_filepath(), '/') . $file->get_filename();
}

$mform = new atto_managefiles_manage_form(null,
    array('options' => $options, 'draftitemid' => $itemid, 'files' => $filenames, 'elementid' => $elementid,
        'removeorphaneddrafts' => $removeorphaneddrafts), 'post', '', array('id' => 'atto_managefiles_manageform'));

if ($data = $mform->get_data()) {
    if (!empty($data->deletefile)) {
        foreach (array_keys($data->deletefile) as $filehash) {
            if ($file = $fs->get_file_by_hash($filehash)) {
                // Make sure the user didn't modify the filehash to delete another file.
                if ($file->get_component() == 'user' && $file->get_filearea() == 'draft'
                        && $file->get_itemid() == $itemid && $file->get_contextid() == $usercontext->id) {
                    $file->delete();
                }
            }
        }
        $filenames = array_diff_key($filenames, $data->deletefile);
        $mform = new atto_managefiles_manage_form(null,
            array('options' => $options, 'draftitemid' => $itemid, 'files' => $filenames, 'elementid' => $data->elementid),
            'post', '', array('id' => 'atto_managefiles_manageform'));
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
