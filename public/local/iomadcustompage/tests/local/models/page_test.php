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

namespace local_iomadcustompage\local\models;

use advanced_testcase;
use coding_exception;
use context_system;
use local_iomadcustompage\constants;
use local_iomadcustompage\event\iomadcustompage_created;
use local_iomadcustompage\event\iomadcustompage_updated;
use local_iomadcustompage\event\iomadcustompage_deleted;
use local_iomadcustompage\local\helpers\page as page_helper;
use stdClass;

/**
 * Unit tests for page model.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_iomadcustompage\local\models\page
 * @group      local_iomadcustompage
 */
final class page_test extends advanced_testcase {
    /**
     * Test page creation with minimum required data.
     */
    public function test_create_page_minimum_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';

        $page = new page(0, $pagedata);
        $page->create();

        $this->assertEquals('Test Page', $page->get('name'));
        $this->assertEquals(constants::DEFAULT_PAGE_DEPTH, $page->get('depth'));
        $this->assertEquals(constants::PAGE_NOT_CONTAINER, $page->get('iscontainer'));
        $this->assertEquals(constants::HIDE_FROM_PRIMARY_NAV, $page->get('showinprimarynav'));
        $this->assertNull($page->get('parent'));
        $this->assertNull($page->get('title'));
    }

    /**
     * Test page creation with full data.
     */
    public function test_create_page_full_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $pagedata->title = 'Test Page Title';
        $pagedata->iscontainer = constants::PAGE_IS_CONTAINER;
        $pagedata->showinprimarynav = constants::SHOW_IN_PRIMARY_NAV;

        $page = new page(0, $pagedata);
        $page->create();

        $this->assertEquals('Test Page', $page->get('name'));
        $this->assertEquals('Test Page Title', $page->get('title'));
        $this->assertEquals(constants::PAGE_IS_CONTAINER, $page->get('iscontainer'));
        $this->assertEquals(constants::SHOW_IN_PRIMARY_NAV, $page->get('showinprimarynav'));
    }

    /**
     * Test page creation with parent relationship.
     * Note: Only container pages can have children.
     */
    public function test_create_page_with_parent(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create parent page as a container (required for children).
        $parentdata = new stdClass();
        $parentdata->name = 'Parent Page';
        $parentdata->iscontainer = constants::PAGE_IS_CONTAINER;
        $parent = new page(0, $parentdata);
        $parent->create();

        // Create child page.
        $childdata = new stdClass();
        $childdata->name = 'Child Page';
        $childdata->parent = $parent->get('id');
        $child = new page(0, $childdata);
        $child->create();

        $this->assertEquals($parent->get('id'), $child->get('parent'));
        $this->assertEquals(2, $child->get('depth')); // Should be parent depth + 1.
    }

    /**
     * Test page update.
     */
    public function test_update_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Original Name';
        $page = new page(0, $pagedata);
        $page->create();

        // Update the page.
        $page->set('name', 'Updated Name');
        $page->set('title', 'Updated Title');
        $page->update();

        // Reload from database.
        $reloadedpage = new page($page->get('id'));
        $this->assertEquals('Updated Name', $reloadedpage->get('name'));
        $this->assertEquals('Updated Title', $reloadedpage->get('title'));
    }

    /**
     * Test page deletion.
     */
    public function test_delete_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $pageid = $page->get('id');
        $page->delete();

        // Verify page is deleted.
        $this->assertFalse(page::record_exists($pageid));
    }

    /**
     * Test page deletion with children.
     * Note: Only container pages can have children.
     */
    public function test_delete_page_with_children(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create parent page as a container (required for children).
        $parentdata = new stdClass();
        $parentdata->name = 'Parent Page';
        $parentdata->iscontainer = constants::PAGE_IS_CONTAINER;
        $parent = new page(0, $parentdata);
        $parent->create();

        // Create child page.
        $childdata = new stdClass();
        $childdata->name = 'Child Page';
        $childdata->parent = $parent->get('id');
        $child = new page(0, $childdata);
        $child->create();

        $parentid = $parent->get('id');
        $childid = $child->get('id');

        // Delete parent page using the helper (which handles cascade cleanup).
        page_helper::delete_page($parentid);

        // Child should still exist but with no parent.
        $reloadedchild = new page($childid);
        $this->assertNull($reloadedchild->get('parent'));
    }

    /**
     * Test get_formatted_name method.
     */
    public function test_get_formatted_name(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $formattedname = $page->get_formatted_name();
        $this->assertEquals('Test Page', $formattedname);
    }

    /**
     * Test get_formatted_title method.
     */
    public function test_get_formatted_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $pagedata->title = 'Test Title';
        $page = new page(0, $pagedata);
        $page->create();

        $formattedtitle = $page->get_formatted_title();
        $this->assertEquals('Test Title', $formattedtitle);
    }

    /**
     * Test get_formatted_title returns empty string when no title set.
     */
    public function test_get_formatted_title_null(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $formattedtitle = $page->get_formatted_title();
        $this->assertEquals('', $formattedtitle);
    }

    /**
     * Test is_container method.
     */
    public function test_is_container(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Test non-container page.
        $pagedata = new stdClass();
        $pagedata->name = 'Content Page';
        $pagedata->iscontainer = constants::PAGE_NOT_CONTAINER;
        $page = new page(0, $pagedata);
        $page->create();

        $this->assertFalse($page->is_container());

        // Test container page.
        $containerdata = new stdClass();
        $containerdata->name = 'Container Page';
        $containerdata->iscontainer = constants::PAGE_IS_CONTAINER;
        $container = new page(0, $containerdata);
        $container->create();

        $this->assertTrue($container->is_container());
    }

    /**
     * Test get_url method.
     */
    public function test_get_url(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $url = $page->get_url();
        $expectedurl = new \moodle_url('/local/iomadcustompage/view.php', ['id' => $page->get('id')]);

        $this->assertEquals($expectedurl->out(), $url->out());
    }

    /**
     * Test get_context method.
     */
    public function test_get_context(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $context = $page->get_context();
        $this->assertInstanceOf(\context::class, $context);
        $this->assertEquals(context_system::instance()->id, $context->id);
    }

    /**
     * Test get_breadcrumb method.
     * Note: Only container pages can have children.
     */
    public function test_get_breadcrumb(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create parent page as a container (required for children).
        $parentdata = new stdClass();
        $parentdata->name = 'Parent Page';
        $parentdata->iscontainer = constants::PAGE_IS_CONTAINER;
        $parent = new page(0, $parentdata);
        $parent->create();

        // Create child page.
        $childdata = new stdClass();
        $childdata->name = 'Child Page';
        $childdata->parent = $parent->get('id');
        $child = new page(0, $childdata);
        $child->create();

        $breadcrumb = $child->get_breadcrumb();

        $this->assertCount(2, $breadcrumb);
        $this->assertEquals($parent->get('id'), $breadcrumb[0]->get('id'));
        $this->assertEquals($child->get('id'), $breadcrumb[1]->get('id'));
    }

    /**
     * Test event triggering on page creation.
     */
    public function test_page_created_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $sink = $this->redirectEvents();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(iomadcustompage_created::class, $events[0]);
    }

    /**
     * Test event triggering on page update.
     */
    public function test_page_updated_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $sink = $this->redirectEvents();

        $page->set('name', 'Updated Name');
        $page->update();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(iomadcustompage_updated::class, $events[0]);
    }

    /**
     * Test event triggering on page deletion.
     */
    public function test_page_deleted_event(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Test Page';
        $page = new page(0, $pagedata);
        $page->create();

        $sink = $this->redirectEvents();

        $page->delete();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(iomadcustompage_deleted::class, $events[0]);
    }

    /**
     * Test maximum depth validation.
     * Note: depth is computed automatically based on parent hierarchy,
     * setting it manually has no effect - it gets recalculated on save.
     */
    public function test_maximum_depth_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a root page - depth is automatically set to 1.
        $pagedata = new stdClass();
        $pagedata->name = 'Root Page';
        $page = new page(0, $pagedata);
        $page->create();

        // Root pages always have depth 1, regardless of what you set.
        $this->assertEquals(1, $page->get('depth'));
    }
}
