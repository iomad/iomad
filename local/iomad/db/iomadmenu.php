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
 * Local IOMAD IOMAD dashboard menu entries
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Set up the IOMAD dashboard menu items for this plugin.
 *
 * @return array
 */
function local_iomad_menu() {

    return [
        'iomad_track' => [
            'category' => 'CourseAdmin',
            'tab' => 3,
            'name' => get_string('importcompletionrecords', 'local_iomad'),
            'url' => '/local/iomad/import_track.php',
            'cap' => 'local/iomad:importtrackfrommoodle',
            'icondefault' => 'report',
            'style' => 'report',
            'icon' => 'fa-bar-chart-o',
            'iconsmall' => 'fa-upload',
        ],

        'EmailTemplates' => [
            'category' => 'CompanyAdmin',
            'tab' => 1,
            'name' => get_string('emailtemplates', 'local_iomad'),
            'url' => '/local/iomad/template_list.php',
            'cap' => 'local/iomad:email_list',
            'icondefault' => 'emailtemplates',
            'style' => 'company',
            'icon' => 'fa-inbox',
            'iconsmall' => 'fa-gear',
        ],
    ];
}
