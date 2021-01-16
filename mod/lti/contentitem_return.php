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
 * Handle the return from the Tool Provider after selecting a content item.
 *
 * @package mod_lti
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$messagetype = required_param('lti_message_type', PARAM_TEXT);
$version = required_param('lti_version', PARAM_TEXT);
$consumerkey = required_param('oauth_consumer_key', PARAM_RAW);
$items = optional_param('content_items', '', PARAM_RAW);
$errormsg = optional_param('lti_errormsg', '', PARAM_TEXT);
$msg = optional_param('lti_msg', '', PARAM_TEXT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
require_sesskey();
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);
require_capability('mod/lti:addcoursetool', $context);

$redirecturl = null;
$returndata = null;
if (empty($errormsg) && !empty($items)) {
    try {
        $returndata = lti_tool_configuration_from_content_item($id, $messagetype, $version, $consumerkey, $items);
    } catch (moodle_exception $e) {
        $errormsg = $e->getMessage();
    }
}

$pageurl = new moodle_url('/mod/lti/contentitem_return.php');
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('popup');
echo $OUTPUT->header();

// Call JS module to redirect the user to the course page or close the dialogue on error/cancel.
$PAGE->requires->js_call_amd('mod_lti/contentitem_return', 'init', [$returndata]);

echo $OUTPUT->footer();

// Add messages to notification stack for rendering later.
if ($errormsg) {
    // Content item selection has encountered an error.
    \core\notification::error($errormsg);

} else if (!empty($returndata)) {
    // Means success.
    if (!$msg) {
        $msg = get_string('successfullyfetchedtoolconfigurationfromcontent', 'lti');
    }
    \core\notification::success($msg);
}
