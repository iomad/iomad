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
 * WebDAV repository data generator
 *
 * @package    repository_webdav
 * @category   test
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * WebDAV repository data generator class
 *
 * @package    repository_webdav
 * @category   test
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_webdav_generator extends testing_repository_generator {

    /**
     * Fill in record defaults.
     *
     * @param array $record
     * @return array
     */
    protected function prepare_record(array $record) {
        $record = parent::prepare_record($record);
        if (!isset($record['webdav_type'])) {
            $record['webdav_type'] = 0;
        }
        if (!isset($record['webdav_server'])) {
            $record['webdav_server'] = 'webdav.server.local';
        }
        if (!isset($record['webdav_port'])) {
            $record['webdav_port'] = '';
        }
        if (!isset($record['webdav_path'])) {
            $record['webdav_path'] = '/';
        }
        if (!isset($record['webdav_user'])) {
            $record['webdav_user'] = '';
        }
        if (!isset($record['webdav_password'])) {
            $record['webdav_password'] = '';
        }
        if (!isset($record['webdav_auth'])) {
            $record['webdav_auth'] = 'none';
        }
        return $record;
    }


}
