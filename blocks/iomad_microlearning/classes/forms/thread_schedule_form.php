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
 * IOMAD microlearning block thread schedule form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use html_writer;
use moodleform;

/**
 * IOMAD microlearning block thread schedule form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_schedule_form extends moodleform {

    /** @var int thread ID */
    protected $threadid;

    /** @var object thread */
    protected $threadinfo;

    /** @var array list of nuggets */
    protected $nuggets;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $threadid
     * @param int $nuggets
     */
    public function __construct($actionurl, $threadid, $nuggets) {
        global $DB;

        $this->threadid = $threadid;
        $this->nuggets = $nuggets;
        $this->threadinfo = $DB->get_record('microlearning_thread', ['id' => $threadid]);
        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'threadid');
        $mform->setType('threadid', PARAM_INT);

        $headhtml = html_writer::start_tag('table', ['class' => 'generaltable', 'width' => "95%"]) .
                    html_writer::start_tag('thead') .
                    html_writer::start_tag('tr') .
                    html_writer::tag(
                        'th',
                        get_string('nugget', 'block_iomad_microlearning'),
                        ['class' => "header c0", 'style' => "text-align:left;", 'scope' => "col"]) .
                    html_writer::tag(
                        'th',
                        get_string('nuggetorder', 'block_iomad_microlearning'),
                        ['class' => "header c1", 'style' => "text-align:left;", 'scope' => "col"]) .
                    html_writer::tag(
                        'th',
                        get_string('scheduledate', 'block_iomad_microlearning'),
                        ['class' => "header c2", 'style' => "text-align:left;", 'scope' => "col"]) .
                    html_writer::tag(
                        'th',
                        get_string('duedate', 'block_iomad_microlearning'),
                        ['class' => "header c3", 'style' => "text-align:left;", 'scope' => "col"]);

        if (!empty($this->threadinfo->send_reminder)) {
            $headhtml .= html_writer::tag(
                'th',
                get_string('reminder1', 'block_iomad_microlearning'),
                ['class' => "header c4", 'style' => "text-align:left;", 'scope' => "col"]) .
                        html_writer::tag(
                'th',
                get_string('reminder2', 'block_iomad_microlearning'),
                ['class' => "header c5", 'style' => "text-align:left;", 'scope' => "col"]);
        }

        $headhtml .= html_writer::end_tag('tr') .
                     html_writer::end_tag('thead') .
                     html_writer::start_tag('tbody');

        $mform->addElement('html', $headhtml);

        foreach ($this->nuggets as $nugget) {
            $mform->addElement(
                'html',
                html_writer::start_tag('tr') .
                html_writer::tag(
                    'td',
                    format_text($nugget->name),
                    ['class' => "cell c0"]) .
                html_writer::tag(
                    'td',
                    $nugget->nuggetorder + 1,
                    ['class' => "cell c1", 'style' => "text-align:left;"]) .
                html_writer::start_tag('td', ['class' => "cell c2", 'style' => "text-align:left;"]));

            $mform->addElement('date_time_selector', "schedulearray[$nugget->id]", '');

            $mform->addElement(
                'html',
                html_writer::end_tag('td') .
                html_writer::start_tag('td', ['class' => "cell c2", 'style' => "text-align:left;"]));

            $mform->addElement('date_time_selector', "duedatearray[$nugget->id]", '', ['optional' => true]);

            if (!empty($this->threadinfo->send_reminder)) {
                $mform->addElement(
                    'html',
                    html_writer::end_tag('td') .
                    html_writer::start_tag('td', ['class' => "cell c3", 'style' => "text-align:left;"]));

                $mform->addElement('date_time_selector', "reminder1array[$nugget->id]", '', ['optional' => true]);

                $mform->addElement(
                    'html',
                    html_writer::end_tag('td') .
                    html_writer::start_tag('td', ['class' => "cell c4", 'style' => "text-align:left;"]));

                $mform->addElement('date_time_selector', "reminder2array[$nugget->id]", '', ['optional' => true]);
            }
            $mform->addElement('html', html_writer::end_tag('td') . html_writer::end_tag('tr'));
        }
        $mform->addElement('html', html_writer::end_tag('tbody') . html_writer::end_tag('table'));

        // Add buttons.
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save'));
        $buttonarray[] = &$mform->createElement(
            'submit',
            'resetallbutton',
            get_string('resetschedule', 'block_iomad_microlearning'));
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonarray', '', [' '], false);

    }

    /**
     * Validation function
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];

        foreach ($this->nuggets as $nugget) {
            if (!empty($data['duedatearray'][$nugget->id]) &&
                $data['duedatearray'][$nugget->id] < $data['schedulearray'][$nugget->id]) {
                $errors['duedatearray'][$nugget->id] = get_string('duedatebeforescheduledate', 'block_iomad_microlearning');
            }

            if (!empty($this->threadinfo->send_reminder) &&
                !empty($data['reminder1array'][$nugget->id]) &&
                $data['reminder1array'][$nugget->id] < $data['schedulearray'][$nugget->id]) {
                $errors['reminder1array'][$nugget->id] = get_string('reminderdatebeforescheduledate', 'block_iomad_microlearning');
            }

            if (!empty($this->threadinfo->send_reminder) &&
                !empty($data['reminder2array'][$nugget->id]) &&
                $data['reminder2array'][$nugget->id] < $data['schedulearray'][$nugget->id]) {
                $errors['reminder2array'][$nugget->id] = get_string('reminderdatebeforescheduledate', 'block_iomad_microlearning');
            }

            if (!empty($this->threadinfo->send_reminder) &&
                !empty($data['reminder2array'][$nugget->id]) &&
                !empty($data['reminder1array'][$nugget->id]) &&
                $data['reminder2array'][$nugget->id] < $data['reminder1array'][$nugget->id]) {
                $errors['reminder2array'][$nugget->id] = get_string('reminderdatesoutoforder', 'block_iomad_microlearning');
            }

            foreach ($this->nuggets as $check) {
                if ($check->nuggetorder <= $nugget->nuggetorder) {
                    continue;
                }
                if ($data['schedulearray'][$check->id] < $data['schedulearray'][$nugget->id]) {
                    $errors['schedulearray'][$check->id] = get_string('scheduleoutoforder', 'block_iomad_microlearning');
                }
            }
        }

        return $errors;
    }
}
