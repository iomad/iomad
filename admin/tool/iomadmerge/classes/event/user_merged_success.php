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
 * The user_merged_success event.
 *
 * @package tool
 * @subpackage iomadmerge
 * @author Gerard Cuello Adell <gerard.urv@gmail.com>
 * @copyright 2016 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_iomadmerge\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Class user_merged_success called when merging user accounts has gone right.
 *
 * @package tool_iomadmerge
 * @author Gerard Cuello Adell <gerard.urv@gmail.com>
 * @copyright 2016 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_merged_success extends user_merged {

    public static function get_name() {
        return get_string('eventusermergedsuccess', 'tool_iomadmerge');
    }

    public static function get_legacy_eventname() {
        return 'merging_success';
    }

    public function get_description() {
        return "The user {$this->userid} merged all user-related data
            from '{$this->other['usersinvolved']['fromid']}' into '{$this->other['usersinvolved']['toid']}'";
    }

}
