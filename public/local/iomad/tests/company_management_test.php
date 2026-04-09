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

namespace local_iomad\company;

use advanced_testcase;
use local_iomad\company;

/**
 * Local IOMAD company creation test
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

        // Get generator (From .../local/iomad/tests/generator/lib.php)
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

        $this->assertTrue($name != $rename);
    }

    /*
    * Test to create departments
    */
    public function test_create_department(): void {

        $this->resetAfterTest();

        // ...
        $this->markTestIncomplete();
    }

    /*
    * Test to edit departments
    */
    public function test_edit_department(): void {

        $this->resetAfterTest();

        // ...
        $this->markTestIncomplete();
    }

    /*
    * Test to create and delete departments
    */
    public function test_delete_department(): void {

        $this->resetAfterTest();

        // ...
        $this->markTestIncomplete();
    }

    /*
    * Test to create a department and add users
    */
    public function test_assign_users_to_department(): void {

        $this->resetAfterTest();

        // ...
        $this->markTestIncomplete();
    }
}
