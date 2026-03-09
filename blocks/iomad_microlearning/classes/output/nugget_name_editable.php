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
 * IOMAD Microlearning nugget name in-place editable class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\output;

use block_iomad_microlearning\event\nugget_updated;
use coding_exception;
use core\exception\moodle_exception;
use core_external;
use core\output\{inplace_editable, renderer_base};
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * IOMAD Microlearning nugget name in-place editable class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nugget_name_editable extends inplace_editable {

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
    public function __construct($nuggetid, $companyid, $threadid, $currentvalue) {
        // Check capabilities to get editable value.
        $context = context_company::instance($companyid);
        $editable = iomad::has_capability('block/iomad_microlearning:edit_nuggets', $context);

        // Invent an itemid.
        $itemid = $companyid . ':' . $threadid . ':' . $nuggetid;

        $value = json_encode($currentvalue);

        parent::__construct('block_iomad_microlearning', 'nugget_name', $itemid, $editable, $value, $value);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $currentvalue = json_decode($this->value);

        $this->value = $currentvalue;
        $this->displayvalue = $currentvalue;

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
        [$companyid, $threadid, $nuggetid] = explode(':', $itemid, 3);

        $companyid = clean_param($companyid, PARAM_INT);
        $threadid = clean_param($threadid, PARAM_INT);
        $nuggetid = clean_param($nuggetid, PARAM_INT);
        $nuggetname = clean_param($newvalue, PARAM_TEXT);;

        // Check user is enrolled in the course.
        $companycontext = context_company::instance($companyid);
        core_external::validate_context($companycontext);

        // Check permissions.
        iomad::require_capability('block/iomad_microlearning:edit_nuggets', $companycontext);

        if (!$nugget = $DB->get_record(
            'block_iomad_microlearning_nuggets',
            ['id' => $nuggetid, 'threadid' => $threadid])) {
            throw new coding_exception('Invalid nugget ID');
        }

        // Is the name empty?
        if (empty($nuggetname)) {
            throw new moodle_exception('nameemptyerror', 'block_iomad_microlearning');
        }

        // Is the name in use?
        $comparename = $DB->sql_compare_text('name');
        $comparenameplaceholder = $DB->sql_compare_text(':nuggetname');
        if ($DB->record_exists_select(
            'block_iomad_microlearning_nuggets',
            "threadid = :threadid AND {$comparename} = {$comparenameplaceholder} AND id <> :nuggetid",
            ['threadid' => $threadid, 'nuggetname' => $nuggetname, 'nuggetid' => $nuggetid])) {

            throw new moodle_exception('nameinuse', 'block_iomad_microlearning');
        }

        // Process changes.
        $DB->set_field('block_iomad_microlearning_nuggets', 'name', $nuggetname, ['id' => $nugget->id]);

        // Fire an event for this.
        $eventother = ['companyid' => $companyid];

        $event = nugget_updated::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $nugget->id,
            'other' => $eventother,
        ]);

        $event->trigger();

        return new self($nuggetid, $companyid, $threadid, $nuggetname);
    }
}
