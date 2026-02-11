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
 * IOMAD Dashboard company SMTP options form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use moodleform;
use core\oauth2\api;
use html_writer;

/**
 * IOMAD Dashboard company SMTP options form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_smtp_options_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set up the form.
        $mform = &$this->_form;

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('html', html_writer::tag('h2', get_string('outgoingmailconfig', 'admin')));
        $mform->addElement(
            'text',
            'smtphosts',
            format_string(
                get_string('company', 'block_iomad_company_admin') .
                    " " .
                    get_string('smtphosts', 'admin')
            )
        );

        $mform->addElement('static', 'smtphostsdescription', '', get_string('configsmtphosts', 'admin'));
        $secureoptions = [
            '' => get_string('none', 'admin'),
            'ssl' => 'SSL',
            'tls' => 'TLS',
        ];
        $mform->addElement(
            'select',
            'smtpsecure',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('smtpsecure', 'admin')),
            $secureoptions
        );
        $mform->addElement('static', 'smtpsecuredescription', '', get_string('configsmtpsecure', 'admin'));
        $authtypeoptions = [
            'LOGIN' => 'LOGIN',
            'PLAIN' => 'PLAIN',
            'NTLM' => 'NTLM',
            'CRAM-MD5' => 'CRAM-MD5',
        ];

        // Get all the issuers.
        $issuers = api::get_all_issuers();
        $enabledissuers = [];
        foreach ($issuers as $issuer) {
            // Get the enabled issuer only.
            if ($issuer->get('enabled')) {
                $enabledissuers[] = $issuer;
            }
        }
        if (count($enabledissuers) > 0) {
            $authtypeoptions['XOAUTH2'] = 'XOAUTH2';
        }
        $authtypeoptionsselect = $mform->addElement(
            'select',
            'smtpauthtype',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('smtpauthtype', 'admin')),
            $authtypeoptions
        );
        $authtypeoptionsselect->setSelected('LOGIN');
        $mform->addElement('static', 'smtpauthtypedescription', '', get_string('configsmtpauthtype', 'admin'));
        if (count($enabledissuers) > 0) {
            $oauth2services = [
                '' => get_string('none', 'admin'),
            ];
            foreach ($enabledissuers as $issuer) {
                $oauth2services[$issuer->get('id')] = s($issuer->get('name'));
            }
            $mform->addElement(
                'select',
                'smtpoauthservice',
                format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                    get_string('issuer', 'auth_oauth2')),
                $oauth2services
            );
            $mform->addElement('static', 'smtpoauthservicedescription', '', get_string('configsmtpoauthservice', 'admin'));
        }
        $mform->addElement(
            'text',
            'smtpuser',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('smtpuser', 'admin'))
        );
        $mform->addElement('static', 'smtpuserdescription', '', get_string('configsmtpuser', 'admin'));
        $mform->addElement(
            'passwordunmask',
            'smtppass',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('smtppass', 'admin'))
        );
        $mform->addElement('static', 'smtppassdescription', '', get_string('configsmtpuser', 'admin'));
        $mform->addElement(
            'text',
            'smtpmaxbulk',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('smtpmaxbulk', 'admin'))
        );
        $mform->setDefault('smtpmaxbulk', 1);
        $mform->addElement('static', 'smtpmaxbulkdescription', '', get_string('configsmtpmaxbulk', 'admin'));
        $mform->addElement(
            'text',
            'noreplyaddress',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('noreplyaddress', 'admin'))
        );
        $mform->setDefault('noreplyaddress', 'noreply@' . get_host_from_url($CFG->wwwroot));
        $mform->addElement('static', 'noreplyaddressdescription', '', get_string('confignoreplyaddress', 'admin'));
        $mform->addElement(
            'text',
            'emaildkimselector',
            format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                get_string('emaildkimselector', 'admin'))
        );
        $mform->addElement('static', 'emaildkimselectordescription', '', get_string('configemaildkimselector', 'admin'));

        // Set the form field types.
        $mform->setType('usecompanysmtpsettings', PARAM_BOOL);
        $mform->setType('smtphosts', PARAM_RAW);
        $mform->setType('smtpsecure', PARAM_TEXT);
        $mform->setType('smtpauthtype', PARAM_TEXT);
        $mform->setType('smtpoauthservice', PARAM_INT);
        $mform->setType('smtpuser', PARAM_NOTAGS);
        $mform->setType('smtppass', PARAM_RAW);
        $mform->setType('smtpmaxbulk', PARAM_INT);
        $mform->setType('noreplyaddress', PARAM_EMAIL);
        $mform->setType('emaildkimselector', PARAM_FILE);

        // Show reset?
        $mform->addElement(
            'selectyesno',
            'allowreset',
            get_string('allowformreset', 'block_iomad_company_admin'),
        );

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $actionbuttons = [];
        $actionbuttons[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $actionbuttons[] = $mform->createElement('cancel');
        $actionbuttons[] = $mform->createElement(
            'submit',
            'resetbutton',
            get_string('resetdefault', 'block_iomad_company_admin'),
            [
                'class' => 'dangerbutton',
            ]);
        $mform->addGroup($actionbuttons, 'buttonar', '', ' ', false);

        $mform->hideIF('resetbutton', 'allowreset', 'eq', 0);
    }
}
