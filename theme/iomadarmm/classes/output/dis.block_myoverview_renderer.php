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
 * myoverview block rendrer
 *
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_iomadarmm\output;
defined('MOODLE_INTERNAL') || die;

use block_myoverview\output\main;
use course_in_list;
use stdClass;


/**
 * myoverview block renderer
 *
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_myoverview_renderer extends \block_myoverview\output\renderer {

    /**
     * Return the main content for the block overview.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) {
        global $CFG;
        $context = $main->export_for_template($this);
        if ($context['coursesview']['hascourses'] == 1) {
            
            $states = [];
            if (isset($context['coursesview']['inprogress'])) {
                $states['inprogress'] = $context['coursesview']['inprogress']['pages'];
            }
            if (isset($context['coursesview']['future'])) {
                $states['future'] = $context['coursesview']['future']['pages'];
            }
            if (isset($context['coursesview']['past'])) {
                $states['past'] = $context['coursesview']['past']['pages'];
            }

            if ($states) {
                foreach($states as $state) {
                    foreach ($state as $page) {
                        $courses = $page['courses'];

                        foreach($courses as $course) {
                            if ($course instanceof stdClass) {
                                require_once($CFG->libdir. '/coursecatlib.php');
                                $courseobj = new course_in_list($course);
                            }
                                
                            $course->hasimg = false;
                            
                            $courseoverviewfiles = $courseobj->get_course_overviewfiles();
                            if(isset($courseoverviewfiles)) {
                                foreach($courseoverviewfiles as $file) {
                                    $isimage = $file->is_valid_image();
                                    if($isimage) {
                                        $course->imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
                                        $course->hasimg = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->render_from_template('block_myoverview/main', $context);
    }
}
