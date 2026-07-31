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
use local_iomadcustompage\custom_context\context_iomadcustompage;
use local_iomadcustompage\event\iomadcustompage_viewed;
use stdClass;

/**
 * Event tests for local_iomadcustompage.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_iomadcustompage\event\iomadcustompage_viewed
 * @group      local_iomadcustompage
 */
final class event_test extends advanced_testcase {
    /**
     * Viewed event should snapshot the real custom pages table.
     */
    public function test_iomadcustompage_viewed_uses_local_iomadcustompages_table(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Viewed page';
        $pagedata->title = '';
        $page = \local_iomadcustompage\local\helpers\page::create_page($pagedata);
        $context = context_iomadcustompage::instance((int) $page->get('id'));

        $event = iomadcustompage_viewed::create_from_object($page->to_record(), $context);

        $this->assertSame('local_iomadcustompages', $event->objecttable);
        $snapshot = $event->get_record_snapshot('local_iomadcustompages', (int) $page->get('id'));
        $this->assertSame((int) $page->get('id'), (int) $snapshot->id);
        $this->assertSame('Viewed page', $snapshot->name);
    }
}
