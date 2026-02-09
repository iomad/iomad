<?php
// This file is part of The Bootstrap Moodle theme
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
 * A filter will limit a result set.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

use coding_exception;
use moodleform;
use MoodleQuickForm;
/**
 * A filter will limit a result set.
 *
 * @package block_dash
 */
class filter implements filter_interface {

    /**
     * @var mixed The value a user has chosen. Or the default.
     */
    private $rawvalue = null;

    /**
     * @var string Unique name used for placeholder.
     */
    private $name;

    /**
     * @var string The select portion of filter SQL express.
     */
    private $select;

    /**
     * @var string Human readable name for field.
     */
    private $label;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var bool If the filter is required to display data. Make sure required filters have default value if you want
     * the results to display without submitting a filter form.
     */
    protected $required = self::NOT_REQUIRED;

    /**
     * @var string SQL WHERE operation.
     */
    private $operation = self::OPERATION_IN_OR_EQUAL;

    /**
     * @var \context
     */
    private $context;

    /**
     * @var string
     */
    private $clausetype = self::CLAUSE_TYPE_WHERE;

    /**
     * @var array
     */
    private $preferences;

    /**
     * Is the filter supports the current user. this will be updated by the datasource.
     *
     * @var bool
     */
    protected $supportcurrentuser = false;

    /**
     * Filter constructor.
     *
     * @param string $name
     * @param string $select
     * @param string $label
     * @param string $clausetype
     */
    public function __construct($name, $select, $label = '', $clausetype = self::CLAUSE_TYPE_WHERE) {
        $this->name = $name;
        $this->select = $select;
        $this->label = $label;
        $this->clausetype = $clausetype;
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        $this->initialized = true;
    }

    /**
     * Get the raw submitted value of the filter.
     *
     * @return mixed
     */
    public function get_raw_value() {
        return $this->rawvalue;
    }

    /**
     * Set raw value.
     *
     * @param mixed $value Raw value (most likely from form submission).
     */
    public function set_raw_value($value) {
        if (!is_null($this->rawvalue)) {
            if (is_array($this->rawvalue)) {
                $this->rawvalue[] = $value;
            } else {
                // Convert scaler to array including new value.
                $this->rawvalue = [$this->rawvalue, $value];
            }
        } else {
            $this->rawvalue = $value;
        }
    }

    /**
     * Check if a user value was set.
     *
     * @return bool
     */
    public function has_raw_value() {
        return !is_null($this->rawvalue);
    }

    /**
     * Check if this filter was applied by the user.
     *
     * @return bool
     */
    public function is_applied() {
        return $this->has_raw_value() && $this->get_raw_value() != $this->get_default_raw_value();
    }

    /**
     * Check if filter is required.
     *
     * @return bool
     */
    public function is_required() {
        return $this->required == self::REQUIRED;
    }

    /**
     * Set if filter is required.
     *
     * @param bool $required
     */
    public function set_required($required) {
        $this->required = $required;
    }

    /**
     * Get filter name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get SQL select.
     *
     * @return string
     */
    public function get_select() {
        return $this->select;
    }

    /**
     * Get filter label.
     *
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Set filter label.
     *
     * @param string $label
     */
    public function set_label($label) {
        $this->label = $label;
    }

    /**
     * Get help text for this filter to help configuration.
     *
     * Return array[string_identifier, component], similar to the $mform->addHelpButton() call.
     *
     * @return array<string, string>
     */
    public function get_help() {
        return null;
    }

    /**
     * Get filter SQL operation.
     *
     * @return string
     */
    public function get_operation() {
        return $this->operation;
    }

    /**
     * Set an operation.
     *
     * @param string $operation
     * @throws coding_exception
     */
    public function set_operation($operation) {
        if (!in_array($operation, $this->get_supported_operations())) {
            throw new coding_exception(get_class($this) . ' does not support operation: ' . $operation);
        }

        $this->operation = $operation;
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value() {
        return null;
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations() {
        // Return all operations.
        return filter_interface::OPERATIONS;
    }

    /**
     * Check if filter has a default raw value.
     *
     * @return bool
     */
    public function has_default_raw_value() {
        return !empty($this->get_default_raw_value());
    }

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values() {
        if (!$this->has_raw_value()) {
            if ($this->has_default_raw_value()) {
                return [$this->get_default_raw_value()];
            }
            return [];
        }

        if (is_array($this->get_raw_value())) {
            return $this->get_raw_value();
        } else {
            return [$this->get_raw_value()];
        }
    }

    /**
     * Return where SQL and params for placeholders.
     *
     * @return array
     * @throws coding_exception|\dml_exception
     */
    public function get_sql_and_params() {
        if (!$this->initialized) {
            throw new coding_exception('Filter was not initialized properly. Did you call parent::init()?');
        }

        if (!$values = $this->get_values()) {
            return ['', []];
        }

        reset($values);
        // Get first value for operations that only support one value.
        $value = $values[0];

        $sql = '';
        $placeholder = $this->get_name();
        $params = [$placeholder => $value];
        $select = $this->get_select();

        switch ($this->get_operation()) {
            case self::OPERATION_EQUAL:
                $sql = "$select = :$placeholder";
                break;
            case self::OPERATION_NOT_EQUAL:
                $sql = "$select != :$placeholder";
                break;
            case self::OPERATION_LESS_THAN:
                $sql = "$select < :$placeholder";
                break;
            case self::OPERATION_GREATER_THAN:
                $sql = "$select > :$placeholder";
                break;
            case self::OPERATION_LESS_THAN_EQUAL:
                $sql = "$select <= :$placeholder";
                break;
            case self::OPERATION_GREATER_THAN_EQUAL:
                $sql = "$select >= :$placeholder";
                break;
            case self::OPERATION_IN_OR_EQUAL:
                list ($sql, $params) = $this->get_in_or_equal();
                $sql = "$select $sql";
                break;
            case self::OPERATION_LIKE:
                $sql = "$select LIKE :$placeholder";
                break;
            case self::OPERATION_LIKE_WILDCARD:
                $sql = "$select LIKE :$placeholder";
                // Convert value to wildcard.
                $params[$placeholder] = '%'.$params[$placeholder].'%';
                break;
            case self::OPERATION_CUSTOM:
                $sql = $this->get_custom_operation();
                break;
        }

        return [$sql, $params];
    }

    /**
     * This variable is used for storing the results of $DB->get_in_or_equal() since it internally keeps a
     * running count (so calling it twice will result in different param names).
     *
     * Used only in filter::get_in_or_equal() as a sort of runtime cache
     *
     * @var array
     */
    private $sqlandparams;

    /**
     * Special get in or equal.
     *
     * @return array
     * @throws coding_exception
     * @throws \dml_exception
     */
    private function get_in_or_equal() {
        global $DB;

        if (!$values = $this->get_values()) {
            return ['', []];
        }

        if (is_null($this->sqlandparams)) {

            $values = $this->get_values();

            $this->sqlandparams = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
        }

        return $this->sqlandparams;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filtercollection
     * @param string $elementnameprefix
     * @throws \Exception
     */
    public function create_form_element(filter_collection_interface $filtercollection,
                                        $elementnameprefix = '') {
        throw new coding_exception('Filter element does not exist. Did you forget to override filter::create_form_element()?');
    }

    /**
     * Get option label based on value.
     *
     * @param string $value
     * @return string
     */
    public function get_option_label($value) {
        return $value;
    }

    /**
     * Get filter context.
     *
     * @return \context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Set context.
     *
     * @param \context $context
     */
    public function set_context(\context $context) {
        $this->context = $context;
    }

    /**
     * Get clause type.
     *
     * @return string
     */
    public function get_clause_type() {
        return $this->clausetype;
    }

    /**
     * Return custom operation SQL.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_custom_operation(): string {
        throw new coding_exception('Must implement get_custom_operation when using OPERATION_CUSTOM');
    }

    /**
     * Set preferences on this filter.
     *
     * @param array $preferences
     */
    public function set_preferences($preferences = null): void {
        $this->preferences = $preferences;
    }

    /**
     * Get preferences related to this filter.
     *
     * @return array
     */
    public function get_preferences(): array {
        if (!$this->preferences) {
            return [];
        }
        return $this->preferences;
    }

    /**
     * Add form fields for this filter (and any settings related to this filter.)
     *
     * @param moodleform $moodleform
     * @param MoodleQuickForm $mform
     * @param string $fieldnameformat
     */
    public function build_settings_form_fields(
        moodleform $moodleform,
        MoodleQuickForm $mform,
        $fieldnameformat = 'filters[%s]'): void {
        $fieldname = sprintf($fieldnameformat, $this->get_name());

        $totaratitle = block_dash_is_totara() ? $this->get_label() : null;
        $mform->addElement('advcheckbox', $fieldname . '[enabled]', $this->get_label(), $totaratitle);

        if ($this->get_help()) {
            [$identifier, $component] = $this->get_help();
            $mform->addHelpButton($fieldname . '[enabled]', $identifier, $component);
        }
    }

    /**
     * Set this datasource is support the profile page user.
     *
     * @return void
     */
    public function set_support_currentuser() {
        $this->supportcurrentuser = true;
    }

    /**
     * Get the current userid.
     *
     * The current page is user profile page, then use the profile user id. Otherwise returns the current loggedin userid.
     *
     * @return int
     */
    public function get_userid() {
        global $PAGE, $USER;

        if ($this->supportcurrentuser) {
            // Confirm the dash is addon on user profile page, then use the profile page user as report user.
            $isprofilepage = $PAGE->pagelayout == 'mypublic' && $PAGE->pagetype == 'user-profile';
            $userid = $isprofilepage ? $PAGE->context->instanceid : $USER->id;
        }

        return $userid ?? $USER->id;
    }
}
