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
 * IOMAD Dashboard local library functions
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, company_user};

/**
 * Fix the page breadcrumb links and text
 *
 * @param object $PAGE
 * @param string $linktext
 * @param moodle_url $linkurl
 * @return void
 */
function company_admin_fix_breadcrumb(&$PAGE, $linktext, $linkurl) {

    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add(get_string('administrationsite'));
    $PAGE->navbar->add(get_string('myhome'), new moodle_url('/my'));
    $PAGE->navbar->add($linktext, $linkurl);
}

/**
 * Callback for inplace editable API.
 *
 * @param string $itemtype - Only user_roles is supported.
 * @param string $itemid - Courseid and userid separated by a :
 * @param string $newvalue - json encoded list of roleids.
 * @return \core\output\inplace_editable
 */
function block_iomad_company_admin_inplace_editable($itemtype, $itemid, $newvalue) {
    if ($itemtype === 'courses_autoenrol') {
        return block_iomad_company_admin\output\courses_autoenrol_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_mandatory') {
        return block_iomad_company_admin\output\courses_mandatory_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_license') {
        return block_iomad_company_admin\output\courses_license_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_hasgrade') {
        return block_iomad_company_admin\output\courses_hasgrade_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_notifyperiod') {
        return block_iomad_company_admin\output\courses_notifyperiod_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_shared') {
        return block_iomad_company_admin\output\courses_shared_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_validlength') {
        return block_iomad_company_admin\output\courses_validlength_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_warncompletion') {
        return block_iomad_company_admin\output\courses_warncompletion_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_warnexpire') {
        return block_iomad_company_admin\output\courses_warnexpire_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'courses_warnnotstarted') {
        return block_iomad_company_admin\output\courses_warnnotstarted_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'enrolment_expireafter') {
        return block_iomad_company_admin\output\enrolment_expireafter_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'user_departments') {
        return block_iomad_company_admin\output\user_departments_editable::update($itemid, $newvalue);
    }
    if ($itemtype === 'user_roles') {
        return block_iomad_company_admin\output\user_roles_editable::update($itemid, $newvalue);
    }
}

/**
 * This function delegates file serving to individual plugins
 *
 * @param string $relativepath
 * @param bool $forcedownload
 * @param null|string $preview the preview mode, defaults to serving the original file
 * @param boolean $offline If offline is requested - don't serve a redirect to an external file, return a file suitable for viewing
 *                         offline (e.g. mobile app).
 * @param bool $embed Whether this file will be served embed into an iframe.
 * @todo MDL-31088 file serving improments
 */
function block_iomad_company_admin_pluginfile($course,
                                              $birecordorcm,
                                              $context,
                                              $filearea,
                                              $args,
                                              $forcedownload,
                                              array $options=[]) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea === 'classroom_description') {
        if ($CFG->forcelogin) {
            // No login necessary - unless login forced everywhere.
            require_login();
        }

        $fs = get_file_storage();

        $filename = array_pop($args);
        $filepath = $args ? '/'.implode('/', $args).'/' : '/';
        if (!$file = $fs->get_file($context->id, 'block_iomad_company_admin', 'classroom_description', 0, $filepath, $filename) ||
            $file->is_directory()) {
            send_file_not_found();
        }

        \core\session\manager::write_close(); // Unlock session during file serving.
        send_stored_file($file, null, 0, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function block_iomad_company_admin_render_navbar_output(\renderer_base $renderer) {
    global $USER, $CFG;

    // Early bail out conditions.
    if (!isloggedin() || isguestuser() || !get_config('local_iomad', 'showcompanydropdown')) {
        return '';
    }

    $output = '';

    // Add the notifications popover.
    if ($companyinfo = company_user::add_user_popup_selector()) {
        $output .= $renderer->render_from_template('block_iomad_company_admin/company_info_popover', $companyinfo);
    }

    return $output;
}
