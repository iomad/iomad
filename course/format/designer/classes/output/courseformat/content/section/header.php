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
 * Contains the default activity header from a section.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat\content\section;

use core\output\named_templatable;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use renderable;
use section_info;
use stdClass;


/**
 * Base class to render a section activity in the activities list.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header extends \core_courseformat\output\local\content\section\header {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $PAGE;
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $data = (object)[
            'num' => $section->section,
            'id' => $section->id,
        ];

        $data->title = $output->section_title_without_link($section, $course);

        $coursedisplay = $format->get_course_display();
        $data->headerdisplaymultipage = false;
        if ($coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $data->headerdisplaymultipage = true;
            $data->title = $output->section_title($section, $course);
            if (format_designer_has_pro() && !$section->uservisible && $section->availableinfo
                && !empty($section->sectioncardredirect)) {
                $target = '_self';
                if ($section->sectioncardtab) {
                    $target = '_blank';
                }
                $data->title = \html_writer::link($section->sectioncardredirect, get_section_name($course, $section),
                    ['target' => $target]);
            }
        }

        if ($section->section > $format->get_last_section_number()) {
            // Stealth sections (orphaned) has special title.
            $data->title = get_string('orphanedactivitiesinsectionno', '', $section->section);
        }

        if (!$section->visible) {
            $data->ishidden = true;
        }

        if ($course->id == SITEID) {
            $data->sitehome = true;
        }

        $data->editing = $format->show_editor();

        if (!$format->show_editor() && $coursedisplay == COURSE_DISPLAY_MULTIPAGE && empty($data->issinglesection)) {
            if ($section->uservisible) {
                $data->url = course_get_url($course, $section->section);
            }
        }
        $data->name = get_section_name($course, $section);
        $data->selecttext = $format->get_format_string('selectsection', $data->name);

        $bodyclasses = explode(" ", $PAGE->bodyclasses);

        $sectionreturn = $format->get_sectionnum();
        if ((!$sectionreturn || !in_array('format-designer-single-section', $bodyclasses))
            && class_exists('core_courseformat\output\local\content\bulkedittoggler')) {
            $data->sectionbulk = true;
        }

        return $data;
    }

}
