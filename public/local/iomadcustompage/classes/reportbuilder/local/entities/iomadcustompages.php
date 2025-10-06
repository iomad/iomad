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
 * Class containing the definition of iomadcustompages entity
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\reportbuilder\local\entities;

use core\exception\coding_exception;
use core_collator;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\autocomplete;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use moodle_exception;

/**
 * iomadcustompage entity
 */
class iomadcustompages extends base {
    /**
     * default table alias getter
     * @return string[]
     */
    protected function get_default_table_aliases(): array {
        return [
          'local_iomadcustompages' => 'cp'
        ];
    }

    protected function get_default_tables(): array {
      return [
        'local_iomadcustompages'
      ];
    }
    /**
     * entity title getter
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entityiomadcustompages', 'local_iomadcustompage');
    }
    /**
     * entity initialiser
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

  /**
   * Returns list of all available columns
   *
   * @return column[]
   * @throws coding_exception
   */
    protected function get_all_columns(): array {
        global $DB;

        $tablealias = $this->get_table_alias('local_iomadcustompages');

        // Name column.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("$tablealias.name")
        ->set_is_sortable(true);

        // Component column.
        $columns[] = (new column(
            'title',
            new lang_string('title', 'local_iomadcustompage'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("{$tablealias}.title")
        ->set_is_sortable(true);

        // Type column.
        $columns[] = (new column(
            'usercreated',
            new lang_string('createdby', 'local_iomadcustompage'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("{$tablealias}.usercreated")
        ->set_is_sortable(true);

        // Start time column.
        $columns[] = (new column(
            'usermodified',
            new lang_string('updatedby', 'local_iomadcustompage'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("{$tablealias}.usermodified")
        ->set_is_sortable(true);

        return $columns;
    }

  /**
   * Return list of all available filters
   *
   * @return filter[]
   * @throws moodle_exception
   */
    protected function get_all_filters(): array {
        global $DB;

        $tablealias = $this->get_table_alias('local_iomadcustompages');

        // Name filter (Filter by classname).
        $filters[] = (new filter(
            autocomplete::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
        ->add_joins($this->get_joins())
        ->set_options_callback(static function (): array {
            global $DB;
            $pagenames = $DB->get_records_sql('SELECT DISTINCT id,name FROM {local_iomadcustompages} ORDER BY name');

            $options = [];
            foreach ($pagenames as $pagename) {
                $options[$pagename->id] = $pagename->name;
            }

            core_collator::asort($options);
            return $options;
        });

        // Title filter.
        $filters[] = (new filter(
            text::class,
            'title',
            new lang_string('title', 'local_iomadcustompage'),
            $this->get_entity_name(),
            "{$tablealias}.title"
        ))
        ->add_joins($this->get_joins());

        return $filters;
    }
}
