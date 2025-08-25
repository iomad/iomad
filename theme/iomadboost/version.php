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
 * IOMAD Boost.
 *
 * @package    theme_iomadboost
 * @copyright  2016 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->release  = '5.0.2 (Build: 20250811)';    // Human-friendly version name
$plugin->version  = 2025041400.500;   // The (date) version of this plugin.
$plugin->requires = 2025041400;   // Requires this Moodle version.
$plugin->component = 'theme_iomadboost';
$plugin->dependencies = ['local_iomad' => 2025041400];
$plugin->supported = [500, 500];
$plugin->maturity = MATURITY_STABLE;
