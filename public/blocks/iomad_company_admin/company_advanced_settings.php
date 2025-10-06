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
$companycontext = \core\context\company::instance($companyid);
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

// Check our capabilities.
$candoiomadoidc = iomad::has_capability('block/iomad_company_admin:configiomadoidc', $companycontext) ? true : false;
$candoiomadsaml2 = iomad::has_capability('block/iomad_company_admin:configiomadsaml2', $companycontext) ? true : false;
$candoiomadoidcsync = iomad::has_capability('block/iomad_company_admin:configiomadoidcsync', $companycontext) ? true : false;
$candopolicies = iomad::has_capability('block/iomad_company_admin:configpolicies', $companycontext) ? true : false;
$candomfa = iomad::has_capability('block/iomad_company_admin:configmfa', $companycontext) ? true : false;
$candomfa = false;

// Check if all of the modules are installed.
$authmodules = \core_plugin_manager::instance()->get_plugins_of_type('auth');
$localmodules = \core_plugin_manager::instance()->get_plugins_of_type('local');
$toolmodules = \core_plugin_manager::instance()->get_plugins_of_type('tool');

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

// Are we showing a form?
$mform = null;
if (!empty($action) &&
    confirm_sesskey()) {
    if ($candoiomadoidc &&
        $action == 'iomadoidcbasic') {
        $mform = new \block_iomad_company_admin\forms\company_iomadoidc_form($PAGE->url);
        // Set the form data
        $companyiomadoidcdata = get_config('auth_iomadoidc');
        $companyiomadoidcdata->action = $action;
        $customiconid = file_get_submitted_draft_itemid('customicon');
        file_prepare_draft_area($customiconid,
                                $systemcontext->id,
                                'auth_iomadoidc',
                                'customicon', $companyid,
                                ['maxfiles' => 1]);
        $companyiomadoidcdata->customicon = $customiconid;

        $mform->set_data($companyiomadoidcdata);
    } else if ($candoiomadoidc &&
        $action == 'iomadoidcmappings') {
        $companyiomadoidcdata = get_config('auth_iomadoidc');
        $companyiomadoidcdata->action = $action;
        $mform = new \block_iomad_company_admin\forms\company_iomadoidc_mappings_form($PAGE->url);
        // Set the form data
        $mform->set_data($companyiomadoidcdata);
    } else if ($candoiomadsaml2 &&
               $action == 'iomadsaml') {
        $companyiomadsaml2data = get_config('auth_iomadsaml2');
        $companyiomadsaml2data->action = $action;
        $mform = new \block_iomad_company_admin\forms\company_iomadsaml2_form($PAGE->url);
        // Set the form data
        $mform->set_data($companyiomadsaml2data);
    } else if ($candoiomadsaml2 &&
               $action == 'iomadsamlmappings') {
        $companyiomadsaml2data = get_config('auth_iomadsaml2');
        $companyiomadsaml2data->action = $action;
        $mform = new \block_iomad_company_admin\forms\company_iomadsaml2_mappings_form($PAGE->url);
        // Set the form data
        $mform->set_data($companyiomadsaml2data);
    } else if ($candomfa &&
               $action == 'iomadmfasettings') {
        //$companyiomadsaml2data = get_config('auth_iomadsaml2');
        //$companyiomadsaml2data->action = $action;
        $mform = new \block_iomad_company_admin\forms\company_mfa_form($PAGE->url);
        // Set the form data
        //$mform->set_data($companyiomadsaml2data);
    }
    if (!empty($mform) &&
        $mform->is_cancelled()) {
            redirect($linkurl);
        die;
    } else if (!empty($mform) &&
               $data = $mform->get_data()) {
        if ($action == 'iomadoidcbasic') {
            // Process the changes for auth_iomadoidc.
            $companyiomadoidcdata = get_config('auth_iomadoidc');
            unset($data->action);
            unset($data->submitbutton);

            foreach ($data as $id => $value) {
                if ($id == "customicon") {
                    $fs = get_file_storage();
                    if (!empty($value)) {
                        $field = $id . $postfix;
                        file_save_draft_area_files($value,
                                                   $systemcontext->id,
                                                   'auth_iomadoidc',
                                                   'customicon', $companyid,
                                                   ['maxfiles' => 1]);

                        // Set the plugin config so it can actually be picked up.
                        if ($files = $fs->get_area_files($systemcontext->id, 'auth_iomadoidc', 'customicon', $companyid)) {
                            foreach ($files as $file) {
                                if ($file->get_filename() != '.') {
                                    break;
                                }
                            }
                            set_config($field, $file->get_filepath() . $file->get_filename(), 'auth_iomadoidc');
                            auth_iomadoidc_initialize_customicon($file->get_filename());
                        } else {
                            set_config($field, "", "auth_iomadoidc");
                        }
                    }
                } else {
                    set_config($id, $value, 'auth_iomadoidc');
                }
            }
            $redirectmessage = get_string('companysavedok' , 'block_iomad_company_admin');
        } else if ($action == 'iomadoidcmappings') {
            // Process the changes for auth_iomadoidc.
            unset($data->action);
            unset($data->submitbutton);
            foreach ($data as $id => $value) {
                set_config($id, $value, 'auth_iomadoidc');
            }
            $redirectmessage = get_string('companysavedok' , 'block_iomad_company_admin');
        } else if ($action == 'iomadsamlmappings') {
            // Process the changes for auth_iomadsaml2.
            unset($data->action);
            unset($data->submitbutton);
            foreach ($data as $id => $value) {
                set_config($id, $value, 'auth_iomasaml2');
            }
            $redirectmessage = get_string('companysavedok' , 'block_iomad_company_admin');
        } else if ($action == 'iomadsaml') {
            // Process the changes for auth_iomadsaml2.
            unset($data->action);
            unset($data->submitbutton);
            $idpmetadata = 'idpmetadata'. $postfix;
            unset($data->idpmetadata);
            // We need the auth plugin definition.
            $iomadsaml2auth = new \auth_iomadsaml2\auth();
            // We also need the current config.
            $iomadsaml2config = get_config('auth_iomadsaml2');
            foreach ($data as $id => $value) {
                if ($id == "nameidpolicy" . $postfix ||
                    $id == "spmetadatasign" . $postfix ||
                    $id == "spentityid" > $postfix ||
                    $id == "wantassertionssigned" . $postfix ||
                    $id == "assertionconsumerservices" . $postfix) {
                    if ($iomadsaml2config->$id != $value) {
                        auth_iomadsaml2_update_sp_metadata();
                    }
                }
                if ($id == 'assertionsconsumerservices' . $postfix) {
                    $value = implode(',', $value);
                }
                set_config($id, $value, 'auth_iomadsaml2');
            }
            $redirectmessage = get_string('companysavedok' , 'block_iomad_company_admin');
        }
        // Set redirect success.
        redirect($linkurl, $redirectmessage, null, \core\output\notification::NOTIFY_SUCCESS);
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

// Display the page.
echo $output->header();

if (!empty($mform)) {
    // Display the form.
    $mform->display();
} else {
    echo $options;
}

echo $output->footer();
