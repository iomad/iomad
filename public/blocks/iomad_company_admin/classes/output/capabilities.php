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
 * Output class for company capabilities
 *
 * @package    block_iomad_company_admin
 * @copyright  2019 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Howard Miller <howardsmiller@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\output;

use renderable;
use renderer_base;
use templatable;
use local_iomad\iomad;

/**
 * Output class for company capabilities
 *
 * @package    block_iomad_company_admin
 * @copyright  2019 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Howard Miller <howardsmiller@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capabilities implements renderable, templatable {

    /** @var array list of capabilities */
    protected $capabilities;

    /** @var int role ID */
    protected $roleid;

    /** @var int company ID */
    protected $companyid;

    /** @var int template ID */
    protected $templateid;

    /** @var string link URL */
    protected $linkurl;

    /**
     * Constructor function
     *
     * @param array $capabilities
     * @param int $roleid
     * @param int $companyid
     * @param int $templateid
     * @param string $linkurl
     */
    public function __construct($capabilities, $roleid, $companyid, $templateid, $linkurl) {
        array_walk($capabilities, function(&$capability) use ($linkurl) {
            $capability->name = get_capability_string($capability->capability);
            $capability->doclink = iomad::documentation_link() . $capability->capability;
            $capability->checked = !$capability->iomad_restriction;
        });
        $this->capabilities = $capabilities;
        $this->roleid = $roleid;
        $this->companyid = $companyid;
        $this->templateid = $templateid;
        $this->linkurl = $linkurl;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        // Role info.
        $role = $DB->get_record('role', ['id' => $this->roleid], '*', MUST_EXIST);

        return [
            'role' => $role,
            'capabilities' => array_values($this->capabilities),
            'companyid' => $this->companyid,
            'linkurl' => $this->linkurl,
        ];
    }
}
