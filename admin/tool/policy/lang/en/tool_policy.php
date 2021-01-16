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
 * Plugin strings are defined here.
 *
 * @package     tool_policy
 * @category    string
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['acceptanceacknowledgement'] = 'I acknowledge that I have received a request to give consent on behalf of user(s).';
$string['acceptancecount'] = '{$a->agreedcount} of {$a->policiescount}';
$string['acceptancenote'] = 'Remarks';
$string['acceptancepolicies'] = 'Policies';
$string['acceptancessavedsucessfully'] = 'The agreements have been saved successfully.';
$string['acceptancestatusoverall'] = 'Overall';
$string['acceptanceusers'] = 'Users';
$string['actions'] = 'Actions';
$string['activate'] = 'Set status to "Active"';
$string['activating'] = 'Activating a policy';
$string['activateconfirm'] = '<p>You are about to activate policy <em>\'{$a->name}\'</em> and make the version <em>\'{$a->revision}\'</em> the current one.</p><p>All users will be required to agree to this new policy version to be able to use the site.</p>';
$string['activateconfirmyes'] = 'Activate';
$string['agreed'] = 'Agreed';
$string['agreedby'] = 'Agreed by';
$string['agreedno'] = 'Consent not given';
$string['agreednowithlink'] = 'Consent not given; click to give consent on behalf of user for {$a}';
$string['agreednowithlinkall'] = 'Consent not given; click to give consent on behalf of user for all policies';
$string['agreedon'] = 'Agreed on';
$string['agreedyes'] = 'Agreed';
$string['agreedyesonbehalf'] = 'Consent given on behalf of user';
$string['agreedyesonbehalfwithlink'] = 'Consent given on behalf of user; click to withdraw user consent for {$a}';
$string['agreedyesonbehalfwithlinkall'] = 'Consent given on behalf of user; click to withdraw user consent for all policies';
$string['agreedyeswithlink'] = 'Consent given; click to withdraw user consent for {$a}';
$string['agreedyeswithlinkall'] = 'Consent given; click to withdraw user consent for all policies';
$string['agreepolicies'] = 'Please agree to the following policies';
$string['backtotop'] = 'Back to top';
$string['consentbulk'] = 'Consent';
$string['consentdetails'] = 'Give consent on behalf of user';
$string['consentpagetitle'] = 'Consent';
$string['contactdpo'] = 'For questions regarding the policies please contact the Data Protection Officer.';
$string['dataproc'] = 'Personal data processing';
$string['deleting'] = 'Deleting a version';
$string['deleteconfirm'] = '<p>Are you sure you want to delete policy <em>\'{$a->name}\'</em>?</p><p>This operation can not be undone.</p>';
$string['editingpolicydocument'] = 'Editing policy';
$string['errorpolicyversionnotfound'] = 'There isn\'t any policy version with this identifier.';
$string['errorsaveasdraft'] = 'Minor change can not be saved as draft';
$string['errorusercantviewpolicyversion'] = 'The user doesn\'t have access to this policy version.';
$string['event_acceptance_created'] = 'User policy agreement created';
$string['event_acceptance_updated'] = 'User policy agreement updated';
$string['filtercapabilityno'] = 'Permission: Can not agree';
$string['filtercapabilityyes'] = 'Permission: Can agree';
$string['filterrevision'] = 'Version: {$a}';
$string['filterrevisionstatus'] = 'Version: {$a->name} ({$a->status})';
$string['filterrole'] = 'Role: {$a}';
$string['filters'] = 'Filters';
$string['filterstatusno'] = 'Status: Not agreed';
$string['filterstatusyes'] = 'Status: Agreed';
$string['filterplaceholder'] = 'Search keyword or select filter';
$string['filterpolicy'] = 'Policy: {$a}';
$string['guestconsent:continue'] = 'Continue';
$string['guestconsentmessage'] = 'If you continue browsing this website, you agree to our policies:';
$string['iagree'] = 'I agree to the {$a}';
$string['iagreetothepolicy'] = 'Give consent on behalf of user';
$string['inactivate'] = 'Set status to "Inactive"';
$string['inactivating'] = 'Inactivating a policy';
$string['inactivatingconfirm'] = '<p>You are about to inactivate policy <em>\'{$a->name}\'</em> version <em>\'{$a->revision}\'</em>.</p>';
$string['inactivatingconfirmyes'] = 'Inactivate';
$string['invalidversionid'] = 'There is no policy with this identifier!';
$string['irevokethepolicy'] = 'Withdraw user consent';
$string['minorchange'] = 'Minor change';
$string['minorchangeinfo'] = 'A minor change does not alter the meaning of the policy. Users are not required to agree to the policy again if the edit is marked as a minor change.';
$string['managepolicies'] = 'Manage policies';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['mustagreetocontinue'] = 'Before continuing you must agree to all these policies.';
$string['newpolicy'] = 'New policy';
$string['newversion'] = 'New version';
$string['nofiltersapplied'] = 'No filters applied';
$string['nopermissiontoagreedocs'] = 'No permission to agree to the policies';
$string['nopermissiontoagreedocs_desc'] = 'Sorry, you do not have the required permissions to agree to the policies.<br />You will not be able to use this site until the following policies are agreed:';
$string['nopermissiontoagreedocsbehalf'] = 'No permission to agree to the policies on behalf of this user';
$string['nopermissiontoagreedocsbehalf_desc'] = 'Sorry, you do not have the required permission to agree to the following policies on behalf of {$a}:';
$string['nopermissiontoagreedocscontact'] = 'For further assistance, please contact';
$string['nopermissiontoviewpolicyversion'] = 'You do not have permissions to view this policy version.';
$string['nopolicies'] = 'There are no policies for registered users with an active version.';
$string['selectpolicyandversion'] = 'Use the filter above to select policy and/or version';
$string['steppolicies'] = 'Policy {$a->numpolicy} out of {$a->totalpolicies}';
$string['pluginname'] = 'Policies';
$string['policiesagreements'] = 'Policies and agreements';
$string['policy:accept'] = 'Agree to the policies';
$string['policy:acceptbehalf'] = 'Give consent for policies on someone else\'s behalf';
$string['policy:managedocs'] = 'Manage policies';
$string['policy:viewacceptances'] = 'View user agreement reports';
$string['policydocaudience'] = 'User consent';
$string['policydocaudience0'] = 'All users';
$string['policydocaudience1'] = 'Authenticated users';
$string['policydocaudience2'] = 'Guests';
$string['policydoccontent'] = 'Full policy';
$string['policydochdrpolicy'] = 'Policy';
$string['policydochdrversion'] = 'Document version';
$string['policydocname'] = 'Name';
$string['policydocrevision'] = 'Version';
$string['policydocsummary'] = 'Summary';
$string['policydocsummary_help'] = 'This text should provide a summary of the policy, potentially in a simplified and easily accessible form, using clear and plain language.';
$string['policydoctype'] = 'Type';
$string['policydoctype0'] = 'Site policy';
$string['policydoctype1'] = 'Privacy policy';
$string['policydoctype2'] = 'Third parties policy';
$string['policydoctype99'] = 'Other policy';
$string['policydocuments'] = 'Policy documents';
$string['policynamedversion'] = 'Policy {$a->name} (version {$a->revision} - {$a->id})';
$string['policyversionacceptedinbehalf'] = 'Consent for this policy has been given on your behalf.';
$string['policyversionacceptedinotherlang'] = 'Consent for this policy version has been given in a different language.';
$string['previousversions'] = '{$a} previous versions';
$string['privacy:metadata:acceptances'] = 'Information about policy agreements made by users';
$string['privacy:metadata:acceptances:policyversionid'] = 'The version of the policy which was accepted.';
$string['privacy:metadata:acceptances:userid'] = 'The user who this policy acceptances relates to.';
$string['privacy:metadata:acceptances:status'] = 'The status of the agreement.';
$string['privacy:metadata:acceptances:lang'] = 'The language used to display the policy when it was accepted.';
$string['privacy:metadata:acceptances:usermodified'] = 'The user who accepted the policy, if made on behalf of another user.';
$string['privacy:metadata:acceptances:timecreated'] = 'The time when the user agreed to the policy';
$string['privacy:metadata:acceptances:timemodified'] = 'The time when the user updated their agreement';
$string['privacy:metadata:acceptances:note'] = 'Any comments added by a user when giving consent on behalf of another user';
$string['privacy:metadata:subsystem:corefiles'] = 'The policy tool stores files includes in the summary and content of a policy.';
$string['privacy:metadata:versions'] = 'Information from versions of the policy documents';
$string['privacy:metadata:versions:name'] = 'The name of the policy.';
$string['privacy:metadata:versions:type'] = 'The type of policy document type.';
$string['privacy:metadata:versions:audience'] = 'The intended audience of the policy.';
$string['privacy:metadata:versions:archived'] = 'Whether the policy version is active or not.';
$string['privacy:metadata:versions:usermodified'] = 'The user who modified the policy.';
$string['privacy:metadata:versions:timecreated'] = 'The time that this version of the policy was created.';
$string['privacy:metadata:versions:timemodified'] = 'The time that this version of the policy was updated.';
$string['privacy:metadata:versions:policyid'] = 'The policy that this version is associated with.';
$string['privacy:metadata:versions:revision'] = 'The revision name of this version of the policy.';
$string['privacy:metadata:versions:summary'] = 'The summary of this version of the policy.';
$string['privacy:metadata:versions:summaryformat'] = 'The format of the summary field.';
$string['privacy:metadata:versions:content'] = 'The content of this version of the policy.';
$string['privacy:metadata:versions:contentformat'] = 'The format of the content field.';
$string['privacysettings'] = 'Privacy settings';
$string['readpolicy'] = 'Please read our {$a}';
$string['refertofullpolicytext'] = 'Please refer to the full {$a} if you would like to review the text.';
$string['revokeacknowledgement'] = 'I acknowledge that I have received a request to withdraw consent on behalf of user(s).';
$string['revokedetails'] = 'Withdraw user consent';
$string['save'] = 'Save';
$string['saveasdraft'] = 'Save as draft';
$string['selectuser'] = 'Select user {$a}';
$string['selectusersforconsent'] = 'Select users to give consent on behalf of';
$string['settodraft'] = 'Create a new draft';
$string['status'] = 'Policy status';
$string['statusinfo'] = 'A policy with \'Active\' status requires users to give their consent, either when they first log in, or in the case of existing users when they next log in.';
$string['status0'] = 'Draft';
$string['status1'] = 'Active';
$string['status2'] = 'Inactive';
$string['useracceptancecount'] = '{$a->agreedcount} of {$a->userscount} ({$a->percent}%)';
$string['useracceptancecountna'] = 'N/A';
$string['useracceptances'] = 'User agreements';
$string['userpolicysettings'] = 'Policies';
$string['usersaccepted'] = 'Agreements';
$string['viewarchived'] = 'View previous versions';
$string['viewconsentpageforuser'] = 'Viewing this page on behalf of {$a}';
