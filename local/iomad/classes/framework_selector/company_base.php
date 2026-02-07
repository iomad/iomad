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
 * Local IOMAD company framework selector base class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\framework_selector;

/**
 * Local IOMAD company framework selector base class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class company_base extends base {

    /** @var int company id  */
    protected $companyid;

    /** @var bool show shared  */
    protected $shared;

    /** @var bool show closed shared  */
    protected $partialshared;

    /** @var array list of required fields */
    protected $requiredfields = ['id', 'shortname'];

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->shared = $options['shared'];
        $this->partialshared = $options['partialshared'];

        parent::__construct($name, $options);
    }

    /**
     * Get the selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['shared'] = $this->shared;
        $options['file'] = 'local/iomad/classes/framework_selector/company_base.php';
        return $options;
    }
}
