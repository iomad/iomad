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
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    // Basic navigation settings
    $local_iomad_folder = new admin_category('local_iomad', get_string('pluginname', 'local_iomad'));
    $ADMIN->add('localplugins', $local_iomad_folder);

    // Set up the config array.
    $companyconfigs = [];
    $generalconfigs = [];
    $reportconfigs = [];
    $signupconfigs = [];
    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/use_email_as_username',
                                                  get_string('iomad_use_email_as_username', 'local_iomad'),
                                                  get_string('iomad_use_email_as_username_help', 'local_iomad'),
                                                  0);

    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/allow_username',
                                                  get_string('iomad_allow_username', 'local_iomad'),
                                                  get_string('iomad_allow_username_help', 'local_iomad'),
                                                  0);

    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/use_mandatory_courses',
                                                get_string('iomad_use_mandatory_courses', 'local_iomad'),
                                                get_string('iomad_use_mandatory_courses_help', 'local_iomad'),
                                                0);

    $companyconfigs[] = new admin_setting_configcheckbox('local_iomad/show_company_structure',
                                                  get_string('iomad_show_company_structure', 'local_iomad'),
                                                  get_string('iomad_show_company_structure_help', 'local_iomad'),
                                                  1);

    $institutionsync = [get_string('no'),
                        get_string('companyshortname', 'block_iomad_company_admin'),
                        get_string('companyname', 'block_iomad_company_admin')];

    $companyconfigs[] = new admin_setting_configselect('local_iomad/sync_institution',
                                                get_string('iomad_sync_institution', 'local_iomad'),
                                                get_string('iomad_sync_institution_help', 'local_iomad'),
                                                1,
                                                $institutionsync);

    $departmentsync = [get_string('no'),
                       get_string('setfromcompany', 'block_iomad_company_admin'),
                       get_string('settocompany', 'block_iomad_company_admin')];

    $companyconfigs[] = new admin_setting_configselect('local_iomad/sync_department',
                                                get_string('iomad_sync_department', 'local_iomad'),
                                                get_string('iomad_sync_department_help', 'local_iomad'),
                                                1,
                                                $departmentsync);

    $companyconfigs[] = new admin_setting_configcheckbox('local_iomad/autoenrol_managers',
                                                  get_string('iomad_autoenrol_managers', 'local_iomad'),
                                                  get_string('iomad_autoenrol_managers', 'local_iomad'),
                                                  1);

    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/autoreallocate_licenses',
                                                  get_string('iomad_autoreallocate_licenses', 'local_iomad'),
                                                  get_string('iomad_autoreallocate_licenses', 'local_iomad'),
                                                  0);

    $reportconfigs[] = new admin_setting_configcheckbox('local_iomad/hidevalidcourses',
                                                  get_string('iomad_hidevalidcourses', 'local_iomad'),
                                                  get_string('iomad_hidevalidcourses', 'local_iomad'),
                                                  0);

    $reportconfigs[] = new admin_setting_configcheckbox('local_iomad/showcharts',
                                                  get_string('iomad_showcharts', 'local_iomad'),
                                                  get_string('iomad_showcharts', 'local_iomad'),
                                                  1);

    $reportconfigs[] = new admin_setting_configcheckbox('local_iomad/downloaddetails',
                                                  get_string('iomad_downloaddetails', 'local_iomad'),
                                                  get_string('iomad_downloaddetails_help', 'local_iomad'),
                                                  1);

    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/useicons',
                                                  get_string('iomad_useicons', 'local_iomad'),
                                                  get_string('iomad_useicons', 'local_iomad'),
                                                  0);

    $generalconfigs[] = new admin_setting_configcheckbox('local_iomad/showcompanydropdown',
                                                  get_string('iomad_showcompanydropdown', 'local_iomad'),
                                                  get_string('iomad_showcompanydropdown', 'local_iomad'),
                                                  1);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/emaildelay',
                                              get_string('emaildelay', 'local_iomad'),
                                              get_string('emaildelay_help', 'local_iomad'),
                                              0,
                                              PARAM_INT);

    $dateformats = [
        '%Y-%m-%d' => 'YYYY-MM-DD',
        '%Y/%m/%d' => 'YYYY/MM/DD',
        '%Y.%m.%d' => 'YYYY.MM.DD',
        '%Y-%d-%m' => 'YYYY-DD-MM',
        '%Y/%d/%m' => 'YYYY/DD/MM',
        '%Y.%d.%m' => 'YYYY.DD.MM',
        '%d-%m-%Y' => 'DD-MM-YYYY',
        '%d/%m/%Y' => 'DD/MM/YYYY',
        '%d.%m.%Y' => 'DD.MM.YYYY',
        '%m-%d-%Y' => 'MM-DD-YYYY',
        '%m/%d/%Y' => 'MM/DD/YYYY',
        '%m.%d.%Y' => 'MM.DD.YYYY',
        '%d %B, %Y' => 'n Month, YYYY',
        '%B %d, %y' => 'Month n, YY',
        '%d %b, %Y' => 'n Mon, YYYY',
        '%b %d, %y' => 'Mon n, YY',
    ];
    $generalconfigs[] = new admin_setting_configselect('local_iomad/date_format',
                                                get_string('dateformat', 'local_iomad'),
                                                '',
                                                '%Y-%m-%d',
                                                $dateformats);

    $reportconfigs[] = new admin_setting_configtext('local_iomad/report_fields',
                                              get_string('iomad_report_fields', 'local_iomad'),
                                              get_string('iomad_report_fields_help', 'local_iomad'),
                                              '',
                                              PARAM_TEXT);

    $reportconfigs[] = new admin_setting_configtext('local_iomad/report_grade_places',
                                              get_string('iomad_report_grade_places', 'local_iomad'),
                                              get_string('iomad_report_grade_places_help', 'local_iomad'),
                                              0,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_users',
                                              get_string('iomad_max_list_users', 'local_iomad'),
                                              get_string('iomad_max_list_users_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_courses',
                                              get_string('iomad_max_list_courses', 'local_iomad'),
                                              get_string('iomad_max_list_courses_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_templates',
                                              get_string('iomad_max_list_templates', 'local_iomad'),
                                              get_string('iomad_max_list_templates_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_companies',
                                              get_string('iomad_max_list_companies', 'local_iomad'),
                                              get_string('iomad_max_list_companies_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_licenses',
                                              get_string('iomad_max_list_licenses', 'local_iomad'),
                                              get_string('iomad_max_list_licenses_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_classrooms',
                                              get_string('iomad_max_list_classrooms', 'local_iomad'),
                                              get_string('iomad_max_list_classrooms_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_email_templates',
                                              get_string('iomad_max_list_email_templates', 'local_iomad'),
                                              get_string('iomad_max_list_email_templates_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_competencies',
                                              get_string('iomad_max_list_competencies', 'local_iomad'),
                                              get_string('iomad_max_list_competencies_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_list_frameworks',
                                              get_string('iomad_max_list_frameworks', 'local_iomad'),
                                              get_string('iomad_max_list_frameworks_help', 'local_iomad'),
                                              30,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_select_users',
                                              get_string('iomad_max_select_users', 'local_iomad'),
                                              get_string('iomad_max_select_users_help', 'local_iomad'),
                                              100,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_select_courses',
                                              get_string('iomad_max_select_courses', 'local_iomad'),
                                              get_string('iomad_max_select_courses_help', 'local_iomad'),
                                              200,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_select_templates',
                                              get_string('iomad_max_select_templates', 'local_iomad'),
                                              get_string('iomad_max_select_templates_help', 'local_iomad'),
                                              200,
                                              PARAM_INT);

    $generalconfigs[] = new admin_setting_configtext('local_iomad/max_select_frameworks',
                                              get_string('iomad_max_select_frameworks', 'local_iomad'),
                                              get_string('iomad_max_select_frameworks_help', 'local_iomad'),
                                              200,
                                              PARAM_INT);

    $name = 'local_iomad/iomadcertificate_logo';
    $title = get_string('iomadcertificate_logo', 'local_iomad');
    $description = get_string('iomadcertificate_logodesc', 'local_iomad');
    $companyconfigs[] = new admin_setting_configstoredfile($name,
                                                    $title,
                                                    $description,
                                                    'iomadcertificate_logo',
                                                    0,
                                                    ['maxfiles' => 1,
                                                     'accepted_types' => ['image']]);

    $name = 'local_iomad/iomadcertificate_signature';
    $title = get_string('iomadcertificate_signature', 'local_iomad');
    $description = get_string('iomadcertificate_signaturedesc', 'local_iomad');
    $companyconfigs[] = new admin_setting_configstoredfile($name,
                                                    $title,
                                                    $description,
                                                    'iomadcertificate_signature',
                                                    0,
                                                    ['maxfiles' => 1,
                                                     'accepted_types' => ['image']]);

    $name = 'local_iomad/iomadcertificate_border';
    $title = get_string('iomadcertificate_border', 'local_iomad');
    $description = get_string('iomadcertificate_borderdesc', 'local_iomad');
    $companyconfigs[] = new admin_setting_configstoredfile($name,
                                                    $title,
                                                    $description,
                                                    'iomadcertificate_border',
                                                    0,
                                                    ['maxfiles' => 1,
                                                     'accepted_types' => ['image']]);

    $name = 'local_iomad/iomadcertificate_watermark';
    $title = get_string('iomadcertificate_watermark', 'local_iomad');
    $description = get_string('iomadcertificate_watermarkdesc', 'local_iomad');
    $companyconfigs[] = new admin_setting_configstoredfile($name,
                                                    $title,
                                                    $description,
                                                    'iomadcertificate_watermark',
                                                    0,
                                                    ['maxfiles' => 1,
                                                     'accepted_types' => ['image']]);

    // Signup settings.
    $signupconfigs[] = new admin_setting_configcheckbox(
        'local_iomad/signup_enable',
        get_string('enable', 'local_iomad'),
        get_string('enable_help', 'local_iomad'),
        1);

    $signupconfigs[] = new admin_setting_configcheckbox(
        'local_iomad/signup_showinstructions',
        get_string('showinstructions', 'local_iomad'),
        get_string('showinstructions_help', 'local_iomad'),
        1);

    $signupconfigs[] = new admin_setting_configcheckbox(
        'local_iomad/signup_useemail',
        get_string('useemail', 'local_iomad'),
        get_string('useemail_help', 'local_iomad'),
        1);

    $signupconfigs[] = new admin_setting_configcheckbox(
        'local_iomad/signup_autoenrol',
        get_string('autoenrol', 'local_iomad'),
        get_string('autoenrol_help', 'local_iomad'),
        1);

    $signupconfigs[] = new admin_setting_configcheckbox(
        'local_iomad/signup_autoenrol_unassigned',
        get_string('autoenrol_unassigned', 'local_iomad'),
        get_string('autoenrol_unassigned_help', 'local_iomad'),
        0);

    // Get the list of enabled authentication types.
    $siteauths = get_enabled_auth_plugins();
    $siteautharray = [];
    foreach ($siteauths as $siteauth) {
        // Skip manual as we don't want to trigger auto company assignment on that one.
        if ($siteauth != 'manual') {
            $siteautharray[$siteauth] = $siteauth;
        }
    }

    // Add the available auth methods. IF, there are companies defined.
    $sitecompanies = $DB->get_records_menu('company', [], 'name', 'id,name');
    if ($sitecompanies) {
        $signupconfigs[] = new admin_setting_configmulticheckbox('local_iomad/signup_auth',
                                                              get_string('authenticationtypes', 'local_iomad'),
                                                              get_string('authenticationtypes_desc', 'local_iomad'),
                                                              [],
                                                              $siteautharray);

        // Get the list of IOMAD roles.
        $siteroles = $DB->get_records_sql_menu("SELECT id, name
                                                FROM {role}
                                                WHERE shortname IN ('companymanager','companydepartmentmanager', 'companyreporter')
                                                ORDER BY name");
        $availableroles = ['0' => get_string('none')] + $siteroles;
        $signupconfigs[] = new admin_setting_configselect('local_iomad/signup_role',
                                                    get_string('defaultrole', 'local_iomad'),
                                                    get_string('configrole', 'local_iomad'),
                                                    0,
                                                    $availableroles);

        // Get the list of companies.
        $availablecompanies = ['0' => 'none'] + $sitecompanies;
        $signupconfigs[] = new admin_setting_configselect('local_iomad/signup_company',
                                                    get_string('defaultcompany', 'local_iomad'),
                                                    get_string('configcompany', 'local_iomad'),
                                                    0,
                                                    $availablecompanies);
    } else {
        // Set defaults as disabled for now.
        set_config('signup_auth', '', 'local_iomad');
        set_config('signup_role', 0, 'local_iomad');
        set_config('signup_company', 0, 'local_iomad');
    }

    // Finally add all of the settings.
    $settings = new admin_settingpage('local_iomad_general',
                                      get_string('general_settings', 'local_iomad'),
                                      'moodle/site:config');
    $ADMIN->add('local_iomad', $settings);
    foreach ($generalconfigs as $config) {
        $config->plugin = 'local_iomad';
        $settings->add($config);
    }
    $settings = new admin_settingpage('local_iomad_company',
                                      get_string('company_settings', 'local_iomad'),
                                      'moodle/site:config');
    $ADMIN->add('local_iomad', $settings);
    foreach ($companyconfigs as $config) {
        $config->plugin = 'local_iomad';
        $settings->add($config);
    }
    $settings = new admin_settingpage('local_iomad_report',
                                      get_string('report_settings', 'local_iomad'),
                                      'moodle/site:config');
    $ADMIN->add('local_iomad', $settings);
    foreach ($reportconfigs as $config) {
        $config->plugin = 'local_iomad';
        $settings->add($config);
    }
    $settings = new admin_settingpage('local_iomad_signup',
                                      get_string('signup_settings', 'local_iomad'),
                                      'moodle/site:config');
    $ADMIN->add('local_iomad', $settings);
    foreach ($signupconfigs as $config) {
        $config->plugin = 'local_iomad';
        $settings->add($config);
    }
}
