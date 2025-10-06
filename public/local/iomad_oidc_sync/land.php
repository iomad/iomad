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
 * Callback landing page.
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');

$companyid    = required_param('companyid', PARAM_INT);
$approved    = required_param('approved', PARAM_INT);

require_login();

$systemcontext = context_system::instance();
$companycontext = $systemcontext;

// If we are 4.3+ we use the company context for this.
if ($CFG->branch > 402) {
    $companycontext = \core\context\company::instance($companyid);
}

iomad::require_capability('local/iomad_oidc_sync:manage', $companycontext);

if (confirm_sesskey()) {
    $DB->set_field('local_iomad_oidc_sync', 'approved', $approved, ['companyid' => $companyid]);
}

redirect($CFG->wwwroot . '/local/iomad_oidc_sync/index.php',
         get_string('approvalset', 'local_iomad_oidc_sync'),
         null,
         \core\output\notification::NOTIFY_SUCCESS);
die;
