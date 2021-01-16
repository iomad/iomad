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
 * Cron running check
 *
 * @package    tool_task
 * @copyright  2020 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_task\check;

defined('MOODLE_INTERNAL') || die();

use core\check\check;
use core\check\result;
/**
 * Cron running check
 *
 * @package    tool_task
 * @copyright  2020 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cronrunning extends check {

    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        $this->id = 'cronrunning';
        $this->name = get_string('checkcronrunning', 'tool_task');
        if (empty($CFG->cronclionly)) {
            $this->actionlink = new \action_link(
                new \moodle_url('/admin/cron.php'),
                get_string('cron', 'admin'));
        }
    }

    /**
     * Return result
     * @return result
     */
    public function get_result() : result {
        global $CFG;

        // Eventually this should replace cron_overdue_warning and
        // cron_infrequent_warning.
        $lastcron = get_config('tool_task', 'lastcronstart');
        $expectedfrequency = $CFG->expectedcronfrequency ?? MINSECS;

        $delta = time() - $lastcron;

        $lastcroninterval = get_config('tool_task', 'lastcroninterval');

        $formatdelta    = format_time($delta);
        $formatexpected = format_time($expectedfrequency);
        $formatinterval = format_time($lastcroninterval);

        $details = format_time($delta);

        if ($delta > $expectedfrequency + MINSECS) {
            $status = result::WARNING;

            if ($delta > DAYSECS) {
                $status = result::CRITICAL;
            }

            if (empty($lastcron)) {
                if (empty($CFG->cronclionly)) {
                    $url = new \moodle_url('/admin/cron.php');
                    $summary = get_string('cronwarningneverweb', 'admin', [
                        'url' => $url->out(),
                        'expected' => $formatexpected,
                    ]);
                } else {
                    $summary = get_string('cronwarningnever', 'admin', [
                        'expected' => $formatexpected,
                    ]);
                }
            } else if (empty($CFG->cronclionly)) {
                $url = new \moodle_url('/admin/cron.php');
                $summary = get_string('cronwarning', 'admin', [
                    'url' => $url->out(),
                    'actual'   => $formatdelta,
                    'expected' => $formatexpected,
                ]);
            } else {
                $summary = get_string('cronwarningcli', 'admin', [
                    'actual'   => $formatdelta,
                    'expected' => $formatexpected,
                ]);
            }
            return new result($status, $summary, $details);
        }

        if ($lastcroninterval > $expectedfrequency) {
            $status = result::WARNING;
            $summary = get_string('croninfrequent', 'admin', [
                'actual'   => $formatinterval,
                'expected' => $formatexpected,
            ]);
            return new result($status, $summary, $details);
        }

        $status = result::OK;
        $summary = get_string('cronok', 'tool_task');

        return new result($status, $summary, $details);
    }
}

