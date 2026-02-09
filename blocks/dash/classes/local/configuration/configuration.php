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
 * Configuration helps with building block instance content.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\configuration;

use block_dash\local\data_source\data_source_factory;
/**
 * Configuration helps with building block instance content.
 *
 * @package block_dash
 */
class configuration extends abstract_configuration {

    /**
     * Create configuration from block instance.
     *
     * @param \block_base $blockinstance
     * @return configuration|configuration_interface
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function create_from_instance(\block_base $blockinstance) {
        $parentcontext = \context::instance_by_id($blockinstance->instance->parentcontextid);

        $datasource = null;
        if (isset($blockinstance->config->data_source_idnumber)) {
            if (!$datasource = data_source_factory::build_data_source($blockinstance->config->data_source_idnumber,
                $parentcontext)) {
                return false;
            }

            if (isset($blockinstance->config->preferences)
                && is_array($blockinstance->config->preferences)
                && !empty($blockinstance->config->preferences)) {
                $datasource->set_preferences($blockinstance->config->preferences);
            }

            $datasource->set_block_instance($blockinstance);
        }

        return new configuration($parentcontext, $datasource);
    }
}
