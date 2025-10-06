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
 * Authentication and endpoints configuration form.
 *
 * @package auth_iomadoidc
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2022 onwards Microsoft, Inc. (http://microsoft.com/)
 */

namespace auth_iomadoidc\form;

use auth_iomadoidc\utils;
use html_writer;
use moodleform;
use tool_brickfield\local\areas\mod_choice\option;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/iomadoidc/lib.php');

/**
 * Class authentication_and_endpoints represents the form on the authentication and endpoints configuration page.
 */
class application extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        $mform =& $this->_form;

        // Basic settings header.
        $mform->addElement('header', 'basic', get_string('settings_section_basic', 'auth_iomadoidc'));

        // IdP type.
        $idptypeoptions = [
            AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID => get_string('idp_type_microsoft_entra_id', 'auth_iomadoidc'),
            AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM => get_string('idp_type_microsoft_identity_platform', 'auth_iomadoidc'),
            AUTH_IOMADOIDC_IDP_TYPE_OTHER => get_string('idp_type_other', 'auth_iomadoidc'),
        ];
        $mform->addElement('select', 'idptype', auth_iomadoidc_config_name_in_form('idptype'), $idptypeoptions);
        $mform->addElement('static', 'idptype_help', '', get_string('idptype_help', 'auth_iomadoidc'));

        // Client ID.
        $mform->addElement('text', 'clientid', auth_iomadoidc_config_name_in_form('clientid'), ['size' => 40]);
        $mform->setType('clientid', PARAM_TEXT);
        $mform->addElement('static', 'clientid_help', '', get_string('clientid_help', 'auth_iomadoidc'));
        $mform->addRule('clientid', null, 'required', null, 'client');

        // Authentication header.
        $mform->addElement('header', 'authentication', get_string('settings_section_authentication', 'auth_iomadoidc'));
        $mform->setExpanded('authentication');

        // Authentication method depending on IdP type.
        $authmethodoptions = [
            AUTH_IOMADOIDC_AUTH_METHOD_SECRET => get_string('auth_method_secret', 'auth_iomadoidc'),
        ];
        if (isset($this->_customdata['iomadoidcconfig']->idptype) &&
            $this->_customdata['iomadoidcconfig']->idptype == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM) {
            $authmethodoptions[AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE] = get_string('auth_method_certificate', 'auth_iomadoidc');
        }
        $mform->addElement('select', 'clientauthmethod', auth_iomadoidc_config_name_in_form('clientauthmethod'), $authmethodoptions);
        $mform->setDefault('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_SECRET);
        $mform->addElement('static', 'clientauthmethod_help', '', get_string('clientauthmethod_help', 'auth_iomadoidc'));

        // Secret.
        $mform->addElement('text', 'clientsecret', auth_iomadoidc_config_name_in_form('clientsecret'), ['size' => 60]);
        $mform->setType('clientsecret', PARAM_TEXT);
        $mform->disabledIf('clientsecret', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_SECRET);
        $mform->addElement('static', 'clientsecret_help', '', get_string('clientsecret_help', 'auth_iomadoidc'));

        // Certificate source.
        $mform->addElement('select', 'clientcertsource', auth_iomadoidc_config_name_in_form('clientcertsource'), [
            AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT => get_string('cert_source_text', 'auth_iomadoidc'),
            AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE => get_string('cert_source_path', 'auth_iomadoidc')
        ]);
        $mform->setDefault('clientcertsource', 0);
        $mform->disabledIf('clientcertsource', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->addElement('static', 'clientcertsource_help', '', get_string('clientcertsource_help', 'auth_iomadoidc'));

        // Certificate private key.
        $mform->addElement('textarea', 'clientprivatekey', auth_iomadoidc_config_name_in_form('clientprivatekey'),
            ['rows' => 10, 'cols' => 80, 'class' => 'cert_textarea']);
        $mform->setType('clientprivatekey', PARAM_TEXT);
        $mform->disabledIf('clientprivatekey', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->disabledIf('clientprivatekey', 'clientcertsource', 'neq', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT);
        $mform->addElement('static', 'clientprivatekey_help', '', get_string('clientprivatekey_help', 'auth_iomadoidc'));

        // Certificate certificate.
        $mform->addElement('textarea', 'clientcert', auth_iomadoidc_config_name_in_form('clientcert'),
            ['rows' => 10, 'cols' => 80, 'class' => 'cert_textarea']);
        $mform->setType('clientcert', PARAM_TEXT);
        $mform->disabledIf('clientcert', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->disabledIf('clientcert', 'clientcertsource', 'neq', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT);
        $mform->addElement('static', 'clientcert_help', '', get_string('clientcert_help', 'auth_iomadoidc'));

        // Certificate file of private key.
        $mform->addElement('text', 'clientprivatekeyfile', auth_iomadoidc_config_name_in_form('clientprivatekeyfile'), ['size' => 60]);
        $mform->setType('clientprivatekeyfile', PARAM_FILE);
        $mform->disabledIf('clientprivatekeyfile', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->disabledIf('clientprivatekeyfile', 'clientcertsource', 'neq', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE);
        $mform->addElement('static', 'clientprivatekeyfile_help', '', get_string('clientprivatekeyfile_help', 'auth_iomadoidc'));

        // Certificate file of certificate or public key.
        $mform->addElement('text', 'clientcertfile', auth_iomadoidc_config_name_in_form('clientcertfile'), ['size' => 60]);
        $mform->setType('clientcertfile', PARAM_FILE);
        $mform->disabledIf('clientcertfile', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->disabledIf('clientcertfile', 'clientcertsource', 'neq', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE);
        $mform->addElement('static', 'clientcertfile_help', '', get_string('clientcertfile_help', 'auth_iomadoidc'));

        // Certificate file passphrase.
        $mform->addElement('text', 'clientcertpassphrase', auth_iomadoidc_config_name_in_form('clientcertpassphrase'), ['size' => 60]);
        $mform->setType('clientcertpassphrase', PARAM_TEXT);
        $mform->disabledIf('clientcertpassphrase', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->addElement('static', 'clientcertpassphrase_help', '', get_string('clientcertpassphrase_help', 'auth_iomadoidc'));

        // Endpoints header.
        $mform->addElement('header', 'endpoints', get_string('settings_section_endpoints', 'auth_iomadoidc'));
        $mform->setExpanded('endpoints');

        // Authorization endpoint.
        $mform->addElement('text', 'authendpoint', auth_iomadoidc_config_name_in_form('authendpoint'), ['size' => 60]);
        $mform->setType('authendpoint', PARAM_URL);
        $mform->setDefault('authendpoint', 'https://login.microsoftonline.com/common/oauth2/authorize');
        $mform->addElement('static', 'authendpoint_help', '', get_string('authendpoint_help', 'auth_iomadoidc'));
        $mform->addRule('authendpoint', null, 'required', null, 'client');

        // Token endpoint.
        $mform->addElement('text', 'tokenendpoint', auth_iomadoidc_config_name_in_form('tokenendpoint'), ['size' => 60]);
        $mform->setType('tokenendpoint', PARAM_URL);
        $mform->setDefault('tokenendpoint', 'https://login.microsoftonline.com/common/oauth2/token');
        $mform->addElement('static', 'tokenendpoint_help', '', get_string('tokenendpoint_help', 'auth_iomadoidc'));
        $mform->addRule('tokenendpoint', null, 'required', null, 'client');

        // "Other parameters" header.
        $mform->addElement('header', 'otherparams', get_string('settings_section_other_params', 'auth_iomadoidc'));
        $mform->setExpanded('otherparams');

        // Resource.
        $mform->addElement('text', 'iomadoidcresource', auth_iomadoidc_config_name_in_form('iomadoidcresource'), ['size' => 60]);
        $mform->setType('iomadoidcresource', PARAM_TEXT);
        $mform->setDefault('iomadoidcresource', 'https://graph.microsoft.com');
        $mform->addElement('static', 'iomadoidcresource_help', '', get_string('iomadoidcresource_help', 'auth_iomadoidc'));

        // Scope.
        $mform->addElement('text', 'iomadoidcscope', auth_iomadoidc_config_name_in_form('iomadoidcscope'), ['size' => 60]);
        $mform->setType('iomadoidcscope', PARAM_TEXT);
        $mform->setDefault('iomadoidcscope', 'openid profile email');
        $mform->addElement('static', 'iomadoidcscope_help', '', get_string('iomadoidcscope_help', 'auth_iomadoidc'));

        // Secret expiry notifications recipients.
        if (auth_iomadoidc_is_local_365_installed()) {
            $mform->addElement('header', 'secretexpirynotification',
                get_string('settings_section_secret_expiry_notification', 'auth_iomadoidc'));
            $mform->setExpanded('secretexpirynotification');

            $mform->addElement('text', 'secretexpiryrecipients', auth_iomadoidc_config_name_in_form('secretexpiryrecipients'),
                ['size' => 256]);
            $mform->setType('secretexpiryrecipients', PARAM_TEXT);
            $mform->disabledIf('secretexpiryrecipients', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_SECRET);
            $mform->disabledIf('secretexpiryrecipients', 'idptype', 'eq', AUTH_IOMADOIDC_IDP_TYPE_OTHER);

            $mform->addElement('static', 'secretexpiryrecipients_help', '', get_string('secretexpiryrecipients_help', 'auth_iomadoidc'));
        }

        $mform->addElement('hidden', 'companyonly');
        $mform->setType('companyonly', PARAM_BOOL);

        // Save buttons.
        $this->add_action_buttons();
    }

    /**
     * Additional validate rules.
     *
     * @param $data
     * @param $files
     * @return array
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!isset($data['clientauthmethod'])) {
            $data['clientauthmethod'] = $this->optional_param('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_SECRET, PARAM_INT);
        }

        // Validate "clientauthmethod" according to "idptype".
        switch ($data['idptype']) {
            case AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID:
            case AUTH_IOMADOIDC_IDP_TYPE_OTHER:
                if ($data['clientauthmethod'] != AUTH_IOMADOIDC_AUTH_METHOD_SECRET) {
                    $errors['clientauthmethod'] = get_string('error_invalid_client_authentication_method', 'auth_iomadoidc');
                }
                break;
            case AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM:
                if (!in_array($data['clientauthmethod'], [AUTH_IOMADOIDC_AUTH_METHOD_SECRET, AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE])) {
                    $errors['clientauthmethod'] = get_string('error_invalid_client_authentication_method', 'auth_iomadoidc');
                }
                break;
        }

        // Validate authentication variables.
        switch ($data['clientauthmethod']) {
            case AUTH_IOMADOIDC_AUTH_METHOD_SECRET:
                if (empty(trim($data['clientsecret']))) {
                    $errors['clientsecret'] = get_string('error_empty_client_secret', 'auth_iomadoidc');
                }
                break;
            case AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE:
                switch ($data['clientcertsource']) {
                    case AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT:
                        if (empty(trim($data['clientprivatekey']))) {
                            $errors['clientprivatekey'] = get_string('error_empty_client_private_key', 'auth_iomadoidc');
                        }
                        if (empty(trim($data['clientcert']))) {
                            $errors['clientcert'] = get_string('error_empty_client_cert', 'auth_iomadoidc');
                        }
                        break;
                    case AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE:
                        if (empty(trim($data['clientprivatekeyfile']))) {
                            $errors['clientprivatekeyfile'] = get_string('error_empty_client_private_key_file', 'auth_iomadoidc');
                        }
                        if (empty(trim($data['clientcertfile']))) {
                            $errors['clientcertfile'] = get_string('error_empty_client_cert_file', 'auth_iomadoidc');
                        }
                        break;
                }
                break;
        }

        // Validate endpoints.
        if (in_array($data['idptype'], [AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID, AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM])) {
            // Ensure authendpoint version matches IdP type.
            $authendpointidptype = auth_iomadoidc_determine_endpoint_version($data['authendpoint']);
            if ($authendpointidptype != $data['idptype']) {
                $errors['authendpoint'] = get_string('error_endpoint_mismatch_auth_endpoint', 'auth_iomadoidc');
            }

            // Ensure tokenendpoint version matches IdP type.
            $tokenendpointtype = auth_iomadoidc_determine_endpoint_version($data['tokenendpoint']);
            if ($tokenendpointtype != $data['idptype']) {
                $errors['tokenendpoint'] = get_string('error_endpoint_mismatch_token_endpoint', 'auth_iomadoidc');
            }

            // If "certificate" authentication method is used, ensure tenant specific endpoints are used.
            if ($data['idptype'] == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM &&
                $data['clientauthmethod'] == AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE) {
                if (strpos($data['authendpoint'], '/common/') !== false ||
                    strpos($data['authendpoint'], '/organizations/') !== false ||
                    strpos($data['authendpoint'], '/consumers/') !== false) {
                    $errors['authendpoint'] = get_string('error_tenant_specific_endpoint_required', 'auth_iomadoidc');
                }
                if (strpos($data['tokenendpoint'], '/common/') !== false ||
                    strpos($data['tokenendpoint'], '/organizations/') !== false ||
                    strpos($data['tokenendpoint'], '/consumers/') !== false) {
                    $errors['tokenendpoint'] = get_string('error_tenant_specific_endpoint_required', 'auth_iomadoidc');
                }
            }
        }

        // Validate iomadoidcresource.
        if (in_array($data['idptype'], [AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID, AUTH_IOMADOIDC_IDP_TYPE_OTHER])) {
            if (empty(trim($data['iomadoidcresource']))) {
                $errors['iomadoidcresource'] = get_string('error_empty_iomadoidcresource', 'auth_iomadoidc');
            }
        }

        return $errors;
    }
}
