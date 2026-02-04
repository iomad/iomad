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
 * The block_iomad_company_admin dashboard page viewed event.
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\event;

use core\event\base;
use coding_exception;
use context_system;
use moodle_url;
use local_iomad\custom_context\context_company;

/**
 * The block_iomad_company_admin dashboard page viewed event.
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_page_viewed extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'company';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('companydashboardpageviewed', 'block_iomad_company_admin');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the IOMAD dashboard page url " .
        self::get_url()->out_omit_querystring() .
        " within company id " .
        $this->objectid;
    }

    /**
     * Get URL related to the action.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url($this->other['pageurl']);
    }

    /**
     * Custom validation.
     *
     * @throws coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['pageurl'])) {
            throw new coding_exception('The \'pageurl\' value must be set in other.');
        }
    }

    /**
     * Define other table mappings being used.
     *
     * @return array
     */
    public static function get_other_mapping() {
        $othermapped = [];

        return $othermapped;
    }

    public static function create_from_url($url) {
        global $USER, $companyid;

        // Set the appropriate context.
        if ($companyid > 0) {
            $context = context_company::instance($companyid);
        } else {
            $context = context_system::instance();
        }

        // Companyid cannot be empty.
        if (is_null($companyid)) {
            $companyid = 0;
        }

        // Set the payload.
        $data = [
            'userid' => $USER->id,
            'companyid' => $companyid,
            'context' => $context,
            'courseid' => 0,
            'objectid' => $companyid,
            'other' => [
                'pageurl' => $url,
            ],
        ];

        // Create dashboard_page_viewed event.
        $event = self::create($data);
        return $event;
    }
}
