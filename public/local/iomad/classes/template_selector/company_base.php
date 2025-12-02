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

/**
 * base class for selecting templates of a company
 */
abstract class company_base extends base {

    protected $companyid;
    protected $shared;
    protected $partialshared = false;

    //overridden to include the sortorder field
    protected $requiredfields = array('id', 'shortname');

    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->shared  = $options['shared'];

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['shared'] = $this->shared;
        $options['file']    = 'local/iomad/classes/template_selector/company_base.php';

        return $options;
    }
}
