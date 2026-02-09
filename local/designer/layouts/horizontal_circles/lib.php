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
 * Layout horizontal circles component lib file.
 *
 * @package   layouts_horizontal_circles
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Layout options for sections.
 *
 * @return array
 */
function layouts_horizontal_circles_sectionoptions() {
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
 * layout data for sections.
 *
 * @param array $data
 * @param cm_info $mod
 * @param stdclass $section
 * @return void
 */
function layouts_horizontal_circles_section_data(&$data, $mod, $section) {
    if ($section->sectiontype == 'horizontal_circles') {
        $size = isset($section->circlesize) ? $section->circlesize : 'small';
        $data['sectionstyle'] .= ' circle-size-'.$size;
    }
}

/**
 * Process Data for activity.
 *
 * @param array $data
 * @param cm_info $mod
 * @param stdclass $section
 * @return void
 */
function layouts_horizontal_circles_activity_data(&$data, $mod, $section) {
    if ($section->sectiontype == 'horizontal_circles') {
        $size = isset($section->circlesize) ? $section->circlesize : 'small';
        $data['modclasses'] .= ' circle-size-'.$size;
    }
}

/**
 * Layout menu for circle.
 *
 * @param course_format $format
 * @param stdclass $section
 * @param stdclass $course
 * @return array
 */
function layouts_horizontal_circles_menu($format, $section, $course) {
    return [
        'type' => 'horizontal_circles',
        'name' => get_string('menu', 'layouts_horizontal_circles'),
        'active' => $format->get_section_option($section->id, 'sectiontype') == 'horizontal_circles',
        'url' => new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $section->section),
    ];
}
