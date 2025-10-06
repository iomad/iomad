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

namespace block_iomad_company_admin\forms;

defined('MOODLE_INTERNAL') || die;

use \iomad;
use \company;
use \moodle_url;
use context_system;
use auth_iomadoidc\utils;
use moodleform;

class company_iomadoidc_form extends moodleform {
    public function definition() {
        global $CFG, $PAGE, $DB, $postfix;

        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('html', "<h2>" . format_string(get_string('pluginname', 'auth_iomadoidc') . " : " .
                                                          get_string('settings', 'moodle')) . "</h2>");

        $mform->addElement('static',
                           'redirecturi' . $postfix,
                           '',
                           get_string('cfg_redirecturi_key', 'auth_iomadoidc'). "&nbsp<b>" . utils::get_redirecturl() . "</b>");
        $mform->addElement('static', 'redirecturidescription', '', get_string('cfg_redirecturi_desc', 'auth_iomadoidc'));

        // Link to authentication options.
        $authenticationconfigurationurl = new moodle_url('/auth/iomadoidc/manageapplication.php', ['companyonly' => true]);
        $mform->addElement('static', 'managelink', '', '<a href="' . $authenticationconfigurationurl->out() .'">' .
                                    get_string('settings_page_application', 'auth_iomadoidc') . '</a>');

        // Additional options heading.
        $mform->addElement('html', "<h3>" . get_string('heading_additional_options', 'auth_iomadoidc') . "</h3>");
        $mform->addelement('static', 'headingces', '', get_string('heading_additional_options_desc', 'auth_iomadoidc'));

        // Force redirect.
        $mform->addElement('advcheckbox', 'forceredirect'. $postfix, get_string('cfg_forceredirect_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'forceredirectdesc', '', get_string('cfg_forceredirect_desc', 'auth_iomadoidc'));

        // Silent login mode.
        $forceloginconfigurl = new moodle_url('/admin/settings.php', ['section' => 'sitepolicies']);
        $mform->addElement('advcheckbox', 'silentloginmode' . $postfix, get_string('cfg_silentloginmode_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'silientloginmodedesc', '', get_string('cfg_silentloginmode_desc', 'auth_iomadoidc', $forceloginconfigurl->out(false)));

        // Auto-append.
        $mform->addElement('text', 'autoappend'. $postfix, get_string('cfg_autoappend_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'autoappenddesc', '', get_string('cfg_autoappend_desc', 'auth_iomadoidc'));
        $mform->setType('autoappend' . $postfix, PARAM_TEXT);

        // Domain hint.
        $mform->addElement('text', 'domainhint'. $postfix, get_string('cfg_domainhint_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'domainhintdesc', '', get_string('cfg_domainhint_desc', 'auth_iomadoidc'));
        $mform->setType('domainhint' . $postfix, PARAM_TEXT);

        // Login flow.
        $loginflowarray = [];
        $logiflowarray[] = $mform->addElement('radio', 'loginflow' . $postfix, get_string('cfg_loginflow_key', 'auth_iomadoidc'), get_string('cfg_loginflow_authcode', 'auth_iomadoidc'), 0);
        $logiflowarray[] = $mform->addElement('static', 'loginflow_authcode_desc', '', get_string('cfg_loginflow_authcode_desc', 'auth_iomadoidc'));
        $logiflowarray[] = $mform->addElement('radio', 'loginflow' . $postfix, '', get_string('cfg_loginflow_rocreds', 'auth_iomadoidc'), 1);
        $logiflowarray[] = $mform->addElement('static', 'loginflow_rocreds_desc', '', get_string('cfg_loginflow_rocreds_desc', 'auth_iomadoidc'));

        $mform->addGroup($loginflowarray, 'loginflowarray', '');
        $mform->setDefault('loginflow', 0);

        // User restrictions heading.
        $mform->addElement('html', "<h3>" . get_string('heading_user_restrictions', 'auth_iomadoidc') . "</h3>");
        $mform->addElement('static', 'user_restrictions_heading_desc', '', get_string('heading_user_restrictions_desc', 'auth_iomadoidc'));

        // User restrictions.
        $mform->addElement('textarea', 'userrestrictions'. $postfix, get_string('cfg_userrestrictions_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'userrestrictionsdesc', '', get_string('cfg_userrestrictions_desc', 'auth_iomadoidc'));
        $mform->setType('userrestrictions' . $postfix, PARAM_TEXT);

        // User restrictions case sensitivity.
        $mform->addElement('advcheckbox', 'userrestrictionscasesensitive'. $postfix, get_string('cfg_userrestrictionscasesensitive_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'userrestrictionscasesensitivedesc', '', get_string('cfg_userrestrictionscasesensitive_desc', 'auth_iomadoidc'));
        $mform->setDefault('userrestrictionscasesensitive' . $postfix, 1);

        // Sign out integration heading.
        $mform->addElement('html', "<h3>" . get_string('heading_sign_out', 'auth_iomadoidc') . "</h3>");
        $mform->addElement('static', 'sign_out_heading_desc', '', get_string('heading_sign_out_desc', 'auth_iomadoidc'));

        // Single sign out from Moodle to IdP.
        $mform->addElement('advcheckbox', 'single_sign_off'. $postfix, get_string('cfg_signoffintegration_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'single_sign_off_desc', '', get_string('cfg_signoffintegration_desc', 'auth_iomadoidc', $CFG->wwwroot));

        // IdP logout endpoint.
        $mform->addElement('text', 'logouturi'. $postfix, get_string('cfg_logoutendpoint_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'logouturi_desc', '', get_string('cfg_logoutendpoint_desc', 'auth_iomadoidc'));
        $mform->setDefault('logouturi' . $postfix, 'https://login.microsoftonline.com/common/oauth2/logout');
        $mform->setType('logouturi' . $postfix, PARAM_URL);

        // Front channel logout URL.
        $mform->addElement('text', 'logoutendpoint'. $postfix, get_string('cfg_frontchannellogouturl_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'logoutendpoint_desc', '', get_string('cfg_frontchannellogouturl_desc', 'auth_iomadoidc'));
        $mform->setDefault('logoutendpoint' . $postfix, utils::get_frontchannellogouturl());
        $mform->setType('logoutendpoint' . $postfix, PARAM_URL);

        // Display heading.
        $mform->addElement('html', "<h3>" . get_string('heading_display', 'auth_iomadoidc') . "</h3>");
        $mform->addElement('static', 'display_heading_desc', '', get_string('heading_display_desc', 'auth_iomadoidc'));

        // Provider Name (opname).
        $mform->addElement('text', 'opname'. $postfix, get_string('cfg_opname_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'opname_desc', '', get_string('cfg_opname_desc', 'auth_iomadoidc'));
        $mform->setDefault('opname' . $postfix, get_string('pluginname', 'auth_iomadoidc'));
        $mform->setType('opname' . $postfix, PARAM_TEXT);

        // Icon.
        $icons = [get_string('cfg_iconalt_o365', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_locked', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_lock', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_go', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_stop', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_user', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_user2', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_key', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_group', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_group2', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_mnet', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_userlock', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_plus', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_check', 'auth_iomadoidc'),
                  get_string('cfg_iconalt_rightarrow', 'auth_iomadoidc'),
                  ];
        $mform->addElement('select', 'icon'. $postfix, get_string('cfg_icon_key', 'auth_iomadoidc'), $icons);
        $mform->addElement('static', 'icondesc', '', get_string('cfg_icon_desc', 'auth_iomadoidc'));

        // Custom icon - we don't use postfix here are it's handled in the data processing.
        $mform->addElement('filemanager', 'customicon', get_string('cfg_customicon_key', 'auth_iomadoidc'), null,
                            ['maxfiles' => 1,
                             'accepted_types' => ['image']]);
        $mform->addElement('static', 'customicondesc', '', get_string('cfg_customicon_desc', 'auth_iomadoidc'));
    
        // Debugging heading.
        $mform->addElement('html', "<h3>" . get_string('heading_debugging', 'auth_iomadoidc') . "</h3>");
        $mform->addElement('static', 'debugging_heading_desc', '', get_string('heading_debugging_desc', 'auth_iomadoidc'));

        // Record debugging messages.
        $mform->addElement('advcheckbox', 'debugmode'. $postfix, get_string('cfg_debugmode_key', 'auth_iomadoidc'));
        $mform->addElement('static', 'debugmode_desc', '', get_string('cfg_debugmode_desc', 'auth_iomadoidc'));

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $this->add_action_buttons();
    }
}
