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
 * Atto text editor multilanguage plugin settings.
 *
 * @package   atto_multilang2
 * @copyright 2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(dirname(__FILE__)) . '/default-css.php';

$settings->add(new admin_setting_configcheckbox('atto_multilang2/highlight',
    get_string('highlight', 'atto_multilang2'), get_string('highlight_desc', 'atto_multilang2'), 1));
$settings->add(new admin_setting_configtextarea('atto_multilang2/customcss',
    get_string('customcss', 'atto_multilang2'), get_string('customcss_desc', 'atto_multilang2'),
    $multilang2defaultcss, PARAM_RAW));
