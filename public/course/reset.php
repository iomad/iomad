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
 * The purpose of this feature is to quickly remove all user related data from a course
 * in order to make it available for a new semester.  This feature can handle the removal
 * of general course data like students, teachers, logs, events and groups as well as module
 * specific data.  Each module must be modified to take advantage of this new feature.
 * The feature will also reset the start date of the course if necessary.
 *
 * @package   core_course
 * @copyright Mark Flach and moodle.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once('reset_form.php');
require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
require_once($CFG->dirroot . '/backup/backup.class.php');
require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');

use core_course\exception\reset_timeout;

$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

$PAGE->set_url('/course/reset.php', ['id' => $id]);
$PAGE->set_pagelayout('standard');

require_login($course, preventredirect: true); // This redirects here if a reset is in progress. Don't keep redirecting.
require_capability('moodle/course:reset', context_course::instance($course->id));

$strreset       = get_string('reset');
$strresetcourse = get_string('resetcourse');
$strremove      = get_string('remove');

$PAGE->set_title($course->fullname.': '.$strresetcourse);
$PAGE->set_heading($course->fullname);
$PAGE->set_secondary_active_tab('coursereuse');

$mform = new course_reset_form();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/course/view.php?id='.$id);

} else if ($data = $mform->get_data()) { // No magic quotes.

    if (isset($data->selectdefault)) {
        $_POST = [];
        $mform = new course_reset_form();
        $mform->load_defaults();

    } else if (isset($data->deselectall)) {
        $_POST = [];
        $mform = new course_reset_form();

    } else {
        $data->reset_start_date_old = $course->startdate;
        $data->reset_end_date_old = $course->enddate;
        // If enabled, move the reset process to an adhoc task.
        if (get_config('core', 'enableasyncresets')) {
            $task = \core_course\task\reset_course::create($data);
            $task->set_attempts_available(3);
            $taskid = \core\task\manager::queue_adhoc_task($task, true);
            if ($taskid) {
                $task->set_id($taskid);
                $task->initialise_stored_progress();
            }
            redirect($PAGE->url);
        }

        echo $OUTPUT->header();
        \backup_helper::print_coursereuse_selector('reset');
        $transaction = $DB->start_delegated_transaction();
        try {
            $timeout = new reset_timeout($course->shortname, time() + reset_timeout::DEFAULT_TIMEOUT);
            $status = reset_course_userdata($data, $timeout);
            $transaction->allow_commit();
            $data = [];
            foreach ($status as $item) {
                $line = [];
                $line[] = $item['component'];
                $line[] = $item['item'];
                if ($item['error'] === false) {
                    $line[] = get_string('statusdone');
                } else {
                    $line[] = html_writer::div(
                        $OUTPUT->pix_icon('i/invalid', get_string('error')) . $item['error'],
                    );
                }
                $data[] = $line;
            }

            $table = new html_table();
            $table->head = [get_string('resetcomponent'), get_string('resettask'), get_string('resetstatus')];
            $table->size = ['20%', '40%', '40%'];
            $table->align = ['left', 'left', 'left'];
            $table->width = '80%';
            $table->data = $data;
            echo html_writer::table($table);
        } catch (reset_timeout $e) {
            try {
                $transaction->rollback($e);
            } catch (\Exception $e) {
                // After we roll back, the original exception is re-thrown. There might be a different exception if it failed,
                // so display whichever message we get.
                echo $OUTPUT->render(
                    new \core\output\notification($e->getMessage(), \core\output\notification::NOTIFY_ERROR, false),
                );
            }
        }

        echo $OUTPUT->continue_button('view.php?id=' . $course->id);  // Back to course page.
        echo $OUTPUT->footer();
        exit;
    }
} else {
    $mform = new course_reset_form();
    $mform->load_defaults();
}

$PAGE->requires->js_call_amd('core_course/resetcourse', 'init');
echo $OUTPUT->header();
\backup_helper::print_coursereuse_selector('reset');

$taskid = \core_course\task\reset_course::get_taskid_for_course($course->id);
// If there is already a reset task queued for this course, display the indicator instead of the form.
if ($taskid) {
    $resettask = \core_course\task\reset_course::load($taskid);
    $indicator = new \core\output\task_indicator(
        $resettask,
        heading: get_string('resetcourse'),
        message: get_string('resetcoursetask', 'course'),
        redirecturl: new moodle_url('/course/view.php', ['id' => $course->id]),
    );
    echo $OUTPUT->render($indicator);
} else {
    echo $OUTPUT->box(get_string('resetinfo'));
    echo $OUTPUT->box(get_string('resetinfoselect'));
    $mform->display();
}
echo $OUTPUT->footer();
