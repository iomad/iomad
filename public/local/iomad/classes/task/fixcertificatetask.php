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
 * An adhoc fix certificates task for local iomad
 *
 * @package    local_iomad
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use core\task\manager;
use context_user;

/**
 * An adhoc fix certificates task for local iomad
 *
 * @package    local_iomad
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fixcertificatetask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fixcertificatetask', 'local_iomad');
    }

    /**
     * Run fixcertificatetask
     */
    public function execute() {
        global $DB;

        // Do we have any certificates we need to sort?
        if ($records = $DB->get_records_sql("SELECT id FROM {local_iomad_track_certs}
                                             WHERE trackid NOT IN
                                             (SELECT itemid FROM {files}
                                              WHERE component = :component
                                              AND filearea = :filearea
                                              AND filename = :filename)",
                                             ['component' => 'local_iomad',
                                              'filearea' => 'issue',
                                              'filename' => '.'])) {

            // Process them.
            foreach ($records as $record) {
                $DB->delete_records('local_iomad_track_certs', ['id' => $record->id]);
            }
        }

        // Get all certificate file records.
        if ($certs = $DB->get_records_sql("SELECT f.id,
                                           f.contextid,
                                           f.component,
                                           f.filearea,
                                           f.itemid,
                                           f.filepath,
                                           f.filename,
                                           lit.userid
                                           FROM {files} f
                                           JOIN {local_iomad_track} lit ON (f.itemid = lit.id)
                                           JOIN {user} u ON (lit.userid = u.id)
                                           WHERE f.filearea = :filearea
                                           AND f.component = :component
                                           AND u.deleted = 0",
                                           ['filearea' => 'issue',
                                            'component' => 'local_iomad'])) {

            // Process the certificates.
            foreach ($certs as $cert) {
                // Create an object with the new data to update the files record in the database
                // under the correct (user) context.
                $usercontext = context_user::instance($cert->userid);
                $record = (object) [
                                    'id' => $cert->id,
                                    'contextid' => $usercontext->id,
                                    'pathnamehash' => sha1('/' .
                                                           $usercontext->id .
                                                           '/' .
                                                           $cert->component .
                                                           '/' .
                                                           $cert->filearea .
                                                           '/' .
                                                           $cert->itemid .
                                                           '' .
                                                           $cert->filepath .
                                                           '' .
                                                           $cert->filename),
                                    ];

                // Update the database.
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
        $task = new fixcertificatetask();
        manager::queue_adhoc_task($task, true);
    }
}
