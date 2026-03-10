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
 * Local IOMAD language strings
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Strings for component 'local_iomad', language 'en'
 */

$string['add_template_button'] = 'Override';
$string['addnewtemplate'] = 'Override a default template';
$string['addtemplateadhoc'] = 'Add new email template adhoc task';
$string['applytemplateset'] = 'Apply template set \'{$a}\' to companies';
$string['authenticationtypes'] = 'Select authentication types';
$string['authenticationtypes_desc'] = 'These are the authentication types which can be used for automatically assigning a user to a company.';
$string['autoenrol'] = 'Auto enrol user';
$string['autoenrol_help'] = 'Selecting this will automatically enrol new users onto non-licensed or self enrol courses assigned to the company.';
$string['autoenrol_unassigned'] = 'Auto enrol unassigned courses';
$string['autoenrol_unassigned_help'] = 'Selecting this will automatically enrol new users onto non-licensed or self enrol courses not assigned to any company.';
$string['backtocompanytemplates'] = 'Finish editing template set';
$string['body'] = 'Body';
$string['cachedef_allcompanycategories'] = 'Cache to hold categories which any company has access to.';
$string['cachedef_companycategories'] = 'Cache to hold categories which are assigned to a company.';
$string['cachedef_companycoursecategories'] = 'Cache to hold categories which a company has access to.';
$string['cannotcallusgetselectedcourse'] = 'You cannot call course_selector::get_selected_course if multi-select is true';
$string['cannotcallusgetselectedframework'] = 'You cannot call framework_selector::get_selected_framework if multi-select is true';
$string['cannotcallusgetselectedtemplate'] = 'You cannot call template_selector::get_selected_template if multi-select is true';
$string['cannotemailnontemporarypasswords'] = 'It is insecure to send passwords by email without forcing them to be changed on first login.';
$string['cc'] = 'CC address';
$string['ccother'] = 'Manual CC address';
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
$string['controls'] = 'Controls';
$string['course_expiry_warning_task'] = 'Email reports - Course expiry warning task';
$string['course_not_completed_task'] = 'Email reports - Course not completed task';
$string['course_not_started_task'] = 'Email reports - Course not started task';
$string['coursesearchfields'] = 'Course search';
$string['courseselectorautoselectunique'] = 'If only one course matches the search, select it automatically';
$string['courseselectorpreserveselected'] = 'Keep selected courses, even if they no longer match the search';
$string['courseselectorsearchanywhere'] = 'Match the search text anywhere in the course\'s name';
$string['courseselectortoomany'] = 'course_selector got more than one selected course, even though multi-select is false';
$string['courseswithoutcompletioncriteriacouunt'] = 'Number of courses which have no completion criteria = {$a}';
$string['courseswithoutcompletionenabledcouunt'] = 'Number of courses which do not have completion enabled = {$a}';
$string['crontask'] = 'IOMAD Cron';
$string['emailcrontask'] = 'IOMAD email processing';
$string['custom'] = 'custom';
$string['daily'] = 'Daily';
$string['dateformat'] = 'Date format';
$string['datesearchfields'] = 'Date search';
$string['default'] = 'default';
$string['defaultcompany'] = 'Default company users are assigned to';
$string['defaultrole'] = 'Role to be assigned';
$string['delete_template'] = 'Delete template';
$string['delete_template_button'] = 'Revert to default';
$string['delete_template_checkfull'] = 'Are you absolutely sure you want to revert {$a} to the default template?';
$string['deletecompany'] = 'Delete company adhoc task';
$string['deletetemplateset'] = 'Delete template set';
$string['deletetemplatesetfull'] = 'Are you absolutely sure you want to delete template set {$a}?';
$string['edit_template'] = 'Edit email template';
$string['editatemplate'] = 'Edit an override template';
$string['edittemplateset'] = 'Edit template set';
$string['iomad:email_add'] = 'Override Default Email Templates';
$string['iomad:email_delete'] = 'Revert to default email templates';
$string['iomad:email_edit'] = 'Edit email templates';
$string['iomad:email_list'] = 'List email templates';
$string['iomad:email_send'] = 'Send emails using templates';
$string['iomad:email_templateset_list'] = 'List saved email template sets';
$string['email_data'] = 'Data for substitutions';
$string['email_template'] = 'Email template \'{$a->name}\' for \'{$a->companyname}\'';
$string['email_template_send'] = 'Send message to all applicable users of \'{$a->companyname}\' using \'{$a->name}\'';
$string['email_templates_for'] = 'Email templates for \'{$a}\'';
$string['emailasusernamehelp'] = 'Enter your email address. This will be your username.';
$string['emaildelay'] = 'Email delay';
$string['emaildelay_help'] = 'Any IOMAD emails will have this value (in seconds) added to the send time by default. This allows for a default delay in sending, much like for forum posts, of any IOMAD email. Timings will still be impacted by the local_mail cron task, but this delay will be a minimum value.';
$string['emaildomaindoesntmatch'] = 'Your email domain is not in the list of accepted domains for this company.';
$string['emailfilter'] = 'Email address contains';
$string['emailrepeatday'] = 'Email re-send day**';
$string['emailrepeatday_help'] = 'This is the specific day that an email is re-sent.';
$string['emailrepeatinfo'] = '<p>**Only warning emails will repeat.</p>';
$string['emailrepeatperiod'] = 'Email re-send every**';
$string['emailrepeatperiod_help'] = 'This is how often an email is re-sent to the user.';
$string['emailrepeatvalue'] = 'Email re-send amount**';
$string['emailrepeatvalue_help'] = 'This is the maximum number of times that this email is re-sent.';
$string['emailtemplatename'] = 'Email template name';
$string['emailtemplates'] = 'Email templates';
$string['emailtemplatesets'] = 'Email template sets';
$string['emailtemplatesetsaved'] = 'Template set saved successfully';
$string['enable'] = 'Enable';
$string['enable_help'] = 'New users will be assigned to a company on creation when this is enabled.';
$string['enable_manager'] = 'Enable for managers';
$string['enable_supervisor'] = 'Enable for supervisors';
$string['enforce_username_match'] = 'Require username to match across tenants';
$string['enforce_username_match_help'] = 'When this option is selected, existing users in other tenants can only match on username (instead of using firstname,lastname and email address) to avoid being created as a new/separate user.';
$string['erroropeningzip'] = 'Error creating ZIP file: {$a}';
$string['firstnamefilter'] = 'First name contains';
$string['fixcertificatetask'] = 'Change certificate context to user context';
$string['fixcourseclearedtask'] = 'Ad-hoc task to update the \'coursecleared\' field in the stored completion records';
$string['fixduplicatetemplatesadhoc'] = 'Ad-hoc task to remove duplicate company email templates.';
$string['fixenrolleddatetask'] = 'Ad-hoc task to update the stored completion information to use the enrolment \'timecreated\' timestamp where this is not already set.';
$string['fixtracklicensetask'] = 'Ad-hoc task to fix stored records license information';
$string['fortnightly'] = 'Fortnightly';
$string['frameworkselectorautoselectunique'] = 'If only one framework matches the search, select it automatically';
$string['frameworkselectorpreserveselected'] = 'Keep selected frameworks, even if they no longer match the search';
$string['frameworkselectorsearchanywhere'] = 'Match the search text anywhere in the framework\'s name';
$string['frameworkselectortoomany'] = 'framework_selector got more than one selected framework, even though multi-select is false';
$string['from'] = 'From';
$string['fromother'] = 'Manual From address';
$string['fromothername'] = 'Manual From name';
$string['general_settings'] = 'General settings';
$string['importcompletionrecords'] = 'Import completion records';
$string['importcompletionsfromfile'] = 'Import completion information from file';
$string['importcompletionsfrommoodle'] = 'Import stored completion information from Moodle tables';
$string['importcompletionsfrommoodlefull'] = 'This will run an ad-hoc task to import all of the completion information from Moodle to the IOMAD reporting tables.';
$string['importcompletionsfrommoodlefullwitherrors'] = 'This will run an ad-hoc task to import SOME of the completion information from Moodle to the IOMAD reporting tables. Not all courses have completion enabled or criteria set up and their information will be missed out. If you want to know which courses these are, use the check link on the previous page.';
$string['importlangpackadhoc'] = 'Import language pack ad-hoc task';
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
$string['iomad:importtrackfrommoodle'] = 'Import completion information from Moodle tables';
$string['iomad_use_email_as_username'] = 'Use email address as user name';
$string['iomad_use_email_as_username_help'] = 'Selecting this will change the way a user\'s username is automatically created for a new user account in IOMAD so that it simply uses their email address';
$string['iomad_useicons'] = 'Use icons in IOMAD dashboard';
$string['iomad_useicons_help'] = 'Selecting this changes the dashboard icons to use images instead of Font Awesome characters.';
$string['iomad_use_mandatory_courses'] = 'Enable mandatory courses';
$string['iomad_use_mandatory_courses_help'] = 'Enabling mandatory courses allows for courses to be flagged as mandatory. Completion reports and the user\'s dashboard can then filter on these courses.';
$string['iomadcertificate_border'] = 'Default border for IOMAD Company certificate';
$string['iomadcertificate_borderdesc'] = 'This is the default border image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 800 pixels x 604 pixels.';
$string['iomadcertificate_logo'] = 'Default logo for IOMAD Company certificate';
$string['iomadcertificate_logodesc'] = 'This is the default logo image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 80 pixels high and have a transparent background.';
$string['iomadcertificate_signature'] = 'Default signature for IOMAD Company certificate';
$string['iomadcertificate_signaturedesc'] = 'This is the default signature image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be 31 pixels x 150 pixels and have a transparent background.';
$string['iomadcertificate_watermark'] = 'Default watermark for IOMAD Company certificate';
$string['iomadcertificate_watermarkdesc'] = 'This is the default watermark image used for the IOMAD Company certificate type. You can override it in the company edit pages. The uploaded image should be no more than 800 pixels x 604 pixels.';
$string['langpackinitialinstalladhoc'] = 'Adhoc task run at installation to ensure the lang packs are loaded into tool_customlang tables.';
$string['lastnamefilter'] = 'Last name contains';
$string['logininfo'] = 'Fill out the form below to create a new user. An email will be sent to the email address you specify to verify the account and allow access.';
$string['manager_completion_digest_task'] = 'Email reports - Manager recent completions digest task';
$string['manager_expiring_digest_task'] = 'Email reports - Manager courses expiring warning digest task';
$string['manager_warning_digest_task'] = 'Email reports - Manager courses not completed warning digest task';
$string['managetemplatesets'] = 'Manage template sets';
$string['migratetemplatesadhoc'] = 'Migrate email templates ad-hoc task';
$string['missingaccesstocourse'] = 'You\'re not allowed to do that.';
$string['missingtemplatesetname'] = 'Please enter a template set name';
$string['monthly'] = 'Monthly';
$string['nocertificatesfound'] = 'No certificates found to download';
$string['nomatchingcourses'] = 'No courses match \'{$a}\'';
$string['nomatchingframeworks'] = 'No frameworks match \'{$a}\'';
$string['nomatchingtemplates'] = 'No templates match \'{$a}\'';
$string['none'] = 'None';
$string['nopermissions'] = 'The IOMAD administrator has not given you permission to do this.';
$string['override'] = 'override';
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
$string['privacy:metadata:companylicense_users:courseid'] = 'Company license users license course ID';
$string['privacy:metadata:companylicense_users:licenseid'] = 'Company license users license ID';
$string['privacy:metadata:companylicense_users:result'] = 'Company license users result';
$string['privacy:metadata:companylicense_users:score'] = 'Company license users score';
$string['privacy:metadata:companylicense_users:timecompleted'] = 'Company license users time completed';
$string['privacy:metadata:companylicense_users:userid'] = 'Company license users user ID';
$string['privacy:metadata:local_email'] = 'IOMAD email information';
$string['privacy:metadata:local_email:body'] = 'Email body';
$string['privacy:metadata:local_email:courseid'] = 'Course ID';
$string['privacy:metadata:local_email:headers'] = 'Additional email header';
$string['privacy:metadata:local_email:id'] = 'ID of record in local_email table';
$string['privacy:metadata:local_email:invoiceid'] = 'IOMAD ecommerce invoice ID';
$string['privacy:metadata:local_email:senderid'] = 'Sender user ID';
$string['privacy:metadata:local_email:sent'] = 'Unix timestamp of when email was sent';
$string['privacy:metadata:local_email:subject'] = 'Email subject';
$string['privacy:metadata:local_email:templatename'] = 'Template name of email sent';
$string['privacy:metadata:local_email:userid'] = 'Recipient user ID';
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
$string['refreshlangpacks'] = 'Import lang pack string to tool_customlang';
$string['removelicenses'] = 'Deleted - Company course records and licenses';
$string['replyto'] = 'Reply to';
$string['replytoother'] = 'Manual Reply to';
$string['report_settings'] = 'Report settings';
$string['resetroles'] = 'Reset roles adhoc task';
$string['resettemplatefull'] = 'Do you want to reset the template for {$a} back to the default settings and language strings for the selected language?';
$string['resettemplatefulllangs'] = 'Do you want to reset the template for {$a} back to the default settings and language strings for all languages?';
$string['save'] = 'Save';
$string['save_to_override_default_template'] = 'Save to override default template';
$string['savecertificatetask'] = 'Adhoc task to store a certificate for a user on course completion';
$string['savetemplateset'] = 'Save as new template set';
$string['search'] = 'Search';
$string['searchoptions'] = 'Search options';
$string['select_course'] = 'Select course';
$string['select_email_var'] = 'Select email variable';
$string['send_button'] = 'Send';
$string['send_emails'] = 'Send emails';
$string['setdefault'] = 'Set default';
$string['setdefaulttemplatesetfull'] = 'Do you want to change the default company template set to {$a}?';
$string['setupiomad'] = 'Start setting up IOMAD';
$string['show_suspended_companies'] = 'Show suspended companies?';
$string['show_suspended_users'] = 'Show suspended users?';
$string['showinstructions'] = 'Show the self signup instructions on the login page';
$string['showinstructions_help'] = 'By default, Moodle will show the self signup instructions on the login page when self enrol is enabled. This allows them to be removed.';
$string['signature'] = 'Signature';
$string['signatureseparator'] = '<p>--</p>';
$string['signup_settings'] = 'Signup settings';
$string['subject'] = 'Subject';
$string['template_list_title'] = 'Email Templates';
$string['templateaddedok'] = 'Template was successfully added.';
$string['templateresetok'] = 'Template for {$a} has been reset successfully.';
$string['templateselectorautoselectunique'] = 'If only one template matches the search, select it automatically';
$string['templateselectorpreserveselected'] = 'Keep selected templates, even if they no longer match the search';
$string['templateselectorsearchanywhere'] = 'Match the search text anywhere in the template\'s name';
$string['templateselectortoomany'] = 'template_selector got more than one selected template, even though multi-select is false';
$string['templatesetdeleted'] = 'Template set deleted successfully.';
$string['templatesetname'] = 'Template set name';
$string['templatesetname_help'] = 'This is the name by which the template set will be referenced.';
$string['templatesetnamealreadyinuse'] = 'This template set name already exists';
$string['templatesetsetdefault'] = 'The default company template set has been updated.';
$string['templatesnoaccessigble'] = '<h4>The email templates are not currently accessible</h4><p>This is due to an adhoc migration task which is being run against them. Emails will still be sent out from the system as normal and access to the templates will return once the task has completed.</p>';
$string['templatetype'] = 'Template type';
$string['templateupdatedok'] = 'Template was successfully updated.';
$string['to'] = 'To';
$string['toomanycoursesmatchsearch'] = 'Too many courses ({$a->count}) match \'{$a->search}\'';
$string['toomanycoursestoshow'] = 'Too many courses ({$a}) to show';
$string['toomanyframeworksmatchsearch'] = 'Too many frameworks ({$a->count}) match \'{$a->search}\'';
$string['toomanyframeworkstoshow'] = 'Too many frameworks ({$a}) to show';
$string['toomanytemplatesmatchsearch'] = 'Too many templates ({$a->count}) match \'{$a->search}\'';
$string['toomanytemplatestoshow'] = 'Too many templates ({$a}) to show';
$string['toother'] = 'Manual To address';
$string['trainingevent_not_selected_task'] = 'Email reports - Training event not selected task';
$string['unsetdefault'] = 'Unset default';
$string['unsetdefaulttemplatesetfull'] = 'Do you want to remove the default company template set {$a}?';
$string['uploadcompletionresult'] = 'Upload completion file result';
$string['useemail'] = 'Force email to be username';
$string['useemail_help'] = 'Selecting this will remove the option for a user to select their own username. Their email address will be used instead.';
$string['userfilter'] = 'Filter results';
$string['usersearchfields'] = 'User search';
$string['weekly'] = 'Weekly';

/*Email template descriptors*/
$string['admin_deleted_name'] = 'Manager role removed';
$string['admin_deleted_name_help'] = 'This email is sent out when a manager role is removed from a user.';
$string['approval_name'] = 'Manager course request approval';
$string['approved_name'] = 'User course access approved';
$string['company_licenseassigned_name'] = 'License assigned to company';
$string['company_licenseassigned_name_help'] = 'This email is sent out to company managers when a license has been created for the company.';
$string['company_suspended_name'] = 'Company suspended';
$string['company_suspended_name_help'] = 'This email is sent out to company managers when their company is suspended.';
$string['company_unsuspended_name'] = 'Company unsuspended';
$string['company_unsuspended_name_help'] = 'This email is sent out to company managers when their company is unsuspended';
$string['completion_course_user_name'] = 'User course completion';
$string['completion_course_user_name_help'] = 'This email is sent out when a user successfully completes a course';
$string['course_classroom_approval_name'] = 'Manager training event approval request';
$string['course_classroom_approved_name'] = 'User training event access approved';
$string['course_classroom_approved_teacher_name'] = 'User training event access approved - teacher email';
$string['course_classroom_denied_name'] = 'User training event access denied';
$string['course_classroom_manager_denied_name'] = 'Department manager training event access denied';
$string['course_classroom_approval_request_name'] = 'User training event request confirmation';
$string['courseclassroom_approved_name'] = 'User training event approved';
$string['course_completed_manager_name'] = 'Manager course completed report';
$string['course_not_started_warning_name'] = 'Course not started warning';
$string['course_not_started_warning_name_help'] = 'This email is sent out to a user when they have been given access to a course and have not started it within the defined timeframe in the IOMAD course settings.';
$string['user_added_to_course_name'] = 'User enroled on course';
$string['invoice_ordercomplete_name'] = 'User invoice order created';
$string['invoice_ordercomplete_admin_name'] = 'Admin invoice order created';
$string['advertise_classroom_based_course_name'] = 'Advertise training event';
$string['user_signed_up_for_event_name'] = 'User training event sign up';
$string['user_signed_up_for_event_reminder_name'] = 'User training event reminder';
$string['user_signed_up_for_event_teacher_name'] = 'User training event sign up - teacher';
$string['user_signed_up_to_waitlist_name'] = 'User training event waiting list sign up';
$string['user_removed_from_event_name'] = 'User training event cancelled';
$string['user_removed_from_event_teacher_name'] = 'User training event cancelled - teacher';
$string['user_removed_from_event_waitlist_name'] = 'User training event removed from waiting list';
$string['license_allocated_name'] = 'User license allocated';
$string['licensepoolexpiring_name'] = 'License expiry date warning';
$string['licensepoolexpiring_name_help'] = 'This email is sent out to company managers when a license is about to expire.';
$string['licensepoolwarning_name'] = 'License usage warning';
$string['licensepoolwarning_name_help'] = 'This email is sent out when the number of allocated slots in the license is reached';
$string['license_reminder_name'] = 'User license activation reminder';
$string['license_removed_name'] = 'User course license revoked';
$string['password_update_name'] = 'User password changed';
$string['completion_warn_user_name'] = 'User course completion warning';
$string['completion_warn_manager_name'] = 'Manager course completion warning report';
$string['completion_digest_manager_name'] = 'Manager course completions weekly report - digest';
$string['expiring_digest_manager_name'] = 'Manager courses expiring weekly warning report - digest';
$string['warning_digest_manager_name'] = 'Manager courses not completed weekly warning report - digest';
$string['expiry_warn_user_name'] = 'User training expiry warning';
$string['expiry_warn_manager_name'] = 'Manager training expiry warning';
$string['expire_name'] = 'User training expired';
$string['expire_manager_name'] = 'Manager training expired report';
$string['trainingevent_not_selected_name'] = 'Training event not selected';
$string['trainingevent_not_selected_name_help'] = 'This email is sent out to a user if they have been enroled on a course with a training event and have not yet signed up for one. It uses the \'Warn not started\' field for control.';
$string['user_reset_name'] = 'User account reset';
$string['user_create_name'] = 'User account created';
$string['user_deleted_name'] = 'User deleted';
$string['user_deleted_name_help'] = 'This email is sent out to a user when their account is deleted.';
$string['user_programcompleted_name'] = 'User course program completed';
$string['user_programcompleted_name_help'] = 'This email is sent out when a user completes all of the courses within a program license.';
$string['user_promoted_name'] = 'User promoted to manager';
$string['user_promoted_name_help'] = 'This email is sent out when a user is promoted to a manager in a company.';
$string['user_suspended_name'] = 'User suspended';
$string['user_suspended_name_help'] = 'This email is sent out to a user when their account is suspended.';
$string['user_unsuspended_name'] = 'User unsuspended';
$string['user_unsuspended_name_help'] = 'This email is sent out to a user when their account is unsuspended.';
$string['completion_course_supervisor_name'] = 'User\'s supervisor completion report';
$string['completion_warn_supervisor_name'] = 'User\'s supervisor course completion warning.';
$string['completion_expiry_warn_name'] = 'User\'s training expired warning';
$string['completion_expiry_warn_supervisor_name'] = 'User\'s supervisor training expired warning';
$string['approval_name_help'] = 'Template sent out to managers when a user has asked for approval to a course.';
$string['approved_name_help'] = 'Template sent out to users when they have been granted access to a course.';
$string['course_classroom_approval_name_help'] = 'Template sent out to managers when a user has asked for approval to a training event.';
$string['course_classroom_approved_name_help'] = 'Template sent out to users when they have been granted access to a training event.';
$string['course_classroom_approved_teacher_name_help'] = 'Template sent out to course teachers when a user has been granted access to a training event.';
$string['course_classroom_denied_name_help'] = 'Template sent out to users when they have been denied access to a training event.';
$string['course_classroom_manager_denied_name_help'] = 'Template sent out to department manager when a user has been denied access to a training event.';
$string['course_classroom_approval_request_name_help'] = 'Template sent out to users when they request access to a training event.';
$string['courseclassroom_approved_name_help'] = 'Template sent out to users when they have been granted access to a training event.';
$string['course_completed_manager_name_help'] = 'Template sent out to a manager when a user completes a course.';
$string['user_added_to_course_name_help'] = 'Template sent out to users when they are enroled on a course.';
$string['invoice_ordercomplete_name_help'] = 'Template sent out to a user when they raise an invoice order in the shop.';
$string['invoice_ordercomplete_admin_name_help'] = 'Template sent out to the shop admin when an invoice order is generated.';
$string['advertise_classroom_based_course_name_help'] = 'Template sent out when a manager advertises a new training event.';
$string['user_signed_up_for_event_name_help'] = 'Template sent out to a user when they sign up for a training event which doesn\'t require manager approval.';
$string['user_signed_up_for_event_reminder_name_help'] = 'Template sent out to remind a user they are signed up for a training event.';
$string['user_signed_up_for_event_teacher_name_help'] = 'Template sent out to teachers when a user has signed up for a training event which doesn\'t require manager approval.';
$string['user_signed_up_to_waitlist_name_help'] = 'Template sent out to a user when they sign on to the waiting list for a training event.';
$string['user_removed_from_event_name_help'] = 'Template sent out to a user for confirmation when they have been removed from a training event.';
$string['user_removed_from_event_teacher_name_help'] = 'Template sent out to teacher when a user has been removed from a training event.';
$string['user_removed_from_event_waitlist_name_help'] = 'Template sent out to a user for confirmation when they have been removed from a training event waiting list.';
$string['license_allocated_name_help'] = 'Template sent out to a user when they have been allocated a license on a course.';
$string['license_reminder_name_help'] = 'Template sent out to a user when a manager sends them a reminder that they have not yet accessed a course they were given a license for.';
$string['license_removed_name_help'] = 'Template sent out to a user when a course license has been revoked.';
$string['password_update_name_help'] = 'Template sent out to a user when their password has been changed by a manager.';
$string['completion_warn_user_name_help'] = 'Template sent out to a user when they have not completed a course in the configured time.';
$string['completion_warn_manager_name_help'] = 'Template sent out to a manager informing them that a user has not completed a course in the configured time.';
$string['completion_digest_manager_name_help'] = 'Template sent out to a manager informing them of the users that completed courses in the last week. The manager emails are sent as a digest.';
$string['expiring_digest_manager_name_help'] = 'Template sent out to a manager informing them that users have courses expiring in the next week. The manager emails are sent as a digest.';
$string['warning_digest_manager_name_help'] = 'Template sent out to a manager informing them that users have not completed courses in the last week. The manager emails are sent as a digest.';
$string['expiry_warn_user_name_help'] = 'Template sent out to a user when their training in a course is due to expire.';
$string['expiry_warn_manager_name_help'] = 'Template sent out to managers informing them of users whose training is due to expire.';
$string['expire_name_help'] = 'Template sent out to a user when their training in a course has expired.';
$string['expire_manager_name_help'] = 'Template sent out to a manager informing them of any users whose training has expired.';
$string['user_reset_name_help'] = 'Template sent out to a user when a manager resets their user information.';
$string['user_create_name_help'] = 'Template sent out to a new user when a new account has been created.';
$string['completion_course_supervisor_name_help'] = 'Template sent out to a user\'s supervisor email address (if defined) when a user completed a course.';
$string['completion_warn_supervisor_name_help'] = 'Template sent out to a user\'s supervisor email address (if defined) when a user has not completed a course in the configured time.';
$string['completion_expiry_warn_name_help'] = 'Template sent out to a user when their training has expired.';
$string['completion_expiry_warn_supervisor_name_help'] = 'Template sent out to a users supervisor email address (if defined) when a user\'s training has expired.';


/* Email templates */
$string['approval_subject'] = 'New course approval';
$string['approval_body'] = '<p>You have been asked to approve access to course {Course_FullName} for {User_FirstName} {User_LastName}.</p><p>Please log onto {Site_FullName} (<a href="{SiteURL}">{SiteURL}</a>) to approve or deny this request.</p>';
$string['approved_subject'] = 'You have been approved access to {Course_FullName}.';
$string['approved_body'] = '<p>You have been granted access to course {Course_FullName}. To access this, please click on <a href="{CourseURL}">{CourseURL}</a>.</p>';
$string['course_classroom_approval_subject'] = 'New face-to-face training event approval.';
$string['course_classroom_approval_body'] = '<p>You have been asked to approve access to the face-to-face training course {Event_Name} for {Approveuser_FirstName} {Approveuser_LastName} at the following event -</p><br>
Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>{Classroom_City} {Classroom_Postcode}</br></br>
<p>Please log onto {Site_FullName} ({SiteURL}) to approve or deny this request.</p>';

$string['course_classroom_approved_subject'] = 'You have been approved access to {Event_Name}.';
$string['course_classroom_approved_body'] = '<p>You have been approved access to the face to face training course {Event_Name} at the following event -</p>
<br>
Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
         {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_approved_teacher_subject'] = 'User approved to Face to face training event.';
$string['course_classroom_approved_teacher_body'] = '<p>{Approveuser_FirstName} {Approveuser_LastName} has been granted access to the face to face training course {Event_Name} at the following event -</p>
<br>
Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
         {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_denied_subject'] = 'Face to face training event approval denied.';
$string['course_classroom_denied_body'] = '<p>Your approval request has been rejected for {Event_Name} at the following event -</p>
<br>
Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
         {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_manager_denied_subject'] = 'Face to face training event approval denied by company manager.';
$string['course_classroom_manager_denied_body'] = '<p>The approval request for {Approveuser_FirstName} {Approveuser_LastName} has been rejected by {User_FirstName} {User_LastName} ({User_Email}) for {Event_Name} at the following event -</p>
<br>
Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
         {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_approval_request_subject'] = 'New face to face training event approval request sent.';
$string['course_classroom_approval_request_body'] = '<p>You have asked for access to the face to face training course {Event_Name} at the following event -</p>
<br>
Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
         {Classroom_City} {Classroom_Postcode}<br>
<p>You will be notified once your manager has approved or denied access.</p>';

$string['courseclassroom_approved_subject'] = 'You have been approved access to {Event_Name}.';
$string['courseclassroom_approved_body'] = '<p>You have been granted access to the training event {Event_Name}. To access this, please click on <a href="{CourseURL}">{CourseURL}</a>.<p>';

$string['course_completed_manager_subject'] = 'Student course completion report';
$string['course_completed_manager_body'] = '<p>Dear {User_FirstName}</p>
<p>{Course_ReportText}</p>';

$string['user_added_to_course_subject'] = 'Added to {Course_FullName}';
$string['user_added_to_course_body'] = '<p>Dear {User_FirstName}</p>
<br>
<p>You have been granted access to the online training for {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to partake in this training.</p>';

$string['invoice_ordercomplete_subject'] = 'Thank you for your order at {Site_ShortName}';
$string['invoice_ordercomplete_body'] = '<p>Dear {User_FirstName} {User_LastName}</p>
<p>Your order reference is {Invoice_Reference}</p>
<p>Thank you for your order of the following:</p>
<p>{Invoice_Itemized}</p>
<p>Once this invoice has been paid, licenses will be created or enrolments will be created by the administrator.</p>';

$string['invoice_ordercomplete_admin_subject'] = 'Ecommerce order (invoice {Invoice_Reference})';
$string['invoice_ordercomplete_admin_body'] = '<p>Dear ecommerce admin</p>
<p>The following order has just been submitted by {Invoice_FirstName} {Invoice_LastName} of {Invoice_Company}.</br>
An invoice has been sent to them via email.</p>
<p>{Invoice_Itemized}</p>';

$string['advertise_classroom_based_course_subject'] = 'Course {Course_FullName}';
$string['advertise_classroom_based_course_body'] = '<p>This to let you know about the following face-to-face training course:</p>
<p>{Course_FullName}</p>
<p>It will be in {Classroom_Name}, which is at</p>
<p>{Classroom_Address}</br>{Classroom_City} {Classroom_Postcode}</br>
{Classroom_Country}</br>
<p>and has a capacity of {Classroom_Capacity}.</p>
<p>Please click on <a href="{CourseURL}">{CourseURL}</a> to find out more about this course and book on this event.</p>';

$string['user_signed_up_for_event_subject'] = 'Attendance notice {Course_FullName}';
$string['user_signed_up_for_event_body'] = '<p>Dear {User_FirstName},</p>
<p>You have signed up for the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}</br>
<p>Please ensure you have completed any pre-course tasks required before attendance</p>';

$string['user_signed_up_for_event_reminder_subject'] = 'Attendance reminder {Course_FullName}';
$string['user_signed_up_for_event_reminder_body'] = '<p>Dear {User_FirstName},</p>
<p>This is to remind you that you have signed up for the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>{Classroom_City} {Classroom_Postcode}</br>
<p>Please ensure you have completed any pre-course tasks required before attendance</p>';

$string['user_removed_from_event_subject'] = 'Cancellation notice {Course_FullName}';
$string['user_removed_from_event_body'] = '<p>Dear {User_FirstName},</p>
<p>you have been marked as no longer attending the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}';

$string['user_signed_up_for_event_teacher_subject'] = 'User attending notice {Course_FullName}';
$string['user_signed_up_for_event_teacher_body'] = '<p>Dear {User_FirstName},</p>
<p>{Approveuser_FirstName} {Approveuser_LastName} has signed up for the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}</br>';

$string['user_signed_up_to_waitlist_subject'] = 'Added to waiting list for {Course_FullName}';
$string['user_signed_up_to_waitlist_body'] = '<p>Dear {User_FirstName},</p>

<p>You have been added to the <b>waiting list</b> for the face-to-face training on {Course_FullName} at the following event:</p>

<p>Time: {Classroom_Time}<br>
Location: {Classroom_Name}<br>
Address: {Classroom_Address}<br>
          {Classroom_City} {Classroom_Postcode}<br>

<p>You do not currently have a confirmed place, but will be informed if this changes.</p>';
$string['user_removed_from_event_teacher_subject'] = 'User cancellation notice {Course_FullName}';
$string['user_removed_from_event_teacher_body'] = '<p>Dear {User_FirstName},</p>
<p>{Approveuser_FirstName} {Approveuser_LastName} is no longer attending the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}';
$string['user_removed_from_event_waitlist_subject'] = 'Waiting list removal notice {Course_FullName}';
$string['user_removed_from_event_waitlist_body'] = '<p>Dear {User_FirstName},</p>
<p>you have been removed from the waitinglist for the face-to-face training on {Course_FullName} at the following event -</p>
<p>Time: {Classroom_Time}</br>
Location: {Classroom_Name}</br>
Address: {Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}';
$string['license_allocated_subject'] = 'Access to course {Course_FullName} granted';
$string['license_allocated_body'] = '<p>Dear {User_FirstName},</p>
<p>You have been granted access to the online training for {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to partake in this training.</br>
Once you have entered the course you will have access to it for {License_Length} days. Unused access will expire after {License_Valid}</p>';
$string['license_reminder_subject'] = 'Reminder: you have been allocated the course {Course_FullName}';
$string['license_reminder_body'] = '<p>Dear {User_FirstName},</p><p>You have been granted access to the online training for {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to partake in this training.</br>Once you have entered the course you will have access to it for {License_Length} days. Unused access will expire after {License_Valid}</p>';
$string['license_removed_subject'] = 'Access to course {Course_FullName} removed';
$string['license_removed_body'] = '<p>Your access to course {Course_FullName} has been revoked. If you feel this is in error, please contact your training manager.</p>';
$string['password_update_subject'] = 'Password change notification for {User_FirstName}';
$string['password_update_body'] = '<p>Your password has been updated by the administrative staff. Your new password is</p>
<p>{User_Newpassword}</p>
<p>Please visit <a href="{LinkURL}">{LinkURL}</a> to change this.</p>';
$string['course_not_started_warning_subject'] = 'Notice: Course {Course_FullName} has not been started';
$string['course_not_started_warning_body'] = '<p>Dear {User_FirstName},</p><p>You have still not yet started your training on {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to rectify this.</p>';
$string['trainingevent_not_selected_subject'] = 'Notice: Training event not selected in course {Course_FullName}';
$string['trainingevent_not_selected_body'] = '<p>Dear {User_FirstName}.</p><p>You have not signed up for any training events available in {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to rectify this.</p>';
$string['completion_warn_user_subject'] = 'Notice: Course {Course_FullName} has not been completed';
$string['completion_warn_user_body'] = '<p>Dear {User_FirstName},</p>
<p>You have still not completed your training on {Course_FullName}. Please visit <a href="{CourseURL}">{CourseURL}</a> to rectify this.</p>';
$string['completion_warn_manager_subject'] = 'User completion failure report';
$string['completion_warn_manager_body'] = '<p>Dear {User_FirstName},</p>
<p>The following users have not completed their training within the normal timeframe:</p>
<p>{Course_ReportText}</p>';
$string['completion_digest_manager_subject'] = 'User course completions weekly report';
$string['completion_digest_manager_body'] = '<p>Dear {User_FirstName},</p>
<p>The following users have completed their training within the last week:</p>
<p>{Course_ReportText}</p>';
$string['expiring_digest_manager_subject'] = 'User courses expiring weekly report';
$string['expiring_digest_manager_body'] = '<p>Dear {User_FirstName},</p>
<p>The following users have training expiring within the next week:</p>
<p>{Course_ReportText}</p>';
$string['warning_digest_manager_subject'] = 'User courses not completed weekly report';
$string['warning_digest_manager_body'] = '<p>Dear {User_FirstName},</p>
<p>The following users have not completed their training within the required time limit:</p>
<p>{Course_ReportText}</p>';
$string['expiry_warn_user_subject'] = 'Notice: Accreditation in {Course_FullName} will expire soon.';
$string['expiry_warn_user_body'] = '<p>Dear {User_FirstName},</p>
<p>Your accredited training on {Course_FullName} is expiring soon. Please arrange for re-accreditation if appropriate.</p>';
$string['expiry_warn_manager_subject'] = 'Accreditation expiry report';
$string['expiry_warn_manager_body'] = '<p>Dear {User_FirstName},</p>
<p>The following users accreditation is due to expire soon:</p>
<p>{Course_ReportText}</p>';
$string['expire_subject'] = 'Course expires';
$string['expire_body'] = '<p>This is to let you know that your training in {Course_FullName} expires soon.</p>';
$string['expire_manager_subject'] = 'Accreditation expired report for {Course_FullName}';
$string['expire_manager_body'] = '<p>Dear {User_FullName},</p>
<p>The following users accreditation in {Course_FullName} has expired:</p>
<p>{User_ReportText}</p>';
$string['user_reset_subject'] = 'The login details for your account have been reset';
$string['user_reset_body'] = '<p>Dear {User_FirstName},</p>
<p>A new user account has been created for you on the \'Training Management System\' and you have been issued with a new temporary password.</p>
<p>Your current login information is now:<p>
<p>username: {User_Username}</br>
password: {User_Newpassword}</br>
(you will have to change your password when you login for the first time)</p>
<p>Best Regards,</p>
<p>{Sender_FirstName} {Sender_LastName}</p>';
$string['user_create_subject'] = 'A new on-line learning account has been created for you';
$string['user_create_body'] = '<p>Dear {User_FirstName},</p>
<p>A new user account has been created for you on {Site_FullName} and you have been issued with a new temporary password.</p>
<p>Your current login information is now:<p>
<p>username: {User_Username}</br>
password: {User_Newpassword}</br>
(you will have to change your password when you login for the first time)</p>
<p>To access your training, login at</p>
<p><a href="{LinkURL}">{LinkURL}</a></p>
<p>In most mail programs, this should appear as a blue, clickable link. If that doesn\'t work, then copy and paste the address into the address bar at the top of your web browser window.</p>
<p>For technical queries, please contact your IT Support team/Helpdesk</p>
<p>Best Regards,</p>
<p>{Sender_FirstName} {Sender_LastName}</p>';
$string['completion_course_supervisor_subject'] = 'Notice: Course {Course_FullName} has been completed';
$string['completion_course_supervisor_body'] = '<p>{User_FirstName} {User_LastName} has completed the training course {Course_FullName}. Please find attached a copy of their certificate for your records.</p>

<p>The certificate is also available from the User Report section on our system should you need a copy in the future.</p>';
$string['completion_course_user_subject'] = 'Course {Course_FullName} has been completed';
$string['completion_course_user_body'] = '<p>Dear {User_FirstName},</p>
<p>Congratulations on completing the training course {Course_FullName}. Please find attached a copy of their certificate for your records.</p>

<p>The certificate is also available from your dashboard should you need a copy in the future.</p>';
$string['user_programcompleted_subject'] = 'Program of courses is complete';
$string['user_programcompleted_body'] = '<p>Dear {User_FirstName} {User_LastName},</p>
<p>Congratulations! You have completed all of the courses within your training program.</p>';
$string['completion_warn_supervisor_subject'] = 'Notice: Course {Course_FullName} has not been completed';
$string['completion_warn_supervisor_body'] = '<p>{User_FirstName} {User_LastName} has not completed their training in course {Course_FullName} within the normal timeframe.</p>';
$string['completion_expiry_warn_supervisor_subject'] = 'Notice: Course {Course_FullName} training expiry';
$string['completion_expiry_warn_supervisor_body'] = '<p>The training for {User_FirstName} {User_LastName} in course {Course_FullName} will expire shortly. Please arrange for them to retake this training if appropriate.</p>';
$string['licensepoolwarning_subject'] = '90% License pool used {License_Name}, {License_ID}';
$string['licensepoolwarning_body'] = '<p>Hi {User_FirstName} {User_LastName}</p>
<p>This message is to notify your company account {Company_Name} has used 90% of the license pool {License_Name}, {License_ID} . You will not be able to allocate further licenses once you reach 100% usage.</p>
<p>Please contact your Program Manager for details.</p>';
$string['licensepoolexpiring_subject'] = 'Alert: License pool expiration {License_Name} {License_Expirydate}, {License_ID}';
$string['licensepoolexpiring_body'] = '<p>Hi {User_FirstName} {User_LastName}</p>
<p>This message is to notify your company account {Company_Name} that that your program license {License_Name}, {License_ID} is expiring on {License_Expirydate}. You will not be able to allocate further licenses to users once you reach the expiration date. Please contact your Program Manager if you have further questions.</p>';
$string['user_promoted_subject'] = 'New role granted';
$string['user_promoted_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>You have been granted admin privileges. To access the administrative suite, tracking and reporting tools please click the following link, or copy and paste the link into your browser, to set your password and login {SiteURL}</p>
<p>On the login page, Click Activate button to activate your account for first time login.</p>
<p>Your email: {User_Email}</p>';
$string['user_deleted_subject'] = 'Account has been deleted';
$string['user_deleted_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your account has been deleted on {SiteURL}. You no longer have access to any of your training courses.</p>
<p>If you feel that this is in error, please contact your manager.</p>
<p>Your email: {User_Email}</p>';
$string['admin_deleted_subject'] = 'Account has been demoted';
$string['admin_deleted_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your admin privileges have been revoked on {SiteURL}. You no longer have access to manage your company.</p>
<p>Your email: {User_Email}</p>
<p>If you feel that this is in error, please contact your manager.</p>';
$string['user_suspended_subject'] = 'Account has been suspended';
$string['user_suspended_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your account has been suspended on {SiteURL}. You no longer have access to any of your training courses.</p>
<p>If you feel that this is in error, please contact your manager.</p>
<p>Your email: {User_Email}</p>';
$string['user_unsuspended_subject'] = 'Account has been unsuspended';
$string['user_unsuspended_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your account has been unsuspended on {SiteURL}. You now have access to any of your training courses.</p>
<p>If you have any further questions, please contact your manager.</p>
<p>Your email: {User_Email}</p>';
$string['company_suspended_subject'] = 'Company account has been suspended';
$string['company_suspended_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your company account has been suspended on {SiteURL}. You no longer have access to manage your company.</p>
<p>Your email: {User_Email}</p>
<p>If you feel that this is in error, please contact support.</p>';
$string['company_unsuspended_subject'] = 'Company account has been unsuspended';
$string['company_unsuspended_body'] = '<p>Hello {User_FirstName} {User_LastName},</p>
<p>Your company account has been unsuspended on {SiteURL}. You now have access to manage your company.</p>
<p>Your email: {User_Email}</p>
<p>If you have any further questions, please contact support.</p>';
$string['company_licenseassigned_subject'] = 'New Training assigned to {Company_Name}';
$string['company_licenseassigned_body'] = '<p>Dear {User_FirstName} {User_LastName},</p>
<p>New courses have been allocated to your company on {SiteURL}.</p>
<p>Please log in using your username {User_Username} to manage this.</p>';
$string['microlearning_nugget_scheduled_name'] = 'Microlearning nugget scheduled for user';
$string['microlearning_nugget_scheduled_name_help'] = 'This email is sent out to a user when a microlearning nugget within a microlearning thread is scheduled.';
$string['microlearning_nugget_scheduled_subject'] = 'New microlearning nugget for you.';
$string['microlearning_nugget_scheduled_body'] = '<p>Hi {User_FirstName}</p>
<p>You have a new microlearning nugget to complete. You can access this by clicking on <a href="{Nugget_URL}">{Nugget_Name}</a></p>';
$string['microlearning_nugget_reminder1_name'] = 'Microlearning nugget first reminder for user';
$string['microlearning_nugget_reminder1_name_help'] = 'This email is sent out to a user when a microlearning nugget within a microlearning thread reaches the frst reminder and has not yet been completed.';
$string['microlearning_nugget_reminder1_subject'] = 'Reminder - New microlearning nugget for you.';
$string['microlearning_nugget_reminder1_body'] = '<p>Hi {User_FirstName}</p>
<p>You have not yet completed your microlearning nugget. You can access this by clicking on <a href="{Nugget_URL}">{Nugget_Name}</a> or by logging into the <a href="{SiteURL}">Site</a></p>';
$string['microlearning_nugget_reminder2_name'] = 'Microlearning nugget second reminder for user';
$string['microlearning_nugget_reminder2_name_help'] = 'This email is sent out to a user when a microlearning nugget within a microlearning thread reaches the frst reminder and has not yet been completed.';
$string['microlearning_nugget_reminder2_subject'] = 'Reminder - New microlearning nugget for you.';
$string['microlearning_nugget_reminder2_body'] = '<p>Hi {User_FirstName}</p>
<p>You have not yet completed your microlearning nugget. You can access this by clicking on <a href="{Nugget_URL}">{Nugget_Name}</a> or by logging into the <a href="{SiteURL}">Site</a></p>';
