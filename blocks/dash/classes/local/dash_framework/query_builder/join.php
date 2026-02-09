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
 * Join a table.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\query_builder;

use block_dash\local\dash_framework\query_builder\exception\invalid_operator_exception;
use block_dash\local\dash_framework\query_builder\exception\invalid_where_clause_exception;
use coding_exception;
use dml_exception;

/**
 * Join a table.
 *
 * @package block_dash
 */
class join {

    /**
     * Inner JOIN query.
     */
    const TYPE_INNER_JOIN = 'JOIN';

    /**
     * SQL Left Join.
     */
    const TYPE_LEFT_JOIN = 'LEFT JOIN';

    /**
     * SQL right Join.
     */
    const TYPE_RIGHT_JOIN = 'RIGHT JOIN';

    /**
     * @var string Table name of joined table.
     */
    private $table;

    /**
     * @var string Joined table alias.
     */
    private $alias;

    /**
     * @var array
     */
    private $joinconditions = [];

    /**
     * SQL join type. See self::TYPE_*
     *
     * @var string
     */
    private $jointype;

    /**
     * @var array Extra paramters used in query build.
     */
    private $extraparameters;

    /**
     * Constructors
     * @param string $table Table name of joined table.
     * @param string $alias Joined table alias.
     * @param string $jointablefield Field of joined table to reference in join condition.
     * @param string $origintablefield Field of origin table to join to.
     * @param string $jointype SQL join type. See self::TYPE_*
     * @param array $extraparameters Extra parameters used in join SQL.
     */
    public function __construct(string $table, string $alias, string $jointablefield, string $origintablefield,
                                $jointype = self::TYPE_INNER_JOIN, array $extraparameters = []) {
        $this->table = $table;
        $this->alias = $alias;
        // Join table field.
        if (!empty($jointablefield)) {
            $this->joinconditions[] = sprintf('%s.%s = %s', $alias, $jointablefield, $origintablefield);
        }
        $this->jointype = $jointype;
        $this->extraparameters = $extraparameters;
    }

    /**
     * Get alias mentioned in query.
     *
     * @return string
     */
    public function get_alias(): string {
        return $this->alias;
    }

    /**
     * Add additional raw join condition.
     *
     * @param string $condition
     */
    public function add_join_condition(string $condition): void {
        $this->joinconditions[] = $condition;
    }

    /**
     * Get SQL and params for join.
     *
     * @return array<string, array>
     */
    public function get_sql_and_params(): array {
        $sql = sprintf('%s {%s} %s ON ', $this->jointype, $this->table, $this->alias);
        $sql .= implode(' AND ', $this->joinconditions);

        return [$sql, $this->extraparameters];
    }
}
