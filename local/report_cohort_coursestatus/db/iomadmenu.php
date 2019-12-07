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

defined('MOODLE_INTERNAL') || die();

// Define the Iomad menu items that are defined by this plugin

function local_report_cohort_coursestatus_menu() {

        return [
            'cohort_coursestatus' => [
                'category' => 'Reports',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_cohort_coursestatus'),
                'url' => '/local/report_cohort_coursestatus/index.php',
                'cap' => 'local/report_cohort_coursestatus:view',
                'icondefault' => 'report',
                'style' => 'report',
                'icon' => 'fa-check-square-o',
                'iconsmall' => 'fa-bar-chart-o',
            ],
        ];
}