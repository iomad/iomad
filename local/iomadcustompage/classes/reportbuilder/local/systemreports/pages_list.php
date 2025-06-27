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

namespace local_iomadcustompage\reportbuilder\local\systemreports;


use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\action;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use lang_string;
use local_iomadcustompage\local\helpers\audience;
use local_iomadcustompage\local\models\page;
use local_iomadcustompage\output\page_name_editable;
use local_iomadcustompage\output\page_title_editable;
use local_iomadcustompage\permission;
use local_iomadcustompage\reportbuilder\local\entities\iomadcustompages;
use moodle_url;
use pix_icon;
use stdClass;

/**
 * Pages list
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pages_list extends system_report {
    /**
     * Initialise the report
     */
    protected function initialise(): void {

        $iomadcustompageentity = new iomadcustompages();
        $entitymainalias = $iomadcustompageentity->get_table_alias('local_iomadcustompages');

        $this->set_main_table('local_iomadcustompages', $entitymainalias);
        $this->add_entity($iomadcustompageentity);

        // Select fields required for actions, permission checks, and row class callbacks.
        $this->add_base_fields("{$entitymainalias}.id,
                                {$entitymainalias}.name,
                                {$entitymainalias}.title,
                                {$entitymainalias}.usercreated,
                                {$entitymainalias}.usermodified, {$entitymainalias}.contextid");

        // Limit the returned list to those pages the current user can access.
        [$where, $params] = audience::user_pages_list_access_sql($entitymainalias);
        $this->add_base_condition_sql($where, $params);

        // Join user entity for "User modified" column.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->add_entity($entityuser
            ->add_join("LEFT JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = {$entitymainalias}.usermodified"));

        $this->add_columns($iomadcustompageentity);
        $this->add_filters($iomadcustompageentity);
        $this->add_actions();

        $this->set_downloadable(false);
    }

    /**
     * Ensure we can view the report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return permission::can_view_pages_list();
    }

    /**
     * Add columns to report
     */
    protected function add_columns(iomadcustompages $iomadcustompageentity): void {

        $tablealias = $this->get_main_table_alias();
        // Page name column.
        $this->add_column((new column(
            'name',
            new lang_string('name'),
            $iomadcustompageentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            // We need enough fields to re-create the persistent and pass to the editable component.
            ->add_fields(implode(', ', [
                "{$tablealias}.id",
                "{$tablealias}.name",
                "{$tablealias}.contextid",
            ]))
            ->set_is_sortable(true, ["{$tablealias}.name"])
            ->add_callback(static function (string $value, stdClass $page): string {
                global $PAGE;
                $editable = new page_name_editable(0, new page(0, $page));
                return $editable->render($PAGE->get_renderer('core'));

            }));

        $this->add_column((new column(
            'title',
            new lang_string('title', 'local_iomadcustompage'),
            $iomadcustompageentity->get_entity_name()
        ))
        ->set_type(column::TYPE_TEXT)
        // We need enough fields to re-create the persistent and pass to the editable component.
        ->add_fields(implode(', ', [
          "{$tablealias}.id",
          "{$tablealias}.title",
          "{$tablealias}.contextid",
        ]))
        ->set_is_sortable(true, ["{$tablealias}.title"])
        ->add_callback(static function (string $value, stdClass $page): string {
            global $PAGE;
            $editable = new page_title_editable(0, new page(0, $page));
            return $editable->render($PAGE->get_renderer('core'));
        }));

        // Time modified column.
        $this->add_column((new column(
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $iomadcustompageentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']));

        // The user who modified the page.
        $this->add_column_from_entity('user:fullname')
            ->set_title(new lang_string('usermodified', 'reportbuilder'));
    }

    /**
     * Add filters to report
     */
    protected function add_filters(iomadcustompages $iomadcustompageentity): void {

        $filters = [
        "{$iomadcustompageentity->get_entity_name()}:name",
        "{$iomadcustompageentity->get_entity_name()}:title",
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Add actions to report
     */
    protected function add_actions(): void {
        // Edit content action.
        $this->add_action((new action(
            new moodle_url('/local/iomadcustompage/edit.php', ['id' => ':id']),
            new pix_icon('t/right', ''),
            [],
            false,
            new lang_string('editpagecontent', 'local_iomadcustompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Edit details action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/edit', ''),
            ['data-action' => 'page-edit', 'data-page-id' => ':id'],
            false,
            new lang_string('editpagedetails', 'local_iomadcustompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Preview action.
        $this->add_action((new action(
            new moodle_url('/local/iomadcustompage/view.php', ['id' => ':id']),
            new pix_icon('i/search', ''),
            [],
            false,
            new lang_string('viewpage', 'local_iomadcustompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                // We check this only to give the action to editors, because normal users can just click on the page name.
                return permission::can_view_page(new page(0, $row));
            }));

        // Delete action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/delete', ''),
            ['data-action' => 'page-delete', 'data-page-id' => ':id', 'data-page-name' => ':name'],
            false,
            new lang_string('deletepage', 'local_iomadcustompage')
        ))
            ->add_callback(function (stdClass $row): bool {

                // Ensure data name attribute is properly formatted.
                $page = new page(0, $row);
                $row->name = $page->get_formatted_name();

                // We don't check whether page is valid to ensure editor can always delete them.
                return permission::can_edit_page($page);
            }));
    }
}
