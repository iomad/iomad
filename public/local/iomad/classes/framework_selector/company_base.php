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

<<<<<<<< HEAD:public/local/framework_selector/version.php
$plugin->release  = '5.0.2 (Build: 20250811)';    // Human-friendly version name.
$plugin->version  = 2025041450;   // The (date) version of this plugin.
$plugin->requires = 2025041400;   // Requires this Moodle version.
$plugin->component  = 'local_framework_selector';
$plugin->dependencies = ['local_iomad' => 2025041400];
$plugin->supported = [500, 500];
$plugin->maturity = MATURITY_STABLE;
========
/**
 * base class for selecting frameworks of a company
 */
abstract class company_base extends base {

    protected $companyid;

    //overridden to include the sortorder field
    protected $requiredfields = array('id', 'shortname');

    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'local/iomad/classes/framework_selector/company_base.php';
        return $options;
    }
}
>>>>>>>> 482b0bc3d37 (IOMAD: Migrate local selector plugins to local_iomad autoload classes - local_framework_selecter - #2524):local/iomad/classes/framework_selector/company_base.php
