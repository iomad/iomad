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
 * Local IOMAD current company framework selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\framework_selector;

/**
 * Local IOMAD current company framework selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_company extends company_base {

    /**
     * Get the selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/framework_selector/current_company.php';
        return $options;
    }

    /**
     * Find company frameworks
     *
     * @param string $search
     * @return array
     */
    public function find_frameworks($search) {
        global $DB;

        // By default wherecondition retrieves all frameworks except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'cf');
        $params['companyid'] = $this->companyid;
        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('cf');
        $countfields = 'SELECT COUNT(1)';

        // Deal with shared frameworks.
        if ($this->shared) {
            $sharedsql = " FROM {competency_framework} cf
                           JOIN {local_iomad_frameworks} if ON cf.id=if.frameworkid
                           WHERE if.shared = 1";
        } else {
            $sharedsql = " FROM {competency_framework} cf WHERE 1 = 2";
        }
        $sql = " FROM {competency_framework} cf
                JOIN {local_iomad_company_comp_frameworks} ccf ON (
                    cf.id = ccf.frameworkid
                    AND ccf.companyid = :companyid
                )
                WHERE $wherecondition";

        $order = ' ORDER BY cf.shortname ASC';

        // Do we get too many results?
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_frameworks')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of frameworks.
        $availableframeworks = $DB->get_records_sql($fields . $sql . $order, $params) +
                               $DB->get_records_sql($fields . $sharedsql . $order, $params);

        // Set up the return array.
        if ($search) {
            $groupname = get_string('currcompanyframeworksmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('currcompanyframeworks', 'block_iomad_company_admin');
        }

        return [$groupname => $availableframeworks];
    }
}
