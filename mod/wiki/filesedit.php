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
 * Manage files in wiki
 *
 * @package   mod_wiki
 * @copyright 2011 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('lib.php');
require_once('locallib.php');
require_once("$CFG->dirroot/mod/wiki/filesedit_form.php");
require_once("$CFG->dirroot/repository/lib.php");

$subwikiid = required_param('subwiki', PARAM_INT);
// not being used for file management, we use it to generate navbar link
$pageid    = optional_param('pageid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if (!$subwiki = wiki_get_subwiki($subwikiid)) {
    print_error('incorrectsubwikiid', 'wiki');
}

// Checking wiki instance of that subwiki
if (!$wiki = wiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'wiki');
}

// Checking course module instance
if (!$cm = get_coursemodule_from_instance("wiki", $subwiki->wikiid)) {
    print_error('invalidcoursemodule');
}

// Checking course instance
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

if (!wiki_user_can_view($subwiki, $wiki)) {
    print_error('cannotviewpage', 'wiki');
}
require_capability('mod/wiki:managefiles', $context);

if (empty($returnurl)) {
    $referer = get_local_referer(false);
    if (!empty($referer)) {
        $returnurl = $referer;
    } else {
        $returnurl = new moodle_url('/mod/wiki/files.php', array('subwiki' => $subwiki->id, 'pageid' => $pageid));
    }
}

$title = get_string('editfiles', 'wiki');

$struser = get_string('user');
$url = new moodle_url('/mod/wiki/filesedit.php', array('subwiki'=>$subwiki->id, 'pageid'=>$pageid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(format_string(get_string('wikifiles', 'wiki')), $CFG->wwwroot . '/mod/wiki/files.php?pageid=' . $pageid);
$PAGE->navbar->add(format_string($title));

$data = new stdClass();
$data->returnurl = $returnurl;
$data->subwikiid = $subwiki->id;
$maxbytes = get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes);
$types = FILE_INTERNAL | FILE_REFERENCE | FILE_CONTROLLED_LINK;
$options = array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => -1, 'accepted_types' => '*', 'return_types' => $types);
file_prepare_standard_filemanager($data, 'files', $options, $context, 'mod_wiki', 'attachments', $subwiki->id);

$mform = new mod_wiki_filesedit_form(null, array('data'=>$data, 'options'=>$options));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $context, 'mod_wiki', 'attachments', $subwiki->id);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($wiki->name));
echo $OUTPUT->box(format_module_intro('wiki', $wiki, $PAGE->cm->id), 'generalbox', 'intro');
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
