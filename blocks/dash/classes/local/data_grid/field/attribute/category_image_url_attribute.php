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
 * Generate the category image url from the fetched record.
 *
 * @package    block_dash
 * @copyright  2024 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

use block_dash\local\data_grid\field\attribute\abstract_field_attribute;

/**
 * Transform data to URL of category image.
 *
 * @package block_dash
 */
class category_image_url_attribute extends abstract_field_attribute {

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param \stdClass $data
     * @param \stdClass $record Entire row
     * @return mixed
     * @throws \moodle_exception
     */
    public function transform_data($data, \stdClass $record) {
        global $CFG, $OUTPUT;

        require_once("$CFG->dirroot/blocks/dash/lib.php");

        // Data is not integer then use the cc_id.
        if (!is_int($data)) {
            $data = $record->cc_id ?? 0;
        }

        $files = $this->get_category_files();

        if (!isset($files[$data]) && !isset($files[0])) {
            return $OUTPUT->image_url('courses', 'block_myoverview');
        }

        // Verify the category images are added for the category or the fallback image is uploaded then use that.
        if (isset($files[$data]) || isset($files[0])) {

            // Category imaage or fallback image.
            $file = $files[$data] ?? $files[0];
            // Generate the URL.
            $fileurl = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(), false
            );

            return $fileurl->out(false);
        }

        return $data;
    }

    /**
     * Get the list of category images.
     *
     * @return array|null
     */
    public function get_category_files() {

        static $list = null;

        if ($list == null) {
            // Get the system context.
            $systemcontext = \context_system::instance();

            // File storage.
            $fs = get_file_storage();

            // Get all files from category image filearea.
            $files = $fs->get_area_files($systemcontext->id, 'block_dash', 'categoryimg', false, 'itemid', false);

            $list = [];
            // Update the files index as itemid.
            if (!empty($files)) {
                foreach ($files as $id => $file) {
                    $list[$file->get_itemid()] = $file;
                }
            }
        }

        return $list;
    }
}
