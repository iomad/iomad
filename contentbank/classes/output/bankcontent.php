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
 * core_contentbank specific renderers
 *
 * @package   core_contentbank
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_contentbank\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Class containing data for bank content
 *
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bankcontent implements renderable, templatable {

    /**
     * @var \core_contentbank\content[]    Array of content bank contents.
     */
    private $contents;

    /**
     * @var array   $toolbar object.
     */
    private $toolbar;

    /**
     * @var \context    Given context. Null by default.
     */
    private $context;

    /**
     * Construct this renderable.
     *
     * @param \core_contentbank\content[] $contents   Array of content bank contents.
     * @param array $toolbar     List of content bank toolbar options.
     * @param \context $context Optional context to check (default null)
     */
    public function __construct(array $contents, array $toolbar, \context $context = null) {
        $this->contents = $contents;
        $this->toolbar = $toolbar;
        $this->context = $context;
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        $PAGE->requires->js_call_amd('core_contentbank/search', 'init');
        $PAGE->requires->js_call_amd('core_contentbank/sort', 'init');

        $data = new stdClass();
        $contentdata = array();
        foreach ($this->contents as $content) {
            $file = $content->get_file();
            $filesize = $file ? $file->get_filesize() : 0;
            $mimetype = $file ? get_mimetype_description($file) : '';
            $contenttypeclass = $content->get_content_type().'\\contenttype';
            $contenttype = new $contenttypeclass($this->context);
            $name = $content->get_name();
            $contentdata[] = array(
                'name' => $name,
                'title' => strtolower($name),
                'link' => $contenttype->get_view_url($content),
                'icon' => $contenttype->get_icon($content),
                'timemodified' => $content->get_timemodified(),
                'bytes' => $filesize,
                'size' => display_size($filesize),
                'type' => $mimetype
            );
        }
        $data->viewlist = get_user_preferences('core_contentbank_view_list');
        $data->contents = $contentdata;
        // The tools are displayed in the action bar on the index page.
        foreach ($this->toolbar as $tool) {
            // Customize the output of a tool, like dropdowns.
            $method = 'export_tool_'.$tool['action'];
            if (method_exists($this, $method)) {
                $this->$method($tool);
            }
            $data->tools[] = $tool;
        }

        return $data;
    }

    /**
     * Adds the content type items to display to the Add dropdown.
     *
     * Each content type is represented as an object with the properties:
     *     - name: the name of the content type.
     *     - baseurl: the base content type editor URL.
     *     - types: different types of the content type to display as dropdown items.
     *
     * @param array $tool Data for rendering the Add dropdown, including the editable content types.
     */
    private function export_tool_add(array &$tool) {
        $editabletypes = $tool['contenttypes'];

        $addoptions = [];
        foreach ($editabletypes as $class => $type) {
            $contentype = new $class($this->context);
            // Get the creation options of each content type.
            $types = $contentype->get_contenttype_types();
            if ($types) {
                // Add a text describing the content type as first option. This will be displayed in the drop down to
                // separate the options for the different content types.
                $contentdesc = new stdClass();
                $contentdesc->typename = get_string('description', $contentype->get_contenttype_name());
                array_unshift($types, $contentdesc);
                // Context data for the template.
                $addcontenttype = new stdClass();
                // Content type name.
                $addcontenttype->name = $type;
                // Content type editor base URL.
                $tool['link']->param('plugin', $type);
                $addcontenttype->baseurl = $tool['link']->out();
                // Different types of the content type.
                $addcontenttype->types = $types;
                $addoptions[] = $addcontenttype;
            }
        }

        $tool['contenttypes'] = $addoptions;
    }
}
