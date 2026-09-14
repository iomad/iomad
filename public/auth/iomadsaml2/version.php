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
 * Version information
 *
 * @package    auth_iomadsaml2
 * @copyright  Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->release  = '5.2 (Build: 20260420)';    // Human-friendly version name
$plugin->version   = 2026040202;    // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = 2026040202;    // Match release exactly to version.
$plugin->requires  = 2025040400;    // Requires Moodle 5.0
$plugin->component = 'auth_iomadsaml2';  // Full name of the plugin (used for diagnostics).
$plugin->maturity  = MATURITY_STABLE;
$plugin->supported = [500, 502];     // A range of branch numbers of supported moodle versions.
$plugin->dependencies = ['local_iomad' => 2026010100];
