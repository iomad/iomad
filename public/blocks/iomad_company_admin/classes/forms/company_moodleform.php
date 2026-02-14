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
 * IOMAD Dashboard company moodle form abstract class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use local_iomad\{company, company_user};
use local_iomad\course_selector\{any, current_company};
use moodleform;

/**
 * IOMAD Dashboard company moodle form abstract class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class company_moodleform extends moodleform {

    /** @var int company ID */
    protected $selectedcompany = 0;

    /**
     * Add a company selector to a form
     *
     * @param boolean $required
     * @return void
     */
    public function add_company_selector($required=true) {

        // Set up the form.
        $mform =& $this->_form;

        if (company_user::is_company_user() ) {
            $mform->addElement('hidden', 'companyid', company_user::companyid());
        } else {
            $companies = company::get_companies_rs();
            $companyoptions = ['' => get_string('selectacompany', 'block_iomad_company_admin')];
            foreach ($companies as $company) {
                if ( company_user::can_see_company( $company->shortname ) ) {
                    $companyoptions[$company->id] = $company->name;
                }
            }
            $companies->close();

            if (count($companyoptions) == 1) {
                $mform->addElement('html', get_string('nocompanies', 'block_iomad_company_admin'));
                return false;
            } else {
                $mform->addElement('select', 'companyid', get_string('company', 'block_iomad_company_admin'), $companyoptions);
                if ($required) {
                    $mform->addRule('companyid', get_string('missingcompany', 'block_iomad_company_admin'),
                                    'required', null, 'client');
                }

                $defaultvalues['companyid'] = [$this->selectedcompany];
                $mform->setDefaults($defaultvalues);
            }
        }
        return true;
    }

    /**
     * Add a course selector to a form
     *
     * @param boolean $multiselect
     * @param integer $rows
     * @param boolean $displayevenifnocourses
     * @return void
     */
    public function add_course_selector($multiselect = true, $rows = 20, $displayevenifnocourses = true) {

        // Set up the form.
        $mform =& $this->_form;

        // Course selector.
        if ( $this->selectedcompany || company_user::is_company_user() ) {
            $courseselector = new current_company('courses', ['companyid' => $this->selectedcompany,
                                                              'multiselect' => $multiselect,
                                                              'departmentid' => $this->departmentid]);
        } else {
            $courseselector = new any('courses', ['multiselect' => $multiselect,
                                                  'departmentid' => $this->departmentid]);
        }
        $courseselector->set_rows($rows);

        if ( $multiselect ) {
            $label = get_string('selectenrolmentcourses', 'block_iomad_company_admin');
        } else {
            $label = get_string('selectenrolmentcourse', 'block_iomad_company_admin');
        }

        $hascourses = true;
        if (!$displayevenifnocourses) {
            $hascourses = count($courseselector->find_courses(''));
        }

        if ($hascourses) {
            $mform->addElement('html', "<div class='fitem'><div class='fitemtitle'>" . $label . "</div><div class='felement'>");
            $mform->addElement('html', $courseselector->display(true));
            $mform->addElement('html', "</div></div>");

            return $courseselector;
        }

        return false;
    }

    /**
     * Add a colour picker to a form
     *
     * @param string $name
     * @param bool $previewconfig
     * @return void
     */
    public function add_colour_picker($name, $previewconfig) {
        global $PAGE, $OUTPUT;

        // Set up the form.
        $mform =& $this->_form;

        $id = "id_" . $name;

        // Variable $cptemplate is adapted from the 'default' template in formslib.php's MoodleQuickForm_Renderer
        // function in MoodleQuickForm_Renderer class.
        // It is adds a {colourpicker} and {preview} tag that is replaced with the $colourpicker and $preview
        // variables below before being passed to the renderer the {advancedimg} {help} bits have been taken
        // out as the rendered doesn't appear to use them in this case.
        $cptemplate = "\n\t\t".'<div class="fitem {advanced}<!-- BEGIN required --> required<!-- END required -->">
                       <div class="fitemtitle"><label>{label}<!-- BEGIN required -->{req}<!-- END required -->
                       </label></div><div class="felement {type}<!-- BEGIN error --> error<!-- END error -->">
                       {colourpicker}<!-- BEGIN error --><span class="error">{error}</span><br />
                       <!-- END error -->{element}{preview}</div></div>';

        // Variable $colourpicker contains the colour picker bits that are to be displayed above the input box.
        $colourpicker = html_writer::start_tag('div', ['class' => 'form-colourpicker defaultsnext']);
        $colourpicker .= html_writer::tag('div', $OUTPUT->pix_icon('i/loading', get_string('loading', 'admin'),
                                          'moodle', ['class' => 'loadingicon']),
                                          ['class' => 'admin_colourpicker clearfix']);

        // Preview contains the bits that are to be displayed below the input box (may just be a div end tag).
        $preview = '';
        if (!empty($previewconfig)) {
            $preview .= html_writer::empty_tag('input', ['type' => 'button',
                                                         'id' => $id.'_preview',
                                                         'value' => get_string('preview'),
                                                         'class' => 'admin_colourpicker_preview']);
        }
        $preview .= html_writer::end_tag('div');

        // Replace {colourpicker} and {preview} in $cptemplate.
        $cptemplate = preg_replace('/\{colourpicker\}/', $colourpicker, $cptemplate);
        $cptemplate = preg_replace('/\{preview\}/', $preview, $cptemplate);

        // Add the input element to the form.
        $PAGE->requires->js_init_call('M.util.init_colour_picker', [$id, $previewconfig]);
        $mform->addElement('text', $name, get_string($name, 'block_iomad_company_admin'), ['size' => 7, 'maxlength' => 7]);
        $mform->defaultRenderer()->setElementTemplate($cptemplate, $name);
        $mform->setType('shortname', PARAM_NOTAGS);
        $mform->addRule($name, get_string('css_color_format', 'block_iomad_company_admin'), 'regex', '/^#([A-F0-9]{3}){1,2}$/i');
    }
}
