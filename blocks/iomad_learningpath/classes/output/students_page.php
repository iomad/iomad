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
 * Students assignment management for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\output;

use renderable;
use renderer_base;
use templatable;
use moodle_url;

/**
 * Students assignment management for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class students_page implements renderable, templatable {

    /**
     * Current context
     *
     * @var object
     */
    protected $context;

    /**
     * Current learning path
     *
     * @var object
     */
    protected $path;

    /**
     * Constructor function
     *
     * @param object $context
     * @param object $path
     */
    public function __construct($context, $path) {
        $this->context = $context;
        $this->path = $path;
    }

    /**
     * Export page contents for template
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        global $companyid, $DB;

        $data = (object) [];
        $data->path = $this->path;
        $data->done = $output->single_button(
            new moodle_url('/blocks/iomad_learningpath/manage.php'),
            get_string('done', 'block_iomad_learningpath')
        );

        // Get the company profile fields.
        $companyprofilecategories = $DB->get_records_sql("SELECT uif.id,uif.name FROM {user_info_category} uic
                                                          JOIN {user_info_field} uif ON (uic.id = uif.categoryid)
                                                          WHERE uic.id NOT IN (
                                                              SELECT profileid FROM {company}
                                                              WHERE id != :companyid
                                                          )
                                                          ORDER BY uif.name DESC",
                                                          ['companyid' => $companyid]);

        $data->profilefields = [];

        // Process them.
        foreach ($companyprofilecategories as $profilecategory) {
            $entry = (object) [];
            $entry->id = $profilecategory->id;
            $entry->title = format_string($profilecategory->name);
            $data->profilefields[] = $entry;
        }

        return $data;
    }
}

