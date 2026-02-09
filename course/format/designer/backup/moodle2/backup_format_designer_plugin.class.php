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
 * Specialised backup for Designer course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Format designer features backup.
 */
class backup_format_designer_plugin extends backup_format_plugin {

    /**
     * Define module plugin structure.
     */
    public function define_module_plugin_structure() {

        $designer = new backup_nested_element('format_designer_options', ['id'], [
            'courseid', 'cmid', 'name', 'value', 'timecreated', 'timemodified',
        ]);

        $designer->set_source_table('format_designer_options',
            ['cmid' => backup::VAR_MODID, 'courseid' => backup::VAR_COURSEID]);

        $files = format_designer\options::get_file_areas('module');
        if ($files) {
            foreach ($files as $file => $component) {
                $designer->annotate_files($component, $file, null);
            }
        }
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'designer');

        return $plugin->add_child($designer);
    }

    /**
     * Define the sections features to backup.
     */
    public function define_section_plugin_structure() {
        $formatoptions = new backup_nested_element('designer', ['id'], ['backgroundimage']);

        // Define sources.
        $formatoptions->set_source_table('course_sections', ['id' => backup::VAR_SECTIONID]);

        $files = format_designer\options::get_file_areas('section');
        if ($files) {
            foreach ($files as $file => $component) {
                $formatoptions->annotate_files($component, $file, null);
            }
        }
        $formatoptions->annotate_files('local_designer', 'courseheaderbgimage', null);
        $formatoptions->annotate_files('local_designer', 'coursebgimage', null);
        $formatoptions->annotate_files('local_designer', 'additionalcontent', null);
        $formatoptions->annotate_files('local_designer', 'prerequisiteinfo', null);

        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'designer');

        return $plugin->add_child($formatoptions);
    }
}
