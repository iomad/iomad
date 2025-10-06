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
 * An adhoc task for local Iomad track
 *
 * @package    local_iomad_track
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Robert Tyrone Cullen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_iomad_track\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use context_user;

class fixcertificatetask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fixcertificatetask', 'local_iomad_track');
    }

    /**
     * Run fixcertificatetask
     */
    public function execute() {
        global $DB;
        if ($records = $DB->get_records_sql("SELECT id FROM {local_iomad_track_certs}
                                             WHERE trackid NOT IN 
                                             (SELECT itemid FROM {files}
                                              WHERE component = :component
                                              AND filearea = :filearea
                                              AND filename = :filename)",
                                             ['component' => 'local_iomad_track',
                                              'filearea' => 'issue',
                                              'filename' => '.'])) {
            foreach ($records as $record) {
                $DB->delete_records('local_iomad_track_certs', ['id' => $record->id]);
            }
        }
        // Get all certificate records which are related to the current course id
        if ($certs = $DB->get_records_sql("SELECT f.id as id, f.contextid as contextid, f.component as component, f.filearea as filearea, f.itemid as itemid, f.filepath as filepath, f.filename as filename, lit.userid as userid FROM {files} f
                                           JOIN {local_iomad_track} lit ON (f.itemid = lit.id)
                                           JOIN {user} u ON (lit.userid = u.id)
                                           WHERE f.filearea = :filearea
                                           AND f.component = :component
                                           AND u.deleted = 0",
                                           ['filearea' => 'issue',
                                            'component' => 'local_iomad_track'])) {
            foreach ($certs as $cert) {
                // Create a stdClass with the updated data to update a specific record in the files table so it is accessible after the course is deleted
                $usercontext = context_user::instance($cert->userid);
                $record = (object)[
                    'id' => $cert->id,
                    'contextid' => $usercontext->id,
                    'pathnamehash' => sha1('/'.$usercontext->id.'/'.$cert->component.'/'.$cert->filearea.'/'.$cert->itemid.''.$cert->filepath.''.$cert->filename)
                ];
                $DB->update_record('files', $record);
            }
        }
    }
    
    /**
     * Queues the task.
     *
     */
    public static function queue_task() {
        // Let's set up the adhoc task.
        $task = new \local_iomad_track\task\fixcertificatetask();
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
