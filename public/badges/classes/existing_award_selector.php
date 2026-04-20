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
 * User selector for existing badge recipients.
 *
 * @package    core_badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Dai Nguyen Trong <ngtrdai@hotmail.com> based on Yuliya Bozhko <yuliya.bozhko@totaralms.com> code
 */
class existing_award_selector extends award_selector_base {
    #[\Override]
    public function find_users($search): array {
        global $DB;

        [$wherecondition, $params] = $this->search_sql($search, 'u');
        [$esql, $eparams] = get_enrolled_sql($this->context, 'moodle/badges:earnbadge', 0, true);
        [$groupsql, $groupwheresql, $groupwheresqlparams] = $this->get_groups_sql();
        [$sort, $sortparams] = users_order_by_sql('u', $search, $this->accesscontext);

        $params = array_merge($params, $eparams, $sortparams, $groupwheresqlparams, [
            'badgeid' => $this->badgeid,
            'issuerrole' => $this->issuerrole,
        ]);

        $fields = $this->required_fields_sql('u');

        $recipients = $DB->get_records_sql(
            "SELECT $fields
                   FROM {user} u
                   JOIN ($esql) je ON je.id = u.id
                   JOIN {badge_manual_award} s ON s.recipientid = u.id
                        $groupsql
                  WHERE $wherecondition
                        AND s.badgeid = :badgeid
                        AND s.issuerrole = :issuerrole
                        $groupwheresql
               ORDER BY $sort",
            $params
        );

        return [get_string('existingrecipients', 'badges') => $recipients];
    }
}
