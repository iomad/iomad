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
 * Plugin language strings
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Strings for component 'local_iomad_oidc_sync', language 'en'
 */

$string['agreeconsent'] = 'Consent';
$string['approvalset'] = 'Approval of IOMAD OIDC sync granted successfully';
$string['boilerplate'] = 'To set up the OIDC sync tasks you must first provide your TENANTNAME or GUID and then click on the appoval link.  Once you have approved, if the connection is not showing as green, click on the red-cross to check.';
$string['configerror'] = 'Invalid configuration detected.';
$string['configlogs'] = 'Config logs';
$string['consent_title'] = 'IOMAD OIDC sync company consent';
$string['consentlink'] = 'Submit consent with Microsoft';
$string['graphproperties'] = 'MS Graph user select fields';
$string['graphproperties_help'] = "By default, only a limited set of properties are returned (businessPhones, displayName, givenName, id, jobTitle, mail, mobilePhone, officeLocation, preferredLanguage, surname, userPrincipalName).<br>
To return an alternative property set, you can specify the desired set of user properties in a comma separated list without spaces. For example, to return <b>only</b> displayName, givenName, and postalCode, set this value to <i>displayName,givenName,postalCode</i>.";
$string['iomad_oidc_sync:manage'] = 'Manage IOMAD OIDC sync settings';
$string['iomad_oidc_sync:view'] = 'View the IOMAD OIDC sync status';
$string['loglink'] = 'View Config log';
$string['managermapping'] = '<b>NOTE</b> - If you are mapping local fields to the remote manager object, then please use the format <b>manager.<i>field</i></b> as the mapping value. E.g. <b>manager.mail</b>';
$string['oidc_sync_task'] = 'IOMAD OIDC sync task';
$string['pluginname'] = 'IOMAD OIDC sync';
$string['privacy:metadata'] = 'The IOMAD OIDC sync plugin only shows data stored in other locations.';
$string['settenantnameorguid'] = 'Set the Tenant name or GUID';
$string['syncgroupid'] = 'Optional object ID of the group to be synchronised”';
$string['tenantnameorguid'] = 'Tenant name or GUID';
$string['tenantnameorguid_changed_success'] = 'Company options saved successfully';
$string['tenantnameorguid_changed_warning'] = 'Company options saved successfully.<br>Due to the change in Tenant name or GUID value, you may need to go through the approval process again';
$string['unsuspendonsync'] = 'Un-suspend existing users';
$string['useroptions'] = 'With removed users we';
