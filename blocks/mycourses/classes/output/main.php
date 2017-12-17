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
 * Class containing data for my overview block.
 *
 * @package    block_mycourses
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_mycourses\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use core_completion\progress;
use  core_course_renderer;

require_once($CFG->dirroot . '/blocks/mycourses/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/coursecatlib.php');


/**
 * Class containing data for my overview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /**
     * @var string The tab to display.
     */
    public $tab;

    /**
     * Constructor.
     *
     * @param string $tab The tab to display.
     */
    public function __construct($tab) {
        $this->tab = $tab;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER;

        // Get the cut off date.
        $cutoffdate = time() - ($CFG->mycourses_archivecutoff * 24 * 60 * 60);

        // Get the completion info.
        $mycompletion = mycourses_get_my_completion();

        $availableview = new available_view($mycompletion, $cutoffdate);
        $inprogressview = new inprogress_view($mycompletion, $cutoffdate);
        $completedview = new completed_view($mycompletion, $cutoffdate);

        // Now, set the tab we are going to be viewing.
        $viewingavailable = false;
        $viewinginprogress = false;
        $viewingcompleted = false;
        if ($this->tab == 'available') {
            $viewingavailable = true;
        } else if ($this->tab == 'completed') {
            $viewingcompleted = true;
        } else {
            $viewinginprogress = true;
        }

        return [
            'midnight' => usergetmidnight(time()),
            'availableview' => $availableview->export_for_template($output),
            'inprogressview' => $inprogressview->export_for_template($output),
            'completedview' => $completedview->export_for_template($output),
            'viewingavailable' => $viewingavailable,
            'viewinginprogress' => $viewinginprogress,
            'viewingcompleted' => $viewingcompleted
        ];
    }
}
