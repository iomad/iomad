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

namespace mod_assign\output;

use templatable;
use renderable;
use moodle_url;

/**
 * Output the actionbar for this activity.
 *
 * @package   mod_assign
 * @copyright 2021 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actionmenu implements templatable, renderable {

    /** @var int The course module ID. */
    private int $cmid;

    /** @var bool Are we using marking workflow? */
    private bool $markingworkflow;

    /**
     * Constructor for this object.
     *
     * @param int $cmid The course module ID.
     * @param bool $markingworkflow Are we using marking workflow?
     */
    public function __construct(int $cmid, bool $markingworkflow) {
        $this->cmid = $cmid;
        $this->markingworkflow = $markingworkflow;
    }

    /**
     * Data to be used for a template.
     *
     * @param  \renderer_base $output renderer base output.
     * @return array Data to be used for a template.
     */
    public function export_for_template(\renderer_base $output): array {
        $return = [];

        if (has_capability('mod/assign:grade', \context_module::instance($this->cmid))) {
            // If we are using multiple markers, then we'll have marker columns which need links to the marker page.
            if ($this->markingworkflow) {
                $gradelink = new moodle_url('/mod/assign/view.php', ['id' => $this->cmid, 'action' => 'marker']);
                $return['marklink'] = $gradelink->out(false);
            }
            $gradelink = new moodle_url('/mod/assign/view.php', ['id' => $this->cmid, 'action' => 'grader']);
            $return['gradelink'] = $gradelink->out(false);
        }

        return $return;
    }
}
