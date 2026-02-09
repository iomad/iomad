<?php
// This file is part of the space theme for Moodle
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
 * Theme space renderer file.
 *
 * @package    theme_iomadmoon
 * @copyright  2016 onwards Onlinecampus Virtuelle PH
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_iomadmoon\output;

use custom_menu;
use html_writer;
use moodle_url;
use stdClass;
use theme_config;

class html_renderer extends \plugin_renderer_base {

    protected static $instance;
    private $theme;

    public static function get_instance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function page_header() {
        $o = '';

        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }

        if (!empty($this->theme->settings->navbarposition)) {
            if ($this->theme->settings->navbarposition == 'fixed') {
                $fixednavbar = true;
            } else {
                $fixednavbar = false;
            }
        } else {
            $fixednavbar = false;
        }

        $o .= $this->image_header($fixednavbar);
        $o .= $this->navigation_menu($fixednavbar);

        return $o;
    }

    /**
     * Render the top image menu.
     */
    protected function image_header($fixednavbar = false) {
        global $CFG;
        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }
        $settings = $this->theme->settings;
        $template = new \stdClass();

        $template->homeurl = new moodle_url('/');

        if (isset($settings->logoposition) && $settings->logoposition == 'right') {
            $template->logocontainerclass = 'col-sm-3 col-md-3 push-sm-9 push-md-9 logo right';
            $template->headerbgcontainerclass = 'col-sm-9 col-md-9 pull-sm-3 pull-md-3  right background';

            if (isset($settings->headerlayout) && ($settings->headerlayout == 1)) {
                $template->logocontainerclass = 'col-sm-3 col-md-3 push-sm-9 push-md-9 logo right logo fixed';
                $template->headerbgcontainerclass = 'col-sm-12 background';
            }
        } else {
            $template->logocontainerclass = 'col-sm-3 col-md-3 col-lg-2 logo left p-0';
            $template->headerbgcontainerclass = 'col-sm-9 col-md-9 col-lg-10 grid background p-0';

            if (isset($settings->headerlayout) && ($settings->headerlayout == 1)) {
                $template->logocontainerclass = 'col-sm-3 col-md-3 col-lg-2 logo left fixed';
                $template->headerbgcontainerclass = 'col-sm-12 background';
            }
        }

        $images = array('logo', 'logosmall', 'headerbg', 'headerbgsmall');

        foreach ($images as $image) {
            if (!empty($settings->$image)) {
                $template->$image = $this->theme->setting_file_url($image, $image);
            } else {
                if ($CFG->branch >= 33) {
                    $template->$image = $this->image_url($image, 'theme_iomadmoon');
                } else {
                    $template->$image = $this->pix_url($image, 'theme_iomadmoon');
                }
            }
        }

        if ($fixednavbar) {
            $template->fixednavbar = true;
        }

        return $this->render_from_template('theme_iomadmoon/imageheading', $template);
    }

    /**
     * Full top Navbar. Returns Mustache rendered menu.
     */
    protected function navigation_menu($fixednavbar = false) {
        $template = new \stdClass();
        $template->output = $this->output;
        $template->navpositionfixed = $fixednavbar;
        return $this->render_from_template('theme_iomadmoon/navigation', $template);
    }

    /**
     * Render the social icons shown in the page footer.
     */
    public function iomadmoon_socialicons() {
        global $CFG;
        $content = '';

        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }

        $template = new stdClass();
        $template->icons = array();

        $socialicons = array('instagramlink', 'twitterlink', 'facebooklink', 'youtubelink');

        if ($CFG->branch >= 33) {
            $imageurlfunc = 'image_url';
        } else {
            $imageurlfunc = 'pix_url';
        }
        foreach ($socialicons as $si) {
            if (!empty($this->theme->settings->$si)) {
                $icon = new stdClass();
                $icon->url = $this->theme->settings->$si;
                $icon->name = str_replace('link', '', $si);
                $icon->image = $this->output->$imageurlfunc($icon->name, 'theme');
                $template->icons[] = $icon;
            }
        }
        return $this->render_from_template('theme_iomadmoon/socialicons', $template);
    }

    /**
     * Render the language menu.
     */
    public function languagemenu() {

        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }

        $haslangmenu = $this->output->lang_menu() != '';
        $langmenu = new stdClass();

        if ($haslangmenu) {
            $langs = get_string_manager()->get_list_of_translations();
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $langmenu->currentlang = $langs[$currentlang];
            } else {
                $langmenu->currentlang = $strlang;
            }
            $langmenu->languages = array();
            foreach ($langs as $type => $name) {
                $thislang = new stdClass();
                $thislang->langname = $name;
                $thislang->langurl = new moodle_url($this->page->url, array('lang' => $type));
                $langmenu->languages[] = $thislang;
            }
            return $this->render_from_template('theme_iomadmoon/language', $langmenu);
        }
    }

    /**
     * Render the text shown in the page footer.
     */
    public function footer() {

        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }

        $template = new stdClass();
        $template->coursefooter = $this->output->course_footer();

        $template->list = array();

        if (isset($this->theme->settings->footertext)) {
            $footertext = $this->theme->settings->footertext;
            $menu = new custom_menu($footertext, current_language());
            foreach ($menu->get_children() as $item) {
                $listitem = new stdClass();
                $listitem->text = $item->get_text();
                $listitem->url = $item->get_url();
                $template->list[] = $listitem;
            }
        }

        $template->socialicons = $this->iomadmoon_socialicons();

        if (!empty($this->theme->settings->footnote)) {
            $template->footnote = $this->theme->settings->footnote;
        }

        $template->logininfo = $this->output->login_info();
        $template->standardfooterhtml = $this->standard_footer_html();

        return $this->render_from_template('theme_iomadmoon/footer', $template);
    }

    /**
     * Find the toplevel category for use in the bodyclasses
     */
    public function toplevel_category() {
        if (empty($this->theme)) {
            $this->theme = theme_config::load('iomadmoon');
        }
        foreach ($this->page->categories as $cat) {
            if ($cat->depth == 1) {
                return 'category-' . $cat->id;
            }
        }
    }
}
