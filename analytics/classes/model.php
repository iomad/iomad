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
 * Prediction model representation.
 *
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Prediction model representation.
 *
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model {

    /**
     * All as expected.
     */
    const OK = 0;

    /**
     * There was a problem.
     */
    const GENERAL_ERROR = 1;

    /**
     * No dataset to analyse.
     */
    const NO_DATASET = 2;

    /**
     * Model with low prediction accuracy.
     */
    const LOW_SCORE = 4;

    /**
     * Not enough data to evaluate the model properly.
     */
    const NOT_ENOUGH_DATA = 8;

    /**
     * Invalid analysable for the time splitting method.
     */
    const ANALYSABLE_REJECTED_TIME_SPLITTING_METHOD = 4;

    /**
     * Invalid analysable for all time splitting methods.
     */
    const ANALYSABLE_STATUS_INVALID_FOR_RANGEPROCESSORS = 8;

    /**
     * Invalid analysable for the target
     */
    const ANALYSABLE_STATUS_INVALID_FOR_TARGET = 16;

    /**
     * Minimum score to consider a non-static prediction model as good.
     */
    const MIN_SCORE = 0.7;

    /**
     * Minimum prediction confidence (from 0 to 1) to accept a prediction as reliable enough.
     */
    const PREDICTION_MIN_SCORE = 0.6;

    /**
     * Maximum standard deviation between different evaluation repetitions to consider that evaluation results are stable.
     */
    const ACCEPTED_DEVIATION = 0.05;

    /**
     * Number of evaluation repetitions.
     */
    const EVALUATION_ITERATIONS = 10;

    /**
     * @var \stdClass
     */
    protected $model = null;

    /**
     * @var \core_analytics\local\analyser\base
     */
    protected $analyser = null;

    /**
     * @var \core_analytics\local\target\base
     */
    protected $target = null;

    /**
     * @var \core_analytics\local\indicator\base[]
     */
    protected $indicators = null;

    /**
     * Unique Model id created from site info and last model modification.
     *
     * @var string
     */
    protected $uniqueid = null;

    /**
     * Constructor.
     *
     * @param int|\stdClass $model
     * @return void
     */
    public function __construct($model) {
        global $DB;

        if (is_scalar($model)) {
            $model = $DB->get_record('analytics_models', array('id' => $model), '*', MUST_EXIST);
            if (!$model) {
                throw new \moodle_exception('errorunexistingmodel', 'analytics', '', $model);
            }
        }
        $this->model = $model;
    }

    /**
     * Quick safety check to discard site models which required components are not available anymore.
     *
     * @return bool
     */
    public function is_available() {
        $target = $this->get_target();
        if (!$target) {
            return false;
        }

        $classname = $target->get_analyser_class();
        if (!class_exists($classname)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the model id.
     *
     * @return int
     */
    public function get_id() {
        return $this->model->id;
    }

    /**
     * Returns a plain \stdClass with the model data.
     *
     * @return \stdClass
     */
    public function get_model_obj() {
        return $this->model;
    }

    /**
     * Returns the model target.
     *
     * @return \core_analytics\local\target\base
     */
    public function get_target() {
        if ($this->target !== null) {
            return $this->target;
        }
        $instance = \core_analytics\manager::get_target($this->model->target);
        $this->target = $instance;

        return $this->target;
    }

    /**
     * Returns the model indicators.
     *
     * @return \core_analytics\local\indicator\base[]
     */
    public function get_indicators() {
        if ($this->indicators !== null) {
            return $this->indicators;
        }

        $fullclassnames = json_decode($this->model->indicators);

        if (!is_array($fullclassnames)) {
            throw new \coding_exception('Model ' . $this->model->id . ' indicators can not be read');
        }

        $this->indicators = array();
        foreach ($fullclassnames as $fullclassname) {
            $instance = \core_analytics\manager::get_indicator($fullclassname);
            if ($instance) {
                $this->indicators[$fullclassname] = $instance;
            } else {
                debugging('Can\'t load ' . $fullclassname . ' indicator', DEBUG_DEVELOPER);
            }
        }

        return $this->indicators;
    }

    /**
     * Returns the list of indicators that could potentially be used by the model target.
     *
     * It includes the indicators that are part of the model.
     *
     * @return \core_analytics\local\indicator\base[]
     */
    public function get_potential_indicators() {

        $indicators = \core_analytics\manager::get_all_indicators();

        if (empty($this->analyser)) {
            $this->init_analyser(array('evaluation' => true));
        }

        foreach ($indicators as $classname => $indicator) {
            if ($this->analyser->check_indicator_requirements($indicator) !== true) {
                unset($indicators[$classname]);
            }
        }
        return $indicators;
    }

    /**
     * Returns the model analyser (defined by the model target).
     *
     * @param array $options Default initialisation with no options.
     * @return \core_analytics\local\analyser\base
     */
    public function get_analyser($options = array()) {
        if ($this->analyser !== null) {
            return $this->analyser;
        }

        $this->init_analyser($options);

        return $this->analyser;
    }

    /**
     * Initialises the model analyser.
     *
     * @throws \coding_exception
     * @param array $options
     * @return void
     */
    protected function init_analyser($options = array()) {

        $target = $this->get_target();
        $indicators = $this->get_indicators();

        if (empty($target)) {
            throw new \moodle_exception('errornotarget', 'analytics');
        }

        $timesplittings = array();
        if (empty($options['notimesplitting'])) {
            if (!empty($options['evaluation'])) {
                // The evaluation process will run using all available time splitting methods unless one is specified.
                if (!empty($options['timesplitting'])) {
                    $timesplitting = \core_analytics\manager::get_time_splitting($options['timesplitting']);
                    $timesplittings = array($timesplitting->get_id() => $timesplitting);
                } else {
                    $timesplittings = \core_analytics\manager::get_enabled_time_splitting_methods();
                }
            } else {

                if (empty($this->model->timesplitting)) {
                    throw new \moodle_exception('invalidtimesplitting', 'analytics', '', $this->model->id);
                }

                // Returned as an array as all actions (evaluation, training and prediction) go through the same process.
                $timesplittings = array($this->model->timesplitting => $this->get_time_splitting());
            }

            if (empty($timesplittings)) {
                throw new \moodle_exception('errornotimesplittings', 'analytics');
            }
        }

        if (!empty($options['evaluation'])) {
            foreach ($timesplittings as $timesplitting) {
                $timesplitting->set_evaluating(true);
            }
        }

        $classname = $target->get_analyser_class();
        if (!class_exists($classname)) {
            throw new \coding_exception($classname . ' class does not exists');
        }

        // Returns a \core_analytics\local\analyser\base class.
        $this->analyser = new $classname($this->model->id, $target, $indicators, $timesplittings, $options);
    }

    /**
     * Returns the model time splitting method.
     *
     * @return \core_analytics\local\time_splitting\base|false Returns false if no time splitting.
     */
    public function get_time_splitting() {
        if (empty($this->model->timesplitting)) {
            return false;
        }
        return \core_analytics\manager::get_time_splitting($this->model->timesplitting);
    }

    /**
     * Creates a new model. Enables it if $timesplittingid is specified.
     *
     * @param \core_analytics\local\target\base $target
     * @param \core_analytics\local\indicator\base[] $indicators
     * @param string $timesplittingid The time splitting method id (its fully qualified class name)
     * @return \core_analytics\model
     */
    public static function create(\core_analytics\local\target\base $target, array $indicators, $timesplittingid = false) {
        global $USER, $DB;

        \core_analytics\manager::check_can_manage_models();

        $indicatorclasses = self::indicator_classes($indicators);

        $now = time();

        $modelobj = new \stdClass();
        $modelobj->target = $target->get_id();
        $modelobj->indicators = json_encode($indicatorclasses);
        $modelobj->version = $now;
        $modelobj->timecreated = $now;
        $modelobj->timemodified = $now;
        $modelobj->usermodified = $USER->id;

        $id = $DB->insert_record('analytics_models', $modelobj);

        // Get db defaults.
        $modelobj = $DB->get_record('analytics_models', array('id' => $id), '*', MUST_EXIST);

        $model = new static($modelobj);

        if ($timesplittingid) {
            $model->enable($timesplittingid);
        }

        if ($model->is_static()) {
            $model->mark_as_trained();
        }

        return $model;
    }

    /**
     * Does this model exist?
     *
     * If no indicators are provided it considers any model with the provided
     * target a match.
     *
     * @param \core_analytics\local\target\base $target
     * @param \core_analytics\local\indicator\base[]|false $indicators
     * @return bool
     */
    public static function exists(\core_analytics\local\target\base $target, $indicators = false) {
        global $DB;

        $existingmodels = $DB->get_records('analytics_models', array('target' => $target->get_id()));

        if (!$indicators && $existingmodels) {
            return true;
        }

        $indicatorids = array_keys($indicators);
        sort($indicatorids);

        foreach ($existingmodels as $modelobj) {
            $model = new \core_analytics\model($modelobj);
            $modelindicatorids = array_keys($model->get_indicators());
            sort($modelindicatorids);

            if ($indicatorids === $modelindicatorids) {
                return true;
            }
        }
        return false;
    }

    /**
     * Updates the model.
     *
     * @param int|bool $enabled
     * @param \core_analytics\local\indicator\base[]|false $indicators False to respect current indicators
     * @param string|false $timesplittingid False to respect current time splitting method
     * @return void
     */
    public function update($enabled, $indicators = false, $timesplittingid = '') {
        global $USER, $DB;

        \core_analytics\manager::check_can_manage_models();

        $now = time();

        if ($indicators !== false) {
            $indicatorclasses = self::indicator_classes($indicators);
            $indicatorsstr = json_encode($indicatorclasses);
        } else {
            // Respect current value.
            $indicatorsstr = $this->model->indicators;
        }

        if ($timesplittingid === false) {
            // Respect current value.
            $timesplittingid = $this->model->timesplitting;
        }

        if ($this->model->timesplitting !== $timesplittingid ||
                $this->model->indicators !== $indicatorsstr) {

            // Delete generated predictions before changing the model version.
            $this->clear();

            // It needs to be reset as the version changes.
            $this->uniqueid = null;

            // We update the version of the model so different time splittings are not mixed up.
            $this->model->version = $now;

            // Reset trained flag.
            if (!$this->is_static()) {
                $this->model->trained = 0;
            }

        } else if ($this->model->enabled != $enabled) {
            // We purge the cached contexts with insights as some will not be visible anymore.
            $this->purge_insights_cache();
        }

        $this->model->enabled = intval($enabled);
        $this->model->indicators = $indicatorsstr;
        $this->model->timesplitting = $timesplittingid;
        $this->model->timemodified = $now;
        $this->model->usermodified = $USER->id;

        $DB->update_record('analytics_models', $this->model);
    }

    /**
     * Removes the model.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        \core_analytics\manager::check_can_manage_models();

        $this->clear();

        // Method self::clear is already clearing the current model version.
        $predictor = \core_analytics\manager::get_predictions_processor();
        $predictor->delete_output_dir($this->get_output_dir(array(), true));

        $DB->delete_records('analytics_models', array('id' => $this->model->id));
        $DB->delete_records('analytics_models_log', array('modelid' => $this->model->id));
    }

    /**
     * Evaluates the model.
     *
     * This method gets the site contents (through the analyser) creates a .csv dataset
     * with them and evaluates the model prediction accuracy multiple times using the
     * machine learning backend. It returns an object where the model score is the average
     * prediction accuracy of all executed evaluations.
     *
     * @param array $options
     * @return \stdClass[]
     */
    public function evaluate($options = array()) {

        \core_analytics\manager::check_can_manage_models();

        if ($this->is_static()) {
            $this->get_analyser()->add_log(get_string('noevaluationbasedassumptions', 'analytics'));
            $result = new \stdClass();
            $result->status = self::NO_DATASET;
            return array($this->get_time_splitting()->get_id() => $result);
        }

        $options['evaluation'] = true;
        $this->init_analyser($options);

        if (empty($this->get_indicators())) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }

        $this->heavy_duty_mode();

        // Before get_labelled_data call so we get an early exception if it is not ready.
        $predictor = \core_analytics\manager::get_predictions_processor();

        $datasets = $this->get_analyser()->get_labelled_data();

        // No datasets generated.
        if (empty($datasets)) {
            $result = new \stdClass();
            $result->status = self::NO_DATASET;
            $result->info = $this->get_analyser()->get_logs();
            return array($result);
        }

        if (!PHPUNIT_TEST && CLI_SCRIPT) {
            echo PHP_EOL . get_string('processingsitecontents', 'analytics') . PHP_EOL;
        }

        $results = array();
        foreach ($datasets as $timesplittingid => $dataset) {

            $timesplitting = \core_analytics\manager::get_time_splitting($timesplittingid);

            $result = new \stdClass();

            $dashestimesplittingid = str_replace('\\', '', $timesplittingid);
            $outputdir = $this->get_output_dir(array('evaluation', $dashestimesplittingid));

            // Evaluate the dataset, the deviation we accept in the results depends on the amount of iterations.
            if ($this->get_target()->is_linear()) {
                $predictorresult = $predictor->evaluate_regression($this->get_unique_id(), self::ACCEPTED_DEVIATION,
                self::EVALUATION_ITERATIONS, $dataset, $outputdir);
            } else {
                $predictorresult = $predictor->evaluate_classification($this->get_unique_id(), self::ACCEPTED_DEVIATION,
                self::EVALUATION_ITERATIONS, $dataset, $outputdir);
            }

            $result->status = $predictorresult->status;
            $result->info = $predictorresult->info;

            if (isset($predictorresult->score)) {
                $result->score = $predictorresult->score;
            } else {
                // Prediction processors may return an error, default to 0 score in that case.
                $result->score = 0;
            }

            $dir = false;
            if (!empty($predictorresult->dir)) {
                $dir = $predictorresult->dir;
            }

            $result->logid = $this->log_result($timesplitting->get_id(), $result->score, $dir, $result->info);

            $results[$timesplitting->get_id()] = $result;
        }

        return $results;
    }

    /**
     * Trains the model using the site contents.
     *
     * This method prepares a dataset from the site contents (through the analyser)
     * and passes it to the machine learning backends. Static models are skipped as
     * they do not require training.
     *
     * @return \stdClass
     */
    public function train() {

        \core_analytics\manager::check_can_manage_models();

        if ($this->is_static()) {
            $this->get_analyser()->add_log(get_string('notrainingbasedassumptions', 'analytics'));
            $result = new \stdClass();
            $result->status = self::OK;
            return $result;
        }

        if (!$this->is_enabled() || empty($this->model->timesplitting)) {
            throw new \moodle_exception('invalidtimesplitting', 'analytics', '', $this->model->id);
        }

        if (empty($this->get_indicators())) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }

        $this->heavy_duty_mode();

        // Before get_labelled_data call so we get an early exception if it is not writable.
        $outputdir = $this->get_output_dir(array('execution'));

        // Before get_labelled_data call so we get an early exception if it is not ready.
        $predictor = \core_analytics\manager::get_predictions_processor();

        $datasets = $this->get_analyser()->get_labelled_data();

        // No training if no files have been provided.
        if (empty($datasets) || empty($datasets[$this->model->timesplitting])) {

            $result = new \stdClass();
            $result->status = self::NO_DATASET;
            $result->info = $this->get_analyser()->get_logs();
            return $result;
        }
        $samplesfile = $datasets[$this->model->timesplitting];

        // Train using the dataset.
        if ($this->get_target()->is_linear()) {
            $predictorresult = $predictor->train_regression($this->get_unique_id(), $samplesfile, $outputdir);
        } else {
            $predictorresult = $predictor->train_classification($this->get_unique_id(), $samplesfile, $outputdir);
        }

        $result = new \stdClass();
        $result->status = $predictorresult->status;
        $result->info = $predictorresult->info;

        if ($result->status !== self::OK) {
            return $result;
        }

        $this->flag_file_as_used($samplesfile, 'trained');

        // Mark the model as trained if it wasn't.
        if ($this->model->trained == false) {
            $this->mark_as_trained();
        }

        return $result;
    }

    /**
     * Get predictions from the site contents.
     *
     * It analyses the site contents (through analyser classes) looking for samples
     * ready to receive predictions. It generates a dataset with all samples ready to
     * get predictions and it passes it to the machine learning backends or to the
     * targets based on assumptions to get the predictions.
     *
     * @return \stdClass
     */
    public function predict() {
        global $DB;

        \core_analytics\manager::check_can_manage_models();

        if (!$this->is_enabled() || empty($this->model->timesplitting)) {
            throw new \moodle_exception('invalidtimesplitting', 'analytics', '', $this->model->id);
        }

        if (empty($this->get_indicators())) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }

        $this->heavy_duty_mode();

        // Before get_unlabelled_data call so we get an early exception if it is not writable.
        $outputdir = $this->get_output_dir(array('execution'));

        // Before get_unlabelled_data call so we get an early exception if it is not ready.
        if (!$this->is_static()) {
            $predictor = \core_analytics\manager::get_predictions_processor();
        }

        $samplesdata = $this->get_analyser()->get_unlabelled_data();

        // Get the prediction samples file.
        if (empty($samplesdata) || empty($samplesdata[$this->model->timesplitting])) {

            $result = new \stdClass();
            $result->status = self::NO_DATASET;
            $result->info = $this->get_analyser()->get_logs();
            return $result;
        }
        $samplesfile = $samplesdata[$this->model->timesplitting];

        // We need to throw an exception if we are trying to predict stuff that was already predicted.
        $params = array('modelid' => $this->model->id, 'action' => 'predicted', 'fileid' => $samplesfile->get_id());
        if ($predicted = $DB->get_record('analytics_used_files', $params)) {
            throw new \moodle_exception('erroralreadypredict', 'analytics', '', $samplesfile->get_id());
        }

        $indicatorcalculations = \core_analytics\dataset_manager::get_structured_data($samplesfile);

        // Prepare the results object.
        $result = new \stdClass();

        if ($this->is_static()) {
            // Prediction based on assumptions.
            $result->status = self::OK;
            $result->info = [];
            $result->predictions = $this->get_static_predictions($indicatorcalculations);

        } else {
            // Estimation and classification processes run on the machine learning backend side.
            if ($this->get_target()->is_linear()) {
                $predictorresult = $predictor->estimate($this->get_unique_id(), $samplesfile, $outputdir);
            } else {
                $predictorresult = $predictor->classify($this->get_unique_id(), $samplesfile, $outputdir);
            }
            $result->status = $predictorresult->status;
            $result->info = $predictorresult->info;
            $result->predictions = $this->format_predictor_predictions($predictorresult);
        }

        if ($result->status !== self::OK) {
            return $result;
        }

        if ($result->predictions) {
            $samplecontexts = $this->execute_prediction_callbacks($result->predictions, $indicatorcalculations);
        }

        if (!empty($samplecontexts) && $this->uses_insights()) {
            $this->trigger_insights($samplecontexts);
        }

        $this->flag_file_as_used($samplesfile, 'predicted');

        return $result;
    }

    /**
     * Formats the predictor results.
     *
     * @param array $predictorresult
     * @return array
     */
    private function format_predictor_predictions($predictorresult) {

        $predictions = array();
        if (!empty($predictorresult->predictions)) {
            foreach ($predictorresult->predictions as $sampleinfo) {

                // We parse each prediction.
                switch (count($sampleinfo)) {
                    case 1:
                        // For whatever reason the predictions processor could not process this sample, we
                        // skip it and do nothing with it.
                        debugging($this->model->id . ' model predictions processor could not process the sample with id ' .
                            $sampleinfo[0], DEBUG_DEVELOPER);
                        continue;
                    case 2:
                        // Prediction processors that do not return a prediction score will have the maximum prediction
                        // score.
                        list($uniquesampleid, $prediction) = $sampleinfo;
                        $predictionscore = 1;
                        break;
                    case 3:
                        list($uniquesampleid, $prediction, $predictionscore) = $sampleinfo;
                        break;
                    default:
                        break;
                }
                $predictiondata = (object)['prediction' => $prediction, 'predictionscore' => $predictionscore];
                $predictions[$uniquesampleid] = $predictiondata;
            }
        }
        return $predictions;
    }

    /**
     * Execute the prediction callbacks defined by the target.
     *
     * @param \stdClass[] $predictions
     * @param array $indicatorcalculations
     * @return array
     */
    protected function execute_prediction_callbacks($predictions, $indicatorcalculations) {

        // Here we will store all predictions' contexts, this will be used to limit which users will see those predictions.
        $samplecontexts = array();
        $records = array();

        foreach ($predictions as $uniquesampleid => $prediction) {

            // The unique sample id contains both the sampleid and the rangeindex.
            list($sampleid, $rangeindex) = $this->get_time_splitting()->infer_sample_info($uniquesampleid);

            if ($this->get_target()->triggers_callback($prediction->prediction, $prediction->predictionscore)) {

                // Prepare the record to store the predicted values.
                list($record, $samplecontext) = $this->prepare_prediction_record($sampleid, $rangeindex, $prediction->prediction,
                    $prediction->predictionscore, json_encode($indicatorcalculations[$uniquesampleid]));

                // We will later bulk-insert them all.
                $records[$uniquesampleid] = $record;

                // Also store all samples context to later generate insights or whatever action the target wants to perform.
                $samplecontexts[$samplecontext->id] = $samplecontext;

                $this->get_target()->prediction_callback($this->model->id, $sampleid, $rangeindex, $samplecontext,
                    $prediction->prediction, $prediction->predictionscore);
            }
        }

        if (!empty($records)) {
            $this->save_predictions($records);
        }

        return $samplecontexts;
    }

    /**
     * Generates insights and updates the cache.
     *
     * @param \context[] $samplecontexts
     * @return void
     */
    protected function trigger_insights($samplecontexts) {

        // Notify the target that all predictions have been processed.
        $this->get_target()->generate_insight_notifications($this->model->id, $samplecontexts);

        // Update cache.
        $cache = \cache::make('core', 'contextwithinsights');
        foreach ($samplecontexts as $context) {
            $modelids = $cache->get($context->id);
            if (!$modelids) {
                // The cache is empty, but we don't know if it is empty because there are no insights
                // in this context or because cache/s have been purged, we need to be conservative and
                // "pay" 1 db read to fill up the cache.
                $models = \core_analytics\manager::get_models_with_insights($context);
                $cache->set($context->id, array_keys($models));
            } else if (!in_array($this->get_id(), $modelids)) {
                array_push($modelids, $this->get_id());
                $cache->set($context->id, $modelids);
            }
        }
    }

    /**
     * Get predictions from a static model.
     *
     * @param array $indicatorcalculations
     * @return \stdClass[]
     */
    protected function get_static_predictions(&$indicatorcalculations) {

        // Group samples by analysable for \core_analytics\local\target::calculate.
        $analysables = array();
        // List all sampleids together.
        $sampleids = array();

        foreach ($indicatorcalculations as $uniquesampleid => $indicators) {
            list($sampleid, $rangeindex) = $this->get_time_splitting()->infer_sample_info($uniquesampleid);

            $analysable = $this->get_analyser()->get_sample_analysable($sampleid);
            $analysableclass = get_class($analysable);
            if (empty($analysables[$analysableclass])) {
                $analysables[$analysableclass] = array();
            }
            if (empty($analysables[$analysableclass][$rangeindex])) {
                $analysables[$analysableclass][$rangeindex] = (object)[
                    'analysable' => $analysable,
                    'indicatorsdata' => array(),
                    'sampleids' => array()
                ];
            }
            // Using the sampleid as a key so we can easily merge indicators data later.
            $analysables[$analysableclass][$rangeindex]->indicatorsdata[$sampleid] = $indicators;
            // We could use indicatorsdata keys but the amount of redundant data is not that big and leaves code below cleaner.
            $analysables[$analysableclass][$rangeindex]->sampleids[$sampleid] = $sampleid;

            // Accumulate sample ids to get all their associated data in 1 single db query (analyser::get_samples).
            $sampleids[$sampleid] = $sampleid;
        }

        // Get all samples data.
        list($sampleids, $samplesdata) = $this->get_analyser()->get_samples($sampleids);

        // Calculate the targets.
        $predictions = array();
        foreach ($analysables as $analysableclass => $rangedata) {
            foreach ($rangedata as $rangeindex => $data) {

                // Attach samples data and calculated indicators data.
                $this->get_target()->clear_sample_data();
                $this->get_target()->add_sample_data($samplesdata);
                $this->get_target()->add_sample_data($data->indicatorsdata);

                // Append new elements (we can not get duplicates because sample-analysable relation is N-1).
                $range = $this->get_time_splitting()->get_range_by_index($rangeindex);
                $this->get_target()->filter_out_invalid_samples($data->sampleids, $data->analysable, false);
                $calculations = $this->get_target()->calculate($data->sampleids, $data->analysable, $range['start'], $range['end']);

                // Missing $indicatorcalculations values in $calculations are caused by is_valid_sample. We need to remove
                // these $uniquesampleid from $indicatorcalculations because otherwise they will be stored as calculated
                // by self::save_prediction.
                $indicatorcalculations = array_filter($indicatorcalculations, function($indicators, $uniquesampleid) use ($calculations) {
                    list($sampleid, $rangeindex) = $this->get_time_splitting()->infer_sample_info($uniquesampleid);
                    if (!isset($calculations[$sampleid])) {
                        return false;
                    }
                    return true;
                }, ARRAY_FILTER_USE_BOTH);

                foreach ($calculations as $sampleid => $value) {

                    $uniquesampleid = $this->get_time_splitting()->append_rangeindex($sampleid, $rangeindex);

                    // Null means that the target couldn't calculate the sample, we also remove them from $indicatorcalculations.
                    if (is_null($calculations[$sampleid])) {
                        unset($indicatorcalculations[$uniquesampleid]);
                        continue;
                    }

                    // Even if static predictions are based on assumptions we flag them as 100% because they are 100%
                    // true according to what the developer defined.
                    $predictions[$uniquesampleid] = (object)['prediction' => $value, 'predictionscore' => 1];
                }
            }
        }
        return $predictions;
    }

    /**
     * Stores the prediction in the database.
     *
     * @param int $sampleid
     * @param int $rangeindex
     * @param int $prediction
     * @param float $predictionscore
     * @param string $calculations
     * @return \context
     */
    protected function prepare_prediction_record($sampleid, $rangeindex, $prediction, $predictionscore, $calculations) {
        $context = $this->get_analyser()->sample_access_context($sampleid);

        $record = new \stdClass();
        $record->modelid = $this->model->id;
        $record->contextid = $context->id;
        $record->sampleid = $sampleid;
        $record->rangeindex = $rangeindex;
        $record->prediction = $prediction;
        $record->predictionscore = $predictionscore;
        $record->calculations = $calculations;
        $record->timecreated = time();

        $analysable = $this->get_analyser()->get_sample_analysable($sampleid);
        $timesplitting = $this->get_time_splitting();
        $timesplitting->set_analysable($analysable);
        $range = $timesplitting->get_range_by_index($rangeindex);
        if ($range) {
            $record->timestart = $range['start'];
            $record->timeend = $range['end'];
        }

        return array($record, $context);
    }

    /**
     * Save the prediction objects.
     *
     * @param \stdClass[] $records
     */
    protected function save_predictions($records) {
        global $DB;
        $DB->insert_records('analytics_predictions', $records);
    }

    /**
     * Enabled the model using the provided time splitting method.
     *
     * @param string|false $timesplittingid False to respect the current time splitting method.
     * @return void
     */
    public function enable($timesplittingid = false) {
        global $DB, $USER;

        \core_analytics\manager::check_can_manage_models();

        $now = time();

        if ($timesplittingid && $timesplittingid !== $this->model->timesplitting) {

            if (!\core_analytics\manager::is_valid($timesplittingid, '\core_analytics\local\time_splitting\base')) {
                throw new \moodle_exception('errorinvalidtimesplitting', 'analytics');
            }

            if (substr($timesplittingid, 0, 1) !== '\\') {
                throw new \moodle_exception('errorinvalidtimesplitting', 'analytics');
            }

            // Delete generated predictions before changing the model version.
            $this->clear();

            // It needs to be reset as the version changes.
            $this->uniqueid = null;

            $this->model->timesplitting = $timesplittingid;
            $this->model->version = $now;

            // Reset trained flag.
            if (!$this->is_static()) {
                $this->model->trained = 0;
            }
        } else if (empty($this->model->timesplitting)) {
            // A valid timesplitting method needs to be supplied before a model can be enabled.
            throw new \moodle_exception('invalidtimesplitting', 'analytics', '', $this->model->id);

        }

        // Purge pages with insights as this may change things.
        if ($this->model->enabled != 1) {
            $this->purge_insights_cache();
        }

        $this->model->enabled = 1;
        $this->model->timemodified = $now;
        $this->model->usermodified = $USER->id;

        // We don't always update timemodified intentionally as we reserve it for target, indicators or timesplitting updates.
        $DB->update_record('analytics_models', $this->model);
    }

    /**
     * Is this a static model (as defined by the target)?.
     *
     * Static models are based on assumptions instead of in machine learning
     * backends results.
     *
     * @return bool
     */
    public function is_static() {
        return (bool)$this->get_target()->based_on_assumptions();
    }

    /**
     * Is this model enabled?
     *
     * @return bool
     */
    public function is_enabled() {
        return (bool)$this->model->enabled;
    }

    /**
     * Is this model already trained?
     *
     * @return bool
     */
    public function is_trained() {
        // Models which targets are based on assumptions do not need training.
        return (bool)$this->model->trained || $this->is_static();
    }

    /**
     * Marks the model as trained
     *
     * @return void
     */
    public function mark_as_trained() {
        global $DB;

        \core_analytics\manager::check_can_manage_models();

        $this->model->trained = 1;
        $DB->update_record('analytics_models', $this->model);
    }

    /**
     * Get the contexts with predictions.
     *
     * @param bool $skiphidden Skip hidden predictions
     * @return \stdClass[]
     */
    public function get_predictions_contexts($skiphidden = true) {
        global $DB, $USER;

        $sql = "SELECT DISTINCT ap.contextid FROM {analytics_predictions} ap
                  JOIN {context} ctx ON ctx.id = ap.contextid
                 WHERE ap.modelid = :modelid";
        $params = array('modelid' => $this->model->id);

        if ($skiphidden) {
            $sql .= " AND NOT EXISTS (
              SELECT 1
                FROM {analytics_prediction_actions} apa
               WHERE apa.predictionid = ap.id AND apa.userid = :userid AND (apa.actionname = :fixed OR apa.actionname = :notuseful)
            )";
            $params['userid'] = $USER->id;
            $params['fixed'] = \core_analytics\prediction::ACTION_FIXED;
            $params['notuseful'] = \core_analytics\prediction::ACTION_NOT_USEFUL;
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Has this model generated predictions?
     *
     * We don't check analytics_predictions table because targets have the ability to
     * ignore some predicted values, if that is the case predictions are not even stored
     * in db.
     *
     * @return bool
     */
    public function any_prediction_obtained() {
        global $DB;
        return $DB->record_exists('analytics_predict_samples',
            array('modelid' => $this->model->id, 'timesplitting' => $this->model->timesplitting));
    }

    /**
     * Whether this model generates insights or not (defined by the model's target).
     *
     * @return bool
     */
    public function uses_insights() {
        $target = $this->get_target();
        return $target::uses_insights();
    }

    /**
     * Whether predictions exist for this context.
     *
     * @param \context $context
     * @return bool
     */
    public function predictions_exist(\context $context) {
        global $DB;

        // Filters out previous predictions keeping only the last time range one.
        $select = "modelid = :modelid AND contextid = :contextid";
        $params = array('modelid' => $this->model->id, 'contextid' => $context->id);
        return $DB->record_exists_select('analytics_predictions', $select, $params);
    }

    /**
     * Gets the predictions for this context.
     *
     * @param \context $context
     * @param bool $skiphidden Skip hidden predictions
     * @param int $page The page of results to fetch. False for all results.
     * @param int $perpage The max number of results to fetch. Ignored if $page is false.
     * @return array($total, \core_analytics\prediction[])
     */
    public function get_predictions(\context $context, $skiphidden = true, $page = false, $perpage = 100) {
        global $DB, $USER;

        \core_analytics\manager::check_can_list_insights($context);

        // Filters out previous predictions keeping only the last time range one.
        $sql = "SELECT ap.*
                  FROM {analytics_predictions} ap
                  JOIN (
                    SELECT sampleid, max(rangeindex) AS rangeindex
                      FROM {analytics_predictions}
                     WHERE modelid = :modelidsubap and contextid = :contextidsubap
                    GROUP BY sampleid
                  ) apsub
                  ON ap.sampleid = apsub.sampleid AND ap.rangeindex = apsub.rangeindex
                WHERE ap.modelid = :modelid and ap.contextid = :contextid";

        $params = array('modelid' => $this->model->id, 'contextid' => $context->id,
            'modelidsubap' => $this->model->id, 'contextidsubap' => $context->id);

        if ($skiphidden) {
            $sql .= " AND NOT EXISTS (
              SELECT 1
                FROM {analytics_prediction_actions} apa
               WHERE apa.predictionid = ap.id AND apa.userid = :userid AND (apa.actionname = :fixed OR apa.actionname = :notuseful)
            )";
            $params['userid'] = $USER->id;
            $params['fixed'] = \core_analytics\prediction::ACTION_FIXED;
            $params['notuseful'] = \core_analytics\prediction::ACTION_NOT_USEFUL;
        }

        $sql .= " ORDER BY ap.timecreated DESC";
        if (!$predictions = $DB->get_records_sql($sql, $params)) {
            return array();
        }

        // Get predicted samples' ids.
        $sampleids = array_map(function($prediction) {
            return $prediction->sampleid;
        }, $predictions);

        list($unused, $samplesdata) = $this->get_analyser()->get_samples($sampleids);

        $current = 0;

        if ($page !== false) {
            $offset = $page * $perpage;
            $limit = $offset + $perpage;
        }

        foreach ($predictions as $predictionid => $predictiondata) {

            $sampleid = $predictiondata->sampleid;

            // Filter out predictions which samples are not available anymore.
            if (empty($samplesdata[$sampleid])) {
                unset($predictions[$predictionid]);
                continue;
            }

            // Return paginated dataset - we cannot paginate in the DB because we post filter the list.
            if ($page === false || ($current >= $offset && $current < $limit)) {
                // Replace \stdClass object by \core_analytics\prediction objects.
                $prediction = new \core_analytics\prediction($predictiondata, $samplesdata[$sampleid]);
                $predictions[$predictionid] = $prediction;
            } else {
                unset($predictions[$predictionid]);
            }

            $current++;
        }

        return [$current, $predictions];
    }

    /**
     * Returns the sample data of a prediction.
     *
     * @param \stdClass $predictionobj
     * @return array
     */
    public function prediction_sample_data($predictionobj) {

        list($unused, $samplesdata) = $this->get_analyser()->get_samples(array($predictionobj->sampleid));

        if (empty($samplesdata[$predictionobj->sampleid])) {
            throw new \moodle_exception('errorsamplenotavailable', 'analytics');
        }

        return $samplesdata[$predictionobj->sampleid];
    }

    /**
     * Returns the description of a sample
     *
     * @param \core_analytics\prediction $prediction
     * @return array 2 elements: list(string, \renderable)
     */
    public function prediction_sample_description(\core_analytics\prediction $prediction) {
        return $this->get_analyser()->sample_description($prediction->get_prediction_data()->sampleid,
            $prediction->get_prediction_data()->contextid, $prediction->get_sample_data());
    }

    /**
     * Returns the output directory for prediction processors.
     *
     * Directory structure as follows:
     * - Evaluation runs:
     *   models/$model->id/$model->version/evaluation/$model->timesplitting
     * - Training  & prediction runs:
     *   models/$model->id/$model->version/execution
     *
     * @param array $subdirs
     * @param bool $onlymodelid Preference over $subdirs
     * @return string
     */
    protected function get_output_dir($subdirs = array(), $onlymodelid = false) {
        global $CFG;

        $subdirstr = '';
        foreach ($subdirs as $subdir) {
            $subdirstr .= DIRECTORY_SEPARATOR . $subdir;
        }

        $outputdir = get_config('analytics', 'modeloutputdir');
        if (empty($outputdir)) {
            // Apply default value.
            $outputdir = rtrim($CFG->dataroot, '/') . DIRECTORY_SEPARATOR . 'models';
        }

        // Append model id.
        $outputdir .= DIRECTORY_SEPARATOR . $this->model->id;
        if (!$onlymodelid) {
            // Append version + subdirs.
            $outputdir .= DIRECTORY_SEPARATOR . $this->model->version . $subdirstr;
        }

        make_writable_directory($outputdir);

        return $outputdir;
    }

    /**
     * Returns a unique id for this model.
     *
     * This id should be unique for this site.
     *
     * @return string
     */
    public function get_unique_id() {
        global $CFG;

        if (!is_null($this->uniqueid)) {
            return $this->uniqueid;
        }

        // Generate a unique id for this site, this model and this time splitting method, considering the last time
        // that the model target and indicators were updated.
        $ids = array($CFG->wwwroot, $CFG->prefix, $this->model->id, $this->model->version);
        $this->uniqueid = sha1(implode('$$', $ids));

        return $this->uniqueid;
    }

    /**
     * Exports the model data.
     *
     * @return \stdClass
     */
    public function export() {

        \core_analytics\manager::check_can_manage_models();

        $data = clone $this->model;
        $data->target = $this->get_target()->get_name();

        if ($timesplitting = $this->get_time_splitting()) {
            $data->timesplitting = $timesplitting->get_name();
        }

        $data->indicators = array();
        foreach ($this->get_indicators() as $indicator) {
            $data->indicators[] = $indicator->get_name();
        }
        return $data;
    }

    /**
     * Returns the model logs data.
     *
     * @param int $limitfrom
     * @param int $limitnum
     * @return \stdClass[]
     */
    public function get_logs($limitfrom = 0, $limitnum = 0) {
        global $DB;

        \core_analytics\manager::check_can_manage_models();

        return $DB->get_records('analytics_models_log', array('modelid' => $this->get_id()), 'timecreated DESC', '*',
            $limitfrom, $limitnum);
    }

    /**
     * Merges all training data files into one and returns it.
     *
     * @return \stored_file|false
     */
    public function get_training_data() {

        \core_analytics\manager::check_can_manage_models();

        $timesplittingid = $this->get_time_splitting()->get_id();
        return \core_analytics\dataset_manager::export_training_data($this->get_id(), $timesplittingid);
    }

    /**
     * Flag the provided file as used for training or prediction.
     *
     * @param \stored_file $file
     * @param string $action
     * @return void
     */
    protected function flag_file_as_used(\stored_file $file, $action) {
        global $DB;

        $usedfile = new \stdClass();
        $usedfile->modelid = $this->model->id;
        $usedfile->fileid = $file->get_id();
        $usedfile->action = $action;
        $usedfile->time = time();
        $DB->insert_record('analytics_used_files', $usedfile);
    }

    /**
     * Log the evaluation results in the database.
     *
     * @param string $timesplittingid
     * @param float $score
     * @param string $dir
     * @param array $info
     * @return int The inserted log id
     */
    protected function log_result($timesplittingid, $score, $dir = false, $info = false) {
        global $DB, $USER;

        $log = new \stdClass();
        $log->modelid = $this->get_id();
        $log->version = $this->model->version;
        $log->target = $this->model->target;
        $log->indicators = $this->model->indicators;
        $log->timesplitting = $timesplittingid;
        $log->dir = $dir;
        if ($info) {
            // Ensure it is not an associative array.
            $log->info = json_encode(array_values($info));
        }
        $log->score = $score;
        $log->timecreated = time();
        $log->usermodified = $USER->id;

        return $DB->insert_record('analytics_models_log', $log);
    }

    /**
     * Utility method to return indicator class names from a list of indicator objects
     *
     * @param \core_analytics\local\indicator\base[] $indicators
     * @return string[]
     */
    private static function indicator_classes($indicators) {

        // What we want to check and store are the indicator classes not the keys.
        $indicatorclasses = array();
        foreach ($indicators as $indicator) {
            if (!\core_analytics\manager::is_valid($indicator, '\core_analytics\local\indicator\base')) {
                if (!is_object($indicator) && !is_scalar($indicator)) {
                    $indicator = strval($indicator);
                } else if (is_object($indicator)) {
                    $indicator = '\\' . get_class($indicator);
                }
                throw new \moodle_exception('errorinvalidindicator', 'analytics', '', $indicator);
            }
            $indicatorclasses[] = $indicator->get_id();
        }

        return $indicatorclasses;
    }

    /**
     * Clears the model training and prediction data.
     *
     * Executed after updating model critical elements like the time splitting method
     * or the indicators.
     *
     * @return void
     */
    public function clear() {
        global $DB, $USER;

        \core_analytics\manager::check_can_manage_models();

        // Delete current model version stored stuff.
        $predictor = \core_analytics\manager::get_predictions_processor();
        $predictor->clear_model($this->get_unique_id(), $this->get_output_dir());

        $predictionids = $DB->get_fieldset_select('analytics_predictions', 'id', 'modelid = :modelid',
            array('modelid' => $this->get_id()));
        if ($predictionids) {
            list($sql, $params) = $DB->get_in_or_equal($predictionids);
            $DB->delete_records_select('analytics_prediction_actions', "predictionid $sql", $params);
        }

        $DB->delete_records('analytics_predictions', array('modelid' => $this->model->id));
        $DB->delete_records('analytics_predict_samples', array('modelid' => $this->model->id));
        $DB->delete_records('analytics_train_samples', array('modelid' => $this->model->id));
        $DB->delete_records('analytics_used_files', array('modelid' => $this->model->id));
        $DB->delete_records('analytics_used_analysables', array('modelid' => $this->model->id));

        // Purge all generated files.
        \core_analytics\dataset_manager::clear_model_files($this->model->id);

        // We don't expect people to clear models regularly and the cost of filling the cache is
        // 1 db read per context.
        $this->purge_insights_cache();

        $this->model->trained = 0;
        $this->model->timemodified = time();
        $this->model->usermodified = $USER->id;
        $DB->update_record('analytics_models', $this->model);
    }

    /**
     * Purges the insights cache.
     */
    private function purge_insights_cache() {
        $cache = \cache::make('core', 'contextwithinsights');
        $cache->purge();
    }

    /**
     * Increases system memory and time limits.
     *
     * @return void
     */
    private function heavy_duty_mode() {
        if (ini_get('memory_limit') != -1) {
            raise_memory_limit(MEMORY_HUGE);
        }
        \core_php_time_limit::raise();
    }
}
