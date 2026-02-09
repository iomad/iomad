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
 * Builds a query.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\query_builder;

use block_dash\local\dash_framework\query_builder\exception\invalid_operator_exception;
use block_dash\local\dash_framework\query_builder\exception\invalid_where_clause_exception;
use coding_exception;
use dml_exception;

/**
 * Builds a query.
 *
 * @package block_dash
 */
class where {

    /**
     * @var int Unique counter for param/placeholder names.
     */
    public static $paramcounter = 0;

    /**
     * Sql equal sign.
     */
    const OPERATOR_EQUAL = '=';
    /**
     * SQL not equal.
     */
    const OPERATOR_NOT_EQUAL = '!=';
    /**
     * SQL IN operator.
     */
    const OPERATOR_IN = 'in';
    /**
     * SQL IN query.
     */
    const OPERATOR_IN_QUERY = 'in_query';

    /**
     * SQL grader than.
     */
    const OPERATOR_GREATERTHAN = '>';

    /**
     * SQL greater than or equal.
     */
    const OPERATOR_GREATERTHAN_EQUAL = '>=';

    /**
     * SQL less than.
     */
    const OPERATOR_LESSTHAN = '<';

    /**
     * SQL less than or equal.
     */
    const OPERATOR_LESSTHAN_EQUAL = '<=';

    /**
     * SQL Like query.
     */
    const OPERATOR_LIKE = 'like';

    /**
     * SQL Not like query.
     */
    const OPERATOR_NOT_LIKE = 'not_like';

    /**
     * SQL Not IN.
     */
    const OPERATOR_NOT_IN = 'not_in';

    /**
     * SQL Not IN query.
     */
    const OPERATOR_NOT_IN_QUERY = 'not_in_query';

    /**
     * SQL conjection of OR.
     */
    const CONJUNCTIVE_OPERATOR_OR = 'OR';

    /**
     * SQL conjection of AND.
     */
    const CONJUNCTIVE_OPERATOR_AND = 'AND';

    /**
     * @var string Field or subquery.
     */
    private $selector;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string See self::OPERATION_*
     */
    private $operator;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $queryparams;

    /**
     * Conjunctive operator.
     *
     * @var string
     */
    private $conjunctive;

    /**
     * Create new where condition.
     *
     * @param string $selector
     * @param array $values
     * @param string $operator
     * @param string $conjunctive
     */
    public function __construct(string $selector, array $values, string $operator = self::OPERATOR_EQUAL,
        string $conjunctive = self::CONJUNCTIVE_OPERATOR_AND) {
        $this->selector = $selector;
        $this->values = array_values($values);
        $this->operator = $operator;
        $this->conjunctive = $conjunctive;
    }

    /**
     * Set sql query to build.
     *
     * @param string $query
     * @param array $params
     */
    public function set_query(string $query, array $params = []) {
        $this->query = $query;
        $this->queryparams = $params;
        $this->operator = self::OPERATOR_IN_QUERY;
    }

    /**
     * Conjunctive operator for this where clasuse with previous.
     *
     * @return string
     */
    public function get_conjunctive_operator() {
        return $this->conjunctive ?: self::CONJUNCTIVE_OPERATOR_AND;
    }

    /**
     * Get sql query and parameters with processed operators.
     *
     * @return array<string, array>
     * @throws invalid_operator_exception
     * @throws invalid_where_clause_exception
     * @throws dml_exception|coding_exception
     */
    public function get_sql_and_params(): array {
        global $DB;

        $sql = '';
        // Named parameters.
        $params = [];

        // First ensure this where clause is valid.
        switch ($this->operator) {
            case self::OPERATOR_EQUAL:
            case self::OPERATOR_IN:
                if (empty($this->values)) {
                    throw new invalid_where_clause_exception();
                }
                break;
        }

        // Build SQL and params.
        switch ($this->operator) {
            case self::OPERATOR_EQUAL:
            case self::OPERATOR_NOT_EQUAL:
            case self::OPERATOR_GREATERTHAN:
            case self::OPERATOR_LESSTHAN:
            case self::OPERATOR_GREATERTHAN_EQUAL:
            case self::OPERATOR_LESSTHAN_EQUAL:
                $placeholder = self::get_param_name();
                $sql = sprintf('%s %s :%s', $this->selector, $this->operator, $placeholder);
                $params[$placeholder] = $this->values[0];
                break;
            case self::OPERATOR_IN:
                // At this point we are guaranteed at least one value being applied.
                [$psql, $pparams] = $DB->get_in_or_equal($this->values, SQL_PARAMS_NAMED, 'p');
                $sql = sprintf('%s %s', $this->selector, $psql);
                $params = array_merge($params, $pparams);
                break;
            case self::OPERATOR_NOT_IN:
                // At this point we are guaranteed at least one value being applied.
                [$psql, $pparams] = $DB->get_in_or_equal($this->values, SQL_PARAMS_NAMED, 'p', false);
                $sql = sprintf('%s %s', $this->selector, $psql);
                $params = array_merge($params, $pparams);
                break;
            case self::OPERATOR_IN_QUERY:
                $sql = sprintf('%s IN (%s)', $this->selector, $this->query);
                $params = array_merge($params, $this->queryparams);
                break;
            case self::OPERATOR_NOT_IN_QUERY:
                $sql = sprintf('%s NOT IN (%s)', $this->selector, $this->query);
                $params = array_merge($params, $this->queryparams);
                break;
            case self::OPERATOR_LIKE:
                $placeholder = self::get_param_name();
                $sql = $DB->sql_like($this->selector, ':'.$placeholder);
                $params[$placeholder] = isset($this->values[0]) ? '%'.$this->values[0].'%' : '';
                break;
            case self::OPERATOR_NOT_LIKE:
                $placeholder = self::get_param_name();
                $sql = $DB->sql_like($this->selector, ':'.$placeholder, true, true, true);
                $params[$placeholder] = isset($this->values[0]) ? '%'.$DB->sql_like_escape($this->values[0]).'%' : '';
                break;
            default:
                throw new invalid_operator_exception('', ['operator' => $this->operator]);
        }

        return [$sql, $params];
    }

    /**
     * Get unique parameter name.
     *
     * @param string $prefix
     * @return string
     */
    public static function get_param_name(string $prefix = 'p_'): string {
        return $prefix . self::$paramcounter++;
    }
}
