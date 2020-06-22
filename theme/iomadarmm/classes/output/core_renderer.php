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

namespace theme_iomadarmm\output;

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
use action_menu_filler;
use action_menu_link_secondary;
use help_icon;
use single_button;
use paging_bar;
use context_course;
use pix_icon;
use context_system;
use core_text;

use htm_slider;
use htm_slide;
use htm_quicklinks;
use htm_quicklink_item;

require_once($CFG->dirroot . '/theme/iomadarmm/classes/slider.php');
require_once($CFG->dirroot . '/theme/iomadarmm/classes/quicklinks.php');
require_once($CFG->dirroot.'/local/iomad/lib/user.php');
require_once($CFG->dirroot.'/local/iomad/lib/iomad.php');


defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_iomadboost
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \core_renderer {

    /** @var custom_menu_item language The language menu if created */
    protected $language = null;

    /**
     * Outputs the opening section of a box.
     *
     * @param string $classes A space-separated list of CSS classes
     * @param string $id An optional ID
     * @param array $attributes An array of other attributes to give the box.
     * @return string the HTML to output.
     */
    public function box_start($classes = 'generalbox', $id = null, $attributes = array()) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }
        return parent::box_start($classes . ' p-y-1', $id, $attributes);
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        global $PAGE;
        

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-xs-12 p-a-1');
        $html .= html_writer::start_div('card m-b-0');
        $html .= html_writer::start_div('card-block');
        $html .= html_writer::div($this->context_header_settings_menu(), 'pull-xs-right context-header-settings-menu');
        $html .= html_writer::start_div('pull-xs-left');
        $html .= $this->context_header();
        $html .= html_writer::end_div();
        $pageheadingbutton = $this->page_heading_button();
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix w-100 pull-xs-left', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button pull-xs-right');
            $html .= html_writer::end_div();
        } else if ($pageheadingbutton) {
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button nonavbar pull-xs-right');
        }
        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * The standard tags that should be included in the <head> tag
     * including a meta description for the front page
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $SITE, $PAGE, $DB;

        // Inject additional 'live' css
        $css = '';

        // Get company colours
        $companyid = \iomad::is_company_user();
        if ($companyid) {
            $company = $DB->get_record('company', array('id' => $companyid), '*', MUST_EXIST);
            $linkcolor = $company->linkcolor;
            if ($linkcolor) {
                $css .= 'a {color: ' . $linkcolor . '} ';
            }
            $headingcolor = $company->headingcolor;
            if ($headingcolor) {
                $css .= '.navbar {background-color: ' . $headingcolor . '} ';
            }
            $maincolor = $company->maincolor;
            if ($maincolor) {
                $css .= 'body, #nav-drawer {background-color: ' . $maincolor . '} ';
            }

            $css .= $company->customcss;
        }

        $output = parent::standard_head_html();
        if ($PAGE->pagelayout == 'frontpage') {
            $summary = s(strip_tags(format_text($SITE->summary, FORMAT_HTML)));
            if (!empty($summary)) {
                $output .= "<meta name=\"description\" content=\"$summary\" />\n";
            }
        }

        if ($css) {
            $output .= '<style>' . $css . '</style>';
        }

        return $output;
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        return $this->render_from_template('core/navbar', $this->page->navbar);
    }

    /**
     * We don't like these...
     *
     */
    public function edit_button(moodle_url $url) {
        return '';
    }

    /**
     * Override to inject the logo.
     *
     * @param array $headerinfo The header info.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {
        global $SITE;

        // get appropriate logo
        if (!$src = $this->get_iomad_logo(null, 150)) {
            $src = $this->get_logo_url(null, 150);
        }

        if ($this->should_display_main_logo($headinglevel)) {
            $sitename = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));
            return html_writer::div(html_writer::empty_tag('img', [
                'src' => $src, 'alt' => $sitename]), 'logo');
        }

        return parent::context_header($headerinfo, $headinglevel);
    }

    /**
     * Get the Iomad logo for the current user
     * @return string logo url or false;
     */
    protected function get_iomad_logo($maxwidth = 100, $maxheight = 100) {
        global $CFG; $DB;

        $fs = get_file_storage();

        $clientlogo = '';
        $companyid = \iomad::is_company_user();
        if ($companyid) {
            $context = \context_system::instance();
            $files = $fs->get_area_files($context->id, 'theme_iomad', 'companylogo', $companyid );
            if ($files) {
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';
                    if ($filename != '.') {
                        $clientlogo = $CFG->wwwroot . "/pluginfile.php/{$context->id}/theme_iomad/companylogo/$companyid/$filename";
                        return $clientlogo;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        global $CFG;

        if ($url = $this->get_iomad_logo($maxwidth, $maxheight)) {
            return $url;
        } else {

            // If that didn't work... try the original version
            return parent::get_compact_logo_url($maxwidth, $maxheight);
        }
    }

    /**
     * Whether we should display the main logo.
     *
     * @return bool
     */
    public function should_display_main_logo($headinglevel = 1) {
        global $PAGE;

        // Only render the logo if we're on the front page or login page and the we have a logo.
        $logo = $this->get_logo_url();
        if ($headinglevel == 1 && !empty($logo)) {
            if ($PAGE->pagelayout == 'frontpage' || $PAGE->pagelayout == 'login') {
                return true;
            }
        }

        return false;
    }
    /**
     * Whether we should display the logo in the navbar.
     *
     * We will when there are no main logos, and we have compact logo.
     *
     * @return bool
     */
    public function should_display_navbar_logo() {
        $logo = $this->get_compact_logo_url();
        return !empty($logo) && !$this->should_display_main_logo();
    }

    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG, $DB;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }

        // Deal with company custom menu items.
        if ($companyid = \iomad::is_company_user()) {
            if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
                if (!empty($companyrec->custommenuitems)) {
                    $custommenuitems = $companyrec->custommenuitems;
                }
            }
        }

        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
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
        if ($companyid = \iomad::is_company_user()) {
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

    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG;

        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if (!$menu->has_children() && !$haslangmenu) {
            return '';
        }

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);

            // $CM = $this->get_setting("custom_menu_position");
            $context->hasIomadBoostCM = true; 
            /*if ((int) $CM === 2) {
                $iteminfo = explode(',', $context->text);
            
                $count = 0;
                foreach ($iteminfo as $info) {
                    if($info && $count == 0) {
                        $context->itemtext = $info;
                    } else if ($info && $count == 1){
                        $context->itemicon = trim($info);
                    }
                    $count++;
                }
                $context->hasIomadBoostCM = false;
            }*/

            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
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
        global $CFG;

        $custommenuitems = false;
        // Deal with company custom menu items.
        if ($companyid = \iomad::is_company_user()) {
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

    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $data = $tabtree->export_for_template($this);
        return $this->render_from_template('core/tabtree', $data);
    }

    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tab) {
        throw new coding_exception('Tab objects should not be directly rendered.');
    }

    /**
     * Prints a nice side block with an optional header.
     *
     * @param block_contents $bc HTML for the content
     * @param string $region the region the block is appearing in.
     * @return string the HTML to be output.
     */
    public function block(block_contents $bc, $region) {
        $bc = clone($bc); // Avoid messing up the object passed in.
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }

        $id = !empty($bc->attributes['id']) ? $bc->attributes['id'] : uniqid('block-');
        $context = new stdClass();
        $context->skipid = $bc->skipid;
        $context->blockinstanceid = $bc->blockinstanceid;
        $context->dockable = $bc->dockable;
        $context->id = $id;
        $context->hidden = $bc->collapsible == block_contents::HIDDEN;
        $context->skiptitle = strip_tags($bc->title);
        $context->showskiplink = !empty($context->skiptitle);
        $context->arialabel = $bc->arialabel;
        $context->ariarole = !empty($bc->attributes['role']) ? $bc->attributes['role'] : 'complementary';
        $context->type = $bc->attributes['data-block'];
        $context->title = $bc->title;
        $context->content = $bc->content;
        $context->annotation = $bc->annotation;
        $context->footer = $bc->footer;
        $context->hascontrols = !empty($bc->controls);
        if ($context->hascontrols) {
            $context->controls = $this->block_controls($bc->controls, $id);
        }

        return $this->render_from_template('core/block', $context);
    }

    /**
     * Returns the CSS classes to apply to the body tag.
     *
     * @since Moodle 2.5.1 2.6
     * @param array $additionalclasses Any additional classes to apply.
     * @return string
     */
    public function body_css_classes(array $additionalclasses = array()) {



        return $this->page->bodyclasses . ' ' . implode(' ', $additionalclasses);
    }

    /**
     * Renders preferences groups.
     *
     * @param  preferences_groups $renderable The renderable
     * @return string The output.
     */
    public function render_preferences_groups(preferences_groups $renderable) {
        return $this->render_from_template('core/preferences_groups', $renderable);
    }

    /**
     * Renders an action menu component.
     *
     * @param action_menu $menu
     * @return string HTML
     */
    public function render_action_menu(action_menu $menu) {

        // We don't want the class icon there!
        foreach ($menu->get_secondary_actions() as $action) {
            if ($action instanceof \action_menu_link && $action->has_class('icon')) {
                $action->attributes['class'] = preg_replace('/(^|\s+)icon(\s+|$)/i', '', $action->attributes['class']);
            }
        }

        if ($menu->is_empty()) {
            return '';
        }
        $context = $menu->export_for_template($this);

        return $this->render_from_template('core/action_menu', $context);
    }

    /**
     * Implementation of user image rendering.
     *
     * @param help_icon $helpicon A help icon instance
     * @return string HTML fragment
     */
    protected function render_help_icon(help_icon $helpicon) {
        $context = $helpicon->export_for_template($this);
        return $this->render_from_template('core/help_icon', $context);
    }

    /**
     * Renders a single button widget.
     *
     * This will return HTML to display a form containing a single button.
     *
     * @param single_button $button
     * @return string HTML fragment
     */
    protected function render_single_button(single_button $button) {
        return $this->render_from_template('core/single_button', $button->export_for_template($this));
    }

    /**
     * Renders a paging bar.
     *
     * @param paging_bar $pagingbar The object.
     * @return string HTML
     */
    protected function render_paging_bar(paging_bar $pagingbar) {
        // Any more than 10 is not usable and causes wierd wrapping of the pagination in this theme.
        $pagingbar->maxdisplay = 10;
        return $this->render_from_template('core/paging_bar', $pagingbar->export_for_template($this));
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $SITE;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);
        $context->loginlogo = $this->get_setting_img('logo_login');

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param mform $form
     * @return string
     */
    public function render_login_signup_form($form) {
        global $SITE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('core/signup_form_layout', $context);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the course administration, only on the course main page.
     *
     * @return string
     */
    public function context_header_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();

        $items = $this->page->navbar->get_items();
        $currentnode = end($items);

        $showcoursemenu = false;
        $showfrontpagemenu = false;
        $showusermenu = false;

        // We are on the course home page.
        if (($context->contextlevel == CONTEXT_COURSE) &&
                !empty($currentnode) &&
                ($currentnode->type == navigation_node::TYPE_COURSE || $currentnode->type == navigation_node::TYPE_SECTION)) {
            $showcoursemenu = true;
        }

        $courseformat = course_get_format($this->page->course);
        // This is a single activity course format, always show the course menu on the activity main page.
        if ($context->contextlevel == CONTEXT_MODULE &&
                !$courseformat->has_view_page()) {

            $this->page->navigation->initialise();
            $activenode = $this->page->navigation->find_active_node();
            // If the settings menu has been forced then show the menu.
            if ($this->page->is_settings_menu_forced()) {
                $showcoursemenu = true;
            } else if (!empty($activenode) && ($activenode->type == navigation_node::TYPE_ACTIVITY ||
                    $activenode->type == navigation_node::TYPE_RESOURCE)) {

                // We only want to show the menu on the first page of the activity. This means
                // the breadcrumb has no additional nodes.
                if ($currentnode && ($currentnode->key == $activenode->key && $currentnode->type == $activenode->type)) {
                    $showcoursemenu = true;
                }
            }
        }

        // This is the site front page.
        if ($context->contextlevel == CONTEXT_COURSE &&
                !empty($currentnode) &&
                $currentnode->key === 'home') {
            $showfrontpagemenu = true;
        }

        // This is the user profile page.
        if ($context->contextlevel == CONTEXT_USER &&
                !empty($currentnode) &&
                ($currentnode->key === 'myprofile')) {
            $showusermenu = true;
        }


        if ($showfrontpagemenu) {
            $settingsnode = $this->page->settingsnav->find('frontpage', navigation_node::TYPE_SETTING);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showcoursemenu) {
            $settingsnode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showusermenu) {
            // Get the course admin node from the settings navigation.
            $settingsnode = $this->page->settingsnav->find('useraccount', navigation_node::TYPE_CONTAINER);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $this->build_action_menu_from_navigation($menu, $settingsnode);
            }
        }

        return $this->render($menu);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the most specific thing from the settings block. E.g. Module administration.
     *
     * @return string
     */
    public function region_main_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();

        if ($context->contextlevel == CONTEXT_MODULE) {

            $this->page->navigation->initialise();
            $node = $this->page->navigation->find_active_node();
            $buildmenu = false;
            // If the settings menu has been forced then show the menu.
            if ($this->page->is_settings_menu_forced()) {
                $buildmenu = true;
            } else if (!empty($node) && ($node->type == navigation_node::TYPE_ACTIVITY ||
                    $node->type == navigation_node::TYPE_RESOURCE)) {

                $items = $this->page->navbar->get_items();
                $navbarnode = end($items);
                // We only want to show the menu on the first page of the activity. This means
                // the breadcrumb has no additional nodes.
                if ($navbarnode && ($navbarnode->key === $node->key && $navbarnode->type == $node->type)) {
                    $buildmenu = true;
                }
            }
            if ($buildmenu) {
                // Get the course admin node from the settings navigation.
                $node = $this->page->settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }

        } else if ($context->contextlevel == CONTEXT_COURSECAT) {
            // For course category context, show category settings menu, if we're on the course category page.
            if ($this->page->pagetype === 'course-index-category') {
                $node = $this->page->settingsnav->find('categorysettings', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }

        } else {
            $items = $this->page->navbar->get_items();
            $navbarnode = end($items);

            if ($navbarnode && ($navbarnode->key === 'participants')) {
                $node = $this->page->settingsnav->find('users', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }

            }
        }
        return $this->render($menu);
    }

    /**
     * Take a node in the nav tree and make an action menu out of it.
     * The links are injected in the action menu.
     *
     * @param action_menu $menu
     * @param navigation_node $node
     * @param boolean $indent
     * @param boolean $onlytopleafnodes
     * @return boolean nodesskipped - True if nodes were skipped in building the menu
     */
    protected function build_action_menu_from_navigation(action_menu $menu,
                                                       navigation_node $node,
                                                       $indent = false,
                                                       $onlytopleafnodes = false) {
        $skipped = false;
        // Build an action menu based on the visible nodes from this navigation tree.
        foreach ($node->children as $menuitem) {
            if ($menuitem->display) {
                if ($onlytopleafnodes && $menuitem->children->count()) {
                    $skipped = true;
                    continue;
                }
                if ($menuitem->action) {
                    if ($menuitem->action instanceof action_link) {
                        $link = $menuitem->action;
                        // Give preference to setting icon over action icon.
                        if (!empty($menuitem->icon)) {
                            $link->icon = $menuitem->icon;
                        }
                    } else {
                        $link = new action_link($menuitem->action, $menuitem->text, null, null, $menuitem->icon);
                    }
                } else {
                    if ($onlytopleafnodes) {
                        $skipped = true;
                        continue;
                    }
                    $link = new action_link(new moodle_url('#'), $menuitem->text, null, ['disabled' => true], $menuitem->icon);
                }
                if ($indent) {
                    $link->add_class('m-l-1');
                }
                if (!empty($menuitem->classes)) {
                    $link->add_class(implode(" ", $menuitem->classes));
                }

                $menu->add_secondary_action($link);
                $skipped = $skipped || $this->build_action_menu_from_navigation($menu, $menuitem, true);
            }
        }
        return $skipped;
    }

    /**
     * Secure login info.
     *
     * @return string
     */
    public function secure_login_info() {
        return $this->login_info(false);
    }

    /**
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null) {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();
        // If not logged in, show the typical not-logged-in string.
        if (!isloggedin()) {
            if (!$loginpage) {
                $returnstr = "<a href=\"$loginurl\">" . get_string('login') . '</a>';
            }
            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );

        }

        // If logged in as a guest user, show a string to that effect.
        if (isguestuser()) {
            if (!$loginpage && $withlinks) {
                $returnstr .= "<a href=\"$loginurl\">".get_string('login').'</a>';
            }

            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );
        }

        // Get some navigation opts.
        $opts = user_get_user_navigation_info($user, $this->page);

        $avatarclasses = "avatars";
        $avatarcontents = html_writer::span($opts->metadata['useravatar'], 'avatar current');
        $usertextcontents = $opts->metadata['userfullname'];

        // Other user.
        if (!empty($opts->metadata['asotheruser'])) {
            $avatarcontents .= html_writer::span(
                $opts->metadata['realuseravatar'],
                'avatar realuser'
            );
            $usertextcontents = $opts->metadata['realuserfullname'];
            $usertextcontents .= html_writer::tag(
                'span',
                get_string(
                    'loggedinas',
                    'moodle',
                    html_writer::span(
                        $opts->metadata['userfullname'],
                        'value'
                    )
                ),
                array('class' => 'meta viewingas')
            );
        }

        // Role.
        if (!empty($opts->metadata['asotherrole'])) {
            $role = core_text::strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['rolename'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['rolename'],
                'meta role role-' . $role
            );
        }

        // User login failures.
        if (!empty($opts->metadata['userloginfail'])) {
            $usertextcontents .= html_writer::span(
                $opts->metadata['userloginfail'],
                'meta loginfailures'
            );
        }

        // MNet.
        if (!empty($opts->metadata['asmnetuser'])) {
            $mnet = strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['mnetidprovidername'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['mnetidprovidername'],
                'meta mnet mnet-' . $mnet
            );
        }

        $returnstr .= html_writer::span(
            html_writer::span($usertextcontents, 'usertext') .
            html_writer::span($avatarcontents, $avatarclasses),
            'userbutton'
        );

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->set_menu_trigger(
            $returnstr
        );
        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        if ($withlinks) {
            $navitemcount = count($opts->navitems);
            $idx = 0;
            foreach ($opts->navitems as $key => $value) {

                switch ($value->itemtype) {
                    case 'divider':
                        // If the nav item is a divider, add one and skip link processing.
                        $am->add($divider);
                        break;

                    case 'invalid':
                        // Silently skip invalid entries (should we post a notification?).
                        break;

                    case 'link':
                        // Process this as a link item.
                        $pix = null;
                        if (isset($value->pix) && !empty($value->pix)) {
                            $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                        } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                            $value->title = html_writer::img(
                                $value->imgsrc,
                                $value->title,
                                array('class' => 'iconsmall')
                            ) . $value->title;
                        }

                        $al = new action_menu_link_secondary(
                            $value->url,
                            $pix,
                            $value->title,
                            array('class' => 'icon')
                        );
                        if (!empty($value->titleidentifier)) {
                            $al->attributes['data-title'] = $value->titleidentifier;
                        }
                        $am->add($al);
                        break;
                }

                $idx++;

                // Add dividers after the first item and before the last item.
                if ($idx == 1 || $idx == $navitemcount - 1) {
                    $am->add($divider);
                }
            }
        }

        return html_writer::div(
            $this->render($am),
            $usermenuclasses
        );
    }

    /**
     * Returns a search box.
     *
     * @param  string $id     The search box wrapper div id, defaults to an autogenerated one.
     * @return string         HTML with the search form hidden by default.
     */
    public function search_box($id = false) {
        global $CFG;

        // Accessing $CFG directly as using \core_search::is_global_search_enabled would
        // result in an extra included file for each site, even the ones where global search
        // is disabled.
        if (empty($CFG->enableglobalsearch) || !has_capability('moodle/search:query', context_system::instance())) {
            return '';
        }

        if ($id == false) {
            $id = uniqid();
        } else {
            // Needs to be cleaned, we use it for the input id.
            $id = clean_param($id, PARAM_ALPHANUMEXT);
        }


        $context = new stdClass();
        $context->id = $id;
        $context->searchurl = $CFG->wwwroot . '/search/index.php';
        $context->labeltext = get_string('enteryoursearchquery', 'search');
        $context->inputplaceholder = get_string('search', 'search');

        return $this->render_from_template('theme_iomadarmm/header-search', $context);
    }


    /*****************************************/
    /* HTM FUNCTIONS */
    /*****************************************/

    /**
     * Get toggle status of a toggle setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return boolean
     */
    public function get_toggle_status($setting) {
        GLOBAL $PAGE;
        $togglestatus = $PAGE->theme->settings->$setting;
        if ($togglestatus == 1 || $togglestatus == 2 && !isloggedin() || $togglestatus == 3 && isloggedin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get value of a non image setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return string
     */
    public function get_setting($setting) {
        GLOBAL $PAGE;
        $value = $PAGE->theme->settings->$setting;
        return $value;
    }

    /**
     * Get url location of a stored file setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return string
     */
    public function get_setting_img($setting) {
        GLOBAL $PAGE;
        $value = $PAGE->theme->setting_file_url($setting, $setting);
        return $value;
    }

    /**
     * htm_fp_slideshow
     * Renders the content for the frontpage slideshow to be passed into the mustache tmeplate
     * 
     * @return string
     */
    public function htm_fp_slideshow() {
        $name = 'frontpage';

        $slideshow = new htm_slider($name);
        $slideshow->add_status($this->get_toggle_status("{$name}_slideshow_toggle"));

        switch ($this->get_setting("{$name}_slideshow_transition")) {
            case 'fade':
                $slideshow->add_setting('transition', 'slide carousel-fade');
                break;
            case 'horizontal':
                $slideshow->add_setting('transition', 'slide');
        }
        
        $slideshow->add_setting('controls', $this->get_setting("{$name}_slideshow_controls"));
        $slideshow->add_setting('pager', $this->get_setting("{$name}_slideshow_pager"));

        $count = $this->get_setting("{$name}_slideshow_count");
        for ($i = 1; $i <= $count; $i++) {
            $data = new stdClass();
            $data->title = $this->get_setting("{$name}_slideshow_{$i}_title");
            $data->summary = $this->get_setting("{$name}_slideshow_{$i}_summary");

            
            $data->showbutton = false;
           
            if (!empty($this->get_setting("{$name}_slideshow_{$i}_button_url"))) {
                $data->buttonUrl = $this->get_setting("{$name}_slideshow_{$i}_button_url");
                $data->buttonText = $this->get_setting("{$name}_slideshow_{$i}_link_text");
                $data->showbutton = true;
            }+
            
            $data->hasimg = false;
            if (!empty($this->get_setting_img("{$name}_slideshow_{$i}_image"))) {
                $data->hasimg = true;
                $data->image = $this->get_setting_img("{$name}_slideshow_{$i}_image");
            }


            if ($data->hasimg) {
                $data->visible = true;
            }
            
            
            $slide = new htm_slide($data, $i);

            $slideshow->add_slide($slide);
        }
        if ($slideshow->count < 2) {
            $slideshow->add_setting('controls', 0);
            $slideshow->add_setting('pager', 0);
        }
        return $slideshow;
    }


    public function htm_display_fpc() {
        $fpc = new stdClass();
        
        $fpc->status = $this->get_toggle_status("frontpage_content_toggle");
        if (!empty($this->get_setting('frontpage_content_title'))) {
            $fpc->title = $this->get_setting('frontpage_content_title');
        }
        $fpc->text = $this->get_setting('frontpage_content_text');

        return $fpc;
    }

    public function htm_display_quicklinks($name) {
        $quicklinks = new htm_quicklinks($name);
        $quicklinks->add_status($this->get_toggle_status("{$name}_quicklink_toggle"));

        $quicklinks->heading = $this->get_setting("fp_ql_section_header_text");

        $count = $this->get_setting("{$name}_quicklink_count");
        for ($i = 1; $i <= $count; $i++) {
            $data = new stdClass();
            
            $data->title = $this->get_setting("{$name}_quicklink_{$i}_title");
            $data->titleSmall = $this->get_setting("{$name}_quicklink_{$i}_title_small");
            $data->text = $this->get_setting("{$name}_quicklink_{$i}_text");
            

            $data->hasimg = false;
            if (!empty($this->get_setting_img("{$name}_quicklink_{$i}_image"))) {
                $data->hasimg = true;
                $data->image = $this->get_setting_img("{$name}_quicklink_{$i}_image");
            }

            if (!empty($this->get_setting("{$name}_quicklink_{$i}_url"))) {
                $data->url = $this->get_setting("{$name}_quicklink_{$i}_url");
                $data->linkText = $this->get_setting( "{$name}_quicklink_{$i}_link_text" );
            }

            $data->display = false;
            if (!empty($data->title) || !empty($data->text) || $data->hasimg) {
                $data->display = true;
            }

            $item = new htm_quicklink_item($data, $i);

            $quicklinks->add_item($item);
        }
        return $quicklinks;
    }

    public function htm_get_sm_urls() {
        $data = new stdClass();
        $data->profiles = [];
        $fb = new stdClass();
        $fb->url = $this->get_setting("facebook_url");
        $fb->icon = 'facebook';
        
        $twitter = new stdClass();
        $twitter->url = $this->get_setting("twitter_url");
        $twitter->icon = 'twitter';

        $linkedin = new stdClass();
        $linkedin->url = $this->get_setting( 'linkedin_url' );
        $linkedin->icon = 'linkedin';

        $instagram = new stdClass();
        $instagram->url = $this->get_setting( "instagram_url" );
        $instagram->icon = 'instagram';

        $yt = new stdClass();
        $yt->url = $this->get_setting("youtube_url");
        $yt->icon = 'youtube';

        if (!empty($fb)) {
            array_push($data->profiles, $fb);
        }
        
        if (!empty($twitter)) {
            array_push($data->profiles, $twitter);
        }

        if (!empty($yt)) {
            array_push($data->profiles, $yt);
        }

        if ( ! empty( $instagram ) ) {
            array_push( $data->profiles, $instagram );
        }

        if ( !empty( $linkedin ) ) {
            array_push( $data->profiles, $linkedin );
        }

        return $data;
    }

    public function htm_display_footer() {
        $data = new stdClass();

        $data->logoTop = $this->get_setting_img( 'logo_footer' );
        $data->logoBottom = $this->get_setting_img( 'logo_footer_bottom' );

        $data->social_title = $this->get_setting( 'footer_social_title' );

        $data->col_1_title = $this->get_setting( 'footer_col_1_title' );
        $data->col_1_text = $this->get_setting( 'footer_col_1_text' );

        $data->col_2_title = $this->get_setting( 'footer_col_2_title' );
        $data->col_2_text = $this->get_setting( 'footer_col_2_text' );

        $data->col_3_title = $this->get_setting( 'footer_col_3_title' );
        $data->col_3_text = $this->get_setting( 'footer_col_3_text' );

        $data->col_4_title = $this->get_setting( 'footer_col_4_title' );
        $data->col_4_text = $this->get_setting( 'footer_col_4_text' );

        $data->bottom_text = $this->get_setting("footer_bottom_text");
        $data->footnote = $this->get_setting("footer_footnote");

        return $data;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function htm_full_header() {
        global $PAGE;
        global $OUTPUT;


        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'page-header'));

            // Page header main content
            $html .= html_writer::start_tag( 'div', array( 'class' => 'page-header__main' ) );
                $html .= html_writer::start_div('overlay');
                $html .= html_writer::end_div();
                $html .= html_writer::start_tag( 'div', array( 'class' => 'page-header__main__content' ) );
                    $html .= html_writer::start_div('container');
                        $html .= $this->context_header();
                    $html .= html_writer::end_div();
                $html .= html_writer::end_tag( 'div' );
            $html .= html_writer::end_tag( 'div' );

             // Page header breadcrumbs
             $html .= html_writer::start_tag( 'div', array( 'class' => 'page-header__breadcrumbs' ) );
             $html .= html_writer::start_div( 'container' );
                 $html .= html_writer::div($this->context_header_settings_menu(), 'pull-xs-right context-header-settings-menu');
                 $pageheadingbutton = $this->page_heading_button();
                 if (empty($PAGE->layout_options['nonavbar'])) {
                     $html .= html_writer::start_div('clearfix w-100 pull-xs-left', array('id' => 'page-navbar'));
                     $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
                     $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button pull-xs-right');
                     $html .= html_writer::end_div();
                 } else if ($pageheadingbutton) {
                     $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button nonavbar pull-xs-right');
                 }
                 $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
             $html .= html_writer::end_div();
         $html .= html_writer::end_tag('div');
            
        $html .= html_writer::end_tag('header');
        return $html;
    }

    public function add_navdrawer_icons($navitems) {
        foreach ($navitems as $navitem) {
            $navitem->showfaicon = true;
            $navitem->isSubItem = false;
            switch ($navitem->key) {
                case 'home':
                    $navitem->faicon = 'home';
                    break;
                case 'myhome':
                    $navitem->faicon = 'tachometer-alt';
                    break;
                case 'calendar':
                    $navitem->faicon = 'calendar-alt';
                    break;
                case 'privatefiles':
                    $navitem->faicon = 'copy';
                    break;
                case 'mycourses':
                    $navitem->faicon = 'book';
                    break;
                case 'sitesettings':
                    $navitem->faicon = 'cog';
                    break;
                case 'themesettings':
                    $navitem->faicon = 'sliders-h';
                    break;
                case 'coursehome':
                    $navitem->faicon = 'book';
                    break;
                case 'participants':
                    $navitem->faicon = 'users';
                    break;
                case 'badgesview':
                    $navitem->faicon = 'badge';
                    break;
                case 'competencies':
                    $navitem->faicon = 'check-circle';
                    break;
                case 'grades':
                    $navitem->faicon = 'trophy';
                    break;
                case 'addblock':
                    $navitem->faicon = 'plus';
                    break;
                case 'coursesettings':
                    $navitem->faicon = 'wrench';
                    break;
                case (is_numeric($navitem->key)):
                    $navitem->faicon = 'folder';
                    $navitem->isSubItem = true;
                    break;
                default: 
                    $navitem->showfaicon = false;
                    $navitem->isSubItem = false;
            }

            if (empty(trim($navitem->text)) && $navitem->isSubItem === true) {
                $navitem->text = 'Section '.$navitem->key;
            }
            if ($navitem->key == 'home' && $navitem->text == 'Dashboard') {
                $navitem->faicon = 'tachometer-alt';
            }
        }
        return $navitems;
    }


}
