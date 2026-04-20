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

namespace core_badges;

/**
 * User selector for potential badge recipients.
 *
 * @package    core_badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Dai Nguyen Trong <ngtrdai@hotmail.com> based on Yuliya Bozhko <yuliya.bozhko@totaralms.com> code
 */
class potential_award_selector extends award_selector_base {
    /** @var int Maximum users to display per page */
    const MAX_USERS_PER_PAGE = 100;

    /** @var array Existing recipients */
    protected array $existingrecipients = [];

    #[\Override]
    public function find_users($search): array {
        global $DB;

        $whereconditions = [];
        [$wherecondition, $params] = $this->search_sql($search, 'u');
        if ($wherecondition) {
            $whereconditions[] = $wherecondition;
        }

        $existingids = [];
        foreach ($this->existingrecipients as $group) {
            foreach ($group as $user) {
                $existingids[] = $user->id;
            }
        }

        if ($existingids) {
            [$usertest, $userparams] = $DB->get_in_or_equal(
                $existingids,
                SQL_PARAMS_NAMED,
                'ex',
                false
            );
            $whereconditions[] = 'u.id ' . $usertest;
            $params = array_merge($params, $userparams);
        }

        if ($whereconditions) {
            $wherecondition = ' WHERE ' . implode(' AND ', $whereconditions);
        }

        [$groupsql, $groupwheresql, $groupwheresqlparams] = $this->get_groups_sql();

        [$esql, $eparams] = get_enrolled_sql(
            $this->context,
            'moodle/badges:earnbadge',
            0,
            true
        );
        $params = array_merge($params, $eparams, $groupwheresqlparams);

        $fields = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(u.id)';

        $params['badgeid'] = $this->badgeid;
        $params['issuerrole'] = $this->issuerrole;

        $sql = " FROM {user} u JOIN ($esql) je ON je.id = u.id
                 LEFT JOIN {badge_manual_award} bm
                     ON (bm.recipientid = u.id AND bm.badgeid = :badgeid AND bm.issuerrole = :issuerrole)
                 $groupsql
                 $wherecondition AND bm.id IS NULL
                 $groupwheresql";

        [$sort, $sortparams] = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > self::MAX_USERS_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql(
            $fields . $sql . $order,
            array_merge($params, $sortparams)
        );

        if (empty($availableusers)) {
            return [];
        }

        return [
            get_string('potentialrecipients', 'badges') => $availableusers,
        ];
    }

    /**
     * Sets the existing recipients to exclude from potential recipients.
     *
     * @param array $users Array of existing recipients grouped by category.
     * @return void
     */
    public function set_existing_recipients(array $users): void {
        $this->existingrecipients = $users;
    }
}
