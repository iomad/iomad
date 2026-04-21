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
    * TODO: Test to create course
    */
    public function test_create_course(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Create IOMAD course.
        $data = (object) [];
        company::create_course($data, $company);
    }

    /*
    * TODO: Test to edit course
    */
    public function test_edit_course(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create IOMAD course.
        // ...
    }

    /*
    * TODO: Test to assign course to company
    */
    public function test_assign_course_to_company(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create non-IOMAD course.
        $company->add_course();
    }

    /*
    * TODO: Test to unassign course from company
    */
    public function test_unassign_course_from_company(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create IOMAD course.
        company::remove_course();
    }

    /*
    * TODO: Test to delete course
    */
    public function test_delete_course(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create IOMAD course.
        company::delete_course();
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

        // Create IOMAD course.
        //$courseid = $generator->create_course();

        // Create IOMAD user.
        $userid = $generator->create_iomad_user(companyid: $department['companyid']);

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

        // Create IOMAD course.
        // ...
    }
}