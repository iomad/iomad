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
 * Class standard_strategy.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data\strategy;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/dash/lib.php');

use block_dash\local\dash_framework\structure\field_interface;
use block_dash\local\data_grid\data\data_collection;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\field\attribute\context_attribute;
/**
 * Class standard_strategy.
 *
 * @package block_dash
 */
class standard_strategy implements data_strategy_interface {

    /**
     * Convert records.
     *
     * @param \stdClass[] $records
     * @param field_interface[] $fielddefinitions
     * @return data_collection_interface
     */
    public function convert_records_to_data_collection($records, array $fielddefinitions) {
        $griddata = block_dash_get_data_collection();

        foreach ($records as $fullrecord) {
            $record = clone $fullrecord;
            if (isset($record->unique_id)) {
                unset($record->unique_id);
            }
            $row = block_dash_get_data_collection();
            foreach ($fielddefinitions as $fielddefinition) {
                $name = $fielddefinition->get_alias();

                if (!property_exists($record, $name)) {
                    continue;
                }

                if ($fielddefinition->has_attribute(context_attribute::class)) {
                    $row->set_context(\context::instance_by_id($record->$name));
                }

                $row->add_data(new field($name, $fielddefinition->transform_data($record->$name, $fullrecord),
                    $fielddefinition->get_visibility(), $fielddefinition->get_title()));
            }

            $griddata->add_child_collection('rows', $row);
        }

        return $griddata;
    }
}
