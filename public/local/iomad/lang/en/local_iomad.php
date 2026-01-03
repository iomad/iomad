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

/**
 * Strings for component 'local_iomad', language 'en'
 */

$string['authenticationtypes'] = 'Select authentication types';
$string['authenticationtypes_desc'] = 'These are the authentication types which can be used for automatically assigning a user to a company.';
$string['autoenrol'] = 'Auto enrol user';
$string['autoenrol_help'] = 'Selecting this will automatically enrol new users onto non-licensed or self enrol courses assigned to the company.';
$string['autoenrol_unassigned'] = 'Auto enrol unassigned courses';
$string['autoenrol_unassigned_help'] = 'Selecting this will automatically enrol new users onto non-licensed or self enrol courses not assigned to any company.';
$string['cachedef_allcompanycategories'] = 'Cache to hold categories which any company has access to.';
$string['cachedef_companycategories'] = 'Cache to hold categories which are assigned to a company.';
$string['cachedef_companycoursecategories'] = 'Cache to hold categories which a company has access to.';
$string['cannotcallusgetselectedcourse'] = 'You cannot call course_selector::get_selected_course if multi-select is true';
$string['cannotcallusgetselectedframework'] = 'You cannot call framework_selector::get_selected_framework if multi-select is true';
$string['cannotcallusgetselectedtemplate'] = 'You cannot call template_selector::get_selected_template if multi-select is true';
$string['cannotemailnontemporarypasswords'] = 'It is insecure to send passwords by email without forcing them to be changed on first login.';
$string['checkcoursestatusmoodle'] = 'Check course settings for import';
$string['choosepassword'] = 'Create new user';
$string['clear'] = 'Clear';
$string['company_license_expiring_task'] = 'Email reports - Company licenses expiring task';
$string['company_settings'] = 'Company settings';
$string['companycityfilter'] = 'Company location contains';
$string['companycountryfilter'] = 'Company country contains';
$string['companycourses'] = 'Other company courses';
$string['companyfilter'] = 'Filter results';
$string['companynamefilter'] = 'Company name contains';
$string['companysearchfields'] = 'Company search fields';
$string['completionimportfromfile'] = 'Completion import from file';
$string['configcompany'] = 'This is the company that the user will be assigned to once they have completed the sign up process if no other company is defined either through the sign up form or through the email domain.';
$string['configrole'] = 'This is the role the user will be given when they have completed the signup process.';
$string['course_expiry_warning_task'] = 'Email reports - Course expiry warning task';
$string['course_not_completed_task'] = 'Email reports - Course not completed task';
$string['course_not_started_task'] = 'Email reports - Course not started task';
$string['coursesearchfields'] = 'Course search';
$string['courseselectorautoselectunique'] = 'If only one course matches the search, select it automatically';
$string['courseselectorpreserveselected'] = 'Keep selected courses, even if they no longer match the search';
$string['courseselectorsearchanywhere'] = 'Match the search text anywhere in the course\'s name';
$string['courseselectortoomany'] = 'course_selector got more than one selected course, even though multi-select is false';
$string['courseswithoutcompletioncriteriacouunt'] ='Number of courses which have no completion criteria = {$a}';
$string['courseswithoutcompletionenabledcouunt'] = 'Number of courses which do not have completion enabled = {$a}';
$string['crontask'] = 'IOMAD Cron';
$string['dateformat'] = 'Date format';
$string['datesearchfields'] = 'Date search';
$string['defaultcompany'] = 'Default company users are assigned to';
$string['defaultrole'] = 'Role to be assigned';
$string['deletecompany'] = 'Delete company adhoc task';
$string['emailasusernamehelp'] = 'Enter your email address. This will be your username.';
$string['emaildelay'] = 'Email delay';
$string['emaildelay_help'] = 'Any IOMAD emails will have this value (in seconds) added to the send time by default. This allows for a default delay in sending, much like for forum posts, of any IOMAD email. Timings will still be impacted by the local_mail cron task, but this delay will be a minimum value.';
$string['emaildomaindoesntmatch'] = 'Your email domain is not in the list of accepted domains for this company.';
$string['emailfilter'] = 'Email address contains';
$string['enable'] = 'Enable';
$string['enable_help'] = 'New users will be assigned to a company on creation when this is enabled.';
$string['firstnamefilter'] = 'First name contains';
$string['fixcertificatetask'] = 'Change certificate context to user context';
$string['fixcourseclearedtask'] = 'Ad-hoc task to update the \'coursecleared\' field in the stored completion records';
$string['fixenrolleddatetask'] = 'Ad-hoc task to update the stored completion information to use the enrolment \'timecreated\' timestamp where this is not already set.';
$string['fixtracklicensetask'] = 'IOMAD track fix license tracking details ad-hoc task';
$string['fixtracklicensetask'] = 'Ad-hoc task to fix stored records license information';
$string['frameworkselectorautoselectunique'] = 'If only one framework matches the search, select it automatically';
$string['frameworkselectorpreserveselected'] = 'Keep selected frameworks, even if they no longer match the search';
$string['frameworkselectorsearchanywhere'] = 'Match the search text anywhere in the framework\'s name';
$string['frameworkselectortoomany'] = 'framework_selector got more than one selected framework, even though multi-select is false';
$string['general_settings'] = 'General settings';
$string['importcompletionrecords'] = 'Import completion records';
$string['importcompletionsfromfile'] = 'Import completion information from file';
$string['importcompletionsfrommoodle'] = 'Import stored completion information from Moodle tables';
$string['importcompletionsfrommoodlefull'] = 'This will run an ad-hoc task to import all of the completion information from Moodle to the IOMAD reporting tables.';
$string['importcompletionsfrommoodlefullwitherrors'] = 'This will run an ad-hoc task to import SOME of the completion information from Moodle to the IOMAD reporting tables. Not all courses have completion enabled or criteria set up and their information will be missed out. If you want to know which courses these are, use the check link on the previous page.';
$string['importmoodlecompletioninformation'] = 'Ad-hoc task to import completion information from Moodle tables';
$string['iomad'] = 'IOMAD';
$string['iomad_allow_username'] = 'Can specify username';
$string['iomad_allow_username_help'] = 'Selecting this will allow the username field to be presented when creating accounts. This will supersede the use email address as username setting.';
$string['iomad_autoenrol_managers'] = 'Enrol managers as non-students';
$string['iomad_autoenrol_managers_help'] = 'If this is unticked, then manager accounts will not be enroled as the company teacher roles on manual enrol courses.';
$string['iomad_autoreallocate_licenses'] = 'Automatically re-allocate license';
$string['iomad_autoreallocate_licenses_help'] = 'If this is ticked, then when a user\'s licensed course entry is deleted within the user report, the system will automatically try to re-allocate another from the company license pool.';
$string['iomad_downloaddetails'] = 'Download activity details in course completion report.';
$string['iomad_downloaddetails_help'] = 'Selecting this will download all of the details of the course completion criteria for the user as well as their status. Without this selected only their status will be included.';
$string['iomad_hidevalidcourses'] = 'Show only current course results in reports as default';
$string['iomad_hidevalidcourses_help'] = 'This changes the display of the completion reports so that it only shows current course results (ones which have not yet expired or have no expiry) by default.';
$string['iomad_max_list_classrooms'] = 'Maximum listed classrooms';
$string['iomad_max_list_classrooms_help'] = 'This defines the maximum number of classrooms displayed on a page';
$string['iomad_max_list_companies'] = 'Maximum listed companies';
$string['iomad_max_list_companies_help'] = 'This defines the maximum number of companies displayed on a page';
$string['iomad_max_list_competencies'] = 'Maximum listed competencies';
$string['iomad_max_list_competencies_help'] = 'This defines the maximum number of competencies displayed on a page';
$string['iomad_max_list_courses'] = 'Maximum listed courses';
$string['iomad_max_list_courses_help'] = 'This defines the maximum number of courses displayed on a page';
$string['iomad_max_list_email_templates'] = 'Maximum listed email templates';
$string['iomad_max_list_email_templates_help'] = 'This defines the maximum number of email templates displayed on a page';
$string['iomad_max_list_frameworks'] = 'Maximum listed frameworks';
$string['iomad_max_list_frameworks_help'] = 'This defines the maximum number of frameworks displayed on a page';
$string['iomad_max_list_licenses'] = 'Maximum listed licenses';
$string['iomad_max_list_licenses_help'] = 'This defines the maximum number of licenses displayed on a page';
$string['iomad_max_list_templates'] = 'Maximum listed learning plan templates';
$string['iomad_max_list_templates_help'] = 'This defines the maximum number of learning plan templates displayed on a page';
$string['iomad_max_list_users'] = 'Maximum listed users';
$string['iomad_max_list_users_help'] = 'This defines the maximum number of users displayed on a page';
$string['iomad_max_select_courses'] = 'Maximum listed courses in selector';
$string['iomad_max_select_courses_help'] = 'This defines the maximum number of courses displayed in a form search selector before \'too many courses\' is shown';
$string['iomad_max_select_frameworks'] = 'Maximum listed frameworks in selector';
$string['iomad_max_select_frameworks_help'] = 'This defines the maximum number of frameworks displayed in a form search selector before \'too many frameworks\' is shown';
$string['iomad_max_select_templates'] = 'Maximum listed learning plan templates in selector';
$string['iomad_max_select_templates_help'] = 'This defines the maximum number of learning plan templates displayed in a form search selector before \'too many templates\' is shown';
$string['iomad_max_select_users'] = 'Maximum listed users in selector';
$string['iomad_max_select_users_help'] = 'This defines the maximum number of users displayed in a form search selector before \'too many users\' is shown';
$string['iomad_report_fields'] = 'Additional report profile fields';
$string['iomad_report_fields_help'] = 'This is a list of profile fields separated by a comma. If you want to use an optional profile field, you need to use profile_field_<shortname> where <shortname> is the shortname defined for the profile field. The order given is the order they are displayed in.';
$string['iomad_report_grade_places'] = 'Number of decimal places for grades in reports';
$string['iomad_report_grade_places_help'] = 'This defines the number of decimal places which will be displayed in IOMAD reports whenever a user\'s grade is listed';
$string['iomad_settings:addinstance'] = 'Add a new IOMAD Settings block';
$string['iomad_show_company_structure'] = 'Show company hierarchy in selector';
$string['iomad_show_company_structure_help'] = 'If checked, child companies will appear indented under the parent company in the company selector. This may cause performance issues for larger sites.';
$string['iomad_showcharts'] = 'Show course completion charts as default';
$string['iomad_showcharts_help'] = 'If checked, the charts will be shown first with an option to show as text instead';
$string['iomad_showcompanydropdown'] = 'Show company switcher in navbar';
$string['iomad_showcompanydropdown_help'] = 'Selecting this displays the company drop down switcher in the navbar when the user can access multiple companies. Users will need to be given another way to access the company switcher is this is disabled and they do not have access to the IOMAD dashboard in their current company.';
$string['iomad_sync_department'] = 'Sync company department with profile';
$string['iomad_sync_department_help'] = 'Selecting this will either keep the user\'s profile field for department in sync with the name of the company department that the user is allocated to (Set from company department), or will assign the user to a company department which matches (Set to company department). If the user is in multiple departments, then this will show \'Multiple\' instead.';
$string['iomad_sync_institution'] = 'Sync company name with profile';
$string['iomad_sync_institution_help'] = 'Selecting this will keep the user\'s institution profile field in sync with either the shortname or name of the company that the user is allocated to. If the user is in multiple companies, then this will show \'Multiple\' instead.';
$string['iomad_track:importfrommoodle'] = 'Import completion information from Moodle tables';
$string['iomad_use_email_as_username'] = 'Use email address as user name';
$string['iomad_use_email_as_username_help'] = 'Selecting this will change the way a user\'s username is automatically created for a new user account in IOMAD so that it simply uses their email address';
$string['iomad_useicons'] = 'Use icons in IOMAD dashboard';
$string['iomad_useicons_help'] = 'Selecting this changes the dashboard icons to use images instead of Font Awesome characters.';
$string['iomadcertificate_border'] = 'Default border for IOMAD Company certificate';
$string['iomadcertificate_borderdesc'] = 'This is the default border image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 800 pixels x 604 pixels.';
$string['iomadcertificate_logo'] = 'Default logo for IOMAD Company certificate';
$string['iomadcertificate_logodesc'] = 'This is the default logo image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 80 pixels high and have a transparent background.';
$string['iomadcertificate_signature'] = 'Default signature for IOMAD Company certificate';
$string['iomadcertificate_signaturedesc'] = 'This is the default signature image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 31 pixels x 150 pixels and have a transparent background.';
$string['iomadcertificate_watermark'] = 'Default watermark for IOMAD Company certificate';
$string['iomadcertificate_watermarkdesc'] = 'This is the default watermark image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be no more than 800 pixels x 604 pixels.';
$string['lastnamefilter'] = 'Last name contains';
$string['logininfo'] = 'Fill out the form below to create a new user. An email will be sent to the email address you specify to verify the account and allow access.';
$string['manager_completion_digest_task'] = 'Email reports - Manager recent completions digest task';
$string['manager_expiring_digest_task'] = 'Email reports - Manager courses expiring warning digest task';
$string['manager_warning_digest_task'] = 'Email reports - Manager courses not completed warning digest task';
$string['missingaccesstocourse'] = 'You\'re not allowed to do that.';
$string['nomatchingcourses'] = 'No courses match \'{$a}\'';
$string['nomatchingframeworks'] = 'No frameworks match \'{$a}\'';
$string['nomatchingtemplates'] = 'No templates match \'{$a}\'';
$string['none'] = 'None';
$string['nopermissions'] = 'The IOMAD administrator has not given you permission to do this.';
$string['pleasesearchmore'] = 'Please search some more';
$string['pleaseusesearch'] = 'Please use the search';
$string['pluginname'] = 'IOMAD';
$string['previouslyselectedcourses'] = 'Previously selected courses not matching \'{$a}\'';
$string['previouslyselectedframeworks'] = 'Previously selected frameworks not matching \'{$a}\'';
$string['previouslyselectedtemplates'] = 'Previously selected templates not matching \'{$a}\'';
$string['privacy:metadata'] = 'The Local IOMAD plugin only shows data stored in other locations.';
$string['privacy:metadata:company_users'] = 'Company users';
$string['privacy:metadata:company_users:companyid'] = 'Company users company ID';
$string['privacy:metadata:company_users:departmentid'] = 'Company users department ID';
$string['privacy:metadata:company_users:managertype'] = 'Company users manager type';
$string['privacy:metadata:company_users:suspended'] = 'Company users suspended flag';
$string['privacy:metadata:company_users:userid'] = 'Company users user ID';
$string['privacy:metadata:companylicense_users'] = 'Company license users';
$string['privacy:metadata:companylicense_users:groupid'] = 'Company license users group ID';
$string['privacy:metadata:companylicense_users:issuedate'] = 'Company license user issue date';
$string['privacy:metadata:companylicense_users:isusing'] = 'Company license users \'isusing\' flag';
$string['privacy:metadata:companylicense_users:licensecourseid'] = 'Company license users license course ID';
$string['privacy:metadata:companylicense_users:licenseid'] = 'Company license users license ID';
$string['privacy:metadata:companylicense_users:result'] = 'Company license users result';
$string['privacy:metadata:companylicense_users:score'] = 'Company license users score';
$string['privacy:metadata:companylicense_users:timecompleted'] = 'Company license users time completed';
$string['privacy:metadata:companylicense_users:userid'] = 'Company license users user ID';
$string['privacy:metadata:local_iomad_track'] = 'Local IOMAD track user information';
$string['privacy:metadata:local_iomad_track:companyid'] = 'User company ID';
$string['privacy:metadata:local_iomad_track:courseid'] = 'Course ID';
$string['privacy:metadata:local_iomad_track:coursename'] = 'Course name.';
$string['privacy:metadata:local_iomad_track:finalscore'] = 'Course final score';
$string['privacy:metadata:local_iomad_track:id'] = 'Local IOMAD track ID';
$string['privacy:metadata:local_iomad_track:licenseallocated'] = 'Unix timestamp of time license was allocated';
$string['privacy:metadata:local_iomad_track:licenseid'] = 'Licese ID';
$string['privacy:metadata:local_iomad_track:licensename'] = 'License name';
$string['privacy:metadata:local_iomad_track:modifiedtime'] = 'Record modified time';
$string['privacy:metadata:local_iomad_track:timecompleted'] = 'Course time completed';
$string['privacy:metadata:local_iomad_track:timeenrolled'] = 'Course time enroled';
$string['privacy:metadata:local_iomad_track:timestarted'] = 'Course time started';
$string['privacy:metadata:local_iomad_track:userid'] = 'User ID';
$string['privacy:metadata:local_iomad_track_certs'] = 'Local iomad track certificate info';
$string['privacy:metadata:local_iomad_track_certs:filename'] = 'Certificate filename';
$string['privacy:metadata:local_iomad_track_certs:id'] = 'Local IOMAD track certificate record ID';
$string['privacy:metadata:local_iomad_track_certs:trackid'] = 'Certificate track ID';
$string['removelicenses'] = 'Deleted - Company course records and licenses';
$string['report_settings'] = 'Report settings';
$string['resetroles'] = 'Reset roles adhoc task';
$string['savecertificatetask'] = 'Adhoc task to store a certificate for a user on course completion';
$string['search'] = 'Search';
$string['searchoptions'] = 'Search options';
$string['setupiomad'] = 'Start setting up IOMAD';
$string['show_suspended_companies'] = 'Show suspended companies?';
$string['show_suspended_users'] = 'Show suspended users?';
$string['showinstructions'] = 'Show the self signup instructions on the login page';
$string['showinstructions_help'] = 'By default, Moodle will show the self signup instructions on the login page when self enrol is enabled. This allows them to be removed.';
$string['signup_settings'] = 'Signup settings';
$string['templateselectorautoselectunique'] = 'If only one template matches the search, select it automatically';
$string['templateselectorpreserveselected'] = 'Keep selected templates, even if they no longer match the search';
$string['templateselectorsearchanywhere'] = 'Match the search text anywhere in the template\'s name';
$string['templateselectortoomany'] = 'template_selector got more than one selected template, even though multi-select is false';
$string['toomanycoursesmatchsearch'] = 'Too many courses ({$a->count}) match \'{$a->search}\'';
$string['toomanycoursestoshow'] = 'Too many courses ({$a}) to show';
$string['toomanyframeworksmatchsearch'] = 'Too many frameworks ({$a->count}) match \'{$a->search}\'';
$string['toomanyframeworkstoshow'] = 'Too many frameworks ({$a}) to show';
$string['toomanytemplatesmatchsearch'] = 'Too many templates ({$a->count}) match \'{$a->search}\'';
$string['toomanytemplatestoshow'] = 'Too many templates ({$a}) to show';
$string['trainingevent_not_selected_task'] = 'Email reports - Training event not selected task';
$string['uploadcompletionresult'] = 'Upload completion file result';
$string['useemail'] = 'Force email to be username';
$string['useemail_help'] = 'Selecting this will remove the option for a user to select their own username. Their email address will be used instead.';
$string['userfilter'] = 'Filter results';
$string['usersearchfields'] = 'User search';
