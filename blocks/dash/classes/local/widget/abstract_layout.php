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
 * widget layout definitions.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget;

use block_dash\local\layout\layout_interface;
use moodle_exception;
use block_dash\local\paginator;

/**
 * widget layout definitions.
 */
abstract class abstract_layout extends \block_dash\local\layout\abstract_layout  implements layout_interface, \templatable {

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template($output) {
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
            'noresult' => isset($config->emptystate['text'])
                ? format_text($config->emptystate['text'], FORMAT_HTML, ['noclean' => true]) : $noresulttxt,
            'editing' => $PAGE->user_is_editing(),
        ];

        if (!empty($this->get_data_source()->get_all_preferences())) {
            try {
                $templatedata['data'] = $this->get_data_source()->get_widget_data();

            } catch (\Exception $e) {
                $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
                if (is_siteadmin()) {
                    $error .= \html_writer::tag('p', $e->getMessage());
                }
                $templatedata['error'] .= $OUTPUT->notification($error, 'error');
                throw new moodle_exception('datanotfetch', 'block_dash', '', null, $e->getMessage());
            }
        }

        $formhtml = $this->get_data_source()->get_filter_collection()->create_form_elements();

        if (!is_null($templatedata['data'])) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'supports_filtering' => $this->supports_filtering(),
                'supports_pagination' => $this->supports_pagination(),
                'preferences' => $this->process_preferences($this->get_data_source()->get_all_preferences()),
            ]);
        }

        if ($this->get_data_source()->get_paginator()->get_page_count() > 1) {
            $templatedata['paginator'] = $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_data_source()
                ->get_paginator()
                ->export_for_template($OUTPUT));
        }

        return $templatedata;
    }

    /**
     * Is the layout supports the fields method.
     *
     * @return bool
     */
    public function supports_field_visibility() {
        return true;
    }

    /**
     * Is the layout supports the filter method.
     *
     * @return bool
     */
    public function supports_filtering() {
        return false;
    }

    /**
     * Is the layout supports the pagination.
     *
     * @return bool
     */
    public function supports_pagination() {
        return false;
    }
    /**
     * Is the layout supports the sorting.
     *
     * @return bool
     */
    public function supports_sorting() {
        return false;
    }
}
