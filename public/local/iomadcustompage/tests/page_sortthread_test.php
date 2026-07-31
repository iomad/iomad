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
 * Unit tests for page sortthread functionality
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage;

use local_iomadcustompage\local\models\page;
use local_iomadcustompage\local\helpers\vancode;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for page sortthread functionality
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_sortthread_test extends \advanced_testcase {
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test sortthread generation for new pages
     */
    public function test_sortthread_generation(): void {
        global $DB;

        // Create first root page.
        $page1 = new page();
        $page1->set('name', 'Page 1');
        $page1->set('iscontainer', 1);

        $page1->create();

        $this->assertEquals('01', $page1->get('sortthread'));

        // Create second root page.
        $page2 = new page();
        $page2->set('name', 'Page 2');
        $page2->create();

        $this->assertEquals('02', $page2->get('sortthread'));

        // Create child page under root container.
        $child1 = new page();
        $child1->set('name', 'Child 1');
        $child1->set('parent', $page1->get('id'));
        $child1->create();

        $this->assertEquals('01.01', $child1->get('sortthread'));

        // Create second child page.
        $child2 = new page();
        $child2->set('name', 'Child 2');
        $child2->set('parent', $page1->get('id'));
        $child2->create();

        $this->assertEquals('01.02', $child2->get('sortthread'));

        // Grandchildren are not allowed because only root containers can have children.
        // Attempting to create a grandchild should fail validation.
        $this->expectException(\core\invalid_persistent_exception::class);
        $grandchild = new page();
        $grandchild->set('name', 'Grandchild 1');
        $grandchild->set('parent', $child1->get('id'));
        $grandchild->create();
    }

    /**
     * Test page ordering by sortthread
     */
    public function test_page_ordering(): void {
        global $DB;

        // Create pages in random order.
        $page2 = new page();
        $page2->set('name', 'Page 2');
        $page2->create();

        $page1 = new page();
        $page1->set('name', 'Page 1');
        $page1->set('iscontainer', 1);

        $page1->create();

        $child2 = new page();
        $child2->set('name', 'Child 2');
        $child2->set('parent', $page1->get('id'));
        $child2->create();

        $child1 = new page();
        $child1->set('name', 'Child 1');
        $child1->set('parent', $page1->get('id'));
        $child1->create();

        // Get pages ordered by sortthread.
        $pages = $DB->get_records('local_iomadcustompages', null, 'sortthread ASC');
        $sortthreads = array_column($pages, 'sortthread');

        // Should be in correct order given creation order (page2 root first).
        $expected = ['01', '02', '02.01', '02.02'];
        $this->assertEquals($expected, array_values($sortthreads));
    }

    /**
     * Test sortthread updates when moving pages
     */
    public function test_sortthread_updates(): void {
        global $DB;

        // Create test hierarchy.
        $root1 = new page();
        $root1->set('name', 'Root 1');
        $root1->set('iscontainer', 1);

        $root1->create();

        $root2 = new page();
        $root2->set('name', 'Root 2');
        $root2->create();

        $child1 = new page();
        $child1->set('name', 'Child 1');
        $child1->set('parent', $root1->get('id'));
        $child1->create();

        $child2 = new page();
        $child2->set('name', 'Child 2');
        $child2->set('parent', $root1->get('id'));
        $child2->create();

        // Test swapping sortthreads.
        $success = $root1->swap_item_sortthreads($child1->get('id'), $child2->get('id'));
        $this->assertTrue($success);

        // Reload pages to check new sortthreads.
        $child1->read();
        $child2->read();

        // Child2 should now come before child1.
        $this->assertEquals('01.01', $child2->get('sortthread'));
        $this->assertEquals('01.02', $child1->get('sortthread'));
    }

    /**
     * Test hierarchy depth calculation
     */
    public function test_hierarchy_depth(): void {
        // Create deep hierarchy.
        $level1 = new page();
        $level1->set('name', 'Level 1');
        $level1->set('iscontainer', 1);

        $level1->create();

        $level2 = new page();
        $level2->set('name', 'Level 2');
        $level2->set('parent', $level1->get('id'));
        $level2->create();

        $this->assertEquals(1, $level1->get('depth'));
        $this->assertEquals(2, $level2->get('depth'));

        $this->assertEquals('01', $level1->get('sortthread'));
        $this->assertEquals('01.01', $level2->get('sortthread'));
    }

    /**
     * Test sortthread regeneration
     */
    public function test_sortthread_regeneration(): void {
        global $DB;

        // Create pages with manual sortthread values.
        $page1id = $DB->insert_record('local_iomadcustompages', [
            'name' => 'Page 1',
            'sortthread' => 'invalid',
            'depth' => 1,
            'path' => '/1',
            'usercreated' => 2,
            'timecreated' => time(),
        ]);

        $page2id = $DB->insert_record('local_iomadcustompages', [
            'name' => 'Page 2',
            'sortthread' => 'also_invalid',
            'depth' => 1,
            'path' => '/2',
            'usercreated' => 2,
            'timecreated' => time(),
        ]);

        // Load page and trigger sortthread regeneration.
        $page1 = new page($page1id);

        // Use reflection to call private method.
        $reflection = new \ReflectionClass($page1);
        $method = $reflection->getMethod('update_all_sortthreads');
        $method->setAccessible(true);
        $method->invoke($page1);

        // Check that sortthreads were regenerated.
        $page1->read();
        $page2 = new page($page2id);

        $this->assertEquals('01', $page1->get('sortthread'));
        $this->assertEquals('02', $page2->get('sortthread'));
    }

    /**
     * Test performance with large number of pages
     */
    public function test_performance_large_dataset(): void {
        $starttime = microtime(true);

        // Create 100 root pages.
        for ($i = 1; $i <= 100; $i++) {
            $page = new page();
            $page->set('name', "Page {$i}");
            $page->create();
        }

        $endtime = microtime(true);
        $duration = $endtime - $starttime;

        // Should complete within reasonable time (adjust threshold as needed).
        $this->assertLessThan(10, $duration, 'Creating 100 pages took too long');

        // Verify sortthreads are correct.
        global $DB;
        $pages = $DB->get_records('local_iomadcustompages', null, 'sortthread ASC');

        $counter = 1;
        foreach ($pages as $page) {
            $expected = vancode::int2vancode($counter);
            $this->assertEquals($expected, $page->sortthread);
            $counter++;
        }
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases(): void {
        // Test with empty database.
        $page = new page();
        $page->set('name', 'First Page');
        $page->create();

        $this->assertEquals('01', $page->get('sortthread'));

        // Test with very long names.
        $longname = str_repeat('A very long page name ', 50);
        $page2 = new page();
        $page2->set('name', $longname);
        $page2->create();

        $this->assertEquals('02', $page2->get('sortthread'));

        // Test with special characters in names.
        $specialname = 'Page with "quotes" & <tags> and émojis 🎉';
        $page3 = new page();
        $page3->set('name', $specialname);
        $page3->create();

        $this->assertEquals('03', $page3->get('sortthread'));
    }
}
