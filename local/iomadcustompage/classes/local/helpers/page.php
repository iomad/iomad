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

namespace local_iomadcustompage\local\helpers;

use coding_exception;
use core\invalid_persistent_exception;
use invalid_parameter_exception;
use local_iomadcustompage\custom_context\context_iomadcustompage;
use local_iomadcustompage\local\models\page as page_model;
use local_iomadcustompage\manager;
use stdClass;

/**
 * Helper class for manipulating custom pages
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {
    /**
     * Create custom page
     *
     * @param stdClass $data
     * @return page_model
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public static function create_page(stdClass $data): page_model {
        $data->name = trim($data->name);
        $data->title = trim($data->title);
        $pagepersistent = manager::create_page_persistent($data);
        $pageid = $pagepersistent->get('id');
        $context = context_iomadcustompage::instance($pageid);
        $data->id = $pageid;
        $newpersistent = new page_model($pageid);
        $newpersistent->set('contextid', $context->id);
        $newpersistent->update();
        return $newpersistent;
    }

  /**
   * Update custom page
   *
   * @param stdClass $data
   * @return page_model
   * @throws coding_exception
   * @throws invalid_parameter_exception
   * @throws invalid_persistent_exception
   */
    public static function update_page(stdClass $data): page_model {
        $page = page_model::get_record(['id' => $data->id]);
        if ($page === false) {
            throw new invalid_parameter_exception('Invalid page');
        }

        $page->set_many([
            'name' => trim($data->name),
            'title' => trim($data->title),
        ])->update();

        return $page;
    }

    /**
     * Delete custom page
     *
     * @param int $pageid
     * @return bool
     * @throws invalid_parameter_exception
     */
    public static function delete_page(int $pageid): bool {
        $page = page_model::get_record(['id' => $pageid]);
        if ($page === false) {
            throw new invalid_parameter_exception('Invalid page');
        }

        return $page->delete();
    }

}
