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
 * Plugin version info
 *
 * @package   local_report_completion_monthly
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->release  = '4.5.8 (Build: 20251208)'; // Human-friendly version name.
$plugin->version  = 2024100745;   // The (date) version of this plugin.
$plugin->requires = 2024100700;   // Requires this Moodle version.
$plugin->component  = 'local_report_completion_monthly';
$plugin->dependencies = ['local_iomad' => 2024090401];
$plugin->supported = [405, 405];
$plugin->maturity = MATURITY_STABLE;
