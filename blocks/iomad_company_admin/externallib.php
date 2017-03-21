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
 * Iomad External Web Services
 *
 * @package block_iomad_company_admin
 * @copyright 2017 E-LearnDesign Limited
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");

class block_iomad_company_admin_external extends external_api {

    /**
     * block_iomad_company_admin_create_company
     *
     * Return description of method parameters
     * @return external_function_parameters
     */
    public static function create_company_parameters() {
        return new external_function_parameters(
            array(
                'company' => new external_single_structure(
                    array(
                        'name' => new external_value(PARAM_TEXT, 'Company long name'),
                        'shortname' => new external_value(PARAM_TEXT, 'Compay short name'),
                        'city' => new external_value(PARAM_TEXT, 'Company location city'),
                        'country' => new external_value(PARAM_TEXT, 'Company location country'),
                    )
                )
            )
        );
    }

    /**
     * block_iomad_company_admin_create_company
     *
     * Implement create_company
     * @param $company
     * @return boolean success
     */
    public static function create_company($company) {
        return true;
    }

    /**
     * block_iomad_company_admin_create_company
     *
     * Returns description of method result value
     * @return external_description
     */
    public static function create_company_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}

