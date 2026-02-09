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
 * Contains the default activity list from a section.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat\content\section;

use moodle_url;
use stdClass;

/**
 * Base class to render a section activity list.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmlist extends \core_courseformat\output\local\content\section\cmlist {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        return $this->render_section_content($output);
    }
    /**
     * Get the render section content.
     *
     * @param object $output typically, the renderer that's calling this function
     * @param bool $htmlparse return type cm innertemplate.
     * @return string|object
     */
    public function render_section_content($output, $htmlparse = false) {
        global $USER, $OUTPUT;
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $modinfo = $format->get_modinfo();
        $user = $USER;

        $data = new stdClass();
        $data->cms = [];

        // By default, non-ajax controls are disabled but in some places like the frontpage
        // it is necessary to display them. This is a temporal solution while JS is still
        // optional for course editing.
        $showmovehere = ismoving($course->id);

        if ($showmovehere) {
            $data->hascms = true;
            $data->showmovehere = true;
            $data->strmovefull = strip_tags(get_string("movefull", "", "'$user->activitycopyname'"));
            $data->movetosectionurl = new moodle_url('/course/mod.php', ['movetosection' => $section->id, 'sesskey' => sesskey()]);
            $data->movingstr = strip_tags(get_string('activityclipboard', '', $user->activitycopyname));
            $data->cancelcopyurl = new moodle_url('/course/mod.php', ['cancelcopy' => 'true', 'sesskey' => sesskey()]);
        }

        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];
                // Check module state in deletionprogress.
                // If the old non-ajax move is necessary, we do not print the selected cm.
                if (($showmovehere && $USER->activitycopy == $mod->id)) {
                    continue;
                }
                if ($mod->is_visible_on_course_page()) {
                    $item = new $this->itemclass($format, $section, $mod, $this->displayoptions);
                    $data->cms[] = (object)[
                        'cmitem' => $item->export_for_template($output),
                        'moveurl' => new moodle_url('/course/mod.php', ['moveto' => $modnumber, 'sesskey' => sesskey()]),
                    ];
                }
            }
        }

        if (!empty($data->cms)) {
            $data->hascms = true;
        }

        $sectionlayoutclass = 'link-layout';
        $sectiontype = $this->format->get_section_option($section->id, 'sectiontype') ?: 'default';
        if ($sectiontype == 'list') {
            $sectionlayoutclass = "list-layout";
        } else if ($sectiontype == 'cards') {
            $sectionlayoutclass = 'card-layout';
        }

        if ($course->coursetype == DESIGNER_TYPE_FLOW) {
            $sectionlayoutclass = 'card-layout';
            $sectiontype = 'cards';
        }
        $templatename = 'format_designer/layout/section_layout_' . $sectiontype;
        $prolayouts = format_designer_get_pro_layouts();
        if (in_array($sectiontype, $prolayouts)) {
            if (format_designer_has_pro()) {
                $templatename = 'layouts_' . $sectiontype . '/layout/section_layout_' . $sectiontype;
            }
        }
        $templatename = $output->is_template_exists($templatename);
        $data->sectionlayoutclass = $sectionlayoutclass;
        $cmscontent = $OUTPUT->render_from_template($templatename, $data);
        if ($htmlparse) {
            return $cmscontent;
        }
        $data->cmscontent = $cmscontent;
        $data->groupmode = isset($mod->groupmode) ? $mod->groupmode : '';
        return $data;
    }
}
