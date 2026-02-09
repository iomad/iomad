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
 * Layout component lib file.
 *
 * @package   layouts_circles
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * circles section options to load in section form.
  *
  * @return array
  */
function layouts_circles_sectionoptions() {
    return [
        'layout' => [
            'circlesize' => [
                'type' => PARAM_TEXT,
                'element_type' => 'select',
                'label' => get_string('circlesize', 'format_designer'),
                'element_attributes' => [
                    [
                        'small' => get_string('small' , 'format_designer'),
                        'medium' => get_string('medium', 'format_designer'),
                        'large' => get_string('large', 'format_designer'),
                    ],
                ],
            ],
        ],
    ];
}

/**
 * Data to add to section content for circles layout.
 *
 * @param array $data
 * @param cm_info $mod
 * @param stdclass $section
 * @return void
 */
function layouts_circles_section_data(&$data, $mod, $section) {
    if ($section->sectiontype == 'circles') {
        $size = isset($section->circlesize) ? $section->circlesize : 'small';
        $data['sectionstyle'] .= ' circle-size-'.$size;
    }
}

/**
 * Circles layout activity data.
 *
 * @param stdclass $data
 * @param cm_info $mod
 * @param stdclass $section
 * @return void
 */
function layouts_circles_activity_data(&$data, $mod, $section) {
    if ($section->sectiontype == 'circles') {
        $size = isset($section->circlesize) ? $section->circlesize : 'small';
        $data['modclasses'] .= ' circle-size-'.$size;
    }
}

/**
 * Add the circle component to layout switcher menu.
 *
 * @param course_format $format
 * @param stdclass $section
 * @param stdclass $course
 * @return array Menu content.
 */
function layouts_circles_menu($format, $section, $course) {
    return [
        'type' => 'circles',
        'name' => get_string('verticalcircles', 'format_designer'),
        'active' => $format->get_section_option($section->id, 'sectiontype') == 'circles',
        'url' => new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $section->section),
    ];
}
