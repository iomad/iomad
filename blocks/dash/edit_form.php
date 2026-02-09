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
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_dash\local\data_source\data_source_factory;

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dash_edit_form extends block_edit_form {

    /**
     * Add form fields.
     *
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     */
    protected function specific_definition($mform) {
        global $CFG;
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'dashconfigheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_dash'));
        $mform->setType('config_title', PARAM_TEXT);

        $this->add_datasource_group($mform, $this->block->config);

        $mform->addElement('header', 'headerfooter', get_string('headerfooter', 'block_dash'));

        $mform->addElement('editor', 'config_header_content', get_string('headercontent', 'block_dash'));
        $mform->setType('config_header_content', PARAM_RAW);
        $mform->addHelpButton('config_header_content', 'headercontent', 'block_dash');

        $mform->addElement('editor', 'config_footer_content', get_string('footercontent', 'block_dash'));
        $mform->setType('config_footer_content', PARAM_RAW);
        $mform->addHelpButton('config_footer_content', 'footercontent', 'block_dash');

        $mform->addElement('header', 'apperance', get_string('appearance'));

        $mform->addElement('select', 'config_showheader', get_string('showheader', 'block_dash'), [
            0 => get_string('hidden', 'block_dash'),
            1 => get_string('visible'),
        ]);
        $mform->setType('config_showheader', PARAM_INT);
        $mform->setDefault('config_showheader', get_config('block_dash', 'showheader'));
        $mform->addHelpButton('config_showheader', 'showheader', 'block_dash');

        $mform->addElement('select', 'config_width', get_string('blockwidth', 'block_dash'), [
            100 => '100',
            50 => '1/2',
            33 => '1/3',
            66 => '2/3',
            25 => '1/4',
            20 => '1/5',
            16 => '1/6',
        ]);
        $mform->setType('config_width', PARAM_INT);
        $mform->setDefault('config_width', 100);

        $mform->addElement('select', 'config_hide_when_empty', get_string('hidewhenempty', 'block_dash'), [
            0 => get_string('no'),
            1 => get_string('yes'),
        ]);

        $mform->setType('config_hide_when_empty', PARAM_INT);
        $mform->setDefault('config_hide_when_empty', get_config('block_dash', 'hide_when_empty'));

        $attributes['tags'] = true;
        $attributes['multiple'] = 'multiple';
        $attributes['placeholder'] = get_string('enterclasses', 'block_dash');

        $cssclassses = explode(',', get_config('block_dash', 'cssclass'));
        $cssclassses = array_combine($cssclassses, $cssclassses);
        $mform->addElement('autocomplete', 'config_css_class', get_string('cssclass', 'block_dash'), $cssclassses, $attributes);
        $mform->setType('config_css_class', PARAM_TEXT);
        $mform->addHelpButton('config_css_class', 'cssclass', 'block_dash');

        $mform->addElement('filemanager', 'config_backgroundimage', get_string('backgroundimage', 'block_dash'), null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image'], 'return_types' => FILE_INTERNAL | FILE_EXTERNAL]);
        $mform->addHelpButton('config_backgroundimage', 'backgroundimage', 'block_dash');

        $postions = [
            'initial' => get_string('initial', 'block_dash'),
            'left top' => get_string('lefttop', 'block_dash'),
            'left center' => get_string('leftcenter', 'block_dash'),
            'left bottom' => get_string('leftbottom', 'block_dash'),
            'right top' => get_string('righttop', 'block_dash'),
            'right center' => get_string('rightcenter', 'block_dash'),
            'right bottom' => get_string('rightbottom', 'block_dash'),
            'center top' => get_string('centertop', 'block_dash'),
            'center center' => get_string('centercenter', 'block_dash'),
            'center bottom' => get_string('centerbottom', 'block_dash'),
            'custom' => get_string('strcustom', 'block_dash'),
        ];
         // Module background image poisiton.
         $mform->addElement('select', 'config_backgroundimage_position', get_string('backgroundposition', 'block_dash'),
         $postions);
         $mform->setType('config_backgroundimage_position', PARAM_RAW);
         $mform->addHelpButton('config_backgroundimage_position', 'backgroundposition', 'block_dash');

         // Module background image custom poisiton.
         $mform->addElement('text', 'config_backgroundimage_customposition',
             get_string('designercustombgposition', 'block_dash'));
         $mform->setType('config_backgroundimage_customposition', PARAM_RAW);
         $mform->addHelpButton('config_backgroundimage_customposition', 'backgroundposition', 'block_dash');
         $mform->hideIf('config_backgroundimage_customposition', 'config_backgroundimage_position', 'neq', 'custom');

        // Module background image size.
        $sizes = [
            'auto' => get_string('auto', 'block_dash'),
            'cover' => get_string('cover', 'block_dash'),
            'contain' => get_string('contain', 'block_dash'),
            'custom' => get_string('strcustom', 'block_dash'),
        ];
        $mform->addElement('select', 'config_backgroundimage_size', get_string('backgroundsize',
            'block_dash'), $sizes);
        $mform->setType('config_backgroundimage_size', PARAM_RAW);
        $mform->addHelpButton('config_backgroundimage_size', 'backgroundsize', 'block_dash');

        // Module background image custom size.
        $mform->addElement('text', 'config_backgroundimage_customsize', get_string('designercustombgsize', 'block_dash'));
        $mform->setType('config_backgroundimage_customsize', PARAM_RAW);
        $mform->addHelpButton('config_backgroundimage_customsize', 'backgroundsize', 'block_dash');
        $mform->hideIf('config_backgroundimage_customsize', 'config_backgroundimage_size', 'neq', 'custom');

        require_once($CFG->dirroot.'/blocks/dash/form/gradientpicker.php');
        MoodleQuickForm::registerElementType('dashgradientpicker', $CFG->dirroot.'/blocks/dash/form/gradientpicker.php',
            'moodlequickform_dashgradientpicker');

        $mform->addElement('dashgradientpicker', 'config_backgroundgradient', get_string('backgroundgradient', 'block_dash'),
            ['placeholder' => 'linear-gradient(#e66465, #9198e5)']);
        $mform->setType('config_backgroundgradient', PARAM_TEXT);
        $mform->addHelpButton('config_backgroundgradient', 'backgroundgradient', 'block_dash');

        require_once($CFG->dirroot.'/blocks/dash/form/element-colorpicker.php');
        MoodleQuickForm::registerElementType('dashcolorpicker', $CFG->dirroot.'/blocks/dash/form/element-colorpicker.php',
            'moodlequickform_dashcolorpicker');

        $mform->addElement('dashcolorpicker', 'config_headerfootercolor', get_string('fontcolor', 'block_dash'));
        $mform->setType('config_headerfootercolor', PARAM_RAW);
        $mform->addHelpButton('config_headerfootercolor', 'fontcolor', 'block_dash');

        $mform->addElement('select', 'config_border_option', get_string('border_option', 'block_dash'), [
            0 => get_string('hidden', 'block_dash'),
            1 => get_string('visible'),
        ]);
        $mform->setType('config_border_option', PARAM_INT);
        $mform->setDefault('config_border_option', 1);
        $mform->addHelpButton('config_border_option', 'border_option', 'block_dash');

        $mform->addElement('text', 'config_border', get_string('bordervalue', 'block_dash'));
        $mform->setType('config_border', PARAM_TEXT);
        $mform->addHelpButton('config_border', 'border', 'block_dash');
        $mform->hideIf('config_border', 'config_border_option', 'eq', 0);

        $mform->addElement('text', 'config_css[min-height]', get_string('minheight', 'block_dash'));
        $mform->setType('config_css[min-height]', PARAM_TEXT);
        $mform->addHelpButton('config_css[min-height]', 'minheight', 'block_dash');

        $mform->addElement('header', 'emptystateheading', get_string('emptystateheading', 'block_dash'));

        $mform->addElement('editor', 'config_emptystate', get_string('content'), ['rows' => 5]);
        $mform->setType('config_emptystate', PARAM_CLEANHTML);

        // Restrict access.
        $mform->addElement('header', 'restrictaccessheading', get_string('restrictaccessheading', 'block_dash'));

        // Operator for restrict access.
        $strrequired = get_string('required');
        $mform->addElement('select', 'config_restrict_operator', get_string('restrict_operator', 'block_dash'), [
            1 => get_string('any'),
            2 => get_string('all'),
        ]);
        $mform->addRule('config_restrict_operator', $strrequired, 'required', null, 'client');

        // Add by cohorts as autocomplete element.
        $cohortslist = \cohort_get_all_cohorts(0, 0);
        $cohortoptions = $cohortslist['cohorts'];
        if ($cohortoptions) {
            array_walk($cohortoptions, function(&$value) {
                $value = $value->name;
            });
        }
        $bycohortswidget = $mform->addElement('autocomplete', 'config_restrict_cohorts',
            get_string('restrictbycohort', 'block_dash'), $cohortoptions);
        $bycohortswidget->setMultiple(true);
        $mform->addHelpButton('config_restrict_cohorts', 'restrictbycohort', 'block_dash');

        // Add by roles as autocomplete element.
        $rolelist = role_get_names(\context_system::instance());
        $roleoptions = [];
        foreach ($rolelist as $role) {
            if ($role->archetype !== 'frontpage') {
                $roleoptions[$role->id] = $role->localname;
            }
        }
        $byroleswidget = $mform->addElement('autocomplete', 'config_restrict_roles', get_string('restrictbyrole', 'block_dash'),
                $roleoptions);
        $byroleswidget->setMultiple(true);
        $mform->addHelpButton('config_restrict_roles', 'restrictbyrole', 'block_dash');

        // Add context as select element.
        $rolecontext = [
            1 => get_string('any'),
            2 => get_string('coresystem'),
        ];
        $mform->addElement('select', 'config_restrict_rolecontext', get_string('restrictrolecontext', 'block_dash'), $rolecontext);
        $mform->setDefault('config_restrict_rolecontext', 1);
        $mform->setType('config_restrict_rolecontext', PARAM_INT);
        $mform->addHelpButton('config_restrict_rolecontext', 'restrictrolecontext', 'block_dash');

        // Course restrictions.
        $context = $this->page->context->contextlevel;
        if ($context == CONTEXT_COURSE || $context == CONTEXT_MODULE) {
            // Course groups.
            $groupslist = groups_get_all_groups($this->page->course->id);
            $groupoptions = [];
            foreach ($groupslist as $group) {
                    $groupoptions[$group->id] = $group->name;
            }
            $bygroupswidget = $mform->addElement('autocomplete', 'config_restrict_groups',
                get_string('restrictbygroup', 'block_dash'), $groupoptions);
            $bygroupswidget->setMultiple(true);
            $mform->addHelpButton('config_restrict_groups', 'restrictbygroup', 'block_dash');

            // Course completion status.
            $completionoptions = [
                'notenrolled' => get_string('status:notenrolled', 'block_dash'),
                'enrolled' => get_string('status:enrolled', 'block_dash'),
                'inprogress' => get_string('inprogress'),
                'completed' => get_string('completed'),
            ];
            $bycompletionwidget = $mform->addElement('autocomplete', 'config_restrict_coursecompletion',
                get_string('restrictbycoursecompletion', 'block_dash'), $completionoptions);
            $bycompletionwidget->setMultiple(true);
            $mform->addHelpButton('config_restrict_coursecompletion', 'restrictbycoursecompletion', 'block_dash');

            // Course grade.
            $graderange = [
                'none' => get_string('none'),
                'lowerthan' => get_string('lowerthan', 'block_dash'),
                'higherthan' => get_string('higherthan', 'block_dash'),
                'between' => get_string('between', 'block_dash'),
            ];
            $mform->addElement('select', 'config_restrict_graderange', get_string('restrictbygrade', 'block_dash'), $graderange);
            $mform->addHelpButton('config_restrict_graderange', 'restrictbygrade', 'block_dash');

            require_once($CFG->dirroot.'/blocks/dash/form/element-range.php');
            MoodleQuickForm::registerElementType('dashrange', $CFG->dirroot.'/blocks/dash/form/element-range.php',
            'moodlequickform_dashrange');

            $mform->addElement('dashrange', 'config_restrict_grademin', '');
            $mform->setType('config_restrict_grademin', PARAM_INT);
            $mform->addRule('config_restrict_grademin', null, 'numeric', null, 'client');
            $mform->hideIf('config_restrict_grademin', 'config_restrict_graderange', 'eq', 'none');

            $mform->addElement('dashrange', 'config_restrict_grademax', '');
            $mform->setType('config_restrict_grademax', PARAM_INT);
            $mform->addRule('config_restrict_grademax', null, 'numeric', null, 'client');
            $mform->hideIf('config_restrict_grademax', 'config_restrict_graderange', 'neq', 'between');

            // Activity completion status.
            $completionoptions = [
                'none' => get_string('none'),
                'incomplete' => get_string('incomplete', 'block_dash'),
                'complete' => get_string('complete'),
                'passed' => get_string('passed', 'block_dash'),
                'failed' => get_string('failed', 'block_dash'),
            ];
            $mform->addElement('select', 'config_restrict_activitycompletion',
                get_string('restrictbyactivitycompletion', 'block_dash'), $completionoptions);
            $mform->addHelpButton('config_restrict_activitycompletion', 'restrictbyactivitycompletion', 'block_dash');

            if ($context != CONTEXT_MODULE) {
                // Include the activities for the restrict access.
                $completion = new \completion_info(get_course($this->page->course->id));
                $activities = $completion->get_activities();
                array_walk($activities, function(&$value) {
                    $value = format_string($value->name);
                });

                $byactivitycompletion = $mform->addElement('autocomplete', 'config_restrict_modules',
                                    get_string('selectactivity', 'block_dash'), $activities);
                $byactivitycompletion->setMultiple(true);
                $mform->addHelpButton('config_restrict_modules', 'selectactivity', 'block_dash');
            }
        }

        $widgetlist = data_source_factory::get_data_source_form_options('widget');
        foreach ($widgetlist as $id => $source) {
            if (method_exists($id, 'extend_config_form')) {
                $id::extend_config_form($mform, $source, $this);
                $showcustom = true;
            }
        }
    }

    /**
     * Add available data source groups.
     *
     * @param moodleform $mform
     * @param stdclass $config
     * @return void
     */
    public function add_datasource_group(&$mform, $config) {
        global $OUTPUT;

        $label[] = $mform->createElement('html', html_writer::start_div('datasource-content heading'));
        $label[] = $mform->createElement('html', html_writer::end_div());

        $mform->addGroup($label, 'datasources_label', get_string('choosefeature', 'block_dash'), [' '], false);
        $mform->setType('datasources_label', PARAM_TEXT);

        if (!isset($config->data_source_idnumber)) {

            self::dash_features_list($mform, $this->block->context, $this->page);
            $mform->addElement('hidden', 'config_dash_configure_options', 1);
            $mform->setType('config_dash_configure_options', PARAM_INT);

        } else {
            if ($ds = data_source_factory::build_data_source($config->data_source_idnumber,
                $this->block->context)) {
                $label = $ds->get_name();
            } else {
                $label = get_string('datasourcemissing', 'block_dash');
            }
            $datalabel = ($ds && $ds->is_widget())
            ? get_string('widget', 'block_dash') : get_string('datasource', 'block_dash');

            $mform->addElement('static', 'data_source_label', $datalabel.' : ', $label);
        }
    }

    /**
     * Data features list.
     *
     * @param \moodleform $mform
     * @param \context $context
     * @param \moodle_page $page
     * @return void
     */
    public static function dash_features_list(&$mform, $context, $page) {
        global $OUTPUT;
        // Group of datasources.
        if (has_capability('block/dash:managedatasource', $context)) {
            $datasources = data_source_factory::get_data_source_form_options();
            // Description of the datasources.
            $group[] = $mform->createElement('html',
                html_writer::tag('p', get_string('datasourcedesc', 'block_dash'), ['class' => 'dash-source-desc']));

            $group[] = $mform->createElement('html', html_writer::start_div('datasource-content'));
            foreach ($datasources as $id => $source) {
                if (block_dash_visible_addons($id)) {
                    $group[] = $mform->createElement('html', html_writer::start_div('datasource-item'));
                    $group[] = $mform->createElement('radio', 'config_data_source_idnumber', '', $source['name'], $id);
                    if ($help = $source['help']) {
                        $helpcontent = $OUTPUT->help_icon($help['name'], $help['component'], $help['name']);
                        $group[] = $mform->createElement('html', $helpcontent);
                    }
                    $group[] = $mform->createElement('html', html_writer::end_div());
                }
            }
            $group[] = $mform->createElement('html', html_writer::end_div());
            $mform->addGroup($group, 'datasources', get_string('buildown', 'block_dash'), [' '], false);
            $mform->setType('datasources', PARAM_TEXT);
            $mform->addHelpButton('datasources', 'buildown', 'block_dash');
        }

        // Widgets data source.
        if (has_capability('block/dash:managewidget', $context)) {
            $widgetlist = data_source_factory::get_data_source_form_options('widget');
            $widgets[] = $mform->createElement('html',
                html_writer::tag('p', get_string('widgetsdesc', 'block_dash'), ['class' => 'dash-source-desc']));
            $widgets[] = $mform->createElement('html', html_writer::start_div('datasource-content'));
            foreach ($widgetlist as $id => $source) {
                if (block_dash_visible_addons($id)) {
                    $widgets[] = $mform->createElement('html', html_writer::start_div('datasource-item'));
                    $widgets[] = $mform->createElement('radio', 'config_data_source_idnumber', '', $source['name'], $id);
                    if ($source['help']) {
                        $widgets[] = $mform->createElement('html', $OUTPUT->help_icon($source['help'], 'block_dash',
                            $source['help']));
                    }
                    $widgets[] = $mform->createElement('html', html_writer::end_div());
                }
            }
            $widgets[] = $mform->createElement('html', html_writer::end_div());
            $mform->addGroup($widgets, 'widgets', get_string('readymatewidgets', 'block_dash'), [' '], false);
            $mform->setType('widgets', PARAM_TEXT);
            $mform->addHelpButton('widgets', 'readymatewidgets', 'block_dash');
        }

        // Content layout.
        $customfeatures = data_source_factory::get_data_source_form_options('custom');
        if ($customfeatures) {
            foreach ($customfeatures as $id => $source) {
                if ($id::has_capbility($context)) {
                    $id::get_features_config($mform, $source);
                    $showcustom = true;
                }
            }
            if (isset($showcustom)) {

                $page->requires->js_amd_inline('require(["jquery"], function($) {
                        $("body").on("change", "[data-target=\"subsource-config\"] [type=radio]", function(e) {
                            var subConfig;
                            if (subConfig = e.target.closest("[data-target=\"subsource-config\"]")) {
                                if (subConfig.parentNode !== null) {
                                    var dataSource = subConfig.parentNode.querySelector("[name=\"config_data_source_idnumber\"]");
                                    dataSource.click(); // = true;
                                }
                            }
                        });
                    })'
                );
            }
        }
    }

    /**
     * Display the configuration form when block is being added to the page
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return false;
    }
}

/**
 * Dash features form to configure the data source or widget.
 */
class block_dash_featuresform extends \moodleform {

    /**
     * Defined the form fields for the datasource selector list.
     *
     * @return void
     */
    public function definition() {
        // @codingStandardsIgnoreStart
        global $PAGE;
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        // @codingStandardsIgnoreEnd

        $mform = $this->_form;

        $mform->updateAttributes(['class' => 'form-inline']);
        $mform->updateAttributes(['id' => 'dash-configuration']);

        $block = $this->_customdata['block'] ?? '';
        // @codingStandardsIgnoreStart
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        block_dash_edit_form::dash_features_list($mform, $block, $PAGE);
        // @codingStandardsIgnoreEnd
    }
}
