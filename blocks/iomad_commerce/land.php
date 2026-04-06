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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_commerce\helper;
use core\exception\moodle_exception;

require_once(__DIR__ . '/../../config.php' );

// Get user and token from wordpress.
$username = required_param( 'user', PARAM_CLEAN );
$token = required_param( 'token', PARAM_ALPHANUM );
$returnurl = required_param('returnurl', PARAM_URL);

// Is external commerce enabled?
if (empty($CFG->commerce_enable_external)) {
    die;
}

// All we're going to do now is to send this
// straight back to Wordpress to make sure it actually
// sent it.

// Sanity check that the passed return URL is valid.
$sqlequalvalue = $DB->sql_equal('value', ':returnurl', false);
$sqllikename = $DB->sql_like('name', ':externalurl');
if (!$configs = $DB->get_records_select(
    'config',
    "{$sqlequalvalue} AND {$sqllikename}",
    ['returnurl' => $returnurl,
     'externalurl' => 'commerce_externalshop_url%'])) {
    // That URL doesn't match anything.
    die;
}

// Don't care about companyid since we are only verifying.
$companyid = 0;

// Do the call.
$data = helper::verifytoken($username, $token, $companyid);

// Get the required user information.
$userlogin = $data->data->user_login;
$useremail = $data->data->user_email;
$firstname = $data->data->meta['first_name'][0];
$lastname = $data->data->meta['last_name'][0];

// Sanity check for username (just in case).
if ($username != $userlogin) {
    throw new moodle_exception("Passed data does not match retreived data");
    die;
}

// Does this user already exist?
$exists = true;
if (!$user = $DB->get_record('user', ['username' => $username])) {
    $user = (object) [];
    $exists = false;
} else {
    // Further sanity checking.
    if ($user->suspended == 1 || $user->deleted == 1) {
        throw new moodle_exception("Access denied");
        die;
    }
}

// Populate user object.
$user->username = $username;
$user->firstname = $firstname;
$user->lastname = $lastname;
$user->email = $useremail;
$user->confirmed = 1;
$user->lastip = getremoteaddr();
$user->timemodified = time();
$user->mnethostid = $CFG->mnet_localhost_id;

// Create or update the user.
if ($exists) {
    $DB->update_record( 'user', $user );
} else {
    // Now we might care about the company ID.
    if (empty($companyid)) {
        foreach ($configs as $config) {
            if ($config->name != 'commerce_externalshop_url') {
                $companyid = (int) substr($config->value, 24);
                break;
            }
        }
    }
    $user->auth = $data->data->auth;
    company_user::create($user, $companyid);
}

// Get the full user again.
$user = get_complete_user_data('username', $username);

// Complete login.
complete_user_login($user);

// And redirect.
redirect($CFG->wwwroot );
