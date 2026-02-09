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
 * Extend this class when creating new layouts.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\data\strategy\data_strategy_interface;
use block_dash\local\data_grid\data\strategy\standard_strategy;
use block_dash\local\data_grid\field\attribute\context_attribute;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\paginator;
use block_dash\local\data_source\data_source_interface;
use block_dash\local\data_source\form\preferences_form;
use html_writer;
use moodle_url;

/**
 * Extend this class when creating new layouts.
 *
 * Then register the layout in a lib.php function: pluginname_register_layouts(). See blocks/dash/lib.php for an
 * example.
 *
 * @package block_dash
 */
abstract class abstract_layout implements layout_interface, \templatable {

    /**
     * @var int Used for creating unique checkbox controller group IDs.
     */
    private static $currentgroupid = null;

    /**
     * The data source used as a data/configuration source for this layout.
     *
     * @var data_source_interface
     */
    private $datasource;

    /**
     * Layout constructor.
     *
     * @param data_source_interface $datasource
     */
    public function __construct(data_source_interface $datasource) {
        $this->datasource = $datasource;
    }

    /**
     * If the layout supports field sorting.
     *
     * @return mixed
     */
    public function supports_sorting() {
        return false;
    }

    /**
     * If the layout supports options.
     */
    public function supports_download() {
        return false;
    }

    /**
     * Get the data source used as a data/configuration source for this layout.
     *
     * @return data_source_interface
     */
    public function get_data_source() {
        return $this->datasource;
    }

    /**
     * Get data strategy.
     *
     * @return data_strategy_interface
     */
    public function get_data_strategy() {
        return new standard_strategy();
    }

    /**
     * Modify objects before data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     */
    public function before_data() {

    }

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {

    }

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the data source. When a user chooses a layout, specific form elements may be
     * displayed after a quick refresh of the form.
     *
     * Be sure to call parent::build_preferences_form() if you override this method.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        global $OUTPUT;

        self::$currentgroupid = random_int(1, 10000);

        $filtercollection = $this->get_data_source()->get_filter_collection();

        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            if ($this->supports_field_visibility()) {
                $group = [];
                foreach ($this->get_data_source()->get_sorted_fields() as $availablefield) {
                    if ($availablefield->has_attribute(identifier_attribute::class)) {
                        continue;
                    }

                    if ($availablefield->has_attribute(context_attribute::class)) {
                        continue;
                    }

                    $fieldname = 'config_preferences[available_fields][' . $availablefield->get_alias() .
                        '][visible]';

                    $title = $availablefield->get_table()->get_title();

                    $icon = $OUTPUT->pix_icon('i/dragdrop', get_string('dragitem', 'block_dash'), 'moodle',
                        ['class' => 'drag-handle']);
                    $title = $icon . '<b>' . $title . '</b>: ' . $availablefield->get_title();

                    $totaratitle = block_dash_is_totara() ? $title : null;
                    $group[] = $mform->createElement('advcheckbox', $fieldname, $title, $totaratitle, [
                        'group' => self::$currentgroupid, // For legacy add_checkbox_controller().
                        'data-togglegroup' => 'group' . self::$currentgroupid, // For checkbox_toggleall.
                        'data-toggle' => 'slave', // For checkbox_toggleall.
                        'data-action' => 'toggle', // For checkbox_toggleall.
                    ]);
                    $mform->setType($fieldname, PARAM_BOOL);
                }
                $mform->addGroup($group, 'available_fields', get_string('enabledfields', 'block_dash'),
                    [''], false);

                $this->add_checkbox_toggleall(self::$currentgroupid, $form, $mform);

                self::$currentgroupid++;
            }
        }

        if ($this->supports_filtering()) {
            if ($form->get_tab() == preferences_form::TAB_FILTERS) {
                $mform->addElement('static', 'filterslabel', '', '<b>' . get_string('enabledfilters', 'block_dash') . '</b>');
                $filtercollection->build_settings_form($form, $mform, 'filter', 'config_preferences[filters][%s]');
            }
        }

        if ($form->get_tab() == preferences_form::TAB_CONDITIONS) {
            $mform->addElement('static', 'conditionslabel', '', '<b>' . get_string('enabledconditions', 'block_dash') . '</b>');
            $filtercollection->build_settings_form($form, $mform, 'condition', 'config_preferences[filters][%s]');
        }

        if (!$this->supports_filtering() && $form->get_tab() == preferences_form::TAB_FILTERS) {
            $mform->addElement('html', $OUTPUT->notification(get_string('layoutdoesnotsupportfiltering', 'block_dash'), 'warning'));
        }
    }

    /**
     * Add button to select/deselect all checkboxes in group.
     *
     * @param string $uniqueid
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    private function add_checkbox_toggleall($uniqueid, \moodleform $form, \MoodleQuickForm $mform) {
        global $OUTPUT;

        if (class_exists('\core\output\checkbox_toggleall')) {
            $masterbutton = new \core\output\checkbox_toggleall('group' . $uniqueid, true, [], true);

            // Then you can export for template.
            $mform->addElement('static', 'toggleall' . $uniqueid, '', $OUTPUT->render($masterbutton));
        } else {
            // Moodle 3.7 and earlier support.
            $form->add_checkbox_controller($uniqueid);
        }
    }

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        return $preferences;
    }

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(\renderer_base $output) {
        global $OUTPUT, $PAGE;

        $config = $this->get_data_source()->get_block_instance()->config;
        $noresulttxt = \html_writer::tag('p', get_string('noresults'), ['class' => 'text-muted']);

        $templatedata = [
            'error' => '',
            'paginator' => '',
            'data' => null,
            'uniqueid' => uniqid(),
            'is_totara' => block_dash_is_totara(),
            'bootstrap3' => get_config('block_dash', 'bootstrap_version') == 3,
            'bootstrap4' => get_config('block_dash', 'bootstrap_version') == 4,
            'noresult' => (isset($config->emptystate))
                ? format_text($config->emptystate['text'], FORMAT_HTML, ['noclean' => true]) : $noresulttxt,
        ];

        if (!empty($this->get_data_source()->get_all_preferences())) {
            try {
                $templatedata['data'] = $this->get_data_source()->get_data();
            } catch (\Exception $e) {
                $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
                if (is_siteadmin()) {
                    $error .= \html_writer::tag('p', $e->getMessage());
                }

                $templatedata['error'] .= $OUTPUT->notification($error, 'error');
            }

            if ($this->get_data_source()->get_paginator()->get_page_count() > 1) {
                $templatedata['paginator'] = $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_data_source()
                    ->get_paginator()
                    ->export_for_template($OUTPUT));
            }
        }

        $layout = isset($config->preferences['layout']) ? $config->preferences['layout'] : '';
        $formhtml = $this->get_data_source()->get_filter_collection()->create_form_elements('', $layout);
        // Get downloads butttons.
        $downloadcontent = '';
        if ($this->supports_download() && $this->get_data_source()->get_preferences('exportdata')) {
            $downloadoptions = [];
            $options = [];
            $downloadlist = '';
            $options['sesskey'] = sesskey();
            $options["download"] = "csv";
            $button = $OUTPUT->single_button(new moodle_url($PAGE->url, $options), get_string("downloadcsv", 'block_dash'), 'get');
            $downloadoptions[] = html_writer::tag('li', $button, ['class' => 'reportoption list-inline-item']);

            $options["download"] = "xls";
            $button = $OUTPUT->single_button(new moodle_url($PAGE->url, $options), get_string("downloadexcel"), 'get');
            $downloadoptions[] = html_writer::tag('li', $button, ['class' => 'reportoption list-inline-item']);

            $downloadlist .= html_writer::tag('ul', implode('', $downloadoptions), ['class' => 'list-inline inline']);
            $downloadlist .= html_writer::tag('div', '', ['class' => 'clearfloat']);
            $downloadcontent .= html_writer::tag('div', $downloadlist, ['class' => 'downloadreport mt-1']);
        }

        if (!is_null($templatedata['data'])) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'downloadcontent' => $downloadcontent,
                'supports_filtering' => $this->supports_filtering(),
                'supports_download' => $this->supports_download(),
                'supports_pagination' => $this->supports_pagination(),
                'preferences' => $this->process_preferences($this->get_data_source()->get_all_preferences()),
            ]);
        }
        return $templatedata;
    }

    /**
     * Map data.
     *
     * @param array $mapping
     * @param data_collection_interface $datacollection
     * @return data_collection_interface
     */
    protected function map_data($mapping, data_collection_interface $datacollection) {
        foreach ($mapping as $newname => $fieldname) {
            if ($fieldname && !is_array($fieldname) && isset($datacollection[$fieldname])) {
                $datacollection->add_data(new field($newname, $datacollection[$fieldname], true));
            } else if ($fieldname && is_array($fieldname)) {
                $value = array_map(function($field) use ($datacollection) {
                    return $datacollection[$field];
                }, $fieldname);
                $datacollection->add_data(new field($newname, implode(" ", $value), true));
            }
        }
        return $datacollection;
    }

    /**
     * Returns supported icons.
     *
     * @return array
     */
    protected function get_icon_list() {
        global $PAGE;

        $icons = [];

        if (isset($PAGE->theme->iconsystem)) {
            if ($iconsystem = \core\output\icon_system::instance($PAGE->theme->iconsystem)) {
                if ($iconsystem instanceof \core\output\icon_system_fontawesome) {
                    foreach ($iconsystem->get_icon_name_map() as $pixname => $faname) {
                        $icons[$faname] = $pixname;
                    }
                }
            }
        } else if (block_dash_is_totara()) {
            foreach (\core\output\flex_icon_helper::get_icons($PAGE->theme->name) as $iconkey => $icon) {
                $icons[$iconkey] = $iconkey;
            }
        }

        return $icons;
    }
}
