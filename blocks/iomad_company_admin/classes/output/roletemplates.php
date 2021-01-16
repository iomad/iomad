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
 * Output class for company role templates
 *
 * @package    block_iomad_company_admin
 * @copyright  2019 Howard Miller <howardsmiller@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\output;

defined('MOODLE_INTERNAL') || die;

use renderable;
use renderer_base;
use templatable;

/**
 * Class contains data for company capabilties 
 *
 * @copyright  2019 Howard Miller <howardsmiller@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roletemplates implements renderable, templatable {

    protected $templates;

    protected $linkurl;

    /**
     * @param array $templates
     * @param string $linkurl
     */
    public function __construct($templates, $linkurl) {
        array_walk($templates, function(&$template) use ($linkurl) {
            $template->editlink = new \moodle_url('/blocks/iomad_company_admin/company_capabilities.php', ['templateid' => $template->id, 'action' => 'edit']);
        });
        $this->templates = $templates;
        $this->linkurl = $linkurl;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        return [
            'templates' => array_values($this->templates),
            'istemplates' => !empty($this->templates),
            'linkurl' => $this->linkurl,
        ];
    }

}