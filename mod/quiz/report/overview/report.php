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
 * This file defines the quiz overview report class.
 *
 * @package   quiz_overview
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\attempts_report;
use mod_quiz\question\bank\qbank_helper;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_options.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_table.php');


/**
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_overview_report extends attempts_report {

    public function display($quiz, $cm, $course) {
        global $DB, $PAGE;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'overview', 'quiz_overview_settings_form', $quiz, $cm, $course);

        $options = new quiz_overview_options('overview', $quiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);
        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                ['context' => context_course::instance($course->id)]);
        $table = new quiz_overview_table($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
        $filename = quiz_report_download_filename(get_string('overviewfilename', 'quiz_overview'),
                $courseshortname, $quiz->name);
        $table->is_downloading($options->download, $filename,
                $courseshortname . ' ' . format_string($quiz->name, true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }
        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security porblem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = quiz_has_questions($quiz->id);

        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $quiz,
                    $options, $currentgroup, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {
            // Construct the SQL.
            $table->setup_sql_queries($allowedjoins);

            if (!$table->is_downloading()) {
                // Output the regrade buttons.
                if (has_capability('mod/quiz:regrade', $this->context)) {
                    $regradesneeded = $this->count_question_attempts_needing_regrade(
                            $quiz, $groupstudentsjoins);
                    if ($currentgroup) {
                        $a= new stdClass();
                        $a->groupname = format_string(groups_get_group_name($currentgroup), true, [
                            'context' => $this->context,
                        ]);
                        $a->coursestudents = get_string('participants');
                        $a->countregradeneeded = $regradesneeded;
                        $regradealldrydolabel =
                                get_string('regradealldrydogroup', 'quiz_overview', $a);
                        $regradealldrylabel =
                                get_string('regradealldrygroup', 'quiz_overview', $a);
                        $regradealllabel =
                                get_string('regradeallgroup', 'quiz_overview', $a);
                    } else {
                        $regradealldrydolabel =
                                get_string('regradealldrydo', 'quiz_overview', $regradesneeded);
                        $regradealldrylabel =
                                get_string('regradealldry', 'quiz_overview');
                        $regradealllabel =
                                get_string('regradeall', 'quiz_overview');
                    }
                    $displayurl = new moodle_url($options->get_url(), ['sesskey' => sesskey()]);
                    echo '<div class="regradebuttons">';
                    echo '<form action="'.$displayurl->out_omit_querystring().'">';
                    echo '<div>';
                    echo html_writer::input_hidden_params($displayurl);
                    echo '<input type="submit" class="btn btn-secondary" name="regradeall" value="'.$regradealllabel.'"/>';
                    echo '<input type="submit" class="btn btn-secondary ml-1" name="regradealldry" value="' .
                            $regradealldrylabel . '"/>';
                    if ($regradesneeded) {
                        echo '<input type="submit" class="btn btn-secondary ml-1" name="regradealldrydo" value="' .
                                $regradealldrydolabel . '"/>';
                    }
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';
                }
                // Print information on the grading method.
                if ($strattempthighlight = quiz_report_highlighting_grading_method(
                        $quiz, $this->qmsubselect, $options->onlygraded)) {
                    echo '<div class="quizattemptcounts mt-3">' . $strattempthighlight . '</div>';
                }
            }

            // Define table columns.
            $columns = [];
            $headers = [];

            if (!$table->is_downloading() && $options->checkboxcolumn) {
                $columnname = 'checkbox';
                $columns[] = $columnname;
                $headers[] = $table->checkbox_col_header($columnname);
            }

            $this->add_user_columns($table, $columns, $headers);
            $this->add_state_column($columns, $headers);
            $this->add_time_columns($columns, $headers);

            $this->add_grade_columns($quiz, $options->usercanseegrades, $columns, $headers, false);

            if (!$table->is_downloading() && has_capability('mod/quiz:regrade', $this->context) &&
                    $this->has_regraded_questions($table->sql->from, $table->sql->where, $table->sql->params)) {
                $columns[] = 'regraded';
                $headers[] = get_string('regrade', 'quiz_overview');
            }

            if ($options->slotmarks) {
                foreach ($questions as $slot => $question) {
                    $columns[] = 'qsgrade' . $slot;
                    $header = get_string('qbrief', 'quiz', $question->displaynumber);
                    if (!$table->is_downloading()) {
                        $header .= '<br />';
                    } else {
                        $header .= ' ';
                    }
                    $header .= '/' . quiz_rescale_grade($question->maxmark, $quiz, 'question');
                    $headers[] = $header;
                }
            }

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), $options, false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out($options->pagesize, true);
        }

        if (!$table->is_downloading() && $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            list($bands, $bandwidth) = self::get_bands_count_and_width($quiz);
            $labels = self::get_bands_labels($bands, $bandwidth, $quiz);

            if ($currentgroup && $this->hasgroupstudents) {
                $sql = "SELECT qg.id
                          FROM {quiz_grades} qg
                          JOIN {user} u on u.id = qg.userid
                        {$groupstudentsjoins->joins}
                          WHERE qg.quiz = $quiz->id AND {$groupstudentsjoins->wheres}";
                if ($DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, $groupstudentsjoins);
                    $chart = self::get_chart($labels, $data);
                    $groupname = format_string(groups_get_group_name($currentgroup), true, [
                        'context' => $this->context,
                    ]);
                    $graphname = get_string('overviewreportgraphgroup', 'quiz_overview', $groupname);
                    // Numerical range data should display in LTR even for RTL languages.
                    echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
                }
            }

            if ($DB->record_exists('quiz_grades', ['quiz' => $quiz->id])) {
                $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());
                $chart = self::get_chart($labels, $data);
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                // Numerical range data should display in LTR even for RTL languages.
                echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
            }
        }
        return true;
    }

    /**
     * Extends parent function processing any submitted actions.
     *
     * @param stdClass $quiz
     * @param stdClass $cm
     * @param int $currentgroup
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params)
     * @param \core\dml\sql_join $allowedjoins (joins, wheres, params)
     * @param moodle_url $redirecturl
     */
    protected function process_actions($quiz, $cm, $currentgroup, \core\dml\sql_join $groupstudentsjoins,
            \core\dml\sql_join $allowedjoins, $redirecturl) {
        parent::process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $redirecturl);

        if (empty($currentgroup) || $this->hasgroupstudents) {
            if (optional_param('regrade', 0, PARAM_BOOL) && confirm_sesskey()) {
                if ($attemptids = optional_param_array('attemptid', [], PARAM_INT)) {
                    $this->start_regrade($quiz, $cm);
                    $this->regrade_attempts($quiz, false, $groupstudentsjoins, $attemptids);
                    $this->finish_regrade($redirecturl);
                }
            }
        }

        if (optional_param('regradeall', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($quiz, $cm);
            $this->regrade_attempts($quiz, false, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);

        } else if (optional_param('regradealldry', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($quiz, $cm);
            $this->regrade_attempts($quiz, true, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);

        } else if (optional_param('regradealldrydo', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->start_regrade($quiz, $cm);
            $this->regrade_attempts_needing_it($quiz, $groupstudentsjoins);
            $this->finish_regrade($redirecturl);
        }
    }

    /**
     * Check necessary capabilities, and start the display of the regrade progress page.
     * @param stdClass $quiz the quiz settings.
     * @param stdClass $cm the cm object for the quiz.
     */
    protected function start_regrade($quiz, $cm) {
        require_capability('mod/quiz:regrade', $this->context);
        $this->print_header_and_tabs(
            $cm,
            get_course($cm->course),
            $quiz,
            $this->mode
        );
    }

    /**
     * Finish displaying the regrade progress page.
     * @param moodle_url $nexturl where to send the user after the regrade.
     * @uses exit. This method never returns.
     */
    protected function finish_regrade($nexturl) {
        global $OUTPUT;
        \core\notification::success(get_string('regradecomplete', 'quiz_overview'));
        echo $OUTPUT->continue_button($nexturl);
        echo $OUTPUT->footer();
        die();
    }

    /**
     * Unlock the session and allow the regrading process to run in the background.
     */
    protected function unlock_session() {
        \core\session\manager::write_close();
        ignore_user_abort(true);
    }

    /**
     * Regrade a particular quiz attempt. Either for real ($dryrun = false), or
     * as a pretend regrade to see which fractions would change. The outcome is
     * stored in the quiz_overview_regrades table.
     *
     * Note, $attempt is not upgraded in the database. The caller needs to do that.
     * However, $attempt->sumgrades is updated, if this is not a dry run.
     *
     * @param stdClass $attempt the quiz attempt to regrade.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $slots if null, regrade all questions, otherwise, just regrade
     *      the questions with those slots.
     * @return array messages array with keys slot number, and values reasons why that slot cannot be regraded.
     */
    public function regrade_attempt($attempt, $dryrun = false, $slots = null): array {
        global $DB;
        // Need more time for a quiz with many questions.
        core_php_time_limit::raise(300);

        $transaction = $DB->start_delegated_transaction();

        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);
        $versioninformation = qbank_helper::get_version_information_for_questions_in_attempt(
            $attempt, $this->context);

        if (is_null($slots)) {
            $slots = $quba->get_slots();
        }

        $messages = [];
        $finished = $attempt->state == quiz_attempt::FINISHED;
        foreach ($slots as $slot) {
            $qqr = new stdClass();
            $qqr->oldfraction = $quba->get_question_fraction($slot);
            $otherquestionversion = question_bank::load_question($versioninformation[$slot]->newquestionid);

            $message = $quba->validate_can_regrade_with_other_version($slot, $otherquestionversion);
            if ($message) {
                $messages[$slot] = $message;
                continue;
            }

            $quba->regrade_question($slot, $finished, null, $otherquestionversion);

            $qqr->newfraction = $quba->get_question_fraction($slot);

            if (abs($qqr->oldfraction - $qqr->newfraction) > 1e-7) {
                $qqr->questionusageid = $quba->get_id();
                $qqr->slot = $slot;
                $qqr->regraded = empty($dryrun);
                $qqr->timemodified = time();
                $DB->insert_record('quiz_overview_regrades', $qqr, false);
            }
        }

        if (!$dryrun) {
            question_engine::save_questions_usage_by_activity($quba);

            $params = [
              'objectid' => $attempt->id,
              'relateduserid' => $attempt->userid,
              'context' => $this->context,
              'other' => [
                'quizid' => $attempt->quiz
              ]
            ];
            $event = \mod_quiz\event\attempt_regraded::create($params);
            $event->trigger();
        }

        $transaction->allow_commit();

        // Really, PHP should not need this hint, but without this, we just run out of memory.
        $quba = null;
        $transaction = null;
        gc_collect_cycles();
        return $messages;
    }

    /**
     * Regrade attempts for this quiz, exactly which attempts are regraded is
     * controlled by the parameters.
     *
     * @param stdClass $quiz the quiz settings.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param \core\dml\sql_join|null $groupstudentsjoins empty for all attempts, otherwise regrade attempts
     * for these users.
     * @param array $attemptids blank for all attempts, otherwise only regrade
     * attempts whose id is in this list.
     */
    protected function regrade_attempts($quiz, $dryrun = false,
            core\dml\sql_join $groupstudentsjoins = null, $attemptids = []) {
        global $DB;
        $this->unlock_session();

        $userfieldsapi = \core_user\fields::for_name();
        $sql = "SELECT quiza.*, " . $userfieldsapi->get_sql('u', false, '', '', false)->selects . "
                  FROM {quiz_attempts} quiza
                  JOIN {user} u ON u.id = quiza.userid";
        $where = "quiz = :qid AND preview = 0";
        $params = ['qid' => $quiz->id];

        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $sql .= "\n{$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }

        if ($attemptids) {
            list($attemptidcondition, $attemptidparams) = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
            $where .= " AND quiza.id $attemptidcondition";
            $params += $attemptidparams;
        }

        $sql .= "\nWHERE {$where}";
        $attempts = $DB->get_records_sql($sql, $params);
        if (!$attempts) {
            return;
        }

        $this->regrade_batch_of_attempts($quiz, $attempts, $dryrun, $groupstudentsjoins);
    }

    /**
     * Regrade those questions in those attempts that are marked as needing regrading
     * in the quiz_overview_regrades table.
     * @param stdClass $quiz the quiz settings.
     * @param \core\dml\sql_join $groupstudentsjoins empty for all attempts, otherwise regrade attempts
     * for these users.
     */
    protected function regrade_attempts_needing_it($quiz, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;
        $this->unlock_session();

        $join = '{quiz_overview_regrades} qqr ON qqr.questionusageid = quiza.uniqueid';
        $where = "quiza.quiz = :qid AND quiza.preview = 0 AND qqr.regraded = 0";
        $params = ['qid' => $quiz->id];

        // Fetch all attempts that need regrading.
        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $join .= "\nJOIN {user} u ON u.id = quiza.userid
                    {$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }

        $toregrade = $DB->get_recordset_sql("
                SELECT quiza.uniqueid, qqr.slot
                  FROM {quiz_attempts} quiza
                  JOIN $join
                 WHERE $where", $params);

        $attemptquestions = [];
        foreach ($toregrade as $row) {
            $attemptquestions[$row->uniqueid][] = $row->slot;
        }
        $toregrade->close();

        if (!$attemptquestions) {
            return;
        }

        list($uniqueidcondition, $params) = $DB->get_in_or_equal(array_keys($attemptquestions));
        $userfieldsapi = \core_user\fields::for_name();
        $attempts = $DB->get_records_sql("
                SELECT quiza.*, " . $userfieldsapi->get_sql('u', false, '', '', false)->selects . "
                  FROM {quiz_attempts} quiza
                  JOIN {user} u ON u.id = quiza.userid
                 WHERE quiza.uniqueid $uniqueidcondition
                ", $params);

        foreach ($attempts as $attempt) {
            $attempt->regradeonlyslots = $attemptquestions[$attempt->uniqueid];
        }

        $this->regrade_batch_of_attempts($quiz, $attempts, false, $groupstudentsjoins);
    }

    /**
     * This is a helper used by {@link regrade_attempts()} and
     * {@link regrade_attempts_needing_it()}.
     *
     * Given an array of attempts, it regrades them all, or does a dry run.
     * Each object in the attempts array must be a row from the quiz_attempts
     * table, with the \core_user\fields::for_name() fields from the user table joined in.
     * In addition, if $attempt->regradeonlyslots is set, then only those slots
     * are regraded, otherwise all slots are regraded.
     *
     * @param stdClass $quiz the quiz settings.
     * @param array $attempts of data from the quiz_attempts table, with extra data as above.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param \core\dml\sql_join $groupstudentsjoins empty for all attempts, otherwise regrade attempts
     */
    protected function regrade_batch_of_attempts($quiz, array $attempts,
            bool $dryrun, \core\dml\sql_join $groupstudentsjoins) {
        global $OUTPUT;
        $this->clear_regrade_table($quiz, $groupstudentsjoins);

        $progressbar = new progress_bar('quiz_overview_regrade', 500, true);
        $a = [
            'count' => count($attempts),
            'done'  => 0,
        ];
        foreach ($attempts as $attempt) {
            $a['done']++;
            $a['attemptnum'] = $attempt->attempt;
            $a['name'] = fullname($attempt);
            $a['attemptid'] = $attempt->id;
            if (!isset($attempt->regradeonlyslots)) {
                $attempt->regradeonlyslots = null;
            }
            $progressbar->update($a['done'], $a['count'],
                    get_string('regradingattemptxofywithdetails', 'quiz_overview', $a));
            $messages = $this->regrade_attempt($attempt, $dryrun, $attempt->regradeonlyslots);
            if ($messages) {
                $items = [];
                foreach ($messages as $slot => $message) {
                    $items[] = get_string('regradingattemptissue', 'quiz_overview',
                            ['slot' => $slot, 'reason' => $message]);
                }
                echo $OUTPUT->notification(
                        html_writer::tag('p', get_string('regradingattemptxofyproblem', 'quiz_overview', $a)) .
                        html_writer::alist($items), \core\output\notification::NOTIFY_WARNING);
            }
        }
        $progressbar->update($a['done'], $a['count'],
                get_string('regradedsuccessfullyxofy', 'quiz_overview', $a));

        if (!$dryrun) {
            $this->update_overall_grades($quiz);
        }
    }

    /**
     * Count the number of attempts in need of a regrade.
     *
     * @param stdClass $quiz the quiz settings.
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params) If this is given, only data relating
     * to these users is cleared.
     * @return int the number of attempts.
     */
    protected function count_question_attempts_needing_regrade($quiz, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;

        $userjoin = '';
        $usertest = '';
        $params = [];
        if ($this->hasgroupstudents) {
            $userjoin = "JOIN {user} u ON u.id = quiza.userid
                    {$groupstudentsjoins->joins}";
            $usertest = "{$groupstudentsjoins->wheres} AND u.id = quiza.userid AND ";
            $params = $groupstudentsjoins->params;
        }

        $params['cquiz'] = $quiz->id;
        $sql = "SELECT COUNT(DISTINCT quiza.id)
                  FROM {quiz_attempts} quiza
                  JOIN {quiz_overview_regrades} qqr ON quiza.uniqueid = qqr.questionusageid
                $userjoin
                 WHERE
                      $usertest
                      quiza.quiz = :cquiz AND
                      quiza.preview = 0 AND
                      qqr.regraded = 0";
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Are there any pending regrades in the table we are going to show?
     * @param string $from tables used by the main query.
     * @param string $where where clause used by the main query.
     * @param array $params required by the SQL.
     * @return bool whether there are pending regrades.
     */
    protected function has_regraded_questions($from, $where, $params) {
        global $DB;
        return $DB->record_exists_sql("
                SELECT 1
                  FROM {$from}
                  JOIN {quiz_overview_regrades} qor ON qor.questionusageid = quiza.uniqueid
                 WHERE {$where}", $params);
    }

    /**
     * Remove all information about pending/complete regrades from the database.
     * @param stdClass $quiz the quiz settings.
     * @param \core\dml\sql_join $groupstudentsjoins (joins, wheres, params). If this is given, only data relating
     * to these users is cleared.
     */
    protected function clear_regrade_table($quiz, \core\dml\sql_join $groupstudentsjoins) {
        global $DB;

        // Fetch all attempts that need regrading.
        $select = "questionusageid IN (
                    SELECT uniqueid
                      FROM {quiz_attempts} quiza";
        $where = "WHERE quiza.quiz = :qid";
        $params = ['qid' => $quiz->id];
        if ($this->hasgroupstudents && !empty($groupstudentsjoins->joins)) {
            $select .= "\nJOIN {user} u ON u.id = quiza.userid
                    {$groupstudentsjoins->joins}";
            $where .= " AND {$groupstudentsjoins->wheres}";
            $params += $groupstudentsjoins->params;
        }
        $select .= "\n$where)";

        $DB->delete_records_select('quiz_overview_regrades', $select, $params);
    }

    /**
     * Update the final grades for all attempts. This method is used following a regrade.
     *
     * @param stdClass $quiz the quiz settings.
     */
    protected function update_overall_grades($quiz) {
        $gradecalculator = $this->quizobj->get_grade_calculator();
        $gradecalculator->recompute_all_attempt_sumgrades();
        $gradecalculator->recompute_all_final_grades();
        quiz_update_grades($quiz);
    }

    /**
     * Get the bands configuration for the quiz.
     *
     * This returns the configuration for having between 11 and 20 bars in
     * a chart based on the maximum grade to be given on a quiz. The width of
     * a band is the number of grade points it encapsulates.
     *
     * @param stdClass $quiz The quiz object.
     * @return array Contains the number of bands, and their width.
     */
    public static function get_bands_count_and_width($quiz) {
        $bands = $quiz->grade;
        while ($bands > 20 || $bands <= 10) {
            if ($bands > 50) {
                $bands /= 5;
            } else if ($bands > 20) {
                $bands /= 2;
            }
            if ($bands < 4) {
                $bands *= 5;
            } else if ($bands <= 10) {
                $bands *= 2;
            }
        }
        // See MDL-34589. Using doubles as array keys causes problems in PHP 5.4, hence the explicit cast to int.
        $bands = (int) ceil($bands);
        return [$bands, $quiz->grade / $bands];
    }

    /**
     * Get the bands labels.
     *
     * @param int $bands The number of bands.
     * @param int $bandwidth The band width.
     * @param stdClass $quiz The quiz object.
     * @return string[] The labels.
     */
    public static function get_bands_labels($bands, $bandwidth, $quiz) {
        $bandlabels = [];
        for ($i = 1; $i <= $bands; $i++) {
            $bandlabels[] = quiz_format_grade($quiz, ($i - 1) * $bandwidth) . ' - ' . quiz_format_grade($quiz, $i * $bandwidth);
        }
        return $bandlabels;
    }

    /**
     * Get a chart.
     *
     * @param string[] $labels Chart labels.
     * @param int[] $data The data.
     * @return \core\chart_base
     */
    protected static function get_chart($labels, $data) {
        $chart = new \core\chart_bar();
        $chart->set_labels($labels);
        $chart->get_xaxis(0, true)->set_label(get_string('gradenoun'));

        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_label(get_string('participants'));
        $yaxis->set_stepsize(max(1, round(max($data) / 10)));

        $series = new \core\chart_series(get_string('participants'), $data);
        $chart->add_series($series);
        return $chart;
    }
}
