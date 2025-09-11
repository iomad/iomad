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
 * Plugin lang strings
 * @package   local_course_selector
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cannotcallusgetselectedcourse'] = 'You cannot call course_selector::get_selected_course if multi-select is true';
$string['clear'] = 'Clear';
$string['courseselectorautoselectunique'] = 'If only one course matches the search, select it automatically';
$string['courseselectorpreserveselected'] = 'Keep selected courses, even if they no longer match the search';
$string['courseselectorsearchanywhere'] = 'Match the search text anywhere in the course\'s name';
$string['courseselectortoomany'] = 'course_selector got more than one selected course, even though multi-select is false';
$string['nomatchingcourses'] = 'No courses match \'{$a}\'';
$string['none'] = 'None';
$string['pleasesearchmore'] = 'Please search some more';
$string['pleaseusesearch'] = 'Please use the search';
$string['pluginname'] = 'Course selectors';
$string['previouslyselectedcourses'] = 'Previously selected courses not matching \'{$a}\'';
$string['privacy:metadata'] = 'The IOMAD local course selector plugin only shows data stored in other locations.';
$string['search'] = 'Search';
$string['searchoptions'] = 'Search options';
$string['toomanycoursesmatchsearch'] = 'Too many courses ({$a->count}) match \'{$a->search}\'';
$string['toomanycoursestoshow'] = 'Too many courses ({$a}) to show';
