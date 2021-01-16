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
 * Strings for component 'tool_langimport', language 'en', branch 'MOODLE_22_STABLE'
 *
 * @package    tool
 * @subpackage langimport
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['downloadnotavailable'] = 'Unable to connect to the download server. It is not possible to install or update the language packs automatically. Please download the appropriate ZIP file(s) from <a href="{$a->src}">{$a->src}</a> and unzip them manually to your data directory <code>{$a->dest}</code>';
$string['install'] = 'Install selected language pack(s)';
$string['installedlangs'] = 'Installed language packs';
$string['langimport'] = 'Language import utility';
$string['langimportdisabled'] = 'Language import feature has been disabled. You have to update your language packs manually at the file-system level. Do not forget to purge string caches after you do so.';
$string['langpackinstalled'] = 'Language pack \'{$a}\' was successfully installed';
$string['langpackinstalledevent'] = 'Language pack installed';
$string['langpackremoved'] = 'Language pack \'{$a}\' was uninstalled';
$string['langpacknotremoved'] = 'An error has occurred; language pack \'{$a}\' is not completely uninstalled. Please check file permissions.';
$string['langpackremovedevent'] = 'Language pack uninstalled';
$string['langpackupdateskipped'] = 'Update of \'{$a}\' language pack skipped';
$string['langpackuptodate'] = 'Language pack \'{$a}\' is up-to-date';
$string['langpackupdated'] = 'Language pack \'{$a}\' was successfully updated';
$string['langpackupdatedevent'] = 'Language pack updated';
$string['langupdatecomplete'] = 'Language pack update completed';
$string['missingcfglangotherroot'] = 'Missing configuration value $CFG->langotherroot';
$string['missinglangparent'] = 'Missing parent language <em>{$a->parent}</em> of <em>{$a->lang}</em>.';
$string['noenglishuninstall'] = 'The English language pack cannot be uninstalled.';
$string['nolangupdateneeded'] = 'All your language packs are up to date, no update is needed';
$string['pluginname'] = 'Language packs';
$string['purgestringcaches'] = 'Purge string caches';
$string['selectlangs'] = 'Select languages to uninstall';
$string['uninstall'] = 'Uninstall selected language pack(s)';
$string['uninstallconfirm'] = 'You are about to completely uninstall these language packs: <strong>{$a}</strong>. Are you sure?';
$string['updatelangs'] = 'Update all installed language packs';
$string['privacy:metadata'] = 'The Language packs plugin does not store any personal data.';
