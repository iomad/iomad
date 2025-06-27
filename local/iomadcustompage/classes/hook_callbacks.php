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

namespace local_iomadcustompage;

use core\hook\after_config;
use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Allows the plugin to perform action based on hook callback.
 *
 * @package    tool_policy
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Unset the $CFG->dbunmodifiedcustommenuitems and reset back the modified custom menu.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(before_standard_top_of_body_html_generation $hook): void {
        global $CFG;

        if (isset($CFG->dbunmodifiedcustommenuitems)) {
          $CFG->custommenuitems = $CFG->dbunmodifiedcustommenuitems;
          unset($CFG->dbunmodifiedcustommenuitems);
        }
    }

    public static function after_config(after_config $hook): void {
      global $CFG;
      require_once($CFG->dirroot . '/local/iomadcustompage/lib.php');

      $customcontextclasses = [
        CONTEXT_CUSTOMPAGE => 'local_iomadcustompage\\custom_context\\context_iomadcustompage',
      ];

      if (isset($CFG->custom_context_classes)) {
        $CFG->custom_context_classes = $CFG->custom_context_classes + $customcontextclasses;
      } else {
        $CFG->custom_context_classes = $customcontextclasses;
      }
    }
}
