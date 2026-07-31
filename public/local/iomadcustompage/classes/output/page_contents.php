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
 * Page contents renderable for template output.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\output;

use local_iomadcustompage\local\models\page;
use renderable;
use renderer_base;
use templatable;

/**
 * Page contents renderable class.
 *
 * Displays block instances attached to a custom page.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_contents implements renderable, templatable {
    /** @var page The page persistent model. */
    private page $page;

    /**
     * Constructor.
     *
     * @param page $page The page persistent model.
     */
    public function __construct(page $page) {
        $this->page = $page;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output The renderer.
     * @return array Data for template including block instances by region.
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        // Get the blocks in the page.
        $pagecontext = $this->page->get_context();
        $instances = $DB->get_recordset('block_instances', ['parentcontextid' => $pagecontext->id]);

        $blocksinstances = [];
        $blocksinstances['nocontents'] = true;

        foreach ($instances as $instance) {
            $blocksinstances['nocontents'] = false;
            if (!isset($blocksinstances[$instance->defaultregion])) {
                $blocksinstances[$instance->defaultregion]['regioncontents'] = [];
                $blocksinstances[$instance->defaultregion]['regionname'] = get_string(
                    'region-' . $instance->defaultregion,
                    'local_iomadcustompage'
                );
            }
            $blocksinstances[$instance->defaultregion]['regioncontents'][] = $instance;
        }
        $instances->close();

        if ($blocksinstances['nocontents']) {
            $blocksinstances['nocontentsmessage'] = get_string('noblocks', 'local_iomadcustompage');
        } else {
            foreach ($blocksinstances as $regionname => $regionblocksinstances) {
                if ($regionname === 'nocontents') {
                    continue;
                }
                foreach ($regionblocksinstances['regioncontents'] as $blocksinstance) {
                    $blocksinstance->name = get_string('pluginname', 'block_' . $blocksinstance->blockname);
                }
            }
        }

        return $blocksinstances;
    }
}
