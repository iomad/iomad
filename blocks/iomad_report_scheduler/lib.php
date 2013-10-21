<?php

require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/local/iomad/lib/user.php');

require_once(dirname(__FILE__) . '/../../local/iomad/lib/blockpage.php');
require_once('lib/user_selectors.php');
require_once('lib/course_selectors.php');

/**
 * moodleform subclass that includes simple method for adding company select box
 */
abstract class company_moodleform extends moodleform {
    protected $selectedcompany = 0;

    function add_company_selector ($required=true) {
        $mform =& $this->_form;
        
        if ( company_user::is_company_user() ) {
            $mform->addElement('hidden', 'companyid', company_user::companyid());
        } else {
            $companies = company::get_companies_rs();
            $company_options = array('' => get_string('selectacompany', 'block_iomad_company_admin'));
            foreach ($companies as $company) {
                if ( company_user::can_see_company( $company->shortname ) ) {
                    $company_options[$company->id] = $company->name;
                }
            }
            $companies->close();
            
            if ( count($company_options) == 1 ) {
                $mform->addElement('html', get_string('nocompanies', 'block_iomad_company_admin'));
                return false;
            } else {
                $mform->addElement('select', 'companyid', get_string('company', 'block_iomad_company_admin'), $company_options);
                if ($required) {
                    $mform->addRule('companyid', get_string('missingcompany', 'block_iomad_company_admin'), 'required', null, 'client');
                }

                $defaultValues['companyid'] = array($this->selectedcompany);
                $mform->setDefaults($defaultValues);
            }
        }
        return true;
    }
    
    function add_course_selector($multiselect = true, $rows = 20, $displayevenifnocourses = true) {
        $mform =& $this->_form;

        // course selector
        if ( $this->selectedcompany || company_user::is_company_user() ) {
            $courseselector = new current_company_course_selector('courses', array('companyid' => $this->selectedcompany, 'multiselect' => $multiselect));
        } else {
            $courseselector = new any_course_selector('courses', array('multiselect' => $multiselect));
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
    
    // this is very loosely based on the admin_setting_configcolourpicker class in adminlib.php
    function add_colour_picker($name, $previewconfig) {
        global $PAGE, $OUTPUT;
        $mform =& $this->_form;
        $id = "id_" . $name;

        // $cptemplate is adapted from the 'default' template in formslib.php's MoodleQuickForm_Renderer function in MoodleQuickForm_Renderer class.
        // it is adds a {colourpicker} and {preview} tag that is replaced with the $colourpicker and $preview variables below before being passed to the renderer
        // the {advancedimg} {help} bits have been taken out as the rendered doesn't appear to use them in this case
        $cptemplate = "\n\t\t".'<div class="fitem {advanced}<!-- BEGIN required --> required<!-- END required -->"><div class="fitemtitle"><label>{label}<!-- BEGIN required -->{req}<!-- END required --></label></div><div class="felement {type}<!-- BEGIN error --> error<!-- END error -->">{colourpicker}<!-- BEGIN error --><span class="error">{error}</span><br /><!-- END error -->{element}{preview}</div></div>';
        
        // $colourpicker contains the colour picker bits that are to be displayed above the input box
        $colourpicker = html_writer::start_tag('div', array('class'=>'form-colourpicker defaultsnext'));
        $colourpicker .= html_writer::tag('div', $OUTPUT->pix_icon('i/loading', get_string('loading', 'admin'), 'moodle', array('class'=>'loadingicon')), array('class'=>'admin_colourpicker clearfix'));

        // preview contains the bits that are to be displayed below the input box (may just be a div end tag)
        $preview = '';
        if (!empty($previewconfig)) {
            $preview .= html_writer::empty_tag('input', array('type'=>'button','id'=>$id.'_preview', 'value'=>get_string('preview'), 'class'=>'admin_colourpicker_preview'));
        }
        $preview .= html_writer::end_tag('div');

        // replace {colourpicker} and {preview} in $cptemplate        
        $cptemplate = preg_replace('/\{colourpicker\}/', $colourpicker, $cptemplate);
        $cptemplate = preg_replace('/\{preview\}/', $preview, $cptemplate);

        // add the input element to the form
        $PAGE->requires->js_init_call('M.util.init_colour_picker', array($id, $previewconfig));
        $mform->addElement('text', $name, get_string($name, 'block_iomad_company_admin'), array('size' => 7, 'maxlength' => 7));
        $mform->defaultRenderer()->setElementTemplate($cptemplate,$name);
        $mform->setType('shortname', PARAM_NOTAGS);
        $mform->addRule($name, get_string('css_color_format', 'block_iomad_company_admin'), 'regex', '/^#([A-F0-9]{3}){1,2}$/i');
    }
}

/**
 * Form to use as company selector on company_managers_form and company_courses_form
 */
class company_select_form extends company_moodleform {
    protected $title = '';
    protected $description = '';
    protected $submitlabel = null;

    function __construct($actionurl, $companyid, $submitlabelstring) {
        $this->selectedcompany = $companyid;

        $this->submitlabel = get_string($submitlabelstring, 'block_iomad_company_admin');

        parent::moodleform($actionurl);
    }

    function definition() {
        global $PAGE, $USER;
        
        if ( !company_user::is_company_user() ) {
            $mform =& $this->_form;

            // Then show the fields about where this block appears.
            $mform->addElement('header', 'header', get_string('company', 'block_iomad_company_admin'));

            if ($this->add_company_selector()) {
                // only display buttons if user doesnt use javascript
                if ($USER->ajax != 1) {
                    $this->add_action_buttons(true, $this->submitlabel);
                }
                
                // Make form auto submit on change of selected company
                $formid = $mform->getAttribute("id");
                $PAGE->requires->js_init_call('M.util.init_select_autosubmit', array($formid, "id_companyid", null));
            }
        }
    }
}