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
 * Template edit form definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\forms;

use html_writer;
use local_iomad\{company, emailvars};
use moodleform;

/**
 * Template edit form definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_edit_form extends moodleform {

    /** @var bool are we adding a template */
    protected $isadding;

    /** @var bool are we editing a template */
    protected $isediting;

    /** @var int template id */
    protected $templateid;

    /** @var object template record*/
    protected $templaterecord;

    /** @var int company id */
    protected $companyid;

    /** @var object company record */
    protected $company;

    /** @var int templateset id */
    protected $templatesetid;

    /** @var array list of company managers */
    protected $companymanagers;

    /** @var array list of company managers */
    protected $multiplecompanymanagers;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param bool $isadding
     * @param bool $isediting
     * @param int $companyid
     * @param int $templateid
     * @param object $templaterecord
     * @param int $templatesetid
     */
    public function __construct($actionurl, $isadding, $isediting, $companyid, $templateid, $templaterecord, $templatesetid) {
        $this->isadding = $isadding;
        $this->isediting = $isediting;
        $this->templateid = $templateid;
        $this->templaterecord = $templaterecord;
        $this->companyid = $companyid;
        $this->templatesetid = $templatesetid;
        $this->company = new company($companyid);
        $this->companymanagers = $this->company->get_managers_select();
        $this->multiplecompanymanagers = $this->companymanagers;
        unset($this->multiplecompanymanagers[0]);
        if (!empty($isadding)) {
            $this->isediting = $isadding;
        }
        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;

        // Set up the form.
        $mform =& $this->_form;

        $strrequired = get_string('required');
        $submitlabel = get_string('save');
        if ($this->isadding) {
            $submitlabel = get_string('save_to_override_default_template', 'local_iomad');
        }

        $buttonarr = [];
        $buttonarr[] = &$mform->createElement(
            'html',
            html_writer::tag(
                'span',
                html_writer::tag(
                    'button',
                    get_string('edit'),
                    [
                        'class' => 'btn btn-secondary emailclicktoedit',
                        'name' => 'edit',
                        'id' => 'id_edit',
                        'type' => 'button',
                    ]
                ),
                [
                    'data-fieldtype' => 'button',
                ]
            ));
        $buttonarr[] = &$mform->createElement('submit', 'save', $submitlabel);
        $buttonarr[] = &$mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarr, 'buttonar', '', [' '], false);

        $mform->addElement('hidden', 'templateid', $this->templateid);
        $mform->addElement('hidden', 'templatename', $this->templaterecord->name);
        $mform->addElement('hidden', 'companyid', $this->companyid);
        $mform->addElement('hidden', 'templatesetid', $this->templatesetid);
        $mform->addElement('hidden', 'templatestringid', $this->templatesetid);
        $mform->addElement('hidden', 'isediting', $this->isediting, ['id' => 'isediting']);
        $mform->setType('isediting', PARAM_INT);
        $mform->setType('templateid', PARAM_INT);
        $mform->setType('templatestringid', PARAM_INT);
        $mform->setType('companyid', PARAM_INT);
        $mform->setType('templatesetid', PARAM_INT);
        $mform->setType('templatename', PARAM_CLEAN);

        if (empty($this->isadding)) {
            $mform->addElement('hidden', 'lang', $this->templaterecord->lang);
            $mform->setType('lang', PARAM_LANG);
        } else {
            // We only want the languages where there isn't already a definition.
            $langs = get_string_manager()->get_list_of_translations();
            $languages = $DB->get_records_sql(
                "SELECT DISTINCT ets.lang
                 FROM {local_iomad_email_templates} et
                 JOIN {local_iomad_email_template_strings} ets ON (et.id = ets.templateid)
                 WHERE et.companyid = :companyid
                 AND et.name = :name",
                ['companyid' => $this->companyid,
                 'name' => $this->templaterecord->name]);
            unset($langs['en']);
            foreach ($languages as $language) {
                unset($langs[$language->lang]);
            }

            // Add the select.
            $mform->addElement('select', 'lang', get_string('language'), $langs);
        }

        // Set up some default arrays.
        $arrayauto = ['multiple' => true];
        $arraytext = ['size' => 100];

        $mform->addElement('autocomplete',
                           'emailto',
                           get_string('to', 'local_iomad'),
                           $this->multiplecompanymanagers,
                           $arrayauto);

        $mform->addElement('text', 'emailtoother', get_string('toother', 'local_iomad'),
                            $arraytext);
        $mform->setType('emailtoother', PARAM_EMAIL);

        $mform->addElement('autocomplete',
                           'emailfrom',
                           get_string('from', 'local_iomad'),
                           $this->companymanagers);

        $mform->addElement('text', 'emailfromother', get_string('fromother', 'local_iomad'), $arraytext);
        $mform->setType('emailfromother', PARAM_EMAIL);

        $mform->addElement('text', 'emailfromothername', get_string('fromothername', 'local_iomad'), $arraytext);
        $mform->setType('emailfromothername', PARAM_TEXT);
        $mform->setDefault('emailfromothername', '{Company_Name}');

        $mform->addElement('autocomplete',
                           'emailcc',
                           get_string('cc', 'local_iomad'),
                           $this->multiplecompanymanagers,
                           $arrayauto);

        $mform->addElement('text', 'emailccother', get_string('ccother', 'local_iomad'), $arraytext);
        $mform->setType('emailccother', PARAM_EMAIL);

        $mform->addElement('autocomplete',
                           'emailreplyto',
                           get_string('replyto', 'local_iomad'),
                           $this->companymanagers);

        $mform->addElement('text', 'emailreplytoother', get_string('replytoother', 'local_iomad'), $arraytext);
        $mform->setType('emailreplytoother', PARAM_EMAIL);

        $mform->addElement('text',
                           'subject',
                           get_string('subject', 'local_iomad'),
                           ['size' => 100,
                            'class' => 'inputholder']);
        $mform->setType('subject', PARAM_NOTAGS);
        $mform->addRule('subject', $strrequired, 'required');

        $mform->addElement('editor',
                           'body_editor',
                           get_string('body', 'local_iomad'),
                           ['enable_filemanagement' => false,
                            'changeformat' => false,
                            'class' => 'fitem_id_body_editor']);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', $strrequired, 'required');
        $mform->setType('body_editor', PARAM_RAW);

        $vars = emailvars::vars();
        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'emailvars']));
        $optioncount = 0;
        foreach ($vars as $option) {
            if ($optioncount > 10) {
                $break = "<br>";
                $optioncount = 0;
            } else {
                $break = "&nbsp";
            }
            $mform->addElement(
                'html',
                html_writer::tag(
                    'a',
                    $option,
                    [
                        'href' => '#',
                        'data-text' => $option,
                        'class' => 'clickforword',
                        ]
                    ) . $break);
            $optioncount++;
        }
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('editor',
                           'signature_editor',
                           get_string('signature', 'local_iomad'),
                           ['enable_filemanagement' => false,
                            'changeformat' => false,
                            'class' => 'fitem_id_signature_editor']);
        $mform->setType('signature_editor', PARAM_RAW);
        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'emailvars']));
        $optioncount = 0;
        foreach ($vars as $option) {
            if ($optioncount > 10) {
                $break = "<br>";
                $optioncount = 0;
            } else {
                $break = "&nbsp";
            }
            $mform->addElement(
                'html',
                html_writer::tag(
                    'a',
                    $option,
                    [
                        'href' => '#',
                        'data-text' => $option,
                        'class' => 'clickforword',
                        ]
                    ) . $break);
            $optioncount++;
        }
        $mform->addElement('html', html_writer::end_tag('div'));

        // Add in repeation parts.
        $repeatperiods = [
            '99' => get_string('always'),
            '0' => get_string('never'),
            '1' => get_string('daily', 'local_iomad'),
            '2' => get_string('weekly', 'local_iomad'),
            '3' => get_string('fortnightly', 'local_iomad'),
            '4' => get_string('monthly', 'local_iomad'),
        ];

        $repeatdays = [
            '99' => get_string('any'),
            '0' => get_string('sunday', 'calendar'),
            '1' => get_string('monday', 'calendar'),
            '2' => get_string('tuesday', 'calendar'),
            '3' => get_string('wednesday', 'calendar'),
            '4' => get_string('thursday', 'calendar'),
            '5' => get_string('friday', 'calendar'),
            '6' => get_string('saturday', 'calendar'),
        ];

        $repeatselect = $mform->addElement('select',
                                           'repeatperiod',
                                           get_string('emailrepeatperiod', 'local_iomad'),
                                           $repeatperiods);
        $repeatselect->setSelected($this->templaterecord->repeatperiod);

        $mform->addElement('text', 'repeatvalue', get_string('emailrepeatvalue', 'local_iomad'));
        $mform->setType('repeatvalue', PARAM_INT);

        $repeatdayselect = $mform->addElement('select',
                                              'repeatday',
                                              get_string('emailrepeatday', 'local_iomad'),
                                              $repeatdays);
        $repeatdayselect->setSelected($this->templaterecord->repeatday - 1);

        $mform->addHelpButton('repeatperiod', 'emailrepeatperiod', 'local_iomad');
        $mform->addHelpButton('repeatvalue', 'emailrepeatvalue', 'local_iomad');
        $mform->addHelpButton('repeatday', 'emailrepeatday', 'local_iomad');

        $mform->addElement('html', html_writer::tag('div',
                                                    get_string('emailrepeatinfo', 'local_iomad'),
                                                    ['class' => 'fdescription required']));

        // Disable everything unless isediting = 1.
        $mform->disabledIf('emailto', 'isediting', 'neq', 1);
        $mform->disabledIf('emailtoother', 'isediting', 'neq', 1);
        $mform->disabledIf('emailfrom', 'isediting', 'neq', 1);
        $mform->disabledIf('emailfromother', 'isediting', 'neq', 1);
        $mform->disabledIf('emailfromothername', 'isediting', 'neq', 1);
        $mform->disabledIf('emailcc', 'isediting', 'neq', 1);
        $mform->disabledIf('emailccother', 'isediting', 'neq', 1);
        $mform->disabledIf('emailreplyto', 'isediting', 'neq', 1);
        $mform->disabledIf('emailreplytoother', 'isediting', 'neq', 1);
        $mform->disabledIf('subject', 'isediting', 'neq', 1);
        $mform->disabledIf('body_editor', 'isediting', 'neq', 1);
        $mform->disabledIf('signature_editor', 'isediting', 'neq', 1);
        $mform->disabledIf('save', 'isediting', 'neq', 1);
        $mform->disabledIf('edit', 'isediting', 'eq', 1);
        $mform->disabledIf('repeatperiod', 'isediting', 'neq', 1);
        $mform->disabledIf('repeatvalue', 'isediting', 'neq', 1);
        $mform->disabledIf('repeatday', 'isediting', 'neq', 1);
        $mform->disabledIf('repeatvalue', 'repeatperiod', 'eq', 99);
        $mform->disabledIf('repeatvalue', 'repeatperiod', 'eq', 0);
        $mform->disabledIf('repeatday', 'repeatperiod', 'eq', 0);

        if ($this->isadding) {
            $mform->addElement('hidden', 'createnew', 1);
            $mform->setType('createnew', PARAM_INT);
        }

        $mform->addGroup($buttonarr, 'buttonar', '', [' '], false);
    }

    /**
     * Get the form data
     *
     * @return object
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            if ($data->body_editor) {
                $data->body = $data->body_editor;
            }
        }

        return $data;
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        if (!empty($data['emailfromother']) && empty($data['emailfromothername'])) {
            $errors['emailfromother'] = get_string('required');
        }

        return $errors;
    }
}
