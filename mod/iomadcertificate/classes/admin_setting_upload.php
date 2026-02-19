<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * IOMAD certificate activity
 *
 * @package   mod_iomadcertificate
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This plugin is based on code originally created as mod_certificate by Mark Nelson <markn@moodle.com>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/adminlib.php');

/**
 * IOMAD certificate activity
 *
 * @package   mod_iomadcertificate
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_iomadcertificate_admin_setting_upload extends admin_setting_configtext {

    /**
     * Constructor function
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param ? $defaultsetting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_RAW, 50);
    }

    /**
     * Display the html
     *
     * @param array $data
     * @param string $query
     * @return void
     */
    public function output_html($data, $query='') {
        // Create a dummy var for this field.
        $this->config_write($this->name, '');

        return format_admin_setting($this, $this->visiblename,
            html_writer::link(new moodle_url('/mod/iomadcertificate/upload_image.php'), get_string('upload')),
            $this->description, true, '', null, $query);
    }
}
