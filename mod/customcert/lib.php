<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Customcert module core interaction API
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Add customcert instance.
 *
 * @param stdClass $data
 * @param mod_customcert_mod_form $mform
 * @return int new customcert instance id
 */
function customcert_add_instance($data, $mform) {
    global $DB;

    // Create a template for this customcert to use.
    $context = context_module::instance($data->coursemodule);
    $template = \mod_customcert\template::create($data->name, $context->id);

    // Add the data to the DB.
    $data->templateid = $template->get_id();
    $data->protection = \mod_customcert\certificate::set_protection($data);
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('customcert', $data);

    // Add a page to this customcert.
    $template->add_page();

    return $data->id;
}

/**
 * Update customcert instance.
 *
 * @param stdClass $data
 * @param mod_customcert_mod_form $mform
 * @return bool true
 */
function customcert_update_instance($data, $mform) {
    global $DB;

    $data->protection = \mod_customcert\certificate::set_protection($data);
    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('customcert', $data);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool true if successful
 */
function customcert_delete_instance($id) {
    global $CFG, $DB;

    // Ensure the customcert exists.
    if (!$customcert = $DB->get_record('customcert', array('id' => $id))) {
        return false;
    }

    // Get the course module as it is used when deleting files.
    if (!$cm = get_coursemodule_from_instance('customcert', $id)) {
        return false;
    }

    // Delete the customcert instance.
    if (!$DB->delete_records('customcert', array('id' => $id))) {
        return false;
    }

    // Now, delete the template associated with this certificate.
    if ($template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid))) {
        $template = new \mod_customcert\template($template);
        $template->delete();
    }

    // Delete the customcert issues.
    if (!$DB->delete_records('customcert_issues', array('customcertid' => $id))) {
        return false;
    }

    // Delete any files associated with the customcert.
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    return true;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified customcert
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array status array
 */
function customcert_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'customcert');
    $status = array();

    if (!empty($data->reset_customcert)) {
        $sql = "SELECT cert.id
                  FROM {customcert} cert
                 WHERE cert.course = :courseid";
        $DB->delete_records_select('customcert_issues', "customcertid IN ($sql)", array('courseid' => $data->courseid));
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteissuedcertificates', 'customcert'),
            'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        shift_course_mod_dates('customcert', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the customcert.
 *
 * @param mod_customcert_mod_form $mform form passed by reference
 */
function customcert_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'customcertheader', get_string('modulenameplural', 'customcert'));
    $mform->addElement('advcheckbox', 'reset_customcert', get_string('deleteissuedcertificates', 'customcert'));
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course
 * @return array
 */
function customcert_reset_course_form_defaults($course) {
    return array('reset_customcert' => 1);
}

/**
 * Returns information about received customcert.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $customcert
 * @return stdClass the user outline object
 */
function customcert_user_outline($course, $user, $mod, $customcert) {
    global $DB;

    $result = new stdClass();
    if ($issue = $DB->get_record('customcert_issues', array('customcertid' => $customcert->id, 'userid' => $user->id))) {
        $result->info = get_string('issued', 'customcert');
        $result->time = $issue->timecreated;
    } else {
        $result->info = get_string('notissued', 'customcert');
    }

    return $result;
}

/**
 * Returns information about received customcert.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $customcert
 * @return string the user complete information
 */
function customcert_user_complete($course, $user, $mod, $customcert) {
    global $DB, $OUTPUT;

    if ($issue = $DB->get_record('customcert_issues', array('customcertid' => $customcert->id, 'userid' => $user->id))) {
        echo $OUTPUT->box_start();
        echo get_string('issued', 'customcert') . ": ";
        echo userdate($issue->timecreated);
        echo $OUTPUT->box_end();
    } else {
        print_string('notissuedyet', 'customcert');
    }
}

/**
 * Serves certificate issues and other files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool|null false if file not found, does not return anything if found - just send the file
 */
function customcert_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');

    // We are positioning the elements.
    if ($filearea === 'image') {
        if ($context->contextlevel == CONTEXT_MODULE) {
            require_login($course, false, $cm);
        } else if ($context->contextlevel == CONTEXT_SYSTEM && !has_capability('mod/customcert:manage', $context)) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = '/' . $context->id . '/mod_customcert/image/' . $relativepath;

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload);
    }
}

/**
 * The features this activity supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function customcert_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Used for course participation report (in case customcert is added).
 *
 * @return array
 */
function customcert_get_view_actions() {
    return array('view', 'view all', 'view report');
}

/**
 * Used for course participation report (in case customcert is added).
 *
 * @return array
 */
function customcert_get_post_actions() {
    return array('received');
}

/**
 * Function to be run periodically according to the moodle cron.
 */
function customcert_cron() {
    return true;
}

/**
 * Serve the edit element as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function mod_customcert_output_fragment_editelement($args) {
    global $DB;

    // Get the element.
    $element = $DB->get_record('customcert_elements', array('id' => $args['elementid']), '*', MUST_EXIST);

    $pageurl = new moodle_url('/mod/customcert/rearrange.php', array('pid' => $element->pageid));
    $form = new \mod_customcert\edit_element_form($pageurl, array('element' => $element));

    return $form->render();
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called.
 *
 * @param settings_navigation $settings
 * @param navigation_node $customcertnode
 */
function customcert_extend_settings_navigation(settings_navigation $settings, navigation_node $customcertnode) {
    global $DB, $PAGE;

    $keys = $customcertnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/customcert:manage', $PAGE->cm->context)) {
        // Get the template id.
        $templateid = $DB->get_field('customcert', 'templateid', array('id' => $PAGE->cm->instance));
        $node = navigation_node::create(get_string('editcustomcert', 'customcert'),
                new moodle_url('/mod/customcert/edit.php', array('tid' => $templateid)),
                navigation_node::TYPE_SETTING, null, 'mod_customcert_edit',
                new pix_icon('t/edit', ''));
        $customcertnode->add_node($node, $beforekey);
    }

    if (has_capability('mod/customcert:verifycertificate', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('verifycertificate', 'customcert'),
            new moodle_url('/mod/customcert/verify_certificate.php', array('contextid' => $PAGE->cm->context->id)),
            navigation_node::TYPE_SETTING, null, 'mod_customcert_verify_certificate',
            new pix_icon('t/check', ''));
        $customcertnode->add_node($node, $beforekey);
    }

    return $customcertnode->trim_if_empty();
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function mod_customcert_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $url = new moodle_url('/mod/customcert/my_certificates.php', array('userid' => $user->id));
    $node = new core_user\output\myprofile\node('miscellaneous', 'mycustomcerts',
        get_string('mycertificates', 'customcert'), null, $url);
    $tree->add_node($node);
}

/**
 * Handles editing the 'name' of the element in a list.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param string $newvalue
 * @return \core\output\inplace_editable
 */
function mod_customcert_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    if ($itemtype === 'elementname') {
        $element = $DB->get_record('customcert_elements', array('id' => $itemid), '*', MUST_EXIST);
        $page = $DB->get_record('customcert_pages', array('id' => $element->pageid), '*', MUST_EXIST);
        $template = $DB->get_record('customcert_templates', array('id' => $page->templateid), '*', MUST_EXIST);

        // Set the template object.
        $template = new \mod_customcert\template($template);
        // Perform checks.
        if ($cm = $template->get_cm()) {
            require_login($cm->course, false, $cm);
        } else {
            $PAGE->set_context(context_system::instance());
            require_login();
        }
        // Make sure the user has the required capabilities.
        $template->require_manage();

        // Clean input and update the record.
        $updateelement = new stdClass();
        $updateelement->id = $element->id;
        $updateelement->name = clean_param($newvalue, PARAM_TEXT);
        $DB->update_record('customcert_elements', $updateelement);

        return new \core\output\inplace_editable('mod_customcert', 'elementname', $element->id, true,
            $updateelement->name, $updateelement->name);
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_customcert_get_fontawesome_icon_map() {
    return [
        'mod_customcert:download' => 'fa-download'
    ];
}
