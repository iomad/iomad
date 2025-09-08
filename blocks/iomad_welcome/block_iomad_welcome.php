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
 * Welcome block for IOMAD.
 *
 * @package   block_iomad_welcome
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Default block class.
 */
class block_iomad_welcome extends block_base {
    /**
     * Initialisation.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_iomad_welcome');
    }

    /**
     * function to check if we hide the header.
     *
     * @return void
     */
    public function hide_header() {
        return false;
    }

    /**
     * Where do we show this block?
     *
     * @return void
     */
    public function applicable_formats() {
        return ['my' => true];
    }

    /**
     * Get the block content.
     *
     * @return void
     */
    public function get_content() {
        global $USER, $CFG, $DB, $OUTPUT;

        // Empty by default.
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = '';

        $systemcontext = context_system::instance();
        $companycontext = $systemcontext;
        if (!empty($company)) {
            $companycontext = \core\context\company::instance($company);
        }

        // Only display if you have the correct capability.
        if (!iomad::has_capability('block/iomad_welcome:view', $companycontext)) {
            return;
        }

        // Only display until companies have been created.
        if ($DB->record_exists('company', [])) {
            return;
        }

        $message = get_string('message', 'block_iomad_welcome');
        $dashboardlink = new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php');
        $dashboardtext = get_string('dashboardtext', 'block_iomad_welcome');
        $this->content->text = '<p><center>' . $message . '</center></p>';
        $this->content->text .= '<p><center><a href="' . $dashboardlink . '">' . $dashboardtext . '</a></center></p>';
        $this->content->footer = '';

        return $this->content;
    }
}
