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

namespace local_iomad\tests;

use advanced_testcase;
use local_iomad\company;

/**
 * Local IOMAD course tests
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_management_test extends advanced_testcase {

    /*
    * Test to create course
    */
    public function test_create_course(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $company->id;
        $course = $generator->create_course($coursedata);

        // Assert that course exists and is in company.
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));
        $this->assertTrue($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));
    }

    /*
    * TODO: Test to edit course
    */
    public function test_edit_course(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $company->id;
        $course = $generator->create_course($coursedata);

        // Edit course.

        // Assert that course has been edited.
    }

    /*
    * Test to assign course to company
    */
    public function test_assign_course_to_company(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create non-IOMAD course.
        $course = $this->getDataGenerator()->create_course();

        // Assign non-IOMAD course to company.
        $company->add_course($course);

        // Assert that course is in company.
        $this->assertTrue($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));
    }

    /*
    * Test to unassign course from company
    */
    public function test_unassign_course_from_company(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $company->id;
        $course = $generator->create_course($coursedata);

        // Assert that course is in company.
        $this->assertTrue($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));

        // Remove IOMAD course from company.
        company::remove_course($course, $company->id);

        // Assert that course is not in company.
        $this->assertFalse($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));
    }

    /*
    * Test to delete course
    */
    public function test_delete_course(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $company->id;
        $course = $generator->create_course($coursedata);

        // Assert that course exists.
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));
        $this->assertTrue($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));

        // Delete IOMAD course.
        company::delete_course($company->id, $course->id, showfeedback: false);

        // Assert that course does not exist.
        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
        $this->assertFalse($DB->record_exists('local_iomad_company_courses', ['courseid' => $course->id, 'companyid' => $company->id]));
    }

    /*
    * TODO: Test to enrol/unenrol users onto a course
    */
    public function test_enrol_users_onto_course(): void {
        global $DB;

        $this->resetAfterTest();
        $this->markTestIncomplete();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $departmentid = $generator->create_department();
        $department = $DB->record_exists('local_iomad_company_departments', ['id' => $departmentid]);
        $companyid = $department->companyid;

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $companyid;
        $course = $generator->create_course($coursedata);

        // Create IOMAD user.
        $userid = $generator->create_iomad_user(companyid: $companyid);

        // Enrol IOMAD user onto course.
        //company::???;

        // Assert that user is enrolled on course
    }

    /*
    * TODO: Test to create teaching location
    */
    public function test_create_teaching_location(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create IOMAD course.
        $coursedata = (object) [];
        $coursedata->companyid = $company->id;
        $course = $generator->create_course($coursedata);

        // ...
    }
}