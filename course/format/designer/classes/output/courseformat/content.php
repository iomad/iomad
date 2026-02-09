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
 * Contains the default content output class.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat;

use core_courseformat\output\local\content as content_base;
use course_modinfo;

/**
 * Base class to render a course content.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Topic format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = true;


    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return \stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $CFG;
        $format = $this->format;
        $course = $this->format->get_course();

        $sections = $this->export_sections($output);
        $initialsection = '';

        $data = (object) [
            'title' => $format->page_title(), // This method should be in the course_format class.
            'initialsection' => $initialsection,
            'sections' => $sections,
            'format' => $format->get_format(),
            'sectionreturn' => null,
        ];

        $singlesectionnum = $format->get_sectionnum();
        $singlesectionnumhandled = false;
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE && optional_param('section', -1, PARAM_INT) >= 0) {
            $singlesectionnumhandled = true;
        }

        // The single section format has extra navigation.
        if ($singlesectionnumhandled) {
            $singlesectionnum = empty($singlesectionnum) ? 0 : $singlesectionnum;
            if (!$PAGE->theme->usescourseindex) {
                $sectionnavigation = new $this->sectionnavigationclass($format, $singlesectionnum);
                $data->sectionnavigation = $sectionnavigation->export_for_template($output);

                $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
                $data->sectionselector = $sectionselector->export_for_template($output);
            }
            $data->hasnavigation = true;
            $data->singlesection = array_shift($data->sections);
            $data->sectionreturn = $singlesectionnum;
        }

        if ($this->hasaddsection) {
            $addsection = new $this->addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
        }

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }

        $data->course = $course;

        if ($course->coursetype == DESIGNER_TYPE_KANBAN) {
            $data->initialsection = array_shift($sections);
            $data->sections = $sections;
        }

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    protected function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();

        foreach ($this->get_sections_to_display($modinfo) as $sectionnum => $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course),
                    format_string($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $section->export_for_template($output);
                }
                continue;
            }

            $sectiondata = $section->export_for_template($output);
            $checksectionvisible = ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE);
            if (!$format->is_section_visible($thissection)) {
                if (!format_designer_has_pro() || !isset($course->displayunavailableactivities)
                    || $course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                    continue;
                }

                if (!$course->displayunavailableactivities) {
                    continue;
                }
                $sectiondata->header->title = get_section_name($course, $sectiondata->num);
            } else if (!$format->is_section_visible($thissection, $checksectionvisible)) {
                $sectiondata->header->title = get_section_name($course, $sectiondata->num);
            }
            $sections[] = $sectiondata;
        }
        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }
        return $sections;
    }

    /**
     * Return an array of sections to display.
     *
     * This method is used to differentiate between display a specific section
     * or a list of them.
     *
     * @param course_modinfo $modinfo the current course modinfo object
     * @return section_info[] an array of section_info to display
     */
    private function get_sections_to_display(course_modinfo $modinfo): array {
        global $CFG;
        $singlesection = $this->format->get_sectionnum();
        $course = $this->format->get_course();
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE && optional_param('section', -1, PARAM_INT) >= 0) {
            $singlesection = empty($singlesection) ? 0 : $singlesection;
            $sections = [
                $modinfo->get_section_info($singlesection),
            ];
            return $sections;
        }
        return $modinfo->get_listed_section_info_all();
    }

}
