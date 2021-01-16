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
 * Grade import key management page.
 *
 * @package   moodlecore
 * @copyright 2008 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/grade/lib.php');

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/import/keymanager.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:import', $context);

// Check if the user has at least one grade publishing capability.
$plugins = grade_helper::get_plugins_import($course->id);
if (!isset($plugins['keymanager'])) {
    print_error('nopermissions');
}

print_grade_page_head($course->id, 'import', 'keymanager', get_string('keymanager', 'grades'));

$stredit   = get_string('edit');
$strdelete = get_string('delete');

$data = array();
$params = array($course->id, $USER->id);
if ($keys = $DB->get_records_select('user_private_key', "script='grade/import' AND instance=? AND userid=?", $params)) {
    foreach($keys as $key) {
        $line = array();
        $line[0] = format_string($key->value);
        $line[1] = $key->iprestriction;
        $line[2] = empty($key->validuntil) ? get_string('always') : userdate($key->validuntil);

        $url = new moodle_url('key.php', array('id' => $key->id));

        $buttons = $OUTPUT->action_icon($url, new pix_icon('t/edit', $stredit));

        $url->param('delete', 1);
        $url->param('sesskey', sesskey());
        $buttons .= $OUTPUT->action_icon($url, new pix_icon('t/delete', $strdelete));

        $line[3] = $buttons;
        $data[] = $line;
    }
}
$table = new html_table();
$table->head  = array(get_string('keyvalue', 'userkey'), get_string('keyiprestriction', 'userkey'), get_string('keyvaliduntil', 'userkey'), $stredit);
$table->size  = array('50%', '30%', '10%', '10%');
$table->align = array('left', 'left', 'left', 'center');
$table->width = '90%';
$table->data  = $data;
echo html_writer::table($table);

echo $OUTPUT->container_start('buttons mdl-align');
echo $OUTPUT->single_button(new moodle_url('key.php', array('courseid'=>$course->id)), get_string('newuserkey', 'userkey'));
echo $OUTPUT->container_end();

echo $OUTPUT->footer();

