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

namespace local_iomadcustompage\output;

use core\output\inplace_editable;
use local_iomadcustompage\local\models\page;
use local_iomadcustompage\permission;
use html_writer;
use moodle_url;

/**
 * Report name editable component
 *
 * @package     local_iomadcustompage
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_name_editable extends inplace_editable {
    /**
     * Class constructor
     *
     * @param int $pageid
     * @param page|null $page The page persistent
     * @param bool $indented Whether to show hierarchy indentation in the display value
     */
    public function __construct(int $pageid, ?page $page = null, bool $indented = false) {
        if ($page === null) {
            $page = new page($pageid);
        }

        $editable = permission::can_edit_page($page);

        $url = $editable
            ? new moodle_url('/local/iomadcustompage/edit.php', ['id' => $page->get('id')])
            : new moodle_url('/local/iomadcustompage/view.php', ['id' => $page->get('id')]);

        $displayname = $indented ? $page->get_formatted_name_with_indent() : $page->get_formatted_name();
        $displayvalue = html_writer::link($url, $displayname);

        parent::__construct(
            'local_iomadcustompage',
            'pagename',
            $page->get('id'),
            $editable,
            $displayvalue,
            strip_tags($displayvalue ?? ''),
            get_string('editpagename', 'local_iomadcustompage')
        );
    }

    /**
     * Update report persistent and return self, called from inplace_editable callback
     *
     * @param int $reportid
     * @param string $value
     * @return self
     */
    public static function update(int $pageid, string $value): self {
        $page = new page($pageid);

        \core_external\external_api::validate_context($page->get_context());
        permission::require_can_edit_page($page);

        $value = trim(clean_param($value, PARAM_TEXT));
        if ($value !== '') {
            $page
                ->set('name', $value)
                ->update();
        }

        return new self(0, $page, true);
    }
}
