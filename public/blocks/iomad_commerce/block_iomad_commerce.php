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
 * IOMAD eCommerce block
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * IOMAD eCommerce block class definition.
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_commerce extends block_base {

    /**
     * Initialisation function.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('buycourses', 'block_iomad_commerce');
    }

    /**
     * Do we hide the header?
     *
     * @return bool
     */
    public function hide_header() {
        return false;
    }

    /**
     * Get the block content
     *
     * @return html
     */
    public function get_content() {
        global $CFG, $USER, $DB;

        // Hide the shop content if the user's company doesn't support ecommerce
        // Always show it if the user is a siteadmin.
        $companyid = local_iomad\iomad::get_my_companyid(context_system::instance(), false);
        $ecommerce = $DB->get_field_sql("SELECT ecommerce
                                         FROM {company} c
                                         WHERE c.id = :companyid",
                                         ['companyid' => $companyid]);

        // Is eCommerce enabled for this tenant?
        if (!is_siteadmin() && !$ecommerce && !$CFG->commerce_admin_enableall) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        // Initial set up.
        $this->content = (object) [];
        $this->content->footer = '';

        // Get the currency type.
        $fatype = "fa-" . strtolower($CFG->commerce_admin_currency);

        if (!isloggedin() || isguestuser()) {
            if (!block_iomad_commerce\helper::is_commerce_configured()) {
                return null;
            }
            $this->content->text = html_writer::start_tag('p');
            $this->content->text .= html_writer::tag('a',
                                                     get_string('shop_login_title', 'block_iomad_commerce'),
                                                     ['href' => new moodle_url($CFG->wwwroot . '/login/index.php')]);
            $this->content->text = html_writer::end_tag_tag('p');
        } else if (!empty($CFG->commerce_enable_external)) {
            // Get and store a one time token.
            $token = local_iomad\company_user::generate_token();
            $configname = "commerce_externalshop_url_$companyid";
            if (empty($CFG->$configname)) {
                $configname = "commerce_externalshop_url";
            }
            $link = new moodle_url($CFG->$configname . '/wp-content/plugins/wooiomad/land.php',
                                   ['username' => $USER->username,
                                    'token' => $token]);
            $this->content->text = html_writer::tag('a',
                                                    get_string('gotoshop', 'block_iomad_commerce'),
                                                    ['class' => 'btn btn-secondary',
                                                     'href' => $link]);
        } else {
            // Has this been setup properly?
            if (!block_iomad_commerce\helper::is_commerce_configured()) {
                $link = new moodle_url('/admin/settings.php', ['section' => 'blocksettingiomad_commerce']);
                $this->content->text = html_writer::tag('div',
                                                        get_string('notconfigured', 'block_iomad_commerce', $link->out()),
                                                        ['class' => 'alert alert-danger']);
            } else {
                // Display the links.
                $shoplink = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/shop.php');
                $this->content->text = html_writer::start_tag('p');
                $this->content->text .= html_writer::tag('span', '', ['class' => "fa $fatype"]);
                $this->content->text .= "&nbsp" .
                                        html_writer::tag('a',
                                                         get_string('shop_title', 'block_iomad_commerce'),
                                                         ['href' => $shoplink]);
                $this->content->text .= html_writer::end_tag('p');

                // Display any basket informtation.
                $this->content->text .= block_iomad_commerce\helper::get_basket_info();
            }
        }

        return $this->content;
    }

    /**
     * Does this block have any configuration?
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}
