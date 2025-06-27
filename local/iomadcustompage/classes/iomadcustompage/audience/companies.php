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
use company;
use iomad;

/**
 * The backend class for IOMAD company audience type
 *
 * @package     local_iomadcustompage
 * @copyright   2025 e-Learn Design ltd https://www.e-learndesign.co.uk
 * @author      Derick Turner
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class companies extends base {
  /**
   * Adds audience's elements to the given mform
   *
   * @param MoodleQuickForm $mform The form to add elements to
   * @throws coding_exception
   * @throws dml_exception
   */
    public function get_config_form(MoodleQuickForm $mform): void {
        
        $companies = $companylist = company::get_companies_select(false);

        $mform->addElement('autocomplete', 'companies', get_string('selectacompany', 'block_iomad_company_admin'), $companies, ['multiple' => true]);
        $mform->addRule('companies', null, 'required', null, 'client');
    }

  /**
   * Helps to build SQL to retrieve users that matches the current audience
   *
   * @param string $usertablealias
   * @return array array of three elements [$join, $where, $params]
   * @throws coding_exception
   * @throws dml_exception
   */
    public function get_sql(string $usertablealias): array {
        global $DB;

        $companies = $this->get_configdata()['companies'];
        $prefix = database::generate_param_name() . '_';
        [$insql, $inparams] = $DB->get_in_or_equal($companies, SQL_PARAMS_NAMED, $prefix);
        [$companyusers] = database::generate_aliases(1);

        $join = "
            JOIN {company_users} {$companyusers} ON {$companyusers}.userid = {$usertablealias}.id";

        $where = "{$companyusers}.companyid {$insql}";

        return [$join, $where, $inparams];
    }

  /**
   * Return user friendly name of this audience type
   *
   * @return string
   * @throws coding_exception
   */
    public function get_name(): string {
        return get_string('hascompany', 'block_iomad_company_admin');
    }

  /**
   * Return the description for the audience.
   *
   * @return string
   * @throws dml_exception
   */
    public function get_description(): string {
        global $DB;
        $companyids = $this->get_configdata()['companies'];
        $companies = $DB->get_records_list('company', 'id', $companyids, 'name', 'name');
        $companies = array_column($companies, 'name');
        return $this->format_description_for_multiselect(array_values($companies));
    }

  /**
   * If the current user is able to add this audience.
   *
   * @return bool
   * @throws dml_exception
   */
    public function user_can_add(): bool {
        // Check if user is able to see any companies.
        $companies = company::get_companies_select(false);
        if (empty($companies)) {
            return false;
        }
        if (!empty($this->get_configdata()['companies'])) {
            return false;
        }

        return true;
    }

  /**
   * If the current user is able to edit this audience.
   *
   * @return bool
   * @throws coding_exception
   * @throws dml_exception
   */
    public function user_can_edit(): bool {
        global $DB, $USER;

        // Check if user can assign all saved role types on this audience instance.
        $companyids = $this->get_configdata()['companies'];
        if (!iomad::has_capability('block/iomad_company_admin:company_view_all', context_system::instance())) {
            if (!$DB->get_records_sql("SELECT id
                                       FROM {company_users}
                                       WHERE userid = :userid
                                       AND companyid IN (" . implode(',', array_keys($companyids)) . ")",
                                      ['userid' => $USER->id])) {
                return false;
            }
        }

        return true;
    }
}
