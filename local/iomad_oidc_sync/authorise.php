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
 * Handle authorisation for using the MS Graph functions.
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');

$companyid    = required_param('companyid', PARAM_INT);
$approved    = optional_param('approved', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
$companycontext = $systemcontext;

// If we are 4.3+ we use the company context for this.
if ($CFG->branch > 402) {
    $companycontext = \core\context\company::instance($companyid);
}

iomad::require_capability('local/iomad_oidc_sync:manage', $companycontext);

$postfix = "_$companyid";
$oidcsyncrec = $DB->get_record('local_iomad_oidc_sync', ['companyid' => $companyid], '*', MUST_EXIST);
if (empty($oidcsyncrec->tenantnameorguid) || !confirm_sesskey()) {
    throw new \moodle_exception('configerror', 'local_iomad_oidc_sync');
}

// Build the redirect URL.
$clientid = get_config('auth_iomadoidc', 'clientid' . $postfix);
$redirecturi = get_config('auth_iomadoidc', 'redirecturi' . $postfix);
$approvelink = new moodle_url('https://login.microsoftonline.com/' . $oidcsyncrec->tenantnameorguid . '/v2.0/adminconsent',
                              ['client_id' => $clientid,
                               'scope' => 'https://graph.microsoft.com/.default',
                               'redirect_uri' => $redirecturi]);

// Build the wanted URL.
$wantsurl = new moodle_url($CFG->wwwroot . '/local/iomad_oidc_sync/land.php',
                          ['companyid' => $companyid,
                           'approved' => true,
                           'sesskey' => sesskey()]);

// Set the Session wantsurl so we get redirected to it.
$SESSION->wantsurl = $wantsurl;
redirect($approvelink);
die;
