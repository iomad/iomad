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
 * IOMADOIDC application configuration page.
 *
 * @package auth_iomadoidc
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2022 onwards Microsoft, Inc. (http://microsoft.com/)
 */

use auth_iomadoidc\form\application;
use core\context\system;
use core\url;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/auth/iomadoidc/lib.php');

// IOMAD.
$companyonly = optional_param('companyonly', false, PARAM_BOOL);

require_login();

$url = new url('/auth/iomadoidc/manageapplication.php');
$PAGE->set_url($url);
$PAGE->set_context(system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('settings_page_application', 'auth_iomadoidc'));
$PAGE->set_title(get_string('settings_page_application', 'auth_iomadoidc'));

$jsparams = [AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM, AUTH_IOMADOIDC_AUTH_METHOD_SECRET, AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE,
    get_string('auth_method_certificate', 'auth_iomadoidc')];
$jsmodule = [
    'name' => 'auth_iomadoidc',
    'fullpath' => '/auth/iomadoidc/js/module.js',
];
$PAGE->requires->js_init_call('M.auth_iomadoidc.init', $jsparams, true, $jsmodule);

// IOMAD.
$postfix = iomad::get_company_postfix();
$companyid = iomad::get_my_companyid(context_system::instance(), false);

// Is this from the company advanced settings page?
if ($companyonly && !empty($companyid)) {
    $companycontext = context_company::instance($companyid);
    //iomad::require_capability('block/iomad_company_admin:configiomadoidc', $companycontext);
    $PAGE->set_pagelayout('base');
    $returnurl = new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php');
} else {
    admin_externalpage_setup('auth_iomadoidc_application');
    require_admin();
    $returnurl = $url;
}

$iomadoidcconfig = get_config('auth_iomadoidc');

$form = new application(null, ['iomadoidcconfig' => $iomadoidcconfig]);

$formdata = ['companyonly' => $companyonly];
$secretfields = ['clientsecret' . $postfix, 'clientcertpassphrase' . $postfix];

// Check if form was submitted (to handle validation errors).
$formsubmitted = optional_param('submitbutton', '', PARAM_TEXT);

foreach (
    [
        'idptype', 'clientid', 'clientauthmethod', 'clientsecret', 'clientprivatekey', 'clientcert',
        'clientcertsource', 'clientprivatekeyfile', 'clientcertfile', 'clientcertpassphrase',
        'authendpoint', 'tokenendpoint', 'iomadoidcresource', 'iomadoidcscope', 'secretexpiryrecipients',
        'bindingusernameclaim', 'customclaimname', 'customclaims',
    ] as $field
) {
    $fieldname = $field . $postfix;
    if (isset($iomadoidcconfig->$fieldname)) {
        // Mask sensitive secret fields for display only.
        if (in_array($fieldname, $secretfields) && !empty($iomadoidcconfig->$fieldname)) {
            $formdata[$fieldname] = auth_iomadoidc_mask_secret($iomadoidcconfig->$fieldname);
        } else {
            $formdata[$fieldname] = $iomadoidcconfig->$fieldname;
        }
    }
}

// After form validation errors, if the change checkbox wasn't checked, restore the masked value.
if ($formsubmitted) {
    $changesecret = optional_param('changesecret', 0, PARAM_BOOL);
    $changecertpassphrase = optional_param('changecertpassphrase', 0, PARAM_BOOL);

    if (!$changesecret && !empty($iomadoidcconfig->clientsecret)) {
        $formdata['clientsecret'] = auth_iomadoidc_mask_secret($iomadoidcconfig->clientsecret);
    }
    if (!$changecertpassphrase && !empty($iomadoidcconfig->clientcertpassphrase)) {
        $formdata['clientcertpassphrase'] = auth_iomadoidc_mask_secret($iomadoidcconfig->clientcertpassphrase);
    }
}

$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $form->get_data()) {
    // Handle odd cases where clientauthmethod is not received.
    if (!isset($fromform->clientauthmethod)) {
        $fromform->clientauthmethod = optional_param('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_SECRET, PARAM_INT);
    }

    // Prepare config settings to save.
    $configstosave = ['idptype', 'clientid', 'clientauthmethod', 'authendpoint', 'tokenendpoint',
        'iomadoidcresource', 'iomadoidcscope', 'customclaims'];

    // Depending on the value of clientauthmethod, save clientsecret or (clientprivatekey and clientcert).
    switch ($fromform->clientauthmethod) {
        case AUTH_IOMADOIDC_AUTH_METHOD_SECRET:
            $configstosave[] = 'clientsecret';
            $configstosave[] = 'secretexpiryrecipients';
            break;
        case AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE:
            $configstosave[] = 'clientcertsource';
            $configstosave[] = 'clientcertpassphrase';
            switch ($fromform->clientcertsource) {
                case AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT:
                    $configstosave[] = 'clientprivatekey';
                    $configstosave[] = 'clientcert';
                    break;
                case AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE:
                    $configstosave[] = 'clientprivatekeyfile';
                    $configstosave[] = 'clientcertfile';
                    break;
            }
            break;
    }

    // Save config settings.
    $updateapplicationtokenrequired = false;
    $settingschanged = false;
    foreach ($configstosave as $config) {
        $configname = $config . $postfix;
        $existingsetting = get_config('auth_iomadoidc', $configname);
        $newvalue = $fromform->$configname;

        // Handle secret fields specially.
        if (in_array($configname, $secretfields)) {
            // If the value is a masked secret and hasn't changed, skip updating it.
            if (auth_iomadoidc_is_masked_secret($newvalue)) {
                // Check if the masked value matches what we would have displayed.
                if ($existingsetting && auth_iomadoidc_mask_secret($existingsetting) === $newvalue) {
                    // Value hasn't been changed, skip this field.
                    continue;
                }
            }

            // CRITICAL: Prevent saving empty secret when there's an existing one.
            // This handles cases where the field is disabled and submits empty.
            if (empty(trim($newvalue)) && !empty($existingsetting)) {
                // Don't delete existing secret with empty value - skip this field.
                continue;
            }
        }

        if ($newvalue != $existingsetting) {
            // Redact secret fields in the config log to prevent exposing sensitive values.
            if (in_array($configname, $secretfields)) {
                $logoldvalue = !empty($existingsetting) ? '[REDACTED]' : '';
                $lognewvalue = !empty($newvalue) ? '[REDACTED]' : '';
                add_to_config_log($configname, $logoldvalue, $lognewvalue, 'auth_iomadoidc');
            } else {
                add_to_config_log($configname, $existingsetting, $newvalue, 'auth_iomadoidc');
            }
            set_config($configname, $newvalue, 'auth_iomadoidc');
            $settingschanged = true;
            if ($config != 'secretexpiryrecipients') {
                $updateapplicationtokenrequired = true;
            }
        }
    }

    // Redirect destination and message depend on IdP type.
    $isgraphapiconnected = false;
    if ($fromform->idptype != AUTH_IOMADOIDC_IDP_TYPE_OTHER) {
        if (auth_iomadoidc_is_local_365_installed()) {
            $isgraphapiconnected = true;
        }
    }

    if ($updateapplicationtokenrequired) {
        if ($isgraphapiconnected) {
            // First, delete the existing application token and purge cache.
            unset_config('apptokens', 'local_o365');
            unset_config('azuresetupresult', 'local_o365');
            purge_all_caches();

            // Then show the message to the user with instructions to update the application token.
            $localo365configurl = new url('/admin/settings.php', ['section' => 'local_o365']);
            redirect($localo365configurl, get_string('application_updated_microsoft', 'auth_iomadoidc'));
        } else {
            redirect($url, get_string('application_updated', 'auth_iomadoidc'));
        }
    } else if ($settingschanged) {
        redirect($url, get_string('application_updated', 'auth_iomadoidc'));
    } else {
        redirect($url, get_string('application_not_changed', 'auth_iomadoidc'));
    }
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
