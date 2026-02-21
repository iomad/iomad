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
 * IOMAD Dashboard course mandatory inplace editable class
 *
 * @package   block_iomad_company_admin
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\output;

use block_iomad_company_admin\event\company_course_updated;
use coding_exception;
use core\output\{inplace_editable, renderer_base};
use core_external;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

/**
 * IOMAD Dashboard course mandatory inplace editable class
 *
 * @package   block_iomad_company_admin
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses_mandatory_editable extends inplace_editable {

    /** @var $context */
    private $context = null;

    /** @var stdClass[] $viewableroles */
    private $mandatoryoptions;

    /** @var string $displayvalues */
    protected $displayvalue;

    /**
     * Constructor.
     *
     * @param stdClass $course The current course
     * @param context $context The course context
     * @param stdClass $user The current user
     * @param stdClass[] $courseroles The list of course roles.
     * @param stdClass[] $assignableroles The list of assignable roles in this course.
     * @param stdClass[] $profileroles The list of roles that should be visible in a users profile.
     * @param stdClass[] $userroles The list of user roles.
     */
    public function __construct($company, $companycontext, $course, $currentvalue) {

        // Check capabilities to get editable value.
        $editable = iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext);

        // Invent an itemid.
        $itemid = $company->id . ':' . $course->courseid;

        $value = $currentvalue;

        // Remember these for the display value.
        $this->mandatoryoptions = ['0' => get_string('no'),
                                   '1' => get_string('yes')];

        $this->context = $companycontext;

        parent::__construct('block_iomad_company_admin', 'courses_mandatory', $itemid, $editable, $value, $value);

        $this->set_type_select($this->mandatoryoptions);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $value = json_decode($this->value);

        $this->displayvalue = format_string($this->mandatoryoptions[$value], true, ['context' => $this->context]);

        return parent::export_for_template($output);
    }

    /**
     * Updates the value in database and returns itself, called from inplace_editable callback
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $CFG, $USER;

        require_once($CFG->libdir . '/external/externallib.php');
        // Check caps.
        // Do the thing.
        // Return one of me.
        // Validate the inputs.
        list($companyid, $courseid) = explode(':', $itemid, 2);

        $companyid = clean_param($companyid, PARAM_INT);
        $company = new company($companyid);
        $courseid = clean_param($courseid, PARAM_INT);
        $mandatory = json_decode($newvalue);
        $mandatory = clean_param($mandatory, PARAM_INT);

        // Check company context is valid.
        $companycontext = context_company::instance($companyid);
        core_external::validate_context($companycontext);

        // Check permissions.
        iomad::require_capability('block/iomad_company_admin:managecourses', $companycontext);

        // Check that the course is set up in IOMAD.
        if (!$courserec = $DB->get_record('iomad_courses', ['courseid' => $courseid])) {
            throw new coding_exception('Course is not under IOMAD control');
        }

        // If this course isn't already in the company_course_options table, add it.
        if (!$currentrec = $DB->get_record('company_course_options', ['companyid' => $companyid, 'courseid' => $courseid])) {
            $currentrec = (object) ['companyid' => $companyid, 'courseid' => $courseid, 'mandatory' => 0];
            $currentrec->id = $DB->insert_record('company_course_options', $currentrec);
        }

        // Process changes.
        $DB->set_field('company_course_options', 'mandatory', $mandatory, ['id' => $currentrec->id]);

        // Fire an event for this.
        $eventother = ['iomadcourse' => (array) $courserec];
        $event = company_course_updated::create(['context' => $companycontext,
                                                 'objectid' => $courseid,
                                                 'userid' => $USER->id,
                                                 'other' => $eventother]);
        $event->trigger();

        return new self($company, $companycontext, $courserec, $mandatory);
    }
}
