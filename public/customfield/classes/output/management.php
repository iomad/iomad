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
 * Customfield component output.
 *
 * @package   core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_customfield\output;

use core_customfield\api;
use core_customfield\customfield\shared_handler;
use core_customfield\handler;
use core_customfield\shared;
use core_customfield\field_controller;
use core\url;
use core\output\action_menu;
use core\output\pix_icon;
use core\output\renderer_base;
use core\output\renderable;
use core\output\templatable;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * Class management
 *
 * @package core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management implements renderable, templatable {

    /**
     * @var handler
     */
    protected $handler;
    /**
     * @var
     */
    protected $categoryid;

    /**
     * management constructor.
     *
     * @param \core_customfield\handler $handler
     */
    public function __construct(handler $handler) {
        $this->handler = $handler;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $fieldtypes = $this->handler->get_available_field_types();
        $categories = $this->handler->get_categories_with_fields(true);
        $component = $this->handler->get_component();
        $area = $this->handler->get_area();
        $itemid = $this->handler->get_itemid();
        $sharedhandler = shared_handler::get_handler('core_customfield', 'shared');

        // Get all enabled shared categories at once.
        $sharedcategoriesenabled = shared::get_records([
            'component' => $component,
            'area' => $area,
            'itemid' => $itemid,
        ]);
        foreach ($sharedcategoriesenabled as $record) {
            $sharedcategoriesenabledbyid[$record->get('categoryid')] = $record;
        }

        $categoriesdata = [];
        $movablefieldscount = 0;
        $movablecategoriescount = 0;

        foreach ($categories as $category) {
            $categoryid = $category->get('id');
            $categoryname = $category->get_formatted_name();
            $canedit = $component === $category->get('component') && $area === $category->get('area');
            $canconvert = $sharedhandler->can_configure() && !$category->get('shared');
            $nameeditable = $canedit
                ? $output->render(api::get_category_inplace_editable($category, true))
                : $categoryname;
            $sharedtogglestatus = !empty($sharedcategoriesenabledbyid[$categoryid]);
            $hasduplicatecustomfield = false;

            if ($canedit) {
                $movablecategoriescount++;
            }

            $categoryaddfieldmenu = $this->get_create_new_field_action_menu($fieldtypes, $categoryid, $canedit);
            $categorysharetoggle = $this->get_share_category_toggle(
                $output,
                $categoryid,
                $categoryname,
                $sharedtogglestatus,
                $component,
                $area,
                $itemid,
                $canedit
            );

            $categoryfields = [];
            foreach ($category->get_fields() as $field) {
                if ($canconvert && !$hasduplicatecustomfield) {
                    $fieldisunique = api::is_shortname_unique($this->handler, $field->get('shortname'), $field->get('id'));
                    $hasduplicatecustomfield = !$fieldisunique;
                }
                if ($canedit) {
                    $movablefieldscount++;
                }
                $categoryfields[] = $this->build_field_data(
                    $field,
                    $fieldtypes,
                    $output,
                    $canedit,
                );
            }

            $categoryactionsmenu = $this->get_category_action_menu($categoryid, $canedit, $canconvert, $hasduplicatecustomfield);

            $categorydata = [
                'id' => $categoryid,
                'name' => $categoryname,
                'nameeditable' => $nameeditable,
                'movetitle' => get_string('movecategory', 'core_customfield', $categoryname),
                'canedit' => $canedit,
                'actionsmenu' => $categoryactionsmenu ? $output->render($categoryactionsmenu) : '',
                'addfieldmenu' => $categoryaddfieldmenu ? $output->render($categoryaddfieldmenu) : '',
                'toggle' => $categorysharetoggle,
                'fields' => $categoryfields,
                'extraclasses' => !$canedit && !$sharedtogglestatus ? 'disabled' : '',
            ];

            $categoriesdata[] = $categorydata;
        }

        return (object)[
            'component' => $component,
            'area' => $area,
            'itemid' => $itemid,
            'usescategories' => $this->handler->uses_categories(),
            'hascategories' => $movablecategoriescount > 0,
            'hassharedcategories' => $movablecategoriescount < count($categoriesdata),
            'categories' => $categoriesdata,
            'canmovecategories' => $movablecategoriescount > 1,
            'canmovefields' => $movablefieldscount > 1 || $movablecategoriescount > 1,
        ];
    }

    /**
     * Get the action menu for a custom field category.
     *
     * @param int $categoryid The category ID.
     * @param bool $canedit Whether the user can edit the category.
     * @param bool $canconvert Whether the user can convert the category.
     * @param bool $hasduplicatecustomfield Whether the category has duplicated custom field short names.
     * @return action_menu|null The action menu.
     */
    private function get_category_action_menu(
        int $categoryid,
        bool $canedit,
        bool $canconvert,
        bool $hasduplicatecustomfield
    ): ?action_menu {
        if (!$canedit) {
            return null;
        }

        $menu = new action_menu();
        $menu->set_kebab_trigger(triggername: get_string('actions'));
        $menu->add(new \action_menu_link_secondary(
            url: new url('#'),
            icon: new pix_icon('t/delete', 'core'),
            text: get_string('delete'),
            attributes: [
                'data-role' => 'deletecategory',
                'data-id' => $categoryid,
            ]
        ));

        if ($canconvert) {
            $menu->add(new \action_menu_link_secondary(
                url: new url('#'),
                icon: new pix_icon('i/siteevent', 'core'),
                text: get_string('convertcategory', 'core_customfield'),
                attributes: [
                    'data-role' => !$hasduplicatecustomfield ? 'convertcategory' : 'hasduplicatecustomfield',
                    'data-id' => $categoryid,
                ]
            ));
        }

        return $menu;
    }

    /**
     * Get the action menu for a custom field.
     *
     * @param field_controller $field The custom field.
     * @param bool $canedit Whether the user can edit the field.
     * @return action_menu|null The action menu.
     */
    private function get_customfield_action_menu(field_controller $field, bool $canedit): ?action_menu {
        if (!$canedit) {
            return null;
        }

        $menu = new action_menu();
        $menu->set_additional_classes('d-flex justify-content-end');
        $menu->set_kebab_trigger(triggername: get_string('actions'), extraclasses: '');

        $fieldname = $field->get_formatted_name();

        $menu->add(new \action_menu_link_secondary(
            url: new url('#'),
            icon: new pix_icon('t/edit', 'core'),
            text: get_string('edit'),
            attributes: ['data-role' => 'editfield', 'data-name' => $fieldname, 'data-id' => $field->get('id')]
        ));
        $menu->add(new \action_menu_link_secondary(
            url: new url('#'),
            icon: new pix_icon('t/delete', 'core'),
            text: get_string('delete'),
            attributes: ['data-role' => 'deletefield', 'data-id' => $field->get('id')]
        ));

        return $menu;
    }

    /**
     * Get the "Create new field" action menu.
     *
     * @param array $fieldtypes The available field types.
     * @param int $categoryid The category ID.
     * @param bool $canedit Whether the user can edit the category.
     * @return action_menu|null The action menu.
     */
    private function get_create_new_field_action_menu(array $fieldtypes, int $categoryid, bool $canedit): ?action_menu {
        if (!$canedit) {
            return null;
        }

        $menu = new action_menu();
        $menu->set_menu_trigger(
            get_string('createnewcustomfield', 'core_customfield'),
            'btn btn-sm btn-subtle-body dropdown-toggle'
        );

        foreach ($fieldtypes as $type => $fieldname) {
            $params = [
                'data-role' => 'addfield',
                'data-categoryid' => $categoryid,
                'data-type' => $type,
                'data-typename' => $fieldname,
            ];
            $action = new \action_menu_link_secondary(new url('#'), null, $fieldname, $params);
            $menu->add($action);
        }

        return $menu;
    }

    /**
     * Build the field array for a custom field.
     *
     * @param field_controller $field The custom field.
     * @param array $fieldtypes The available field types.
     * @param \renderer_base $output The renderer.
     * @param bool $canedit Whether the field can be edited.
     * @return array The field data.
     */
    private function build_field_data(
        field_controller $field,
        array $fieldtypes,
        renderer_base $output,
        bool $canedit,
    ): array {
        $fieldname = $field->get_formatted_name();
        $actionsmenu = $this->get_customfield_action_menu($field, $canedit);

        $fieldarray = [
            'type'      => $fieldtypes[$field->get('type')],
            'id'        => $field->get('id'),
            'name'      => $fieldname,
            'shortname' => $field->get('shortname'),
            'movetitle' => get_string('movefield', 'core_customfield', $fieldname),
            'actionsmenu'   => $actionsmenu ? $output->render($actionsmenu) : '',
        ];

        return $fieldarray;
    }

    /**
     * Build the shared toggle for a category.
     *
     * @param renderer_base $output The renderer.
     * @param int $categoryid The category ID.
     * @param string $categoryname The category name.
     * @param bool $sharedenabled Whether sharing is enabled.
     * @param string $component The component name.
     * @param string $area The area name.
     * @param int $itemid The item ID.
     * @param bool $canedit Whether the category can be edited.
     * @return string The rendered toggle HTML.
     */
    private function get_share_category_toggle(
        renderer_base $output,
        int $categoryid,
        string $categoryname,
        bool $sharedenabled,
        string $component,
        string $area,
        int $itemid,
        bool $canedit
    ): string {
        if ($canedit) {
            return '';
        }

        $attributes = [
            ['name' => 'data-id', 'value' => $categoryid],
            ['name' => 'data-action', 'value' => 'shared-toggle'],
            ['name' => 'data-state', 'value' => $sharedenabled ? '1' : '0'],
            ['name' => 'data-component', 'value' => $component],
            ['name' => 'data-area', 'value' => $area],
            ['name' => 'data-itemid', 'value' => $itemid],
        ];

        return $output->render_from_template('core/toggle', [
            'id'              => 'shared-toggle-' . $categoryid,
            'checked'         => $sharedenabled,
            'extraattributes' => $attributes,
            'label'           => get_string('enableplugin', 'core_admin', $categoryname),
            'labelclasses'    => 'visually-hidden',
        ]);
    }
}
