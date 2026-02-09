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

use coding_exception;
use dml_exception;

/**
 * Builds a query.
 *
 * @package block_dash
 */
class builder {

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $tablealias;

    /**
     * @var string[]
     */
    private $selects = [];

    /**
     * @var array
     */
    private $wheres = [];

    /**
     * @var array
     */
    private $rawwhere;

    /**
     * @var array
     */
    private $rawwhereparameters = [];

    /**
     * @var int Return a subset of records, starting at this point (optional).
     */
    private $limitfrom = 0;

    /**
     * @var int Return a subset comprising this many records in total (optional, required if $limitfrom is set).
     */
    private $limitnum = 0;

    /**
     * @var array ['field1' => 'ASC', 'field2' => 'DESC', ...]
     */
    private $orderby = [];

    /**
     * @var join[]
     */
    private $joins = [];

    /**
     * @var array ['field1', 'field2', ...]
     */
    private $groupby = [];

    /**
     * Extra conditions to be added in WHERE clause.
     *
     * @var array
     */
    private $rawconditions = [];

    /**
     * @var array
     */
    private $rawconditionparameters = [];

    /**
     * @var array
     */
    private $rawjoins = [];

    /**
     * @var array
     */
    private $rawjoinsparameters = [];

    /**
     * Fields to retried from sql query. Sql select field.
     * @param string $field
     * @param string $alias
     * @return builder
     */
    public function select(string $field, string $alias): builder {
        $this->selects[$alias] = $field;
        return $this;
    }

    /**
     * Set all selects on builder.
     *
     * @param array $selects [alias => field, ...]
     * @return $this
     */
    public function set_selects(array $selects): builder {
        $this->selects = [];
        foreach ($selects as $alias => $select) {
            $this->selects[$alias] = $select;
        }
        return $this;
    }

    /**
     * Set main table of query.
     *
     * @param string $table
     * @param string $alias
     * @return builder
     */
    public function from(string $table, string $alias): builder {
        $this->table = $table;
        $this->tablealias = $alias;
        return $this;
    }

    /**
     * Join table in query.
     *
     * @param string $table Table name of joined table.
     * @param string $alias Joined table alias.
     * @param string $jointablefield Field of joined table to reference in join condition.
     * @param string $origintablefield Field of origin table to join to.
     * @param string $jointype SQL join type. See self::TYPE_*
     * @param array $extraparameters Extra parameters used in join SQL.
     * @return $this
     */
    public function join(string $table, string $alias, string $jointablefield, string $origintablefield,
                         $jointype = join::TYPE_INNER_JOIN, array $extraparameters = []): builder {
        $this->joins[] = new join($table, $alias, $jointablefield, $origintablefield, $jointype, $extraparameters);
        return $this;
    }

    /**
     * Join raw in query.
     *
     * @param string $joinsql SQL join type. See self::TYPE_*
     * @param array $parameters Extra parameters used in join SQL.
     * @return $this
     */
    public function join_raw(string $joinsql, array $parameters = []): builder {
        $this->rawjoins[] = [$joinsql, $parameters];
        return $this;
    }

    /**
     * Add additional join condition to existing join.
     *
     * @param string $alias
     * @param string $condition
     * @return $this
     * @throws coding_exception
     */
    public function join_condition(string $alias, string $condition): builder {
        $added = false;
        foreach ($this->joins as $join) {
            if ($join->get_alias() == $alias) {
                $join->add_join_condition($condition);
                $added = true;
                break;
            }
        }

        if (!$added) {
            throw new coding_exception('Table alias not found: ' . $alias);
        }

        return $this;
    }

    /**
     * Add where clause to query.
     *
     * @param string $selector Field or alias of where clause.
     * @param array $values Values that where clause will compare to.
     * @param string $operator Equals, greater than, in, etc etc. See where::OPERATOR_*
     * @param string $conjunctive AND, OR etc etc. See where::CONJUCTIVE_OPERATOR_*
     *
     * @return where
     */
    public function where(string $selector, array $values, string $operator = where::OPERATOR_EQUAL,
        string $conjunctive = where::CONJUNCTIVE_OPERATOR_AND): where {
        $where = new where($selector, $values, $operator, $conjunctive);
        $this->wheres[] = $where;
        return $where;
    }

    /**
     * Add where (in subquery) clause to query.
     *
     * @param string $selector Field or alias of where clause.
     * @param string $query Subquery of WHERE IN (subquery) clause.
     * @param array $params Any extra parameters used in subquery.
     * @return where
     */
    public function where_in_query(string $selector, string $query, array $params = []): where {
        $where = new where($selector, []);
        $where->set_query($query, $params);
        $this->wheres[] = $where;
        return $where;
    }

    /**
     * Add where clause to query.
     *
     * @param string $wheresql
     * @param array $parameters
     * @return builder
     */
    public function where_raw(string $wheresql, array $parameters = []): builder {
        $this->rawwhere[] = $wheresql;
        $this->rawwhereparameters = array_merge($this->rawwhereparameters, $parameters);

        return $this;
    }

    /**
     * Order by a field.
     *
     * @param string $field Field or alias to order by.
     * @param string $direction 'ASC' or 'DESC'
     * @return builder
     * @throws coding_exception
     */
    public function orderby(string $field, string $direction): builder {
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            throw new coding_exception('Invalid order by direction ' . $direction);
        }

        $this->orderby[$field] = $direction;
        return $this;
    }

    /**
     * Remove order by conditions.
     *
     * @return builder
     */
    public function remove_orderby(): builder {
        $this->orderby = [];
        return $this;
    }

    /**
     * Group by field for aggregations.
     *
     * @param string $field
     * @return builder
     */
    public function groupby(string $field): builder {
        $this->groupby[] = $field;
        return $this;
    }

    /**
     * Add raw condition to builder.
     *
     * @param string $condition
     * @param array $parameters
     * @return builder
     */
    public function rawcondition(string $condition, array $parameters = []): builder {
        $this->rawconditions[] = $condition;
        $this->rawconditionparameters = $parameters;
        return $this;
    }

    /**
     * Get the query where conditions.
     * @return where[]
     */
    public function get_wheres(): array {
        return $this->wheres;
    }

    /**
     * Get the query limit from.
     *
     * @return int
     */
    public function get_limitfrom(): int {
        return $this->limitfrom;
    }

    /**
     * Set the query limit from.
     *
     * @param int $limitfrom
     * @return $this
     */
    public function limitfrom(int $limitfrom): builder {
        $this->limitfrom = $limitfrom;
        return $this;
    }

    /**
     * Get the query limit number.
     *
     * @return int
     */
    public function get_limitnum(): int {
        return $this->limitnum;
    }

    /**
     * Set the query limit number.
     *
     * @param int $limitnum
     * @return $this
     */
    public function limitnum(int $limitnum): builder {
        $this->limitnum = $limitnum;
        return $this;
    }

    /**
     * Build and return complete query select SQL.
     *
     * @return string
     */
    protected function build_select(): string {
        $selects = [];
        foreach ($this->selects as $alias => $select) {
            $selects[] = $select . ' AS ' . $alias;
        }

        return implode(',', $selects);
    }

    /**
     * Get the query where condition and it parameters.
     * @return array
     * @throws exception\invalid_operator_exception
     */
    protected function get_where_sql_and_params(): array {
        $wheresql = [];
        $params = [];
        $wsql = ''; // Where builder queryies.
        foreach ($this->get_wheres() as $where) {
            [$sql, $wparams] = $where->get_sql_and_params();
            $conjunc = $where->get_conjunctive_operator() ?: 'AND';
            $wsql .= !empty($wsql) ? sprintf(' %s %s ', $conjunc, $sql) : $sql;
            $params = array_merge($params, $wparams);
        }

        $wheresql[] = $wsql;

        if ($this->rawwhere) {
            foreach ($this->rawwhere as $where) {
                $wheresql[] = $where;
            }
            $params = array_merge($params, $this->rawwhereparameters);
        }

        return [implode(' AND ', array_filter($wheresql)), $params];
    }

    /**
     * Get the query and required parameters.
     *
     * @return array<string, array>
     * @throws exception\invalid_operator_exception
     */
    final public function get_sql_and_params(): array {
        $sql = 'SELECT DISTINCT ' . $this->build_select() . ' FROM {' . $this->table . '} ' . $this->tablealias;
        $params = [];

        foreach ($this->joins as $join) {
            [$jsql, $jparams] = $join->get_sql_and_params();
            $sql .= ' ' . $jsql . ' ';
            $params = array_merge($params, $jparams);
        }

        foreach ($this->rawjoins as $join) {
            [$jsql, $jparams] = $join;
            $sql .= ' ' . $jsql . ' ';
            $params = array_merge($params, $jparams);
        }

        [$wsql, $wparams] = $this->get_where_sql_and_params();

        if ($wsql) {
            $sql .= ' WHERE ' . $wsql;
            $params = array_merge($params, $wparams);
        }

        if (count($this->groupby) > 0) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
        }

        if ($this->orderby) {
            $orderbys = [];
            foreach ($this->orderby as $field => $direction) {
                $orderbys[] = sprintf('%s %s', $field, $direction);
            }

            $sql .= ' ORDER BY ' . implode(', ', $orderbys);
        }

        return [$sql, $params];
    }

    /**
     * Execute query and return results.
     *
     * @return array
     * @throws dml_exception
     * @throws exception\invalid_operator_exception
     */
    public function query() {
        global $DB;

        [$sql, $params] = $this->get_sql_and_params();
        return $DB->get_records_sql($sql, $params, $this->get_limitfrom(), $this->get_limitnum());
    }

    /**
     * Get number of records this query will return.
     *
     * @param int $isunique Counted by unique id.
     * @return int
     * @throws dml_exception
     * @throws exception\invalid_operator_exception
     */
    public function count($isunique): int {
        global $DB;
        $builder = clone $this;

        if ($isunique) {
            $builder->set_selects([
                'uni' => $DB->sql_concat('MAX(cm.id)', 'MAX(u.id)', 'MAX(c.id)', 'MAX(cc.id)'),
                'count' => 'COUNT(*)']
            );
        } else {
            $builder->set_selects(['count' => 'COUNT(DISTINCT ' . $this->tablealias . '.id)']);
        }

        $builder->limitfrom(0)->limitnum(0)->remove_orderby();
        if (!$records = $builder->query()) {
            return 0;
        }
        return array_values($records)[0]->count;
    }
}
