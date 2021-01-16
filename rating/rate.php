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
 * This page receives non-ajax rating submissions
 *
 * It is similar to rate_ajax.php. Unlike rate_ajax.php a return url is required.
 *
 * @package    core_rating
 * @category   rating
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot.'/rating/lib.php');

$contextid   = required_param('contextid', PARAM_INT);
$component   = required_param('component', PARAM_COMPONENT);
$ratingarea  = required_param('ratingarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$scaleid     = required_param('scaleid', PARAM_INT);
$userrating  = required_param('rating', PARAM_INT);
$rateduserid = required_param('rateduserid', PARAM_INT); // Which user is being rated. Required to update their grade.
$returnurl   = required_param('returnurl', PARAM_LOCALURL); // Required for non-ajax requests.

$result = new stdClass;

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null; // Now we have a context object, throw away the id from the user.
$PAGE->set_context($context);
$PAGE->set_url('/rating/rate.php', array('contextid' => $context->id));

if (!confirm_sesskey() || !has_capability('moodle/rating:rate', $context)) {
    print_error('ratepermissiondenied', 'rating');
}

$rm = new rating_manager();

// Check the module rating permissions.
// Doing this check here rather than within rating_manager::get_ratings() so we can choose how to handle the error.
$pluginpermissionsarray = $rm->get_plugin_permissions_array($context->id, $component, $ratingarea);

if (!$pluginpermissionsarray['rate']) {
    print_error('ratepermissiondenied', 'rating');
} else {
    $params = array(
        'context'     => $context,
        'component'   => $component,
        'ratingarea'  => $ratingarea,
        'itemid'      => $itemid,
        'scaleid'     => $scaleid,
        'rating'      => $userrating,
        'rateduserid' => $rateduserid
    );
    if (!$rm->check_rating_is_valid($params)) {
        echo $OUTPUT->header();
        echo get_string('ratinginvalid', 'rating');
        echo $OUTPUT->footer();
        die();
    }
}

if ($userrating != RATING_UNSET_RATING) {
    $ratingoptions = new stdClass;
    $ratingoptions->context = $context;
    $ratingoptions->component = $component;
    $ratingoptions->ratingarea = $ratingarea;
    $ratingoptions->itemid  = $itemid;
    $ratingoptions->scaleid = $scaleid;
    $ratingoptions->userid  = $USER->id;

    $rating = new rating($ratingoptions);
    $rating->update_rating($userrating);
} else { // Delete the rating if the user set to "Rate..."
    $options = new stdClass;
    $options->contextid = $context->id;
    $options->component = $component;
    $options->ratingarea = $ratingarea;
    $options->userid = $USER->id;
    $options->itemid = $itemid;

    $rm->delete_ratings($options);
}

if (!empty($cm) && $context->contextlevel == CONTEXT_MODULE) {
    // Tell the module that its grades have changed.
    $modinstance = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);
    $modinstance->cmidnumber = $cm->id; // MDL-12961.
    $functionname = $cm->modname.'_update_grades';
    require_once($CFG->dirroot."/mod/{$cm->modname}/lib.php");
    if (function_exists($functionname)) {
        $functionname($modinstance, $rateduserid);
    }
}

redirect($returnurl);
