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

class company_edit_form extends \company_moodleform {
    protected $firstcompany;
    protected $isadding;
    protected $title = '';
    protected $description = '';
    protected $companyid;
    protected $companyrecord;
    protected $parentcompanyid;
    protected $previousroletemplateid;
    protected $previousemailtemplateid;
    protected $child;
    protected $context;
    protected $parentcompany;

    public function __construct($actionurl, $isadding, $companyid, $companyrecord, $firstcompany = false, $parentcompanyid = 0, $child = false) {
        global $DB, $CFG;

        $this->isadding = $isadding;
        $this->companyid = $companyid;
        $this->companyrecord = $companyrecord;
        $this->firstcompany = $firstcompany;
        $this->parentcompanyid = $parentcompanyid;
        $this->previousroletemplateid = $companyrecord->previousroletemplateid;
        $this->previousemailtemplateid = $companyrecord->previousemailtemplateid;
        if (!empty($companyrecord->templates)) {
            $this->companyrecord->templates = array();
        }
        $this->child = $child;
        if (empty($this->companyrecord->theme)) {
            $this->companyrecord->theme = $CFG->theme;
        }
        if ($parentcompanyid) {
            $this->parentcompany = $DB->get_record('company', ['id' => $parentcompanyid], '*', MUST_EXIST);
            $this->context = \core\context\company::instance($parentcompanyid);
        }
        if (!empty($companyid)) {
            $this->context = \core\context\company::instance($companyid);
        }
        // Default context is system as no company details have been added at all.
        if (empty($this->context)) {
            $this->context = context_system::instance();
        }
        parent::__construct($actionurl);
    }

    public function definition() {
        global $CFG, $PAGE, $DB;
        $systemcontext = context_system::instance();

        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'companyid', $this->companyid);
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'currentparentid', $this->parentcompanyid);
        $mform->setType('currentparentid', PARAM_INT);
        $mform->addElement('hidden', 'companyterminated');
        $mform->setType('companyterminated', PARAM_INT);
        $mform->setDefault('companyterminated', 0);

        // If this is the first company then some extra help is displayed.
        if ($this->firstcompany) {
            $mform->addElement('html', '<div class="alert alert-info">' . get_string('firstcompany', 'block_iomad_company_admin') . '</div>');
        }

        $mform->addElement('text', 'name',
                            get_string('companyname', 'block_iomad_company_admin'),
                            'maxlength="50" size="50"');
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'shortname',
                            get_string('companyshortname', 'block_iomad_company_admin'),
                            'maxlength="25" size="25"');
        $mform->setType('shortname', PARAM_NOTAGS);
        $mform->addRule('shortname', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'code',
                            get_string('companycode', 'block_iomad_company_admin'),
                            'maxlength="255" size="25"');
        $mform->setType('code', PARAM_NOTAGS);
        $mform->addHelpButton('code', 'companycode', 'block_iomad_company_admin');

        $mform->addElement('hidden', 'previousroletemplateid');
        $mform->addElement('hidden', 'previousemailtemplateid');

        $mform->setType('parentid', PARAM_INT);
        $mform->setType('templates', PARAM_RAW);
        $mform->setType('previousroletemplateid', PARAM_INT);
        $mform->setType('previousemailtemplateid', PARAM_INT);

        $mform->addElement('textarea', 'address',
                            get_string('address'));
        $mform->setType('address', PARAM_NOTAGS);

        $mform->addElement('text', 'city',
                            get_string('companycity', 'block_iomad_company_admin'),
                            'maxlength="50" size="50"');
        $mform->setType('city', PARAM_NOTAGS);
        $mform->addRule('city', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'region',
                            get_string('companyregion', 'block_iomad_company_admin'),
                            'maxlength="50" size="50"');
        $mform->setType('region', PARAM_NOTAGS);

        $mform->addElement('text', 'postcode',
                            get_string('postcode', 'block_iomad_company_admin'), ['size' => 20, 'maxlength' => 20]);
        $mform->setType('postcode', PARAM_NOTAGS);

        /* copied from user/editlib.php */
        $choices = get_string_manager()->get_list_of_countries();
        $choices = array('' => get_string('selectacountry').'...') + $choices;
        $mform->addElement('select', 'country', get_string('selectacountry'), $choices);
        $mform->addRule('country', $strrequired, 'required', null, 'client');
        if (!empty($CFG->country)) {
            $mform->setDefault('country', $CFG->country);
        }

        /* === Company email notifications === */
        $mform->addElement('header', 'manageremails', get_string('manageremails', 'block_iomad_company_admin'));
        $mform->setExpanded('manageremails', false);

        $emailchoices = array('0' => get_string('none'),
                              '1' => get_string('reminderemails', 'block_iomad_company_admin'),
                              '2' => get_string('completionemails', 'block_iomad_company_admin'),
                              '3' => get_string('allemails', 'block_iomad_company_admin'));

        $mform->addElement('select', 'managernotify', get_string('managernotify', 'block_iomad_company_admin'), $emailchoices);
        $mform->setDefault('managernotify', 0);
        $mform->addHelpButton('managernotify', 'managernotify', 'block_iomad_company_admin');

        // Add in the release frequency scheduler.
        $daysofweek = array(get_string('none'),
                            get_string('sunday', 'calendar'),
                            get_string('monday', 'calendar'),
                            get_string('tuesday', 'calendar'),
                            get_string('wednesday', 'calendar'),
                            get_string('thursday', 'calendar'),
                            get_string('friday', 'calendar'),
                            get_string('saturday', 'calendar'));

        $mform->addElement('select', 'managerdigestday', get_string('managerdigestday', 'block_iomad_company_admin'), $daysofweek);
        $mform->setDefault('managerdigestday', 0);
        $mform->addHelpButton('managerdigestday', 'managerdigestday', 'block_iomad_company_admin');

        if (iomad::has_capability('local/email:edit', $this->context)) {
            // Add in the company email template selector.
            $emailtemplates = \company::get_email_templates($this->companyid);
            if (!empty($emailtemplates[$this->previousemailtemplateid])) {
                $mform->addElement('select', 'emailtemplate', get_string('applyemailtemplate', 'block_iomad_company_admin', $emailtemplates[$this->previousemailtemplateid]), $emailtemplates);
            } else {
                $mform->addElement('select', 'emailtemplate', get_string('applyemailtemplate', 'block_iomad_company_admin', get_string('none')), $emailtemplates);
            }
            $mform->addHelpButton('emailtemplate', 'applyemailtemplate', 'block_iomad_company_admin');
        }

        // Get the company profile choices.
        $globalfields = $DB->get_records_sql_menu("SELECT id,name from {user_info_field} WHERE
                                              categoryid NOT IN (
                                                SELECT profileid from {company}
                                              )");
        if (!$this->isadding) {
            // Get the company info.
            $companyfields = $DB->get_records_sql_menu("SELECT id,name from {user_info_field} WHERE
                                                  categoryid = (
                                                    SELECT profileid from {company}
                                                    WHERE id = :companyid
                                                  )", array('companyid' => $this->companyid));
        } else {
            $companyfields = array();
        }
        $profilefields = array('0' => get_string('none')) + $globalfields + $companyfields;

        $mform->addElement('select', 'emailprofileid', get_string('emailprofileid', 'block_iomad_company_admin'), $profilefields);
        $mform->setDefault('emailprofileid', 0);
        $mform->addHelpButton('emailprofileid', 'emailprofileid', 'block_iomad_company_admin');

        /* === end company email notifications === */
         $mform->addElement('header', 'companyadvanced', get_string('companyadvanced', 'block_iomad_company_admin'));

        $mform->addElement('textarea', 'companydomains', get_string('companydomains', 'block_iomad_company_admin'), array('display' => 'noofficial'));
        $mform->setType('companydomains', PARAM_NOTAGS);
        $mform->addHelpButton('companydomains', 'companydomains', 'block_iomad_company_admin');

        // Max users is restricted.
        if (iomad::has_capability('block/iomad_company_admin:company_edit_restricted', $this->context)) {
            $mform->addElement('text', 'maxusers', get_string('companymaxusers', 'block_iomad_company_admin'));
            $mform->addHelpButton('maxusers', 'companymaxusers', 'block_iomad_company_admin');

            $mform->addElement('text', 'hostname', get_string('companyhostname', 'block_iomad_company_admin'));
            $mform->addHelpButton('hostname', 'companyhostname', 'block_iomad_company_admin');
        } else {
            $mform->addElement('hidden', 'maxusers');
            $mform->addElement('hidden', 'hostname');
        }
        $mform->setType('maxusers', PARAM_INT);
        $mform->setType('hostname', PARAM_NOTAGS);

        // Add in the company role template selector.
        $templates = \company::get_role_templates($this->companyid);
        $mform->addElement('select', 'roletemplate', get_string('applyroletemplate', 'block_iomad_company_admin', $templates[$this->previousroletemplateid]), $templates);
        $mform->addHelpButton('roletemplate', 'roletemplate', 'block_iomad_company_admin');

        if (iomad::has_capability('block/iomad_company_admin:company_add', $this->context)) {
            // Add in the template selector for the company.
            $templates = $DB->get_records_menu('company_role_templates', array(), 'name', 'id,name');
            $mform->addElement('autocomplete', 'templates', get_string('availabletemplates', 'block_iomad_company_admin'), $templates, array('multiple' => true));
            $mform->addHelpButton('templates', 'availabletemplates', 'block_iomad_company_admin');

            // Add the parent company selector.
            $companies = $DB->get_records_sql_menu("SELECT id,name FROM {company}
                                            WHERE id != :companyid
                                            ORDER by name", array('companyid' => $this->companyid));
            $allcompanies = array('0' => get_string('none')) + $companies;
            $mform->addElement('select', 'parentid', get_string('parentcompany', 'block_iomad_company_admin'), $allcompanies, array('onchange' => 'this.form.submit()'));
            $mform->setDefault('parentid', 0);
            $mform->addHelpButton('parentid', 'parentcompany', 'block_iomad_company_admin');

        } else if (iomad::has_capability('block/iomad_company_admin:company_add_child', $this->context) && !empty($this->parentcompanyid)) {
            // Add it as a hidden field.
            $mform->addElement('hidden', 'parentid', $this->parentcompanyid);
            if (!empty($this->companyrecord->templates)) {
                foreach ($this->companyrecord->templates as $companytemplateid) {
                    $mform->addElement('hidden', 'templates[' . $companytemplateid . ']', $companytemplateid);
                }
            }
        } else {
            // Add it as a hidden field.
            $mform->addElement('hidden', 'parentid');
            if (!empty($this->companyrecord->templates)) {
                foreach ($this->companyrecord->templates as $companytemplateid) {
                    $mform->addElement('hidden', 'templates[' . $companytemplateid . ']', $companytemplateid);
                }
            }
        }

        // Add the ecommerce selector.
        if (empty($CFG->commerce_admin_enableall) && iomad::has_capability('block/iomad_company_admin:company_add', $this->context)) {
            $mform->addElement('selectyesno', 'ecommerce', get_string('enableecommerce', 'block_iomad_company_admin'));
            $mform->setDefault('ecommerce', 0);
            $accounts = \core_payment\helper::get_payment_accounts_menu($systemcontext);
            if (empty($CFG->commerce_enable_external) && !empty($accounts)) {
                if (empty($this->companyrecord->paymentaccount)) {
                    $usedefaultpaymentaccountvalue = "checked";
                } else {
                    $usedefaultpaymentaccountvalue = "";
                }
                $mform->addElement('checkbox', 'usedefaultpaymentaccount', get_string('usedefaultpayment', 'block_iomad_company_admin'));
                $mform->setDefault('usedefaultpaymentaccount', $usedefaultpaymentaccountvalue);
                if ($accounts) {
                   $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
                }
                $mform->addElement('select', 'paymentaccount', get_string('paymentaccount', 'payment'), $accounts);
                $mform->hideIf('paymentaccount', 'usedefaultpaymentaccount', 'checked');
                $mform->disabledIf('paymentaccount', 'usedefaultpaymentaccount', 'checked');
            }
        } else {
            $mform->addElement('hidden', 'ecommerce');
            $mform->addElement('hidden', 'paymentaccount');
        }

        // Deal with potential custom dashboard.
        $plugins = \core_component::get_plugin_list('local');
        if (!empty($plugins['iomadcustompage'])) {
            // Get all of the custom pages the user can see.
            $iomadcustompagesql = "";
            if (!iomad::has_capability('local/iomadcustompage:editall', $this->context)) {
                $myiomadcustompages = \local_iomadcustompage\local\helpers\audience::user_pages_list();
                if (!empty($myiomadcustompages)) {
                    $iomadcustompagesql = " AND lcp.id in (" . implode(',', $myiomadcustompages) . ")";
                } else {
                    $iomadcustompagesql = " AND 1=2 ";
                }
            }

            // Only get the custom pages which are in the company context.
            $iomadcustompages = $DB->get_records_sql_menu("SELECT lcp.id,lcp.name FROM {local_iomadcustompages} lcp
                                                      JOIN {context} c ON lcp.contextid = c.id
                                                      WHERE " . $DB->sql_like('c.path', ':path') . "
                                                      $iomadcustompagesql
                                                      ORDER BY lcp.name",
                                                     ['path' => "/" . SYSCONTEXTID . "/" . $this->context->id . "/%"]);
            if (!empty($iomadcustompages)) {
                $iomadcustompages = ['0' => get_string('none', 'admin')] + $iomadcustompages;
                $mform->addElement('select', 'dashboard', get_string('customdashboard', 'block_iomad_company_admin'), $iomadcustompages);
            }
        }

        // Valid to and suspend after are restricted.
        if (iomad::has_capability('block/iomad_company_admin:company_edit_restricted', $this->context)) {
            $mform->addElement('date_time_selector', 'validto', get_string('companyvalidto', 'block_iomad_company_admin'), array('optional' => true));
            $mform->addElement('duration', 'suspendafter', get_string('companyterminateafter', 'block_iomad_company_admin'));
            $mform->addHelpButton('validto', 'companyvalidto', 'block_iomad_company_admin');
            $mform->addHelpButton('suspendafter', 'companyterminateafter', 'block_iomad_company_admin');
            $mform->disabledIF('suspendafter', 'validto[enabled]', 'notchecked');
        } else {
            $mform->addElement('hidden', 'validto');
            $mform->addElement('hidden', 'suspendafter');
            $mform->setType('validto', PARAM_INT);
            $mform->setType('suspendafter', PARAM_INT);
        }

        $mform->setType('parentid', PARAM_INT);
        $mform->setType('ecommerce', PARAM_INT);
        $mform->setType('paymentaccount', PARAM_INT);
        $mform->setType('templates', PARAM_RAW);

        // Add custom fields.
        $mform->addElement('text', 'custom1',
                            get_string('custom1', 'block_iomad_company_admin'),
                            'maxlength="255" size="50"');
        $mform->setType('custom1', PARAM_NOTAGS);
        $mform->addElement('text', 'custom2',
                            get_string('custom2', 'block_iomad_company_admin'),
                            'maxlength="255" size="50"');
        $mform->setType('custom2', PARAM_NOTAGS);
        $mform->addElement('text', 'custom3',
                            get_string('custom3', 'block_iomad_company_admin'),
                            'maxlength="255" size="50"');
        $mform->setType('custom3', PARAM_NOTAGS);

        // Add in the auto department signup profile field.
        if (!empty($this->companyid)) {
            // Get the company profile choices.
            $globalmenufields = $DB->get_records_sql_menu("SELECT id,name from {user_info_field} WHERE
                                                           datatype = :datatype
                                                           AND categoryid NOT IN (
                                                           SELECT profileid from {company}
                                                           )",
                                                           ['datatype' => 'menu']);
            $companymenufields = $DB->get_records_sql_menu("SELECT id,name from {user_info_field} WHERE
                                                            datatype = :datatype
                                                            AND categoryid = (
                                                              SELECT profileid from {company}
                                                              WHERE id = :companyid
                                                            )",
                                                            ['companyid' => $this->companyid, 'datatype' => 'menu']);

            $allmenufields = array_merge(['0' => get_string('none')], $companymenufields, $globalmenufields);
            $mform->addElement('select', 'departmentprofileid', get_string('departmentprofileid', 'block_iomad_company_admin'), $allmenufields, ['optional' => true]);
            $mform->addHelpButton('departmentprofileid', 'departmentprofileid', 'block_iomad_company_admin');
        }

        /* === User defaults === */
        $mform->addElement('header', 'userdefaults',
                            get_string('userdefaults', 'block_iomad_company_admin'));

        $choices = array();
        $choices['0'] = get_string('emaildisplayno');
        $choices['1'] = get_string('emaildisplayyes');
        $choices['2'] = get_string('emaildisplaycourse');
        $mform->addElement('select', 'maildisplay', get_string('emaildisplay'), $choices);
        $mform->setDefault('maildisplay', $CFG->defaultpreference_maildisplay);

        $choices = array();
        $choices['0'] = get_string('textformat');
        $choices['1'] = get_string('htmlformat');
        $mform->addElement('select', 'mailformat', get_string('emailformat'), $choices);
        $mform->setDefault('mailformat', $CFG->defaultpreference_mailformat);

        $choices = array();
        $choices['0'] = get_string('emaildigestoff');
        $choices['1'] = get_string('emaildigestcomplete');
        $choices['2'] = get_string('emaildigestsubjects');
        $mform->addElement('select', 'maildigest', get_string('emaildigest'), $choices);
        $mform->setDefault('maildigest', $CFG->defaultpreference_maildigest);

        $choices = array();
        $choices['1'] = get_string('autosubscribeyes');
        $choices['0'] = get_string('autosubscribeno');
        $mform->addElement('select', 'autosubscribe', get_string('autosubscribe'), $choices);
        $mform->setDefault('autosubscribe', $CFG->defaultpreference_autosubscribe);

        if (!empty($CFG->forum_trackreadposts)) {
            $choices = array();
            $choices['0'] = get_string('trackforumsno');
            $choices['1'] = get_string('trackforumsyes');
            $mform->addElement('select', 'trackforums', get_string('trackforums'), $choices);
            $mform->setDefault('trackforums', $CFG->defaultpreference_trackforums);
        }

        $editors = editors_get_enabled();
        if (count($editors) > 1) {
            $choices = array();
            $choices['0'] = get_string('texteditor');
            $choices['1'] = get_string('htmleditor');
            $mform->addElement('select', 'htmleditor', get_string('textediting'), $choices);
            $mform->setDefault('htmleditor', 1);
        } else {
            $mform->addElement('hidden', 'htmleditor');
            $mform->setDefault('htmleditor', 1);
            $mform->setType('htmleditor', PARAM_INT);
        }

        $choices = \core_date::get_list_of_timezones();
        $choices['99'] = get_string('serverlocaltime');
        if ($CFG->forcetimezone != 99) {
            $mform->addElement('static', 'forcedtimezone',
                                get_string('timezone'), $choices[$CFG->forcetimezone]);
        } else {
            $mform->addElement('select', 'timezone', get_string('timezone'), $choices);
            $mform->setDefault('timezone', '99');
        }

        $mform->addElement('select', 'lang', get_string('preferredlanguage'),
                                             get_string_manager()->get_list_of_translations());
        $mform->setDefault('lang', $CFG->lang);

        /* === end user defaults === */
        $companytheme = $this->companyrecord->theme;
        $ischild = false;
        $isiomadtheme = false;
        try {
            $theme = \theme_config::load($companytheme);
            if (preg_match('/iomad/', $companytheme) ||
                !empty($theme->isiomadtheme)) {
                $isiomadtheme = true;
            }
            $iomadthemes = array('iomad', 'iomadboost', 'iomadbootstrap');
            foreach ($theme->parents as $parentstheme) {
                if (in_array($parentstheme, $iomadthemes)){
                    $ischild = true;
                    break;
                }
            }
        } catch (Exception $e) {
            // Bad theme
        }
        // Only show the Appearence section if the theme is iomad or you have abilities
        // to change that.
        if (iomad::has_capability('block/iomad_company_admin:company_edit_appearance', $this->context)) {
            $mform->addElement('header', 'appearance',
                                    get_string('appearance', 'block_iomad_company_admin'));

            // If has the edit all companies capability we want to add a theme selector.
            if (iomad::has_capability('block/iomad_company_admin:company_add', $this->context)) {

                // Get the list of themes.
                $themes = \core_component::get_plugin_list('theme');
                $themeselectarray = array();
                foreach ($themes as $themename => $themedir) {

                    // Load the theme config.
                    try {
                        $theme = \theme_config::load($themename);
                    } catch (Exception $e) {
                        // Bad theme, just skip it for now.
                        continue;
                    }
                    if ($themename !== $theme->name) {
                        // Obsoleted or broken theme, just skip for now.
                        continue;
                    }
                    if (!$CFG->themedesignermode && $theme->hidefromselector) {
                        // The theme doesn't want to be shown in the theme selector and as theme
                        // designer mode is switched off we will respect that decision.
                        continue;
                    }

                    // Build the theme selection list.
                    $themeselectarray[$themename] = get_string('pluginname', 'theme_'.$themename);
                }
                $mform->addElement('select', 'theme',
                                    get_string('selectatheme', 'block_iomad_company_admin'),
                                    $themeselectarray);
                $mform->getElement('theme')->setSelected($companytheme);
            } else {
                $mform->addElement('hidden', 'theme', $this->companyrecord->theme);
                $mform->setType('theme', PARAM_TEXT);
            }

            // If theme is already set to a real theme, dont show this.
            if ( $isiomadtheme || $ischild) {
                $mform->addElement('HTML', get_string('theoptionsbelow',
                                                      'block_iomad_company_admin'));
                $mform->addElement('filemanager', 'companylogo',
                                    get_string('companylogo', 'block_iomad_company_admin'), null,
                                    ['maxfiles' => 1,
                                     'accepted_types' => ['.jpg', '.png']]);

                $mform->addElement('filemanager', 'companylogocompact',
                                    get_string('companylogocompact', 'block_iomad_company_admin'), null,
                                    ['subdirs' => 0,
                                     'maxbytes' => 150 * 1024,
                                     'maxfiles' => 1,
                                     'accepted_types' => ['.jpg', '.png']]);

                $mform->addElement('filemanager', 'companyfavicon',
                                    get_string('companyfavicon', 'block_iomad_company_admin'), null,
                                    ['maxfiles' => 1,
                                     'accepted_types' => ['image']]);

                $mform->addElement('textarea', 'customcss',
                                    get_string('customcss', 'block_iomad_company_admin'),
                                    'wrap="virtual" rows="20" cols="75"');
                $mform->setType('customcss', PARAM_RAW);
                $mform->addElement('iomad_colourpicker', 'headingcolor', get_string('headingcolor', 'block_iomad_company_admin'), ['size' => 20, 'maxlength' => 20]);
                $mform->setType('headingcolor', PARAM_CLEAN);
                $mform->addElement('iomad_colourpicker', 'maincolor', get_string('maincolor', 'block_iomad_company_admin'), ['size' => 20, 'maxlength' => 20]);
                $mform->setType('maincolor', PARAM_CLEAN);
                $mform->addElement('iomad_colourpicker', 'linkcolor', get_string('linkcolor', 'block_iomad_company_admin'), ['size' => 20, 'maxlength' => 20]);
                $mform->setType('linkcolor', PARAM_CLEAN);
            } else {
                $mform->addElement('hidden', 'id_companylogo', $this->companyrecord->companylogo);
                $mform->addElement('hidden', 'companylogo', $this->companyrecord->companylogo);
                $mform->setType('companylogo', PARAM_CLEAN);
                $mform->setType('id_companylogo', PARAM_CLEAN);
                $mform->addElement('hidden', 'customcss');
                $mform->setType('customcss', PARAM_RAW);
                $mform->addElement('hidden', 'headingcolor');
                $mform->setType('headingcolor', PARAM_CLEAN);
                $mform->addElement('hidden', 'maincolor');
                $mform->setType('maincolor', PARAM_CLEAN);
                $mform->addElement('hidden', 'linkcolor');
                $mform->setType('linkcolor', PARAM_CLEAN);
            }

            // Company custom menu items.
            $mform->addElement('textarea', 'custommenuitems',
                                get_string('custommenuitems', 'admin'),
                                'wrap="virtual" rows="20" cols="75"');
            $mform->setType('customcss', PARAM_RAW);
            $mform->addElement('HTML', get_string('configcustommenuitems', 'admin'));
        } else {
                $mform->addElement('hidden', 'theme', $this->companyrecord->theme);
                $mform->setType('theme', PARAM_TEXT);
                $mform->addElement('hidden', 'companylogo', $this->companyrecord->companylogo);
                $mform->setType('companylogo', PARAM_CLEAN);
                $mform->addElement('hidden', 'customcss');
                $mform->setType('customcss', PARAM_RAW);
        }

        // Only show the certificate section if you have capability.
        if (iomad::has_capability('block/iomad_company_admin:company_edit_certificateinfo', $this->context)) {
            $mform->addElement('header', 'certificatedesign', get_string('certificatedesign', 'block_iomad_company_admin'));

            $mform->addElement('advcheckbox', 'uselogo', get_string('company_uselogo', 'block_iomad_company_admin'), null, null, array(0,1));
            $mform->addElement('filemanager', 'companycertificateseal',
                                get_string('companycertificateseal', 'block_iomad_company_admin'), null,
                                array('subdirs' => 0,
                                      'maxbytes' => 150 * 1024,
                                      'maxfiles' => 1,
                                      'accepted_types' => array('*.jpg', '*.gif', '*.png')));
            $mform->disabledIf('companycertificateseal', 'uselogo');

            $mform->addElement('advcheckbox', 'usesignature', get_string('company_usesignature', 'block_iomad_company_admin'), null, null, array(0,1));
            $mform->addElement('filemanager', 'companycertificatesignature',
                                get_string('companycertificatesignature', 'block_iomad_company_admin'), null,
                                array('subdirs' => 0,
                                      'maxbytes' => 150 * 1024,
                                      'maxfiles' => 1,
                                      'accepted_types' => array('*.jpg', '*.gif', '*.png')));
            $mform->disabledIf('companycertificatesignature', 'usesignature');

            $mform->addElement('advcheckbox', 'useborder', get_string('company_useborder', 'block_iomad_company_admin'), null, null, array(0,1));
            $mform->addElement('filemanager', 'companycertificateborder',
                                get_string('companycertificateborder', 'block_iomad_company_admin'), null,
                                array('subdirs' => 0,
                                      'maxbytes' => 150 * 1024,
                                      'maxfiles' => 1,
                                      'accepted_types' => array('*.jpg', '*.gif', '*.png')));
            $mform->disabledIf('companycertificateborder', 'useborder');

            $mform->addElement('advcheckbox', 'usewatermark', get_string('company_usewatermark', 'block_iomad_company_admin'), null, null, array(0,1));
            $mform->addElement('filemanager', 'companycertificatewatermark',
                                get_string('companycertificatewatermark', 'block_iomad_company_admin'), null,
                                array('subdirs' => 0,
                                      'maxbytes' => 150 * 1024,
                                      'maxfiles' => 1,
                                      'accepted_types' => array('*.jpg', '*.gif', '*.png')));
            $mform->disabledIf('companycertificatewatermark', 'usewatermark');

            $mform->addElement('advcheckbox', 'showgrade', get_string('company_showgrade', 'block_iomad_company_admin'), null, null, array(0,1));

            $mform->addHelpButton('companycertificateseal', 'companycertificateseal', 'block_iomad_company_admin');
            $mform->addHelpButton('companycertificatesignature', 'companycertificatesignature', 'block_iomad_company_admin');
            $mform->addHelpButton('companycertificateborder', 'companycertificateborder', 'block_iomad_company_admin');
            $mform->addHelpButton('companycertificatewatermark', 'companycertificatewatermark', 'block_iomad_company_admin');
            $mform->addHelpButton('uselogo', 'company_uselogo', 'block_iomad_company_admin');
            $mform->addHelpButton('usesignature', 'company_usesignature', 'block_iomad_company_admin');
            $mform->addHelpButton('useborder', 'company_useborder', 'block_iomad_company_admin');
            $mform->addHelpButton('usewatermark', 'company_usewatermark', 'block_iomad_company_admin');
            $mform->addHelpButton('showgrade', 'company_showgrade', 'block_iomad_company_admin');
            $mform->setDefault('uselogo', 1);
            $mform->setDefault('usesignature', 1);
            $mform->setDefault('useborder', 1);
            $mform->setDefault('usewatermark', 1);
            $mform->setDefault('showgrade', 1);

        } else {
            $mform->addElement('hidden', 'companycertificateseal', $this->companyrecord->companycertificateseal);
            $mform->setType('companycertificateseal', PARAM_CLEAN);
            $mform->addElement('hidden', 'companycertificatesignature', $this->companyrecord->companycertificatesignature);
            $mform->setType('companycertificatesignature', PARAM_CLEAN);
            $mform->addElement('hidden', 'companycertificateborder', $this->companyrecord->companycertificateborder);
            $mform->setType('companycertificateborder', PARAM_CLEAN);
            $mform->addElement('hidden', 'companycertificatewatermark', $this->companyrecord->companycertificatewatermark);
            $mform->setType('companycertificatewatermark', PARAM_CLEAN);
            $mform->addElement('hidden', 'uselogo', $this->companyrecord->uselogo);
            $mform->setType('uselogo', PARAM_INT);
            $mform->addElement('hidden', 'usesignature', $this->companyrecord->usesignature);
            $mform->setType('usesignature', PARAM_INT);
            $mform->addElement('hidden', 'useborder', $this->companyrecord->useborder);
            $mform->setType('useborder', PARAM_INT);
            $mform->addElement('hidden', 'usewatermark', $this->companyrecord->usewatermark);
            $mform->setType('usewatermark', PARAM_INT);
            $mform->addElement('hidden', 'showgrade', $this->companyrecord->showgrade);
            $mform->setType('showgrade', PARAM_INT);
        }

        // Only show the Company SMTP section if you have the capability.
        if (iomad::has_capability('block/iomad_company_admin:company_edit_smtp', $this->context)) {
            $mform->addElement('header', 'companysmtpsettings', get_string('outgoingmailconfig', 'admin'));

            $mform->addElement('advcheckbox', 'usecompanysmtpsettings', get_string('company_usesmtpsettings', 'block_iomad_company_admin'), null, null, array(0,1));
            $mform->setDefault('usecompanysmtpsettings', 'checked');
            $mform->addElement('text',
                               'smtphosts' . $this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . " " .
                                             get_string('smtphosts', 'admin')));
            $mform->addElement('static', 'smtphostsdescription', '', get_string('configsmtphosts', 'admin'));
            $secureoptions = [
                              '' => get_string('none', 'admin'),
                              'ssl' => 'SSL',
                              'tls' => 'TLS',
                              ];
            $mform->addElement('select',
                               'smtpsecure' . $this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('smtpsecure', 'admin')),
                               $secureoptions);
            $mform->addElement('static', 'smtpsecuredescription', '', get_string('configsmtpsecure', 'admin'));
            $authtypeoptions = [
                                'LOGIN' => 'LOGIN',
                                'PLAIN' => 'PLAIN',
                                'NTLM' => 'NTLM',
                                'CRAM-MD5' => 'CRAM-MD5',
                              ];

            // Get all the issuers.
            $issuers = \core\oauth2\api::get_all_issuers();
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
            $authtypeoptionsselect = $mform->addElement('select',
                                                        'smtpauthtype'.$this->companyid,
                                                        format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                                                      get_string('smtpauthtype', 'admin')),
                                                        $authtypeoptions);
            $authtypeoptionsselect->setSelected('LOGIN');
            $mform->addElement('static', 'smtpauthtypedescription', '', get_string('configsmtpauthtype', 'admin'));
            if (count($enabledissuers) > 0) {
                $oauth2services = [
                    '' => get_string('none', 'admin'),
                ];
                foreach ($enabledissuers as $issuer) {
                    $oauth2services[$issuer->get('id')] = s($issuer->get('name'));
                }
                $mform->addElement('select',
                                   'smtpoauthservice'.$this->companyid,
                                   format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                                  get_string('issuer', 'auth_oauth2')),
                                   $oauth2services);
            $mform->addElement('static', 'smtpoauthservicedescription', '', get_string('configsmtpoauthservice', 'admin'));
            }
            $mform->addElement('text',
                               'smtpuser'.$this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('smtpuser', 'admin')));
            $mform->addElement('static', 'smtpuserdescription', '', get_string('configsmtpuser', 'admin'));
            $mform->addElement('passwordunmask',
                               'smtppass'.$this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('smtppass', 'admin')));
            $mform->addElement('static', 'smtppassdescription', '', get_string('configsmtpuser', 'admin'));
            $mform->addElement('text',
                               'smtpmaxbulk'.$this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('smtpmaxbulk', 'admin')));
            $mform->setDefault('smtpmaxbulk'.$this->companyid, 1);
            $mform->addElement('static', 'smtpmaxbulkdescription', '', get_string('configsmtpmaxbulk', 'admin'));
            $mform->addElement('text',
                               'noreplyaddress'.$this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('noreplyaddress', 'admin')));
            $mform->setDefault('noreplyaddress'.$this->companyid, 'noreply@' . get_host_from_url($CFG->wwwroot));
            $mform->addElement('static', 'noreplyaddressdescription', '', get_string('confignoreplyaddress', 'admin'));
            $mform->addElement('text',
                               'emaildkimselector'.$this->companyid,
                               format_string(get_string('company', 'block_iomad_company_admin') . ' ' .
                                             get_string('emaildkimselector', 'admin')));
            $mform->addElement('static', 'emaildkimselectordescription', '', get_string('configemaildkimselector', 'admin'));

            // Set the show/hide depending on checked option;
            $mform->hideIf('smtphosts' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtpsecure' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtpauthtype' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtpoauthservice' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtpuser' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtppass' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtpmaxbulk' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('noreplyaddress' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('emaildkimselector' . $this->companyid, 'usecompanysmtpsettings');
            $mform->hideIf('smtphostsdescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtpsecuredescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtpauthtypedescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtpoauthservicedescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtpuserdescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtppassdescription', 'usecompanysmtpsettings');
            $mform->hideIf('smtpmaxbulkdescription', 'usecompanysmtpsettings');
            $mform->hideIf('noreplyaddressdescription', 'usecompanysmtpsettings');
            $mform->hideIf('emaildkimselectordescription', 'usecompanysmtpsettings');
        } else {
            $mform->addElement('hidden', 'usecompanysmtpsettings');
            $mform->addElement('hidden', 'smtphosts' . $this->companyid);
            $mform->addElement('hidden', 'smtpsecure' . $this->companyid);
            $mform->addElement('hidden', 'smtpauthtype'.$this->companyid);
            $mform->addElement('hidden', 'smtpoauthservice'.$this->companyid);
            $mform->addElement('hidden', 'smtpuser'.$this->companyid);
            $mform->addElement('hidden', 'smtppass'.$this->companyid);
            $mform->addElement('hidden', 'smtpmaxbulk'.$this->companyid);
            $mform->addElement('hidden', 'noreplyaddress'.$this->companyid);
            $mform->addElement('hidden', 'emaildkimselector'.$this->companyid);
        }
        $mform->setType('usecompanysmtpsettings', PARAM_BOOL);
        $mform->setType('smtphosts' . $this->companyid, PARAM_RAW);
        $mform->setType('smtpsecure' . $this->companyid, PARAM_TEXT);
        $mform->setType('smtpauthtype'.$this->companyid, PARAM_TEXT);
        $mform->setType('smtpoauthservice'.$this->companyid, PARAM_INT);
        $mform->setType('smtpuser'.$this->companyid, PARAM_NOTAGS);
        $mform->setType('smtppass'.$this->companyid, PARAM_RAW);
        $mform->setType('smtpmaxbulk'.$this->companyid, PARAM_INT);
        $mform->setType('noreplyaddress'.$this->companyid, PARAM_EMAIL);
        $mform->setType('emaildkimselector'.$this->companyid, PARAM_FILE);

        $submitlabel = null; // Default.
        if ($this->isadding) {
            $submitlabel = get_string('saveasnewcompany', 'block_iomad_company_admin');
            $mform->addElement('hidden', 'createnew', 1);
            $mform->setType('createnew', PARAM_INT);
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $this->add_action_buttons(true, $submitlabel);
    }

    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->title = '';
            $data->description = '';

            if ($this->title) {
                $data->title = $this->title;
            }

            if ($this->description) {
                $data->description = $this->description;
            }
        }
        return $data;
    }

    // Perform some extra moodle validation.
    public function validation($data, $files) {
        global $DB, $CFG, $SESSION;

        $errors = parent::validation($data, $files);
        if ($data['parentid'] != $data['currentparentid']) {
            $SESSION->current_editing_company_data = $data;
            $createnew = !empty($data['createnew']) ? true : false;
            $redirectparams = ['createnew' => $createnew,
                               'parentid' => $data['parentid'],
                               'parentchanged' => true];
            if ($data['parentid'] == 0 && !empty($data['companyid'])) {
                 $redirectparams['createnew'] = false;
            }
            redirect(new moodle_url('/blocks/iomad_company_admin/company_edit_form.php', $redirectparams));
            die;
        }

        if ($foundcompanies = $DB->get_records('company', array('name' => $data['name']))) {
            if (!empty($this->companyid)) {
                unset($foundcompanies[$this->companyid]);
            }
            if (!empty($foundcompanies)) {
                foreach ($foundcompanies as $foundcompany) {
                    $foundcompanynames[] = $foundcompany->name;
                }
                $foundcompanynamestring = implode(',', $foundcompanynames);
                $errors['name'] = get_string('companynametaken',
                                            'block_iomad_company_admin', $foundcompanynamestring);
            }
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $data['shortname'])) {
            // Check allowed pattern (numbers, letters and underscore).
            $errors['shortname'] = get_string('invalidshortnameerror', 'core_customfield');
        } else if ($foundcompanies = $DB->get_records('company', array('shortname' => trim($data['shortname'])))) {
            if (!empty($this->companyid)) {
                unset($foundcompanies[$this->companyid]);
            }
            if (!empty($foundcompanies)) {
                foreach ($foundcompanies as $foundcompany) {
                    $foundcompanyshortnames[] = $foundcompany->shortname;
                }
                $foundcompanynamestring = implode(',', $foundcompanyshortnames);
                $errors['shortname'] = get_string('companyshortnametaken',
                                                 'block_iomad_company_admin',
                                                  $foundcompanynamestring);
            }
        }

        if (!empty($data['code']) &&
            $foundcompanies = $DB->get_records('company', array('code' => $data['code']))) {
            if (!empty($this->companyid)) {
                unset($foundcompanies[$this->companyid]);
            }
            if (!empty($foundcompanies)) {
                foreach ($foundcompanies as $foundcompany) {
                    $foundcompanycodes[] = $foundcompany->code;
                }
                $foundcompanynamestring = implode(',', $foundcompanycodes);
                $errors['code'] = get_string('companycodetaken',
                                                 'block_iomad_company_admin',
                                                  $foundcompanynamestring);
            }
        }

        if (!empty($data['hostname']) && $foundcompanies = $DB->get_records('company', array('hostname' => $data['hostname']))) {
            if (!empty($this->companyid)) {
                unset($foundcompanies[$this->companyid]);
            }
            if (!empty($foundcompanies)) {
                foreach ($foundcompanies as $foundcompany) {
                    $foundcompanyhostnames[] = $foundcompany->hostname;
                }
                $foundcompanynamestring = implode(',', $foundcompanyhostnames);
                $errors['hostname'] = get_string('companyhostnametaken',
                                                 'block_iomad_company_admin',
                                                  $foundcompanynamestring);
            }
        }

        if ($data['maxusers'] < 0) {
            $errors['maxusers'] = get_string('invalidnum', 'error');
        }

        return $errors;
    }
}
