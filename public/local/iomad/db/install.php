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
 * Local IOMAD install functions
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\task\langpackinitialinstall;
use local_iomad\task\resetrolestask;
use core\task\manager;

/**
 * Local IOMAD install functions
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_local_iomad_install() {
    global $CFG, $DB;

    $systemcontext = context_system::instance();

    // Even worse - change the theme.
    $theme = theme_config::load('iomadboost');
    set_config('theme', $theme->name);
    set_config('allowuserthemes', 1);

    // Enable completion tracking.
    set_config('enablecompletion', 1);

    // Set the default blocks in courses.
    $defblocks = '';
    set_config('defaultblocks_topics', $defblocks);
    set_config('defaultblocks_weeks', $defblocks);

    // Change the default settings for extended username chars to be true.
    $DB->execute("update {config} set value='1' where name='extendedusernamechars'");

    // Set up the new roles for IOMAD.
    // Create the Company Manager role.
    if (!$companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager'])) {
        $companymanagerid = create_role(
            'Company Manager',
            'companymanager',
            '(IOMAD) Manages individual companies - can upload users etc.',
            'companymanager'
        );
    } else {
        $companymanagerid = $companymanagerrole->id;
    }

    // If not done already, allow assignment at company context.
    set_role_contextlevels($companymanagerid, [CONTEXT_COMPANY]);

    // Create new Company Department Manager role.
    if (!$companydepartmentmanager = $DB->get_record('role', ['shortname' => 'companydepartmentmanager'])) {
        $companydepartmentmanagerid = create_role(
            'Company Department Manager',
            'companydepartmentmanager',
            '(IOMAD) Manages departments within companies - can upload users etc.',
            'companydepartmentmanager'
        );
    } else {
        $companydepartmentmanagerid = $companydepartmentmanager->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($companydepartmentmanagerid, [CONTEXT_COMPANY]);

    // Create the Company Course Editor.
    if (!$companycourseeditor = $DB->get_record('role', ['shortname' => 'companycourseeditor'])) {
        $companycourseeditorid = create_role(
            'Company Course Editor',
            'companycourseeditor',
            '(IOMAD) Teacher style role for Company manager provided to them when they create their own course.',
            'companycourseeditor'
        );
    } else {
        $companycourseeditorid = $companycourseeditor->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($companycourseeditorid, [CONTEXT_COURSE]);

    // Create new Company Course Non Editor role.
    if (!$companycoursenoneditor = $DB->get_record( 'role', ['shortname' => 'companycoursenoneditor'])) {
        $companycoursenoneditorid = create_role(
            'Company Course Non Editor',
            'companycoursenoneditor',
            '(IOMAD) Non editing teacher style role form Company and department managers',
            'companycoursenoneditor'
        );
    } else {
        $companycoursenoneditorid = $companycoursenoneditor->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($companycoursenoneditorid, [CONTEXT_COURSE]);

    // Create new Company reporter role.
    if (!$companyreporter = $DB->get_record('role', ['shortname' => 'companyreporter'])) {
        $companyreporterid = create_role(
            'Company Report Only',
            'companyreporter',
            '(IOMAD) Access to company reports only..',
            'companyreporter'
        );
    } else {
        $companyreporterid = $companyreporter->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($companyreporterid, [CONTEXT_COMPANY]);

    // Create new Client administrator role.
    if (!$clientadministrator = $DB->get_record('role', ['shortname' => 'clientadministrator'])) {
        $clientadministratorid = create_role(
            'Client Administrator',
            'clientadministrator',
            '(IOMAD) Client access to all companies..',
            'clientadministrator'
        );
    } else {
        $clientadministratorid = $clientadministrator->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($clientadministratorid, [CONTEXT_SYSTEM]);

    // Create new Client reporter role.
    if (!$clientreporter = $DB->get_record('role', ['shortname' => 'clientreporter'])) {
        $clientreporterid = create_role(
            'Client Report Only',
            'clientreporter',
            '(IOMAD) Client access to all company reports only..',
            'clientreporter'
        );
    } else {
        $clientreporterid = $clientreporter->id;
    }

    // If not done already, allow assignment at system context.
    set_role_contextlevels($clientreporterid, [CONTEXT_SYSTEM]);

    // Create an adhoctask to set up these roles once cron runs again.
    $roleresettask = new resetrolestask();

    // Queue the task.
    manager::queue_adhoc_task($roleresettask);

    // Set the refreshlangpacks task to run ASAP.
    set_config('local_iomad_email_templates_migrating', 1);
    $refreshinstalltask = new langpackinitialinstall();
    manager::queue_adhoc_task($refreshinstalltask);

}
