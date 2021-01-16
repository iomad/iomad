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
 * Allows the user to manage calendar subscriptions.
 *
 * @copyright 2012 Jonathan Harker
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package calendar
 */
namespace core_calendar\local\event\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding a subscription to a Moodle course calendar.
 * @copyright 2012 Jonathan Harker
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class managesubscriptions extends \moodleform {

    use eventtype;

    /**
     * Defines the form used to add calendar subscriptions.
     */
    public function definition() {
        $mform = $this->_form;
        $eventtypes = calendar_get_all_allowed_types();
        if (empty($eventtypes)) {
            print_error('nopermissiontoupdatecalendar');
        }

        $mform->addElement('header', 'addsubscriptionform', get_string('importcalendarheading', 'calendar'));

        // Name.
        $mform->addElement('text', 'name', get_string('subscriptionname', 'calendar'), array('maxsize' => '255', 'size' => '40'));
        $mform->addRule('name', get_string('required'), 'required');
        $mform->setType('name', PARAM_TEXT);

        // Import from (url | importfile).
        $mform->addElement('html', get_string('importfrominstructions', 'calendar'));
        $choices = array(CALENDAR_IMPORT_FROM_FILE => get_string('importfromfile', 'calendar'),
            CALENDAR_IMPORT_FROM_URL  => get_string('importfromurl',  'calendar'));
        $mform->addElement('select', 'importfrom', get_string('importcalendarfrom', 'calendar'), $choices);
        $mform->setDefault('importfrom', CALENDAR_IMPORT_FROM_URL);

        // URL.
        $mform->addElement('text', 'url', get_string('importfromurl', 'calendar'), array('maxsize' => '255', 'size' => '50'));
        // Cannot set as PARAM_URL since we need to allow webcal:// protocol.
        $mform->setType('url', PARAM_RAW);
        $mform->setForceLtr('url');

        // Poll interval
        $choices = calendar_get_pollinterval_choices();
        $mform->addElement('select', 'pollinterval', get_string('pollinterval', 'calendar'), $choices);
        $mform->setDefault('pollinterval', 604800);
        $mform->addHelpButton('pollinterval', 'pollinterval', 'calendar');
        $mform->setType('pollinterval', PARAM_INT);

        // Import file
        $mform->addElement('filepicker', 'importfile', get_string('importfromfile', 'calendar'), null, array('accepted_types' => '.ics'));

        // Disable appropriate elements depending on import from value.
        $mform->disabledIf('pollinterval', 'importfrom', 'eq', CALENDAR_IMPORT_FROM_FILE);
        $mform->disabledIf('url',  'importfrom', 'eq', CALENDAR_IMPORT_FROM_FILE);
        $mform->disabledIf('importfile', 'importfrom', 'eq', CALENDAR_IMPORT_FROM_URL);

        // Add the select elements for the available event types.
        $this->add_event_type_elements($mform, $eventtypes);

        // Eventtype: 0 = user, 1 = global, anything else = course ID.
        $mform->addElement('submit', 'add', get_string('add'));
    }

    /**
     * Validates the returned data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        $coursekey = isset($data['groupcourseid']) ? 'groupcourseid' : 'courseid';
        $eventtypes = calendar_get_all_allowed_types();
        $eventtype = isset($data['eventtype']) ? $data['eventtype'] : null;

        if (empty($eventtype) || !isset($eventtypes[$eventtype])) {
            $errors['eventtype'] = get_string('invalideventtype', 'calendar');
        }

        if ($data['importfrom'] == CALENDAR_IMPORT_FROM_FILE) {
            if (empty($data['importfile'])) {
                $errors['importfile'] = get_string('errorrequiredurlorfile', 'calendar');
            } else {
                // Make sure the file area is not empty and contains only one file.
                $draftitemid = $data['importfile'];
                $fs = get_file_storage();
                $usercontext = \context_user::instance($USER->id);
                $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);
                if (count($files) !== 1) {
                    $errors['importfile'] = get_string('errorrequiredurlorfile', 'calendar');
                }
            }
        } else if (($data['importfrom'] == CALENDAR_IMPORT_FROM_URL)) {
            // Clean input calendar url.
            $url = clean_param($data['url'], PARAM_URL);
            if (empty($url) || ($url !== $data['url'])) {
                $errors['url']  = get_string('invalidurl', 'error');
            }
        } else {
            // Shouldn't happen.
            $errors['url'] = get_string('errorrequiredurlorfile', 'calendar');
        }

        return $errors;
    }

    public function definition_after_data() {
        $mform =& $this->_form;

        $mform->applyFilter('url', static::class . '::strip_webcal');
        $mform->applyFilter('url', 'trim');
    }

    /**
     * Replace webcal:// urls with http:// as
     * curl does not understand this protocol
     *
     * @param string @url url to examine
     * @return string url with webcal:// replaced
     */
    public static function strip_webcal($url) {
        if (strpos($url, 'webcal://') === 0) {
            $url = str_replace('webcal://', 'http://', $url);
        }
        return $url;
    }
}
