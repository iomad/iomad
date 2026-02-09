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
 * Contains the default cm controls output class.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat\content\cm;

use core_courseformat\output\local\content\cm\controlmenu as controlmenu_base;
use action_menu;
use action_menu_link;

/**
 * Base class to render cm controls.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {

    /**
     * Generate the aciton menu element.
     *
     * This method is public in case some block needs to modify the menu before output it.
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return aciton_menu the activity action menu
     */
    public function get_action_menu(\renderer_base $output): ?action_menu {

        if (!empty($this->menu)) {
            return $this->menu;
        }

        $mod = $this->mod;

        $controls = $this->cm_control_items();

        if (empty($controls) || (isset($mod->get_course()->coursedisplay) &&
            $mod->get_course()->coursedisplay == COURSE_DISPLAY_MULTIPAGE)) {
            return null;
        }

        // Convert control array into an action_menu.
        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-cm-edit-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        $ownerselector = $this->displayoptions['ownerselector'] ?? '#module-' . $mod->id;
        $menu->set_owner_selector($ownerselector);

        foreach ($controls as $control) {
            if ($control instanceof action_menu_link) {
                $control->add_class('cm-edit-action');
            }
            $menu->add($control);
        }

        $this->menu = $menu;

        return $menu;
    }
}
