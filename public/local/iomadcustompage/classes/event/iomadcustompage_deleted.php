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

declare(strict_types=1);

namespace local_iomadcustompage\event;

use coding_exception;
use context_system;
use core\event\base;
use dml_exception;
use Exception;
use local_iomadcustompage\local\models\page;

/**
 * IOMAD Custom page deleted event class.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string    name:      The name of the page
 *      - string    title:    The title of the page
 * }
 */
class iomadcustompage_deleted extends base {
    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = page::TABLE;
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

  /**
   * Creates an instance from a page object
   *
   * @param page $page
   * @return self
   * @throws dml_exception
   * @throws coding_exception
   */
    public static function create_from_object(page $page): self {

        try {
            $context = $page->get_context();
        } catch (Exception $e) {
            // When page is being deleted this will be handled.
            $context = context_system::instance();
        }
        $eventparams = [
            'context'  => $context,
            'objectid' => $page->get('id'),
            'other' => [
                'name'     => $page->get('name'),
                'title'   => $page->get('title'),
            ],
        ];
        $event = self::create($eventparams);
        $event->add_record_snapshot($event->objecttable, $page->to_record());
        return $event;
    }

  /**
   * Returns localised general event name.
   *
   * @return string
   * @throws coding_exception
   */
    public static function get_name() {
        return get_string('pagedeleted', 'local_iomadcustompage');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the custom page with id '$this->objectid'.";
    }

    /**
     * Custom validations.
     *
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->objectid)) {
            throw new coding_exception('The \'objectid\' must be set.');
        }
    }
}
