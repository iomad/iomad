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
 * Restore functions for block_iomad_html
 *
 * @package   block_iomad_html
 * @subpackage backup-moodle2
 * @author    Derick Turner - based on the standard Moodle HTML block
 * @copyright E-Learn Design - http://www.e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised restore task for the html block
 * (requires encode_content_links in some configdata attrs)
 *
 * TODO: Finish phpdocs
 */
class restore_iomad_html_block_task extends restore_block_task {

    /**
     * Define the restore settings
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Define the restore steps
     *
     * @return void
     */
    protected function define_my_steps() {
    }

    /**
     * Get the places where files could be used
     *
     * @return array
     */
    public function get_fileareas() {
        return ['content'];
    }

    /**
     * Get which attributes are encoded
     *
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return ['text']; // We need to encode some attrs in configdata.
    }

    /**
     * Define the contents which need restrored
     *
     * @return array
     */
    public static function define_decode_contents() {

        $contents = [];

        $contents[] = new restore_iomad_html_block_decode_content('block_instances', 'configdata', 'block_instance');

        return $contents;
    }

    /**
     * Define any decode rules
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }
}

/**
 * Specialised restore_decode_content provider that unserializes the configdata
 * field, to serve the configdata->text content to the restore_decode_processor
 * packaging it back to its serialized form after process
 */
class restore_iomad_html_block_decode_content extends restore_decode_content {

    /** @var Temp storage for unserialized configdata */
    protected $configdata;

    /** Get the backup iterator
     *
     * @return array
     */
    protected function get_iterator() {
        global $DB;

        // Build the SQL dynamically here.
        $fieldslist = 't.' . implode(', t.', $this->fields);
        $sql = "SELECT t.id, $fieldslist
                  FROM {" . $this->tablename . "} t
                  JOIN {backup_ids_temp} b ON b.newitemid = t.id
                 WHERE b.backupid = ?
                   AND b.itemname = ?
                   AND t.blockname = 'iomad_html'";
        $params = [$this->restoreid, $this->mapping];
        return ($DB->get_recordset_sql($sql, $params));
    }

    /**
     * Perform any preprocessing
     *
     * @param text $field
     * @return void
     */
    protected function preprocess_field($field) {
        $this->configdata = unserialize(base64_decode($field));
        return isset($this->configdata->text) ? $this->configdata->text : '';
    }

    /**
     * Perform and field post processing
     *
     * @param text $field
     * @return void
     */
    protected function postprocess_field($field) {
        $this->configdata->text = $field;
        return base64_encode(serialize($this->configdata));
    }
}
