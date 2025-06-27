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

namespace local_iomadcustompage\local\models;

use coding_exception;
use context;
use context_helper;
use context_system;
use core\persistent;
use dml_exception;
use local_iomadcustompage\event\iomadcustompage_created;
use local_iomadcustompage\event\iomadcustompage_deleted;
use local_iomadcustompage\event\iomadcustompage_updated;

/**
 * Persistent class to represent a page
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends persistent {
    /** @var string The table name. */
    public const TABLE = 'local_iomadcustompages';

    /**
     * Return the definition of the properties of this model
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'name' => [
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'title' => [
              'type' => PARAM_TEXT,
              'null' => NULL_ALLOWED,
              'default' => null,
            ],
            'contextid' => [
                'type' => PARAM_INT,
                'default' => static function (): int {
                    return context_system::instance()->id;
                },
            ],
            'parent' => [
              'type' => PARAM_INT,
              'null' => NULL_ALLOWED,
              'default' => null,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'default' => static function (): int {
                    global $USER;
                    return (int) $USER->id;
                },
            ],
        ];
    }

    /**
     * Trigger page created event when persistent is created
     */
    protected function after_create(): void {
            iomadcustompage_created::create_from_object($this)->trigger();
    }

    /**
     * Cascade page deletion, first deleting any linked persistents
     */
    protected function before_delete(): void {
        $pageparams = ['pageid' => $this->get('id')];

        // Audiences.
        foreach (audience::get_records($pageparams) as $audience) {
            $audience->delete();
        }

        // Delete the page and related context instance like blocks.
        context_helper::delete_instance(CONTEXT_CUSTOMPAGE, $this->get('id'));
    }

  /**
   * Throw page deleted event when persistent is deleted
   *
   * @param bool $result
   * @throws coding_exception
   * @throws dml_exception
   */
    protected function after_delete($result): void {
        iomadcustompage_deleted::create_from_object($this)->trigger();
    }

  /**
   * Throw page updated event when persistent is updated
   *
   * @param bool $result
   * @throws coding_exception
   */
    protected function after_update($result): void {
        iomadcustompage_updated::create_from_object($this)->trigger();
    }

  /**
   * Return page context, used by exporters
   *
   * @return context
   * @throws coding_exception
   */
    public function get_context(): context {
        return context::instance_by_id($this->raw_get('contextid'));
    }

  /**
   * Return formatted page name
   *
   * @return string
   * @throws coding_exception
   */
    public function get_formatted_name(): string {
        return format_string($this->raw_get('name'), true, ['context' => $this->get_context(), 'escape' => true]);
    }

    /**
     * Return formatted page title
     * @return string
     * @throws coding_exception
     */
    public function get_formatted_title(): string {
        return format_string($this->raw_get('title'), true, ['context' => $this->get_context(), 'escape' => true]);
    }
}
