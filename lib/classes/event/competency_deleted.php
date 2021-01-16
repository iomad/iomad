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
 * Competency deleted event.
 *
 * @package    core_competency
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

use core\event\base;
use core_competency\competency;

defined('MOODLE_INTERNAL') || die();

/**
 * Competency deleted event class.
 *
 * @package    core_competency
 * @since      Moodle 3.1
 * @copyright  2016 Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_deleted extends base {

    /**
     * Convenience method to instantiate the event.
     *
     * @param competency $competency The competency.
     * @return self
     */
    public static function create_from_competency(competency $competency) {
        if (!$competency->get('id')) {
            throw new \coding_exception('The competency ID must be set.');
        }
        $event = static::create(array(
            'contextid' => $competency->get_context()->id,
            'objectid' => $competency->get('id'),
        ));
        $event->add_record_snapshot(competency::TABLE, $competency->to_record());
        return $event;
    }

    /**
     * Instantiate events from competency ids.
     *
     * @param array $competencyids Array of competency ids.
     * @param int $contextid The context id.
     * @return self[] Array of events.
     */
    public static function create_multiple_from_competencyids($competencyids, $contextid) {
        $events = array();
        foreach ($competencyids as $id) {
            $events[$id] = static::create(array(
                'contextid' => $contextid,
                'objectid' => $id
            ));
        }
        return $events;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the competency with id '$this->objectid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcompetencydeleted', 'core_competency');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = competency::TABLE;
    }

    /**
     * Get_objectid_mapping method.
     *
     * @return string the name of the restore mapping the objectid links to
     */
    public static function get_objectid_mapping() {
        return base::NOT_MAPPED;
    }

}
