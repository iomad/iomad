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
 * Local IOMAD potential company framework selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\framework_selector;

/**
 * Local IOMAD potential company framework selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_company extends company_base {

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/framework_selector/potential_company.php';
        return $options;
    }

    /**
     * Search for potential company frameworks
     * @param atring $search
     * @return array
     */
    public function find_frameworks($search) {
        global $DB;

        // By default wherecondition retrieves all frameworks except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'cf');
        $params['companyid'] = $this->companyid;

        // Deal with shared frameworks.  Cannot be added to a company in this manner.
        $sharedsql = " AND cf.id NOT IN (
                           SELECT frameworkid
                           FROM {local_iomad_frameworks}
                           WHERE shared <> 1
                       )
                       AND cf.id NOT IN (
                           SELECT frameworkid
                           FROM {local_iomad_company_comp_frameworks}
                           WHERE companyid = :companyid
                       )";

        $fields = 'SELECT ' . $this->required_fields_sql('cf');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields = 'SELECT DISTINCT cf.id,' . $this->required_fields_sql('cf');
        $distinctcountfields = 'SELECT COUNT(DISTINCT cf.id) ';

        $sqldistinct = " FROM {competency_framework} cf
                        WHERE $wherecondition
                        $sharedsql";

        $sql = " FROM {competency_framework} cf
                 WHERE $wherecondition
                 $sharedsql";

        $order = ' ORDER BY cf.shortname ASC';

        // Check if we got too many results.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_frameworks')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get all of the frameworks.
        $allframeworks = $DB->get_records_sql($fields . $sql . $order, $params) +
                         $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);

        // Deduplicate this.
        $availableframeworks = [];
        foreach ($allframeworks as $framework) {
            $availableframeworks[$framework->id] = $framework;
        }

        // Deal with any search groupings.
        if ($search) {
            $groupname = get_string('potframeworksmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potframeworks', 'block_iomad_company_admin');
        }

        return [$groupname => $availableframeworks];
    }
}
