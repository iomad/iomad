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
 * Tour Step Renderable.
 *
 * @package    tool_usertours
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_usertours\output;

defined('MOODLE_INTERNAL') || die();

use tool_usertours\step as stepsource;

/**
 * Tour Step Renderable.
 *
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step implements \renderable {

    /**
     * @var The step instance.
     */
    protected $step;

    /**
     * The step output.
     *
     * @param   stepsource      $step       The step being output.
     */
    public function __construct(stepsource $step) {
        $this->step = $step;
    }

    /**
     * Export the step configuration.
     *
     * @param   renderer_base   $output     The renderer.
     * @return  object
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;
        $step = $this->step;

        $result = (object) [
            'stepid'    => $step->get_id(),
            'title'     => external_format_text(
                    stepsource::get_string_from_input($step->get_title()),
                    FORMAT_HTML,
                    $PAGE->context->id,
                    'tool_usertours'
                )[0],
            'content'   => external_format_text(
                    stepsource::get_string_from_input($step->get_content()),
                    FORMAT_HTML,
                    $PAGE->context->id,
                    'tool_usertours'
                )[0],
            'element'   => $step->get_target()->convert_to_css(),
        ];

        $result->content = str_replace("\n", "<br>\n", $result->content);

        foreach ($step->get_config_keys() as $key) {
            $result->$key = $step->get_config($key);
        }

        return $result;
    }
}
