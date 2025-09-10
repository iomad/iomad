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
 * Backup functions
 * @package    block_iomad_html
 * @subpackage backup-moodle2
 * @author    Derick Turner - based on the standard Moodle HTML block
 * @copyright E-Learn Design - http://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised backup task for the html block
 * (requires encode_content_links in some configdata attrs)
 *
 * TODO: Finish phpdocs
 */
class backup_iomad_html_block_task extends backup_block_task {

    /**
     * define the backup settings
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Define the backup steps
     *
     * @return void
     */
    protected function define_my_steps() {
    }

    /**
     * Get the areas where files could be uploaded
     *
     * @return void
     */
    public function get_fileareas() {
        return ['content'];
    }

    /**
     * Get the attributes which need encoding
     *
     * @return void
     */
    public function get_configdata_encoded_attributes() {
        return ['text']; // We need to encode some attrs in configdata.
    }

    /**
     * Encode any links
     *
     * @param text $content
     * @return void
     */
    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}

