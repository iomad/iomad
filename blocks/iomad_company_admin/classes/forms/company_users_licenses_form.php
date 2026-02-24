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
 * IOMAD Dashboard assign license(s) and course(s) to a user form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use block_iomad_company_admin\event\{user_license_assigned, user_license_unassigned};
use context_course;
use core\exception\moodle_exception;
use html_writer;
use local_iomad\{company, iomad};
use local_iomad\course_selector\{current_user_license, potential_user_license};
use moodleform;

/**
 * IOMAD Dashboard assign license(s) and course(s) to a user form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_users_licenses_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object potential user license course selector */
    protected $potentialcourses = null;

    /** @var object current user license course selector */
    protected $currentcourses = null;

    /** @var object course */
    protected $course = null;

    /** @var int department ID */
    protected $departmentid = 0;

    /** @var int top level department ID */
    protected $companydepartment = 0;

    /** @var array list of departments */
    protected $subhierarchieslist = null;

    /** @var int parent department ID */
    protected $parentlevel = null;

    /** @var int user ID */
    protected $userid = null;

    /** @var object user */
    protected $user = null;

    /** @var int license ID */
    protected $licenseid = 0;

    /** @var array list of license courses */
    protected $liccourses = [];

    /** @var object license */
    protected $license = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param objject $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param int $userid
     * @param int $licenseid
     */
    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $userid, $licenseid) {
        global $USER, $DB;
        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $company = new company($this->selectedcompany);
        $this->parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $this->parentlevel->id;
        $this->licenseid = $licenseid;
        $this->liccourses = $DB->get_records_sql("SELECT c.* FROM {course} c
                                                  JOIN {local_iomad_company_license_courses} clc
                                                  ON (c.id = clc.courseid)
                                                  WHERE clc.licenseid = :licenseid",
                                                 ['licenseid' => $this->licenseid]);

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
            $userhierarchylevel = $this->parentlevel->id;
        } else {
            $userlevel = $company->get_userlevel($USER);
            $userhierarchylevel = key($userlevel);
        }

        $this->subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
        if ($departmentid == 0) {
            $this->departmentid = $userhierarchylevel;
        } else {
            $this->departmentid = $departmentid;
        }
        $this->userid = $userid;
        $this->user = $DB->get_record('user', ['id' => $this->userid]);
        $this->license = $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid]);

        parent::__construct($actionurl);
    }

    /**
     * Set the form course
     *
     * @param [type] $courses
     * @return void
     */
    public function set_course($courses) {
        $keys = array_keys($courses);
        $this->course = $courses[$keys[0]];
    }

    /**
     * Create the form course selectors
     *
     * @return void
     */
    public function create_course_selectors() {
        if (!empty ($this->userid)) {
            $options = [
                'context' => $this->context,
                'companyid' => $this->selectedcompany,
                'user' => $this->user,
                'departmentid' => $this->departmentid,
                'subdepartments' => $this->subhierarchieslist,
                'parentdepartment' => $this->parentlevel,
                'licenseid' => $this->licenseid,
                'shared' => true,
            ];
            if (! $this->potentialcourses) {
                $this->potentialcourses = new potential_user_license('potentialusercourses', $options);
            }
            if (! $this->currentcourses) {
                $this->currentcourses = new current_user_license('currentcourses', $options);
            }
        } else {
            return;
        }

    }

    /**
     * Form default definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->addElement('hidden', 'licenseid', $this->licenseid);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->setType('licenseid', PARAM_INT);
    }

    /**
     * Form definition after data has been set
     *
     * @return void
     */
    public function definition_after_data() {
        global $DB, $OUTPUT;

        // Set up the form.
        $mform =& $this->_form;

        if (!empty($this->userid)) {
            $this->_form->addElement('hidden', 'userid', $this->userid);
        } else {
            die('No user selected.');
        }

        $this->create_course_selectors();
        // Adding the elements in the definition_after_data function rather than in the definition function
        // so that when the currentcourses or potentialcourses get changed in the process function, the
        // changes get displayed, rather than the lists as they are before processing.

        $company = new company($this->selectedcompany);
        $programstr = "";
        if (!empty($this->licenseid)) {

            // Is this a program?
            if ($this->license->program) {
                // Get the courses.
                if (!empty($this->liccourses)) {
                    $coursecount = count($this->liccourses);
                    $programstr = get_string('licenseassignedto', 'block_iomad_company_admin');
                    $count = 1;
                    foreach ($this->liccourses as $course) {
                        if ($count > 1) {
                            $programstr .= ", ".$course->fullname;
                        } else {
                            $programstr .= $course->fullname;
                        }
                        $count++;
                    }
                    $this->license->allocation = $this->license->allocation / $coursecount;
                    $this->license->used = $this->license->used / $coursecount;
                }
                $licenseleft2 = get_string('programleft2', 'block_iomad_company_admin');
            } else {
                $licenseleft2 = get_string('licenseleft2', 'block_iomad_company_admin');
            }
            $licensestring = get_string('licensedetails', 'block_iomad_company_admin', $this->license);
            $licensestring2 = get_string('licensedetails2', 'block_iomad_company_admin', $this->license);
            $licensestring3 = get_string('licensedetails3', 'block_iomad_company_admin', $this->license);
        } else {
            $licensestring = '';
            $licensestring2 = '';
            $licensestring3 = '';
        }

        if (!empty($this->licenseid)) {
            $mform->addElement(
                'html',
                html_writer::empty_tag('br') .
                html_writer::start_tag(
                    'p',
                    [
                        'align' => 'center',
                    ]) .
                html_writer::tag(
                    'b',
                    get_string('licenseleft1', 'block_iomad_company_admin') .
                    ((intval($licensestring3, 0)) - (intval($licensestring2, 0))) .
                    $licenseleft2 .
                    html_writer::empty_tag('br') .
                    $programstr
                    )
                );

            $mform->addElement('date_time_selector', 'due', get_string('senddate', 'block_iomad_company_admin'));
            $mform->addHelpButton('due', 'senddate', 'block_iomad_company_admin');
            if ($this->license->startdate > time()) {
                $mform->setDefault('due', $this->license->startdate);
            }

            // Is this a license program?
            if ($this->license->program) {
                $programselect = $mform->addElement(
                    'selectyesno',
                    'allocate',
                    get_string('programallocate', 'block_iomad_company_admin'));
                $mform->addHelpButton('allocate', 'programallocate', 'block_iomad_company_admin');

                // Do we have any of these courses /license combo yet?
                if ($DB->get_records('local_iomad_company_license_users', ['userid' => $this->userid, 'licenseid' => $this->licenseid])) {
                    $mform->addElement('hidden', 'inuse', true);
                    $mform->setType('inuse', PARAM_INT);
                    $programselect->setSelected(true);
                } else {
                    $mform->addElement('hidden', 'inuse', false);
                    $mform->setType('inuse', PARAM_INT);
                    $programselect->setSelected(false);
                }
                $this->add_action_buttons(false, get_string('updatelicense', 'block_iomad_company_admin'));
            } else {
                $mform->addElement(
                    'html',
                    html_writer::start_tag(
                        'table',
                        [
                            'summary' => '',
                            'class' => 'generaltable generalbox groupmanagementtable boxaligncenter',
                            'cellspacing' => 0,
                        ]
                    ) .
                        html_writer::start_tag('tr') .
                        html_writer::start_tag('td', ['id' => 'existingcell'])
                );

                $mform->addElement('html', $this->currentcourses->display(true));

                $enrolbuttonshtml = html_writer::end_tag('td') .
                                    html_writer::start_tag('td', ['id' => 'buttonscell']) .
                                    html_writer::start_tag('p', ['class' => 'arrow_button']);

                // Can we allocate licenses?
                if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $this->context)) {
                    $enrolbuttonshtml .= html_writer::empty_tag(
                        'input',
                        [
                            'name' => 'add',
                            'id' => 'add',
                            'type' => 'submit',
                            'value' => $OUTPUT->larrow() . ' ' . get_string('licenseallocate', 'block_iomad_company_admin'),
                            'title' => get_string('licenseallocate', 'block_iomad_company_admin'),
                            'class' => 'btn btn-secondary',
                        ]
                    ) .
                    html_writer::empty_tag('br');
                }

                // Can we unallocate licenses?
                if (iomad::has_capability('block/iomad_company_admin:unallocate_licenses', $this->context)) {
                    $enrolbuttonshtml .= html_writer::empty_tag(
                        'input',
                        [
                            'name' => 'remove',
                            'id' => 'remove',
                            'type' => 'submit',
                            'value' => get_string('licenseremove', 'block_iomad_company_admin') . ' ' . $OUTPUT->rarrow(),
                            'title' => get_string('licenseremove', 'block_iomad_company_admin'),
                            'class' => 'btn btn-secondary',
                        ]
                    );
                }
                $enrolbuttonshtml .= html_writer::end_tag('p') .
                                     html_writer::end_tag('td') .
                                     html_writer::start_tag('td', ['id' => 'potencialcell']);

                $mform->addElement('html', $enrolbuttonshtml);

                $mform->addElement('html', $this->potentialcourses->display(true));

                $mform->addElement(
                    'html',
                    html_writer::end_tag('td') .
                        html_writer::end_tag('tr') .
                        html_writer::end_tag('table')
                );

                // Disable the onchange popup.
                $mform->disable_form_change_checker();
            }
        } else {
            $mform->addElement(
                'html',
                html_writer::empty_tag('br') .
                html_writer::start_tag('p', ['align' => 'center']) .
                html_writer::tag('b', get_string('selectlicenseblurb', 'block_iomad_company_admin')) .
                html_writer::end_tag('p'));
        }
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {

        $errors = [];

        // If we are removing we don't care about the date.
        if (optional_param('remove', false, PARAM_BOOL)) {
            $removing = true;
        } else {
            $removing = false;
        }

        // Is the due date valid?
        if (!empty($data['due']) && $data['due'] > $this->license->expirydate && !$removing) {
            $errors['due'] = get_string('licensedueafterexpirywarning', 'block_iomad_company_admin');
        }
        if (!empty($data['due']) && $data['due'] < $this->license->startdate && !$removing) {
            $errors['due'] = get_string('licenseduebeforestartwarning', 'block_iomad_company_admin');
        }

        return $errors;
    }

    /**
     * Process the form
     *
     * @return void
     */
    public function process() {
        global $DB, $CFG;

        if ($this->is_validated()) {
            $this->create_course_selectors();

            // Process program changes.
            if (optional_param('submitbutton', false, PARAM_BOOL) && confirm_sesskey()) {
                $inuse = optional_param('inuse', false, PARAM_BOOL);
                $allocate = optional_param('allocate', false, PARAM_BOOL);
                if ($inuse == $allocate && $allocate == 1) {
                    return;
                }
                if ($licenserecord = (array) $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid])) {
                    if ($allocate && ($licenserecord['used'] + count($this->liccourses) > $licenserecord['allocation'])) {
                        echo html_writer::tag(
                            'div',
                            html_writer::tag(
                                'span',
                                get_string('triedtoallocatetoomanylicenses', 'block_iomad_company_admin'),
                                [
                                    'class' => 'error',
                                ]
                            ),
                            [
                                'class' => 'mform',
                            ]
                        );
                        return;
                    } else {
                        $due = optional_param_array('due', [], PARAM_INT);
                        if (!empty($due)) {
                            $duedate = strtotime($due['year'] . '-' .
                                                 $due['month'] . '-' .
                                                 $due['day'] . ' ' .
                                                 $due['hour'] . ':' .
                                                 $due['minute']);
                        } else {
                            $duedate = 0;
                        }
                        // Is the user using any of the licenses and it's not a subscription?
                        if (!$allocate &&
                            $licenserecord['type'] == 0 &&
                            $DB->get_records('local_iomad_company_license_users', [
                                'userid' => $this->userid,
                                'licenseid' => $licenserecord['id'],
                                'isusing' => 1,
                                ])
                            ) {
                            return;
                        }

                        // Deal with the course allocations/removals.
                        foreach ($this->liccourses as $course) {
                            if ($allocate) {
                                $assignrecord = [
                                    'userid' => $this->userid,
                                    'licenseid' => $licenserecord['id'],
                                    'isusing' => 0,
                                    'licensecourseid' => $course->id,
                                ];

                                // Check we are not adding multiple times.
                                if (!$DB->get_record('local_iomad_company_license_users', $assignrecord)) {
                                    $assignrecord['issuedate'] = time();
                                    $assignrecord['id'] = $DB->insert_record('local_iomad_company_license_users', $assignrecord);

                                    // Create an event.
                                    $eventother = [
                                        'licenseid' => $licenserecord['id'],
                                        'issuedate' => $assignrecord['issuedate'],
                                        'duedate' => $duedate,
                                    ];
                                    $event = user_license_assigned::create([
                                        'context' => context_course::instance($course->id),
                                        'objectid' => $assignrecord['id'],
                                        'courseid' => $course->id,
                                        'userid' => $this->userid,
                                        'other' => $eventother,
                                    ]);
                                    $event->trigger();
                                }
                            } else {
                                $userlicenserecord = $DB->get_record('local_iomad_company_license_users', [
                                    'userid' => $this->userid,
                                    'licensecourseid' => $course->id,
                                    'licenseid' => $licenserecord['id'],
                                ]);
                                if (!empty($userlicenserecord->id)) {
                                    $DB->delete_records('local_iomad_company_license_users', ['id' => $userlicenserecord->id]);

                                    // Create an event.
                                    $eventother = [
                                        'licenseid' => $licenserecord['id'],
                                        'duedate' => 0,
                                    ];
                                    $event = user_license_unassigned::create([
                                        'context' => context_course::instance($course->id),
                                        'objectid' => $licenserecord['id'],
                                        'courseid' => $course->id,
                                        'userid' => $this->userid,
                                        'other' => $eventother,
                                    ]);
                                    $event->trigger();
                                }
                            }
                        }
                    }
                    return;
                }
            }

            // Process incoming enrolments.
            if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
                $coursestoassign = $this->potentialcourses->get_selected_courses();
                if (!empty($coursestoassign)) {

                    if ($licenserecord = (array) $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid])) {
                        if ($licenserecord['used'] + count($coursestoassign) > $licenserecord['allocation']) {
                            echo html_writer::tag(
                                'div',
                                html_writer::tag(
                                    'span',
                                    get_string('triedtoallocatetoomanylicenses', 'block_iomad_company_admin'),
                                    [
                                        'class' => 'error',
                                    ]
                                ),
                                [
                                    'class' => 'mform',
                                ]
                            );
                        } else {
                            $due = optional_param_array('due', [], PARAM_INT);
                            if (!empty($due)) {
                                $duedate = strtotime($due['year'] . '-' .
                                                     $due['month'] . '-' .
                                                     $due['day'] . ' ' .
                                                     $due['hour'] . ':' .
                                                     $due['minute']);
                            } else {
                                $duedate = 0;
                            }
                            foreach ($coursestoassign as $addcourse) {
                                $assignrecord = [
                                    'userid' => $this->userid,
                                    'licenseid' => $licenserecord['id'],
                                    'isusing' => 0,
                                    'licensecourseid' => $addcourse->id,
                                ];

                                // Check we are not adding multiple times.
                                if (!$DB->get_record('local_iomad_company_license_users', $assignrecord)) {
                                    $assignrecord['issuedate'] = time();
                                    $userlicid = $DB->insert_record('local_iomad_company_license_users', $assignrecord);

                                    // Create an event.
                                    $eventother = [
                                        'licenseid' => $licenserecord['id'],
                                        'issuedate' => $assignrecord['issuedate'],
                                        'duedate' => $duedate,
                                    ];
                                    $event = user_license_assigned::create([
                                        'context' => context_course::instance($addcourse->id),
                                        'objectid' => $userlicid,
                                        'courseid' => $addcourse->id,
                                        'userid' => $this->userid,
                                        'other' => $eventother,
                                    ]);
                                    $event->trigger();
                                }
                            }
                        }
                    }

                    $this->potentialcourses->invalidate_selected_courses();
                    $this->currentcourses->invalidate_selected_courses();
                }
            }

            // Process incoming unenrolments.
            if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
                $coursestounassign = $this->currentcourses->get_selected_courses();
                if (!empty($coursestounassign)) {
                    foreach ($coursestounassign as $removecourse) {
                        if ($userlicenserecord = $DB->get_record('local_iomad_company_license_users',
                                                                 ['id' => $removecourse->id])) {
                            $licenserecord = (array) $DB->get_record('local_iomad_company_licenses', ['id' => $userlicenserecord->licenseid]);
                            if ($userlicenserecord->isusing == 0 || $licenserecord['type'] != 0) {
					$DB->delete_records('local_iomad_company_license_users', ['id' => $userlicenserecord->id]);

                                // Create an event.
                                $eventother = [
                                    'licenseid' => $licenserecord['id'],
                                    'duedate' => 0,
                                ];
                                $event = user_license_unassigned::create([
                                    'context' => context_course::instance($userlicenserecord->licensecourseid),
                                    'objectid' => $licenserecord['id'],
                                    'courseid' => $userlicenserecord->licensecourseid,
                                    'userid' => $this->userid,
                                    'other' => $eventother,
                                ]);
                                $event->trigger();
                            }
                        }
                    }

                    $this->potentialcourses->invalidate_selected_courses();
                    $this->currentcourses->invalidate_selected_courses();
                }
            }
        }
    }
}
