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

class potential_company extends company_base {

        public function __construct($name, $options) {
        // Shared default is false.
        if (empty($options['shared'])) {
            $this->shared = false;
        } else {
            $this->shared = $options['shared'];
        }

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/template_selector/potential_company.php';

        return $options;
    }

    /**
     * Potential company manager templates
     * @param <type> $search
     * @return array
     */
    public function find_templates($search) {
        global $CFG, $DB, $SITE;
        // By default wherecondition retrieves all templates except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'ct');
        $params['companyid'] = $this->companyid;

        // Deal with shared templates.  Cannot be added to a company in this manner.
        $sharedsql = " AND ct.id NOT IN (SELECT cct.templateid FROM {company_comp_templates} cct
                                         LEFT JOIN {iomad_templates} it
                                         ON (cct.templateid = it.templateid)
                                         WHERE it.shared=1 ) ";

        $fields      = 'SELECT ' . $this->required_fields_sql('ct');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ct.id,' . $this->required_fields_sql('ct');
        $distinctcountfields = 'SELECT COUNT(DISTINCT ct.id) ';

        $sqldistinct = " FROM {competency_template} ct
                        WHERE $wherecondition
                        $sharedsql";

        $sql = " FROM {competency_template} ct
                 WHERE $wherecondition
                 $sharedsql";

        $order = ' ORDER BY ct.shortname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount >  get_config('local_iomad', 'max_select_templates')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $alltemplates = $DB->get_records_sql($fields . $sql . $order, $params) +
        $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);

        // Only show one list of templates
        $availabletemplates = array();
        foreach ($alltemplates as $template) {
            $availabletemplates[$template->id] = $template;
        }

        if (empty($availabletemplates)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('pottemplatesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('pottemplates', 'block_iomad_company_admin');
        }

        return array($groupname => $availabletemplates);
    }
}
