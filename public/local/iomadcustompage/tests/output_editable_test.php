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
use core\context\system as context_system;
use local_iomadcustompage\local\models\page;
use local_iomadcustompage\output\page_name_editable;
use local_iomadcustompage\output\page_title_editable;
use stdClass;

/**
 * Tests for custom page inplace editables.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_iomadcustompage\output\page_name_editable
 * @covers     \local_iomadcustompage\output\page_title_editable
 * @group      local_iomadcustompage
 */
final class output_editable_test extends advanced_testcase {
    /**
     * Create a page owned by current admin user.
     *
     * @return page
     */
    private function create_page(): page {
        $data = new stdClass();
        $data->name = 'Editable page';
        $data->title = 'Editable title';

        return manager::create_page_persistent($data);
    }

    /**
     * Name editable should be enabled for users allowed to edit the page.
     */
    public function test_page_name_editable_is_enabled_for_editor(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = $this->create_page();
        global $PAGE;

        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/local/iomadcustompage/index.php');

        $editable = new page_name_editable((int) $page->get('id'));
        $exported = $editable->export_for_template($PAGE->get_renderer('core'));

        $this->assertArrayHasKey('component', $exported);
        $this->assertSame('local_iomadcustompage', $exported['component']);
        $this->assertSame('pagename', $exported['itemtype']);
        $this->assertSame((int) $page->get('id'), (int) $exported['itemid']);
    }

    /**
     * Title update should allow clearing title back to null.
     */
    public function test_page_title_editable_update_can_clear_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = $this->create_page();
        $this->assertSame('Editable title', $page->get('title'));

        page_title_editable::update((int) $page->get('id'), '   ');

        $reloaded = new page((int) $page->get('id'));
        $this->assertNull($reloaded->get('title'));
        $this->assertSame('', $reloaded->get_formatted_title());
    }

    /**
     * Title update should still persist non-empty values.
     */
    public function test_page_title_editable_update_sets_non_empty_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = $this->create_page();
        page_title_editable::update((int) $page->get('id'), ' Updated title ');

        $reloaded = new page((int) $page->get('id'));
        $this->assertSame('Updated title', $reloaded->get('title'));
    }

    /**
     * Name update should trim and persist value.
     */
    public function test_page_name_editable_update_sets_trimmed_name(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = $this->create_page();
        page_name_editable::update((int) $page->get('id'), ' Updated name ');

        $reloaded = new page((int) $page->get('id'));
        $this->assertSame('Updated name', $reloaded->get('name'));
    }
}
