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
 * Call to action label.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output;

use cm_info;
use completion_info;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/course/format/designer/lib.php");

/**
 * Call to action label.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class call_to_action extends cm_completion {

    /**
     * Get the call action label html.
     * @return string
     * @throws \coding_exception
     */
    final public function get_call_to_action_label(): string {
        global $USER;
        $modtype = $this->get_cm()->get_module_type_name();
        if ($this->is_restricted()) {
            return get_string('calltoactionrestricted', 'format_designer');
        }
        if (!$this->is_tracked_user($USER->id) ||
            !$this->get_completion_info()->is_enabled($this->get_cm())) {
            return get_string('calltoactionview', 'format_designer', $modtype);
        }

        if ($this->get_completion_state() == COMPLETION_INCOMPLETE) {
            return $this->get_completion_data()->viewed ?
                get_string('calltoactioncontinue', 'format_designer', $modtype) :
                get_string('calltoactionstart', 'format_designer', $modtype);
        }

        return get_string('calltoactionview', 'format_designer', $modtype);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $cmid = $this->get_cm()->id;
        $actiontextcolor = '';
        if (format_designer_has_pro()) {
            $moduledesign = \format_designer\options::get_options($cmid);
            if ($moduledesign) {
                $actiontextcolor = !empty($moduledesign->textcolor) ? "color: ". $moduledesign->textcolor . ";" : '';
            }
        }
        return [
            'calltoactionlabel' => $this->get_call_to_action_label(),
            'colorclass' => $this->get_color_class(),
            'modurl' => $this->get_cm_url(),
            'actiontextcolor' => $actiontextcolor,
            'isrestricted' => $this->is_restricted(),
        ];
    }
}
