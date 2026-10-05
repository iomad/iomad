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

namespace core_customfield\customfield;

use core_customfield\field_controller;
use core_customfield\handler;

/**
 * Custom field handler used to test the default instance lifecycle.
 *
 * @package   core_customfield
 * @copyright 2026 Cameron Ball <cameronball@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_handler extends handler {
    /** @var string Mutable state used to verify instance isolation. */
    public string $value = '';

    #[\Override]
    public function get_configuration_context(): \context {
        return \context_system::instance();
    }

    #[\Override]
    public function get_configuration_url(): \moodle_url {
        return new \moodle_url('/');
    }

    #[\Override]
    public function get_instance_context(int $instanceid = 0): \context {
        return \context_system::instance();
    }

    #[\Override]
    public function can_configure(): bool {
        return true;
    }

    #[\Override]
    public function can_edit(field_controller $field, int $instanceid = 0): bool {
        return true;
    }

    #[\Override]
    public function can_view(field_controller $field, int $instanceid): bool {
        return true;
    }
}
