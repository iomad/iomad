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
 * IOMAD dashboard authentication options form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use html_writer;
use moodleform;

/**
 * IOMAD dashboard authentication options form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_auth_options_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set up the form.
        $mform = & $this->_form;

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('html', html_writer::tag('h2', get_string('authentication', 'admin')));

        // Site force login options.
        $mform->addElement(
            'advcheckbox',
            'forcelogin',
            get_string('forcelogin', 'admin'),
            get_string('configforcelogin', 'admin')
        );
        $mform->setDefault('forcelogin', $CFG->forcelogin);

        $mform->addElement(
            'advcheckbox',
            'forceloginforprofiles',
            get_string('forceloginforprofiles', 'admin'),
            get_string('configforceloginforprofiles', 'admin')
        );
        $mform->setDefault('forceloginforprofiles', $CFG->forceloginforprofiles);

        $mform->addElement(
            'advcheckbox',
            'forceloginforprofileimage',
            get_string('forceloginforprofileimage', 'admin'),
            get_string('forceloginforprofileimage_help', 'admin')
        );
        $mform->setDefault('forceloginforprofileimage', $CFG->forceloginforprofileimage);

        // Get the list of available self signup authentication plugins.
        $availableauths = [];
        $availableauths[''] = get_string('disable');
        $authsenabled = get_enabled_auth_plugins();
        foreach ($authsenabled as $auth) {
            $authplugin = get_auth_plugin($auth);
            // If we can self signup, we don't want it.
            if (!$authplugin->can_signup()) {
                continue;
            }

            // Get the auth title (from core or own auth lang files).
            $availableauths[$auth] = $authplugin->get_title();
        }

        $mform->addElement('select',
                           'registerauth',
                           get_string('selfregistration', 'auth'),
                           $availableauths);
        $mform->addElement('static', 'registerauthdesc', '', get_string('selfregistration_help', 'auth'));
        $mform->setDefault('registerauth', $CFG->registerauth);

        $mform->addElement('advcheckbox',
                           'authloginviaemail',
                           get_string('authloginviaemail', 'core_auth'),
                           get_string('authloginviaemail_desc', 'core_auth'));
        $mform->setDefault('authloginviaemail', $CFG->authloginviaemail);

        $mform->addElement('select',
                           'guestloginbutton',
                           get_string('guestloginbutton', 'auth'),
                           [get_string('hide'),
                            get_string('show')]);
        $mform->addElement('static', 'guestloginbuttondesc', '', get_string('showguestlogin', 'auth'));
        $mform->setDefault('guestloginbutton', $CFG->guestloginbutton);

        $mform->addElement(
            'text',
            'alternateloginurl',
            get_string('alternateloginurl', 'auth')
        );
        $mform->addElement('static',
                           'alternateloginurldesc',
                           '',
                           get_string('alternatelogin', 'auth', htmlspecialchars(get_login_url(), ENT_COMPAT)));
        $mform->setDefault('alternateloginurl', '');

        $mform->addElement(
            'advcheckbox',
            'showloginform',
            get_string('showloginform', 'core_auth'),
            get_string('showloginform_desc', 'core_auth')
        );
        $mform->setDefault('showloginform', $CFG->showloginform);

        $mform->addElement(
            'text',
            'forgottenpasswordurl',
            get_string('forgottenpasswordurl', 'auth')
        );
        $mform->addElement('static', 'forgottenpasswordurldesc', '', get_string('forgottenpassword', 'auth'));
        $mform->setDefault('forgottenpasswordurl', '');

        $mform->addElement(
            'editor',
            'auth_instructions',
            get_string('instructions', 'auth')
        );
        $mform->addElement('static', 'instructionsdesc', '', get_string('authinstructions', 'auth'));
        $mform->setDefault('auth_instructions', '');

        $mform->addElement(
            'textarea',
            'allowemailaddresses',
            get_string('allowemailaddresses', 'admin')
        );
        $mform->addElement('static', 'allowemailaddressesdesc', '', get_string('configallowemailaddresses', 'admin'));
        $mform->setDefault('allowemailaddresses', '');

        $mform->addElement(
            'textarea',
            'denyemailaddresses',
            get_string('denyemailaddresses', 'admin')
        );
        $mform->addElement('static', 'denyemailaddressesdesc', '', get_string('configdenyemailaddresses', 'admin'));
        $mform->setDefault('denyemailaddresses', '');

        // ReCaptcha.
        $mform->addElement(
            'selectyesno',
            'enableloginrecaptcha',
            get_string('auth_loginrecaptcha', 'auth'),
        );
        $mform->setDefault('enableloginrecaptcha', 0);

        $mform->addElement('text', 'recaptchapublickey', get_string('recaptchapublickey', 'admin'));
        $mform->addElement('static', 'recaptchapublickeydesc', '', get_string('configrecaptchapublickey', 'admin'));
        $mform->setDefault('recaptchapublickeydesc', '');

        $mform->addElement('text', 'recaptchaprivatekey', get_string('recaptchaprivatekey', 'admin'));
        $mform->addElement('static', 'recaptchaprivatekeydesc', '', get_string('configrecaptchaprivatekey', 'admin'));
        $mform->setDefault('recaptchapublickeydesc', '');

        // Toggle password visiblity icon.
        $mform->addElement(
            'select',
            'loginpasswordtoggle',
            get_string('auth_loginpasswordtoggle', 'auth'),
            [
                TOGGLE_SENSITIVE_DISABLED => get_string('disabled', 'admin'),
                TOGGLE_SENSITIVE_ENABLED => get_string('enabled', 'admin'),
                TOGGLE_SENSITIVE_SMALL_SCREENS_ONLY => get_string('smallscreensonly', 'admin'),
            ],
        );
        $mform->addElement('static', 'loginpasswordtoggledesc', '', get_string('auth_loginpasswordtoggle_desc', 'auth'));
        $mform->setDefault('loginpasswordtoggle', $CFG->loginpasswordtoggle);

        // Show reset?
        $mform->addElement(
            'selectyesno',
            'allowreset',
            get_string('allowformreset', 'block_iomad_company_admin'),
        );

        $mform->setType('recaptchapublickey', PARAM_NOTAGS);
        $mform->setType('recaptchaprivatekey', PARAM_NOTAGS);
        $mform->setType('denyemailaddresses', PARAM_NOTAGS);
        $mform->setType('allowemailaddresses', PARAM_NOTAGS);
        $mform->setType('forgottenpasswordurl', PARAM_URL);
        $mform->setType('alternateloginurl', PARAM_URL);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $actionbuttons = [];
        $actionbuttons[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $actionbuttons[] = $mform->createElement('cancel');
        $actionbuttons[] = $mform->createElement('submit',
                                                 'resetbutton',
                                                 get_string('resetdefault', 'block_iomad_company_admin'),
                                                 [
                                                     'class' => 'dangerbutton',
                                                 ]);
        $mform->addGroup($actionbuttons, 'buttonar', '', ' ', false);

        $mform->hideIF('resetbutton', 'allowreset', 'eq', 0);
    }
}
