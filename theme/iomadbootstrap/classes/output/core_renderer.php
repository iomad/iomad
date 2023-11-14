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
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_iomadbootstrap
 * @copyright 2022 Derick Turner
 * @author    Derick Turner
 * @based on theme_classic by Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_iomadbootstrap\output;

use coding_exception;
use html_writer;
use tabobject;
use tabtree;
use custom_menu_item;
use custom_menu;
use block_contents;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use preferences_groups;
use action_menu;
use help_icon;
use single_button;
use paging_bar;
use context_course;
use pix_icon;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/iomad/lib/user.php');
require_once($CFG->dirroot.'/local/iomad/lib/iomad.php');

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * Note: This class is required to avoid inheriting Boost's core_renderer,
 *       which removes the edit button required by IOMAD Bootstrap.
 *
 * @package    theme_iomadbootstrap
 * @copyright  2018 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer {

    /**
     * The standard tags that should be included in the <head> tag
     * including a meta description for the front page
     * We cheekily add un-cached CSS for Iomad here
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $SITE, $PAGE, $DB;

        // Inject additional 'live' css
        $css = '';

        // Get company colours
        $companyid = \iomad::get_my_companyid(\context_system::instance(), false);
        if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
            $company = $DB->get_record('company', array('id' => $companyid), '*', MUST_EXIST);
            $linkcolor = $company->linkcolor;
            if ($linkcolor) {
                $css .= 'a {color: ' . $linkcolor . '} ';
            }
            $headingcolor = $company->headingcolor;
            if ($headingcolor) {
                $css .= '.navbar {background-color: ' . $headingcolor . '!important} ';
            }
            $maincolor = $company->maincolor;
            if ($maincolor) {
                $css .= 'body, #nav-drawer {background-color: ' . $maincolor . '!important} ';
            }

            $css .= $company->customcss;
        }

        $output = parent::standard_head_html();

        if ($css) {
            $output .= '<style>' . $css . '</style>';
        }

        return $output;
    }

    /**
     * We want to show the custom menus as a list of links in the footer on small screens.
     * Just return the menu object exported so we can render it differently.
     */
    public function custom_menu_flat() {
        global $CFG, $DB;
        $custommenuitems = '';

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }

        // Deal with company custom menu items.
        if ($companyid = \iomad::get_my_companyid(\context_system::instance(), false)) {
            if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
                if (!empty($companyrec->custommenuitems)) {
                    $custommenuitems = $companyrec->custommenuitems;
                }
            }
        }

        $custommenu = new custom_menu($custommenuitems, current_language());
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $custommenu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        return $custommenu->export_for_template($this);
    }

    /**
     * This code renders the navbar button to control the display of the custom menu
     * on smaller screens.
     *
     * Do not display the button if the menu is empty.
     *
     * @return string HTML fragment
     */
    public function navbar_button() {
        global $CFG, $DB;

        $custommenuitems = false;
        // Deal with company custom menu items.
        if ($companyid = \iomad::get_my_companyid(\context_system::instance(), false)) {
            if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
                if (!empty($companyrec->custommenuitems)) {
                    $custommenuitems = true;
                }
            }
        }

        if (empty($CFG->custommenuitems) && $this->lang_menu() == '' && empty($custommenuitems)) {
            return '';
        }

        $iconbar = html_writer::tag('span', '', array('class' => 'icon-bar'));
        $button = html_writer::tag('a', $iconbar . "\n" . $iconbar. "\n" . $iconbar, array(
            'class'       => 'btn btn-navbar',
            'data-toggle' => 'collapse',
            'data-target' => '.nav-collapse'
        ));
        return $button;
    }
}
