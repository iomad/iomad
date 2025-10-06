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
 * Hook callbacks for SAML2
 *
 * @package    auth_iomadsaml2
 * @copyright  2024 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => 'auth_iomadsaml2\local\hooks\output\before_http_headers::callback',
        'priority' => 0,
    ],
    [
        'hook' => \core\hook\after_config::class,
        'callback' => 'auth_iomadsaml2\local\hooks\after_config::callback',
        'priority' => 0,
    ],
];
