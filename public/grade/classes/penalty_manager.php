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

namespace core_grades;

use core\context;
use core\plugininfo\gradepenalty;
use core_plugin_manager;
use grade_grade;
use grade_item;
use moodle_url;
use navigation_node;
use pix_icon;
use settings_navigation;
use stdClass;

/**
 * Manager class for grade penalty.
 *
 * @package   core_grades
 * @copyright 2024 Catalyst IT Australia Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class penalty_manager {
    /**
     * The freeze sentinel value stored in the `gradebook_calculations_freeze_<courseid>` config setting
     * for courses that may be affected by the pre-MDL-88407 penalty calculation bug.
     *
     * A course is treated as needing the legacy penalty calculation while its stored freeze value is
     * less than or equal to this constant.
     */
    const PENALTY_CALCULATION_FREEZE_VERSION = 20260808;

    /**
     * Whether a course's gradebook is currently frozen specifically for the pre-MDL-88407 penalty
     * calculation bug.
     *
     * @param int $courseid The course id.
     * @return bool
     */
    public static function is_frozen_for_legacy_penalty(int $courseid): bool {
        $freeze = get_config('core', 'gradebook_calculations_freeze_' . $courseid);
        return $freeze && (int)$freeze <= self::PENALTY_CALCULATION_FREEZE_VERSION;
    }

    /**
     * List the modules that support the grade penalty feature.
     *
     * @return array list of supported modules.
     */
    public static function get_supported_modules(): array {
        $plugintype = 'mod';
        $mods = \core_component::get_plugin_list($plugintype);
        $supported = [];
        foreach ($mods as $mod => $plugindir) {
            if (plugin_supports($plugintype, $mod, FEATURE_GRADE_HAS_PENALTY)) {
                $supported[] = $mod;
            }
        }
        return $supported;
    }

    /**
     * List the modules that currently have the grade penalty feature enabled.
     *
     * @return array List of enabled modules.
     */
    public static function get_enabled_modules(): array {
        return array_filter(explode(',', get_config('core', 'gradepenalty_enabledmodules')));
    }

    /**
     * Enable the grade penalty feature for a module.
     *
     * @param string $module The module name (e.g. 'assign').
     */
    public static function enable_module(string $module): void {
        self::enable_modules([$module]);
    }

    /**
     * Enable the grade penalty feature for multiple modules.
     *
     * @param array $modules List of module names.
     */
    public static function enable_modules(array $modules): void {
        $result = array_unique(array_merge(self::get_enabled_modules(), $modules));
        set_config('gradepenalty_enabledmodules', implode(',', $result));
    }

    /**
     * Disable the grade penalty feature for a module.
     *
     * @param string $module The module name (e.g. 'assign').
     */
    public static function disable_module(string $module): void {
        self::disable_modules([$module]);
    }

    /**
     * Disable the grade penalty feature for multiple modules.
     *
     * @param array $modules List of module names.
     */
    public static function disable_modules(array $modules): void {
        $result = array_diff(self::get_enabled_modules(), $modules);
        set_config('gradepenalty_enabledmodules', implode(',', $result));
    }

    /**
     * Check if the module has the grade penalty feature enabled.
     *
     * @param string $module The module name (e.g. 'assign').
     * @return bool Whether grade penalties are enabled for the module.
     */
    public static function is_penalty_enabled_for_module(string $module): bool {
        return in_array($module, self::get_enabled_modules());
    }

    /**
     * Whether the grade penalty feature is enabled for a grade.
     *
     * @param grade_grade $grade
     * @return bool
     */
    private static function is_penalty_enabled_for_grade(grade_grade $grade): bool {
        if (empty($grade)) {
            return false;
        }

        $grademin = $grade->get_grade_min();

        // No penalty for minimum grades.
        if ($grade->rawgrade <= $grademin) {
            return false;
        }

        if ($grade->finalgrade <= $grademin) {
            return false;
        }

        // No penalty for overridden grades.
        // We may need a separate setting to allow grade penalties for overridden grades.
        if (!empty($grade->overridden)) {
            return false;
        }

        // No penalty for locked grades.
        if (!empty($grade->locked)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate grade penalties for a user and their grade via the enabled penalty plugins.
     *
     * @param penalty_container $container The penalty container.
     * @return penalty_container The penalty container with the calculated penalties.
     */
    private static function calculate_penalties(penalty_container $container): penalty_container {
        // Iterate through all the penalty plugins to calculate the total penalty.
        foreach (core_plugin_manager::instance()->get_plugins_of_type('gradepenalty') as $pluginname => $plugin) {
            if (gradepenalty::is_plugin_enabled($pluginname)) {
                $classname = "\\gradepenalty_{$pluginname}\\penalty_calculator";
                if (class_exists($classname)) {
                    $classname::calculate_penalty($container);
                }
            }
        }
        // Returning the container is not strictly necessary but makes it clear the container is being modified.
        return $container;
    }

    /**
     * Apply grade penalties to a user.
     *
     * Grade penalties are determined by the enabled penalty plugin.
     * This function should be called each time a module creates or updates a grade item for a user.
     *
     * @param int $userid The user ID
     * @param grade_item $gradeitem grade item
     * @param int $submissiondate submission date
     * @param int $duedate due date
     * @param bool $previewonly do not update the grade if true, only return the penalty
     * @return penalty_container Information about the applied penalty.
     */
    public static function apply_grade_penalty_to_user(
        int $userid,
        grade_item $gradeitem,
        int $submissiondate,
        int $duedate,
        bool $previewonly = false
    ): penalty_container {

        try {
            $container = self::apply_penalty($userid, $gradeitem, $submissiondate, $duedate, $previewonly);
        } catch (\core\exception\moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER);
        }
        return $container;
    }

    /**
     * Fetch the penalty for a user based on the submission date and due date and deduct marks from the grade item accordingly.
     *
     * @param int $userid The user ID.
     * @param grade_item $gradeitem The grade item.
     * @param int $submissiondate The date and time of the user submission.
     * @param int $duedate The date and time the submission is due.
     * @param bool $previewonly If true, the grade will not be updated.
     * @return penalty_container The penalty container containing information about the applied penalty.
     */
    private static function apply_penalty(
        int $userid,
        grade_item $gradeitem,
        int $submissiondate,
        int $duedate,
        bool $previewonly = false
    ): penalty_container {

        // Get the grade and create a penalty container.
        $grade = $gradeitem->get_grade($userid);
        $container = new penalty_container($gradeitem, $grade, $submissiondate, $duedate);

        // Do not apply penalties if the module is disabled.
        if (!self::is_penalty_enabled_for_module($gradeitem->itemmodule)) {
            return $container;
        }

        // Do not apply penalties if the grade is not eligible.
        if (!self::is_penalty_enabled_for_grade($grade)) {
            return $container;
        }

        // Call all penalty plugins to calculate the penalty.
        $container = self::calculate_penalties($container);

        // Update the grade if not in preview mode.
        if (!$previewonly) {
            // Check whether the gradebook is frozen and this grade is confirmed to use the legacy
            // pre-MDL-88407 representation. Only confirmed legacy grades use the legacy calculation,
            // which stores the already-penalised value in rawgrade. Grades that are already correct,
            // or cannot be confirmed as legacy, use the normal calculation to avoid corrupting rawgrade
            // (MDL-89497).
            if (
                self::is_frozen_for_legacy_penalty($gradeitem->courseid)
                && self::requires_legacy_penalty_calculation(
                    $grade,
                    self::get_authoritative_user_grades($gradeitem),
                    $gradeitem
                )
            ) {
                // Update the raw grade and store the deducted mark.
                $gradeitem->update_raw_grade($userid, $container->get_grade_after_penalties(), 'gradepenalty');
                $gradeitem->update_deducted_mark($userid, $container->get_penalty());
            } else {
                $oldfinalgrade = $grade->finalgrade;

                // Read the penalised raw grade before updating deductedmark, as get_grade_after_penalties()
                // re-checks whether the grade is legacy-corrupted using the current stored grade state.
                // Updating deductedmark first would cause it to see a partially updated state and could apply
                // the penalty incorrectly (see test_frozen_gradebook_uses_fixed_calculation_for_verified_grade, MDL-89749).
                $penalisedraw = $container->get_grade_after_penalties();
                $grade->deductedmark = $container->get_penalty();
                $grade->finalgrade = self::apply_grade_item_factors($penalisedraw, $gradeitem, $grade);

                $grade->timemodified = time();
                $grade->update('gradepenalty');

                if (grade_floats_different($grade->finalgrade, $oldfinalgrade)) {
                    \core\event\user_graded::create_from_grade($grade)->trigger();
                    // Regrade parent category/course totals so the penalised finalgrade
                    // is preserved and updated through nested categories if present.
                    if (!$gradeitem->needsupdate && !grade_item::fetch_course_item($gradeitem->courseid)->needsupdate) {
                        $updateditem = grade_item::fetch([
                            'itemtype' => 'category',
                            'iteminstance' => $gradeitem->categoryid,
                            'courseid' => $gradeitem->courseid,
                        ]) ?: grade_item::fetch_course_item($gradeitem->courseid);
                        if (grade_regrade_final_grades($gradeitem->courseid, $userid, $updateditem) !== true) {
                            // Fast regrade failed; mark the parent (category/course) item for regrading.
                            $updateditem->force_regrading();
                        }
                    }
                }
            }
        }

        return $container;
    }

    /**
     * Apply grade-item multfactor/plusfactor to a raw penalised grade, returning a value
     * on the same scale as the gradebook finalgrade.
     *
     * @param float $rawgrade The penalised raw grade (before grade-item factors are applied).
     * @param grade_item $gradeitem The grade item whose multfactor/plusfactor to apply.
     * @param grade_grade|null $usergrade The user's grade_grade record, which carries the
     *        rawgrademin/rawgrademax stored at grading time. Falls back to gradeitem
     *        grademin/grademax when null or when the record has not yet been persisted.
     * @return float|null The adjusted grade, or null when rawgrade is null.
     */
    public static function apply_grade_item_factors(
        float $rawgrade,
        grade_item $gradeitem,
        ?grade_grade $usergrade = null
    ): ?float {
        $rawmin = !empty($usergrade->id) ? $usergrade->rawgrademin : $gradeitem->grademin;
        $rawmax = !empty($usergrade->id) ? $usergrade->rawgrademax : $gradeitem->grademax;
        return $gradeitem->adjust_raw_grade($rawgrade, $rawmin, $rawmax);
    }

    /**
     * Fetches the authoritative raw grades for every user in an Assignment instance using
     * Assignment's own get_user_grades() function.
     *
     * This is deliberately restricted to mod_assign. {$modname}_get_user_grades() is an internal
     * convention followed by only a handful of core modules, not a formal inter-component contract.
     * In particular, how an ungraded attempt is represented is module-specific and cannot safely be
     * interpreted generically.
     *
     * @param grade_item $gradeitem The grade item whose module instance to query.
     * @return array|null Grades indexed by userid, or null if this is not an Assignment grade item, or
     *                     Assignment's get_user_grades() could not be found.
     */
    public static function get_authoritative_user_grades(grade_item $gradeitem): ?array {
        global $DB;

        if ($gradeitem->itemtype !== 'mod' || $gradeitem->itemmodule !== 'assign') {
            return null;
        }

        $modinstance = $DB->get_record($gradeitem->itemmodule, ['id' => $gradeitem->iteminstance]);
        if (!$modinstance) {
            return null;
        }

        // Use component_callback(), rather than a direct require_once() and call into mod_assign's
        // own library, so that core_grades does not reach directly into another component's internals.
        return component_callback('mod_' . $gradeitem->itemmodule, 'get_user_grades', [$modinstance, 0], null);
    }

    /**
     * Whether a penalised grade should use the legacy pre-MDL-88407 calculation.
     *
     * A legacy calculation is used when the gradebook is frozen and the grade's stored rawgrade
     * differs from Assignment's authoritative raw grade. Grades for ungraded latest attempts are
     * not treated as legacy because there is no authoritative grade to compare against.
     *
     * A second check handles cases where the stored rawgrade matches the authoritative grade but the
     * grade may still be affected by the legacy calculation. It recomputes finalgrade using the stored
     * rawgrade and deductedmark with the fixed post-MDL-88407 formula, and considers the grade legacy
     * if this differs from the stored finalgrade.
     *
     * If the authoritative grades cannot be obtained, the legacy calculation is used conservatively.
     *
     * @param grade_grade $grade The grade to check.
     * @param array|null $authoritativegrades Assignment grades indexed by userid, or null if unavailable.
     * @param grade_item $gradeitem The grade item, used to recompute the legacy and fixed finalgrade.
     * @return bool
     */
    public static function requires_legacy_penalty_calculation(
        grade_grade $grade,
        ?array $authoritativegrades,
        grade_item $gradeitem
    ): bool {
        if ($authoritativegrades === null) {
            // Not an Assignment grade item, or Assignment's get_user_grades() could not be found - there
            // is no way to check, so conservatively assume this row may still be legacy-corrupted.
            return true;
        }

        if (!isset($authoritativegrades[$grade->userid])) {
            // No current authoritative grade for this student's latest attempt at all.
            return false;
        }

        $originalraw = $authoritativegrades[$grade->userid]->rawgrade;

        // Null and -1 both represent an ungraded attempt - same reasoning as above.
        if ($originalraw === null || (float)$originalraw === -1.0) {
            return false;
        }

        if (grade_floats_different($grade->rawgrade, $originalraw)) {
            // A genuine mismatch is the positive signal that this row is still legacy-corrupted.
            return true;
        }

        // The stored rawgrade matches the authoritative mark, but that alone can be a coincidence.
        // The legacy formula applies grade-item factors to rawgrade directly, ignoring deductedmark;
        // the fixed formula subtracts deductedmark from rawgrade first.
        $legacyfinalgrade = $gradeitem->adjust_raw_grade($grade->rawgrade, $grade->rawgrademin, $grade->rawgrademax);
        $fixedpenalisedraw = max($gradeitem->grademin, $grade->rawgrade - $grade->deductedmark);
        $fixedfinalgrade = $gradeitem->adjust_raw_grade($fixedpenalisedraw, $grade->rawgrademin, $grade->rawgrademax);

        return !grade_floats_different($grade->finalgrade, $legacyfinalgrade)
            && grade_floats_different($grade->finalgrade, $fixedfinalgrade);
    }

    /**
     * Repairs rawgrade values left in the legacy representation by MDL-88407.
     *
     * A grade is repaired when:
     * - it is an Assignment grade with a deducted mark and a non-null rawgrade;
     * - its grade item is unlocked;
     * - the grade itself is unlocked and not overridden;
     * - Assignment's own current raw grade differs from the stored gradebook rawgrade.
     *
     * Assignment's own get_user_grades() function is used as the authoritative source for the current
     * raw grade (see get_authoritative_user_grades() for why this is restricted to Assignment). Grades
     * for ungraded attempts are skipped.
     *
     * @param int|null $courseid Specify a course ID to run this script on just one course.
     */
    public static function repair_penalised_rawgrade(?int $courseid = null): void {
        global $DB;

        $params = [];
        $singlecoursesql = '';
        if ($courseid !== null) {
            $singlecoursesql = 'AND gi.courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        // Find grade items with penalised grades that may need to be repaired.
        $sql = "SELECT DISTINCT gi.id
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gg.deductedmark > 0
                   AND gg.rawgrade IS NOT NULL
                   AND gi.locked = 0
                   AND gg.locked = 0
                   AND gg.overridden = 0
                   AND gi.itemtype = 'mod'
                   AND gi.itemmodule = 'assign'
                   AND gi.gradetype = :gradetype
                   $singlecoursesql";

        $params['gradetype'] = GRADE_TYPE_VALUE;
        $itemids = $DB->get_fieldset_sql($sql, $params);

        foreach ($itemids as $itemid) {
            $gradeitem = grade_item::fetch(['id' => $itemid]);
            if (!$gradeitem) {
                continue;
            }

            $modgrades = self::get_authoritative_user_grades($gradeitem);
            if ($modgrades === null) {
                continue;
            }

            $sql = 'itemid = :itemid
                    AND deductedmark > 0
                    AND rawgrade IS NOT NULL
                    AND locked = 0
                    AND overridden = 0
                    AND finalgrade IS NOT NULL';

            $params = [
                'itemid' => $itemid,
            ];

            $graderecords = $DB->get_recordset_select('grade_grades', $sql, $params);
            foreach ($graderecords as $graderecord) {
                if (!isset($modgrades[$graderecord->userid])) {
                    continue;
                }

                $originalraw = $modgrades[$graderecord->userid]->rawgrade;

                // Null and -1 both represent an ungraded attempt, so skip it.
                if ($originalraw === null || (float)$originalraw === -1.0) {
                    continue;
                }

                if (grade_floats_different($graderecord->rawgrade, $originalraw)) {
                    // The module's current raw grade is the authoritative value. If it differs
                    // from the stored gradebook rawgrade, repair the gradebook value.
                    $DB->set_field('grade_grades', 'rawgrade', $originalraw, ['id' => (int)$graderecord->id]);
                }
            }
            $graderecords->close();
        }
    }

    /**
     * Returns the penalty indicator HTML code if a penalty is applied to the grade.
     * Otherwise, returns an empty string.
     *
     * @param grade_grade $grade Grade object
     * @return string HTML code for penalty indicator
     */
    public static function show_penalty_indicator(grade_grade $grade): string {
        global $PAGE;

        // Show penalty indicator if penalty is greater than 0.
        if ($grade->is_penalty_applied_to_final_grade()) {
            $indicator = new \core_grades\output\penalty_indicator(2, $grade);
            $renderer = $PAGE->get_renderer('core_grades');
            return $renderer->render_penalty_indicator($indicator);
        }

        return '';
    }

    /**
     * Allow penalty plugin to extend course navigation.
     *
     * @param navigation_node $navigation The navigation node
     * @param stdClass $course The course object
     * @param context $coursecontext The course context
     */
    public static function extend_navigation_course(navigation_node $navigation,
                                                    stdClass $course,
                                                    context $coursecontext): void {
        // Create new navigation node for grade penalty.
        $penaltynav = $navigation->add(get_string('gradepenalty', 'core_grades'),
            new moodle_url('/grade/penalty/view.php', ['contextid' => $coursecontext->id]),
            navigation_node::TYPE_CONTAINER, null, 'gradepenalty', new pix_icon('i/grades', ''));

        // Allow plugins to extend the navigation.
        $pluginfunctions = get_plugin_list_with_function('gradepenalty', 'extend_navigation_course');
        foreach ($pluginfunctions as $plugin => $function) {
            if (gradepenalty::is_plugin_enabled($plugin)) {
                $function($penaltynav, $course, $coursecontext);
            }
        }

        // Do not display the node if there are no children.
        if (!$penaltynav->has_children()) {
            $penaltynav->remove();
        }
    }

    /**
     * Allow penalty plugin to extend navigation module.
     *
     * @param settings_navigation $settings The settings navigation object
     * @param navigation_node $navref The navigation node
     * @return void
     */
    public static function extend_navigation_module(settings_navigation $settings, navigation_node $navref): void {
        $context = $settings->get_page()->context;
        $cm = $settings->get_page()->cm;

        // Create new navigation node for grade penalty.
        $penaltynav = $navref->add(get_string('gradepenalty', 'core_grades'),
            new moodle_url('/grade/penalty/view.php', ['contextid' => $context->id, 'cm' => $cm->id]),
            navigation_node::TYPE_CONTAINER, null, 'gradepenalty', new pix_icon('i/grades', ''));

        // Allow plugins to extend the navigation.
        $pluginfunctions = get_plugin_list_with_function('gradepenalty', 'extend_navigation_module');
        foreach ($pluginfunctions as $plugin => $function) {
            if (gradepenalty::is_plugin_enabled($plugin) && self::is_penalty_enabled_for_module($cm->modname)) {
                $function($penaltynav, $cm);
            }
        }

        // Do not display the node if there are no children.
        if (!$penaltynav->has_children()) {
            $penaltynav->remove();
        }
    }

    /**
     * Recalculate grade penalties
     *
     * @param context $context The context
     * @param int $usermodified The user who triggered the recalculation
     * return void
     */
    public static function recalculate_penalty(context $context, int $usermodified = 0): void {
        if ($usermodified == 0) {
            global $USER;
            $usermodified = $USER->id;
        }

        // Get enabled modules.
        $enabledmodules = self::get_enabled_modules();

        foreach ($enabledmodules as $module) {
            // If it is in a module context, make sure the module is the same as the enabled module.
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cmid = $context->instanceid;
                $cm = get_coursemodule_from_id($module, $cmid);
                if (empty($cm)) {
                    continue;
                }
            }

            // Check if the module supports has penalty recalculator class.
            $classname = "\\mod_{$module}\\penalty_recalculator";
            if (class_exists($classname)) {
                $classname::recalculate_penalty($context, $usermodified);
            }
        }
    }
}
