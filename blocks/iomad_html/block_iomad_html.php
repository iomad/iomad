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
 * Form for editing HTML block instances.
 *
 * @package   block_iomad_html
 * @author    Derick Turner - based on the standard Moodle HTML block
 * @copyright E-Learn Design - http://www.e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing HTML block instances.
 *
 * @package   block_iomad_html
 * @author    Derick Turner - based on the standard Moodle HTML block
 * @copyright E-Learn Design - http://www.e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_html extends block_base {

    /**
     * Initialisation function
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_iomad_html');
    }

    /**
     * Check if we have any config
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Where can be add this block
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Specialist settings
     *
     * @return void
     */
    public function specialization() {
        $this->title = isset($this->config->title) ?
                       format_string($this->config->title) :
                       format_string(get_string('newiomad_htmlblock', 'block_iomad_html'));
    }

    /**
     * Check if we can have more than one
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Get the block content
     *
     * @return text
     */
    public function get_content() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        // Do we have a company set?
        if (!empty($this->config->companies)) {
            // Check the user's company against this.
            $companyid = iomad::get_my_companyid(context_system::instance(), false);

            // Is the companyid valid?
            if ($companyid < 1) {
                // No company.
                return;
            }

            // Get the company context.
            $companycontext = \core\context\company::instance($companyid);
            if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
                if (!in_array($companyid, $this->config->companies)) {
                    // We dont have permissions to see all companies and this is not for our company.
                    return;
                }
            }
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // Fancy html allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        if (isset($this->config->text)) {
            // Rewrite url.
            $this->config->text = file_rewrite_pluginfile_urls($this->config->text,
                                                               'pluginfile.php',
                                                               $this->context->id,
                                                               'block_iomad_html',
                                                               'content',
                                                               null);
            // Default to FORMAT_HTML which is what will have been used before the
            // editor was properly implemented for the block.
            $format = FORMAT_HTML;
            // Check to see if the format has been properly set on the config.
            if (isset($this->config->format)) {
                $format = $this->config->format;
            }
            $this->content->text = format_text($this->config->text, $format, $filteropt);
        } else {
            $this->content->text = '';
        }

        unset($filteropt); // Memory footprint.

        return $this->content;
    }


    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->text = file_save_draft_area_files($data->text['itemid'],
                                                   $this->context->id,
                                                   'block_iomad_html',
                                                   'content',
                                                   0,
                                                   ['subdirs' => true],
                                                   $data->text['text']);
        $config->format = $data->text['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Delete
     *
     * @return void
     */
    public function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_iomad_html');
        return true;
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($fromid) {
        $fromcontext = context_block::instance($fromid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_iomad_html', 'content', 0, false)) {
            $draftitemid = 0;
            file_prepare_draft_area($draftitemid, $fromcontext->id, 'block_iomad_html', 'content', 0, ['subdirs' => true]);
            file_save_draft_area_files($draftitemid, $this->context->id, 'block_iomad_html', 'content', 0, ['subdirs' => true]);
        }
        return true;
    }

    /**
     * Is the content trusted?
     *
     * @return void
     */
    public function content_is_trusted() {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING)) {
            return false;
        }
        // Find out if this block is on the profile page.
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // This is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here.
                return true;
            } else {
                // No JS on public personal pages, it would be a big security issue!
                return false;
            }
        }

        return true;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
    }

    /**
     * Add custom html attributes to aid with theming and styling
     *
     * @return void
     */
    public function html_attributes() {
        global $CFG;

        $attributes = parent::html_attributes();

        if (!empty($CFG->block_iomad_html_allowcssclasses)) {
            if (!empty($this->config->classes)) {
                $attributes['class'] .= ' '.$this->config->classes;
            }
        }

        return $attributes;
    }
}
