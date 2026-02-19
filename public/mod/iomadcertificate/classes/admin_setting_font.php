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
class mod_iomadcertificate_admin_setting_font extends admin_setting_configselect {
    /**
     * Constructor
     * @param string $name unique ascii name, either 'mysetting' for settings that
     * in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string|int $defaultsetting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, null);
    }
    /**
     * Lazy load the font options.
     *
     * @return bool true if loaded, false if error
     */
    public function load_choices() {
        global $CFG;

        if (is_array($this->choices)) {
            return true;
        }

        require_once("$CFG->libdir/pdflib.php");

        $doc = new pdf();

        if (method_exists($doc, 'get_font_families')) {
            $this->choices = [];
            $fontfamilies = $doc->get_font_families();
            foreach ($fontfamilies as $family => $fonts) {
                $this->choices[$family] = $family;
            }

        } else {
            $this->choices = [
                'freeserif' => 'freeserif',
                'freesans' => 'freesans',
            ];
        }

        return true;
    }
}
