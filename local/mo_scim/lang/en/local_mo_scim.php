<?php
// This file is part of miniOrange moodle plugin
//
// This plugin is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for local_mo_scim.
 *
 * @copyright   2021  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @package     local_mo_scim
 */

$string['mo_scimtitle']                        = 'Moodle SCIM Automated User Provisioning';
$string['pluginname']                          = 'Moodle SCIM Automated User Provisioning';
$string['mo_scim_configure_api_setting']       = 'This Moodle SCIM solution by miniOrange enables automated user provisioning, including user creation, updating, and deletion, from your Identity Provider (IDP) directly to your Moodle site. This integration ensures seamless synchronization between your IDP and Moodle, streamlining user management and enhancing security.';
$string['mo_scim_credentials']                 = 'SCIM API Credentials: ';
$string['mo_scim_attributes']                  = 'Custom Attribute Mapping:';
$string['mo_scim_attributes_desc']             = 'To create a custom attribute in Moodle, go to <b>Site Administration > Users > User Profile Fields</b>. Create a new Text Input field and provide a short name. Ensure that the Short Name in Moodle matches the Attribute Name in your IDP.';
$string['mo_scim_User_Authentication_API_URL'] = 'SCIM Base URL';
$string['mo_scim_User_Authentication_API_URL_OKTA']  = 'SCIM Base URL For Okta';
$string['mo_scim_User_Authentication_API_URL_AZURE'] = 'SCIM Base URL For Azure';
$string['mo_scim_apikey']                      = 'SCIM Bearer Token';
$string['mo_scim_login_settings']              = 'Login with miniOrange Credentials';
$string['mo_scim_username']                    = 'Username:';
$string['mo_scim_username_desc']               = 'Enter the username';
$string['mo_scim_password']                    = 'Password:';
$string['mo_scim_password_desc']               = 'Enter the Password';
$string['mo_scim_license_key']                 = 'License Key:';
$string['mo_scim_license_key_desc']            = 'Enter the license Key';
$string['mo_scim_invalid_customer']            = 'Invalid Customer. Please Check the UserName or PassWord.';
$string['mo_scim_heading_user_deprovision']    = 'User Deprovisioning Settings';
$string['mo_scim_desc_user_deprovision']       = 'Specify what action Moodle should perform when a user is deprovisioned through SCIM. You can choose to permanently delete the user or suspend their account.';
$string['mo_scim_user_deprovision_action']     = 'User Deprovision Action';
$string['mo_scim_user_deprovision_delete']     = 'Delete';
$string['mo_scim_user_deprovision_suspend']    = 'Suspend';
