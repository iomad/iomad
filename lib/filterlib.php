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
 * Library functions for managing text filter plugins.
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** The states a filter can be in, stored in the filter_active table. */
define('TEXTFILTER_ON', 1);
/** The states a filter can be in, stored in the filter_active table. */
define('TEXTFILTER_INHERIT', 0);
/** The states a filter can be in, stored in the filter_active table. */
define('TEXTFILTER_OFF', -1);
/** The states a filter can be in, stored in the filter_active table. */
define('TEXTFILTER_DISABLED', -9999);

/**
 * Define one exclusive separator that we'll use in the temp saved tags
 *  keys. It must be something rare enough to avoid having matches with
 *  filterobjects. MDL-18165
 */
define('TEXTFILTER_EXCL_SEPARATOR', '-%-');


/**
 * Class to manage the filtering of strings. It is intended that this class is
 * only used by weblib.php. Client code should probably be using the
 * format_text and format_string functions.
 *
 * This class is a singleton.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_manager {
    /**
     * @var moodle_text_filter[][] This list of active filters, by context, for filtering content.
     * An array contextid => ordered array of filter name => filter objects.
     */
    protected $textfilters = array();

    /**
     * @var moodle_text_filter[][] This list of active filters, by context, for filtering strings.
     * An array contextid => ordered array of filter name => filter objects.
     */
    protected $stringfilters = array();

    /** @var array Exploded version of $CFG->stringfilters. */
    protected $stringfilternames = array();

    /** @var filter_manager Holds the singleton instance. */
    protected static $singletoninstance;

    /**
     * Constructor. Protected. Use {@link instance()} instead.
     */
    protected function __construct() {
        $this->stringfilternames = filter_get_string_filters();
    }

    /**
     * Factory method. Use this to get the filter manager.
     *
     * @return filter_manager the singleton instance.
     */
    public static function instance() {
        global $CFG;
        if (is_null(self::$singletoninstance)) {
            if (!empty($CFG->perfdebug) and $CFG->perfdebug > 7) {
                self::$singletoninstance = new performance_measuring_filter_manager();
            } else {
                self::$singletoninstance = new self();
            }
        }
        return self::$singletoninstance;
    }

    /**
     * Resets the caches, usually to be called between unit tests
     */
    public static function reset_caches() {
        if (self::$singletoninstance) {
            self::$singletoninstance->unload_all_filters();
        }
        self::$singletoninstance = null;
    }

    /**
     * Unloads all filters and other cached information
     */
    protected function unload_all_filters() {
        $this->textfilters = array();
        $this->stringfilters = array();
        $this->stringfilternames = array();
    }

    /**
     * Load all the filters required by this context.
     *
     * @param context $context the context.
     */
    protected function load_filters($context) {
        $filters = filter_get_active_in_context($context);
        $this->textfilters[$context->id] = array();
        $this->stringfilters[$context->id] = array();
        foreach ($filters as $filtername => $localconfig) {
            $filter = $this->make_filter_object($filtername, $context, $localconfig);
            if (is_null($filter)) {
                continue;
            }
            $this->textfilters[$context->id][$filtername] = $filter;
            if (in_array($filtername, $this->stringfilternames)) {
                $this->stringfilters[$context->id][$filtername] = $filter;
            }
        }
    }

    /**
     * Factory method for creating a filter.
     *
     * @param string $filtername The filter name, for example 'tex'.
     * @param context $context context object.
     * @param array $localconfig array of local configuration variables for this filter.
     * @return moodle_text_filter The filter, or null, if this type of filter is
     *      not recognised or could not be created.
     */
    protected function make_filter_object($filtername, $context, $localconfig) {
        global $CFG;
        $path = $CFG->dirroot .'/filter/'. $filtername .'/filter.php';
        if (!is_readable($path)) {
            return null;
        }
        include_once($path);

        $filterclassname = 'filter_' . $filtername;
        if (class_exists($filterclassname)) {
            return new $filterclassname($context, $localconfig);
        }

        return null;
    }

    /**
     * Apply a list of filters to some content.
     * @param string $text
     * @param moodle_text_filter[] $filterchain array filter name => filter object.
     * @param array $options options passed to the filters.
     * @param array $skipfilters of filter names. Any filters that should not be applied to this text.
     * @return string $text
     */
    protected function apply_filter_chain($text, $filterchain, array $options = array(),
            array $skipfilters = null) {
        foreach ($filterchain as $filtername => $filter) {
            if ($skipfilters !== null && in_array($filtername, $skipfilters)) {
                continue;
            }
            $text = $filter->filter($text, $options);
        }
        return $text;
    }

    /**
     * Get all the filters that apply to a given context for calls to format_text.
     *
     * @param context $context
     * @return moodle_text_filter[] A text filter
     */
    protected function get_text_filters($context) {
        if (!isset($this->textfilters[$context->id])) {
            $this->load_filters($context);
        }
        return $this->textfilters[$context->id];
    }

    /**
     * Get all the filters that apply to a given context for calls to format_string.
     *
     * @param context $context the context.
     * @return moodle_text_filter[] A text filter
     */
    protected function get_string_filters($context) {
        if (!isset($this->stringfilters[$context->id])) {
            $this->load_filters($context);
        }
        return $this->stringfilters[$context->id];
    }

    /**
     * Filter some text
     *
     * @param string $text The text to filter
     * @param context $context the context.
     * @param array $options options passed to the filters
     * @param array $skipfilters of filter names. Any filters that should not be applied to this text.
     * @return string resulting text
     */
    public function filter_text($text, $context, array $options = array(),
            array $skipfilters = null) {
        $text = $this->apply_filter_chain($text, $this->get_text_filters($context), $options, $skipfilters);
        // <nolink> tags removed for XHTML compatibility
        $text = str_replace(array('<nolink>', '</nolink>'), '', $text);
        return $text;
    }

    /**
     * Filter a piece of string
     *
     * @param string $string The text to filter
     * @param context $context the context.
     * @return string resulting string
     */
    public function filter_string($string, $context) {
        return $this->apply_filter_chain($string, $this->get_string_filters($context));
    }

    /**
     * @deprecated Since Moodle 3.0 MDL-50491. This was used by the old text filtering system, but no more.
     */
    public function text_filtering_hash() {
        throw new coding_exception('filter_manager::text_filtering_hash() can not be used any more');
    }

    /**
     * Setup page with filters requirements and other prepare stuff.
     *
     * This method is used by {@see format_text()} and {@see format_string()}
     * in order to allow filters to setup any page requirement (js, css...)
     * or perform any action needed to get them prepared before filtering itself
     * happens by calling to each every active setup() method.
     *
     * Note it's executed for each piece of text filtered, so filter implementations
     * are responsible of controlling the cardinality of the executions that may
     * be different depending of the stuff to prepare.
     *
     * @param moodle_page $page the page we are going to add requirements to.
     * @param context $context the context which contents are going to be filtered.
     * @since Moodle 2.3
     */
    public function setup_page_for_filters($page, $context) {
        $filters = $this->get_text_filters($context);
        foreach ($filters as $filter) {
            $filter->setup($page, $context);
        }
    }

    /**
     * Setup the page for globally available filters.
     *
     * This helps setting up the page for filters which may be applied to
     * the page, even if they do not belong to the current context, or are
     * not yet visible because the content is lazily added (ajax). This method
     * always uses to the system context which determines the globally
     * available filters.
     *
     * This should only ever be called once per request.
     *
     * @param moodle_page $page The page.
     * @since Moodle 3.2
     */
    public function setup_page_for_globally_available_filters($page) {
        $context = context_system::instance();
        $filterdata = filter_get_globally_enabled_filters_with_config();
        foreach ($filterdata as $name => $config) {
            if (isset($this->textfilters[$context->id][$name])) {
                $filter = $this->textfilters[$context->id][$name];
            } else {
                $filter = $this->make_filter_object($name, $context, $config);
                if (is_null($filter)) {
                    continue;
                }
            }
            $filter->setup($page, $context);
        }
    }
}


/**
 * Filter manager subclass that does nothing. Having this simplifies the logic
 * of format_text, etc.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class null_filter_manager {
    public function filter_text($text, $context, array $options = array(),
            array $skipfilters = null) {
        return $text;
    }

    public function filter_string($string, $context) {
        return $string;
    }

    public function text_filtering_hash() {
        throw new coding_exception('filter_manager::text_filtering_hash() can not be used any more');
    }
}


/**
 * Filter manager subclass that tracks how much work it does.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class performance_measuring_filter_manager extends filter_manager {
    /** @var int number of filter objects created. */
    protected $filterscreated = 0;

    /** @var int number of calls to filter_text. */
    protected $textsfiltered = 0;

    /** @var int number of calls to filter_string. */
    protected $stringsfiltered = 0;

    protected function unload_all_filters() {
        parent::unload_all_filters();
        $this->filterscreated = 0;
        $this->textsfiltered = 0;
        $this->stringsfiltered = 0;
    }

    protected function make_filter_object($filtername, $context, $localconfig) {
        $this->filterscreated++;
        return parent::make_filter_object($filtername, $context, $localconfig);
    }

    public function filter_text($text, $context, array $options = array(),
            array $skipfilters = null) {
        $this->textsfiltered++;
        return parent::filter_text($text, $context, $options, $skipfilters);
    }

    public function filter_string($string, $context) {
        $this->stringsfiltered++;
        return parent::filter_string($string, $context);
    }

    /**
     * Return performance information, in the form required by {@link get_performance_info()}.
     * @return array the performance info.
     */
    public function get_performance_summary() {
        return array(array(
            'contextswithfilters' => count($this->textfilters),
            'filterscreated' => $this->filterscreated,
            'textsfiltered' => $this->textsfiltered,
            'stringsfiltered' => $this->stringsfiltered,
        ), array(
            'contextswithfilters' => 'Contexts for which filters were loaded',
            'filterscreated' => 'Filters created',
            'textsfiltered' => 'Pieces of content filtered',
            'stringsfiltered' => 'Strings filtered',
        ));
    }
}


/**
 * Base class for text filters. You just need to override this class and
 * implement the filter method.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class moodle_text_filter {
    /** @var context The context we are in. */
    protected $context;

    /** @var array Any local configuration for this filter in this context. */
    protected $localconfig;

    /**
     * Set any context-specific configuration for this filter.
     *
     * @param context $context The current context.
     * @param array $localconfig Any context-specific configuration for this filter.
     */
    public function __construct($context, array $localconfig) {
        $this->context = $context;
        $this->localconfig = $localconfig;
    }

    /**
     * @deprecated Since Moodle 3.0 MDL-50491. This was used by the old text filtering system, but no more.
     */
    public function hash() {
        throw new coding_exception('moodle_text_filter::hash() can not be used any more');
    }

    /**
     * Setup page with filter requirements and other prepare stuff.
     *
     * Override this method if the filter needs to setup page
     * requirements or needs other stuff to be executed.
     *
     * Note this method is invoked from {@see setup_page_for_filters()}
     * for each piece of text being filtered, so it is responsible
     * for controlling its own execution cardinality.
     *
     * @param moodle_page $page the page we are going to add requirements to.
     * @param context $context the context which contents are going to be filtered.
     * @since Moodle 2.3
     */
    public function setup($page, $context) {
        // Override me, if needed.
    }

    /**
     * Override this function to actually implement the filtering.
     *
     * @param $text some HTML content.
     * @param array $options options passed to the filters
     * @return the HTML content after the filtering has been applied.
     */
    public abstract function filter($text, array $options = array());
}


/**
 * This is just a little object to define a phrase and some instructions
 * for how to process it.  Filters can create an array of these to pass
 * to the filter_phrases function below.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class filterobject {
    /** @var string */
    var $phrase;
    var $hreftagbegin;
    var $hreftagend;
    /** @var bool */
    var $casesensitive;
    var $fullmatch;
    /** @var mixed */
    var $replacementphrase;
    var $work_phrase;
    var $work_hreftagbegin;
    var $work_hreftagend;
    var $work_casesensitive;
    var $work_fullmatch;
    var $work_replacementphrase;
    /** @var bool */
    var $work_calculated;

    /**
     * A constructor just because I like constructing
     *
     * @param string $phrase
     * @param string $hreftagbegin
     * @param string $hreftagend
     * @param bool $casesensitive
     * @param bool $fullmatch
     * @param mixed $replacementphrase
     */
    public function __construct($phrase, $hreftagbegin = '<span class="highlight">',
                                   $hreftagend = '</span>',
                                   $casesensitive = false,
                                   $fullmatch = false,
                                   $replacementphrase = NULL) {

        $this->phrase           = $phrase;
        $this->hreftagbegin     = $hreftagbegin;
        $this->hreftagend       = $hreftagend;
        $this->casesensitive    = $casesensitive;
        $this->fullmatch        = $fullmatch;
        $this->replacementphrase= $replacementphrase;
        $this->work_calculated  = false;

    }
}

/**
 * Look up the name of this filter
 *
 * @param string $filter the filter name
 * @return string the human-readable name for this filter.
 */
function filter_get_name($filter) {
    if (strpos($filter, 'filter/') === 0) {
        debugging("Old '$filter'' parameter used in filter_get_name()");
        $filter = substr($filter, 7);
    } else if (strpos($filter, '/') !== false) {
        throw new coding_exception('Unknown filter type ' . $filter);
    }

    if (get_string_manager()->string_exists('filtername', 'filter_' . $filter)) {
        return get_string('filtername', 'filter_' . $filter);
    } else {
        return $filter;
    }
}

/**
 * Get the names of all the filters installed in this Moodle.
 *
 * @return array path => filter name from the appropriate lang file. e.g.
 * array('tex' => 'TeX Notation');
 * sorted in alphabetical order of name.
 */
function filter_get_all_installed() {
    global $CFG;

    $filternames = array();
    foreach (core_component::get_plugin_list('filter') as $filter => $fulldir) {
        if (is_readable("$fulldir/filter.php")) {
            $filternames[$filter] = filter_get_name($filter);
        }
    }
    core_collator::asort($filternames);
    return $filternames;
}

/**
 * Set the global activated state for a text filter.
 *
 * @param string $filtername The filter name, for example 'tex'.
 * @param int $state One of the values TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_DISABLED.
 * @param int $move -1 means up, 0 means the same, 1 means down
 */
function filter_set_global_state($filtername, $state, $move = 0) {
    global $DB;

    // Check requested state is valid.
    if (!in_array($state, array(TEXTFILTER_ON, TEXTFILTER_OFF, TEXTFILTER_DISABLED))) {
        throw new coding_exception("Illegal option '$state' passed to filter_set_global_state. " .
                "Must be one of TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_DISABLED.");
    }

    if ($move > 0) {
        $move = 1;
    } else if ($move < 0) {
        $move = -1;
    }

    if (strpos($filtername, 'filter/') === 0) {
        //debugging("Old filtername '$filtername' parameter used in filter_set_global_state()", DEBUG_DEVELOPER);
        $filtername = substr($filtername, 7);
    } else if (strpos($filtername, '/') !== false) {
        throw new coding_exception("Invalid filter name '$filtername' used in filter_set_global_state()");
    }

    $transaction = $DB->start_delegated_transaction();

    $syscontext = context_system::instance();
    $filters = $DB->get_records('filter_active', array('contextid' => $syscontext->id), 'sortorder ASC');

    $on = array();
    $off = array();

    foreach($filters as $f) {
        if ($f->active == TEXTFILTER_DISABLED) {
            $off[$f->filter] = $f;
        } else {
            $on[$f->filter] = $f;
        }
    }

    // Update the state or add new record.
    if (isset($on[$filtername])) {
        $filter = $on[$filtername];
        if ($filter->active != $state) {
            add_to_config_log('filter_active', $filter->active, $state, $filtername);

            $filter->active = $state;
            $DB->update_record('filter_active', $filter);
            if ($filter->active == TEXTFILTER_DISABLED) {
                unset($on[$filtername]);
                $off = array($filter->filter => $filter) + $off;
            }

        }

    } else if (isset($off[$filtername])) {
        $filter = $off[$filtername];
        if ($filter->active != $state) {
            add_to_config_log('filter_active', $filter->active, $state, $filtername);

            $filter->active = $state;
            $DB->update_record('filter_active', $filter);
            if ($filter->active != TEXTFILTER_DISABLED) {
                unset($off[$filtername]);
                $on[$filter->filter] = $filter;
            }
        }

    } else {
        add_to_config_log('filter_active', '', $state, $filtername);

        $filter = new stdClass();
        $filter->filter    = $filtername;
        $filter->contextid = $syscontext->id;
        $filter->active    = $state;
        $filter->sortorder = 99999;
        $filter->id = $DB->insert_record('filter_active', $filter);

        $filters[$filter->id] = $filter;
        if ($state == TEXTFILTER_DISABLED) {
            $off[$filter->filter] = $filter;
        } else {
            $on[$filter->filter] = $filter;
        }
    }

    // Move only active.
    if ($move != 0 and isset($on[$filter->filter])) {
        $i = 1;
        foreach ($on as $f) {
            $f->newsortorder = $i;
            $i++;
        }

        $filter->newsortorder = $filter->newsortorder + $move;

        foreach ($on as $f) {
            if ($f->id == $filter->id) {
                continue;
            }
            if ($f->newsortorder == $filter->newsortorder) {
                if ($move == 1) {
                    $f->newsortorder = $f->newsortorder - 1;
                } else {
                    $f->newsortorder = $f->newsortorder + 1;
                }
            }
        }

        core_collator::asort_objects_by_property($on, 'newsortorder', core_collator::SORT_NUMERIC);
    }

    // Inactive are sorted by filter name.
    core_collator::asort_objects_by_property($off, 'filter', core_collator::SORT_NATURAL);

    // Update records if necessary.
    $i = 1;
    foreach ($on as $f) {
        if ($f->sortorder != $i) {
            $DB->set_field('filter_active', 'sortorder', $i, array('id'=>$f->id));
        }
        $i++;
    }
    foreach ($off as $f) {
        if ($f->sortorder != $i) {
            $DB->set_field('filter_active', 'sortorder', $i, array('id'=>$f->id));
        }
        $i++;
    }

    $transaction->allow_commit();
}

/**
 * @param string $filtername The filter name, for example 'tex'.
 * @return boolean is this filter allowed to be used on this site. That is, the
 *      admin has set the global 'active' setting to On, or Off, but available.
 */
function filter_is_enabled($filtername) {
    if (strpos($filtername, 'filter/') === 0) {
        //debugging("Old filtername '$filtername' parameter used in filter_is_enabled()", DEBUG_DEVELOPER);
        $filtername = substr($filtername, 7);
    } else if (strpos($filtername, '/') !== false) {
        throw new coding_exception("Invalid filter name '$filtername' used in filter_is_enabled()");
    }
    return array_key_exists($filtername, filter_get_globally_enabled());
}

/**
 * Return a list of all the filters that may be in use somewhere.
 *
 * @staticvar array $enabledfilters
 * @return array where the keys and values are both the filter name, like 'tex'.
 */
function filter_get_globally_enabled() {
    static $enabledfilters = null;
    if (is_null($enabledfilters)) {
        $filters = filter_get_global_states();
        $enabledfilters = array();
        foreach ($filters as $filter => $filerinfo) {
            if ($filerinfo->active != TEXTFILTER_DISABLED) {
                $enabledfilters[$filter] = $filter;
            }
        }
    }
    return $enabledfilters;
}

/**
 * Get the globally enabled filters.
 *
 * This returns the filters which could be used in any context. Essentially
 * the filters which are not disabled for the entire site.
 *
 * @return array Keys are filter names, and values the config.
 */
function filter_get_globally_enabled_filters_with_config() {
    global $DB;

    $sql = "SELECT f.filter, fc.name, fc.value
              FROM {filter_active} f
         LEFT JOIN {filter_config} fc
                ON fc.filter = f.filter
               AND fc.contextid = f.contextid
             WHERE f.contextid = :contextid
               AND f.active != :disabled
          ORDER BY f.sortorder";

    $rs = $DB->get_recordset_sql($sql, [
        'contextid' => context_system::instance()->id,
        'disabled' => TEXTFILTER_DISABLED
    ]);

    // Massage the data into the specified format to return.
    $filters = array();
    foreach ($rs as $row) {
        if (!isset($filters[$row->filter])) {
            $filters[$row->filter] = array();
        }
        if ($row->name !== null) {
            $filters[$row->filter][$row->name] = $row->value;
        }
    }
    $rs->close();

    return $filters;
}

/**
 * Return the names of the filters that should also be applied to strings
 * (when they are enabled).
 *
 * @return array where the keys and values are both the filter name, like 'tex'.
 */
function filter_get_string_filters() {
    global $CFG;
    $stringfilters = array();
    if (!empty($CFG->filterall) && !empty($CFG->stringfilters)) {
        $stringfilters = explode(',', $CFG->stringfilters);
        $stringfilters = array_combine($stringfilters, $stringfilters);
    }
    return $stringfilters;
}

/**
 * Sets whether a particular active filter should be applied to all strings by
 * format_string, or just used by format_text.
 *
 * @param string $filter The filter name, for example 'tex'.
 * @param boolean $applytostrings if true, this filter will apply to format_string
 *      and format_text, when it is enabled.
 */
function filter_set_applies_to_strings($filter, $applytostrings) {
    $stringfilters = filter_get_string_filters();
    $prevfilters = $stringfilters;
    $allfilters = core_component::get_plugin_list('filter');

    if ($applytostrings) {
        $stringfilters[$filter] = $filter;
    } else {
        unset($stringfilters[$filter]);
    }

    // Remove missing filters.
    foreach ($stringfilters as $filter) {
        if (!isset($allfilters[$filter])) {
            unset($stringfilters[$filter]);
        }
    }

    if ($prevfilters != $stringfilters) {
        set_config('stringfilters', implode(',', $stringfilters));
        set_config('filterall', !empty($stringfilters));
    }
}

/**
 * Set the local activated state for a text filter.
 *
 * @param string $filter The filter name, for example 'tex'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param integer $state One of the values TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_INHERIT.
 * @return void
 */
function filter_set_local_state($filter, $contextid, $state) {
    global $DB;

    // Check requested state is valid.
    if (!in_array($state, array(TEXTFILTER_ON, TEXTFILTER_OFF, TEXTFILTER_INHERIT))) {
        throw new coding_exception("Illegal option '$state' passed to filter_set_local_state. " .
                "Must be one of TEXTFILTER_ON, TEXTFILTER_OFF or TEXTFILTER_INHERIT.");
    }

    if ($contextid == context_system::instance()->id) {
        throw new coding_exception('You cannot use filter_set_local_state ' .
                'with $contextid equal to the system context id.');
    }

    if ($state == TEXTFILTER_INHERIT) {
        $DB->delete_records('filter_active', array('filter' => $filter, 'contextid' => $contextid));
        return;
    }

    $rec = $DB->get_record('filter_active', array('filter' => $filter, 'contextid' => $contextid));
    $insert = false;
    if (empty($rec)) {
        $insert = true;
        $rec = new stdClass;
        $rec->filter = $filter;
        $rec->contextid = $contextid;
    }

    $rec->active = $state;

    if ($insert) {
        $DB->insert_record('filter_active', $rec);
    } else {
        $DB->update_record('filter_active', $rec);
    }
}

/**
 * Set a particular local config variable for a filter in a context.
 *
 * @param string $filter The filter name, for example 'tex'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param string $name the setting name.
 * @param string $value the corresponding value.
 */
function filter_set_local_config($filter, $contextid, $name, $value) {
    global $DB;
    $rec = $DB->get_record('filter_config', array('filter' => $filter, 'contextid' => $contextid, 'name' => $name));
    $insert = false;
    if (empty($rec)) {
        $insert = true;
        $rec = new stdClass;
        $rec->filter = $filter;
        $rec->contextid = $contextid;
        $rec->name = $name;
    }

    $rec->value = $value;

    if ($insert) {
        $DB->insert_record('filter_config', $rec);
    } else {
        $DB->update_record('filter_config', $rec);
    }
}

/**
 * Remove a particular local config variable for a filter in a context.
 *
 * @param string $filter The filter name, for example 'tex'.
 * @param integer $contextid The id of the context to get the local config for.
 * @param string $name the setting name.
 */
function filter_unset_local_config($filter, $contextid, $name) {
    global $DB;
    $DB->delete_records('filter_config', array('filter' => $filter, 'contextid' => $contextid, 'name' => $name));
}

/**
 * Get local config variables for a filter in a context. Normally (when your
 * filter is running) you don't need to call this, becuase the config is fetched
 * for you automatically. You only need this, for example, when you are getting
 * the config so you can show the user an editing from.
 *
 * @param string $filter The filter name, for example 'tex'.
 * @param integer $contextid The ID of the context to get the local config for.
 * @return array of name => value pairs.
 */
function filter_get_local_config($filter, $contextid) {
    global $DB;
    return $DB->get_records_menu('filter_config', array('filter' => $filter, 'contextid' => $contextid), '', 'name,value');
}

/**
 * This function is for use by backup. Gets all the filter information specific
 * to one context.
 *
 * @param int $contextid
 * @return array Array with two elements. The first element is an array of objects with
 *      fields filter and active. These come from the filter_active table. The
 *      second element is an array of objects with fields filter, name and value
 *      from the filter_config table.
 */
function filter_get_all_local_settings($contextid) {
    global $DB;
    return array(
        $DB->get_records('filter_active', array('contextid' => $contextid), 'filter', 'filter,active'),
        $DB->get_records('filter_config', array('contextid' => $contextid), 'filter,name', 'filter,name,value'),
    );
}

/**
 * Get the list of active filters, in the order that they should be used
 * for a particular context, along with any local configuration variables.
 *
 * @param context $context a context
 * @return array an array where the keys are the filter names, for example
 *      'tex' and the values are any local
 *      configuration for that filter, as an array of name => value pairs
 *      from the filter_config table. In a lot of cases, this will be an
 *      empty array. So, an example return value for this function might be
 *      array(tex' => array())
 */
function filter_get_active_in_context($context) {
    global $DB, $FILTERLIB_PRIVATE;

    if (!isset($FILTERLIB_PRIVATE)) {
        $FILTERLIB_PRIVATE = new stdClass();
    }

    // Use cache (this is a within-request cache only) if available. See
    // function filter_preload_activities.
    if (isset($FILTERLIB_PRIVATE->active) &&
            array_key_exists($context->id, $FILTERLIB_PRIVATE->active)) {
        return $FILTERLIB_PRIVATE->active[$context->id];
    }

    $contextids = str_replace('/', ',', trim($context->path, '/'));

    // The following SQL is tricky. It is explained on
    // http://docs.moodle.org/dev/Filter_enable/disable_by_context
    $sql = "SELECT active.filter, fc.name, fc.value
         FROM (SELECT f.filter, MAX(f.sortorder) AS sortorder
             FROM {filter_active} f
             JOIN {context} ctx ON f.contextid = ctx.id
             WHERE ctx.id IN ($contextids)
             GROUP BY filter
             HAVING MAX(f.active * ctx.depth) > -MIN(f.active * ctx.depth)
         ) active
         LEFT JOIN {filter_config} fc ON fc.filter = active.filter AND fc.contextid = $context->id
         ORDER BY active.sortorder";
    $rs = $DB->get_recordset_sql($sql);

    // Massage the data into the specified format to return.
    $filters = array();
    foreach ($rs as $row) {
        if (!isset($filters[$row->filter])) {
            $filters[$row->filter] = array();
        }
        if (!is_null($row->name)) {
            $filters[$row->filter][$row->name] = $row->value;
        }
    }

    $rs->close();

    return $filters;
}

/**
 * Preloads the list of active filters for all activities (modules) on the course
 * using two database queries.
 *
 * @param course_modinfo $modinfo Course object from get_fast_modinfo
 */
function filter_preload_activities(course_modinfo $modinfo) {
    global $DB, $FILTERLIB_PRIVATE;

    if (!isset($FILTERLIB_PRIVATE)) {
        $FILTERLIB_PRIVATE = new stdClass();
    }

    // Don't repeat preload
    if (!isset($FILTERLIB_PRIVATE->preloaded)) {
        $FILTERLIB_PRIVATE->preloaded = array();
    }
    if (!empty($FILTERLIB_PRIVATE->preloaded[$modinfo->get_course_id()])) {
        return;
    }
    $FILTERLIB_PRIVATE->preloaded[$modinfo->get_course_id()] = true;

    // Get contexts for all CMs
    $cmcontexts = array();
    $cmcontextids = array();
    foreach ($modinfo->get_cms() as $cm) {
        $modulecontext = context_module::instance($cm->id);
        $cmcontextids[] = $modulecontext->id;
        $cmcontexts[] = $modulecontext;
    }

    // Get course context and all other parents...
    $coursecontext = context_course::instance($modinfo->get_course_id());
    $parentcontextids = explode('/', substr($coursecontext->path, 1));
    $allcontextids = array_merge($cmcontextids, $parentcontextids);

    // Get all filter_active rows relating to all these contexts
    list ($sql, $params) = $DB->get_in_or_equal($allcontextids);
    $filteractives = $DB->get_records_select('filter_active', "contextid $sql", $params, 'sortorder');

    // Get all filter_config only for the cm contexts
    list ($sql, $params) = $DB->get_in_or_equal($cmcontextids);
    $filterconfigs = $DB->get_records_select('filter_config', "contextid $sql", $params);

    // Note: I was a bit surprised that filter_config only works for the
    // most specific context (i.e. it does not need to be checked for course
    // context if we only care about CMs) however basede on code in
    // filter_get_active_in_context, this does seem to be correct.

    // Build course default active list. Initially this will be an array of
    // filter name => active score (where an active score >0 means it's active)
    $courseactive = array();

    // Also build list of filter_active rows below course level, by contextid
    $remainingactives = array();

    // Array lists filters that are banned at top level
    $banned = array();

    // Add any active filters in parent contexts to the array
    foreach ($filteractives as $row) {
        $depth = array_search($row->contextid, $parentcontextids);
        if ($depth !== false) {
            // Find entry
            if (!array_key_exists($row->filter, $courseactive)) {
                $courseactive[$row->filter] = 0;
            }
            // This maths copes with reading rows in any order. Turning on/off
            // at site level counts 1, at next level down 4, at next level 9,
            // then 16, etc. This means the deepest level always wins, except
            // against the -9999 at top level.
            $courseactive[$row->filter] +=
                ($depth + 1) * ($depth + 1) * $row->active;

            if ($row->active == TEXTFILTER_DISABLED) {
                $banned[$row->filter] = true;
            }
        } else {
            // Build list of other rows indexed by contextid
            if (!array_key_exists($row->contextid, $remainingactives)) {
                $remainingactives[$row->contextid] = array();
            }
            $remainingactives[$row->contextid][] = $row;
        }
    }

    // Chuck away the ones that aren't active.
    foreach ($courseactive as $filter=>$score) {
        if ($score <= 0) {
            unset($courseactive[$filter]);
        } else {
            $courseactive[$filter] = array();
        }
    }

    // Loop through the contexts to reconstruct filter_active lists for each
    // cm on the course.
    if (!isset($FILTERLIB_PRIVATE->active)) {
        $FILTERLIB_PRIVATE->active = array();
    }
    foreach ($cmcontextids as $contextid) {
        // Copy course list
        $FILTERLIB_PRIVATE->active[$contextid] = $courseactive;

        // Are there any changes to the active list?
        if (array_key_exists($contextid, $remainingactives)) {
            foreach ($remainingactives[$contextid] as $row) {
                if ($row->active > 0 && empty($banned[$row->filter])) {
                    // If it's marked active for specific context, add entry
                    // (doesn't matter if one exists already).
                    $FILTERLIB_PRIVATE->active[$contextid][$row->filter] = array();
                } else {
                    // If it's marked inactive, remove entry (doesn't matter
                    // if it doesn't exist).
                    unset($FILTERLIB_PRIVATE->active[$contextid][$row->filter]);
                }
            }
        }
    }

    // Process all config rows to add config data to these entries.
    foreach ($filterconfigs as $row) {
        if (isset($FILTERLIB_PRIVATE->active[$row->contextid][$row->filter])) {
            $FILTERLIB_PRIVATE->active[$row->contextid][$row->filter][$row->name] = $row->value;
        }
    }
}

/**
 * List all of the filters that are available in this context, and what the
 * local and inherited states of that filter are.
 *
 * @param context $context a context that is not the system context.
 * @return array an array with filter names, for example 'tex'
 *      as keys. and and the values are objects with fields:
 *      ->filter filter name, same as the key.
 *      ->localstate TEXTFILTER_ON/OFF/INHERIT
 *      ->inheritedstate TEXTFILTER_ON/OFF - the state that will be used if localstate is set to TEXTFILTER_INHERIT.
 */
function filter_get_available_in_context($context) {
    global $DB;

    // The complex logic is working out the active state in the parent context,
    // so strip the current context from the list.
    $contextids = explode('/', trim($context->path, '/'));
    array_pop($contextids);
    $contextids = implode(',', $contextids);
    if (empty($contextids)) {
        throw new coding_exception('filter_get_available_in_context cannot be called with the system context.');
    }

    // The following SQL is tricky, in the same way at the SQL in filter_get_active_in_context.
    $sql = "SELECT parent_states.filter,
                CASE WHEN fa.active IS NULL THEN " . TEXTFILTER_INHERIT . "
                ELSE fa.active END AS localstate,
             parent_states.inheritedstate
         FROM (SELECT f.filter, MAX(f.sortorder) AS sortorder,
                    CASE WHEN MAX(f.active * ctx.depth) > -MIN(f.active * ctx.depth) THEN " . TEXTFILTER_ON . "
                    ELSE " . TEXTFILTER_OFF . " END AS inheritedstate
             FROM {filter_active} f
             JOIN {context} ctx ON f.contextid = ctx.id
             WHERE ctx.id IN ($contextids)
             GROUP BY f.filter
             HAVING MIN(f.active) > " . TEXTFILTER_DISABLED . "
         ) parent_states
         LEFT JOIN {filter_active} fa ON fa.filter = parent_states.filter AND fa.contextid = $context->id
         ORDER BY parent_states.sortorder";
    return $DB->get_records_sql($sql);
}

/**
 * This function is for use by the filter administration page.
 *
 * @return array 'filtername' => object with fields 'filter' (=filtername), 'active' and 'sortorder'
 */
function filter_get_global_states() {
    global $DB;
    $context = context_system::instance();
    return $DB->get_records('filter_active', array('contextid' => $context->id), 'sortorder', 'filter,active,sortorder');
}

/**
 * Delete all the data in the database relating to a filter, prior to deleting it.
 *
 * @param string $filter The filter name, for example 'tex'.
 */
function filter_delete_all_for_filter($filter) {
    global $DB;

    unset_all_config_for_plugin('filter_' . $filter);
    $DB->delete_records('filter_active', array('filter' => $filter));
    $DB->delete_records('filter_config', array('filter' => $filter));
}

/**
 * Delete all the data in the database relating to a context, used when contexts are deleted.
 *
 * @param integer $contextid The id of the context being deleted.
 */
function filter_delete_all_for_context($contextid) {
    global $DB;
    $DB->delete_records('filter_active', array('contextid' => $contextid));
    $DB->delete_records('filter_config', array('contextid' => $contextid));
}

/**
 * Does this filter have a global settings page in the admin tree?
 * (The settings page for a filter must be called, for example, filtersettingfiltertex.)
 *
 * @param string $filter The filter name, for example 'tex'.
 * @return boolean Whether there should be a 'Settings' link on the config page.
 */
function filter_has_global_settings($filter) {
    global $CFG;
    $settingspath = $CFG->dirroot . '/filter/' . $filter . '/settings.php';
    if (is_readable($settingspath)) {
        return true;
    }
    $settingspath = $CFG->dirroot . '/filter/' . $filter . '/filtersettings.php';
    return is_readable($settingspath);
}

/**
 * Does this filter have local (per-context) settings?
 *
 * @param string $filter The filter name, for example 'tex'.
 * @return boolean Whether there should be a 'Settings' link on the manage filters in context page.
 */
function filter_has_local_settings($filter) {
    global $CFG;
    $settingspath = $CFG->dirroot . '/filter/' . $filter . '/filterlocalsettings.php';
    return is_readable($settingspath);
}

/**
 * Certain types of context (block and user) may not have local filter settings.
 * the function checks a context to see whether it may have local config.
 *
 * @param object $context a context.
 * @return boolean whether this context may have local filter settings.
 */
function filter_context_may_have_filter_settings($context) {
    return $context->contextlevel != CONTEXT_BLOCK && $context->contextlevel != CONTEXT_USER;
}

/**
 * Process phrases intelligently found within a HTML text (such as adding links).
 *
 * @staticvar array $usedpharses
 * @param string $text             the text that we are filtering
 * @param array $link_array       an array of filterobjects
 * @param array $ignoretagsopen   an array of opening tags that we should ignore while filtering
 * @param array $ignoretagsclose  an array of corresponding closing tags
 * @param bool $overridedefaultignore True to only use tags provided by arguments
 * @return string
 **/
function filter_phrases($text, &$link_array, $ignoretagsopen=NULL, $ignoretagsclose=NULL,
        $overridedefaultignore=false) {

    global $CFG;

    static $usedphrases;

    $ignoretags = array();  // To store all the enclosig tags to be completely ignored.
    $tags = array();        // To store all the simple tags to be ignored.

    if (!$overridedefaultignore) {
        // A list of open/close tags that we should not replace within
        // Extended to include <script>, <textarea>, <select> and <a> tags
        // Regular expression allows tags with or without attributes
        $filterignoretagsopen  = array('<head>' , '<nolink>' , '<span class="nolink">',
                '<script(\s[^>]*?)?>', '<textarea(\s[^>]*?)?>',
                '<select(\s[^>]*?)?>', '<a(\s[^>]*?)?>');
        $filterignoretagsclose = array('</head>', '</nolink>', '</span>',
                 '</script>', '</textarea>', '</select>','</a>');
    } else {
        // Set an empty default list.
        $filterignoretagsopen = array();
        $filterignoretagsclose = array();
    }

    // Add the user defined ignore tags to the default list.
    if ( is_array($ignoretagsopen) ) {
        foreach ($ignoretagsopen as $open) {
            $filterignoretagsopen[] = $open;
        }
        foreach ($ignoretagsclose as $close) {
            $filterignoretagsclose[] = $close;
        }
    }

    // Invalid prefixes and suffixes for the fullmatch searches
    // Every "word" character, but the underscore, is a invalid suffix or prefix.
    // (nice to use this because it includes national characters (accents...) as word characters.
    $filterinvalidprefixes = '([^\W_])';
    $filterinvalidsuffixes = '([^\W_])';

    // Double up some magic chars to avoid "accidental matches"
    $text = preg_replace('/([#*%])/','\1\1',$text);


    //Remove everything enclosed by the ignore tags from $text
    filter_save_ignore_tags($text,$filterignoretagsopen,$filterignoretagsclose,$ignoretags);

    // Remove tags from $text
    filter_save_tags($text,$tags);

    // Time to cycle through each phrase to be linked
    $size = sizeof($link_array);
    for ($n=0; $n < $size; $n++) {
        $linkobject =& $link_array[$n];

        // Set some defaults if certain properties are missing
        // Properties may be missing if the filterobject class has not been used to construct the object
        if (empty($linkobject->phrase)) {
            continue;
        }

        // Avoid integers < 1000 to be linked. See bug 1446.
        $intcurrent = intval($linkobject->phrase);
        if (!empty($intcurrent) && strval($intcurrent) == $linkobject->phrase && $intcurrent < 1000) {
            continue;
        }

        // All this work has to be done ONLY it it hasn't been done before
         if (!$linkobject->work_calculated) {
            if (!isset($linkobject->hreftagbegin) or !isset($linkobject->hreftagend)) {
                $linkobject->work_hreftagbegin = '<span class="highlight"';
                $linkobject->work_hreftagend   = '</span>';
            } else {
                $linkobject->work_hreftagbegin = $linkobject->hreftagbegin;
                $linkobject->work_hreftagend   = $linkobject->hreftagend;
            }

            // Double up chars to protect true duplicates
            // be cleared up before returning to the user.
            $linkobject->work_hreftagbegin = preg_replace('/([#*%])/','\1\1',$linkobject->work_hreftagbegin);

            if (empty($linkobject->casesensitive)) {
                $linkobject->work_casesensitive = false;
            } else {
                $linkobject->work_casesensitive = true;
            }
            if (empty($linkobject->fullmatch)) {
                $linkobject->work_fullmatch = false;
            } else {
                $linkobject->work_fullmatch = true;
            }

            // Strip tags out of the phrase
            $linkobject->work_phrase = strip_tags($linkobject->phrase);

            // Double up chars that might cause a false match -- the duplicates will
            // be cleared up before returning to the user.
            $linkobject->work_phrase = preg_replace('/([#*%])/','\1\1',$linkobject->work_phrase);

            // Set the replacement phrase properly
            if ($linkobject->replacementphrase) {    //We have specified a replacement phrase
                // Strip tags
                $linkobject->work_replacementphrase = strip_tags($linkobject->replacementphrase);
            } else {                                 //The replacement is the original phrase as matched below
                $linkobject->work_replacementphrase = '$1';
            }

            // Quote any regular expression characters and the delimiter in the work phrase to be searched
            $linkobject->work_phrase = preg_quote($linkobject->work_phrase, '/');

            // Work calculated
            $linkobject->work_calculated = true;

        }

        // If $CFG->filtermatchoneperpage, avoid previously (request) linked phrases
        if (!empty($CFG->filtermatchoneperpage)) {
            if (!empty($usedphrases) && in_array($linkobject->work_phrase,$usedphrases)) {
                continue;
            }
        }

        // Regular expression modifiers
        $modifiers = ($linkobject->work_casesensitive) ? 's' : 'isu'; // works in unicode mode!

        // Do we need to do a fullmatch?
        // If yes then go through and remove any non full matching entries
        if ($linkobject->work_fullmatch) {
            $notfullmatches = array();
            $regexp = '/'.$filterinvalidprefixes.'('.$linkobject->work_phrase.')|('.$linkobject->work_phrase.')'.$filterinvalidsuffixes.'/'.$modifiers;

            preg_match_all($regexp,$text,$list_of_notfullmatches);

            if ($list_of_notfullmatches) {
                foreach (array_unique($list_of_notfullmatches[0]) as $key=>$value) {
                    $notfullmatches['<*'.$key.'*>'] = $value;
                }
                if (!empty($notfullmatches)) {
                    $text = str_replace($notfullmatches,array_keys($notfullmatches),$text);
                }
            }
        }

        // Finally we do our highlighting
        if (!empty($CFG->filtermatchonepertext) || !empty($CFG->filtermatchoneperpage)) {
            $resulttext = preg_replace('/('.$linkobject->work_phrase.')/'.$modifiers,
                                      $linkobject->work_hreftagbegin.
                                      $linkobject->work_replacementphrase.
                                      $linkobject->work_hreftagend, $text, 1);
        } else {
            $resulttext = preg_replace('/('.$linkobject->work_phrase.')/'.$modifiers,
                                      $linkobject->work_hreftagbegin.
                                      $linkobject->work_replacementphrase.
                                      $linkobject->work_hreftagend, $text);
        }


        // If the text has changed we have to look for links again
        if ($resulttext != $text) {
            // Set $text to $resulttext
            $text = $resulttext;
            // Remove everything enclosed by the ignore tags from $text
            filter_save_ignore_tags($text,$filterignoretagsopen,$filterignoretagsclose,$ignoretags);
            // Remove tags from $text
            filter_save_tags($text,$tags);
            // If $CFG->filtermatchoneperpage, save linked phrases to request
            if (!empty($CFG->filtermatchoneperpage)) {
                $usedphrases[] = $linkobject->work_phrase;
            }
        }


        // Replace the not full matches before cycling to next link object
        if (!empty($notfullmatches)) {
            $text = str_replace(array_keys($notfullmatches),$notfullmatches,$text);
            unset($notfullmatches);
        }
    }

    // Rebuild the text with all the excluded areas

    if (!empty($tags)) {
        $text = str_replace(array_keys($tags), $tags, $text);
    }

    if (!empty($ignoretags)) {
        $ignoretags = array_reverse($ignoretags);     // Reversed so "progressive" str_replace() will solve some nesting problems.
        $text = str_replace(array_keys($ignoretags),$ignoretags,$text);
    }

    // Remove the protective doubleups
    $text =  preg_replace('/([#*%])(\1)/','\1',$text);

    // Add missing javascript for popus
    $text = filter_add_javascript($text);


    return $text;
}

/**
 * @todo Document this function
 * @param array $linkarray
 * @return array
 */
function filter_remove_duplicates($linkarray) {

    $concepts  = array(); // keep a record of concepts as we cycle through
    $lconcepts = array(); // a lower case version for case insensitive

    $cleanlinks = array();

    foreach ($linkarray as $key=>$filterobject) {
        if ($filterobject->casesensitive) {
            $exists = in_array($filterobject->phrase, $concepts);
        } else {
            $exists = in_array(core_text::strtolower($filterobject->phrase), $lconcepts);
        }

        if (!$exists) {
            $cleanlinks[] = $filterobject;
            $concepts[] = $filterobject->phrase;
            $lconcepts[] = core_text::strtolower($filterobject->phrase);
        }
    }

    return $cleanlinks;
}

/**
 * Extract open/lose tags and their contents to avoid being processed by filters.
 * Useful to extract pieces of code like <a>...</a> tags. It returns the text
 * converted with some <#xTEXTFILTER_EXCL_SEPARATORx#> codes replacing the extracted text. Such extracted
 * texts are returned in the ignoretags array (as values), with codes as keys.
 *
 * @param string $text                  the text that we are filtering (in/out)
 * @param array $filterignoretagsopen  an array of open tags to start searching
 * @param array $filterignoretagsclose an array of close tags to end searching
 * @param array $ignoretags            an array of saved strings useful to rebuild the original text (in/out)
 **/
function filter_save_ignore_tags(&$text, $filterignoretagsopen, $filterignoretagsclose, &$ignoretags) {

    // Remove everything enclosed by the ignore tags from $text
    foreach ($filterignoretagsopen as $ikey=>$opentag) {
        $closetag = $filterignoretagsclose[$ikey];
        // form regular expression
        $opentag  = str_replace('/','\/',$opentag); // delimit forward slashes
        $closetag = str_replace('/','\/',$closetag); // delimit forward slashes
        $pregexp = '/'.$opentag.'(.*?)'.$closetag.'/is';

        preg_match_all($pregexp, $text, $list_of_ignores);
        foreach (array_unique($list_of_ignores[0]) as $key=>$value) {
            $prefix = (string)(count($ignoretags) + 1);
            $ignoretags['<#'.$prefix.TEXTFILTER_EXCL_SEPARATOR.$key.'#>'] = $value;
        }
        if (!empty($ignoretags)) {
            $text = str_replace($ignoretags,array_keys($ignoretags),$text);
        }
    }
}

/**
 * Extract tags (any text enclosed by < and > to avoid being processed by filters.
 * It returns the text converted with some <%xTEXTFILTER_EXCL_SEPARATORx%> codes replacing the extracted text. Such extracted
 * texts are returned in the tags array (as values), with codes as keys.
 *
 * @param string $text   the text that we are filtering (in/out)
 * @param array $tags   an array of saved strings useful to rebuild the original text (in/out)
 **/
function filter_save_tags(&$text, &$tags) {

    preg_match_all('/<([^#%*].*?)>/is',$text,$list_of_newtags);
    foreach (array_unique($list_of_newtags[0]) as $ntkey=>$value) {
        $prefix = (string)(count($tags) + 1);
        $tags['<%'.$prefix.TEXTFILTER_EXCL_SEPARATOR.$ntkey.'%>'] = $value;
    }
    if (!empty($tags)) {
        $text = str_replace($tags,array_keys($tags),$text);
    }
}

/**
 * Add missing openpopup javascript to HTML files.
 *
 * @param string $text
 * @return string
 */
function filter_add_javascript($text) {
    global $CFG;

    if (stripos($text, '</html>') === FALSE) {
        return $text; // This is not a html file.
    }
    if (strpos($text, 'onclick="return openpopup') === FALSE) {
        return $text; // No popup - no need to add javascript.
    }
    $js ="
    <script type=\"text/javascript\">
    <!--
        function openpopup(url,name,options,fullscreen) {
          fullurl = \"".$CFG->wwwroot."\" + url;
          windowobj = window.open(fullurl,name,options);
          if (fullscreen) {
            windowobj.moveTo(0,0);
            windowobj.resizeTo(screen.availWidth,screen.availHeight);
          }
          windowobj.focus();
          return false;
        }
    // -->
    </script>";
    if (stripos($text, '</head>') !== FALSE) {
        // Try to add it into the head element.
        $text = str_ireplace('</head>', $js.'</head>', $text);
        return $text;
    }

    // Last chance - try adding head element.
    return preg_replace("/<html.*?>/is", "\\0<head>".$js.'</head>', $text);
}
