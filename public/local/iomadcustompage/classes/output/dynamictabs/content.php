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

namespace local_iomadcustompage\output\dynamictabs;

use coding_exception;
use core\exception\moodle_exception;
use core\output\dynamic_tabs\base;
use local_iomadcustompage\factories\page_factory;
use local_iomadcustompage\local\models\page;
use local_iomadcustompage\permission;
use moodle_url;
use renderer_base;

/**
 * Page contents tab
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends base {
  /**
   * Export this for use in a mustache template context.
   *
   * @param renderer_base $output
   * @return array
   * @throws coding_exception
   * @throws moodle_exception
   */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $iomadcustompage = page_factory::create((int)$this->data['pageid']);

        $pageediturl = new moodle_url('/local/iomadcustompage/view.php', ['id' => $this->data['pageid']]);

        return [
        'pageid' => $this->data['pageid'],
        'pageediturl' => $pageediturl->out(),
        'pagecontents' => $iomadcustompage->content_output(),
        ];
    }

  /**
   * The label to be displayed on the tab
   *
   * @return string
   * @throws coding_exception
   */
    public function get_tab_label(): string {
        return get_string('content', 'local_iomadcustompage');
    }

    /**
     * Check permission of the current user to access this tab
     *
     * @return bool
     */
    public function is_available(): bool {
        $pagepersistent = new page((int)$this->data['pageid']);
        return permission::can_edit_page($pagepersistent);
    }

    /**
     * Template to use to display tab contents
     *
     * @return string
     */
    public function get_template(): string {
        return 'local_iomadcustompage/local/dynamictabs/content';
    }
}
