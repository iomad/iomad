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
 * Local IOMAD company tests
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class company_management_test extends advanced_testcase {

    /*
    * Test creating companies
    */
    public function test_create_company(): void {
        global $DB;

        $this->resetAfterTest();

        // Get generator (from .../local/iomad/tests/generator/lib.php).
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();

        // Check that company exists.
        $this->assertTrue($DB->record_exists('local_iomad_companies', ['id' => $company->get('id')]));
    }

    /*
    * Test create company with invalid shortname
    */
    public function test_create_company_shortname_validation(): void {

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // We expect this test to throw an error.
        $this->expectExceptionMessageMatches('(Company \'shortname\' can only contain alphanumeric characters \(both uppercase and lowercase\) and underscores \(_\).)');

        // Create company.
        $companyrecord = (object) [];
        $companyrecord->shortname = 'test_company!';
        $generator->create_company($companyrecord);
    }

    /*
    * Test create company with missing shortname & country
    */
    public function test_create_company_missing_required(): void {

        $this->resetAfterTest();

        // We expect this test to throw an error.
        $this->expectExceptionMessageMatches("(error/Missing the following parameters for Company creation: shortname, and country.+)");

        // Create company.
        $companyrecord = (object) [];
        $companyrecord->name = 'Company';
        $companyrecord->city = 'Testland';
        company::create_company($companyrecord);
    }

    /*
    * Test to edit company
    */
    public function test_edit_company(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $oldrecord = $generator->create_company();
        $name = $DB->get_record('local_iomad_companies', ['id' => $oldrecord->id])->name;

        // Edit company.
        $updatedrecord = (object) [];
        $updatedrecord->id = $oldrecord->id;
        $updatedrecord->name = 'Testing Company';
        $updatedrecord->shortname = $oldrecord->get('shortname');
        $updatedrecord->city = $oldrecord->get('city');
        $updatedrecord->country = $oldrecord->get('country');
        company::create_company($updatedrecord);
        $rename = $DB->get_record('local_iomad_companies', ['id' => $updatedrecord->id])->name;

        // Assert that the company has been successfully renamed.
        $this->assertTrue($name != $rename);
    }

    /*
    * Test to delete company
    */
    public function test_delete_company(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Only users with correct permissions can delete companies (or else get 'Error: Call to a member function has_courses() on null' for line 561 of company.php)
        $this->setAdminUser();

        // Create company.
        $company = $generator->create_company();

        // Assert that the company exists.
        $this->assertTrue($DB->record_exists('local_iomad_companies', ['id' => $company->id]));

        // Delete the company.
        company::delete_company($company->id);

        // Assert that the company doesn't exist.
        $this->assertFalse($DB->record_exists('local_iomad_companies', ['id' => $company->id]));
    }

    /*
    * Test to create departments
    */
    public function test_create_department(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create department.
        $departmentid = $generator->create_department();

        // Assert that the department exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_departments', ['id' => $departmentid]));
    }

    /*
    * Test to edit departments
    */
    public function test_edit_department(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create department.
        $departmentid = $generator->create_department();

        // Assert that the department exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_departments', ['id' => $departmentid]));

        // Change name
        $record = (object) [];
        $record->id = $departmentid;
        $record->name = 'New Name';
        $generator->create_department($record);
        $this->assertTrue($record->name == $DB->get_record('local_iomad_company_departments', ['id' => $departmentid])->name);
    }

    /*
    * Test to delete departments
    */
    public function test_delete_department(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create department.
        $departmentid = $generator->create_department();

        // Assert that the department exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_departments', ['id' => $departmentid]));

        // Delete the department.
        company::delete_department_recursive($departmentid);

        // Assert that the department doesn't exist.
        $this->assertFalse($DB->record_exists('local_iomad_company_departments', ['id' => $departmentid]));
    }

    /*
    * Test to assign users to a company
    */
    public function test_assign_users_to_company(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create user.
        $userid = $generator->create_iomad_user();

        // Assert that the user record exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_users', ['userid' => $userid]));

        // Create companies.
        $newcompany = $generator->create_company();

        // Assert that user is not part of company B.
        $this->assertFalse($DB->record_exists('local_iomad_company_users', ['userid' => $userid, 'companyid' => $newcompany->id]));

        // Assign user to Company B.
        $newcompany->assign_user_to_company($userid);

        // Assert that user is part of company B.
        $this->assertTrue($DB->record_exists('local_iomad_company_users', ['userid' => $userid, 'companyid' => $newcompany->id]));
    }

    /*
    * Test to assign users to a department
    */
    public function test_assign_users_to_department(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $newcompany = $generator->create_company();

        // Create department.
        $record = (object) [];
        $record->companyid = $newcompany->id;
        $departmentid = $generator->create_department($record);

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // Assign user to company.
        $newcompany->assign_user_to_company($userid);

        // Assign IOMAD user to department.
        company::assign_user_to_department($departmentid, $userid);

        // Assert that user is in department.
        $this->assertTrue($DB->record_exists('local_iomad_company_users',
                                                ['userid' => $userid,
                                                 'companyid' => $newcompany->id,
                                                 'departmentid' => $departmentid]));
    }
}
