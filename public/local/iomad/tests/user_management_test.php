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
use local_iomad\company_user;
use local_iomad\custom_context\context_company;

/**
 * Local IOMAD user tests
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_management_test extends advanced_testcase {

    /*
    * Test to create user
    */
    public function test_create_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // Assert that the user record exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_users', ['userid' => $userid]));
        $this->assertTrue($DB->record_exists('user', ['id' => $userid]));
    }

    /*
    * TODO: Test to edit user
    * BAD TEST: Works, but the function it uses to edit users is only used in testing
    */
    public function test_edit_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Must be a user (or else get 'dml_missing_record_exception: Invalid user')
        $this->setAdminUser();

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();
        $this->assertTrue($DB->record_exists('user', ['id' => $userid, 'deleted' => 0]));

        // Edit IOMAD user.
        $newrecord = (object) [];
        $newrecord->id = $userid;
        $newrecord->firstname = 'NewName';
        $userid = company_user::edit($newrecord);

        // Assert that IOMAD user fullname has changed
        $this->assertTrue($newrecord->firstname == $DB->get_record('user', ['id' => $userid])->firstname);
    }

    /*
    * Test to delete user
    */
    public function test_delete_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // Assert that the user record exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_users', ['userid' => $userid]));
        $this->assertTrue($DB->record_exists('user', ['id' => $userid]));

        // Delete user.
        company_user::delete($userid);

        // Assert that the user record does not exist.
        $this->assertTrue((int) $DB->get_record('user', ['id' => $userid])->deleted == 0);
    }

    /*
    * Test to assign user roles
    */
    public function test_assign_user_company_roles(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create company.
        $company = $generator->create_company();
        $companycontext = context_company::instance($company->id);

        // Create IOMAD user.
        $userid = $generator->create_iomad_user(companyid: $company->id);

        // Assign IOMAD user role.
        $roleid = $DB->get_record('role', ['shortname' => 'companycourseeditor'])->id;
        role_assign($roleid, $userid, $companycontext);

        // Assert that IOMAD user has role.
        $this->assertTrue($DB->record_exists('role_assignments', ['roleid' => $roleid, 'contextid' => $companycontext->id, 'userid' => $userid]));
    }

    /*
    * TODO: Test to add users to department and assign roles
    */
    public function test_assign_user_department_rolls(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // Assign IOMAD user to department.


        // Assign IOMAD user role.


        // Assert that IOMAD user has role.
    }
}
