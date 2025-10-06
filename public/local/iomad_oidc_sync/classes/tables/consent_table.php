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
 * Consent table
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad_oidc_sync\tables;

use table_sql;
use moodle_url;
use company;
use iomad;
use html_writer;
use context_system;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Table class definition
 */
class consent_table extends table_sql {

    /**
     * Generate the consent button
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {

        return format_string($row->name);
    }

    /**
     * Generate the consent button
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_consentlink($row) {

        $postfix = "_" . $row->id;
        $clientid = get_config('auth_iomadoidc', 'clientid' . $postfix);

        // Get the company URL.
        $company = new company($row->id);
        $wwwroot = $company->get_wwwroot();

        // Set up the bit for the consent link.
        $uri = $wwwroot . "/auth/iomadoidc";
        $redirecturi = urlencode($uri);
        $tenantnameorguid = get_config('auth_iomadoidc', 'tenantnameorguid' . $postfix);
        if (empty($tenantnameorguid)) {
            $tenantnameorguid = 'organizations';
        }

        // Create the consent link.
        $adminconsenturl = "https://login.microsoftonline.com/" . $tenantnameorguid .
                           "/v2.0/adminconsent?client_id=" . $clientid .
                           "&scope=https://graph.microsoft.com/.default&redirect_uri=" . $redirecturi;
        return "<a class='btn btn-primary' href='" . $adminconsenturl . "' target='_self'>" .
                get_string('agreeconsent', 'local_iomad_oidc_sync') . "</a>";
    }

    /**
     * Generate the consent button
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_approved($row) {
        global $params, $OUTPUT, $CFG, $companycontext;

        $enabled = !empty($row->tenantnameorguid);
        $clientid = get_config('auth_iomadoidc', 'clientid_' . $row->id);
        $canmanage = iomad::has_capability('local/iomad_oidc_sync:manage', $companycontext);

        $extraclass = "";
        if (!$enabled) {
            $extraclass = " dimmed_text";
        }

        if (empty($row->tenantnameorguid)) {
            $row->tenantnameorguid = "TENANTNAMEORGUID_".$row->id;
        }
        $returnurl = new moodle_url($CFG->wwwroot . '/local/iomad_oidc_sync/land.php',
                                    ['companyid' => $row->id,
                                     'approved' => 1]);

        $enablelink = new moodle_url('/local/iomad_oidc_sync/index.php',
                                      $params +
                                      ['approvecompanyid' => $row->id,
                                       'action' => 'enable',
                                       'sesskey' => sesskey()]);

        $disablelink = new moodle_url('/local/iomad_oidc_sync/index.php',
                                       $params +
                                       ['approvecompanyid' => $row->id,
                                        'action' => 'disable',
                                       'sesskey' => sesskey()]);

        $approvelink = new moodle_url('/local/iomad_oidc_sync/authorise.php',
                                      ['companyid' => $row->id,
                                       'sesskey' => sesskey(),
                                       'approved' => true]);

        $checklink = new moodle_url('/local/iomad_oidc_sync/index.php',
                                    $params);

        $return = "";
        // Deal with the settings link.
        if ($canmanage) {
            $return .= html_writer::start_tag('a', ['href' => '#',
                                                    'data-action' => 'show-tenantorguidform',
                                                    'data-companyid' => $row->id,
                                                    'data-tenantnameorguid' => $row->tenantnameorguid]);
        }
        $return .= html_writer::tag('i',
                                    '',
                                    ['class' => 'icon fa fa-cog fa-fw ',
                                     'title' => get_string('settings'),
                                     'role' => 'img',
                                     'aria-label' => get_string('settings')]);
        if ($canmanage) {
            $return .= html_writer::end_tag('a');
        }

        $astyle = "";
        if (!$enabled) {
            $astyle = "pointer-events:none;";
        }
        if ($canmanage) {
            $return .= html_writer::start_tag('a', ['href' => $approvelink,
                                                    'target' => 'self',
                                                    'style' => $astyle,
                                                    'data-approvelink' . $row->id => $row->id]);
        }
        $return .= html_writer::tag('i',
                                    '',
                                    ['class' => 'icon fa fa-user fa-fw ' . $extraclass,
                                     'title' => get_string('approve'),
                                     'role' => 'img',
                                     'aria-label' => get_string('approve')]);
        if ($canmanage) {
            $return .= html_writer::end_tag('a');
        }

        if (empty($row->approved)) {
            if ($canmanage) {
                $return .= html_writer::start_tag('a', ['href' => $checklink]);
            }
            $return .= $OUTPUT->pix_icon('no', get_string('no'), 'tool_oauth2', ['class' => 'approveicon',
                                                                                 'data-approveicon' . $row->id => $row->id]);
            if ($canmanage) {
                $return .= html_writer::end_tag('a');
            }
        } else {
            $return .= $OUTPUT->pix_icon('yes', get_string('yes'), 'tool_oauth2', ['class' => 'approveicon',
                                                                                   'data-approveicon' . $row->id => $row->id]);
        }

        if (empty($row->enabled)) {
            if ($enabled) {
                if ($canmanage) {
                    $return .= html_writer::start_tag('a', ['href' => $enablelink]);
                }
            }
            $return .= html_writer::tag('i',
                                        '',
                                        ['class' => 'icon fa fa-toggle-off fa-fw ' . $extraclass,
                                         'title' => get_string('enable'),
                                         'role' => 'img',
                                         'data-enableicon' . $row->id => $row->id,
                                         'aria-label' => get_string('enable')]);
            if ($enabled) {
                if ($canmanage) {
                    $return .= html_writer::end_tag('a');
                }
            }
        } else {
            if ($enabled) {
                if ($canmanage) {
                    $return .= html_writer::start_tag('a', ['href' => $disablelink, 'data-enablelink'. $row->id => $row->id]);
                }
            }
            $return .= html_writer::tag('i',
                                        '',
                                        ['class' => 'icon fa fa-toggle-on fa-fw ' . $extraclass,
                                         'title' => get_string('disable'),
                                         'role' => 'img',
                                         'data-enableicon' . $row->id => $row->id,
                                         'aria-label' => get_string('disable')]);
            if ($enabled) {
                if ($canmanage) {
                    $return .= html_writer::end_tag('a');
                }
            }
        }

        return $return;
    }
}
