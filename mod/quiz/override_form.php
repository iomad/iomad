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
 * Settings form for overrides in the quiz module.
 *
 * @package    mod_quiz
 * @copyright  2010 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/quiz/mod_form.php');


/**
 * Form for editing settings overrides.
 *
 * @copyright  2010 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_override_form extends moodleform {

    /** @var object course module object. */
    protected $cm;

    /** @var object the quiz settings object. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $context;

    /** @var bool editing group override (true) or user override (false). */
    protected $groupmode;

    /** @var int groupid, if provided. */
    protected $groupid;

    /** @var int userid, if provided. */
    protected $userid;

    /**
     * Constructor.
     * @param moodle_url $submiturl the form action URL.
     * @param object course module object.
     * @param object the quiz settings object.
     * @param context the quiz context.
     * @param bool editing group override (true) or user override (false).
     * @param object $override the override being edited, if it already exists.
     */
    public function __construct($submiturl, $cm, $quiz, $context, $groupmode, $override) {

        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->context = $context;
        $this->groupmode = $groupmode;
        $this->groupid = empty($override->groupid) ? 0 : $override->groupid;
        $this->userid = empty($override->userid) ? 0 : $override->userid;

        parent::__construct($submiturl, null, 'post');

    }

    protected function definition() {
        global $CFG, $DB;

        $cm = $this->cm;
        $mform = $this->_form;

        $mform->addElement('header', 'override', get_string('override', 'quiz'));

        if ($this->groupmode) {
            // Group override.
            if ($this->groupid) {
                // There is already a groupid, so freeze the selector.
                $groupchoices = array();
                $groupchoices[$this->groupid] = groups_get_group_name($this->groupid);
                $mform->addElement('select', 'groupid',
                        get_string('overridegroup', 'quiz'), $groupchoices);
                $mform->freeze('groupid');
            } else {
                // Prepare the list of groups.
                $groups = groups_get_all_groups($cm->course);
                if (empty($groups)) {
                    // Generate an error.
                    $link = new moodle_url('/mod/quiz/overrides.php', array('cmid'=>$cm->id));
                    print_error('groupsnone', 'quiz', $link);
                }

                $groupchoices = array();
                foreach ($groups as $group) {
                    $groupchoices[$group->id] = $group->name;
                }
                unset($groups);

                if (count($groupchoices) == 0) {
                    $groupchoices[0] = get_string('none');
                }

                $mform->addElement('select', 'groupid',
                        get_string('overridegroup', 'quiz'), $groupchoices);
                $mform->addRule('groupid', get_string('required'), 'required', null, 'client');
            }
        } else {
            // User override.
            if ($this->userid) {
                // There is already a userid, so freeze the selector.
                $user = $DB->get_record('user', array('id'=>$this->userid));
                $userchoices = array();
                $userchoices[$this->userid] = fullname($user);
                $mform->addElement('select', 'userid',
                        get_string('overrideuser', 'quiz'), $userchoices);
                $mform->freeze('userid');
            } else {
                // Prepare the list of users.
                $users = array();
                list($sort, $sortparams) = users_order_by_sql('u');
                if (!empty($sortparams)) {
                    throw new coding_exception('users_order_by_sql returned some query parameters. ' .
                            'This is unexpected, and a problem because there is no way to pass these ' .
                            'parameters to get_users_by_capability. See MDL-34657.');
                }
                $users = get_users_by_capability($this->context, 'mod/quiz:attempt',
                        'u.id, u.email, ' . get_all_user_name_fields(true, 'u'),
                        $sort, '', '', '', '', false, true);

                // Filter users based on any fixed restrictions (groups, profile).
                $info = new \core_availability\info_module($cm);
                $users = $info->filter_user_list($users);

                if (empty($users)) {
                    // Generate an error.
                    $link = new moodle_url('/mod/quiz/overrides.php', array('cmid'=>$cm->id));
                    print_error('usersnone', 'quiz', $link);
                }

                $userchoices = array();
                $canviewemail = in_array('email', get_extra_user_fields($this->context));
                foreach ($users as $id => $user) {
                    if (empty($invalidusers[$id]) || (!empty($override) &&
                            $id == $override->userid)) {
                        if ($canviewemail) {
                            $userchoices[$id] = fullname($user) . ', ' . $user->email;
                        } else {
                            $userchoices[$id] = fullname($user);
                        }
                    }
                }
                unset($users);

                $mform->addElement('searchableselector', 'userid',
                        get_string('overrideuser', 'quiz'), $userchoices);
                $mform->addRule('userid', get_string('required'), 'required', null, 'client');
            }
        }

        // Password.
        // This field has to be above the date and timelimit fields,
        // otherwise browsers will clear it when those fields are changed.
        $mform->addElement('passwordunmask', 'password', get_string('requirepassword', 'quiz'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'requirepassword', 'quiz');
        $mform->setDefault('password', $this->quiz->password);

        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen',
                get_string('quizopen', 'quiz'), mod_quiz_mod_form::$datefieldoptions);
        $mform->setDefault('timeopen', $this->quiz->timeopen);

        $mform->addElement('date_time_selector', 'timeclose',
                get_string('quizclose', 'quiz'), mod_quiz_mod_form::$datefieldoptions);
        $mform->setDefault('timeclose', $this->quiz->timeclose);

        // Time limit.
        $mform->addElement('duration', 'timelimit',
                get_string('timelimit', 'quiz'), array('optional' => true));
        $mform->addHelpButton('timelimit', 'timelimit', 'quiz');
        $mform->setDefault('timelimit', $this->quiz->timelimit);

        // Number of attempts.
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts',
                get_string('attemptsallowed', 'quiz'), $attemptoptions);
        $mform->setDefault('attempts', $this->quiz->attempts);

        // Submit buttons.
        $mform->addElement('submit', 'resetbutton',
                get_string('reverttodefaults', 'quiz'));

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
                get_string('save', 'quiz'));
        $buttonarray[] = $mform->createElement('submit', 'againbutton',
                get_string('saveoverrideandstay', 'quiz'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonbar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonbar');

    }

    public function validation($data, $files) {
        global $COURSE, $DB;
        $errors = parent::validation($data, $files);

        $mform =& $this->_form;
        $quiz = $this->quiz;

        if ($mform->elementExists('userid')) {
            if (empty($data['userid'])) {
                $errors['userid'] = get_string('required');
            }
        }

        if ($mform->elementExists('groupid')) {
            if (empty($data['groupid'])) {
                $errors['groupid'] = get_string('required');
            }
        }

        // Ensure that the dates make sense.
        if (!empty($data['timeopen']) && !empty($data['timeclose'])) {
            if ($data['timeclose'] < $data['timeopen'] ) {
                $errors['timeclose'] = get_string('closebeforeopen', 'quiz');
            }
        }

        // Ensure that at least one quiz setting was changed.
        $changed = false;
        $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
        foreach ($keys as $key) {
            if ($data[$key] != $quiz->{$key}) {
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            $errors['timeopen'] = get_string('nooverridedata', 'quiz');
        }

        return $errors;
    }
}
