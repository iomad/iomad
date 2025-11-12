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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\framework_selector;

class current_company extends company_base {
    /**
     * Company frameworks
     * @param <type> $search
     * @return array
     */
    protected $shared;
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];

        // Default for shared is true.
        if (isset($options['shared'])) {
            $this->shared = $options['shared'];
        } else {
            $this->shared = true;
        }
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'local/iomad/classes/framework_selector/current_company.php';
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_frameworks($search) {
        global $CFG, $DB;
        // By default wherecondition retrieves all frameworks except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'cf');
        $params['companyid'] = $this->companyid;
        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('cf');
        $countfields = 'SELECT COUNT(1)';


        // Deal with shared frameworks.
        if ($this->shared) {
            $sharedsql = " FROM {competency_framework} cf
                           INNER JOIN {iomad_frameworks} if
                           ON cf.id=if.frameworkid
                           WHERE if.shared = 1";
        } else {
            $sharedsql = " FROM {competency_framework} cf WHERE 1 = 2";
        }
        $sql = " FROM {competency_framework} cf
                INNER JOIN {company_comp_frameworks} ccf ON (cf.id = ccf.frameworkid AND ccf.companyid = :companyid)
                WHERE $wherecondition";

        $order = ' ORDER BY cf.shortname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_frameworks) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableframeworks = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($fields . $sharedsql . $order, $params);

        if (empty($availableframeworks)) {
            return array();
        }

        // Set up empty return.
        $frameworkarray = array();
        if (!empty($availableframeworks)) {
            if ($search) {
                $groupname = get_string('currcompanyframeworksmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('currcompanyframeworks', 'block_iomad_company_admin');
            }
            $frameworkarray[$groupname] = $availableframeworks;
        }

        return $frameworkarray;
    }
}

