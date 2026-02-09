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
 * Class renderer.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\output;

use block_dash\local\data_source\abstract_data_source;
use block_dash\local\data_source\data_source_interface;

use Mustache_Engine;
/**
 * Class renderer.
 *
 * @package block_dash
 */
class renderer extends \plugin_renderer_base {

    /**
     * @var Mustache_Engine
     */
    private $mustache;

    /**
     * Return an instance of the mustache class without the custom Moodle file loader.
     *
     * This will be used for rendering templates from strings only.
     *
     * @return \Mustache_Engine
     */
    protected function get_mustache() {
        global $CFG;

        if ($this->mustache === null) {
            require_once("{$CFG->libdir}/filelib.php");

            $themename = $this->page->theme->name;
            $themerev = theme_get_revision();

            // Create new localcache directory.
            $cachedir = make_localcache_directory("mustache/$themerev/$themename");

            // Remove old localcache directories.
            $mustachecachedirs = glob("{$CFG->localcachedir}/mustache/*", GLOB_ONLYDIR);
            foreach ($mustachecachedirs as $localcachedir) {
                $cachedrev = [];
                preg_match("/\/mustache\/([0-9]+)$/", $localcachedir, $cachedrev);
                $cachedrev = isset($cachedrev[1]) ? intval($cachedrev[1]) : 0;
                if ($cachedrev > 0 && $cachedrev < $themerev) {
                    fulldelete($localcachedir);
                }
            }

            $loader = new mustache_custom_loader();
            $stringhelper = new \core\output\mustache_string_helper();
            $quotehelper = new \core\output\mustache_quote_helper();
            $jshelper = new \core\output\mustache_javascript_helper($this->page);
            $pixhelper = new \core\output\mustache_pix_helper($this);
            $shortentexthelper = new \core\output\mustache_shorten_text_helper();
            $userdatehelper = new \core\output\mustache_user_date_helper();

            // We only expose the variables that are exposed to JS templates.
            $safeconfig = $this->page->requires->get_config_for_javascript($this->page, $this);

            $helpers = ['config' => $safeconfig,
                'str' => [$stringhelper, 'str'],
                'quote' => [$quotehelper, 'quote'],
                'js' => [$jshelper, 'help'],
                'pix' => [$pixhelper, 'pix'],
                'shortentext' => [$shortentexthelper, 'shorten'],
                'userdate' => [$userdatehelper, 'transform'],
            ];

            if (block_dash_is_totara()) {
                $flexhelper = new \core\output\mustache_flex_icon_helper($this);
                $helpers['flex_icon'] = [$flexhelper, 'flex_icon'];
            }

            $this->mustache = new Mustache_Engine([
                'cache' => $cachedir,
                'escape' => 's',
                'loader' => $loader,
                'helpers' => $helpers,
                'pragmas' => [\Mustache_Engine::PRAGMA_BLOCKS],
                // Don't allow the JavaScript helper to be executed from within another
                // helper. If it's allowed it can be used by users to inject malicious
                // JS into the page.
                'blacklistednestedhelpers' => ['js'],
            ]);

        }

        return $this->mustache;
    }

    /**
     * Renders a template by string with the given context.
     *
     * The provided data needs to be array/stdClass made up of only simple types.
     * Simple types are array,stdClass,bool,int,float,string
     *
     * @since 2.9
     * @param string $templatestring Raw template string
     * @param array|\stdClass $context Context containing data for the template.
     * @return string|boolean
     */
    public function render_from_template($templatestring, $context) {
        return trim($this->get_mustache()->render($templatestring, $context));
    }

    /**
     * Render a data source.
     *
     * @param abstract_data_source $datasource
     * @return bool|string
     * @throws \coding_exception
     */
    public function render_data_source(abstract_data_source $datasource) {
        return $this->render_from_template($datasource->get_layout()->get_mustache_template_name(),
            $datasource->export_for_template($this));
    }

    /**
     * Compatibility for Totara.
     *
     * @param query_debug $debug
     * @return bool|string
     */
    public function render_query_debug(query_debug $debug) {
        return $this->render_from_template('block_dash/query_debug', $debug->export_for_template($this));
    }
}
