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
 * Event observer for local iomad plugin.
 *
 * @package    block_iomad_learningpath
 * @copyright  2016 E-Learn Design Ltd. (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath;

use block_iomad_learningpath\companypaths;

/**
 * Event observer for local iomad plugin.
 *
 * @package    block_iomad_learningpath
 * @copyright  2016 E-Learn Design Ltd. (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_learningpath_observer {

    /**
     * Triggered via block_iomad_company_admin::company_license_deleted event.
     *
     * @param \block_iomad_company_admin\event\company_license_deleted $event
     * @return bool true on success.
     */
    public static function company_license_deleted($event) {
        companypaths::company_license_deleted($event);
        return true;
    }

    /**
     * Triggered via block_iomad_company_admin::company_license_updated event.
     *
     * @param \block_iomad_company_admin\event\company_license_updated $event
     * @return bool true on success.
     */
    public static function company_license_updated($event) {
        companypaths::company_license_updated($event);
        return true;
    }

    /**
     * Triggered via block_iomad_company_admin::user_license_assigned event.
     *
     * @param \block_iomad_company_admin\event\user_license_assigned $event
     * @return bool true on success.
     */
    public static function user_license_assigned($event) {
        companypaths::user_license_assigned($event);
        return true;
    }

    /**
     * Triggered via block_iomad_company_admin::user_license_unassigned event.
     *
     * @param \block_iomad_company_admin\event\user_license_unassigned $event
     * @return bool true on success.
     */
    public static function user_license_unassigned($event) {
        companypaths::user_license_unassigned($event);
        return true;
    }
}
