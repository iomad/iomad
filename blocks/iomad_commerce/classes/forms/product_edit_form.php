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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\forms;

use block_iomad_commerce\helper;
use context;
use core\notification;
use core_form\dynamic_form;
use lang_string;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use moodle_url;

/**
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class product_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $mycompanyid = $this->optional_param('mycompanyid', 0, PARAM_INT);
        $productid = $this->optional_param('productid', 0, PARAM_INT);
        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => false,
            'noclean' => true,
        ];

        // Get the supported currencies.
        $codes = \core_payment\helper::get_supported_currencies();
        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }
        uasort($currencies, function($a, $b) {
            return strcmp($a, $b);
        });

        // Get the available information.
        if ($companyid == 0) {
            $courses = $DB->get_records_sql_menu(
                 "SELECT c.id, c.fullname
                    FROM {course} c
                    JOIN {local_iomad_courses} ic ON (c.id = ic.courseid)
                ORDER BY c.fullname");
            $paths = [];
        } else {
            $company = new company($mycompanyid);
            $courses = $company->get_menu_courses(true, false);
            $paths = $DB->get_records_sql_menu(
                "SELECT ilp.id, ilp.name
                   FROM {block_iomad_learningpath} ilp
                  WHERE ilp.companyid = :companyid
                    AND ilp.active = 1
                    AND ilp.id IN (
                        SELECT pathid
                          FROM {block_iomad_learningpath_courses} ilpc
                         WHERE ilp.id = ilpc.pathid)",
                ['companyid' => $companyid]);
        }

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'mycompanyid');
        $mform->setType('mycompanyid', PARAM_INT);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'default');
        $mform->setType('default', PARAM_BOOL);
        $mform->addElement('hidden', 'deletedBlockPrices', 0);
        $mform->setType('deletedBlockPrices', PARAM_INT);

        // Adding the elements in the definition_after_data function rather than in the definition function
        // so that when the currentcourses or potentialcourses get changed in the process function, the
        // changes get displayed, rather than the lists as they are before processing.

        if (!empty($courses)) {
            $mform->addElement('text', 'name', get_string('name'));
            $mform->setType('name', PARAM_NOTAGS);

            $mform->addElement('selectyesno', 'enabled', get_string('course_shop_enabled', 'block_iomad_commerce'));
            $mform->addHelpButton('enabled', 'course_shop_enabled', 'block_iomad_commerce');

            $mform->addElement('autocomplete', 'itemcourses', get_string('courses'), $courses, ['multiple' => true]);

            // Create an element for learning paths.
            $mform->addElement('autocomplete',
                               'itempaths',
                               get_string('learning_paths', 'block_iomad_commerce'),
                               $paths,
                               ['multiple' => true]);

            $mform->addElement('editor', 'short_summary_editor', get_string('course_short_summary', 'block_iomad_commerce'),
                                          null, $editoroptions);
            $mform->setType('short_summary_editor', PARAM_RAW);
            $mform->addRule('short_summary_editor', get_string('missingshortsummary', 'block_iomad_commerce'),
                                                    'required', null, 'client');

            $mform->addElement('editor', 'summary_editor', get_string('course_long_description', 'block_iomad_commerce'),
                                          null, $editoroptions);
            $mform->setType('summary_editor', PARAM_RAW);

            // Set the license details.
            if (get_config('local_iomad', 'autoenrol_managers')) {
                $licensetypes = [0 => get_string('standard', 'block_iomad_company_admin'),
                                 1 => get_string('reusable', 'block_iomad_company_admin'),
                                 4 => get_string('blanket', 'block_iomad_company_admin')];
            } else {
                $licensetypes = [0 => get_string('standard', 'block_iomad_company_admin'),
                                 1 => get_string('reusable', 'block_iomad_company_admin'),
                                 2 => get_string('educator', 'block_iomad_company_admin'),
                                 3 => get_string('educatorreusable', 'block_iomad_company_admin'),
                                 4 => get_string('blanket', 'block_iomad_company_admin')];
            }

            $mform->addElement('select', 'type', get_string('licensetype', 'block_iomad_company_admin'), $licensetypes);
            $mform->addHelpButton('type', 'licensetype', 'block_iomad_company_admin');

            $mform->addElement('selectyesno', 'program', get_string('licenseprogram', 'block_iomad_company_admin'));
            $mform->addHelpButton('program', 'licenseprogram', 'block_iomad_company_admin');

            $mform->addElement('selectyesno', 'instant', get_string('licenseinstant', 'block_iomad_company_admin'));
            $mform->addHelpButton('instant', 'licenseinstant', 'block_iomad_company_admin');

            // Disable things depending on license type.
            $mform->disabledIf('program', 'type', 'eq', 4);
            $mform->disabledIf('instant', 'type', 'eq', 4);

            $mform->addElement('duration', 'single_purchase_validlength',
                                        get_string('single_purchase_validlength', 'block_iomad_commerce'),
                                        ['defaultunit' => DAYSECS]);
            $mform->addHelpButton('single_purchase_validlength', 'single_purchase_validlength', 'block_iomad_commerce');

            $mform->addElement('duration', 'single_purchase_shelflife',
                                        get_string('single_purchase_shelflife', 'block_iomad_commerce'),
                                        ['defaultunit' => DAYSECS]);
            $mform->addHelpButton('single_purchase_shelflife', 'single_purchase_shelflife', 'block_iomad_commerce');

            $mform->addElement('duration', 'cutofftime',
                                        get_string('licensecutoffdate', 'block_iomad_company_admin'),
                                        ['optional' => true, 'defaultunit' => DAYSECS]);
            $mform->addHelpButton('cutofftime', 'licensecutoffdate', 'block_iomad_company_admin');

            $mform->addElement('advcheckbox', 'clearonexpire', get_string('clearonexpire', 'block_iomad_company_admin'));

            $mform->addHelpButton('clearonexpire', 'clearonexpire', 'block_iomad_company_admin');
            $mform->disabledIf('clearonexpire', 'cutoffdate[enabled]');

            $mform->addElement('select', 'currency', get_string('currency'), $currencies);

            $mform->addElement('header', 'header', get_string('single_purchase', 'block_iomad_commerce'));

            $mform->addElement('selectyesno', 'allow_single_purchase', get_string('allow_single_purchase', 'block_iomad_commerce'));
            $mform->addHelpButton('allow_single_purchase', 'allow_single_purchase', 'block_iomad_commerce');

            $mform->addElement('text', 'single_purchase_price',
                                        get_string('single_purchase_price', 'block_iomad_commerce'));
            $mform->addRule('single_purchase_price',
                             get_string('decimalnumberonly', 'block_iomad_commerce'), 'numeric');
            $mform->disabledIf('single_purchase_price', 'allow_single_purchase', 'eq', 0);

            $mform->setType('single_purchase_price', PARAM_TEXT);
            $mform->addHelpButton('single_purchase_price', 'single_purchase_price', 'block_iomad_commerce');

            /****** license blocks *********/
            $mform->addElement('header', 'header', get_string('licenseblocks', 'block_iomad_commerce'));

            $licenseblockarray = [
                $mform->createElement('html', '<tr><td style="text-align: right;">'),
                $mform->createElement('text', 'item_block_start'),
                $mform->createElement('html', '</td><td style="text-align: right;">'),
                $mform->createElement('text', 'item_block_price'),
                $mform->createElement('html', '</td></tr>'),
            ];

            // Set the default number to be repeated.
            if ($repeatno = $DB->count_records('block_iomad_commerce_product_blockprices', ['itemid' => $productid])) {
                $repeatno++;
            } else {
                $repeatno = 1;
            }

            // Set up the options for the repeated item.
            $repeatoptions = ['item_block_start' => ['rule' => 'numeric', 'type' => PARAM_INT],
                              'item_block_price' => ['rule' => 'numeric', 'type' => PARAM_LOCALISEDFLOAT]];

            $mform->addElement('html', '<table id="licenseblockstable" class="generaltable" width="95%">' .
                                        '<tr>
                                            <th style="text-align: right;">' .
                                                get_string('licenseblock_start', 'block_iomad_commerce') .
                                           '</th>
                                            <th style="text-align: right;">' .
                                                get_string('licenseblock_price', 'block_iomad_commerce') .
                                            '</th>
                                        </tr>');
            $this->repeat_elements($licenseblockarray,
                                   $repeatno,
                                   $repeatoptions,
                                   'option_repeats',
                                   'option_add_fields',
                                   1,
                                   null,
                                   true);
            $mform->addElement('html', '</table>');

            /******** tags **************/
            $mform->addElement('header', 'header', get_string('categorization', 'block_iomad_commerce'));

            $mform->addElement('textarea',
                               'tags',
                               get_string('tags', 'block_iomad_commerce'),
                               ['rows' => 5,
                                'cols' => 60]);
            $mform->addHelpButton('tags', 'tags', 'block_iomad_commerce');
            $mform->setType('tags', PARAM_NOTAGS);

            $vars = helper::get_shop_tags(true);
            $options = "<option value=''>" . get_string('select_tag', 'block_iomad_commerce') . "</option>";
            foreach ($vars as $i) {
                $options .= "<option value='{$i}'>$i</option>";
            }

            $select = "<select class='tags custom-select' onchange='iomad.onSelectTag(this)'>$options</select>";
            $html = "<div class='fitem'><div class='fitemtitle'></div><div class='felement'>$select</div></div>";

            $mform->addElement('html', $html);

            /******** end tags **********/

        } else {
            $mform->addElement('html', get_string('nocoursesnotontheshop', 'block_iomad_commerce'));
        }
    }

    /**
     * Form get data function
     *
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            if ($data->short_summary_editor) {
                $data->short_description = $data->short_summary_editor["text"];
            }
            if ($data->summary_editor) {
                $data->long_description = $data->summary_editor["text"];
            }
        }

        return $data;
    }

    /**
     * Form validation function
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['allow_single_purchase']) {
            if (floatval($data['single_purchase_price']) <= 0) {
                $errors['single_purchase_price'] = get_string('error_singlepurchaseprice', 'block_iomad_commerce');
            }
            if (intval($data['single_purchase_validlength']) <= 0) {
                $errors['single_purchase_validlength'] = get_string('error_singlepurchasevalidlength', 'block_iomad_commerce');
            }
        }

        if (!empty($data['allow_single_purchase']) && empty($data['program']) && count($data['itemcourses']) > 1) {
            $errors['allow_single_purchase'] = get_string('error_incompatibletype', 'block_iomad_commerce');
        }

        if (count($data['itemcourses']) == 0 && count($data['itempaths']) == 0) {
            $errors['itempaths'] = get_string('requiredcoursepath', 'block_iomad_commerce');
        }

        if (count($data['itemcourses']) > 0 && count($data['itempaths']) > 0) {
            $errors['itempaths'] = get_string('onlyonecoursepath', 'block_iomad_commerce');
        }

        if (!empty($data['item_block_start'][0]) && $data['item_block_start'][0] > 2) {
            $errors['item_block_start[0]'] = get_string('error_invalidlicensenumber', 'block_iomad_commerce');
        }

        for ($i = 0; $i < count($data['item_block_start']); $i++) {
            if (!empty($data['item_block_start'][$i]) &&
                !isset($data['item_block_price'][$i])) {
                $errors['item_block_price['.$i.']'] = get_string('error_invalidlicenseprice', 'block_iomad_commerce');
            }
        }

        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $USER;

        // Get the info from the form.
        $product = $this->get_data();
        $product->userid = $USER->id;

        // Process it.
        $dorefresh = false;
        if (helper::update_product($product)) {
            if (empty($product->id)) {
                if (!empty($product->companyid)) {
                    $returnmessage = get_string('itemaddedsuccessfully', 'block_iomad_commerce');
                } else {
                    $returnmessage = get_string('templatecreatedok', 'block_iomad_commerce');
                }
                $dorefresh = true;
            } else {
                if (!empty($product->companyid)) {
                    $returnmessage = get_string('productupdatedok', 'block_iomad_commerce');
                } else {
                    $returnmessage = get_string('templateupdatedok', 'block_iomad_commerce');
                }
            }
            $result = true;
            notification::success($returnmessage);
        } else {
            if (empty($product->id)) {
                if (!empty($product->companyid)) {
                    $returnmessage = get_string('productcreatefailed', 'block_iomad_commerce');
                } else {
                    $returnmessage = get_string('templatecreatefailed', 'block_iomad_commerce');
                }
            } else {
                if (!empty($product->companyid)) {
                    $returnmessage = get_string('productupdatefailed', 'block_iomad_commerce');
                } else {
                    $returnmessage = get_string('templateupdatefailed', 'block_iomad_commerce');
                }
            }
            $result = false;
            notification::error($returnmessage);
        }

        // Return stuff to the JS.
        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
            'dorefresh' => $dorefresh,
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $mycompanyid = $this->optional_param('mycompanyid', 0, PARAM_INT);
        $productid = $this->optional_param('productid', 0, PARAM_INT);
        $companycontext = context_company::instance($mycompanyid);

        // Check we can do these things.
        if ($companyid == 0) {
            iomad::require_capability('block/iomad_commerce:manage_default', $companycontext);
        } else if ($productid == 0) {
            iomad::require_capability('block/iomad_commerce:add_course', $companycontext);
        } else {
            iomad::require_capability('block/iomad_commerce:edit_course', $companycontext);
        }

        // Do we have an existing record?
        if (!$product = $DB->get_record('block_iomad_commerce_products', ['id' => $productid])) {
            $product = (object) [
                'id' => $productid,
                'companyid' => $companyid,
            ];
        }

        // Get the rest of the information.
        helper::populate_product($product);
        $product->mycompanyid = $mycompanyid;

        // Send it.
        $this->set_data($product);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $productid = $this->optional_param('productid', 0, PARAM_INT);
        $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/courselist.php');
        $context = $this->get_context_for_dynamic_submission();

        // Check we can do these things.
        if ($companyid == 0) {
            if (!iomad::has_capability('block/iomad_commerce:manage_default', $context)) {
                throw new moodle_exception(
                    'nopermissions',
                    '',
                    $returnurl->out(),
                    get_string(
                        'block/iomad_commerce:manage_default',
                        'block_iomad_commerce'
                    )
                );
            }
        } else if ($productid == 0) {
            if (!iomad::has_capability('block/iomad_commerce:add_course', $context)) {
                throw new moodle_exception(
                    'nopermissions',
                    '',
                    $returnurl->out(),
                    get_string(
                        'block/iomad_commerce:add_course',
                        'block_iomad_commerce'
                    )
                );
            }
        } else {
            if (!iomad::has_capability('block/iomad_commerce:edit_course', $context)) {
                throw new moodle_exception(
                    'nopermissions',
                    '',
                    $returnurl->out(),
                    get_string(
                        'block/iomad_commerce:edit_course',
                        'block_iomad_commerce'
                    )
                );
            }
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('mycompanyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_commerce/courselist.php');
    }
}
