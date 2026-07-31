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
 * Performance benchmark tests for sortthread operations.
 *
 * @package     local_iomadcustompage
 * @category    test
 * @copyright   2024
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage;

use local_iomadcustompage\constants;
use local_iomadcustompage\local\iomadcustompage\page as page_helper;
use local_iomadcustompage\local\models\page;

defined('MOODLE_INTERNAL') || die();

/**
 * Performance benchmark test cases.
 *
 * @coversDefaultClass \local_iomadcustompage\local\iomadcustompage\page
 */
final class performance_benchmark_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    public function test_reorder_performance_large_root_set(): void {
        $count = 1000; // Root pages to create.

        $pages = [];
        for ($i = 0; $i < $count; $i++) {
            $p = new page();
            $p->set('name', 'Page ' . ($i + 1));
            $p->create();
            $pages[] = $p;
        }

        $this->assertCount($count, $pages);
        $mid = (int)floor($count / 2);
        $target = $pages[$mid];

        $helper = new page_helper($target);

        // Warm up.
        $helper->reorder_page($target->get('id'), constants::PAGE_UP);
        $target->read();

        $moves = 200;
        $start = microtime(true);
        for ($i = 0; $i < $moves; $i++) {
            $dir = ($i % 2 === 0) ? constants::PAGE_UP : constants::PAGE_DOWN;
            $helper->reorder_page($target->get('id'), $dir);
            $target->read();
        }
        $elapsed = microtime(true) - $start;

        // This is a soft performance guardrail; adjust if your environment is slower.
        // Aim: < 3 seconds for 200 moves in a 1000-item root set on a typical dev machine.
        $this->assertLessThan(3.0, $elapsed, 'Reordering performance regression detected. Took ' . $elapsed . 's');
    }
}
