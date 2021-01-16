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
 * Data generator.
 *
 * @package    mod_h5pactivity
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_h5pactivity\local\manager;

defined('MOODLE_INTERNAL') || die();


/**
 * h5pactivity module data generator class.
 *
 * @package    mod_h5pactivity
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_h5pactivity_generator extends testing_module_generator {

    /**
     * Creates new h5pactivity module instance. By default it contains a short
     * text file.
     *
     * @param array|stdClass $record data for module being generated. Requires 'course' key
     *     (an id or the full object). Also can have any fields from add module form.
     * @param null|array $options general options for course module. Since 2.6 it is
     *     possible to omit this argument by merging options into $record
     * @return stdClass record from module-defined table with additional field
     *     cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array $options = null): stdClass {
        global $CFG, $USER;
        // Ensure the record can be modified without affecting calling code.
        $record = (object)(array)$record;

        // Fill in optional values if not specified.
        if (!isset($record->packagefilepath)) {
            $record->packagefilepath = $CFG->dirroot.'/h5p/tests/fixtures/h5ptest.zip';
        }
        if (!isset($record->grade)) {
            $record->grade = 100;
        }
        if (!isset($record->displayoptions)) {
            $factory = new \core_h5p\factory();
            $core = $factory->get_core();
            $config = \core_h5p\helper::decode_display_options($core);
            $record->displayoptions = \core_h5p\helper::get_display_options($core, $config);
        }
        if (!isset($record->enabletracking)) {
            $record->enabletracking = 1;
        }
        if (!isset($record->grademethod)) {
            $record->grademethod = manager::GRADEHIGHESTATTEMPT;
        }
        if (!isset($record->reviewmode)) {
            $record->reviewmode = manager::REVIEWCOMPLETION;
        }

        // The 'packagefile' value corresponds to the draft file area ID. If not specified, create from packagefilepath.
        if (empty($record->packagefile)) {
            if (!isloggedin() || isguestuser()) {
                throw new coding_exception('H5P activity generator requires a current user');
            }
            if (!file_exists($record->packagefilepath)) {
                throw new coding_exception("File {$record->packagefilepath} does not exist");
            }
            $usercontext = context_user::instance($USER->id);

            // Pick a random context id for specified user.
            $record->packagefile = file_get_unused_draft_itemid();

            // Add actual file there.
            $filerecord = ['component' => 'user', 'filearea' => 'draft',
                    'contextid' => $usercontext->id, 'itemid' => $record->packagefile,
                    'filename' => basename($record->packagefilepath), 'filepath' => '/'];
            $fs = get_file_storage();
            $fs->create_file_from_pathname($filerecord, $record->packagefilepath);
        }

        // Do work to actually add the instance.
        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creata a fake attempt
     * @param stdClass $instance object returned from create_instance() call
     * @param stdClass|array $record
     * @return stdClass generated object
     * @throws coding_exception if function is not implemented by module
     */
    public function create_content($instance, $record = []) {
        global $DB, $USER;

        $currenttime = time();
        $cmid = $record['cmid'];
        $userid = $record['userid'] ?? $USER->id;
        $conditions = ['h5pactivityid' => $instance->id, 'userid' => $userid];
        $attemptnum = $DB->count_records('h5pactivity_attempts', $conditions) + 1;
        $attempt = (object)[
                'h5pactivityid' => $instance->id,
                'userid' => $userid,
                'timecreated' => $currenttime,
                'timemodified' => $currenttime,
                'attempt' => $attemptnum,
                'rawscore' => 3,
                'maxscore' => 5,
                'completion' => 1,
                'success' => 1,
                'scaled' => 0.6,
            ];
        $attempt->id = $DB->insert_record('h5pactivity_attempts', $attempt);

        // Create 3 diferent tracking results.
        $result = (object)[
                'attemptid' => $attempt->id,
                'subcontent' => '',
                'timecreated' => $currenttime,
                'interactiontype' => 'compound',
                'description' => 'description for '.$userid,
                'correctpattern' => '',
                'response' => '',
                'additionals' => '{"extensions":{"http:\/\/h5p.org\/x-api\/h5p-local-content-id":'.
                        $cmid.'},"contextExtensions":{}}',
                'rawscore' => 3,
                'maxscore' => 5,
                'completion' => 1,
                'success' => 1,
                'scaled' => 0.6,
            ];
        $DB->insert_record('h5pactivity_attempts_results', $result);

        $result->subcontent = 'bd03477a-90a1-486d-890b-0657d6e80ffd';
        $result->interactiontype = 'compound';
        $result->response = '0[,]5[,]2[,]3';
        $result->additionals = '{"choices":[{"id":"0","description":{"en-US":"Blueberry\n"}},'.
                '{"id":"1","description":{"en-US":"Raspberry\n"}},{"id":"5","description":'.
                '{"en-US":"Strawberry\n"}},{"id":"2","description":{"en-US":"Cloudberry\n"}},'.
                '{"id":"3","description":{"en-US":"Halle Berry\n"}},'.
                '{"id":"4","description":{"en-US":"Cocktail cherry\n"}}],'.
                '"extensions":{"http:\/\/h5p.org\/x-api\/h5p-local-content-id":'.$cmid.
                ',"http:\/\/h5p.org\/x-api\/h5p-subContentId":"'.$result->interactiontype.
                '"},"contextExtensions":{}}';
        $result->rawscore = 1;
        $result->scaled = 0.2;
        $DB->insert_record('h5pactivity_attempts_results', $result);

        $result->subcontent = '14fcc986-728b-47f3-915b-'.$userid;
        $result->interactiontype = 'matching';
        $result->correctpattern = '["0[.]1[,]1[.]0[,]2[.]2"]';
        $result->response = '1[.]0[,]0[.]1[,]2[.]2';
        $result->additionals = '{"source":[{"id":"0","description":{"en-US":"A berry"}}'.
                ',{"id":"1","description":{"en-US":"An orange berry"}},'.
                '{"id":"2","description":{"en-US":"A red berry"}}],'.
                '"target":[{"id":"0","description":{"en-US":"Cloudberry"}},'.
                '{"id":"1","description":{"en-US":"Blueberry"}},'.
                '{"id":"2","description":{"en-US":"Redcurrant\n"}}],'.
                '"contextExtensions":{}}';
        $result->rawscore = 2;
        $result->scaled = 0.4;
        $DB->insert_record('h5pactivity_attempts_results', $result);

        return $attempt;
    }
}
