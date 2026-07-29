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
 * Plugin settings.
 *
 * @package auth_iomadoidc
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft, Inc. (http://microsoft.com/)
 */

defined('MOODLE_INTERNAL') || die();

use auth_iomadoidc\adminsetting\auth_iomadoidc_admin_setting_endpoint;
use auth_iomadoidc\adminsetting\auth_iomadoidc_admin_setting_iconselect;
use auth_iomadoidc\adminsetting\auth_iomadoidc_admin_setting_loginflow;
use auth_iomadoidc\adminsetting\auth_iomadoidc_admin_setting_redirecturi;
use auth_iomadoidc\utils;
use core\url;
use local_iomad\iomad;

require_once($CFG->dirroot . '/auth/iomadoidc/lib.php');

if ($hassiteconfig) {
    // IOMAD.
    $postfix = iomad::get_company_postfix();

    // Redirect the category overview page to the first settings tab, so that the Bootstrap
    // nav-tabs behave correctly instead of showing all sub-pages' content at once.
    if ($PAGE->has_set_url() && $PAGE->url->get_param('category') === 'iomadoidcfolder') {
        redirect(new \core\url('/admin/settings.php', ['section' => 'auth_iomadoidc_application']));
    }

    // Add folder for IOMADOIDC settings.
    $iomadoidcfolder = new admin_category('iomadoidcfolder', get_string('pluginname', 'auth_iomadoidc'));
    $ADMIN->add('authsettings', $iomadoidcfolder);

    // Application configuration settings page.
    $applicationsettings = new admin_settingpage(
        'auth_iomadoidc_application',
        get_string('settings_page_application', 'auth_iomadoidc')
    );

    // Add navigation tabs.
    $applicationsettings->add(new admin_setting_heading(
        'auth_iomadoidc_application_nav',
        '',
        auth_iomadoidc_get_settings_nav_html('auth_iomadoidc_application')
    ));

    // Link to the guided Application Configuration Wizard.
    $wizardurl = new url('/auth/iomadoidc/manageapplication.php');
    $applicationsettings->add(new admin_setting_description(
        'auth_iomadoidc/application_wizard_link',
        '',
        get_string('settings_application_wizard_desc', 'auth_iomadoidc', $wizardurl->out())
    ));

    // Basic settings heading.
    $applicationsettings->add(new admin_setting_heading(
        'auth_iomadoidc/application_basic_heading',
        get_string('settings_section_basic', 'auth_iomadoidc'),
        ''
    ));

    // Redirect URI.
    $applicationsettings->add(
        new auth_iomadoidc_admin_setting_redirecturi(
            'auth_iomadoidc/redirecturi' . $postfix,
            get_string('cfg_redirecturi_key', 'auth_iomadoidc'),
            get_string('cfg_redirecturi_desc', 'auth_iomadoidc'),
            utils::get_redirecturl()
        )
    );

    // IdP type.
    $idptypeoptions = [
        AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID => get_string('idp_type_microsoft_entra_id', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM => get_string('idp_type_microsoft_identity_platform', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_IDP_TYPE_OTHER => get_string('idp_type_other', 'auth_iomadoidc'),
    ];
    $idptypesetting = new admin_setting_configselect(
        'auth_iomadoidc/idptype' . $postfix,
        get_string('idptype', 'auth_iomadoidc'),
        get_string('idptype_help', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID,
        $idptypeoptions
    );
    $idptypesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $idptypesetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($idptypesetting);

    // Client ID.
    $clientidsetting = new admin_setting_configtext(
        'auth_iomadoidc/clientid' . $postfix,
        get_string('clientid', 'auth_iomadoidc'),
        get_string('clientid_help', 'auth_iomadoidc'),
        '',
        PARAM_TEXT
    );
    $clientidsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $applicationsettings->add($clientidsetting);

    // Authentication heading.
    $applicationsettings->add(new admin_setting_heading(
        'auth_iomadoidc/application_auth_heading',
        get_string('settings_section_authentication', 'auth_iomadoidc'),
        ''
    ));

    // Client authentication method.
    $clientauthmethoptions = [
        AUTH_IOMADOIDC_AUTH_METHOD_SECRET => get_string('auth_method_secret', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE => get_string('auth_method_certificate', 'auth_iomadoidc'),
    ];
    $clientauthmethodsetting = new admin_setting_configselect(
        'auth_iomadoidc/clientauthmethod' . $postfix,
        get_string('clientauthmethod', 'auth_iomadoidc'),
        get_string('clientauthmethod_help', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_AUTH_METHOD_SECRET,
        $clientauthmethoptions
    );
    $clientauthmethodsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientauthmethodsetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientauthmethodsetting);

    // Client secret.
    $clientsecretsetting = new admin_setting_configpasswordunmask(
        'auth_iomadoidc/clientsecret' . $postfix,
        get_string('clientsecret', 'auth_iomadoidc'),
        get_string('clientsecret_help', 'auth_iomadoidc'),
        ''
    );
    $clientsecretsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientsecretsetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientsecretsetting);

    // Certificate source.
    $certsourceoptions = [
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT => get_string('cert_source_text', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE => get_string('cert_source_path', 'auth_iomadoidc'),
    ];
    $clientcertsourcesetting = new admin_setting_configselect(
        'auth_iomadoidc/clientcertsource' . $postfix,
        get_string('clientcertsource', 'auth_iomadoidc'),
        get_string('clientcertsource_help', 'auth_iomadoidc'),
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT,
        $certsourceoptions
    );
    $clientcertsourcesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientcertsourcesetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientcertsourcesetting);

    // Client certificate private key (plain text).
    $clientprivatekeysetting = new admin_setting_configtextarea(
        'auth_iomadoidc/clientprivatekey' . $postfix,
        get_string('clientprivatekey', 'auth_iomadoidc'),
        get_string('clientprivatekey_help', 'auth_iomadoidc'),
        '',
        PARAM_TEXT
    );
    $clientprivatekeysetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientprivatekeysetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientprivatekeysetting);

    // Client certificate public key (plain text).
    $clientcertsetting = new admin_setting_configtextarea(
        'auth_iomadoidc/clientcert' . $postfix,
        get_string('clientcert', 'auth_iomadoidc'),
        get_string('clientcert_help', 'auth_iomadoidc'),
        '',
        PARAM_TEXT
    );
    $clientcertsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientcertsetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientcertsetting);

    // Client certificate private key file name.
    $clientprivatekeyfilesetting = new admin_setting_configtext(
        'auth_iomadoidc/clientprivatekeyfile' . $postfix,
        get_string('clientprivatekeyfile', 'auth_iomadoidc'),
        get_string('clientprivatekeyfile_help', 'auth_iomadoidc'),
        '',
        PARAM_FILE
    );
    $clientprivatekeyfilesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientprivatekeyfilesetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientprivatekeyfilesetting);

    // Client certificate public key file name.
    $clientcertfilesetting = new admin_setting_configtext(
        'auth_iomadoidc/clientcertfile' . $postfix,
        get_string('clientcertfile', 'auth_iomadoidc'),
        get_string('clientcertfile_help', 'auth_iomadoidc'),
        '',
        PARAM_FILE
    );
    $clientcertfilesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientcertfilesetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientcertfilesetting);

    // Client certificate passphrase.
    $clientcertpassphrasesetting = new admin_setting_configpasswordunmask(
        'auth_iomadoidc/clientcertpassphrase' . $postfix,
        get_string('clientcertpassphrase', 'auth_iomadoidc'),
        get_string('clientcertpassphrase_help', 'auth_iomadoidc'),
        ''
    );
    $clientcertpassphrasesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $clientcertpassphrasesetting->set_updatedcallback('auth_iomadoidc_validate_auth_settings');
    $applicationsettings->add($clientcertpassphrasesetting);

    // Endpoints heading.
    $applicationsettings->add(new admin_setting_heading(
        'auth_iomadoidc/application_endpoints_heading',
        get_string('settings_section_endpoints', 'auth_iomadoidc'),
        ''
    ));

    // Authorization endpoint.
    $authendpointsetting = new auth_iomadoidc_admin_setting_endpoint(
        'auth_iomadoidc/authendpoint' . $postfix,
        get_string('authendpoint', 'auth_iomadoidc'),
        get_string('authendpoint_help', 'auth_iomadoidc'),
        'https://login.microsoftonline.com/organizations/oauth2/authorize',
        'auth'
    );
    $authendpointsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $applicationsettings->add($authendpointsetting);

    // Token endpoint.
    $tokenendpointsetting = new auth_iomadoidc_admin_setting_endpoint(
        'auth_iomadoidc/tokenendpoint' . $postfix,
        get_string('tokenendpoint', 'auth_iomadoidc'),
        get_string('tokenendpoint_help', 'auth_iomadoidc'),
        'https://login.microsoftonline.com/organizations/oauth2/token',
        'token'
    );
    $tokenendpointsetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $applicationsettings->add($tokenendpointsetting);

    // Other parameters heading.
    $applicationsettings->add(new admin_setting_heading(
        'auth_iomadoidc/application_otherparams_heading',
        get_string('settings_section_other_params', 'auth_iomadoidc'),
        ''
    ));

    // IOMADOIDC resource.
    $iomadoidcresourcesetting = new admin_setting_configtext(
        'auth_iomadoidc/iomadoidcresource' . $postfix,
        get_string('iomadoidcresource', 'auth_iomadoidc'),
        get_string('iomadoidcresource_help', 'auth_iomadoidc'),
        'https://graph.microsoft.com',
        PARAM_TEXT
    );
    $iomadoidcresourcesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $applicationsettings->add($iomadoidcresourcesetting);

    // IOMADOIDC scope.
    $iomadoidcscopesetting = new admin_setting_configtext(
        'auth_iomadoidc/iomadoidcscope' . $postfix,
        get_string('iomadoidcscope', 'auth_iomadoidc'),
        get_string('iomadoidcscope_help', 'auth_iomadoidc'),
        'openid profile email',
        PARAM_TEXT
    );
    $iomadoidcscopesetting->set_updatedcallback('auth_iomadoidc_reset_app_tokens');
    $applicationsettings->add($iomadoidcscopesetting);

    // Secret expiry notification (only when local_o365 is installed).
    if (auth_iomadoidc_is_local_365_installed()) {
        $applicationsettings->add(new admin_setting_heading(
            'auth_iomadoidc/application_secretexpiry_heading',
            get_string('settings_section_secret_expiry_notification', 'auth_iomadoidc'),
            ''
        ));

        $applicationsettings->add(new admin_setting_configtext(
            'auth_iomadoidc/secretexpiryrecipients' . $postfix,
            get_string('secretexpiryrecipients', 'auth_iomadoidc'),
            get_string('secretexpiryrecipients_help', 'auth_iomadoidc'),
            '',
            PARAM_TEXT
        ));
    }

    // Conditional display: show secret field only when auth method is "secret".
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientsecret' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_SECRET
    );

    // Conditional display: show certificate fields only when auth method is "certificate".
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcertsource' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcertpassphrase' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );

    // Conditional display: certificate text fields only when cert source is "text".
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientprivatekey' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientprivatekey' . $postfix,
        'auth_iomadoidc/clientcertsource' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcert' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcert' . $postfix,
        'auth_iomadoidc/clientcertsource' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT
    );

    // Conditional display: certificate file fields only when cert source is "file".
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientprivatekeyfile' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientprivatekeyfile' . $postfix,
        'auth_iomadoidc/clientcertsource' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcertfile' . $postfix,
        'auth_iomadoidc/clientauthmethod' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE
    );
    $applicationsettings->hide_if(
        'auth_iomadoidc/clientcertfile' . $postfix,
        'auth_iomadoidc/clientcertsource' . $postfix,
        'neq',
        AUTH_IOMADOIDC_AUTH_CERT_SOURCE_FILE
    );

    // Conditional display: secret expiry recipients only for non-OTHER Microsoft IdP using secret auth.
    if (auth_iomadoidc_is_local_365_installed()) {
        $applicationsettings->hide_if(
            'auth_iomadoidc/secretexpiryrecipients' . $postfix,
            'auth_iomadoidc/clientauthmethod' . $postfix,
            'neq',
            AUTH_IOMADOIDC_AUTH_METHOD_SECRET
        );
        $applicationsettings->hide_if(
            'auth_iomadoidc/secretexpiryrecipients' . $postfix,
            'auth_iomadoidc/idptype' . $postfix,
            'eq',
            AUTH_IOMADOIDC_IDP_TYPE_OTHER
        );
    }

    $ADMIN->add('iomadoidcfolder', $applicationsettings);

    $idptype = iomad::get_config('auth_iomadoidc', 'idptype');
    if ($idptype) {
        // Binding username claim settings page.
        $bindingusernamesettings = new admin_settingpage(
            'auth_iomadoidc_binding_username_claim',
            get_string('settings_page_binding_username_claim', 'auth_iomadoidc')
        );

        // Add navigation tabs.
        $bindingusernamesettings->add(new admin_setting_heading(
            'auth_iomadoidc_binding_username_claim_nav',
            '',
            auth_iomadoidc_get_settings_nav_html('auth_iomadoidc_binding_username_claim')
        ));

        // Determine options and description based on IdP type and user sync state.
        switch ($idptype) {
            case AUTH_IOMADOIDC_IDP_TYPE_OTHER:
                $bindingclaimdesc = 'binding_username_claim_help_non_ms';
                $bindingusernameoptions = [
                    'auto' => get_string('binding_username_auto', 'auth_iomadoidc'),
                    'preferred_username' => 'preferred_username',
                    'email' => 'email',
                    'unique_name' => 'unique_name',
                    'sub' => 'sub',
                    'samaccountname' => 'samaccountname',
                    'custom' => get_string('binding_username_custom', 'auth_iomadoidc'),
                ];
                break;
            case AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM:
            case AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID:
                if (auth_iomadoidc_is_local_365_installed() && auth_iomadoidc_is_user_sync_enabled()) {
                    $bindingclaimdesc = 'binding_username_claim_help_ms_with_user_sync';
                    $bindingusernameoptions = [
                        'auto' => get_string('binding_username_auto', 'auth_iomadoidc'),
                        'email' => 'email',
                        'upn' => 'upn',
                        'oid' => 'oid',
                        'samaccountname' => 'samaccountname',
                    ];
                } else {
                    $bindingclaimdesc = 'binding_username_claim_help_ms_no_user_sync';
                    $bindingusernameoptions = [
                        'auto' => get_string('binding_username_auto', 'auth_iomadoidc'),
                        'preferred_username' => 'preferred_username',
                        'email' => 'email',
                        'upn' => 'upn',
                        'unique_name' => 'unique_name',
                        'oid' => 'oid',
                        'sub' => 'sub',
                        'samaccountname' => 'samaccountname',
                        'custom' => get_string('binding_username_custom', 'auth_iomadoidc'),
                    ];
                }
                break;
            default:
                $bindingclaimdesc = 'binding_username_claim_help_ms_no_user_sync';
                $bindingusernameoptions = [
                    'auto' => get_string('binding_username_auto', 'auth_iomadoidc'),
                    'preferred_username' => 'preferred_username',
                    'email' => 'email',
                    'upn' => 'upn',
                    'unique_name' => 'unique_name',
                    'sub' => 'sub',
                    'oid' => 'oid',
                    'samaccountname' => 'samaccountname',
                    'custom' => get_string('binding_username_custom', 'auth_iomadoidc'),
                ];
        }

        $bindingusernamesettings->add(new admin_setting_configselect(
            'auth_iomadoidc/bindingusernameclaim' . $postfix,
            get_string('bindingusernameclaim', 'auth_iomadoidc'),
            get_string($bindingclaimdesc, 'auth_iomadoidc'),
            'auto',
            $bindingusernameoptions
        ));

        // Custom claim name (only when the 'custom' option is available for the current IdP type).
        if (array_key_exists('custom', $bindingusernameoptions)) {
            $bindingusernamesettings->add(
                new admin_setting_configtext(
                    'auth_iomadoidc/customclaimname' . $postfix,
                    get_string('customclaimname', 'auth_iomadoidc'),
                    get_string('customclaimname_description', 'auth_iomadoidc'),
                    '',
                    PARAM_TEXT
                )
            );
            $bindingusernamesettings->hide_if(
                'auth_iomadoidc/customclaimname' . $postfix,
                'auth_iomadoidc/bindingusernameclaim' . $postfix,
                'neq',
                'custom'
            );
        }

        $toolurl = new url('/auth/iomadoidc/change_binding_username_claim_tool.php');
        $bindingusernamesettings->add(new admin_setting_heading(
            'auth_iomadoidc_binding_username_claim_tool_link',
            '',
            get_string('binding_username_claim_tool_link_desc', 'auth_iomadoidc', $toolurl->out())
        ));

        $ADMIN->add('iomadoidcfolder', $bindingusernamesettings);

        // Change binding username claim tool page (bulk migration tool, not a settings page).
        $ADMIN->add(
            'iomadoidcfolder',
            new admin_externalpage(
                'auth_iomadoidc_change_binding_username_claim_tool',
                get_string('settings_page_change_binding_username_claim_tool', 'auth_iomadoidc'),
                new url('/auth/iomadoidc/change_binding_username_claim_tool.php')
            )
        );
    }

    // Other settings page and its settings.
    $settings = new admin_settingpage('auth_iomadoidc_other_settings', get_string('settings_page_other_settings', 'auth_iomadoidc'));

    // Add navigation tabs.
    $settings->add(new admin_setting_heading(
        'auth_iomadoidc_other_settings_nav',
        '',
        auth_iomadoidc_get_settings_nav_html('auth_iomadoidc_other_settings')
    ));

    // Additional options heading.
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/additional_options_heading',
            get_string('heading_additional_options', 'auth_iomadoidc'),
            get_string('heading_additional_options_desc', 'auth_iomadoidc')
        )
    );

    // Force redirect.
    $settings->add(
        new admin_setting_configcheckbox(
            'auth_iomadoidc/forceredirect' . $postfix,
            get_string('cfg_forceredirect_key', 'auth_iomadoidc'),
            get_string('cfg_forceredirect_desc', 'auth_iomadoidc'),
            0
        )
    );

    // Silent login mode.
    $forceloginconfigurl = new url('/admin/settings.php', ['section' => 'sitepolicies']);
    $settings->add(
        new admin_setting_configcheckbox(
            'auth_iomadoidc/silentloginmode' . $postfix,
            get_string('cfg_silentloginmode_key', 'auth_iomadoidc'),
            get_string('cfg_silentloginmode_desc', 'auth_iomadoidc', $forceloginconfigurl->out(false)),
            0
        )
    );

    // Auto-append.
    $settings->add(
        new admin_setting_configtext(
            'auth_iomadoidc/autoappend' . $postfix,
            get_string('cfg_autoappend_key', 'auth_iomadoidc'),
            get_string('cfg_autoappend_desc', 'auth_iomadoidc'),
            '',
            PARAM_TEXT
        )
    );

    // Domain hint.
    $settings->add(
        new admin_setting_configtext(
            'auth_iomadoidc/domainhint' . $postfix,
            get_string('cfg_domainhint_key', 'auth_iomadoidc'),
            get_string('cfg_domainhint_desc', 'auth_iomadoidc'),
            '',
            PARAM_TEXT
        )
    );

    // Login flow.
    $settings->add(
        new auth_iomadoidc_admin_setting_loginflow(
            'auth_iomadoidc/loginflow' . $postfix,
            get_string('cfg_loginflow_key', 'auth_iomadoidc'),
            '',
            'authcode'
        )
    );

    // User restrictions heading.
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/user_restrictions_heading',
            get_string('heading_user_restrictions', 'auth_iomadoidc'),
            get_string('heading_user_restrictions_desc', 'auth_iomadoidc')
        )
    );

    // User restrictions.
    $settings->add(
        new admin_setting_configtextarea(
            'auth_iomadoidc/userrestrictions' . $postfix,
            get_string('cfg_userrestrictions_key', 'auth_iomadoidc'),
            get_string('cfg_userrestrictions_desc', 'auth_iomadoidc'),
            '',
            PARAM_TEXT
        )
    );

    // User restrictions case sensitivity.
    $settings->add(
        new admin_setting_configcheckbox(
            'auth_iomadoidc/userrestrictionscasesensitive' . $postfix,
            get_string('cfg_userrestrictionscasesensitive_key', 'auth_iomadoidc'),
            get_string('cfg_userrestrictionscasesensitive_desc', 'auth_iomadoidc'),
            '1'
        )
    );

    // Sign out integration heading.
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/sign_out_heading',
            get_string('heading_sign_out', 'auth_iomadoidc'),
            get_string('heading_sign_out_desc', 'auth_iomadoidc')
        )
    );

    // Single sign out from Moodle to IdP.
    $settings->add(
        new admin_setting_configcheckbox(
            'auth_iomadoidc/single_sign_off' . $postfix,
            get_string('cfg_signoffintegration_key', 'auth_iomadoidc'),
            get_string('cfg_signoffintegration_desc', 'auth_iomadoidc', $CFG->wwwroot),
            '0'
        )
    );

    // IdP logout endpoint.
    $settings->add(
        new admin_setting_configtext(
            'auth_iomadoidc/logouturi' . $postfix,
            get_string('cfg_logoutendpoint_key', 'auth_iomadoidc'),
            get_string('cfg_logoutendpoint_desc', 'auth_iomadoidc'),
            'https://login.microsoftonline.com/organizations/oauth2/logout',
            PARAM_URL
        )
    );

    $settings->hide_if('auth_iomadoidc/logouturi' . $postfix, 'auth_iomadoidc/single_sign_off' . $postfix, 'notchecked');

    // Front channel logout URL.
    $settings->add(
        new auth_iomadoidc_admin_setting_redirecturi(
            'auth_iomadoidc/logoutendpoint' . $postfix,
            get_string('cfg_frontchannellogouturl_key', 'auth_iomadoidc'),
            get_string('cfg_frontchannellogouturl_desc', 'auth_iomadoidc'),
            utils::get_frontchannellogouturl()
        )
    );

    // Display heading.
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/display_heading' . $postfix,
            get_string('heading_display', 'auth_iomadoidc'),
            get_string('heading_display_desc', 'auth_iomadoidc')
        )
    );

    // Provider Name (opname).
    $settings->add(
        new admin_setting_configtext(
            'auth_iomadoidc/opname' . $postfix,
            get_string('cfg_opname_key', 'auth_iomadoidc'),
            get_string('cfg_opname_desc', 'auth_iomadoidc'),
            get_string('pluginname', 'auth_iomadoidc'),
            PARAM_TEXT
        )
    );

    $settings->add(new admin_setting_configcheckbox(
        'auth_iomadoidc/set_pix' . $postfix,
        get_string('cfg_set_pix_key', 'auth_iomadoidc'),
        get_string('cfg_set_pix_desc', 'auth_iomadoidc'),
        '1'
    ));

    // Icon.
    $icons = [
        [
            'pix' => 'o365',
            'alt' => new lang_string('cfg_iconalt_o365', 'auth_iomadoidc'),
            'component' => 'auth_iomadoidc',
        ],
        [
            'pix' => 't/locked',
            'alt' => new lang_string('cfg_iconalt_locked', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/lock',
            'alt' => new lang_string('cfg_iconalt_lock', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/go',
            'alt' => new lang_string('cfg_iconalt_go', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/stop',
            'alt' => new lang_string('cfg_iconalt_stop', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/user',
            'alt' => new lang_string('cfg_iconalt_user', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'u/user35',
            'alt' => new lang_string('cfg_iconalt_user2', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'i/permissions',
            'alt' => new lang_string('cfg_iconalt_key', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'i/cohort',
            'alt' => new lang_string('cfg_iconalt_group', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'i/groups',
            'alt' => new lang_string('cfg_iconalt_group2', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'i/mnethost',
            'alt' => new lang_string('cfg_iconalt_mnet', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 'i/permissionlock',
            'alt' => new lang_string('cfg_iconalt_userlock', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/more',
            'alt' => new lang_string('cfg_iconalt_plus', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/approve',
            'alt' => new lang_string('cfg_iconalt_check', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
        [
            'pix' => 't/right',
            'alt' => new lang_string('cfg_iconalt_rightarrow', 'auth_iomadoidc'),
            'component' => 'moodle',
        ],
    ];
    $settings->add(
        new auth_iomadoidc_admin_setting_iconselect(
            'auth_iomadoidc/icon' . $postfix,
            get_string('cfg_icon_key', 'auth_iomadoidc'),
            get_string('cfg_icon_desc', 'auth_iomadoidc'),
            'auth_iomadoidc:o365',
            $icons
        )
    );

    // Custom icon.
    $configkey = new lang_string('cfg_customicon_key', 'auth_iomadoidc');
    $configdesc = new lang_string('cfg_customicon_desc', 'auth_iomadoidc');
    $customiconsetting = new admin_setting_configstoredfile(
        'auth_iomadoidc/customicon' . $postfix,
        get_string('cfg_customicon_key', 'auth_iomadoidc'),
        get_string('cfg_customicon_desc', 'auth_iomadoidc'),
        'customicon',
        0,
        ['accepted_types' => ['.png', '.jpg', '.ico'], 'maxbytes' => get_max_upload_file_size()]
    );
    $customiconsetting->set_updatedcallback('auth_iomadoidc_initialize_customicon');
    $settings->add($customiconsetting);

    $settings->hide_if('auth_iomadoidc/icon' . $postfix, 'auth_iomadoidc/set_pix' . $postfix, 'notchecked');
    $settings->hide_if('auth_iomadoidc/customicon' . $postfix, 'auth_iomadoidc/set_pix' . $postfix, 'notchecked');

    // Debugging heading.
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/debugging_heading',
            get_string('heading_debugging', 'auth_iomadoidc'),
            get_string('heading_debugging_desc', 'auth_iomadoidc')
        )
    );

    // Record debugging messages.
    $settings->add(
        new admin_setting_configcheckbox(
            'auth_iomadoidc/debugmode' . $postfix,
            get_string('cfg_debugmode_key', 'auth_iomadoidc'),
            get_string('cfg_debugmode_desc', 'auth_iomadoidc'),
            '0'
        )
    );

    // Tools heading.
    $cleanupurl = new \core\url('/auth/iomadoidc/cleanupiomadoidctokens.php');
    $settings->add(
        new admin_setting_heading(
            'auth_iomadoidc/tools_heading',
            get_string('heading_tools', 'auth_iomadoidc'),
            get_string('cleanup_iomadoidc_tokens_link_desc', 'auth_iomadoidc', $cleanupurl->out())
        )
    );

    $ADMIN->add('iomadoidcfolder', $settings);

    // Cleanup IOMADOIDC tokens page.
    $ADMIN->add(
        'iomadoidcfolder',
        new admin_externalpage(
            'auth_iomadoidc_cleanup_iomadoidc_tokens',
            get_string('settings_page_cleanup_iomadoidc_tokens', 'auth_iomadoidc'),
            new url('/auth/iomadoidc/cleanupiomadoidctokens.php')
        )
    );

    // Other settings page and its settings.
    $fieldmappingspage = new admin_settingpage('auth_iomadoidc_field_mapping', get_string('settings_page_field_mapping', 'auth_iomadoidc'));

    // Add navigation tabs.
    $fieldmappingspage->add(new admin_setting_heading(
        'auth_iomadoidc_field_mapping_nav',
        '',
        auth_iomadoidc_get_settings_nav_html('auth_iomadoidc_field_mapping')
    ));

    $ADMIN->add('iomadoidcfolder', $fieldmappingspage);

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('iomadoidc');
    auth_iomadoidc_display_auth_lock_options(
        $fieldmappingspage,
        $authplugin->authtype,
        $authplugin->userfields,
        get_string('cfg_field_mapping_desc', 'auth_iomadoidc'),
        true,
        false,
        $authplugin->get_custom_user_profile_fields()
    );
}

$settings = null;
