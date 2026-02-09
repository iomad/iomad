<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Verify license settings
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
$data = data_submitted();

if (isset($data->s_local_mo_scim_username)) {
    $config->username = $data->s_local_mo_scim_username;
}
if (isset($data->s_local_mo_scim_password)) {
    $config->password = $data->s_local_mo_scim_password;
}
if (isset($data->s_local_mo_scim_license_key)) {
    $config->license_key = $data->s_local_mo_scim_license_key;
}
$url      = 'https://login.xecurify.com/moas/rest/customer/key';
$username = isset($data->s_local_mo_scim_username) ? $config->username : '';
$password = isset($data->s_local_mo_scim_password) ? $config->password : '';
$code     = isset($data->s_local_mo_scim_license_key) ? $config->license_key : '';

$fields = [
    'email'    => $username,
    'password' => $password,
];

$fieldstring = json_encode($fields);

$headers = [
    'Content-Type: application/json',
    'Authorization: Basic',
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);

curl_setopt($ch, CURLOPT_POST, 1);

curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$response = json_decode($response);
curl_close($ch);

if (isset($response->status)) {
    $content = (array) $response;
    if (is_array($content)) {
        set_config('moapikey', $content['apiKey'], 'local_mo_scim');
        set_config('moid', $content['id'], 'local_mo_scim');
        set_config('motoken', $content['token'], 'local_mo_scim');


        $validlicenseurl = 'https://login.xecurify.com/moas/api/backupcode/verify';

        $currenttimeinmillis = round(microtime(true) * 1000);

        $stringtohash = $content['id'] . number_format($currenttimeinmillis, 0, '', '') . $content['apiKey'];
        $hashvalue    = hash('sha512', $stringtohash);

        $currenttimeinmillis = number_format($currenttimeinmillis, 0, '', '');

        $siteurl = $CFG->wwwroot;


        $fields = [
            'code'             => $code,
            'customerKey'      => $content['id'],
            'licenseType'      => 'MOODLE_SCIM_PLUGIN',
            'additionalFields' => [
                'field1' => $siteurl,
            ],
        ];

        $fieldstring = json_encode($fields);

        $headers = [
            'Content-Type: application/json',
            'Customer-Key: ' . $content['id'],
            'Timestamp: ' . $currenttimeinmillis,
            'Authorization: ' . $hashvalue,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $validlicenseurl);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            echo "An error occurred during the request: $error";
        } else {
            $response = json_decode($response);
            if ($response !== null && strcasecmp($response->status, 'SUCCESS') == 0) {
                set_config('scim_lk', true, 'local_mo_scim');
            } else if ($response !== null && strcasecmp($response->status, 'ERROR') == 0) {
                $errormessage = !empty($response->message) ? $response->message : "License Validation Failed";
                echo "Request failed: $errormessage";
            } else {
                echo "Unexpected response format.";
            }
        }
        curl_close($ch);
    }
}


$settings->add(
    new admin_setting_heading(
        'local_mo_scim/login_credentials',
        new lang_string('mo_scim_login_settings', 'local_mo_scim'),
        ''
    )
);
$settings->add(
    new admin_setting_configtext(
        'local_mo_scim/username',
        get_string('mo_scim_username', 'local_mo_scim'),
        get_string('mo_scim_username_desc', 'local_mo_scim'),
        '',
        PARAM_RAW_TRIMMED
    )
);

$settings->add(
    new admin_setting_configpasswordunmask(
        'local_mo_scim/password',
        get_string('mo_scim_password', 'local_mo_scim'),
        get_string('mo_scim_password_desc', 'local_mo_scim'),
        '',
        PARAM_RAW_TRIMMED
    )
);

$settings->add(
    new admin_setting_configtext(
        'local_mo_scim/license_key',
        get_string('mo_scim_license_key', 'local_mo_scim'),
        get_string('mo_scim_license_key_desc', 'local_mo_scim'),
        '',
        PARAM_RAW_TRIMMED
    )
);
