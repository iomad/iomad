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

/**
 * Unit tests for move up/down functionality
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage;

use local_iomadcustompage\external\page\update_sort_order;
use local_iomadcustompage\constants;
use local_iomadcustompage\local\iomadcustompage\page as page_helper;
use local_iomadcustompage\local\models\page;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for move up/down functionality
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class move_functionality_test extends \advanced_testcase {
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test move up functionality with root pages
     */
    public function test_move_up_root_pages(): void {
        global $DB;

        // Create three root pages.
        $page1 = new page();
        $page1->set('name', 'Page 1');
        $page1->create();

        $page2 = new page();
        $page2->set('name', 'Page 2');
        $page2->create();

        $page3 = new page();
        $page3->set('name', 'Page 3');
        $page3->create();

        // Verify initial order.
        $this->assertEquals('01', $page1->get('sortthread'));
        $this->assertEquals('02', $page2->get('sortthread'));
        $this->assertEquals('03', $page3->get('sortthread'));

        // Move page 2 up (should swap with page 1).
        $pagehelper = new page_helper($page2);
        $result = $pagehelper->reorder_page($page2->get('id'), constants::PAGE_UP);
        $this->assertTrue($result);

        // Reload pages to check new order.
        $page1->read();
        $page2->read();
        $page3->read();

        $this->assertEquals('02', $page1->get('sortthread'));
        $this->assertEquals('01', $page2->get('sortthread'));
        $this->assertEquals('03', $page3->get('sortthread'));

        // Try to move page 2 up again (should fail - already at top).
        $result = $pagehelper->reorder_page($page2->get('id'), constants::PAGE_UP);
        $this->assertFalse($result);
    }

    /**
     * Test move down functionality with root pages
     */
    public function test_move_down_root_pages(): void {
        global $DB;

        // Create three root pages.
        $page1 = new page();
        $page1->set('name', 'Page 1');
        $page1->create();

        $page2 = new page();
        $page2->set('name', 'Page 2');
        $page2->create();

        $page3 = new page();
        $page3->set('name', 'Page 3');
        $page3->create();

        // Move page 2 down (should swap with page 3).
        $pagehelper = new page_helper($page2);
        $result = $pagehelper->reorder_page($page2->get('id'), constants::PAGE_DOWN);
        $this->assertTrue($result);

        // Reload pages to check new order.
        $page1->read();
        $page2->read();
        $page3->read();

        $this->assertEquals('01', $page1->get('sortthread'));
        $this->assertEquals('03', $page2->get('sortthread'));
        $this->assertEquals('02', $page3->get('sortthread'));

        // Try to move page 2 down again (should fail - already at bottom).
        $result = $pagehelper->reorder_page($page2->get('id'), constants::PAGE_DOWN);
        $this->assertFalse($result);
    }

    /**
     * Test move functionality with child pages
     */
    public function test_move_child_pages(): void {
        // Create parent page.
        $parent = new page();
        $parent->set('name', 'Parent Page');
        $parent->set('iscontainer', 1);
        $parent->create();

        // Create child pages.
        $child1 = new page();
        $child1->set('name', 'Child 1');
        $child1->set('parent', $parent->get('id'));
        $child1->create();

        $child2 = new page();
        $child2->set('name', 'Child 2');
        $child2->set('parent', $parent->get('id'));
        $child2->create();

        $child3 = new page();
        $child3->set('name', 'Child 3');
        $child3->set('parent', $parent->get('id'));
        $child3->create();

        // Verify initial order.
        $this->assertEquals('01.01', $child1->get('sortthread'));
        $this->assertEquals('01.02', $child2->get('sortthread'));
        $this->assertEquals('01.03', $child3->get('sortthread'));

        // Move child 2 up.
        $pagehelper = new page_helper($child2);
        $result = $pagehelper->reorder_page($child2->get('id'), constants::PAGE_UP);
        $this->assertTrue($result);

        // Reload children to check new order.
        $child1->read();
        $child2->read();
        $child3->read();

        $this->assertEquals('01.02', $child1->get('sortthread'));
        $this->assertEquals('01.01', $child2->get('sortthread'));
        $this->assertEquals('01.03', $child3->get('sortthread'));
    }

    /**
     * Test edge case: single page
     */
    public function test_single_page_move(): void {
        // Create single page.
        $page = new page();
        $page->set('name', 'Only Page');
        $page->create();

        $pagehelper = new page_helper($page);

        // Try to move up (should fail).
        $result = $pagehelper->reorder_page($page->get('id'), constants::PAGE_UP);
        $this->assertFalse($result);

        // Try to move down (should fail).
        $result = $pagehelper->reorder_page($page->get('id'), constants::PAGE_DOWN);
        $this->assertFalse($result);
    }

    /**
     * Test edge case: first and last items
     */
    public function test_boundary_conditions(): void {
        // Create three pages.
        $page1 = new page();
        $page1->set('name', 'First Page');
        $page1->create();

        $page2 = new page();
        $page2->set('name', 'Middle Page');
        $page2->create();

        $page3 = new page();
        $page3->set('name', 'Last Page');
        $page3->create();

        // Test first page move up (should fail).
        $pagehelper1 = new page_helper($page1);
        $result = $pagehelper1->reorder_page($page1->get('id'), constants::PAGE_UP);
        $this->assertFalse($result);

        // Test last page move down (should fail).
        $pagehelper3 = new page_helper($page3);
        $result = $pagehelper3->reorder_page($page3->get('id'), constants::PAGE_DOWN);
        $this->assertFalse($result);

        // Test middle page moves (should succeed).
        $pagehelper2 = new page_helper($page2);
        $result = $pagehelper2->reorder_page($page2->get('id'), constants::PAGE_UP);
        $this->assertTrue($result);

        $result = $pagehelper2->reorder_page($page2->get('id'), constants::PAGE_DOWN);
        $this->assertTrue($result);
    }

    /**
     * Test sortthread persistence after moves
     */
    public function test_sortthread_persistence(): void {
        global $DB;

        // Create pages.
        $page1 = new page();
        $page1->set('name', 'Page A');
        $page1->create();

        $page2 = new page();
        $page2->set('name', 'Page B');
        $page2->create();

        // Move page 2 up.
        $pagehelper = new page_helper($page2);
        $pagehelper->reorder_page($page2->get('id'), constants::PAGE_UP);

        // Check database directly.
        $dbpage1 = $DB->get_record('local_iomadcustompages', ['id' => $page1->get('id')]);
        $dbpage2 = $DB->get_record('local_iomadcustompages', ['id' => $page2->get('id')]);

        $this->assertEquals('02', $dbpage1->sortthread);
        $this->assertEquals('01', $dbpage2->sortthread);

        // Verify ordering query works correctly.
        $pages = $DB->get_records('local_iomadcustompages', null, 'sortthread ASC');
        $pageids = array_keys($pages);

        $this->assertEquals($page2->get('id'), $pageids[0]);
        $this->assertEquals($page1->get('id'), $pageids[1]);
    }

    /**
     * Test mixed hierarchy moves
     */
    public function test_mixed_hierarchy_moves(): void {
        // Create root pages.
        $root1 = new page();
        $root1->set('name', 'Root 1');
        $root1->set('iscontainer', 1);
        $root1->create();

        $root2 = new page();
        $root2->set('name', 'Root 2');
        $root2->create();

        // Create children under root1.
        $child1 = new page();
        $child1->set('name', 'Child 1');
        $child1->set('parent', $root1->get('id'));
        $child1->create();

        $child2 = new page();
        $child2->set('name', 'Child 2');
        $child2->set('parent', $root1->get('id'));
        $child2->create();

        // Move root2 up (should swap with root1).
        $pagehelper = new page_helper($root2);
        $result = $pagehelper->reorder_page($root2->get('id'), constants::PAGE_UP);
        $this->assertTrue($result);

        // Reload all pages.
        $root1->read();
        $root2->read();
        $child1->read();
        $child2->read();

        // Check that root pages swapped.
        $this->assertEquals('02', $root1->get('sortthread'));
        $this->assertEquals('01', $root2->get('sortthread'));

        // Check that children maintained their relative positions under root1.
        $this->assertEquals('02.01', $child1->get('sortthread'));
        $this->assertEquals('02.02', $child2->get('sortthread'));
    }

    /**
     * Test moving a child page to root and back to parent.
     *
     * This tests the scenario:
     * 1. Create parent (container) with child
     * 2. Move child to root (remove parent)
     * 3. Move child back to parent
     * 4. Verify child appears correctly under parent
     */
    public function test_move_child_to_root_and_back(): void {
        global $DB;

        // Create parent container.
        $parent = new page();
        $parent->set('name', 'Parent Container');
        $parent->set('iscontainer', 1);
        $parent->create();

        // Create child page under parent.
        $child = new page();
        $child->set('name', 'Child Page');
        $child->set('parent', $parent->get('id'));
        $child->create();

        $childid = $child->get('id');
        $parentid = $parent->get('id');

        // Verify initial state.
        $this->assertEquals($parentid, $child->get('parent'));
        $this->assertEquals(2, $child->get('depth'));
        $this->assertStringStartsWith('01.', $child->get('sortthread'));

        // Step 1: Move child to root (remove parent).
        $child->set('parent', null);
        $child->update();

        // Reload child.
        $child = new page($childid);

        // Verify child is now at root level.
        $this->assertNull($child->get('parent'));
        $this->assertEquals(1, $child->get('depth'));
        // Should be a root-level sortthread (no dot).
        $this->assertStringNotContainsString('.', $child->get('sortthread'));

        // Step 2: Move child back to parent.
        $child->set('parent', $parentid);
        $child->update();

        // Reload child.
        $child = new page($childid);

        // Verify child is back under parent.
        $this->assertEquals($parentid, $child->get('parent'));
        $this->assertEquals(2, $child->get('depth'));
        // Should have parent's sortthread as prefix.
        $parentsortthread = $parent->get('sortthread');
        $this->assertStringStartsWith($parentsortthread . '.', $child->get('sortthread'));

        // Verify the child appears in a query ordering by sortthread.
        $children = $DB->get_records('local_iomadcustompages', ['parent' => $parentid], 'sortthread ASC');
        $this->assertCount(1, $children);
        $this->assertEquals($childid, reset($children)->id);

        // Also verify by fetching all pages sorted by sortthread and checking order.
        $allpages = $DB->get_records('local_iomadcustompages', null, 'sortthread ASC');
        $pageids = array_keys($allpages);

        // Parent should come before child.
        $parentpos = array_search($parentid, $pageids);
        $childpos = array_search($childid, $pageids);
        $this->assertLessThan($childpos, $parentpos, 'Parent should appear before child when sorted by sortthread');
    }

    /**
     * Test bulk reorder updates subtree sortthread prefixes.
     *
     * This specifically validates local_iomadcustompage_page_update_sort_order:
     * when root/container pages are reordered, their descendants must have
     * sortthread prefixes updated to match the new parent prefix.
     */
    public function test_bulk_reorder_updates_descendant_sortthreads(): void {
        // Create two root containers.
        $root1 = new page();
        $root1->set('name', 'Root 1');
        $root1->set('iscontainer', 1);
        $root1->create();

        $root2 = new page();
        $root2->set('name', 'Root 2');
        $root2->set('iscontainer', 1);
        $root2->create();

        // Create descendants under each root.
        $root1child = new page();
        $root1child->set('name', 'Root 1 Child');
        $root1child->set('parent', $root1->get('id'));
        $root1child->create();

        $root2child = new page();
        $root2child->set('name', 'Root 2 Child');
        $root2child->set('parent', $root2->get('id'));
        $root2child->create();

        // Sanity check initial prefixes.
        $this->assertEquals('01', $root1->get('sortthread'));
        $this->assertEquals('02', $root2->get('sortthread'));
        $this->assertEquals('01.01', $root1child->get('sortthread'));
        $this->assertEquals('02.01', $root2child->get('sortthread'));

        // Reorder roots to [root2, root1].
        $result = update_sort_order::execute([$root2->get('id'), $root1->get('id')], 0);
        $this->assertTrue($result['success']);

        // Reload persistents.
        $root1->read();
        $root2->read();
        $root1child->read();
        $root2child->read();

        // Verify roots swapped.
        $this->assertEquals('01', $root2->get('sortthread'));
        $this->assertEquals('02', $root1->get('sortthread'));

        // Verify descendants now follow the new prefixes.
        $this->assertEquals('01.01', $root2child->get('sortthread'));
        $this->assertEquals('02.01', $root1child->get('sortthread'));
    }
}
