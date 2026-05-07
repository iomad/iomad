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
 * Local IOMAD licence tests
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class licence_management_test extends advanced_testcase {

    /*
    * TODO: Test to create licence
    */
    public function test_create_licence(): void {
        global $DB;

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create licence.
        $licenserecord = (object) [];
        $licenserecord->companyid = $companyid;
        $licenseid = $generator->create_license($licenserecord);

        // Assert that licence exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid]));
    }

    /*
    * Test to edit licence
    */
    public function test_edit_licence(): void {
        global $DB;

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create licence.
        $licenserecord = (object) [];
        $licenserecord->companyid = $companyid;
        $licenserecord->name = 'original_license_name';
        $licenseid = $generator->create_license($licenserecord);

        // Assert that licence exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid, 'name' => $licenserecord->name]));

        // Edit licence.
        $licenserecord->licenseid = $licenseid;
        $licenserecord->name = 'license_name_changed';
        $licenseid = company::create_license($licenserecord);

        // Assert that licence exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid, 'name' => $licenserecord->name]));
    }

    /*
    * Test to delete licence
    */
    public function test_delete_licence(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create licence.
        $licenserecord = (object) [];
        $licenserecord->companyid = $companyid;
        $licenseid = $generator->create_license($licenserecord);

        // Assert that licence has been created.
        $this->assertTrue($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid]));

        // Delete licence.
        \block_iomad_company_admin\external\delete_license::execute($companyid, $licenseid);

        // Assert that licence has been deleted.
        $this->assertFalse($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid]));
    }

    /*
    * TODO: Test to create users and allocate licences
    */
    public function test_allocate_licence_to_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();
        $companyid = $company->id;

        // Create course.
        $courserecord = (object) [];
        $courserecord->companyid = $companyid;
        $course = $generator->create_course($courserecord);
        $courseid = $course->id;

        // Create licence.
        $licenserecord = (object) [];
        $licenserecord->companyid = $companyid;
        $licenserecord->licensecourses = array($courseid);
        $licenseid = $generator->create_license($licenserecord);

        // Create user.
        $userid = $generator->create_iomad_user(companyid: $companyid);

        // Allocate licence.
        company::allocate_license($licenseid, $userid);

        // Assert that licence has been allocated.
        //$this->assertTrue($DB->record_exists('local_iomad_company_licenses', ['id' => $licenseid, 'used' => 1]));
        $this->assertTrue($DB->record_exists('local_iomad_company_license_courses', ['licenseid' => $licenseid, 'courseid' => $courseid]));
        $this->assertTrue($DB->record_exists('local_iomad_company_license_users', [
                                'userid' => $userid,
                                'courseid' => $courseid,
                                'licenseid' => $licenseid
                            ]));
    }
}
