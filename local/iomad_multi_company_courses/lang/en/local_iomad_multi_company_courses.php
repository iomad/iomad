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
  * Language strings for the IOMAD Multi-Company Courses plugin
  *
  * @package   local_iomad_multi_company_courses
  * @copyright 2025 Thomas Schlienger
  * @author    Thomas Schlienger
  * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

$string['pluginname'] = 'Multi-Company Course Assignment';
$string['assigncoursesmulti'] = 'Assign courses to multiple companies';
$string['multi_company_courses_for'] = 'Multi-company course assignment for {$a}';

$string['companysearch'] = 'Company search';
$string['companycode'] = 'Company code pattern';
$string['companycode_help'] = 'Enter a pattern to search for in company codes. For example, entering "Platin" will find all companies with "Platin" in their code field. The search is case-insensitive and matches partial strings.';
$string['searchcompanies'] = 'Search companies';
$string['matchingcompanies'] = 'Matching companies';
$string['foundcompanies'] = 'Found {$a} matching companies:';
$string['nocompaniesmatched'] = 'No companies found matching the specified pattern. Please try a different search term.';

$string['courseselection'] = 'Course selection';
$string['assigncoursestocompanies'] = 'Assign selected courses to all matching companies';
$string['coursesassignedsuccessfully'] = 'Successfully assigned {$a->courses} courses to {$a->companies} companies (total {$a->total} assignments).';
$string['nocourseselected'] = 'Please select at least one course to assign.';
$string['nocompaniesselected'] = 'No companies were selected for assignment.';
$string['assignmenterror'] = 'An error occurred while assigning courses. Please try again.';

$string['privacy:metadata'] = 'The IOMAD Multi-Company Courses plugin does not store any personal data.';

// Sorting functionality
$string['sortby'] = 'Sort by';
$string['sortbyname'] = 'Course name (A-Z)';
$string['sortbymodified'] = 'Last modified (newest first)';
$string['changesort'] = 'Change sort order';
$string['modified'] = 'Modified';
$string['limitedresults'] = 'showing {$a->shown} of {$a->total} courses';

// Help strings

// Help strings
$string['multicompanyassignment'] = 'Multi-company course assignment';
$string['multicompanyassignment_help'] = 'This feature allows you to assign courses to or unassign courses from multiple companies at once by searching for companies using patterns in their company codes. This is useful for bulk operations when you need to assign or unassign the same courses to multiple related companies.';

// Mode selection
$string['modeselection'] = 'Mode selection';
$string['currentmode'] = 'Current mode';
$string['switchto'] = 'Switch to';
$string['assignmode'] = 'Assign Courses';
$string['unassignmode'] = 'Unassign Courses';
$string['currentcourses'] = 'Currently Assigned Courses';

// Assigned courses
$string['assignedcourses'] = 'Assigned courses';
$string['assignedcoursesmatching'] = 'Assigned courses matching "{$a}"';
$string['unassigncoursestocompanies'] = 'Unassign selected courses from all matching companies';
$string['unassigncoursesfromcompanies'] = 'Unassign courses from companies';
$string['coursesunassignedsuccessfully'] = 'Successfully unassigned {$a->courses} courses from {$a->companies} companies (total {$a->unassignments} unassignments).';
$string['coursesskipped'] = '{$a->courses} course(s) were skipped from {$a->combinations} company-course assignment(s) because they have enrollments and the unenroll checkbox was not ticked: {$a->detailslist}';

// Unenrollment warnings and options
$string['sharedhasenrollments'] = 'shared with enrolments';
$string['oktounenroll'] = 'Ok to unenroll users';
$string['unenrollwarning'] = '<div class="alert alert-warning">Warning: Unassigning these courses with enabled "OK to unenroll users" will unenroll all users of the matching companies from the selected courses.</div>';
$string['unenrollincapable'] = '<div class="alert alert-danger">You do not have permission to unassign courses that have enrollments.</div>';

// Capabilities
$string['iomad_multi_company_courses:assign'] = 'Assign courses to multiple companies';
$string['iomad_multi_company_courses:unassign'] = 'Unassign courses from multiple companies with user unenrollment';
$string['iomad_multi_company_courses:view'] = 'View multi-company course assignment interface';