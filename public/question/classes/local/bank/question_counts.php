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

namespace core_question\local\bank;

use core\context\course;
use core\context\module;
use core\di;
use core\exception\required_capability_exception;

/**
 * Methods for counting the questions in different contexts
 *
 * @package   core_question
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_counts {
    /**
     * Return a list of question counts for each course module context.
     *
     * @param int[] $coursemodulecontextids The course modules to return a count for.
     * @return array [cmid => count]
     */
    public function by_course_modules(array $coursemodulecontextids): array {
        $db = di::get(\moodle_database::class);

        if (empty($coursemodulecontextids)) {
            return [];
        }

        [$contextinsql, $contextinparams] = $db->get_in_or_equal($coursemodulecontextids, SQL_PARAMS_NAMED);

        // Get a count of all questions in each module context, keyed by cmid.
        // Only look in contexts for those module which support FEATURE_PUBLISHES_QUESTIONS.
        // Return a count of 0 for those modules with no questions or question categories.
        // The double LEFT JOIN of question_versions ensures we only get the latest version for a question bank entry.
        $sql = "
            SELECT c.instanceid,
                   COUNT(
                       CASE
                           WHEN q.id IS NOT NULL THEN 1
                       END
                   ) AS count
              FROM {context} c
              JOIN {question_categories} qc ON qc.contextid = c.id
         LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
         LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
         LEFT JOIN {question_versions} qv1 ON qv1.questionbankentryid = qbe.id
                  AND qv.version < qv1.version
                  AND qv1.status != :hidden1
         LEFT JOIN {question} q ON q.id = qv.questionid
             WHERE c.id {$contextinsql}
                   AND (qv.status != :hidden OR q.id IS NULL)
                   AND (q.parent = '0' OR q.id IS NULL)
                   AND (qv1.questionbankentryid IS NULL OR q.id IS NULL)
          GROUP BY c.instanceid
        ";
        $params = [
            ...$contextinparams,
            'hidden' => question_version_status::QUESTION_STATUS_HIDDEN,
            'hidden1' => question_version_status::QUESTION_STATUS_HIDDEN,
        ];
        return $db->get_records_sql_menu($sql, $params);
    }

    /**
     * Return the SQL query for getting a count of questions in a single category.
     *
     * @param int $showallversions 1 to show all versions not only the latest.
     * @param string $categoryparam Category ID parameter or field. This can be a paramter that is added to the $params array
     *     by the calling code, or field in another table where this is used as a subquery.
     * @return array The SQL and its parameters.
     */
    public function by_category_query(int $showallversions = 0, string $categoryparam = 'c.id'): array {
        $sql = "
            SELECT COUNT(1)
              FROM {question} q
              JOIN {question_versions} qv ON qv.questionid = q.id
              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
         LEFT JOIN {question_versions} qv2 ON qv2.questionbankentryid = qbe.id
                   AND qv2.version > qv.version
                   AND qv2.status != :hidden1
             WHERE q.parent = :topparent
                   AND qv.status != :hidden
                   AND (:showallversions = 1 OR qv2.id IS NULL)
                   AND qbe.questioncategoryid = {$categoryparam}
        ";
        $params = [
            'showallversions' => $showallversions,
            'topparent' => 0,
            'hidden' => question_version_status::QUESTION_STATUS_HIDDEN,
            'hidden1' => question_version_status::QUESTION_STATUS_HIDDEN,
        ];
        return [$sql, $params];
    }
}
