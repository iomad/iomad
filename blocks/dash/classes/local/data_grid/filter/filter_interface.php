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

use moodleform;
use MoodleQuickForm;
/**
 * A filter will limit a result set.
 *
 * @package block_dash
 */
interface filter_interface {

    /**
     * Filter value is required
     */
    const REQUIRED = true;

    /**
     * Filter value is not required.
     */
    const NOT_REQUIRED = false;

    /**
     * Operation equal (=).
     */
    const OPERATION_EQUAL = 'equal';

    /**
     * Operation not equal (!=).
     */
    const OPERATION_NOT_EQUAL = 'not_equal';

    /**
     * Operation less than (<).
     */
    const OPERATION_LESS_THAN = 'less';

    /**
     * Operation greater than (>).
     */
    const OPERATION_GREATER_THAN = 'greater';

    /**
     * Operation less than or equal to (<=).
     */
    const OPERATION_LESS_THAN_EQUAL = 'less_equal';

    /**
     * Operation greater than or equal (>=).
     */
    const OPERATION_GREATER_THAN_EQUAL = 'greater_equal';

    /**
     * Operation in or equal (= or IN()).
     */
    const OPERATION_IN_OR_EQUAL = 'in_or_equal';

    /**
     * Operation like (LIKE)
     */
    const OPERATION_LIKE = 'like';

    /**
     * Operation like with wildcard (LIKE %%).
     */
    const OPERATION_LIKE_WILDCARD = 'like_wild';

    /**
     * Custom operation, such as a subquery.
     */
    const OPERATION_CUSTOM = 'custom';

    /**
     * Filter clause is included in "where".
     */
    const CLAUSE_TYPE_WHERE = 'where';

    /**
     * filter clause is included in "having".
     */
    const CLAUSE_TYPE_HAVING = 'having';

    /**
     * Supported SQL WHERE operations.
     */
    const OPERATIONS = [
        self::OPERATION_EQUAL,
        self::OPERATION_NOT_EQUAL,
        self::OPERATION_LESS_THAN,
        self::OPERATION_GREATER_THAN,
        self::OPERATION_LESS_THAN_EQUAL,
        self::OPERATION_GREATER_THAN_EQUAL,
        self::OPERATION_IN_OR_EQUAL,
        self::OPERATION_LIKE,
        self::OPERATION_LIKE_WILDCARD,
        self::OPERATION_CUSTOM,
    ];

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init();

    /**
     * Get the raw submitted value of the filter.
     *
     * @return mixed
     */
    public function get_raw_value();

    /**
     * Set raw value.
     *
     * @param mixed $value Raw value (most likely from form submission).
     */
    public function set_raw_value($value);

    /**
     * Check if a user value was set.
     *
     * @return bool
     */
    public function has_raw_value();

    /**
     * Check if this filter was applied by the user.
     *
     * @return bool
     */
    public function is_applied();

    /**
     * Is filter required.
     *
     * @return bool
     */
    public function is_required();

    /**
     * Set if filter is required.
     *
     * @param bool $required
     */
    public function set_required($required);

    /**
     * Get filter name.
     *
     * @return string
     */
    public function get_name();

    /**
     * Get filter SQL select.
     *
     * @return string
     */
    public function get_select();

    /**
     * Get filter label.
     *
     * @return string
     */
    public function get_label();

    /**
     * Set filter label.
     *
     * @param string $label
     */
    public function set_label($label);

    /**
     * Get help text for this filter to help configuration.
     *
     * Return array[string_identifier, component], similar to the $mform->addHelpButton() call.
     *
     * @return array<string, string>
     */
    public function get_help();

    /**
     * Get SQL operation.
     *
     * @return string
     */
    public function get_operation();

    /**
     * Set an operation.
     *
     * @param string $operation
     * @throws \coding_exception
     */
    public function set_operation($operation);

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value();

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations();

    /**
     * If filter has a default value.
     *
     * @return bool
     */
    public function has_default_raw_value();

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values();

    /**
     * Return where SQL and params for placeholders.
     *
     * @return array
     * @throws \Exception
     */
    public function get_sql_and_params();

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filtercollection
     * @param string $elementnameprefix
     * @throws \Exception
     */
    public function create_form_element(filter_collection_interface $filtercollection,
                                        $elementnameprefix = '');

    /**
     * Get option label based on value.
     *
     * @param string $value
     * @return string
     */
    public function get_option_label($value);

    /**
     * Get filter context.
     *
     * @return \context
     */
    public function get_context();

    /**
     * Set context.
     *
     * @param \context $context
     */
    public function set_context(\context $context);

    /**
     * Get clause type.
     *
     * @return string
     */
    public function get_clause_type();

    /**
     * Return custom operation SQL.
     *
     * @return string
     */
    public function get_custom_operation(): string;

    /**
     * Set preferences on this filter.
     *
     * @param array $preferences
     */
    public function set_preferences($preferences = null): void;

    /**
     * Get preferences related to this filter.
     *
     * @return array
     */
    public function get_preferences(): array;

    /**
     * Add form fields for this filter (and any settings related to this filter.)
     *
     * @param moodleform $moodleform
     * @param MoodleQuickForm $mform
     * @param string $fieldnameformat
     */
    public function build_settings_form_fields(moodleform $moodleform,
        MoodleQuickForm $mform, $fieldnameformat = 'filters[%s]'): void;
}
