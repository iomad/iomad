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
 * Message sink.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Message sink.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phpunit_message_sink {
    /** @var array of records from message_read table */
    protected $messages = array();

    /**
     * Stop message redirection.
     *
     * Use if you do not want message redirected any more.
     */
    public function close() {
        phpunit_util::stop_message_redirection();
    }

    /**
     * To be called from phpunit_util only!
     *
     * @param stdClass $message record from message_read table
     */
    public function add_message($message) {
        /* Number messages from 0. */
        $this->messages[] = $message;
    }

    /**
     * Returns all redirected messages.
     *
     * The instances are records form the message_read table.
     * The array indexes are numbered from 0 and the order is matching
     * the creation of events.
     *
     * @return array
     */
    public function get_messages() {
        return $this->messages;
    }

    /**
     * Return number of messages redirected to this sink.
     * @return int
     */
    public function count() {
        return count($this->messages);
    }

    /**
     * Removes all previously stored messages.
     */
    public function clear() {
        $this->messages = array();
    }
}
