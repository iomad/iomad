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
 * Defines capabilities for the local_iomadcustompage plugin.
 * This file specifies access control permissions and capabilities for managing custom pages.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
  'local/iomadcustompage:edit' => [
    'captype' => 'write',
    'riskbitmask' => RISK_SPAM | RISK_DATALOSS | RISK_XSS,
    'contextlevel' => CONTEXT_CUSTOMPAGE,
    'archetypes' => [
    ],
  ],
  'local/iomadcustompage:editall' => [
    'captype' => 'write',
    'riskbitmask' => RISK_SPAM | RISK_DATALOSS | RISK_XSS,
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => [
    ],
  ],
  'local/iomadcustompage:view' => [
    'captype' => 'read',
    'riskbitmask' => RISK_PERSONAL,
    'contextlevel' => CONTEXT_CUSTOMPAGE,
    'archetypes' => [
    ],
  ],
];
