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
 * Local IOMAD current company template selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\template_selector;

/**
 * Local IOMAD current company template selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_company extends company_base {

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        // Shared default is true.
        if (empty($options['shared'])) {
            $this->shared = true;
        } else {
            $this->shared = $options['shared'];
        }

        parent::__construct($name, $options);
    }

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/template_selector/current_company.php';

        return $options;
    }

    /**
     * Find company templates
     *
     * @param string $search
     * @return array
     */
    public function find_templates($search) {
        global $DB;

        // By default wherecondition retrieves all templates except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'ct');
        $params['companyid'] = $this->companyid;
        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('ct');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {competency_template} ct
                JOIN {local_iomad_company_comp_templates} cct ON (
                    ct.id = cct.templateid
                    AND cct.companyid = :companyid
                )
                WHERE $wherecondition";

        $order = ' ORDER BY ct.shortname ASC';

        // Are we getting back too many results?
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_templates')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the available templates.
        $availabletemplates = $DB->get_records_sql($fields . $sql . $order, $params);

        // Set up the search group text.
        if ($search) {
            $groupname = get_string('currcompanytemplatesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('currcompanytemplates', 'block_iomad_company_admin');
        }

        return [$groupname => $availabletemplates];
    }
}
