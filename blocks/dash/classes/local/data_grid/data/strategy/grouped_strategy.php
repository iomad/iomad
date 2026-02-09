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
 * Group data by a certain field.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data\strategy;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/dash/lib.php');

use block_dash\local\data_grid\data\data_collection;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\dash_framework\structure\field_interface;
/**
 * Group data by a certain field.
 *
 * @package block_dash
 */
class grouped_strategy implements data_strategy_interface {

    /**
     * @var field_interface
     */
    private $groupbyfielddefinition;

    /**
     * @var field_interface
     */
    private $grouplabelfielddefinition;

    /**
     * Create new grouped strategy.
     *
     * @param field_interface $groupbyfielddefinition
     * @param field_interface $grouplabelfielddefinition
     */
    public function __construct(field_interface $groupbyfielddefinition,
                                field_interface $grouplabelfielddefinition) {
        $this->groupbyfielddefinition = $groupbyfielddefinition;
        $this->grouplabelfielddefinition = $grouplabelfielddefinition;
    }

    /**
     * Convert records.
     *
     * @param \stdClass[] $records
     * @param field_interface[] $fielddefinitions
     * @return data_collection_interface
     */
    public function convert_records_to_data_collection($records, array $fielddefinitions) {
        $griddata = block_dash_get_data_collection();

        $sections = [];

        foreach ($records as $fullrecord) {
            $record = clone $fullrecord;
            $row = block_dash_get_data_collection();

            $label = $record->{$this->grouplabelfielddefinition->get_alias()};
            if (!$groupby = $record->{$this->groupbyfielddefinition->get_alias()}) {
                continue;
            }

            if (isset($record->unique_id)) {
                unset($record->unique_id);
            }

            foreach ($fielddefinitions as $fielddefinition) {
                $alias = $fielddefinition->get_alias();

                $row->add_data(new field($alias, $fielddefinition->transform_data($record->$alias, $fullrecord),
                    $fielddefinition->get_visibility(), $fielddefinition->get_title()));
            }

            if (!isset($sections[$groupby])) {
                $sections[$groupby] = block_dash_get_data_collection();
                $sections[$groupby]->add_data(new field('groupby', $groupby, $fielddefinition->get_visibility()));
                $sections[$groupby]->add_data(new field('label', $label, $fielddefinition->get_visibility()));
            }
            $sections[$groupby]->add_child_collection('rows', $row);
        }

        foreach ($sections as $section) {
            $griddata->add_child_collection('sections', $section);
        }

        return $griddata;
    }
}
