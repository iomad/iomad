<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_quiz\cache;

use core_cache\data_source_interface;
use core_cache\definition;

/**
 * Data source implementation for the new quiz_overrides cache.
 *
 * This loads all applicable overrides for a (quizid, userid) pair:
 * - The user override (if present)
 * - All group overrides for groups the user belongs to in the quiz's course
 *
 * @package     mod_quiz
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_overrides_cache implements data_source_interface {
    /** @var ?quiz_overrides_cache Singleton instance. */
    private static $instance = null;

    #[\Override]
    public static function get_instance_for_cache(definition $definition): quiz_overrides_cache {
        return self::$instance ??= new quiz_overrides_cache();
    }

    #[\Override]
    public function load_for_cache($key) {
        global $DB;

        // Core cache invalidation asks datasources for this internal key.
        if ($key === 'lastinvalidation') {
            return false;
        }

        // All regular keys use the "{quizid}_{userid}" format.
        [$quizid, $userid] = self::split_cache_key((string) $key);

        $subquery = "SELECT g.id
                       FROM {groups} g
                       JOIN {groups_members} gm ON gm.groupid = g.id
                       JOIN {quiz} q ON q.course = g.courseid
                      WHERE q.id = :subqueryquizid AND gm.userid = :subqueryuserid";

        $sql = "SELECT *
                  FROM {quiz_overrides}
                 WHERE quiz = :quizid AND (userid = :userid OR groupid IN ($subquery))";

        return $DB->get_records_sql($sql, [
            'quizid' => $quizid,
            'userid' => $userid,
            'subqueryquizid' => $quizid,
            'subqueryuserid' => $userid,
        ]);
    }

    #[\Override]
    public function load_many_for_cache(array $keys) {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->load_for_cache($key);
        }
        return $results;
    }

    /**
     * Split a cache key into its quizid and userid components.
     *
     * @param string $key The cache key.
     * @return array{0:int,1:int} An array with quizid and userid as integers.
     */
    private static function split_cache_key(string $key): array {
        return array_map('intval', explode('_', $key));
    }
}
