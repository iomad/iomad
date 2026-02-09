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
 * Create group OR edit group settings.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot. "/local/designer/lib.php");

// Get url variables.
$courseid = required_param('courseid', PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/designer:manageprerequisitesgroup', $context);


$strgroups = get_string('pregroups', 'format_designer');
$PAGE->set_title($strgroups);
$PAGE->set_heading($strgroups);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/designer/pregrouplist.php', ['courseid' => $courseid]);

$stredit   = get_string('edit');
$strdelete = get_string('delete');

$returnurl = new moodle_url('/local/designer/pregrouplist.php', ['sort' => $sort,
    'dir' => $dir, 'perpage' => $perpage, 'page' => $page, 'courseid' => $courseid,
]);

if ($delete && confirm_sesskey()) {              // Delete a selected user, after confirmation.
    require_capability('local/designer:manageprerequisitesgroup', $context);

    $group = $DB->get_record('local_designer_pregroups', ['id' => $delete], '*', MUST_EXIST);

    if (!$group) {
        throw new moodle_exception('groupnotfound', 'format_designer');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = $group->name;
        echo $OUTPUT->heading(get_string('deletegroup', 'format_designer'));

        $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheckgroup', 'format_designer', "'$fullname'"), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (local_designer_delete_group($group, $courseid)) {
            redirect($returnurl, get_string('deletesuccess', 'format_designer'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednotgroup', 'format_designer', $group->name));
        }
    }
}

$baseurl = new moodle_url('/local/designer/pregrouplist.php', ['sort' => $sort,
    'dir' => $dir, 'perpage' => $perpage, 'courseid' => $courseid,
]);

if ($sort) {
    $sort = "$sort $dir";
}

$groups = $DB->get_records('local_designer_pregroups', ['courseid' => $courseid], $sort, '*',
    $page * $perpage, $perpage);
$groupscount = $DB->count_records('local_designer_pregroups', ['courseid' => $courseid]);

echo $OUTPUT->header();

if (has_capability('local/designer:manageprerequisitesgroup', $context)) {
    $url = new moodle_url('/local/designer/pregroup.php', ['courseid' => $courseid]);
    echo $OUTPUT->single_button($url, get_string('addnewgroup', 'format_designer'), 'get');
}

echo $OUTPUT->paging_bar($groupscount, $page, $perpage, $baseurl);
$column = "name";
$string["name"] = get_string('group');

$columndir = $dir == "ASC" ? "DESC" : "ASC";
$columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
['class' => 'iconsort']);

$groupname = "<a href=\"pregrouplist.php?sort=$column&amp;courseid=$courseid&amp;
    dir=$columndir\">".$string[$column]."</a>$columnicon";

if (!$groups) {
    echo $OUTPUT->heading(get_string('nogroupsfound', 'format_designer'));
    $table = null;
} else {
    $table = new html_table();
    $table->head = [];
    $table->colclasses = [];
    $table->head[] = $groupname;
    $table->attributes['class'] = 'admintable generaltable table-sm';
    $table->head[] = get_string('description');
    $table->head[] = get_string('courses');
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';

    $table->id = "pregroups";
    foreach ($groups as $group) {
        $buttons = [];
        // Delete button.
        $url = new moodle_url('/local/designer/pregrouplist.php', ['delete' => $group->id,
            'sesskey' => sesskey(), 'courseid' => $courseid,
        ]);
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));

        // Edit button.
        $url = new moodle_url('/local/designer/pregroup.php', ['id' => $group->id, 'courseid' => $courseid]);
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));

        $row = [];

        $row[] = $group->name;
        $row[] = format_text(file_rewrite_pluginfile_urls($group->description,
                    'pluginfile.php',
                    $context->id,
                    'local_designer',
                    'description',
                    $group->id), $group->descriptionformat);
        $row[] = local_designer_group_coursescontent($group->id);
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }
}

if (!empty($table)) {
    echo html_writer::start_tag('div', ['class' => 'no-overflow']);
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($groupscount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();



