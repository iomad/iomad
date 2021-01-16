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
 * Class for exporting data purpose.
 *
 * @package    tool_dataprivacy
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_dataprivacy\external;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\external\persistent_exporter;
use DateInterval;
use Exception;
use renderer_base;
use tool_dataprivacy\purpose;

/**
 * Class for exporting field data.
 *
 * @copyright  2018 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purpose_exporter extends persistent_exporter {

    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class() {
        return purpose::class;
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return array(
            'context' => 'context',
        );
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'formattedretentionperiod' => [
                'type' => PARAM_TEXT
            ],
            'formattedlawfulbases' => [
                'type' => name_description_exporter::read_properties_definition(),
                'multiple' => true
            ],
            'formattedsensitivedatareasons' => [
                'type' => name_description_exporter::read_properties_definition(),
                'multiple' => true,
                'optional' => true
            ],
        ];
    }

    /**
     * Return other properties.
     *
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     * @throws Exception
     */
    protected function get_other_values(renderer_base $output) {
        $values = [];

        $formattedbases = [];
        $lawfulbases = explode(',', $this->persistent->get('lawfulbases'));
        if (!empty($lawfulbases)) {
            foreach ($lawfulbases as $basis) {
                if (empty(trim($basis))) {
                    continue;
                }
                $formattedbases[] = (object)[
                    'name' => get_string($basis . '_name', 'tool_dataprivacy'),
                    'description' => get_string($basis . '_description', 'tool_dataprivacy')
                ];
            }
        }
        $values['formattedlawfulbases'] = $formattedbases;

        $formattedsensitivereasons = [];
        $sensitivereasons = explode(',', $this->persistent->get('sensitivedatareasons'));
        if (!empty($sensitivereasons)) {
            foreach ($sensitivereasons as $reason) {
                if (empty(trim($reason))) {
                    continue;
                }
                $formattedsensitivereasons[] = (object)[
                    'name' => get_string($reason . '_name', 'tool_dataprivacy'),
                    'description' => get_string($reason . '_description', 'tool_dataprivacy')
                ];
            }
        }
        $values['formattedsensitivedatareasons'] = $formattedsensitivereasons;

        $retentionperiod = $this->persistent->get('retentionperiod');
        if ($retentionperiod) {
            $interval = new DateInterval($retentionperiod);

            // It is one or another.
            if ($interval->y) {
                $formattedtime = get_string('numyears', 'moodle', $interval->format('%y'));
            } else if ($interval->m) {
                $formattedtime = get_string('nummonths', 'moodle', $interval->format('%m'));
            } else if ($interval->d) {
                $formattedtime = get_string('numdays', 'moodle', $interval->format('%d'));
            } else {
                $formattedtime = get_string('retentionperiodzero', 'tool_dataprivacy');
            }
        } else {
            $formattedtime = get_string('retentionperiodnotdefined', 'tool_dataprivacy');
        }
        $values['formattedretentionperiod'] = $formattedtime;

        return $values;
    }
}
