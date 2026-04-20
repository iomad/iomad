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

namespace core_course\exception;

use core\clock;
use core\di;
use core\exception\moodle_exception;

/**
 * Exception thrown when a course reset takes too long
 *
 * @package   core_course
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_timeout extends moodle_exception {
    /**
     * @var int Default timeout in seconds.
     */
    const DEFAULT_TIMEOUT = 10;

    /**
     * @var int The unix timestamp of the time limit to check.
     */
    protected int $timelimit;

    /**
     * Set the timeout message including details of the course.
     *
     * @param string $shortname
     * @param int $timelimit
     */
    public function __construct(
        string $shortname,
        int $timelimit,
    ) {
        parent::__construct('resettimeout', 'course', a: $shortname);
        $this->timelimit = $timelimit;
    }

    /**
     * Check if we are within the time limit.
     *
     * @return bool True if we are within the timelimit, false if the limit has been exceeded.
     */
    public function within_timelimit(): bool {
        $clock = di::get(clock::class);
        if (!is_null($this->timelimit) && $clock->time() > $this->timelimit) {
            return false;
        }
        return true;
    }

    /**
     * If passed an instance of this exception that is over the time limit, throw the exception.
     *
     * @param self|null $timeout
     */
    public static function throw_if_expired(?self $timeout): void {
        if (!is_null($timeout) && !$timeout->within_timelimit()) {
            throw $timeout;
        }
    }
}
