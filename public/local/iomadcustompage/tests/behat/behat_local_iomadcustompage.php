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
 * Behat data generator for local_iomadcustompage.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

use local_iomadcustompage\manager;
use local_iomadcustompage\local\models\page;
use local_iomadcustompage\local\models\audience;

/**
 * Behat data generator for local_iomadcustompage.
 */
class behat_local_iomadcustompage_generator extends behat_generator_base {
    /**
     * Get a list of entities that can be created for this plugin.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'pages' => [
                'singular' => 'page',
                'datagenerator' => 'page',
                'required' => ['name'],
                'switchids' => ['parent' => 'name'],
            ],
            'audiences' => [
                'singular' => 'audience',
                'datagenerator' => 'audience',
                'required' => ['page', 'classname'],
                'switchids' => ['page' => 'name'],
            ],
        ];
    }

    /**
     * Look up page ID from page name.
     *
     * @param string $name
     * @return int
     */
    protected function get_page_id(string $name): int {
        global $DB;

        if (!$id = $DB->get_field('local_iomadcustompages', 'id', ['name' => $name])) {
            throw new InvalidArgumentException('The specified page with name "' . $name . '" does not exist');
        }

        return (int)$id;
    }

    /**
     * Add page entity.
     *
     * @param array $data
     * @return int
     */
    protected function process_page(array $data): int {
        global $DB;

        $record = (object)$data;

        // Handle parent relationship.
        if (!empty($record->parent)) {
            $record->parent = $this->get_page_id($record->parent);
        }

        // Set defaults.
        $record->contextid = $record->contextid ?? context_system::instance()->id;
        $record->usercreated = $record->usercreated ?? get_admin()->id;
        $record->usermodified = $record->usermodified ?? get_admin()->id;
        $record->timecreated = $record->timecreated ?? time();
        $record->timemodified = $record->timemodified ?? time();

        $page = manager::create_page_persistent($record);
        return $page->get('id');
    }

    /**
     * Add audience entity.
     *
     * @param array $data
     * @return int
     */
    protected function process_audience(array $data): int {
        $record = (object)$data;

        // Convert page name to ID.
        $record->pageid = $this->get_page_id($record->page);
        unset($record->page);

        // Set defaults.
        $record->configdata = $record->configdata ?? '{}';
        $record->usercreated = $record->usercreated ?? get_admin()->id;
        $record->usermodified = $record->usermodified ?? get_admin()->id;
        $record->timecreated = $record->timecreated ?? time();
        $record->timemodified = $record->timemodified ?? time();

        $audience = new audience(0, $record);
        $audience->create();

        return $audience->get('id');
    }
}
