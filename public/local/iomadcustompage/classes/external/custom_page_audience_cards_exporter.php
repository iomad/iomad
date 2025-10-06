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

namespace local_iomadcustompage\external;

use coding_exception;
use core_collator;
use core_component;
use core_plugin_manager;
use core_reportbuilder\external\custom_report_menu_cards_exporter;
use local_iomadcustompage\local\audiences\base;
use renderer_base;

/**
 * IOMAD Custom page audience cards exporter class
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_page_audience_cards_exporter extends custom_report_menu_cards_exporter {
  /**
   * Get the additional values to inject while exporting
   *
   * @param renderer_base $output
   * @return array
   * @throws coding_exception
   */
    protected function get_other_values(renderer_base $output): array {
        $menucards = [];

        // Iterate over all audience types.
        $audiences = core_component::get_component_classes_in_namespace('local_iomadcustompage', 'iomadcustompage\\audience');
        $audiencekeyindex = 0;
        foreach ($audiences as $class => $path) {
            if (is_subclass_of($class, base::class)) {
                $audience = $class::instance();
                if (!$audience->user_can_add()) {
                    continue;
                }

                // The name of each card will be the component the audience belongs to.
                [$component] = explode('\\', $class);
                if ($plugininfo = core_plugin_manager::instance()->get_plugin_info($component)) {
                    $componentname = $plugininfo->displayname;
                } else {
                    $componentname = get_string('site');
                }

                // New menu card per component.
                if (!array_key_exists($componentname, $menucards)) {
                    $menucards[$componentname] = [
                        'name' => $componentname,
                        'key' => 'index' . ++$audiencekeyindex,
                        'items' => [],
                    ];
                }

                // Append menu card item per audience.
                $menucards[$componentname]['items'][] = [
                    'name' => $audience->get_name(),
                    'identifier' => get_class($audience),
                    'title' => get_string('addaudience', 'core_reportbuilder', $audience->get_name()),
                    'action' => 'add-audience',
                    'disabled' => !$audience->is_available(),
                ];
            }
        }

        // Order items in each menu card alphabetically.
        array_walk($menucards, static function (array &$menucard): void {
            core_collator::asort_array_of_arrays_by_key($menucard['items'], 'name');
            $menucard['items'] = array_values($menucard['items']);
        });

        return [
            'menucards' => array_values($menucards),
        ];
    }
}
