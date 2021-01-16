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

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin for spell checking (Moodle custom replacement for standard TinyMCE
 * plugin, but with same name, which seems a bit unhelpful).
 *
 * @package   tinymce_spellchecker
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tinymce_spellchecker extends editor_tinymce_plugin {
    /** @var array list of buttons defined by this plugin */
    protected $buttons = array('spellchecker');

    protected function update_init_params(array &$params, context $context,
            array $options = null) {
        global $CFG;

        if (!$this->is_legacy_browser()) {
            return;
        }

        // Check some speller is configured.
        $engine = $this->get_config('spellengine', '');
        if (!$engine or $engine === 'GoogleSpell') {
            return;
        }

        // Check at least one language is supported.
        $spelllanguagelist = $this->get_config('spelllanguagelist', '');
        if ($spelllanguagelist !== '') {
            // Prevent the built-in spell checker in Firefox, Safari and other sane browsers.
            unset($params['gecko_spellcheck']);

            if ($row = $this->find_button($params, 'code')) {
                // Add button after 'code'.
                $this->add_button_after($params, $row, 'spellchecker', 'code');
            }

            // Add JS file, which uses default name.
            $this->add_js_plugin($params);
            $params['spellchecker_rpc_url'] = $CFG->wwwroot .
                    '/lib/editor/tinymce/plugins/spellchecker/rpc.php';
            $params['spellchecker_languages'] = $spelllanguagelist;
        }
    }

    protected function is_legacy_browser() {
        // IE8 and IE9 are the only supported browsers that do not have spellchecker.
        if (core_useragent::is_ie() and !core_useragent::check_ie_version(10)) {
            return true;
        }
        // The rest of browsers supports spellchecking or is horribly outdated and we do not care...
        return false;
    }
}
