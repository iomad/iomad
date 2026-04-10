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

/**
 * Local IOMAD user creation test
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

        // Create user.
        $userid = $generator->create_iomad_user();

        // Assert that the user record exists.
        $this->assertTrue($DB->record_exists('local_iomad_company_users', ['userid' => $userid]));
    }

    /*
    * Test to edit user
    */
    public function test_edit_user(): void {
        global $DB;

        $this->resetAfterTest();
        $this->markTestIncomplete();

        // ...
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
}
