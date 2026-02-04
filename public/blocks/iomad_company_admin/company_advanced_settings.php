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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Script to let a user import departments to a particular company.
 */

use block_iomad_company_admin\forms\company_mfa_form;
use block_iomad_company_admin\iomad_company_admin;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/auth/iomadoidc/lib.php');
require_once($CFG->dirroot.'/auth/iomadsaml2/locallib.php');
require_once('lib.php');

$action = optional_param('action', '', PARAM_ALPHA);

require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);
$postfix = "_$companyid";

iomad::require_capability('block/iomad_company_admin:companyadvancedsettings', $companycontext);

$linktext = get_string('companyadvanced', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php');

$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// get output renderer
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Check our capabilities.
$candoiomadoidc = iomad::has_capability('block/iomad_company_admin:configiomadoidc', $companycontext) ? true : false;
$candoiomadsaml2 = iomad::has_capability('block/iomad_company_admin:configiomadsaml2', $companycontext) ? true : false;
$candoiomadoidcsync = iomad::has_capability('block/iomad_company_admin:configiomadoidcsync', $companycontext) ? true : false;
$candopolicies = iomad::has_capability('block/iomad_company_admin:configpolicies', $companycontext) ? true : false;
$candoauthoptions = iomad::has_capability('block/iomad_company_admin:companyauthsettings', $companycontext) ? true : false;
$candomfa = iomad::has_capability('block/iomad_company_admin:configmfa', $companycontext) ? true : false;
$candomfa = false;

// Check if all of the modules are installed.
$authmodules = core_plugin_manager::instance()->get_plugins_of_type('auth');
$localmodules = core_plugin_manager::instance()->get_plugins_of_type('local');
$toolmodules = core_plugin_manager::instance()->get_plugins_of_type('tool');

if (empty($authmodules['iomadoidc'])) {
    $candoiomadoidc = false;
    $candoiomadoidcsync = false;
}
if (empty($authmodules['iomadsaml2'])) {
    $candoiomadsaml2 = false;
}
if (empty($localmodules['iomad_oidc_sync'])) {
    $candoiomadoidcsync = false;
}
if (empty($toolmodules['iomadpolicy'])) {
    $candopolicies = false;
}
$candosmpt = iomad::has_capability('block/iomad_company_admin:company_edit_smtp', $companycontext);

// Are we showing a form?
$mform = null;
if (!empty($action) &&
    confirm_sesskey()) {
    if ($candoiomadoidc &&
        $action == 'iomadoidcbasic') {
        $mform = iomad_company_admin::get_company_iomadoidc_form();
    } else if ($candoiomadoidc &&
        $action == 'iomadoidcmappings') {
        $mform = iomad_company_admin::get_company_iomadoidc_mappings_form();
    } else if ($candoiomadsaml2 &&
               $action == 'iomadsaml') {
        $mform = iomad_company_admin::get_company_iomadsaml2_form();
    } else if ($candoiomadsaml2 &&
               $action == 'iomadsamlmappings') {
        $mform = iomad_company_admin::get_company_iomadsaml2_mappings_form();
    } else if ($candomfa &&
               $action == 'iomadmfasettings') {
        //$companyiomadsaml2data = get_config('auth_iomadsaml2');
        //$companyiomadsaml2data->action = $action;
        $mform = new company_mfa_form($PAGE->url);
        // Set the form data
        //$mform->set_data($companyiomadsaml2data);
    } else if ($candoauthoptions &&
               $action == 'companyauthoptions') {
        $mform = iomad_company_admin::get_company_auth_options_form($PAGE->url);
    } else if ($candosmpt &&
               $action == 'companysmtpsettings') {
        $mform = iomad_company_admin::get_company_smtp_options_form($PAGE->url);
    }

    // Process the form.
    if (!empty($mform) &&
        $mform->is_cancelled()) {
            redirect($linkurl);
        die;
    } else if (!empty($mform) &&
               $data = $mform->get_data()) {
        if ($action == 'iomadoidcbasic') {
            // Process the changes for auth_iomadoidc.
            $redirectmessage = iomad_company_admin::process_company_iomadoidc_form($data);
        } else if ($action == 'iomadoidcmappings') {
            // Process the changes for auth_iomadoidc mappings.
            $redirectmessage = iomad_company_admin::process_company_iomadoidc_mappings_form($data);
        } else if ($action == 'iomadsamlmappings') {
            // Process the changes for auth_iomadsaml2 mappings.
            $redirectmessage = iomad_company_admin::process_company_iomadsaml2_mappings_form($data);
        } else if ($action == 'iomadsaml') {
            // Process the changes for auth_iomadsaml2.
            $redirectmessage = iomad_company_admin::process_company_iomadsaml2_form($data);
        } else if ($action == 'companyauthoptions') {
            // Process the changes for general auth settings.
            $redirectmessage = iomad_company_admin::process_company_auth_options_form($data);
        } else if ($action == 'companysmtpsettings') {
            // Process the changes for company smtp settings.
            $redirectmessage = iomad_company_admin::process_company_smtp_options_form($data);
        }

        // Set redirect success.
        redirect($linkurl, $redirectmessage, null, notification::NOTIFY_SUCCESS);
        die;
    }
}

$options = html_writer::start_tag('div', ['class' => 'containerfluid']);
if ($candoiomadoidc || $candoiomadsaml2) {
    $options .= html_writer::start_tag('div', ['class' => 'row']);
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-3']);
    $options .= html_writer::tag('h4', get_string('authenticationoptions', 'auth'));
    $options .= html_writer::end_tag('div');
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-9']);
    $options .= html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    if ($candoauthoptions) {
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('commonsettings', 'admin'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'companyauthoptions',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
    }
    if ($candoiomadoidc) {
        $options .= html_writer::tag('li', html_writer::tag('strong', get_string('pluginname', 'auth_iomadoidc')));
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('settings_page_application', 'auth_iomadoidc'),
                                     array('href' => new moodle_url('/auth/iomadoidc/manageapplication.php',
                                                                    ['companyonly' => true])));

        $options .= html_writer::end_tag('li');
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('settings', 'moodle'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'iomadoidcbasic',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('auth_data_mapping', 'auth'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'iomadoidcmappings',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
    }
    if ($candoiomadoidcsync) {
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('pluginname', 'local_iomad_oidc_sync'),
                                     array('href' => new moodle_url('/local/iomad_oidc_sync/index.php',
                                                                    ['action' => 'iomadoidc',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
    }
    if ($candoiomadsaml2) {
        $options .= html_writer::tag('li', html_writer::tag('strong', get_string('pluginname', 'auth_iomadsaml2')));
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('settings', 'moodle'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'iomadsaml',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('auth_data_mapping', 'auth'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'iomadsamlmappings',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
    }
    $options .= html_writer::end_tag('ul');
    $options .= html_writer::end_tag('div');
}
$options .= html_writer::end_tag('div');

// User parts.
if ($candomfa || $candopolicies) {
    $options .= html_writer::start_tag('div', ['class' => 'row']);
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-3']);
    $options .= html_writer::tag('h4', get_string('serviceusersettings', 'webservice'));
    $options .= html_writer::end_tag('div');
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-9']);
    $options .= html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    if ($candopolicies) {
        $options .= html_writer::tag('li', html_writer::tag('strong', get_string('pluginname', 'tool_policy')));
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('settings', 'moodle'),
                                     array('href' => new moodle_url('/admin/tool/iomadpolicy/managedocs.php',
                                                                    ['companyonly' => true])));

        $options .= html_writer::end_tag('li');
    }
    if ($candomfa) {
        $options .= html_writer::tag('li', html_writer::tag('strong', get_string('pluginname', 'tool_mfa')));
        $options .= html_writer::start_tag('li');
        $options .= html_writer::tag('a',
                                     get_string('settings', 'moodle'),
                                     array('href' => new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php',
                                                                    ['action' => 'iomadmfasettings',
                                                                     'sesskey' => sesskey()])));

        $options .= html_writer::end_tag('li');
    }
    $options .= html_writer::end_tag('ul');
    $options .= html_writer::end_tag('div');
    $options .= html_writer::end_tag('div');
}
$options .= html_writer::end_tag('div');

// SMTP Settings
if ($candosmpt) {
    $options .= html_writer::start_tag('div', ['class' => 'row']);
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-3']);
    $options .= html_writer::tag('h4', get_string('categoryemail', 'admin'));
    $options .= html_writer::end_tag('div');
    $options .= html_writer::start_tag('div', ['class' => 'col-sm-9']);
    $options .= html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    $options .= html_writer::start_tag('li');
    $options .= html_writer::tag(
        'a',
        get_string('outgoingmailconfig', 'admin'),
        [
            'href' => new moodle_url(
                '/blocks/iomad_company_admin/company_advanced_settings.php',
                [
                    'action' => 'companysmtpsettings',
                    'sesskey' => sesskey(),
                ]
            ),
        ]);

    $options .= html_writer::end_tag('li');
    $options .= html_writer::end_tag('ul');
    $options .= html_writer::end_tag('div');
    $options .= html_writer::end_tag('div');
}
$options .= html_writer::end_tag('div');

// Display the page.
echo $output->header();

if (!empty($mform)) {
    // Display the form.
    $mform->display();
} else {
    echo $options;
}

echo $output->footer();
