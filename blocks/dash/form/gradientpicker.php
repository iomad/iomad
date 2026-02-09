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
 * Dash - Form element for color picker.
 *
 * @package   block_dash
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('HTML/QuickForm/input.php');
require_once($CFG->dirroot.'/lib/form/templatable_form_element.php');
require_once($CFG->dirroot.'/lib/form/text.php');

/**
 * Form element for color picker.
 *
 * @package   block_dash
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodlequickform_dashgradientpicker extends MoodleQuickForm_text implements templatable {

    use templatable_form_element {
        export_for_template as export_for_template_base;
    }

    /**
     * Constructor.
     *
     * @param string $elementname (optional) Name of the text field.
     * @param string $elementlabel (optional) Text field label.
     * @param string $attributes (optional) Either a typical HTML attribute string or an associative array.
     */
    public function __construct($elementname=null, $elementlabel=null, $attributes=null) {
        parent::__construct($elementname, $elementlabel, $attributes);
        $this->setType('text');

        // Add a CSS class for styling the color picker.
        $class = $this->getAttribute('class');
        if (empty($class)) {
            $class = '';
        }
        $this->updateAttributes(['class' => $class.' block_dash-form-gradient-picker']);
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        // Compose template context for the mform element.
        $context = $this->export_for_template_base($output);

        $PAGE->requires->js_amd_inline('require(["jquery", "block_dash/gradient"], function($) {
            var background = document.querySelectorAll("input[name=config_backgroundgradient]")[0];
                if (background) {
                    new lc_color_picker(background, {
                        modes : ["linear-gradient", "solid"],
                    });
                }
            })'
        );

        return $context;
    }
}
