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
class cmname extends \core_courseformat\output\local\content\cm\cmname {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): array {
        global $DB;
        $format = $this->format;
        $mod = $this->mod;
        $displayoptions = $this->displayoptions;
        if (!$mod->is_visible_on_course_page() || !$mod->url && !$mod->modname == 'videotime') {
            // Nothing to be displayed to the user.
            $data = new stdClass();
            $data->mod = $mod;
            return (array) $data;
        }

        // Usually classes are loaded in the main cm output. However when the user uses the inplace editor
        // the cmname output does not calculate the css classes.
        if (!isset($displayoptions['linkclasses']) || !isset($displayoptions['textclasses'])) {
            $cmclass = $format->get_output_classname('content\\cm');
            $cmoutput = new $cmclass(
                $format,
                $this->section,
                $mod,
                $displayoptions
            );
            $displayoptions['linkclasses'] = $cmoutput->get_link_classes();
            $displayoptions['textclasses'] = $cmoutput->get_text_classes();
        }

        $useactivityimage = '';
        if (format_designer_has_pro()) {
            if ($mod->modname == 'videotime') {
                if ($videorecord = $DB->get_record('videotime', ['id' => $mod->instance])) {
                    if (isset($videorecord->label_mode) && $videorecord->label_mode == 2) {
                        $useactivityimage = \format_designer\options::get_option($mod->id, 'useactivityimage');
                    }
                }
            }
        }
        $sectiontype = $format->get_section_option($mod->section, 'sectiontype') ?: get_config('format_designer', 'sectiontype');
        $removecenter = ($sectiontype == 'default') ? true : false;
        $data = (object)[
            'url' => ($mod->modname == 'videotime') ? new moodle_url('/mod/videotime/view.php', ['id' => $mod->id]) : $mod->url,
            'instancename' => ($mod->modname == 'videotime') ? $mod->name : $mod->get_formatted_name(),
            'uservisible' => $mod->uservisible,
            'icon' => $mod->get_icon_url(),
            'modname' => $mod->modname,
            'pluginname' => get_string('pluginname', 'mod_' . $mod->modname),
            'linkclasses' => $displayoptions['linkclasses'],
            'textclasses' => $displayoptions['textclasses'],
            'purpose' => plugin_supports('mod', $mod->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER),
            'useactivityimage' => $useactivityimage,
            'activityname' => $this->get_title_data($output),
            'removecenter' => $removecenter,
        ];

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
        return (array) $data;
    }
}
