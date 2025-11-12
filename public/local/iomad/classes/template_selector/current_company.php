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

namespace local_iomad\template_selector;

class current_company extends company_base {
    /**
     * Company templates
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
        $options['file']    = 'local/iomad/classes/template_selector/current_company.php';
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_templates($search) {
        global $CFG, $DB;
        // By default wherecondition retrieves all templates except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'ct');
        $params['companyid'] = $this->companyid;
        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('ct');
        $countfields = 'SELECT COUNT(1)';


        // Deal with shared templates.
        if ($this->shared) {
            $sharedsql = " FROM {competency_template} ct
                           INNER JOIN {iomad_templates} it
                           ON ct.id=it.templateid
                           WHERE it.shared = 1";
        } else {
            $sharedsql = " FROM {competency_template} ct WHERE 1 = 2";
        }

        $sql = " FROM {competency_template} ct
                INNER JOIN {company_comp_templates} cct ON (ct.id = cct.templateid AND cct.companyid = :companyid)
                WHERE $wherecondition";

        $order = ' ORDER BY ct.shortname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params);
            if ($potentialmemberscount >  $CFG->iomad_max_select_templates) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availabletemplates = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($fields . $sharedsql . $order, $params);

        if (empty($availabletemplates)) {
            return array();
        }

        // Set up empty return.
        $templatearray = array();
        if (!empty($availabletemplates)) {
            if ($search) {
                $groupname = get_string('currcompanytemplatesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('currcompanytemplates', 'block_iomad_company_admin');
            }
            $templatearray[$groupname] = $availabletemplates;
        }

        return $templatearray;
    }
}

