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
 * Specialised restore for Designer course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised restore for Designer course format.
 */
class restore_format_designer_plugin extends restore_format_plugin {

    /** @var int */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 3.3 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release; // The major version: 2.9, 3.0, 3.10...
        return version_compare($backuprelease, '3.3', '<');
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore.
     *
     * @return restore_path_element[]
     */
    public function define_course_plugin_structure() {
        global $DB;

        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        $target = $this->step->get_task()->get_target();
        if (($target == backup::TARGET_CURRENT_ADDING || $target == backup::TARGET_EXISTING_ADDING) &&
                $this->need_restore_numsections()) {
            $maxsection = $DB->get_field_sql(
                'SELECT max(section) FROM {course_sections} WHERE course = ?',
                [$this->step->get_task()->get_courseid()]);
            $this->originalnumsections = (int)$maxsection;
        }

        // Dummy path element is needed in order for after_restore_course() to be called.
        return [new restore_path_element('dummy_course', $this->get_pathfor('/dummycourse'))];
    }

    /**
     * Module stucture path.
     */
    public function define_module_plugin_structure() {
        $paths[] = new restore_path_element('format_designer_options', '/module/format_designer_options');
        return $paths;
    }

    /**
     * Section structure path.
     */
    public function define_section_plugin_structure() {
        $paths[] = new restore_path_element('dummy_section', $this->get_pathfor('/dummysection'));
        return $paths;
    }

    /**
     * Format designer options process.
     * @param array $data
     */
    public function process_format_designer_options($data) {
        global $DB, $CFG;

        $data = (object) $data;
        $data->courseid = $this->get_mappingid('course', $data->courseid);
        $data->cmid = $this->get_mappingid('course_module', $data->cmid);

        $DB->insert_record('format_designer_options', $data);
    }

    /**
     * Dummy process method.
     *
     * @return void
     */
    public function process_dummy_course() {

    }

    /**
     * Dummy process method.
     *
     * @return void
     */
    public function process_dummy_section() {

    }

    /**
     * Executed after course restore is complete.
     *
     * This method is only executed if course configuration was overridden.
     *
     * @return void
     */
    public function after_restore_course() {
        global $DB;

        if (!$this->need_restore_numsections()) {
            // Backup file was made in Moodle 3.3 or later, we don't need to process 'numsecitons'.
            return;
        }

        $data = $this->connectionpoint->get_data();
        $backupinfo = $this->step->get_task()->get_info();
        if ($backupinfo->original_course_format !== 'designer' || !isset($data['tags']['numsections'])) {
            // Backup from another course format or backup file does not even have 'numsections'.
            return;
        }

        $numsections = (int)$data['tags']['numsections'];
        foreach ($backupinfo->sections as $key => $section) {
            // For each section from the backup file check if it was restored and if was "orphaned" in the original
            // course and mark it as hidden. This will leave all activities in it visible and available just as it was
            // in the original course.
            // Exception is when we restore with merging and the course already had a section with this section number,
            // in this case we don't modify the visibility.
            if ($this->step->get_task()->get_setting_value($key . '_included')) {
                $sectionnum = (int)$section->title;
                if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                    $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                        [$this->step->get_task()->get_courseid(), $sectionnum]);
                }
            }
        }
    }

    /**
     * Update the files of editors after restore execution.
     *
     * @return void
     */
    protected function after_restore_module() {
        if (!PHPUNIT_TEST) {
            $files = \format_designer\options::get_file_areas();
            foreach ($files as $filearea => $component) {
                $this->add_related_files($component, $filearea, null);
            }
        }
    }

    /**
     * After section restore add the section related files.
     */
    protected function after_restore_section() {
        if (!PHPUNIT_TEST) {
            $files = \format_designer\options::get_file_areas('section');
            foreach ($files as $file => $component) {
                $this->add_related_files($component, $file, 'course_section');
            }

            // Restore the courseheaderbgimage.
            $this->add_related_files('local_designer', 'courseheaderbgimage', null, null,
                $this->step->get_task()->get_old_courseid());
            // Restore the coursebgimage.
            $this->add_related_files('local_designer', 'coursebgimage', null, null, $this->step->get_task()->get_old_courseid());
            // Restore the additionalcontent.
            $this->add_related_files('local_designer', 'additionalcontent', null, null,
                $this->step->get_task()->get_old_courseid());
            // Restore the prerequisiteinfo.
            $this->add_related_files('local_designer', 'prerequisiteinfo', null, null, $this->step->get_task()->get_old_courseid());
        }
    }
}
