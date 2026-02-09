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
 * Contains the default activity title.
 *
 * This class is usually rendered inside the cmname inplace editable.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat\content\cm;

use moodle_url;
use stdClass;

/**
 * Base class to render a course module title inside a course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class title extends \core_courseformat\output\local\content\cm\title {

    /**
     * Return the title template data to be used inside the inplace editable.
     *
     */
    protected function get_title_displayvalue (): string {
        global $PAGE, $CFG;

        // Inplace editable uses core renderer by default. However, course elements require
        // the format specific renderer.
        $courseoutput = $this->format->get_renderer($PAGE);

        $mod = $this->mod;

        $data = (object)[
            'url' => ($mod->modname == 'videotime') ? new moodle_url('/mod/videotime/view.php', ['id' => $mod->id]) : $mod->url,
            'instancename' => ($mod->modname == 'videotime') ? $mod->name : $mod->get_formatted_name(),
            'uservisible' => $mod->uservisible,
            'linkclasses' => $this->displayoptions['linkclasses'],
        ];
        $useactivityitemcustom = \format_designer\options::get_option($mod->id, 'customtitleuseactivityitem');
        if ($useactivityitemcustom) {
            $data->designercmname = $this->format->get_cm_secondary_title($mod);
        }

        // File type after name, for alphabetic lists (screen reader).
        if (strpos(
            \core_text::strtolower($data->instancename),
            \core_text::strtolower($mod->modfullname)
        ) === false) {
            $data->altname = get_accesshide(' ' . $mod->modfullname);
        }

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $data->onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);
        if (format_designer_has_pro()) {
            require_once($CFG->dirroot. "/local/designer/lib.php");
            if ($textcolor = \format_designer\options::get_option($mod->id, 'textcolor')) {
                $data->moduletextcolor = "color: $textcolor" . ";";
            }
        }

        return $courseoutput->render_from_template(
            'format_designer/courseformat/content/cm/title',
            $data
        );
    }

}
