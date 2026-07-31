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

declare(strict_types=1);

namespace local_iomadcustompage;

use advanced_testcase;
use context_system;
use local_iomadcustompage\local\models\page;
use stdClass;

/**
 * Unit tests for permission class.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_iomadcustompage\permission
 * @group      local_iomadcustompage
 */
final class permission_test extends advanced_testcase {
    /**
     * Test can_view_pages_list with admin user.
     */
    public function test_can_view_pages_list_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $canview = permission::can_view_pages_list();
        $this->assertTrue($canview);
    }

    /**
     * Test can_view_pages_list with guest user.
     */
    public function test_can_view_pages_list_guest(): void {
        $this->resetAfterTest();
        $this->setGuestUser();

        $canview = permission::can_view_pages_list();
        $this->assertFalse($canview);
    }

    /**
     * Test require_can_view_pages_list with admin user.
     */
    public function test_require_can_view_pages_list_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Should not throw exception.
        permission::require_can_view_pages_list();
        $this->assertTrue(true);
    }

    /**
     * Test require_can_view_pages_list with guest user.
     */
    public function test_require_can_view_pages_list_guest(): void {
        $this->resetAfterTest();
        $this->setGuestUser();

        $this->expectException(page_access_exception::class);
        permission::require_can_view_pages_list();
    }

    /**
     * Test can_view_page with admin user.
     */
    public function test_can_view_page_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = manager::create_page_persistent($pagedata);

        $canview = permission::can_view_page($page);
        $this->assertTrue($canview);
    }

    /**
     * Test require_can_view_page with admin user.
     */
    public function test_require_can_view_page_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = manager::create_page_persistent($pagedata);

        // Should not throw exception.
        permission::require_can_view_page($page);
        $this->assertTrue(true);
    }

    /**
     * Test can_create_page with admin user.
     */
    public function test_can_create_page_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $cancreate = permission::can_create_page();
        $this->assertTrue($cancreate);
    }

    /**
     * Test can_create_page with guest user.
     */
    public function test_can_create_page_guest(): void {
        $this->resetAfterTest();
        $this->setGuestUser();

        $cancreate = permission::can_create_page();
        $this->assertFalse($cancreate);
    }

    /**
     * Test can_edit_page with admin user.
     */
    public function test_can_edit_page_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = manager::create_page_persistent($pagedata);

        $canedit = permission::can_edit_page($page);
        $this->assertTrue($canedit);
    }

    /**
     * Test permissions with specific context.
     */
    public function test_permissions_with_context(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $context = context_system::instance();

        $canview = permission::can_view_pages_list(null, $context);
        $this->assertTrue($canview);
    }

    /**
     * Test permissions with specific user ID.
     * Note: User needs local/iomadcustompage:view capability to view pages list.
     */
    public function test_permissions_with_userid(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();

        $context = context_system::instance();

        // Assign the specific capability to the user.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/iomadcustompage:view', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $user->id, $context);

        // Pass user ID as int.
        $canview = permission::can_view_pages_list((int) $user->id);
        $this->assertTrue($canview);
    }
}
