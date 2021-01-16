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
 * This file defines a class with "Number of errors" grading strategy logic
 *
 * @package    workshopform_numerrors
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');        // Interface definition.
require_once($CFG->libdir . '/gradelib.php'); // To handle float vs decimal issues.

/**
 * Server workshop files
 *
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function workshopform_numerrors_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea !== 'description') {
        return false;
    }

    $itemid = (int)array_shift($args); // the id of the assessment form dimension
    if (!$workshop = $DB->get_record('workshop', array('id' => $cm->instance))) {
        send_file_not_found();
    }

    if (!$dimension = $DB->get_record('workshopform_numerrors', array('id' => $itemid ,'workshopid' => $workshop->id))) {
        send_file_not_found();
    }

    // TODO now make sure the user is allowed to see the file
    // (media embedded into the dimension description)
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/workshopform_numerrors/$filearea/$itemid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * "Number of errors" grading strategy logic.
 */
class workshop_numerrors_strategy implements workshop_strategy {

    /** @const default number of dimensions to show */
    const MINDIMS = 3;

    /** @const number of dimensions to add */
    const ADDDIMS = 2;

    /** @var workshop the parent workshop instance */
    protected $workshop;

    /** @var array definition of the assessment form fields */
    protected $dimensions = null;

    /** @var array mapping of the number of errors to a grade */
    protected $mappings = null;

    /** @var array options for dimension description fields */
    protected $descriptionopts;

    /**
     * Constructor
     *
     * @param workshop $workshop The workshop instance record
     * @return void
     */
    public function __construct(workshop $workshop) {
        $this->workshop         = $workshop;
        $this->dimensions       = $this->load_fields();
        $this->mappings         = $this->load_mappings();
        $this->descriptionopts  = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => -1);
    }

    /**
     * Factory method returning an instance of an assessment form editor class
     *
     * @param $actionurl URL of form handler, defaults to auto detect the current url
     */
    public function get_edit_strategy_form($actionurl=null) {
        global $CFG;    // needed because the included files use it
        global $PAGE;

        require_once(__DIR__ . '/edit_form.php');

        $fields             = $this->prepare_form_fields($this->dimensions, $this->mappings);
        $nodimensions       = count($this->dimensions);
        $norepeatsdefault   = max($nodimensions + self::ADDDIMS, self::MINDIMS);
        $norepeats          = optional_param('norepeats', $norepeatsdefault, PARAM_INT);    // number of dimensions
        $noadddims          = optional_param('noadddims', '', PARAM_ALPHA);                 // shall we add more?
        if ($noadddims) {
            $norepeats += self::ADDDIMS;
        }

        // Append editor context to editor options, giving preference to existing context.
        $this->descriptionopts = array_merge(array('context' => $PAGE->context), $this->descriptionopts);

        // prepare the embeded files
        for ($i = 0; $i < $nodimensions; $i++) {
            // prepare all editor elements
            $fields = file_prepare_standard_editor($fields, 'description__idx_'.$i, $this->descriptionopts,
                $PAGE->context, 'workshopform_numerrors', 'description', $fields->{'dimensionid__idx_'.$i});
        }

        $customdata = array();
        $customdata['workshop'] = $this->workshop;
        $customdata['strategy'] = $this;
        $customdata['norepeats'] = $norepeats;
        $customdata['nodimensions'] = $nodimensions;
        $customdata['descriptionopts'] = $this->descriptionopts;
        $customdata['current']  = $fields;
        $attributes = array('class' => 'editstrategyform');

        return new workshop_edit_numerrors_strategy_form($actionurl, $customdata, 'post', '', $attributes);
    }

    /**
     * Save the assessment dimensions into database
     *
     * Saves data into the main strategy form table. If the record->id is null or zero,
     * new record is created. If the record->id is not empty, the existing record is updated. Records with
     * empty 'description' field are removed from database.
     * The passed data object are the raw data returned by the get_data().
     *
     * @uses $DB
     * @param stdClass $data Raw data returned by the dimension editor form
     * @return void
     */
    public function save_edit_strategy_form(stdclass $data) {
        global $DB, $PAGE;

        $workshopid = $data->workshopid;
        $norepeats  = $data->norepeats;

        $data       = $this->prepare_database_fields($data);
        $records    = $data->numerrors; // data to be saved into {workshopform_numerrors}
        $mappings   = $data->mappings;  // data to be saved into {workshopform_numerrors_map}
        $todelete   = array();          // dimension ids to be deleted
        $maxnonegative = 0;             // maximum number of (weighted) negative responses

        for ($i=0; $i < $norepeats; $i++) {
            $record = $records[$i];
            if (0 == strlen(trim($record->description_editor['text']))) {
                if (!empty($record->id)) {
                    // existing dimension record with empty description - to be deleted
                    $todelete[] = $record->id;
                }
                continue;
            }
            if (empty($record->id)) {
                // new field
                $record->id = $DB->insert_record('workshopform_numerrors', $record);
            } else {
                // exiting field
                $DB->update_record('workshopform_numerrors', $record);
            }
            $maxnonegative += $record->weight;
            // re-save with correct path to embeded media files
            $record = file_postupdate_standard_editor($record, 'description', $this->descriptionopts, $PAGE->context,
                                                      'workshopform_numerrors', 'description', $record->id);
            $DB->update_record('workshopform_numerrors', $record);
        }
        $this->delete_dimensions($todelete);

        // re-save the mappings
        $todelete = array();
        foreach ($data->mappings as $nonegative => $grade) {
            if (is_null($grade)) {
                // no grade set for this number of negative responses
                $todelete[] = $nonegative;
                continue;
            }
            if (isset($this->mappings[$nonegative])) {
                $DB->set_field('workshopform_numerrors_map', 'grade', $grade,
                            array('workshopid' => $this->workshop->id, 'nonegative' => $nonegative));
            } else {
                $DB->insert_record('workshopform_numerrors_map',
                            (object)array('workshopid' => $this->workshop->id, 'nonegative' => $nonegative, 'grade' => $grade));
            }
        }
        // clear mappings that are not valid any more
        if (!empty($todelete)) {
            list($insql, $params) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED);
            $insql = "nonegative $insql OR ";
        } else {
            $insql = '';
        }
        $sql = "DELETE FROM {workshopform_numerrors_map}
                      WHERE (($insql nonegative > :maxnonegative) AND (workshopid = :workshopid))";
        $params['maxnonegative'] = $maxnonegative;
        $params['workshopid']   = $this->workshop->id;
        $DB->execute($sql, $params);
    }

    /**
     * Factory method returning an instance of an assessment form
     *
     * @param moodle_url $actionurl URL of form handler, defaults to auto detect the current url
     * @param string $mode          Mode to open the form in: preview/assessment
     * @param stdClass $assessment
     * @param bool $editable
     * @param array $options
     */
    public function get_assessment_form(moodle_url $actionurl=null, $mode='preview', stdclass $assessment=null, $editable=true, $options=array()) {
        global $CFG;    // needed because the included files use it
        global $PAGE;
        global $DB;
        require_once(__DIR__ . '/assessment_form.php');

        $fields         = $this->prepare_form_fields($this->dimensions, $this->mappings);
        $nodimensions   = count($this->dimensions);

        // rewrite URLs to the embeded files
        for ($i = 0; $i < $nodimensions; $i++) {
            $fields->{'description__idx_'.$i} = file_rewrite_pluginfile_urls($fields->{'description__idx_'.$i},
                'pluginfile.php', $PAGE->context->id, 'workshopform_numerrors', 'description', $fields->{'dimensionid__idx_'.$i});
        }

        if ('assessment' === $mode and !empty($assessment)) {
            // load the previously saved assessment data
            $grades = $this->get_current_assessment_data($assessment);
            $current = new stdclass();
            for ($i = 0; $i < $nodimensions; $i++) {
                $dimid = $fields->{'dimensionid__idx_'.$i};
                if (isset($grades[$dimid])) {
                    $current->{'gradeid__idx_'.$i}      = $grades[$dimid]->id;
                    $current->{'grade__idx_'.$i}        = ($grades[$dimid]->grade == 0 ? -1 : 1);
                    $current->{'peercomment__idx_'.$i}  = $grades[$dimid]->peercomment;
                }
            }
        }

        // set up the required custom data common for all strategies
        $customdata['workshop'] = $this->workshop;
        $customdata['strategy'] = $this;
        $customdata['mode']     = $mode;
        $customdata['options']  = $options;

        // set up strategy-specific custom data
        $customdata['nodims']   = $nodimensions;
        $customdata['fields']   = $fields;
        $customdata['current']  = isset($current) ? $current : null;
        $attributes = array('class' => 'assessmentform numerrors');

        return new workshop_numerrors_assessment_form($actionurl, $customdata, 'post', '', $attributes, $editable);
    }

    /**
     * Saves the filled assessment
     *
     * This method processes data submitted using the form returned by {@link get_assessment_form()}
     *
     * @param stdClass $assessment Assessment being filled
     * @param stdClass $data       Raw data as returned by the assessment form
     * @return float|null          Raw grade (from 0.00000 to 100.00000) for submission as suggested by the peer
     */
    public function save_assessment(stdclass $assessment, stdclass $data) {
        global $DB;

        if (!isset($data->nodims)) {
            throw new coding_exception('You did not send me the number of assessment dimensions to process');
        }
        for ($i = 0; $i < $data->nodims; $i++) {
            $grade = new stdclass();
            $grade->id                  = $data->{'gradeid__idx_' . $i};
            $grade->assessmentid        = $assessment->id;
            $grade->strategy            = 'numerrors';
            $grade->dimensionid         = $data->{'dimensionid__idx_' . $i};
            $grade->grade               = ($data->{'grade__idx_' . $i} <= 0 ? 0 : 1);
            $grade->peercomment         = $data->{'peercomment__idx_' . $i};
            $grade->peercommentformat   = FORMAT_HTML;
            if (empty($grade->id)) {
                // new grade
                $grade->id = $DB->insert_record('workshop_grades', $grade);
            } else {
                // updated grade
                $DB->update_record('workshop_grades', $grade);
            }
        }
        return $this->update_peer_grade($assessment);
    }

    /**
     * Has the assessment form been defined and is ready to be used by the reviewers?
     *
     * @return boolean
     */
    public function form_ready() {
        if (count($this->dimensions) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @see parent::get_assessments_recordset()
     */
    public function get_assessments_recordset($restrict=null) {
       global $DB;

        $sql = 'SELECT s.id AS submissionid,
                       a.id AS assessmentid, a.weight AS assessmentweight, a.reviewerid, a.gradinggrade,
                       g.dimensionid, g.grade
                  FROM {workshop_submissions} s
                  JOIN {workshop_assessments} a ON (a.submissionid = s.id)
                  JOIN {workshop_grades} g ON (g.assessmentid = a.id AND g.strategy = :strategy)
                 WHERE s.example=0 AND s.workshopid=:workshopid'; // to be cont.
        $params = array('workshopid' => $this->workshop->id, 'strategy' => $this->workshop->strategy);

        if (is_null($restrict)) {
            // update all users - no more conditions
        } elseif (!empty($restrict)) {
            list($usql, $uparams) = $DB->get_in_or_equal($restrict, SQL_PARAMS_NAMED);
            $sql .= " AND a.reviewerid $usql";
            $params = array_merge($params, $uparams);
        } else {
            throw new coding_exception('Empty value is not a valid parameter here');
        }

        $sql .= ' ORDER BY s.id'; // this is important for bulk processing

        return $DB->get_recordset_sql($sql, $params);

    }

    /**
     * @see parent::get_dimensions_info()
     */
    public function get_dimensions_info() {
        global $DB;

        $params = array('workshopid' => $this->workshop->id);
        $dimrecords = $DB->get_records('workshopform_numerrors', array('workshopid' => $this->workshop->id), 'sort', 'id,weight');
        foreach ($dimrecords as $dimid => $dimrecord) {
            $dimrecords[$dimid]->min = 0;
            $dimrecords[$dimid]->max = 1;
        }
        return $dimrecords;
    }

    /**
     * Is a given scale used by the instance of workshop?
     *
     * This grading strategy does not use scales.
     *
     * @param int $scaleid id of the scale to check
     * @param int|null $workshopid id of workshop instance to check, checks all in case of null
     * @return bool
     */
    public static function scale_used($scaleid, $workshopid=null) {
        return false;
    }

    /**
     * Delete all data related to a given workshop module instance
     *
     * @see workshop_delete_instance()
     * @param int $workshopid id of the workshop module instance being deleted
     * @return void
     */
    public static function delete_instance($workshopid) {
        global $DB;

        $DB->delete_records('workshopform_numerrors', array('workshopid' => $workshopid));
        $DB->delete_records('workshopform_numerrors_map', array('workshopid' => $workshopid));
    }

    ////////////////////////////////////////////////////////////////////////////////
    // Internal methods                                                           //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Loads the fields of the assessment form currently used in this workshop
     *
     * @return array definition of assessment dimensions
     */
    protected function load_fields() {
        global $DB;

        $sql = 'SELECT *
                  FROM {workshopform_numerrors}
                 WHERE workshopid = :workshopid
                 ORDER BY sort';
        $params = array('workshopid' => $this->workshop->id);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Loads the mappings of the number of errors to the grade
     *
     * @return array of records
     */
    protected function load_mappings() {
        global $DB;
        return $DB->get_records('workshopform_numerrors_map', array('workshopid' => $this->workshop->id), 'nonegative',
                                'nonegative,grade'); // we can use nonegative as key here as it must be unique within workshop
    }

    /**
     * Prepares the database data to be used by the mform
     *
     * @param array $dims Array of raw dimension records as returned by {@link load_fields()}
     * @param array $maps Array of raw mapping records as returned by {@link load_mappings()}
     * @return array Array of fields data to be used by the mform set_data
     */
    protected function prepare_form_fields(array $dims, array $maps) {

        $formdata = new stdclass();
        $key = 0;
        foreach ($dims as $dimension) {
            $formdata->{'dimensionid__idx_' . $key}             = $dimension->id;
            $formdata->{'description__idx_' . $key}             = $dimension->description;
            $formdata->{'description__idx_' . $key.'format'}    = $dimension->descriptionformat;
            $formdata->{'grade0__idx_' . $key}                  = $dimension->grade0;
            $formdata->{'grade1__idx_' . $key}                  = $dimension->grade1;
            $formdata->{'weight__idx_' . $key}                  = $dimension->weight;
            $key++;
        }

        foreach ($maps as $nonegative => $map) {
            $formdata->{'map__idx_' . $nonegative} = $map->grade;
        }

        return $formdata;
    }

    /**
     * Deletes dimensions and removes embedded media from its descriptions
     *
     * todo we may check that there are no assessments done using these dimensions and probably remove them
     *
     * @param array $ids list to delete
     * @return void
     */
    protected function delete_dimensions(array $ids) {
        global $DB, $PAGE;

        $fs         = get_file_storage();
        foreach ($ids as $id) {
            $fs->delete_area_files($PAGE->context->id, 'workshopform_numerrors', 'description', $id);
        }
        $DB->delete_records_list('workshopform_numerrors', 'id', $ids);
    }

    /**
     * Prepares data returned by {@link workshop_edit_numerrors_strategy_form} so they can be saved into database
     *
     * It automatically adds some columns into every record. The sorting is
     * done by the order of the returned array and starts with 1.
     * Called internally from {@link save_edit_strategy_form()} only. Could be private but
     * keeping protected for unit testing purposes.
     *
     * @param stdClass $raw Raw data returned by mform
     * @return array Array of objects to be inserted/updated in DB
     */
    protected function prepare_database_fields(stdclass $raw) {
        global $PAGE;

        $cook               = new stdclass();   // to be returned
        $cook->numerrors    = array();          // to be stored in {workshopform_numerrors}
        $cook->mappings     = array();          // to be stored in {workshopform_numerrors_map}

        for ($i = 0; $i < $raw->norepeats; $i++) {
            $cook->numerrors[$i]                        = new stdclass();
            $cook->numerrors[$i]->id                    = $raw->{'dimensionid__idx_'.$i};
            $cook->numerrors[$i]->workshopid            = $this->workshop->id;
            $cook->numerrors[$i]->sort                  = $i + 1;
            $cook->numerrors[$i]->description_editor    = $raw->{'description__idx_'.$i.'_editor'};
            $cook->numerrors[$i]->grade0                = $raw->{'grade0__idx_'.$i};
            $cook->numerrors[$i]->grade1                = $raw->{'grade1__idx_'.$i};
            $cook->numerrors[$i]->weight                = $raw->{'weight__idx_'.$i};
        }

        $i = 1;
        while (isset($raw->{'map__idx_'.$i})) {
            if (is_numeric($raw->{'map__idx_'.$i})) {
                $cook->mappings[$i] = $raw->{'map__idx_'.$i}; // should be a value from 0 to 100
            } else {
                $cook->mappings[$i] = null; // the user did not set anything
            }
            $i++;
        }

        return $cook;
    }

    /**
     * Returns the list of current grades filled by the reviewer
     *
     * @param stdClass $assessment Assessment record
     * @return array of filtered records from the table workshop_grades
     */
    protected function get_current_assessment_data(stdclass $assessment) {
        global $DB;

        if (empty($this->dimensions)) {
            return array();
        }
        list($dimsql, $dimparams) = $DB->get_in_or_equal(array_keys($this->dimensions), SQL_PARAMS_NAMED);
        // beware! the caller may rely on the returned array is indexed by dimensionid
        $sql = "SELECT dimensionid, wg.*
                  FROM {workshop_grades} wg
                 WHERE assessmentid = :assessmentid AND strategy= :strategy AND dimensionid $dimsql";
        $params = array('assessmentid' => $assessment->id, 'strategy' => 'numerrors');
        $params = array_merge($params, $dimparams);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Aggregates the assessment form data and sets the grade for the submission given by the peer
     *
     * @param stdClass $assessment Assessment record
     * @return float|null          Raw grade (0.00000 to 100.00000) for submission as suggested by the peer
     */
    protected function update_peer_grade(stdclass $assessment) {
        $grades     = $this->get_current_assessment_data($assessment);
        $suggested  = $this->calculate_peer_grade($grades);
        if (!is_null($suggested)) {
            $this->workshop->set_peer_grade($assessment->id, $suggested);
        }
        return $suggested;
    }

    /**
     * Calculates the aggregated grade given by the reviewer
     *
     * @param array $grades Grade records as returned by {@link get_current_assessment_data}
     * @return float|null   Raw grade (0.00000 to 100.00000) for submission as suggested by the peer
     */
    protected function calculate_peer_grade(array $grades) {
        if (empty($grades)) {
            return null;
        }
        $sumerrors  = 0;    // sum of the weighted errors (i.e. the negative responses)
        foreach ($grades as $grade) {
            if (grade_floats_different($grade->grade, 1.00000)) {
                // negative reviewer's response
                $sumerrors += $this->dimensions[$grade->dimensionid]->weight;
            }
        }
        return $this->errors_to_grade($sumerrors);
    }

    /**
     * Returns a grade 0.00000 to 100.00000 for the given number of errors
     *
     * This is where we use the mapping table defined by the teacher. If a grade for the given
     * number of errors (negative assertions) is not defined, the most recently defined one is used.
     * Example of the defined mapping:
     * Number of errors | Grade
     *                0 | 100%  (always)
     *                1 | -     (not defined)
     *                2 | 80%
     *                3 | 60%
     *                4 | -
     *                5 | 30%
     *                6 | 0%
     * With this mapping, one error is mapped to 100% grade and 4 errors is mapped to 60%.
     *
     * @param mixed $numerrors Number of errors
     * @return float          Raw grade (0.00000 to 100.00000) for the given number of negative assertions
     */
    protected function errors_to_grade($numerrors) {
        $grade = 100.00000;
        for ($i = 1; $i <= $numerrors; $i++) {
            if (isset($this->mappings[$i])) {
                $grade = $this->mappings[$i]->grade;
            }
        }
        if ($grade > 100.00000) {
            $grade = 100.00000;
        }
        if ($grade < 0.00000) {
            $grade = 0.00000;
        }
        return grade_floatval($grade);
    }
}
