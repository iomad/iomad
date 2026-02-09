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
 * autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onward Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alwaysenrol'] = 'Always Enrol';
$string['alwaysenrol_help'] = 'When set to Yes the plugins will always enrol users, even if they already have access to the course through another method.';
$string['auto'] = 'Auto';
$string['auto_desc'] = 'This group has been automatically created by the Auto Enrol plugin. It will be deleted if you remove the Auto Enrol plugin from the course.';
$string['autoenrol:config'] = 'Configure automatic enrolments';
$string['autoenrol:hideshowinstance'] = 'User can enable or disable autoenrol instances';
$string['autoenrol:manage'] = 'Manage autoenrolled users';
$string['autoenrol:method'] = 'User can enrol users onto a course at login';
$string['autoenrol:unenrol'] = 'User can unenrol autoenrolled users';
$string['autoenrol:unenrolself'] = 'User can unenrol themselves if they are being enrolled on access';
$string['autounenrolaction'] = 'Auto unenrol action';
$string['autounenrolaction_help'] = 'Select the action to carry out when the user filtering rule is no more matched. Please note that some user data and settings are purged from course during course unenrolment.';
$string['availabilityplugins'] = 'Enabled availability plugins';
$string['availabilityplugins_help'] = 'Select availability plugins that could be used in Autoenrol user filter. Use Ctrl+click or Cmd+click for multiple selection.';
$string['cannotenrol'] = 'You can\'t enrol to this course using auto enrol.';
$string['config'] = 'Configuration';
$string['countlimit'] = 'Limit';
$string['countlimit_help'] = 'This instance will count the number of enrolments it makes on a course and can stop enrolling users once it reaches a certain level. The default setting of 0 means unlimited.';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}
* Link to course {$a->link}
* User email {$a->email}
* User fullname {$a->fullname}';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during automatic enrolments';
$string['deleteselectedusers'] = 'Delete selected user enrolments';
$string['editselectedusers'] = 'Edit selected user enrolments';
$string['emptyfield'] = 'No {$a}';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users will be enrolled until this date only.';
$string['enrolme'] = 'Enrol me';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user enrols themselves. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users will be enrolled from this date onward only.';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'Autoenrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Autoenrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'Autoenrolment expiry notification';
$string['expirynotifyall'] = 'Teacher and enrolled user';
$string['expirynotifyenroller'] = 'Teacher only';
$string['filter'] = 'Allow Only';
$string['filter_help'] = 'When a group focus is selected you can use this field to filter which type of user you wish to enrol onto the course. For example, if you grouped by authentication and filtered with "manual" only users who have registered directly with your site would be enrolled.';
$string['filtering'] = 'User Filtering';
$string['g_auth'] = 'Auth Method';
$string['g_dept'] = 'Department';
$string['g_email'] = 'Email Address';
$string['g_inst'] = 'Institution';
$string['g_lang'] = 'Language';
$string['g_none'] = 'Select...';
$string['general'] = 'General';
$string['groupname'] = 'Group name';
$string['groupname_help'] = 'When you group by User Filter only a group will be created and this will be the name of the group.';
$string['groupon'] = 'Group By';
$string['groupon_help'] = 'AutoEnrol can automatically add users to a group when they are enrolled based upon one of these user fields.';
$string['instancename'] = 'Custom Label';
$string['instancename_help'] = 'You can add a custom label to make it clear what this enrolment method does. This option is most useful when there are multiple instances of AutoEnrol on one course.';
$string['loginenrol'] = 'Allow enrolments on login';
$string['loginenrol_desc'] = 'Allow enrolment on login could slowdown your site performance. As an alternative you can use the scheduled task to update enrolments for all courses or the cli command for specific courses.';
$string['longtimenosee'] = 'Unenrol inactive after';
$string['longtimenosee_help'] = 'If users haven\'t accessed a course for a long time, then they are automatically unenrolled. This parameter specifies that time limit.';
$string['m_confirmation'] = 'Confirmation on enrol screen';
$string['m_course'] = 'Entering the Course';
$string['m_site'] = 'Logging into Site';
$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can autoenrol. 0 means no limit.';
$string['messageprovider:expiry_notification'] = 'Autoenrol enrolment expiry notifications';
$string['method'] = 'Enrol When';
$string['method_help'] = 'Power users can use this setting to change the plugin\'s behaviour so that users are enrolled to the course upon logging in rather than waiting for them to access the course. This is helpful for courses which should be visible on a users "my courses" list by default.';
$string['newenrols'] = 'Allow new enrolments';
$string['newenrols_desc'] = 'Allow users to Autoenrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can enrol into this course.';
$string['nogroupon'] = 'Do not create groups';
$string['pluginname'] = 'Auto Enrol ';
$string['pluginname_desc'] = 'The automatic enrolment module allows an option for logged in users to be automatically granted entry to a course and enrolled. This is similar to allowing guest access but the students will be permanently enrolled and therefore able to participate in forum and activities within the area.';
$string['pluginnotenabled'] = 'Autoenrol plugin not enabled';
$string['privacy:metadata:core_group'] = 'Autoenrol plugin can create new groups or use existing groups to add participants that match the Autoenrol filter.';
$string['removegroups'] = 'Remove groups';
$string['removegroups_desc'] = 'When an enrolment instance is deleted, should it attempt to remove the groups it has created?';
$string['role'] = 'Default assigned role';
$string['role_help'] = 'Power users can use this setting to change the permission level at which users are enrolled.';
$string['selfunenrol'] = 'Enable self unenrol';
$string['selfunenrol_desc'] = 'Allow users to unenrol themself by default in new Autoenrol instances.';
$string['selfunenrol_help'] = 'When set to Yes the users can unenrol by themself.';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'When a user is auto enrolled in the course, they may be sent a welcome message email. If sent from the course contact (by default the teacher), and more than one user has this role, the email is sent from the first user to be assigned the role.';
$string['sendexpirynotificationstask'] = 'Autoenrol enrolment send expiry notifications task';
$string['softmatch'] = 'Soft Match';
$string['softmatch_help'] = 'When enabled AutoEnrol will enrol a user when they partially match the "Allow Only" value instead of requiring an exact match. Soft matches are also case-insensitive. The value of "Filter By" will be used for the group name.';
$string['status'] = 'Allow existing enrolments';
$string['status_desc'] = 'Enable Autoenrol method in new courses.';
$string['status_help'] = 'If enabled together with \'Allow new enrolments\' disabled, only users who Autoenrolled previously can access the course. If disabled, this Autoenrolment method is effectively disabled, since all existing Autoenrolments are suspended and new users cannot Autoenrol.';
$string['syncenrolmentstask'] = 'Synchronise Autoenrol task';
$string['syncexpirationstask'] = 'Autoenrol expirations check task';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"? You can revisit the course to be reenrolled but information such as grades and assignment submissions may be lost.';
$string['unenrolusers'] = 'Unenrol users';
$string['userfilter'] = 'User Filter';
$string['userfilter_help'] = 'When is set Autoenrol will enrol users only when they match the rules.';
$string['warning'] = 'Caution!';
$string['warning_message'] = 'Adding this plugin to your course will allow any registered Moodle users access to your course. Only install this plugin if you want to allow open access to your course for users who have logged in.';
$string['welcomemessage'] = 'Welcome message';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page so that we can learn more about you:

  {$a->profileurl}';



