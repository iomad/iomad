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
 * IOMAD microlearning block auto login landing page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_microlearning\microlearning;

require_once(__DIR__ . '/../../config.php');

$nuggetid = required_param('nuggetid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$accesskey = required_param('accesskey', PARAM_CLEAN);

// We don't have require_login() here as this is a log in page.

// Check the user id still valid.
if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0])) {
    throw new moodle_exception('invaliduser', 'block_iomad_microlearning');
}

// Check the nugget id still valid.
if (!$nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid])) {
    throw new moodle_exception('invalidnugget', 'block_iomad_microlearning');
}

// Are we already logged in?
$allowcontinue = false;
if (isloggedin() && !isguestuser()) {
    $allowcontinue = true;
} else if ($DB->get_record_sql(
    "SELECT id FROM {block_iomad_microlearning_thread_users}
     WHERE userid = :userid
     AND nuggetid = :nuggetid
     AND accesskey = :accesskey
     AND schedule_date > :expirytime
     AND schedule_date < :time",
    ['userid' => $userid,
     'nuggetid' => $nuggetid,
     'accesskey' => $accesskey,
     'time' => time(),
     'expirytime' => time() - $CFG->microlearninglinkexpires * 24 * 60 * 60])) {

    // Valid access token.  Log in the user.
    $allowcontinue = true;
    complete_user_login($user);

    \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

    // Sets the username cookie.
    if (!empty($CFG->nolastloggedin)) {
        // Do not store last logged in user in cookie
        // auth plugins can temporarily override this from loginpage_hook()
        // do not save $CFG->nolastloggedin in database!
        $nolastloggedin = true;
    } else if (empty($CFG->rememberusername) || ($CFG->rememberusername == 2 && empty($frm->rememberusername))) {
        // No permanent cookies, delete old one if exists.
        set_moodle_cookie('');

    } else {
        set_moodle_cookie($USER->username);
    }
    // Add something to the SESSION so we can trap where they came from.
    $SESSION->came_via_microlearning = true;
}

// Get the nugget url.
$linkurl = microlearning::get_nugget_url($nugget);

// Are we going straight there?
if ($allowcontinue) {
    redirect($linkurl);
} else {
    // Got to log in first.
    $SESSION->wantsurl = $linkurl;
    redirect(new moodle_url('/login/index.php'));
}
