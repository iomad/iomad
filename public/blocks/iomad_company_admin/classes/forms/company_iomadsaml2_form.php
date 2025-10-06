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
use auth_iomadsaml2\admin\iomadsaml2_settings;
use auth_iomadsaml2\admin\setting_button;
use auth_iomadsaml2\admin\setting_textonly;
use auth_iomadsaml2\ssl_algorithms;
use auth_iomadsaml2\user_fields;
use moodleform;
use html_writer;
use auth_iomadsaml2\idp_data;
use auth_iomadsaml2\idp_parser;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

require_once($CFG->dirroot.'/auth/iomadsaml2/locallib.php');

class company_iomadsaml2_form extends moodleform {
    public function definition() {
        global $CFG, $PAGE, $DB, $postfix;

        $yesno = [get_string('no'),
                  get_string('yes'),
                 ];

        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('html', "<h2>" . format_string(get_string('pluginname', 'auth_iomadsaml2') . " : " .
                                                          get_string('settings', 'moodle')) . "</h2>");

        $mform->addElement('static', 'headingdescription', '', get_string('auth_iomadsaml2description', 'auth_iomadsaml2'));

        // IDP Metadata.
        $mform->addElement('textarea',
                           'idpmetadata'. $postfix,
                            get_string('idpmetadata', 'auth_iomadsaml2'));
        $mform->addElement('static', 'idpmetadatadesc', '', get_string('idpmetadata_help', 'auth_iomadsaml2'));
        $mform->setType('idpmetadata' . $postfix, PARAM_RAW);

        // IDP name.
        $mform->addElement('text',
                'idpname' . $postfix,
                get_string('idpname', 'auth_iomadsaml2'));
        $mform->addElement('static', 'idpnamedesc', '', get_string('idpname_help', 'auth_iomadsaml2'));
        $mform->setDefault('idpname' . $postfix, get_string('idpnamedefault', 'auth_iomadsaml2'));
        $mform->setType('idpname' . $postfix, PARAM_TEXT);

        // Manage available IdPs.
        $mform->addElement('static',
            'availableidps' . $postfix,
            get_string('availableidps', 'auth_iomadsaml2'),
            html_writer::tag('a', get_string('availableidps', 'auth_iomadsaml2'),
                             ['href' => $CFG->wwwroot . '/auth/iomadsaml2/availableidps.php',
                              'class' => 'btn btn-primary']));
        $mform->addElement('static', 'availableidpsdesc', '', get_string('availableidps_help', 'auth_iomadsaml2'));

        // Display IDP Link.
        $mform->addElement('select',
                'showidplink' . $postfix,
                get_string('showidplink', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'showidplinkdesc', '', get_string('showidplink_help', 'auth_iomadsaml2'));

        // IDP Metadata refresh.
        $mform->addElement('select',
                'idpmetadatarefresh' . $postfix,
                get_string('idpmetadatarefresh', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'idpmetadatarefreshdesc', '', get_string('idpmetadatarefresh_help', 'auth_iomadsaml2'));

        // Debugging.
        $mform->addElement('select',
                'debug' . $postfix,
                get_string('debug', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'devbuddesc', '', get_string('debug_help', 'auth_iomadsaml2', $CFG->wwwroot . '/auth/iomadsaml2/debug.php'));

        // Logging.
        $mform->addElement('select',
                'logtofile' . $postfix,
                get_string('logtofile', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'logtofiledesc', '', get_string('logtofile_help', 'auth_iomadsaml2'));
        $mform->addElement('text',
                'logdir' . $postfix,
                get_string('logdir', 'auth_iomadsaml2'));
        $mform->addElement('static', 'logdirdesc', get_string('logdir_help', 'auth_iomadsaml2'));
        $mform->setDefault('loggdir' . $postfix, get_string('logdirdefault', 'auth_iomadsaml2'));
        $mform->setType('logdir' . $postfix, PARAM_TEXT);

        // See section 8.3 from http://docs.oasis-open.org/security/saml/v2.0/saml-core-2.0-os.pdf for more information.
        $nameidlist = [
            'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:WindowsDomainQualifiedName',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:kerberos',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:entity',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        ];
        $mform->addElement('select',
                           'nameidpolicy' . $postfix,
                           get_string('nameidpolicy', 'auth_iomadsaml2'),
                           array_combine($nameidlist, $nameidlist));
        $mform->addElement('static', 'nameidppolicydesc', '', get_string('nameidpolicy_help', 'auth_iomadsaml2'));
        $mform->setDefault('nameidpolicy' . $postfix, 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

        // Add NameID as attribute.
        $mform->addElement('select',
                'nameidasattrib' . $postfix,
                get_string('nameidasattrib', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'ma,eidasattribdesc', '', get_string('nameidasattrib_help', 'auth_iomadsaml2'));

        // Lock certificate.
        $mform->addElement('static',
                'certificatelock' . $postfix,
                get_string('certificatelock', 'auth_iomadsaml2'),
            html_writer::tag('a', get_string('certificatelock', 'auth_iomadsaml2'),
                             ['href' => $CFG->wwwroot . '/auth/iomadsaml2/certificatelock.php',
                              'class' => 'btn btn-primary']));
        $mform->addElement('static', 'certificatelockdesc', '', get_string('certificatelock_help', 'auth_iomadsaml2'));

        // Regenerate certificate.
        $mform->addElement('html',
                'certificate' . $postfix,
                get_string('certificate', 'auth_iomadsaml2'),
            html_writer::tag('a', get_string('certificate', 'auth_iomadsaml2'),
                             ['href' => $CFG->wwwroot . '/auth/iomadsaml2/regenerate.php',
                              'class' => 'btn btn-primary']));
        $mform->addElement('static', 'certificatelockdesc', '', get_string('certificate_help', 'auth_iomadsaml2', $CFG->wwwroot . '/auth/iomadsaml2/cert.php'));

        $mform->addElement('passwordunmask',
            'privatekeypass' . $postfix,
            get_string('privatekeypass', 'auth_iomadsaml2'));
        $mform->addElement('static', 'privatekeypassdesc', '', get_string('privatekeypass_help', 'auth_iomadsaml2'));
        $mform->setDefault('privatekeypass' . $postfix, get_site_identifier());
        $mform->setType('privatekeypass' . $postfix, PARAM_TEXT);

        // SP Metadata.
        $mform->addElement('static',
               'spmetadata' . $postfix,
               get_string('spmetadata', 'auth_iomadsaml2'),
               get_string('spmetadata_help', 'auth_iomadsaml2', $CFG->wwwroot . '/auth/iomadsaml2/sp/metadata.php'));

        // SP Metadata signature.
        $mform->addElement('select',
                'spmetadatasign' . $postfix,
                get_string('spmetadatasign', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'spmetadatasigndesc', '', get_string('spmetadatasign_help', 'auth_iomadsaml2'));

        $mform->addElement('text',
            'spentityid' . $postfix,
            get_string('spentityid', 'auth_iomadsaml2'));
        $mform->addElement('static', 'spentityiddesc', '', get_string('spentityid_help', 'auth_iomadsaml2'));
        $mform->setType('spentityid' . $postfix, PARAM_TEXT);

        $mform->addElement('select',
            'wantassertionssigned' . $postfix,
            get_string('wantassertionssigned', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'wantassetionssigneddesc', '', get_string('wantassertionssigned_help', 'auth_iomadsaml2'));

        $assertionsconsumerservices = [
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => 'HTTP Post',
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact' => 'HTTP Artifact',
            'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser' => 'Holder-of-Key Web Browser SSO',
        ];

        $acssetting = $mform->addElement('select',
                                         'assertionsconsumerservices' . $postfix,
                                         get_string('assertionsconsumerservices', 'auth_iomadsaml2'),
                                         $assertionsconsumerservices);
        $acssetting->setMultiple('true');
        $mform->addElement('static', 'assertionsconsumerservicesdesc', '', get_string('assertionsconsumerservices_help', 'auth_iomadsaml2'));

        $mform->addElement('select',
            'allowcreate' . $postfix,
            get_string('allowcreate', 'auth_iomadsaml2'),
            $yesno);
        $mform->addElement('static', 'allowcreatedesc', '', get_string('allowcreate_help', 'auth_iomadsaml2'));

        $mform->addElement('text',
            'authncontext' . $postfix,
            get_string('authncontext', 'auth_iomadsaml2'));
        $mform->addElement('static', 'authncontextdesc', '', get_string('authncontext_help', 'auth_iomadsaml2'));
        $mform->setType('authncontext' . $postfix, PARAM_TEXT);

        $mform->addElement('select',
            'signaturealgorithm' . $postfix,
            get_string('signaturealgorithm', 'auth_iomadsaml2'),
            ssl_algorithms::get_valid_saml_signature_algorithms());
        $mform->addElement('static', 'signaturealgorithmdesc', '', get_string('signaturealgorithm_help', 'auth_iomadsaml2'));
        $mform->setDefault('signaturealgorithm' . $postfix, ssl_algorithms::get_default_saml_signature_algorithm());

        // Dual Login.
        $dualloginoptions = [
            iomadsaml2_settings::OPTION_DUAL_LOGIN_NO      => get_string('no'),
            iomadsaml2_settings::OPTION_DUAL_LOGIN_YES     => get_string('yes'),
            iomadsaml2_settings::OPTION_DUAL_LOGIN_PASSIVE => get_string('passivemode', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_DUAL_LOGIN_TEST    => get_string('test_idp_conn', 'auth_iomadsaml2'),
        ];
        $mform->addElement('select',
                'duallogin' . $postfix,
                get_string('duallogin', 'auth_iomadsaml2'),
                $dualloginoptions);
        $mform->addElement('static', 'duallogindesc', '', get_string('duallogin_help', 'auth_iomadsaml2'));
        $mform->setDefault('duallogin' . $postfix, iomadsaml2_settings::OPTION_DUAL_LOGIN_YES);

        if (get_config('auth_iomadsaml2', 'duallogin' . $postfix) == iomadsaml2_settings::OPTION_DUAL_LOGIN_TEST) {
            $mform->addElement('text', 'testendpoint' . $postfix,
                get_string('test_endpoint', 'auth_iomadsaml2'));
            $mform->addElement('static', 'testendpointdesc', '', get_string('test_endpoint_desc', 'auth_iomadsaml2'));
            $mform->setDefault('test_endpoint' . $postfix, 'https://example.com');
            $mform->setType('test_endpoint' . $postfix, PARAM_URL);
        }

        $mform->addElement('textarea',
            'noredirectips' . $postfix,
            get_string('noredirectips', 'auth_iomadsaml2'));
        $mform->addElement('static', 'nodirectipsdesc', '', get_string('noredirectips_help', 'auth_iomadsaml2'));
        $mform->setType('nodirectips', PARAM_TEXT);

        // Auto login.
        $autologinoptions = [
            iomadsaml2_settings::OPTION_AUTO_LOGIN_NO => get_string('no'),
            iomadsaml2_settings::OPTION_AUTO_LOGIN_SESSION => get_string('autologinbysession', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_AUTO_LOGIN_COOKIE => get_string('autologinbycookie', 'auth_iomadsaml2'),
        ];
        $mform->addElement('select',
                'autologin' . $postfix,
                get_string('autologin', 'auth_iomadsaml2'),
                $autologinoptions);
        $mform->addElement('static', 'autologindesc', '', get_string('autologin_help', 'auth_iomadsaml2'));
        $mform->setDefault('autologin' . $postfix, iomadsaml2_settings::OPTION_AUTO_LOGIN_NO);
        $mform->addElement('text',
                'autologincookie' . $postfix,
                 get_string('autologincookie', 'auth_iomadsaml2'));
        $mform->addElement('static', 'autologincookiedesc', '', get_string('autologincookie_help', 'auth_iomadsaml2'));
        $mform->setType('autologincookie' . $postfix, PARAM_TEXT);

        // Allow any auth type.
        $mform->addElement('select',
                'anyauth' . $postfix,
                get_string('anyauth', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'anyauthdesc', '', get_string('anyauth_help', 'auth_iomadsaml2'));

        // Simplify attributes.
        $mform->addElement('select',
                'attrsimple' . $postfix,
                get_string('attrsimple', 'auth_iomadsaml2'), $yesno);
        $mform->addElement('static', 'attrsimpledec', '', get_string('attrsimple_help', 'auth_iomadsaml2'));

        // IDP to Moodle mapping.
        // IDP attribute.
        $mform->addElement('text',
                'idpattr' . $postfix,
                get_string('idpattr', 'auth_iomadsaml2'));
        $mform->addElement('static', 'idpattrdesc', '', get_string('idpattr_help', 'auth_iomadsaml2'));
        $mform->setDefault('idpattr' . $postfix, 'uid');
        $mform->setType('idpattr' . $postfix, PARAM_TEXT);

        // Moodle Field.
        $mform->addElement('select',
                'mdlattr' . $postfix,
                get_string('mdlattr', 'auth_iomadsaml2'),
                user_fields::get_supported_fields());
        $mform->addElement('static', 'mdlattrdesc', '', get_string('mdlattr_help', 'auth_iomadsaml2'));
        $mform->setDefault('mdlattr' . $postfix, 'username');

        // Lowercase.
        $toloweroptions = [
            iomadsaml2_settings::OPTION_TOLOWER_EXACT => get_string('tolower:exact', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_TOLOWER_LOWER_CASE => get_string('tolower:lowercase', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_TOLOWER_CASE_INSENSITIVE => get_string('tolower:caseinsensitive', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_TOLOWER_CASE_AND_ACCENT_INSENSITIVE => get_string('tolower:caseandaccentinsensitive', 'auth_iomadsaml2'),
        ];
        $mform->addElement('select',
                'tolower' . $postfix,
                get_string('tolower', 'auth_iomadsaml2'),
                $toloweroptions);
        $mform->addElement('static', 'tolowerdesc', '', get_string('tolower_help', 'auth_iomadsaml2'));
        $mform->setDefault('tolower' . $postfix, iomadsaml2_settings::OPTION_TOLOWER_EXACT);

        // Requested Attributes.
        $mform->addElement('textarea',
            'requestedattributes' . $postfix,
            get_string('requestedattributes', 'auth_iomadsaml2'));
        $mform->addElement('static', 'requestedattributesdesc', '', get_string('requestedattributes_help', 'auth_iomadsaml2',
                                                                               ['example' => "<pre>
    urn:mace:dir:attribute-def:eduPersonPrincipalName
    urn:mace:dir:attribute-def:mail *</pre>"]));
        $mform->setType('requestedattributes' . $postfix, PARAM_TEXT);

        // Autocreate Users.
        $mform->addElement('select',
                'autocreate' . $postfix,
                get_string('autocreate', 'auth_iomadsaml2'),
                $yesno);
        $mform->addElement('static', 'autocreatedesc', '', get_string('autocreate_help', 'auth_iomadsaml2'));
        $mform->setDefault('autocreate' . $postfix, 0);

        // Group access rules.
        $mform->addElement('textarea',
            'grouprules' . $postfix,
            get_string('grouprules', 'auth_iomadsaml2'));
        $mform->addElement('static', 'grouprulesdesc', '', get_string('grouprules_help', 'auth_iomadsaml2'));
        $mform->setType('grouprules' . $postfix, PARAM_TEXT);

        // Alternative Logout URL.
        $mform->addElement('text',
                'alterlogout' . $postfix,
                get_string('alterlogout', 'auth_iomadsaml2'));
        $mform->addElement('static', 'alterlogoutdesc', '', get_string('alterlogout_help', 'auth_iomadsaml2'));
        $mform->setType('alterlogout' . $postfix, PARAM_URL);

        // Multi IdP display type.
        $multiidpdisplayoptions = [
            iomadsaml2_settings::OPTION_MULTI_IDP_DISPLAY_DROPDOWN => get_string('multiidpdropdown', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_MULTI_IDP_DISPLAY_BUTTONS => get_string('multiidpbuttons', 'auth_iomadsaml2')
        ];
        $mform->addElement('select',
            'multiidpdisplay' . $postfix,
            get_string('multiidpdisplay', 'auth_iomadsaml2'),
            $multiidpdisplayoptions);
        $mform->addElement('static', 'multiidpdisplaydesc', '', get_string('multiidpdisplay_help', 'auth_iomadsaml2'));
        $mform->setDefault('multiidpdisplay' . $postfix, iomadsaml2_settings::OPTION_MULTI_IDP_DISPLAY_DROPDOWN);

        // Attempt Single Sign out.
        $mform->addElement('select',
            'attemptsignout' . $postfix,
            get_string('attemptsignout', 'auth_iomadsaml2'),
            $yesno);
        $mform->addElement('static', 'attemptsignoutdesc', '', get_string('attemptsignout_help', 'auth_iomadsaml2'));
        $mform->setDefault('attempsignout' . $postfix, 1);

        // SAMLPHP tempdir
        $mform->addElement('text',
            'tempdir' . $postfix,
            get_string('tempdir', 'auth_iomadsaml2'));
        $mform->addElement('static', 'tempdirdesc', '', get_string('tempdir_help', 'auth_iomadsaml2'));
        $mform->setDefault('tempdir' . $postfix, get_string('tempdirdefault', 'auth_iomadsaml2'));
        $mform->setType('tempdir' . $postfix, PARAM_TEXT);

        // SAMLPHP version.
        $authplugin = get_auth_plugin('iomadsaml2');
        $mform->addElement('static',
                'sspversion',
                get_string('sspversion', 'auth_iomadsaml2'),
                $authplugin->get_ssp_version()
                );

        // User block and redirect feature setting section.
        $mform->addElement('html', '<h3>' . get_string('blockredirectheading', 'auth_iomadsaml2') . '</h3>');
        $mform->addElement('static', 'redirectheaddesc', '', get_string('auth_iomadsaml2blockredirectdescription', 'auth_iomadsaml2'));

        // Flagged login response options.
        $flaggedloginresponseoptions = [
            iomadsaml2_settings::OPTION_FLAGGED_LOGIN_MESSAGE => get_string('flaggedresponsetypemessage', 'auth_iomadsaml2'),
            iomadsaml2_settings::OPTION_FLAGGED_LOGIN_REDIRECT => get_string('flaggedresponsetyperedirect', 'auth_iomadsaml2')
        ];

        // Flagged login response options selector.
        $mform->addElement('select',
            'flagresponsetype' . $postfix,
            get_string('flagresponsetype', 'auth_iomadsaml2'),
            $flaggedloginresponseoptions);
        $mform->addElement('static', 'flagresponsetypedesc', '', get_string('flagresponsetype_help', 'auth_iomadsaml2'));
        $mform->setDefault('flagresponsetype' . $postfix, iomadsaml2_settings::OPTION_FLAGGED_LOGIN_REDIRECT);


        // Set the http OR https fully qualified scheme domain name redirect destination for flagged accounts.
        $mform->addElement('text',
            'flagredirecturl' . $postfix,
            get_string('flagredirecturl', 'auth_iomadsaml2'));
        $mform->addElement('static', 'flagredirecturldesc', '', get_string('flagredirecturl_help', 'auth_iomadsaml2'));
        $mform->setType('flagredirecturl' . $postfix, PARAM_URL);

        // Set the displayed message for flagged accounts.
        $mform->addElement('textarea',
            'flagmessage' . $postfix,
            get_string('flagmessage', 'auth_iomadsaml2'));
        $mform->addElement('static', 'flagmessagedesc', '', get_string('flagmessage_help', 'auth_iomadsaml2'));
        $mform->setDefault('flagmessage' . $postfix, get_string('flagmessage_default', 'auth_iomadsaml2'));
        $mform->setType('flagmessage' . $postfix, PARAM_TEXT);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB, $CFG, $SESSION, $postfix;

        $errors = parent::validation($data, $files);

        $idpmetadata = 'idpmetadata'. $postfix;
        $value = trim($data[$idpmetadata]);
        if (!empty($value)) {
            try {
                $idps = $this->get_idps_data($value);
                $this->process_all_idps_metadata($idps);
            } catch (setting_idpmetadata_exception $exception) {
                $errors[$idpmetadata] = get_string('idpmetadata_invalid', 'auth_iomadsaml2');
            }
        }

        return $errors;
    }

    /**
     * Process all idps metadata.
     *
     * @param idp_data[] $idps
     */
    private function process_all_idps_metadata($idps) {
        global $DB, $companyid;

        $currentidpsrs = $DB->get_records('auth_iomadsaml2_idps', ['companyid' => $companyid]);
        $oldidps = array();
        foreach ($currentidpsrs as $idpentity) {
            if (!isset($oldidps[$idpentity->metadataurl])) {
                $oldidps[$idpentity->metadataurl] = array();
            }

            $oldidps[$idpentity->metadataurl][$idpentity->entityid] = $idpentity;
        }

        foreach ($idps as $idp) {
            $this->process_idp_metadata($idp, $oldidps);
        }

        // We remove any old IdPs that are left over.
        $this->remove_old_idps($oldidps);
    }

    /**
     * Process idp metadata.
     *
     * @param idp_data $idp
     * @param mixed $oldidps
     * @throws setting_idpmetadata_exception
     */
    private function process_idp_metadata(idp_data $idp, &$oldidps) {
        $xpath = $this->get_idp_xml_path($idp);
        $idpelements = $this->find_all_idp_sso_descriptors($xpath);

        if ($idpelements->length == 1) {
            $this->process_idp_xml($idp, $idpelements->item(0), $xpath, $oldidps, 1);
        } else if ($idpelements->length > 1) {
            foreach ($idpelements as $childidpelements) {
                $this->process_idp_xml($idp, $childidpelements, $xpath, $oldidps, 0);
            }
        }

        $this->save_idp_metadata_xml($idp->idpurl, $idp->get_rawxml());
    }

    /**
     * Process idp metadata.
     *
     * @param idp_data $idp
     * @param DOMElement $idpelements
     * @param DOMXPath $xpath
     * @param mixed $oldidps
     * @param int $activedefault
     */
    private function process_idp_xml(idp_data $idp, DOMElement $idpelements, DOMXPath $xpath,
                                        &$oldidps, $activedefault = 0) {
        global $DB, $companyid;
        $entityid = $idpelements->getAttribute('entityID');

        // Locate a displayname element provided by the IdP XML metadata.
        $names = $xpath->query('.//mdui:DisplayName', $idpelements);
        $idpname = null;
        if ($names && $names->length > 0) {
            $idpname = $names->item(0)->textContent;
        } else if (!empty($idp->idpname)) {
            $idpname = $idp->idpname;
        } else {
            $idpname = get_string('idpnamedefault', 'auth_iomadsaml2');
        }

        // Locate a logo element provided by the IdP XML metadata.
        $logos = $xpath->query('.//mdui:Logo', $idpelements);
        $logo = null;
        if ($logos && $logos->length > 0) {
            $logo = $logos->item(0)->textContent;
        }

        if (isset($oldidps[$idp->idpurl][$entityid])) {
            $oldidp = $oldidps[$idp->idpurl][$entityid];

            if (!empty($idpname) && $oldidp->defaultname !== $idpname) {
                $DB->set_field('auth_iomadsaml2_idps', 'defaultname', $idpname, array('id' => $oldidp->id));
            }

            if (!empty($logo) && $oldidp->logo !== $logo) {
                $DB->set_field('auth_iomadsaml2_idps', 'logo', $logo, array('id' => $oldidp->id));
            }

            // Remove the idp from the current array so that we don't delete it later.
            unset($oldidps[$idp->idpurl][$entityid]);
        } else {
            $newidp = new \stdClass();
            $newidp->metadataurl = $idp->idpurl;
            $newidp->entityid = $entityid;
            $newidp->activeidp = $activedefault;
            $newidp->defaultidp = 0;
            $newidp->adminidp = 0;
            $newidp->defaultname = $idpname;
            $newidp->logo = $logo;
            $newidp->companyid = $companyid;

            $DB->insert_record('auth_iomadsaml2_idps', $newidp);
        }
    }

    /**
     * Process idp metadata.
     *
     * @param mixed $oldidps
     */
    private function remove_old_idps($oldidps) {
        global $DB;

        foreach ($oldidps as $metadataidps) {
            foreach ($metadataidps as $oldidp) {
                $DB->delete_records('auth_iomadsaml2_idps', array('id' => $oldidp->id));
            }
        }
    }

    /**
     * Get idps data.
     *
     * @param string $value
     * @return idp_data[]
     */
    public function get_idps_data($value) {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $parser = new idp_parser();
        $idps = $parser->parse($value);

        // Download the XML if it was not parsed from the ipdmetadata field.
        foreach ($idps as $idp) {
            if (!is_null($idp->get_rawxml())) {
                continue;
            }

            $rawxml = \download_file_content($idp->idpurl);
            if ($rawxml === false) {
                throw new setting_idpmetadata_exception(
                    get_string('idpmetadata_badurl', 'auth_iomadsaml2', $idp->idpurl)
                );
            }
            $idp->set_rawxml($rawxml);
        }

        return $idps;
    }

    /**
     * Get idp xml path.
     *
     * @param idp_data $idp
     * @return DOMXPath
     */
    private function get_idp_xml_path(idp_data $idp) {
        $xml = new DOMDocument();

        libxml_use_internal_errors(true);

        $rawxml = $idp->rawxml;

        if (!$xml->loadXML($rawxml, LIBXML_PARSEHUGE)) {
            $errors = libxml_get_errors();
            $lines = explode("\n", $rawxml);
            $msg = '';
            foreach ($errors as $error) {
                $msg .= "<br>Error ({$error->code}) line $error->line char  $error->column: $error->message";
            }

            throw new setting_idpmetadata_exception(get_string('idpmetadata_invalid', 'auth_iomadsaml2') . $msg);
        }

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');

        return $xpath;
    }

    /**
     * Find all idp SSO descriptors.
     *
     * @param DOMXPath $xpath
     * @return DOMNodeList
     */
    private function find_all_idp_sso_descriptors(DOMXPath $xpath) {
        $idpelements = $xpath->query('//md:EntityDescriptor[//md:IDPSSODescriptor]');
        return $idpelements;
    }

    /**
     * Save idp metadata xml.
     *
     * @param string $url
     * @param string $xml
     */
    private function save_idp_metadata_xml($url, $xml) {
        global $CFG, $iomadsaml2auth;
        require_once("{$CFG->dirroot}/auth/iomadsaml2/setup.php");

        $file = $iomadsaml2auth->get_file_idp_metadata_file($url);
        file_put_contents($file, $xml);
    }
}
