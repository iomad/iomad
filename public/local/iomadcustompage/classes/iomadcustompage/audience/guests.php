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
use core_reportbuilder\local\helpers\database;
use dml_exception;
use local_iomadcustompage\local\audiences\base;
use MoodleQuickForm;

/**
 * Administrators audience type
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guests extends base {
  /**
   * Add audience elements to the current form
   *
   * @param MoodleQuickForm $mform
   * @throws coding_exception
   */
    public function get_config_form(MoodleQuickForm $mform): void {
        $mform->addElement('static', 'guest', get_string('guest', 'moodle'));
    }

  /**
   * Return SQL to retrieve users that match this audience
   *
   * @param string $usertablealias
   * @return array [$join, $select, $params]
   * @throws dml_exception
   * @throws coding_exception
   */
    public function get_sql(string $usertablealias): array {
        global $CFG, $DB;

        $siteguest = array_map('intval', explode(',', $CFG->siteguest));
        [$select, $params] = $DB->get_in_or_equal($siteguest, SQL_PARAMS_NAMED, database::generate_param_name() . '_');

        return ['', "{$usertablealias}.id {$select}", $params];
    }

  /**
   * Return name of this audience
   *
   * @return string
   * @throws coding_exception
   */
    public function get_name(): string {
        return get_string('nonauthenticatedusers', 'local_iomadcustompage');
    }

    /**
     * Return description of this audience.
     *
     * @return string
     */
    public function get_description(): string {
        $guestuser = guest_user();
        $guest = fullname($guestuser);

        return $this->format_description_for_multiselect([$guest]);
    }

    /**
     * Whether the current user is able to edit this audience
     *
     * @return bool
     */
    public function user_can_edit(): bool {
        return $this->user_can_add();
    }

    /**
     * Whether the current user is able to add this audience
     *
     * @return bool
     */
    public function user_can_add(): bool {
        return is_siteadmin();
    }
    /**
     * if the guest access audience available
     * @return bool
     */
    public function is_available(): bool {
        global $CFG;
        return (bool)$CFG->guestloginbutton;
    }
}
