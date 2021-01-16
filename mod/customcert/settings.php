<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Creates a link to the upload form on the settings page.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$url = $CFG->wwwroot . '/mod/customcert/verify_certificate.php';
$settings->add(new admin_setting_configcheckbox('customcert/verifyallcertificates',
    get_string('verifyallcertificates', 'customcert'),
    get_string('verifyallcertificates_desc', 'customcert', $url),
    0));

$settings->add(new admin_setting_configcheckbox('customcert/showposxy',
    get_string('showposxy', 'customcert'),
    get_string('showposxy_desc', 'customcert'),
    0));

$settings->add(new \mod_customcert\admin_setting_link('customcert/managetemplates',
    get_string('managetemplates', 'customcert'), get_string('managetemplatesdesc', 'customcert'),
    get_string('managetemplates', 'customcert'), new moodle_url('/mod/customcert/manage_templates.php'), ''));

$settings->add(new \mod_customcert\admin_setting_link('customcert/uploadimage',
    get_string('uploadimage', 'customcert'), get_string('uploadimagedesc', 'customcert'),
    get_string('uploadimage', 'customcert'), new moodle_url('/mod/customcert/upload_image.php'), ''));
