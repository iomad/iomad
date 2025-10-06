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

declare(strict_types=1);

namespace local_iomadcustompage\iomadcustompage\audience;

use coding_exception;
use context_system;
use core_reportbuilder\local\helpers\database;
use dml_exception;
use local_iomadcustompage\local\audiences\base;
use MoodleQuickForm;

/**
 * The backend class for All users audience type
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allusers extends base {
  /**
   * Adds audience's elements to the given mform
   *
   * @param MoodleQuickForm $mform The form to add elements to
   * @throws coding_exception
   */
    public function get_config_form(MoodleQuickForm $mform): void {
        $mform->addElement('static', 'allsiteusers', get_string('allsiteusers', 'core_reportbuilder'));
    }

    /**
     * Helps to build SQL to retrieve users that matches the current page audience
     *
     * @param string $usertablealias
     * @return array array of three elements [$join, $where, $params]
     */
    public function get_sql(string $usertablealias): array {
        global $CFG;

        $guestuser = database::generate_param_name();
        return ['', "$usertablealias.suspended = 0 AND $usertablealias.deleted = 0 AND $usertablealias.id <> :{$guestuser}",
            [$guestuser => $CFG->siteguest]];
    }

  /**
   * Return user friendly name of this audience type
   *
   * @return string
   * @throws coding_exception
   */
    public function get_name(): string {
        return get_string('allusers', 'core_reportbuilder');
    }

  /**
   * Return the description for the audience.
   *
   * @return string
   * @throws coding_exception
   */
    public function get_description(): string {
        return get_string('allsiteusers', 'core_reportbuilder');
    }

  /**
   * If the current user is able to add this audience.
   *
   * @return bool
   * @throws dml_exception
   * @throws coding_exception
   */
    public function user_can_add(): bool {
        return has_capability('moodle/user:viewalldetails', context_system::instance());
    }

  /**
   * If the current user is able to edit this audience.
   *
   * @return bool
   * @throws coding_exception
   * @throws dml_exception
   */
    public function user_can_edit(): bool {
        return has_capability('moodle/user:viewalldetails', context_system::instance());
    }
}
