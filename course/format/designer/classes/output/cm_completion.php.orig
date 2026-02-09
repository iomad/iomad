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
 * Displays completion information badge for a cm.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output;

use cm_info;
use coding_exception;
use completion_info;
use core_availability\info;
use html_writer;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/course/format/designer/lib.php");

/**
 * Displays completion information badge for a cm.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm_completion implements renderable, templatable {

    /**
     * @var cm_info
     */
    private $cm;

    /**
     * @var completion_info[]
     */
    private static $completioninfos = [];

    /**
     * Constructor.
     *
     * @param cm_info $cm
     */
    public function __construct(cm_info $cm) {
        $this->cm = $cm;
    }

    /**
     * Get course module.
     *
     * @return cm_info
     */
    final protected function get_cm(): cm_info {
        return $this->cm;
    }

    /**
     * Get course module url.
     *
     * @return cm_info
     */
    final protected function get_cm_url() {
        return isset($this->cm->url) ? $this->cm->url : '';
    }

    /**
     * Check if completion info should be displayed at all.
     *
     * @return bool
     * @throws coding_exception
     */
    final public function is_visible(): bool {

        if (!$this->cm->uservisible) {
            if (!$this->is_restricted()) {
                return false;
            }
        }
        if (!isloggedin() || isguestuser() || $this->get_completion_mode() == COMPLETION_TRACKING_NONE) {
            return false;
        }
        return true;
    }

    /**
     * Get completion info object for this cm's course.
     *
     * @return completion_info
     */
    final public function get_completion_info(): completion_info {
        if (!isset(self::$completioninfos[$this->cm->course])) {
            self::$completioninfos[$this->cm->course] = new completion_info($this->cm->get_course());
        }

        return self::$completioninfos[$this->cm->course];
    }

    /**
     * Get completion info for this cm.
     *
     * @return stdClass
     */
    final public function get_completion_data(): stdClass {
        return $this->get_completion_info()->get_data($this->cm, true);
    }

    /**
     * Get completion mode:
     *
     * - COMPLETION_TRACKING_NONE
     * - COMPLETION_TRACKING_MANUAL
     * - COMPLETION_TRACKING_AUTOMATIC
     *
     * @return int
     */
    final public function get_completion_mode(): int {
        $mode = $this->get_completion_info()->is_enabled($this->cm);
        return $mode;
    }

    /**
     * Get completion state:
     *
     * - COMPLETION_INCOMPLETE
     * - COMPLETION_COMPLETE
     * - COMPLETION_COMPLETE_PASS
     * - COMPLETION_COMPLETE_FAIL
     *
     * @return int
     */
    final public function get_completion_state(): int {
        return !is_null($this->get_completion_data()->completionstate) ? $this->get_completion_data()->completionstate : 0;
    }

    /**
     * Check completion fail:
     *
     * @return int
     */
    final public function get_completion_fail(): bool {
        $result = false;
        if (isset($this->get_completion_data()->completiongrade)) {
            if ($this->get_completion_data()->completionstate == COMPLETION_COMPLETE_FAIL) {
                $result = true;
            }
        }
        return $result;
    }


    /**
     * Check if user is tracked for this cm.
     *
     * @param int|null $userid
     * @return bool
     */
    final public function is_tracked_user(int $userid = null): bool {
        global $USER;

        if (is_null($userid)) {
            $userid = $USER->id;
        }

        return $this->get_completion_info()->is_tracked_user($userid);
    }

    /**
     * Check if user is editing the course.
     *
     * @return bool
     */
    final public function is_editing(): bool {
        global $PAGE;
        return $PAGE->user_is_editing();
    }

    /**
     * Check if user completion state was overridden by another user (teacher, admin, etc).
     *
     * @return bool
     */
    final public function is_overridden(): bool {
        return isset($this->get_completion_data()->overrideby) && !empty($this->get_completion_data()->overrideby);
    }

    /**
     * Get date cm was completed. Basing off completion date. Also check completion state.
     *
     * @return int
     */
    final public function get_completion_date(): int {
        return !is_null($this->get_completion_data()->timemodified) ? $this->get_completion_data()->timemodified : 0;
    }

    /**
     * Get when cm must be completed by. Check is timemanagement tool contains any duedates for this module.
     *
     * @return int
     */
    final public function get_completion_expected(): int {
        if ($duedate = \format_designer\options::timetool_duedate($this->cm)) {
            return $duedate;
        }
        return $this->cm->completionexpected;
    }

    /**
     * Get user that overrode completion state of user.
     *
     * @see cm_completion::is_overridden()
     *
     * @return stdClass|null
     * @throws \dml_exception
     */
    final public function get_override_user(): ?stdClass {
        if ($user = \core_user::get_user($this->get_completion_data()->overrideby, '*', IGNORE_MISSING)) {
            $user->fullname = fullname($user);
            return $user;
        }
        return null;
    }

    /**
     * Check if cm is overdue for user.
     *
     * @return bool
     */
    final public function is_overdue(): bool {
        return $this->get_completion_expected() > 0 && $this->get_completion_expected() < strtotime("-1 day");
    }

    /**
     * Get time ago since cm was overdue.
     *
     * @return string
     */
    final public function get_overdue_by(): string {
        return $this->get_time_ago($this->get_completion_expected());
    }

    /**
     * Check if cm is due within a day.
     *
     * @return bool
     */
    final public function is_due_today(): bool {
        return $this->get_completion_expected() > 0 &&
            (date('y-m-d', $this->get_completion_expected()) == date('y-m-d'));
    }

    /**
     * Get manual completion checkbox. Note: Mostly legacy code from Moodle.
     *
     * @return string
     * @throws \dml_exception
     * @throws coding_exception
     */
    final public function get_completion_checkbox(): array {
        global $OUTPUT, $CFG;

        if ($this->get_completion_state() == COMPLETION_INCOMPLETE ||
        $this->get_completion_state() == COMPLETION_COMPLETE_FAIL) {
            $completionicon = 'manual-n' . ($this->get_completion_data()->overrideby ? '-override' : '');
        } else if ($this->get_completion_state() == COMPLETION_COMPLETE ||
            $this->get_completion_state() == COMPLETION_COMPLETE_PASS) {
            $completionicon = 'manual-y' . ($this->get_completion_data()->overrideby ? '-override' : '');
        }
        if ($this->is_overridden()) {
            $args = new stdClass();
            $args->modname = $this->get_cm_formatted_name();
            if ($overridebyuser = $this->get_override_user()) {
                $args->overrideuser = fullname($overridebyuser);
            }
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $args);
        } else {
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $this->get_cm_formatted_name());
        }

        $output = '';
        $newstate =
            $this->get_completion_state() == COMPLETION_COMPLETE
                ? COMPLETION_INCOMPLETE
                : COMPLETION_COMPLETE;
        // In manual mode the icon is a toggle form...

        // If this completion state is used by the
        // conditional activities system, we need to turn
        // off the JS.
        $extraclass = '';
        if (!empty($CFG->enableavailability) &&
            info::completion_value_used($this->cm->get_course(), $this->cm->id)) {
            $extraclass = ' preventjs';
        }
        $buttonclass = 'btn btn-link';
        if ($this->is_restricted()) {
            $buttonclass .= ' disabled';
        }

        $output = html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'id', 'value' => $this->cm->id, ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(), ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'modulename', 'value' => $this->get_cm_formatted_name(), ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate, ]);
        $output .= html_writer::end_tag('div');

        $manualcompletiondata = [
            'url' => new moodle_url('/course/togglecompletion.php'),
            'sesskey' => sesskey(),
            'modulename' => $this->get_cm_formatted_name(),
            'inputfield' => $output,
            'buttonclass' => $buttonclass,
        ];
        return $manualcompletiondata;
    }

    /**
     * Get formatted name for cm to use within strings or tooltips.
     *
     * @return string
     */
    final public function get_cm_formatted_name(): string {
        return html_entity_decode($this->cm->get_formatted_name(), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get Bootstrap color class for this cm completion status.
     *
     * @return string
     */
    final public function get_color_class(): string {
        if ($this->is_editing() || !$this->is_tracked_user()) {

            if ($this->is_restricted()) {
                return 'restricted';
            }
            if ($this->get_completion_mode() == COMPLETION_TRACKING_MANUAL) {
                return 'secondary';
            }
            if ($this->get_completion_mode() == COMPLETION_TRACKING_AUTOMATIC) {
                return 'info';
            }
        } else {
            if ($this->is_restricted()) {
                return 'restricted';
            }
            if ($this->get_completion_mode() == COMPLETION_TRACKING_NONE) {
                return 'secondary';
            }
            if (in_array($this->get_completion_state(), [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                if ($this->get_completion_expected() && $this->get_completion_expected() < time()) {
                    return 'due-success';
                } else {
                    return 'success';
                }
            }

            if ($this->get_completion_fail() == COMPLETION_COMPLETE_FAIL) {
                return 'danger';
            }

            if ($this->get_completion_state() == COMPLETION_INCOMPLETE) {
                if ($this->is_due_today()) {
                    return 'warning';
                } else if ($this->is_overdue()) {
                    return 'danger';
                } else {
                    return 'notstarted';
                }
            }
        }

        return 'secondary';
    }

    /**
     * Check if cm is restricted from the user.
     *
     * @return bool
     */
    final public function is_restricted(): bool {
        return !empty($this->cm->availableinfo);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $withavailability = false;
        $course = $this->cm->get_course();
        if ($this->get_completion_mode() != COMPLETION_TRACKING_NONE
            && $this->get_completion_mode() != COMPLETION_TRACKING_AUTOMATIC) {
            $withavailability = !empty($CFG->enableavailability) && info::completion_value_used($course, $this->cm->id);
        }

        $data = [
            'cmid' => $this->cm->id,
            'activityname' => $this->cm->name,
            'withavailability' => $withavailability,
            'istrackeduser' => $this->is_tracked_user(),
            'isediting' => $this->is_editing(),
            'ispreview' => $this->is_editing() || !$this->is_tracked_user(),
            'isoverridden' => $this->is_overridden(),
            'overrideuser' => $this->get_override_user(),
            'isoverdue' => $this->is_overdue(),
            'overdueby' => $this->get_overdue_by(),
            'duetoday' => $this->is_due_today(),
            'colorclass' => $this->get_color_class(),
            'completioncheckbox' => $this->get_completion_checkbox(),
            'completionexpected' => ($this->get_completion_expected()) ? true : false,
            'completiontrackingmanual' => $this->get_completion_mode() == COMPLETION_TRACKING_MANUAL,
            'completiontrackingautomatic' => $this->get_completion_mode() == COMPLETION_TRACKING_AUTOMATIC,
            'completionincomplete' => $this->get_completion_state() == COMPLETION_INCOMPLETE &&
            $this->get_completion_fail() == false,
            'completioncomplete' => $this->get_completion_state() == COMPLETION_COMPLETE,
            'completionincompletepass' => $this->get_completion_state() == COMPLETION_COMPLETE_PASS,
            'completionincompletefail' => $this->get_completion_fail(),
        ];
        if ($completiondate = $this->get_completion_date()) {
            $data['completiondate'] = format_designer_format_date($completiondate);
        }

        if ($completionexpected = $this->get_completion_expected()) {
            $data['completionexpected'] = format_designer_format_date($completionexpected);
        }

        return $data;
    }

    /**
     * Get the completion past time.
     * @param int $timestamp
     */
    private function get_time_ago(int $timestamp): string {
        $now = new \DateTime();
        $ago = new \DateTime('@' . $timestamp);
        $diff = $now->diff($ago);

        $weeks = floor($diff->d / 7);
        $diff->d -= $weeks * 7;

        $string = [
            'y' => get_string('timeagoyear', 'format_designer'),
            'm' => get_string('timeagomonth', 'format_designer'),
            'w' => get_string('timeagoweek', 'format_designer'),
            'd' => get_string('timeagoday', 'format_designer'),
            'h' => get_string('timeagohour', 'format_designer'),
            'i' => get_string('timeagominute', 'format_designer'),
            's' => get_string('timeagosecond', 'format_designer'),
        ];
        foreach ($string as $k => &$v) {
            $value = ($k == 'w') ? $weeks : $diff->$k;
            if ($value) {
                $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ' . get_string('timeago', 'format_designer')
            : get_string('timeagojustnow', 'format_designer');
    }
}
