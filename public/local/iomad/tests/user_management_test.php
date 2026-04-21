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
use local_iomad\{company, company_user};

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
    */
    public function test_edit_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_iomad');

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();
        $userrecord = $DB->get_record('local_iomad_company_users', ['userid' => $userid]);

        // Edit IOMAD user.
        $newrecord = (object) [];
        $newrecord->id = $userid;
        $newrecord->firstname = 'NewName';
        $userid = company_user::create($newrecord, $userrecord->companyid);  // this isn't how you update user info, is it?

        // Assert that IOMAD user fullname has changed
        $this->assertTrue($newrecord->firstname == $DB->get_record('user', ['id' => $userid])->firstname);
    }

    /*
    * TODO: Test to delete user
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
    * TODO: Test to create users and assign roles
    */
    public function test_assign_user_company_roles(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // ...
    }

    /*
    * TODO: Test to add users to department and assign roles
    */
    public function test_assign_user_department_rolls(): void {

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // Create IOMAD user.
        $userid = $generator->create_iomad_user();

        // ...
    }
}
