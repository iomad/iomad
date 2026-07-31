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
use coding_exception;
use dml_missing_record_exception;
use local_iomadcustompage\local\models\page;
use stdClass;

/**
 * Unit tests for manager class.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_iomadcustompage\manager
 * @group      local_iomadcustompage
 */
final class manager_test extends advanced_testcase {
    /**
     * Test get_page_from_id with valid page ID.
     */
    public function test_get_page_from_id_valid(): void {
        $this->resetAfterTest();

        // Create a test page.
        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = manager::create_page_persistent($pagedata);

        // Test retrieving the page.
        $retrievedpage = manager::get_page_from_id($page->get('id'));

        $this->assertInstanceOf(page::class, $retrievedpage);
        $this->assertEquals($page->get('id'), $retrievedpage->get('id'));
        $this->assertEquals('Test Page', $retrievedpage->get('name'));
    }

    /**
     * Test get_page_from_id with invalid page ID.
     */
    public function test_get_page_from_id_invalid(): void {
        $this->resetAfterTest();

        // Test with negative ID.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid page ID provided: -1');
        manager::get_page_from_id(-1);
    }

    /**
     * Test get_page_from_id with zero ID.
     */
    public function test_get_page_from_id_zero(): void {
        $this->resetAfterTest();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid page ID provided: 0');
        manager::get_page_from_id(0);
    }

    /**
     * Test get_page_from_id with non-existent page ID.
     */
    public function test_get_page_from_id_nonexistent(): void {
        $this->resetAfterTest();

        $this->expectException(dml_missing_record_exception::class);
        manager::get_page_from_id(99999);
    }

    /**
     * Test create_page_persistent with valid data.
     */
    public function test_create_page_persistent_valid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $pagedata->title = 'Test Page Title';

        $page = manager::create_page_persistent($pagedata);

        $this->assertInstanceOf(page::class, $page);
        $this->assertEquals('Test Page', $page->get('name'));
        $this->assertEquals('Test Page Title', $page->get('title'));
        $this->assertTrue($page->get('id') > 0);
    }

    /**
     * Test create_page_persistent with missing name.
     */
    public function test_create_page_persistent_missing_name(): void {
        $this->resetAfterTest();

        $pagedata = new stdClass();
        $pagedata->title = 'Test Page Title';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Required field 'name' is missing or empty");
        manager::create_page_persistent($pagedata);
    }

    /**
     * Test create_page_persistent with empty name.
     */
    public function test_create_page_persistent_empty_name(): void {
        $this->resetAfterTest();

        $pagedata = new stdClass();
        $pagedata->name = '';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Required field 'name' is missing or empty");
        manager::create_page_persistent($pagedata);
    }

    /**
     * Test create_page_persistent with name too long.
     * Note: The manager doesn't validate name length - it's handled by the persistent.
     * Long names are truncated/handled by the database or form validation.
     */
    public function test_create_page_persistent_long_name(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = str_repeat('a', 200); // Long but valid name.

        $page = manager::create_page_persistent($pagedata);

        $this->assertInstanceOf(page::class, $page);
        $this->assertTrue($page->get('id') > 0);
    }

    /**
     * Test create_page_persistent with invalid parent.
     * Note: Throws invalid_persistent_exception since validation happens in persistent.
     */
    public function test_create_page_persistent_invalid_parent(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // First create a non-container page to use as an invalid parent.
        $noncontainerdata = new stdClass();
        $noncontainerdata->name = 'Non-Container Page';
        $noncontainerdata->iscontainer = 0;
        $noncontainer = manager::create_page_persistent($noncontainerdata);

        // Now try to create a child under the non-container page.
        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $pagedata->parent = $noncontainer->get('id');

        $this->expectException(\core\invalid_persistent_exception::class);
        manager::create_page_persistent($pagedata);
    }

    /**
     * Test clean_multilang_text with null input.
     */
    public function test_clean_multilang_text_null(): void {
        $result = manager::clean_multilang_text(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test clean_multilang_text with empty string.
     */
    public function test_clean_multilang_text_empty(): void {
        $result = manager::clean_multilang_text('');
        $this->assertEquals('', $result);
    }

    /**
     * Test clean_multilang_text with normal text.
     */
    public function test_clean_multilang_text_normal(): void {
        $text = 'This is normal text';
        $result = manager::clean_multilang_text($text);
        $this->assertEquals('This is normal text', $result);
    }

    /**
     * Test clean_multilang_text with empty paragraphs.
     */
    public function test_clean_multilang_text_empty_paragraphs(): void {
        $text = '<p></p><p>Valid content</p><p>&nbsp;</p>';
        $result = manager::clean_multilang_text($text);
        $this->assertEquals('<p>Valid content</p>', $result);
    }

    /**
     * Test clean_multilang_text with trailing whitespace.
     */
    public function test_clean_multilang_text_trailing_whitespace(): void {
        $text = 'Valid content&nbsp;&nbsp;<br/>';
        $result = manager::clean_multilang_text($text);
        $this->assertEquals('Valid content', $result);
    }

    /**
     * Test page breadcrumb with single page.
     * Note: Using page->get_breadcrumb() instead of non-existent manager::get_page_hierarchy().
     */
    public function test_get_page_breadcrumb_single(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Root Page';
        $page = manager::create_page_persistent($pagedata);

        $breadcrumb = $page->get_breadcrumb();

        $this->assertCount(1, $breadcrumb);
        $this->assertEquals($page->get('id'), $breadcrumb[0]->get('id'));
    }

    /**
     * Test page breadcrumb with parent-child relationship.
     * Note: Only container pages can have children.
     */
    public function test_get_page_breadcrumb_parent_child(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create parent page as a container (required for children).
        $parentdata = new stdClass();
        $parentdata->name = 'Parent Page';
        $parentdata->iscontainer = 1;
        $parent = manager::create_page_persistent($parentdata);

        // Create child page.
        $childdata = new stdClass();
        $childdata->name = 'Child Page';
        $childdata->parent = $parent->get('id');
        $child = manager::create_page_persistent($childdata);

        $breadcrumb = $child->get_breadcrumb();

        $this->assertCount(2, $breadcrumb);
        $this->assertEquals($parent->get('id'), $breadcrumb[0]->get('id'));
        $this->assertEquals($child->get('id'), $breadcrumb[1]->get('id'));
    }

    /**
     * Test can_user_access_page with admin user.
     */
    public function test_can_user_access_page_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = manager::create_page_persistent($pagedata);

        $canaccess = manager::can_user_access_page($page);
        $this->assertTrue($canaccess);
    }

    /**
     * Test setup_page_breadcrumb with empty array.
     */
    public function test_setup_page_breadcrumb_empty(): void {
        global $PAGE;
        $this->resetAfterTest();

        $PAGE->set_url('/test.php');

        // Should not throw any exceptions.
        manager::setup_page_breadcrumb([]);
        $this->assertTrue(true); // Test passes if no exception thrown.
    }

    /**
     * Test setup_page_breadcrumb with invalid item.
     */
    public function test_setup_page_breadcrumb_invalid_item(): void {
        global $PAGE;
        $this->resetAfterTest();

        $PAGE->set_url('/test.php');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid breadcrumb item: must be a page object');
        manager::setup_page_breadcrumb(['invalid']);
    }

    /**
     * Test setup_page_breadcrumb with too many items.
     * Note: This triggers a debugging message, which we need to allow.
     */
    public function test_setup_page_breadcrumb_too_many_items(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $PAGE->set_url('/test.php');

        // Create 15 pages to exceed the limit.
        $pages = [];
        for ($i = 0; $i < 15; $i++) {
            $pagedata = new stdClass();
            $pagedata->name = "Page $i";
            $pages[] = manager::create_page_persistent($pagedata);
        }

        // Call the method that triggers debugging.
        manager::setup_page_breadcrumb($pages);

        // Expect debugging message about exceeding max depth.
        $this->assertDebuggingCalled('Breadcrumb depth exceeds maximum allowed', DEBUG_DEVELOPER);
    }
}
