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
 * A page to display a list of ratings for a given item (forum post etc)
 *
 * @package    core_rating
 * @category   rating
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");
require_once("lib.php");

$contextid  = required_param('contextid', PARAM_INT);
$component  = required_param('component', PARAM_COMPONENT);
$ratingarea = required_param('ratingarea', PARAM_AREA);
$itemid     = required_param('itemid', PARAM_INT);
$scaleid    = required_param('scaleid', PARAM_INT);
$sort       = optional_param('sort', '', PARAM_ALPHA);
$popup      = optional_param('popup', 0, PARAM_INT); // Any non-zero value if in a popup window.

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$url = new moodle_url('/rating/index.php', array('contextid' => $contextid,
                                                 'component' => $component,
                                                 'ratingarea' => $ratingarea,
                                                 'itemid' => $itemid,
                                                 'scaleid' => $scaleid));
if (!empty($sort)) {
    $url->param('sort', $sort);
}
if (!empty($popup)) {
    $url->param('popup', $popup);
}
$PAGE->set_url($url);
$PAGE->set_context($context);

if ($popup) {
    $PAGE->set_pagelayout('popup');
}

$params = array('contextid' => $contextid,
                'component' => $component,
                'ratingarea' => $ratingarea,
                'itemid' => $itemid,
                'scaleid' => $scaleid);
if (!has_capability('moodle/rating:view', $context) ||
        !component_callback($component, 'rating_can_see_item_ratings', array($params), true)) {
    print_error('noviewrate', 'rating');
}

$canviewallratings = has_capability('moodle/rating:viewall', $context);

switch ($sort) {
    case 'firstname':
        $sqlsort = "u.firstname ASC";
        break;
    case 'rating':
        $sqlsort = "r.rating ASC";
        break;
    default:
        $sqlsort = "r.timemodified ASC";
}

$scalemenu = make_grades_menu($scaleid);

$strrating  = get_string('rating', 'rating');
$strname    = get_string('name');
$strtime    = get_string('time');

$PAGE->set_title(get_string('allratingsforitem', 'rating'));
echo $OUTPUT->header();

$ratingoptions = new stdClass;
$ratingoptions->context = $context;
$ratingoptions->component = $component;
$ratingoptions->ratingarea = $ratingarea;
$ratingoptions->itemid = $itemid;
$ratingoptions->sort = $sqlsort;

$rm = new rating_manager();
$ratings = $rm->get_all_ratings_for_item($ratingoptions);
if (!$ratings) {
    $msg = get_string('noratings', 'rating');
    echo html_writer::tag('div', $msg, array('class' => 'mdl-align'));
} else {
    // To get the sort URL, copy the current URL and remove any previous sort.
    $sorturl = new moodle_url($url);
    $sorturl->remove_params('sort');

    $table = new html_table;
    $table->cellpadding = 3;
    $table->cellspacing = 3;
    $table->attributes['class'] = 'generalbox ratingtable';
    $table->head = array(
        '',
        html_writer::link(new moodle_url($sorturl, array('sort' => 'firstname')), $strname),
        html_writer::link(new moodle_url($sorturl, array('sort' => 'rating')), $strrating),
        html_writer::link(new moodle_url($sorturl, array('sort' => 'time')), $strtime)
    );
    $table->colclasses = array('', 'firstname', 'rating', 'time');
    $table->data = array();

    // If the scale was changed after ratings were submitted some ratings may have a value above the current maximum.
    // We can't just do count($scalemenu) - 1 as custom scales start at index 1, not 0.
    $maxrating = max(array_keys($scalemenu));

    foreach ($ratings as $rating) {
        if (!$canviewallratings and $USER->id != $rating->userid) {
            continue;
        }

        // Undo the aliasing of the user id column from user_picture::fields().
        // We could clone the rating object or preserve the rating id if we needed it again
        // but we don't.
        $rating->id = $rating->userid;

        $row = new html_table_row();
        $row->attributes['class'] = 'ratingitemheader';
        if ($course && $course->id) {
            $row->cells[] = $OUTPUT->user_picture($rating, array('courseid' => $course->id));
        } else {
            $row->cells[] = $OUTPUT->user_picture($rating);
        }
        $row->cells[] = fullname($rating);
        if ($rating->rating > $maxrating) {
            $rating->rating = $maxrating;
        }
        $row->cells[] = $scalemenu[$rating->rating];
        $row->cells[] = userdate($rating->timemodified);
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}
if ($popup) {
    echo $OUTPUT->close_window_button();
}
echo $OUTPUT->footer();
