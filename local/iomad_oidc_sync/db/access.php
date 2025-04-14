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
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Need to add this or you get warning in the roles pages.
global $CFG;

$contextlevel = CONTEXT_COMPANY;

$capabilities = array(

    'local/iomad_oidc_sync:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => $contextlevel,
        'archetypes' => array(
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ),
    ),

    'local/iomad_oidc_sync:manage' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => $contextlevel,
        'archetypes' => array(
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ),
    )
);
