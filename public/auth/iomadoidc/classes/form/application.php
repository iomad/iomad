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

use moodleform;

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
        if (
            isset($this->_customdata['iomadoidcconfig']->idptype) &&
            $this->_customdata['iomadoidcconfig']->idptype == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM
        ) {
            $authmethodoptions[AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE] = get_string('auth_method_certificate', 'auth_iomadoidc');
        }
        $mform->addElement('select', 'clientauthmethod', auth_iomadoidc_config_name_in_form('clientauthmethod'), $authmethodoptions);
        $mform->setDefault('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_SECRET);
        $mform->addElement('static', 'clientauthmethod_help', '', get_string('clientauthmethod_help', 'auth_iomadoidc'));

        // Secret - Check if there's an existing secret to determine if we should add a "change" checkbox.
        $hasexistingsecret = isset($this->_customdata['iomadoidcconfig']->clientsecret) &&
            !empty($this->_customdata['iomadoidcconfig']->clientsecret);

        if ($hasexistingsecret) {
            $mform->addElement(
                'advcheckbox',
                'changesecret',
                get_string('change_client_secret', 'auth_iomadoidc'),
                get_string('change_client_secret_desc', 'auth_iomadoidc')
            );
            $mform->setType('changesecret', PARAM_BOOL);
            $mform->disabledIf('changesecret', 'clientauthmethod', 'eq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);

            // Store the original masked value in a hidden field AND as a data attribute for JavaScript to use.
            $maskedsecret = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientsecret);
            $mform->addElement('hidden', 'originalsecretmasked', $maskedsecret);
            $mform->setType('originalsecretmasked', PARAM_TEXT);
        }

        $attributes = ['size' => 60, 'autocomplete' => 'off', 'class' => 'secret-field'];
        if ($hasexistingsecret) {
            $maskedsecret = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientsecret);
            $attributes['data-original-masked'] = $maskedsecret;
        }
        $mform->addElement('text', 'clientsecret', auth_iomadoidc_config_name_in_form('clientsecret'), $attributes);
        $mform->setType('clientsecret', PARAM_TEXT);
        $mform->disabledIf('clientsecret', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_SECRET);

        if ($hasexistingsecret) {
            $mform->disabledIf('clientsecret', 'changesecret', 'notchecked');
        }

        $mform->addElement('static', 'clientsecret_help', '', get_string('clientsecret_help', 'auth_iomadoidc'));

        // Certificate source.
        $mform->addElement('select', 'clientcertsource', auth_iomadoidc_config_name_in_form('clientcertsource'), [
            AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT => get_string('cert_source_text', 'auth_iomadoidc'),
            AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE => get_string('cert_source_path', 'auth_iomadoidc'),
        ]);
        $mform->setDefault('clientcertsource', 0);
        $mform->disabledIf('clientcertsource', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->addElement('static', 'clientcertsource_help', '', get_string('clientcertsource_help', 'auth_iomadoidc'));

        // Certificate private key.
        $mform->addElement(
            'textarea',
            'clientprivatekey',
            auth_iomadoidc_config_name_in_form('clientprivatekey'),
            ['rows' => 10, 'cols' => 80, 'class' => 'cert_textarea']
        );
        $mform->setType('clientprivatekey', PARAM_TEXT);
        $mform->disabledIf('clientprivatekey', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);
        $mform->disabledIf('clientprivatekey', 'clientcertsource', 'neq', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT);
        $mform->addElement('static', 'clientprivatekey_help', '', get_string('clientprivatekey_help', 'auth_iomadoidc'));

        // Certificate certificate.
        $mform->addElement(
            'textarea',
            'clientcert',
            auth_iomadoidc_config_name_in_form('clientcert'),
            ['rows' => 10, 'cols' => 80, 'class' => 'cert_textarea']
        );
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

        // Certificate file passphrase - Check if there's an existing passphrase.
        $hasexistingpassphrase = isset($this->_customdata['iomadoidcconfig']->clientcertpassphrase) &&
            !empty($this->_customdata['iomadoidcconfig']->clientcertpassphrase);

        if ($hasexistingpassphrase) {
            $mform->addElement(
                'advcheckbox',
                'changecertpassphrase',
                get_string('change_cert_passphrase', 'auth_iomadoidc'),
                get_string('change_cert_passphrase_desc', 'auth_iomadoidc')
            );
            $mform->setType('changecertpassphrase', PARAM_BOOL);

            // Store the original masked value in a hidden field AND as a data attribute for JavaScript to use.
            $maskedpassphrase = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientcertpassphrase);
            $mform->addElement('hidden', 'originalpassphrasemasked', $maskedpassphrase);
            $mform->setType('originalpassphrasemasked', PARAM_TEXT);
        }

        $attributes = ['size' => 60, 'autocomplete' => 'off', 'class' => 'secret-field'];
        if ($hasexistingpassphrase) {
            $maskedpassphrase = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientcertpassphrase);
            $attributes['data-original-masked'] = $maskedpassphrase;
        }
        $mform->addElement('text', 'clientcertpassphrase', auth_iomadoidc_config_name_in_form('clientcertpassphrase'), $attributes);
        $mform->setType('clientcertpassphrase', PARAM_TEXT);
        $mform->disabledIf('clientcertpassphrase', 'clientauthmethod', 'neq', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE);

        if ($hasexistingpassphrase) {
            $mform->disabledIf('clientcertpassphrase', 'changecertpassphrase', 'notchecked');
        }

        $mform->addElement('static', 'clientcertpassphrase_help', '', get_string('clientcertpassphrase_help', 'auth_iomadoidc'));

        // Endpoints header.
        $mform->addElement('header', 'endpoints', get_string('settings_section_endpoints', 'auth_iomadoidc'));
        $mform->setExpanded('endpoints');

        // Authorization endpoint.
        $mform->addElement('text', 'authendpoint', auth_iomadoidc_config_name_in_form('authendpoint'), ['size' => 60]);
        $mform->setType('authendpoint', PARAM_URL);
        $mform->setDefault('authendpoint', 'https://login.microsoftonline.com/organizations/oauth2/authorize');
        $mform->addElement('static', 'authendpoint_help', '', get_string('authendpoint_help', 'auth_iomadoidc'));
        $mform->addRule('authendpoint', null, 'required', null, 'client');

        // Token endpoint.
        $mform->addElement('text', 'tokenendpoint', auth_iomadoidc_config_name_in_form('tokenendpoint'), ['size' => 60]);
        $mform->setType('tokenendpoint', PARAM_URL);
        $mform->setDefault('tokenendpoint', 'https://login.microsoftonline.com/organizations/oauth2/token');
        $mform->addElement('static', 'tokenendpoint_help', '', get_string('tokenendpoint_help', 'auth_iomadoidc'));
        $mform->addRule('tokenendpoint', null, 'required', null, 'client');

        // Other parameters header.
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

        // Custom Claim.
        $mform->addElement('text', 'customclaims', auth_iomadoidc_config_name_in_form('customclaims'), ['size' => 120]);
        $mform->setType('customclaims', PARAM_TEXT);
        $mform->setDefault('customclaims', '');
        $mform->addElement('static', 'customclaims_help', '', get_string('customclaims_help', 'auth_iomadoidc'));
        // Secret expiry notifications recipients.
        if (auth_iomadoidc_is_local_365_installed()) {
            $mform->addElement(
                'header',
                'secretexpirynotification',
                get_string('settings_section_secret_expiry_notification', 'auth_iomadoidc')
            );
            $mform->setExpanded('secretexpirynotification');

            $mform->addElement(
                'text',
                'secretexpiryrecipients',
                auth_iomadoidc_config_name_in_form('secretexpiryrecipients'),
                ['size' => 256]
            );
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
     * @param array $data Submitted data for validation.
     * @param array $files Uploaded files for validation.
     * @return array An array of validation errors, if any.
     */
    public function validation($data, $files) {
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
                // Check if user is attempting to change the secret.
                $changesecret = isset($data['changesecret']) ? $data['changesecret'] : true;
                $existingsecret = get_config('auth_iomadoidc', 'clientsecret');

                if ($changesecret) {
                    // User wants to change the secret, validate the new value.
                    if (empty(trim($data['clientsecret']))) {
                        $errors['clientsecret'] = get_string('error_empty_client_secret', 'auth_iomadoidc');
                    } else if (auth_iomadoidc_is_masked_secret($data['clientsecret'])) {
                        // User checked "change secret" but didn't enter a new value.
                        $errors['clientsecret'] = get_string('error_masked_secret_not_changed', 'auth_iomadoidc');
                    }
                } else if (empty($existingsecret) && empty(trim($data['clientsecret']))) {
                    // No existing secret and field is empty - this is invalid.
                    // This handles edge cases where checkbox logic fails.
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

                // Validate certificate passphrase if user is attempting to change it.
                $changecertpassphrase = isset($data['changecertpassphrase']) ? $data['changecertpassphrase'] : true;

                if ($changecertpassphrase && !empty($data['clientcertpassphrase'])) {
                    if (auth_iomadoidc_is_masked_secret($data['clientcertpassphrase'])) {
                        // User checked "change passphrase" but didn't enter a new value.
                        $errors['clientcertpassphrase'] = get_string('error_masked_secret_not_changed', 'auth_iomadoidc');
                    }
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
            if (
                $data['idptype'] == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM &&
                $data['clientauthmethod'] == AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
            ) {
                if (
                    strpos($data['authendpoint'], '/common/') !== false ||
                    strpos($data['authendpoint'], '/organizations/') !== false ||
                    strpos($data['authendpoint'], '/consumers/') !== false
                ) {
                    $errors['authendpoint'] = get_string('error_tenant_specific_endpoint_required', 'auth_iomadoidc');
                }
                if (
                    strpos($data['tokenendpoint'], '/common/') !== false ||
                    strpos($data['tokenendpoint'], '/organizations/') !== false ||
                    strpos($data['tokenendpoint'], '/consumers/') !== false
                ) {
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

        // Validate custom claims.
        if (!empty($data['customclaims'])) {
            $claims = explode(' ', $data['customclaims']);
            foreach ($claims as $claim) {
                $claim = trim($claim);
                if (!empty($claim) && !preg_match('/^[a-zA-Z0-9_-]+$/', $claim)) {
                    $errors['customclaims'] = get_string('error_invalid_custom_claim', 'auth_iomadoidc');
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Process data after form definition and data loading.
     * This is called after set_data() and after validation errors, allowing us to override submitted values.
     *
     * @return void
     */
    public function definition_after_data() {
        parent::definition_after_data();

        $mform =& $this->_form;

        // Get the current checkbox states using getSubmitValue (works for submitted data).
        $changesecret = $mform->getSubmitValue('changesecret');
        $changecertpassphrase = $mform->getSubmitValue('changecertpassphrase');

        // If the "change secret" checkbox is NOT checked and there's an existing secret,
        // ensure the field shows the masked value (especially important after validation errors).
        if (isset($this->_customdata['iomadoidcconfig']->clientsecret) && !empty($this->_customdata['iomadoidcconfig']->clientsecret)) {
            $currentvalue = $mform->getSubmitValue('clientsecret');

            // If checkbox is not checked and field is empty or doesn't match masked value, restore it.
            if (empty($changesecret) && (empty($currentvalue) || !auth_iomadoidc_is_masked_secret($currentvalue))) {
                $maskedsecret = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientsecret);
                // Force the element to use the masked value.
                $element = $mform->getElement('clientsecret');
                $element->setValue($maskedsecret);

                // Also update the data attribute for JavaScript reliability.
                $element->updateAttributes(['data-original-masked' => $maskedsecret]);
            }
        }

        // Same logic for certificate passphrase.
        if (
            isset($this->_customdata['iomadoidcconfig']->clientcertpassphrase) &&
            !empty($this->_customdata['iomadoidcconfig']->clientcertpassphrase)
        ) {
            $currentvalue = $mform->getSubmitValue('clientcertpassphrase');

            // If checkbox is not checked and field is empty or doesn't match masked value, restore it.
            if (empty($changecertpassphrase) && (empty($currentvalue) || !auth_iomadoidc_is_masked_secret($currentvalue))) {
                $maskedpassphrase = auth_iomadoidc_mask_secret($this->_customdata['iomadoidcconfig']->clientcertpassphrase);
                // Force the element to use the masked value.
                $element = $mform->getElement('clientcertpassphrase');
                $element->setValue($maskedpassphrase);

                // Also update the data attribute for JavaScript reliability.
                $element->updateAttributes(['data-original-masked' => $maskedpassphrase]);
            }
        }
    }
}
