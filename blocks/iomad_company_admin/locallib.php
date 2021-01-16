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

class iomad_company_admin {

    /**
     * Get the roles for the company_capabilties screen
     */
    public static function get_roles() {
        global $DB;

        $roles = $DB->get_records('role', array(), 'sortorder ASC');

        // Only want the ones that have a 'name' defined please.
        $namedroles = array();
        foreach ($roles as $role) {
            if ($role->name) {
                $namedroles[$role->id] = $role;
            }
        }

        return $namedroles;
    }

    /**
     * Get the Iomad capabilities for given role
     * (We only need to worry about the ones that are SET
     * so we can fish them out of the role_capabilities table
     * directly)
     */
    public static function get_iomad_capabilities($roleid, $companyid) {
        global $DB;

        // We need capabilities defined in the site context
        $context = context_system::instance();
        $capabilities = $DB->get_records('role_capabilities', array('roleid' => $roleid, 'contextid' => $context->id));

        // Filter out caps. Only want 'local/report' and ones containing 'iomad'
        $filtered_capabilities = array();
        foreach ($capabilities as $capability) {
            if ((strpos($capability->capability, 'local/report')===false)
                    && (strpos($capability->capability, 'iomad')===false)
                    && (strpos($capability->capability, 'local/email')===false)
                    ) {
                continue;
            }

            // add the iomad restriction info
            if ($restriction = $DB->get_record('company_role_restriction', array(
                            'roleid' => $roleid,
                            'companyid' => $companyid,
                            'capability' => $capability->capability
            ))) {
                $capability->iomad_restriction = true;
            } else {
                $capability->iomad_restriction = false;
            }
            $filtered_capabilities[$capability->id] = $capability;
        }

        return $filtered_capabilities;
    }

    /**
     * Get the Iomad template capabilities for given role
     * (We only need to worry about the ones that are SET
     * so we can fish them out of the role_capabilities table
     * directly)
     */
    public static function get_iomad_template_capabilities($roleid, $templateid) {
        global $DB;

        // We need capabilities defined in the site context
        $context = context_system::instance();
        $capabilities = $DB->get_records('role_capabilities', array('roleid' => $roleid, 'contextid' => $context->id));

        // Filter out caps. Only want 'local/report' and ones containing 'iomad'
        $filtered_capabilities = array();
        foreach ($capabilities as $capability) {
            if ((strpos($capability->capability, 'local/report')===false)
                    && (strpos($capability->capability, 'iomad')===false)
                    && (strpos($capability->capability, 'local/email')===false)
                    ) {
                continue;
            }

            // add the iomad restriction info
            if ($restriction = $DB->get_record('company_role_templates_caps', array(
                            'roleid' => $roleid,
                            'templateid' => $templateid,
                            'capability' => $capability->capability
            ))) {
                $capability->iomad_restriction = true;
            } else {
                $capability->iomad_restriction = false;
            }
            $filtered_capabilities[$capability->id] = $capability;
        }

        return $filtered_capabilities;
    }
}