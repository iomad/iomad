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
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core_group
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Groups not used in course or activity
 */
define('NOGROUPS', 0);

/**
 * Groups used, users do not see other groups
 */
define('SEPARATEGROUPS', 1);

/**
 * Groups used, students see other groups
 */
define('VISIBLEGROUPS', 2);


/**
 * Determines if a group with a given groupid exists.
 *
 * @category group
 * @param int $groupid The groupid to check for
 * @return bool True if the group exists, false otherwise or if an error
 * occurred.
 */
function groups_group_exists($groupid) {
    global $DB;
    return $DB->record_exists('groups', array('id'=>$groupid));
}

/**
 * Gets the name of a group with a specified id
 *
 * @category group
 * @param int $groupid The id of the group
 * @return string The name of the group
 */
function groups_get_group_name($groupid) {
    global $DB;
    return $DB->get_field('groups', 'name', array('id'=>$groupid));
}

/**
 * Gets the name of a grouping with a specified id
 *
 * @category group
 * @param int $groupingid The id of the grouping
 * @return string The name of the grouping
 */
function groups_get_grouping_name($groupingid) {
    global $DB;
    return $DB->get_field('groupings', 'name', array('id'=>$groupingid));
}

/**
 * Returns the groupid of a group with the name specified for the course.
 * Group names should be unique in course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $name name of group (without magic quotes)
 * @return int $groupid
 */
function groups_get_group_by_name($courseid, $name) {
    $data = groups_get_course_data($courseid);
    foreach ($data->groups as $group) {
        if ($group->name == $name) {
            return $group->id;
        }
    }
    return false;
}

/**
 * Returns the groupid of a group with the idnumber specified for the course.
 * Group idnumbers should be unique within course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $idnumber idnumber of group
 * @return group object
 */
function groups_get_group_by_idnumber($courseid, $idnumber) {
    if (empty($idnumber)) {
        return false;
    }
    $data = groups_get_course_data($courseid);
    foreach ($data->groups as $group) {
        if ($group->idnumber == $idnumber) {
            return $group;
        }
    }
    return false;
}

/**
 * Returns the groupingid of a grouping with the name specified for the course.
 * Grouping names should be unique in course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $name name of group (without magic quotes)
 * @return int $groupid
 */
function groups_get_grouping_by_name($courseid, $name) {
    $data = groups_get_course_data($courseid);
    foreach ($data->groupings as $grouping) {
        if ($grouping->name == $name) {
            return $grouping->id;
        }
    }
    return false;
}

/**
 * Returns the groupingid of a grouping with the idnumber specified for the course.
 * Grouping names should be unique within course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $idnumber idnumber of the group
 * @return grouping object
 */
function groups_get_grouping_by_idnumber($courseid, $idnumber) {
    if (empty($idnumber)) {
        return false;
    }
    $data = groups_get_course_data($courseid);
    foreach ($data->groupings as $grouping) {
        if ($grouping->idnumber == $idnumber) {
            return $grouping;
        }
    }
    return false;
}

/**
 * Get the group object
 *
 * @category group
 * @param int $groupid ID of the group.
 * @param string $fields (default is all fields)
 * @param int $strictness (IGNORE_MISSING - default)
 * @return bool|stdClass group object or false if not found
 * @throws dml_exception
 */
function groups_get_group($groupid, $fields='*', $strictness=IGNORE_MISSING) {
    global $DB;
    return $DB->get_record('groups', array('id'=>$groupid), $fields, $strictness);
}

/**
 * Get the grouping object
 *
 * @category group
 * @param int $groupingid ID of the group.
 * @param string $fields
 * @param int $strictness (IGNORE_MISSING - default)
 * @return stdClass group object
 */
function groups_get_grouping($groupingid, $fields='*', $strictness=IGNORE_MISSING) {
    global $DB;
    return $DB->get_record('groupings', array('id'=>$groupingid), $fields, $strictness);
}

/**
 * Gets array of all groups in a specified course.
 *
 * @category group
 * @param int $courseid The id of the course.
 * @param mixed $userid optional user id or array of ids, returns only groups of the user.
 * @param int $groupingid optional returns only groups in the specified grouping.
 * @param string $fields
 * @param bool $withmembers If true - this will return an extra field which is the list of userids that
 *                          are members of this group.
 * @return array Returns an array of the group objects (userid field returned if array in $userid)
 */
function groups_get_all_groups($courseid, $userid=0, $groupingid=0, $fields='g.*', $withmembers=false) {
    global $DB;

    // We need to check that we each field in the fields list belongs to the group table and that it has not being
    // aliased. If its something else we need to avoid the cache and run the query as who knows whats going on.
    $knownfields = true;
    if ($fields !== 'g.*') {
        // Quickly check if the first field is no longer g.id as using the
        // cache will return an array indexed differently than when expect
        if (strpos($fields, 'g.*') !== 0 && strpos($fields, 'g.id') !== 0) {
            $knownfields = false;
        } else {
            $fieldbits = explode(',', $fields);
            foreach ($fieldbits as $bit) {
                $bit = trim($bit);
                if (strpos($bit, 'g.') !== 0 or stripos($bit, ' AS ') !== false) {
                    $knownfields = false;
                    break;
                }
            }
        }
    }

    if (empty($userid) && $knownfields && !$withmembers) {
        // We can use the cache.
        $data = groups_get_course_data($courseid);
        if (empty($groupingid)) {
            // All groups.. Easy!
            $groups = $data->groups;
        } else {
            $groups = array();
            foreach ($data->mappings as $mapping) {
                if ($mapping->groupingid != $groupingid) {
                    continue;
                }
                if (isset($data->groups[$mapping->groupid])) {
                    $groups[$mapping->groupid] = $data->groups[$mapping->groupid];
                }
            }
        }
        // Yay! We could use the cache. One more query saved.
        return $groups;
    }
    $memberselect = '';
    $memberjoin = '';

    if (empty($userid)) {
        $userfrom  = "";
        $userwhere = "";
        $params = array();
    } else {
        list($usql, $params) = $DB->get_in_or_equal($userid);
        $userfrom  = ", {groups_members} gm";
        $userwhere = "AND g.id = gm.groupid AND gm.userid $usql";
    }

    if (!empty($groupingid)) {
        $groupingfrom  = ", {groupings_groups} gg";
        $groupingwhere = "AND g.id = gg.groupid AND gg.groupingid = ?";
        $params[] = $groupingid;
    } else {
        $groupingfrom  = "";
        $groupingwhere = "";
    }

    if ($withmembers) {
        $memberselect = $DB->sql_concat("COALESCE(ugm.userid, 0)", "':'", 'g.id') . ' AS ugmid, ugm.userid, ';
        $memberjoin = ' LEFT JOIN {groups_members} ugm ON ugm.groupid = g.id ';
    }

    array_unshift($params, $courseid);

    $results = $DB->get_records_sql("SELECT $memberselect $fields
                                   FROM {groups} g $userfrom $groupingfrom $memberjoin
                                  WHERE g.courseid = ? $userwhere $groupingwhere
                               ORDER BY name ASC", $params);

    if ($withmembers) {
        // We need to post-process the results back into standard format.
        $groups = [];
        foreach ($results as $row) {
            if (!isset($groups[$row->id])) {
                $row->members = [$row->userid => $row->userid];
                unset($row->userid);
                unset($row->ugmid);
                $groups[$row->id] = $row;
            } else {
                $groups[$row->id]->members[$row->userid] = $row->userid;
            }
        }
        $results = $groups;
    }

    return $results;
}

/**
 * Gets array of all groups in a set of course.
 *
 * @category group
 * @param array $courses Array of course objects or course ids.
 * @return array Array of groups indexed by course id.
 */
function groups_get_all_groups_for_courses($courses) {
    global $DB;

    if (empty($courses)) {
        return [];
    }

    $groups = [];
    $courseids = [];

    foreach ($courses as $course) {
        $courseid = is_object($course) ? $course->id : $course;
        $groups[$courseid] = [];
        $courseids[] = $courseid;
    }

    $groupfields = [
        'g.id as gid',
        'g.courseid',
        'g.idnumber',
        'g.name',
        'g.description',
        'g.descriptionformat',
        'g.enrolmentkey',
        'g.picture',
        'g.hidepicture',
        'g.timecreated',
        'g.timemodified'
    ];

    $groupsmembersfields = [
        'gm.id as gmid',
        'gm.groupid',
        'gm.userid',
        'gm.timeadded',
        'gm.component',
        'gm.itemid'
    ];

    $concatidsql = $DB->sql_concat_join("'-'", ['g.id', 'COALESCE(gm.id, 0)']) . ' AS uniqid';
    list($courseidsql, $params) = $DB->get_in_or_equal($courseids);
    $groupfieldssql = implode(',', $groupfields);
    $groupmembersfieldssql = implode(',', $groupsmembersfields);
    $sql = "SELECT {$concatidsql}, {$groupfieldssql}, {$groupmembersfieldssql}
              FROM {groups} g
         LEFT JOIN {groups_members} gm
                ON gm.groupid = g.id
             WHERE g.courseid {$courseidsql}";

    $results = $DB->get_records_sql($sql, $params);

    // The results will come back as a flat dataset thanks to the left
    // join so we will need to do some post processing to blow it out
    // into a more usable data structure.
    //
    // This loop will extract the distinct groups from the result set
    // and add it's list of members to the object as a property called
    // 'members'. Then each group will be added to the result set indexed
    // by it's course id.
    //
    // The resulting data structure for $groups should be:
    // $groups = [
    //      '1' = [
    //          '1' => (object) [
    //              'id' => 1,
    //              <rest of group properties>
    //              'members' => [
    //                  '1' => (object) [
    //                      <group member properties>
    //                  ],
    //                  '2' => (object) [
    //                      <group member properties>
    //                  ]
    //              ]
    //          ],
    //          '2' => (object) [
    //              'id' => 2,
    //              <rest of group properties>
    //              'members' => [
    //                  '1' => (object) [
    //                      <group member properties>
    //                  ],
    //                  '3' => (object) [
    //                      <group member properties>
    //                  ]
    //              ]
    //          ]
    //      ]
    // ]
    //
    foreach ($results as $key => $result) {
        $groupid = $result->gid;
        $courseid = $result->courseid;
        $coursegroups = $groups[$courseid];
        $groupsmembersid = $result->gmid;
        $reducefunc = function($carry, $field) use ($result) {
            // Iterate over the groups properties and pull
            // them out into a separate object.
            list($prefix, $field) = explode('.', $field);

            if (property_exists($result, $field)) {
                $carry[$field] = $result->{$field};
            }

            return $carry;
        };

        if (isset($coursegroups[$groupid])) {
            $group = $coursegroups[$groupid];
        } else {
            $initial = [
                'id' => $groupid,
                'members' => []
            ];
            $group = (object) array_reduce(
                $groupfields,
                $reducefunc,
                $initial
            );
        }

        if (!empty($groupsmembersid)) {
            $initial = ['id' => $groupsmembersid];
            $groupsmembers = (object) array_reduce(
                $groupsmembersfields,
                $reducefunc,
                $initial
            );

            $group->members[$groupsmembers->userid] = $groupsmembers;
        }

        $coursegroups[$groupid] = $group;
        $groups[$courseid] = $coursegroups;
    }

    return $groups;
}

/**
 * Gets array of all groups in current user.
 *
 * @since Moodle 2.5
 * @category group
 * @return array Returns an array of the group objects.
 */
function groups_get_my_groups() {
    global $DB, $USER;
    return $DB->get_records_sql("SELECT *
                                   FROM {groups_members} gm
                                   JOIN {groups} g
                                    ON g.id = gm.groupid
                                  WHERE gm.userid = ?
                                   ORDER BY name ASC", array($USER->id));
}

/**
 * Returns info about user's groups in course.
 *
 * @category group
 * @param int $courseid
 * @param int $userid $USER if not specified
 * @return array Array[groupingid][groupid] including grouping id 0 which means all groups
 */
function groups_get_user_groups($courseid, $userid=0) {
    global $USER, $DB;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cache = cache::make('core', 'user_group_groupings');

    // Try to retrieve group ids from the cache.
    $usergroups = $cache->get($userid);

    if ($usergroups === false) {
        $sql = "SELECT g.id, g.courseid, gg.groupingid
                  FROM {groups} g
                  JOIN {groups_members} gm ON gm.groupid = g.id
             LEFT JOIN {groupings_groups} gg ON gg.groupid = g.id
                 WHERE gm.userid = ?";

        $rs = $DB->get_recordset_sql($sql, array($userid));

        $usergroups = array();
        $allgroups  = array();

        foreach ($rs as $group) {
            if (!array_key_exists($group->courseid, $allgroups)) {
                $allgroups[$group->courseid] = array();
            }
            $allgroups[$group->courseid][$group->id] = $group->id;
            if (!array_key_exists($group->courseid, $usergroups)) {
                $usergroups[$group->courseid] = array();
            }
            if (is_null($group->groupingid)) {
                continue;
            }
            if (!array_key_exists($group->groupingid, $usergroups[$group->courseid])) {
                $usergroups[$group->courseid][$group->groupingid] = array();
            }
            $usergroups[$group->courseid][$group->groupingid][$group->id] = $group->id;
        }
        $rs->close();

        foreach (array_keys($allgroups) as $cid) {
            $usergroups[$cid]['0'] = array_keys($allgroups[$cid]); // All user groups in the course.
        }

        // Cache the data.
        $cache->set($userid, $usergroups);
    }

    if (array_key_exists($courseid, $usergroups)) {
        return $usergroups[$courseid];
    } else {
        return array('0' => array());
    }
}

/**
 * Gets an array of all groupings in a specified course. This value is cached
 * for a single course (so you can call it repeatedly for the same course
 * without a performance penalty).
 *
 * @category group
 * @param int $courseid return all groupings from course with this courseid
 * @return array Returns an array of the grouping objects (empty if none)
 */
function groups_get_all_groupings($courseid) {
    $data = groups_get_course_data($courseid);
    return $data->groupings;
}

/**
 * Determines if the user is a member of the given group.
 *
 * If $userid is null, use the global object.
 *
 * @category group
 * @param int $groupid The group to check for membership.
 * @param int $userid The user to check against the group.
 * @return bool True if the user is a member, false otherwise.
 */
function groups_is_member($groupid, $userid=null) {
    global $USER, $DB;

    if (!$userid) {
        $userid = $USER->id;
    }

    return $DB->record_exists('groups_members', array('groupid'=>$groupid, 'userid'=>$userid));
}

/**
 * Determines if current or specified is member of any active group in activity
 *
 * @category group
 * @staticvar array $cache
 * @param stdClass|cm_info $cm course module object
 * @param int $userid id of user, null means $USER->id
 * @return bool true if user member of at least one group used in activity
 */
function groups_has_membership($cm, $userid=null) {
    global $CFG, $USER, $DB;

    static $cache = array();

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cachekey = $userid.'|'.$cm->course.'|'.$cm->groupingid;
    if (isset($cache[$cachekey])) {
        return($cache[$cachekey]);
    }

    if ($cm->groupingid) {
        // find out if member of any group in selected activity grouping
        $sql = "SELECT 'x'
                  FROM {groups_members} gm, {groupings_groups} gg
                 WHERE gm.userid = ? AND gm.groupid = gg.groupid AND gg.groupingid = ?";
        $params = array($userid, $cm->groupingid);

    } else {
        // no grouping used - check all groups in course
        $sql = "SELECT 'x'
                  FROM {groups_members} gm, {groups} g
                 WHERE gm.userid = ? AND gm.groupid = g.id AND g.courseid = ?";
        $params = array($userid, $cm->course);
    }

    $cache[$cachekey] = $DB->record_exists_sql($sql, $params);

    return $cache[$cachekey];
}

/**
 * Returns the users in the specified group.
 *
 * @category group
 * @param int $groupid The groupid to get the users for
 * @param int $fields The fields to return
 * @param int $sort optional sorting of returned users
 * @return array|bool Returns an array of the users for the specified
 * group or false if no users or an error returned.
 */
function groups_get_members($groupid, $fields='u.*', $sort='lastname ASC') {
    global $DB;

    return $DB->get_records_sql("SELECT $fields
                                   FROM {user} u, {groups_members} gm
                                  WHERE u.id = gm.userid AND gm.groupid = ?
                               ORDER BY $sort", array($groupid));
}


/**
 * Returns the users in the specified grouping.
 *
 * @category group
 * @param int $groupingid The groupingid to get the users for
 * @param string $fields The fields to return
 * @param string $sort optional sorting of returned users
 * @return array|bool Returns an array of the users for the specified
 * group or false if no users or an error returned.
 */
function groups_get_grouping_members($groupingid, $fields='u.*', $sort='lastname ASC') {
    global $DB;

    return $DB->get_records_sql("SELECT $fields
                                   FROM {user} u
                                     INNER JOIN {groups_members} gm ON u.id = gm.userid
                                     INNER JOIN {groupings_groups} gg ON gm.groupid = gg.groupid
                                  WHERE  gg.groupingid = ?
                               ORDER BY $sort", array($groupingid));
}

/**
 * Returns effective groupmode used in course
 *
 * @category group
 * @param stdClass $course course object.
 * @return int group mode
 */
function groups_get_course_groupmode($course) {
    return $course->groupmode;
}

/**
 * Returns effective groupmode used in activity, course setting
 * overrides activity setting if groupmodeforce enabled.
 *
 * If $cm is an instance of cm_info it is easier to use $cm->effectivegroupmode
 *
 * @category group
 * @param cm_info|stdClass $cm the course module object. Only the ->course and ->groupmode need to be set.
 * @param stdClass $course object optional course object to improve perf
 * @return int group mode
 */
function groups_get_activity_groupmode($cm, $course=null) {
    if ($cm instanceof cm_info) {
        return $cm->effectivegroupmode;
    }
    if (isset($course->id) and $course->id == $cm->course) {
        //ok
    } else {
        // Get course object (reuse $COURSE if possible).
        $course = get_course($cm->course, false);
    }

    return empty($course->groupmodeforce) ? $cm->groupmode : $course->groupmode;
}

/**
 * Print group menu selector for course level.
 *
 * @category group
 * @param stdClass $course course object
 * @param mixed $urlroot return address. Accepts either a string or a moodle_url
 * @param bool $return return as string instead of printing
 * @return mixed void or string depending on $return param
 */
function groups_print_course_menu($course, $urlroot, $return=false) {
    global $USER, $OUTPUT;

    if (!$groupmode = $course->groupmode) {
        if ($return) {
            return '';
        } else {
            return;
        }
    }

    $context = context_course::instance($course->id);
    $aag = has_capability('moodle/site:accessallgroups', $context);

    $usergroups = array();
    if ($groupmode == VISIBLEGROUPS or $aag) {
        $allowedgroups = groups_get_all_groups($course->id, 0, $course->defaultgroupingid);
        // Get user's own groups and put to the top.
        $usergroups = groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid);
    } else {
        $allowedgroups = groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid);
    }

    $activegroup = groups_get_course_group($course, true, $allowedgroups);

    $groupsmenu = array();
    if (!$allowedgroups or $groupmode == VISIBLEGROUPS or $aag) {
        $groupsmenu[0] = get_string('allparticipants');
    }

    $groupsmenu += groups_sort_menu_options($allowedgroups, $usergroups);

    if ($groupmode == VISIBLEGROUPS) {
        $grouplabel = get_string('groupsvisible');
    } else {
        $grouplabel = get_string('groupsseparate');
    }

    if ($aag and $course->defaultgroupingid) {
        if ($grouping = groups_get_grouping($course->defaultgroupingid)) {
            $grouplabel = $grouplabel . ' (' . format_string($grouping->name) . ')';
        }
    }

    if (count($groupsmenu) == 1) {
        $groupname = reset($groupsmenu);
        $output = $grouplabel.': '.$groupname;
    } else {
        $select = new single_select(new moodle_url($urlroot), 'group', $groupsmenu, $activegroup, null, 'selectgroup');
        $select->label = $grouplabel;
        $output = $OUTPUT->render($select);
    }

    $output = '<div class="groupselector">'.$output.'</div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Turn an array of groups into an array of menu options.
 * @param array $groups of group objects.
 * @return array groupid => formatted group name.
 */
function groups_list_to_menu($groups) {
    $groupsmenu = array();
    foreach ($groups as $group) {
        $groupsmenu[$group->id] = format_string($group->name);
    }
    return $groupsmenu;
}

/**
 * Takes user's allowed groups and own groups and formats for use in group selector menu
 * If user has allowed groups + own groups will add to an optgroup
 * Own groups are removed from allowed groups
 * @param array $allowedgroups All groups user is allowed to see
 * @param array $usergroups Groups user belongs to
 * @return array
 */
function groups_sort_menu_options($allowedgroups, $usergroups) {
    $useroptions = array();
    if ($usergroups) {
        $useroptions = groups_list_to_menu($usergroups);

        // Remove user groups from other groups list.
        foreach ($usergroups as $group) {
            unset($allowedgroups[$group->id]);
        }
    }

    $allowedoptions = array();
    if ($allowedgroups) {
        $allowedoptions = groups_list_to_menu($allowedgroups);
    }

    if ($useroptions && $allowedoptions) {
        return array(
            1 => array(get_string('mygroups', 'group') => $useroptions),
            2 => array(get_string('othergroups', 'group') => $allowedoptions)
        );
    } else if ($useroptions) {
        return $useroptions;
    } else {
        return $allowedoptions;
    }
}

/**
 * Generates html to print menu selector for course level, listing all groups.
 * Note: This api does not do any group mode check use groups_print_course_menu() instead if you want proper checks.
 *
 * @param stdclass          $course  course object.
 * @param string|moodle_url $urlroot return address. Accepts either a string or a moodle_url.
 * @param bool              $update  set this to true to update current active group based on the group param.
 * @param int               $activegroup Change group active to this group if $update set to true.
 *
 * @return string html or void
 */
function groups_allgroups_course_menu($course, $urlroot, $update = false, $activegroup = 0) {
    global $SESSION, $OUTPUT, $USER;

    $groupmode = groups_get_course_groupmode($course);
    $context = context_course::instance($course->id);
    $groupsmenu = array();

    if (has_capability('moodle/site:accessallgroups', $context)) {
        $groupsmenu[0] = get_string('allparticipants');
        $allowedgroups = groups_get_all_groups($course->id, 0, $course->defaultgroupingid);
    } else {
        $allowedgroups = groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid);
    }

    $groupsmenu += groups_list_to_menu($allowedgroups);

    if ($update) {
        // Init activegroup array if necessary.
        if (!isset($SESSION->activegroup)) {
            $SESSION->activegroup = array();
        }
        if (!isset($SESSION->activegroup[$course->id])) {
            $SESSION->activegroup[$course->id] = array(SEPARATEGROUPS => array(), VISIBLEGROUPS => array(), 'aag' => array());
        }
        if (empty($groupsmenu[$activegroup])) {
            $activegroup = key($groupsmenu); // Force set to one of accessible groups.
        }
        $SESSION->activegroup[$course->id][$groupmode][$course->defaultgroupingid] = $activegroup;
    }

    $grouplabel = get_string('groups');
    if (count($groupsmenu) == 0) {
        return '';
    } else if (count($groupsmenu) == 1) {
        $groupname = reset($groupsmenu);
        $output = $grouplabel.': '.$groupname;
    } else {
        $select = new single_select(new moodle_url($urlroot), 'group', $groupsmenu, $activegroup, null, 'selectgroup');
        $select->label = $grouplabel;
        $output = $OUTPUT->render($select);
    }

    return $output;

}

/**
 * Print group menu selector for activity.
 *
 * @category group
 * @param stdClass|cm_info $cm course module object
 * @param string|moodle_url $urlroot return address that users get to if they choose an option;
 *   should include any parameters needed, e.g. "$CFG->wwwroot/mod/forum/view.php?id=34"
 * @param bool $return return as string instead of printing
 * @param bool $hideallparticipants If true, this prevents the 'All participants'
 *   option from appearing in cases where it normally would. This is intended for
 *   use only by activities that cannot display all groups together. (Note that
 *   selecting this option does not prevent groups_get_activity_group from
 *   returning 0; it will still do that if the user has chosen 'all participants'
 *   in another activity, or not chosen anything.)
 * @return mixed void or string depending on $return param
 */
function groups_print_activity_menu($cm, $urlroot, $return=false, $hideallparticipants=false) {
    global $USER, $OUTPUT;

    if ($urlroot instanceof moodle_url) {
        // no changes necessary

    } else {
        if (strpos($urlroot, 'http') !== 0) { // Will also work for https
            // Display error if urlroot is not absolute (this causes the non-JS version to break)
            debugging('groups_print_activity_menu requires absolute URL for ' .
                      '$urlroot, not <tt>' . s($urlroot) . '</tt>. Example: ' .
                      'groups_print_activity_menu($cm, $CFG->wwwroot . \'/mod/mymodule/view.php?id=13\');',
                      DEBUG_DEVELOPER);
        }
        $urlroot = new moodle_url($urlroot);
    }

    if (!$groupmode = groups_get_activity_groupmode($cm)) {
        if ($return) {
            return '';
        } else {
            return;
        }
    }

    $context = context_module::instance($cm->id);
    $aag = has_capability('moodle/site:accessallgroups', $context);

    $usergroups = array();
    if ($groupmode == VISIBLEGROUPS or $aag) {
        $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping
        // Get user's own groups and put to the top.
        $usergroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
    } else {
        $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid); // only assigned groups
    }

    $activegroup = groups_get_activity_group($cm, true, $allowedgroups);

    $groupsmenu = array();
    if ((!$allowedgroups or $groupmode == VISIBLEGROUPS or $aag) and !$hideallparticipants) {
        $groupsmenu[0] = get_string('allparticipants');
    }

    $groupsmenu += groups_sort_menu_options($allowedgroups, $usergroups);

    if ($groupmode == VISIBLEGROUPS) {
        $grouplabel = get_string('groupsvisible');
    } else {
        $grouplabel = get_string('groupsseparate');
    }

    if ($aag and $cm->groupingid) {
        if ($grouping = groups_get_grouping($cm->groupingid)) {
            $grouplabel = $grouplabel . ' (' . format_string($grouping->name) . ')';
        }
    }

    if (count($groupsmenu) == 1) {
        $groupname = reset($groupsmenu);
        $output = $grouplabel.': '.$groupname;
    } else {
        $select = new single_select($urlroot, 'group', $groupsmenu, $activegroup, null, 'selectgroup');
        $select->label = $grouplabel;
        $output = $OUTPUT->render($select);
    }

    $output = '<div class="groupselector">'.$output.'</div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns group active in course, changes the group by default if 'group' page param present
 *
 * @category group
 * @param stdClass $course course bject
 * @param bool $update change active group if group param submitted
 * @param array $allowedgroups list of groups user may access (INTERNAL, to be used only from groups_print_course_menu())
 * @return mixed false if groups not used, int if groups used, 0 means all groups (access must be verified in SEPARATE mode)
 */
function groups_get_course_group($course, $update=false, $allowedgroups=null) {
    global $USER, $SESSION;

    if (!$groupmode = $course->groupmode) {
        // NOGROUPS used
        return false;
    }

    $context = context_course::instance($course->id);
    if (has_capability('moodle/site:accessallgroups', $context)) {
        $groupmode = 'aag';
    }

    if (!is_array($allowedgroups)) {
        if ($groupmode == VISIBLEGROUPS or $groupmode === 'aag') {
            $allowedgroups = groups_get_all_groups($course->id, 0, $course->defaultgroupingid);
        } else {
            $allowedgroups = groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid);
        }
    }

    _group_verify_activegroup($course->id, $groupmode, $course->defaultgroupingid, $allowedgroups);

    // set new active group if requested
    $changegroup = optional_param('group', -1, PARAM_INT);
    if ($update and $changegroup != -1) {

        if ($changegroup == 0) {
            // do not allow changing to all groups without accessallgroups capability
            if ($groupmode == VISIBLEGROUPS or $groupmode === 'aag') {
                $SESSION->activegroup[$course->id][$groupmode][$course->defaultgroupingid] = 0;
            }

        } else {
            if ($allowedgroups and array_key_exists($changegroup, $allowedgroups)) {
                $SESSION->activegroup[$course->id][$groupmode][$course->defaultgroupingid] = $changegroup;
            }
        }
    }

    return $SESSION->activegroup[$course->id][$groupmode][$course->defaultgroupingid];
}

/**
 * Returns group active in activity, changes the group by default if 'group' page param present
 *
 * @category group
 * @param stdClass|cm_info $cm course module object
 * @param bool $update change active group if group param submitted
 * @param array $allowedgroups list of groups user may access (INTERNAL, to be used only from groups_print_activity_menu())
 * @return mixed false if groups not used, int if groups used, 0 means all groups (access must be verified in SEPARATE mode)
 */
function groups_get_activity_group($cm, $update=false, $allowedgroups=null) {
    global $USER, $SESSION;

    if (!$groupmode = groups_get_activity_groupmode($cm)) {
        // NOGROUPS used
        return false;
    }

    $context = context_module::instance($cm->id);
    if (has_capability('moodle/site:accessallgroups', $context)) {
        $groupmode = 'aag';
    }

    if (!is_array($allowedgroups)) {
        if ($groupmode == VISIBLEGROUPS or $groupmode === 'aag') {
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);
        } else {
            $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
        }
    }

    _group_verify_activegroup($cm->course, $groupmode, $cm->groupingid, $allowedgroups);

    // set new active group if requested
    $changegroup = optional_param('group', -1, PARAM_INT);
    if ($update and $changegroup != -1) {

        if ($changegroup == 0) {
            // allgroups visible only in VISIBLEGROUPS or when accessallgroups
            if ($groupmode == VISIBLEGROUPS or $groupmode === 'aag') {
                $SESSION->activegroup[$cm->course][$groupmode][$cm->groupingid] = 0;
            }

        } else {
            if ($allowedgroups and array_key_exists($changegroup, $allowedgroups)) {
                $SESSION->activegroup[$cm->course][$groupmode][$cm->groupingid] = $changegroup;
            }
        }
    }

    return $SESSION->activegroup[$cm->course][$groupmode][$cm->groupingid];
}

/**
 * Gets a list of groups that the user is allowed to access within the
 * specified activity.
 *
 * @category group
 * @param stdClass|cm_info $cm Course-module
 * @param int $userid User ID (defaults to current user)
 * @return array An array of group objects, or false if none
 */
function groups_get_activity_allowed_groups($cm,$userid=0) {
    // Use current user by default
    global $USER;
    if(!$userid) {
        $userid=$USER->id;
    }

    // Get groupmode for activity, taking into account course settings
    $groupmode=groups_get_activity_groupmode($cm);

    // If visible groups mode, or user has the accessallgroups capability,
    // then they can access all groups for the activity...
    $context = context_module::instance($cm->id);
    if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $context, $userid)) {
        return groups_get_all_groups($cm->course, 0, $cm->groupingid);
    } else {
        // ...otherwise they can only access groups they belong to
        return groups_get_all_groups($cm->course, $userid, $cm->groupingid);
    }
}

/**
 * Determine if a given group is visible to user or not in a given context.
 *
 * @since Moodle 2.6
 * @param int      $groupid Group id to test. 0 for all groups.
 * @param stdClass $course  Course object.
 * @param stdClass $cm      Course module object.
 * @param int      $userid  user id to test against. Defaults to $USER.
 * @return boolean true if visible, false otherwise
 */
function groups_group_visible($groupid, $course, $cm = null, $userid = null) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $groupmode = empty($cm) ? groups_get_course_groupmode($course) : groups_get_activity_groupmode($cm, $course);
    if ($groupmode == NOGROUPS || $groupmode == VISIBLEGROUPS) {
        // Groups are not used, or everything is visible, no need to go any further.
        return true;
    }

    $context = empty($cm) ? context_course::instance($course->id) : context_module::instance($cm->id);
    if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
        // User can see everything. Groupid = 0 is handled here as well.
        return true;
    } else if ($groupid != 0) {
        // Group mode is separate, and user doesn't have access all groups capability. Check if user can see requested group.
        $groups = empty($cm) ? groups_get_all_groups($course->id, $userid) : groups_get_activity_allowed_groups($cm, $userid);
        if (array_key_exists($groupid, $groups)) {
            // User can see the group.
            return true;
        }
    }
    return false;
}

/**
 * Get sql and parameters that will return user ids for a group
 *
 * @param int $groupid
 * @return array($sql, $params)
 */
function groups_get_members_ids_sql($groupid) {
    $groupjoin = groups_get_members_join($groupid, 'u.id');

    $sql = "SELECT DISTINCT u.id
              FROM {user} u
            $groupjoin->joins
             WHERE u.deleted = 0";

    return array($sql, $groupjoin->params);
}

/**
 * Get sql join to return users in a group
 *
 * @param int $groupid
 * @param string $useridcolumn The column of the user id from the calling SQL, e.g. u.id
 * @return \core\dml\sql_join Contains joins, wheres, params
 */
function groups_get_members_join($groupid, $useridcolumn) {
    // Use unique prefix just in case somebody makes some SQL magic with the result.
    static $i = 0;
    $i++;
    $prefix = 'gm' . $i . '_';

    $join = "JOIN {groups_members} {$prefix}gm ON ({$prefix}gm.userid = $useridcolumn AND {$prefix}gm.groupid = :{$prefix}gmid)";
    $param = array("{$prefix}gmid" => $groupid);

    return new \core\dml\sql_join($join, '', $param);
}

/**
 * Internal method, sets up $SESSION->activegroup and verifies previous value
 *
 * @param int $courseid
 * @param int|string $groupmode SEPARATEGROUPS, VISIBLEGROUPS or 'aag' (access all groups)
 * @param int $groupingid 0 means all groups
 * @param array $allowedgroups list of groups user can see
 */
function _group_verify_activegroup($courseid, $groupmode, $groupingid, array $allowedgroups) {
    global $SESSION, $USER;

    // init activegroup array if necessary
    if (!isset($SESSION->activegroup)) {
        $SESSION->activegroup = array();
    }
    if (!array_key_exists($courseid, $SESSION->activegroup)) {
        $SESSION->activegroup[$courseid] = array(SEPARATEGROUPS=>array(), VISIBLEGROUPS=>array(), 'aag'=>array());
    }

    // make sure that the current group info is ok
    if (array_key_exists($groupingid, $SESSION->activegroup[$courseid][$groupmode]) and !array_key_exists($SESSION->activegroup[$courseid][$groupmode][$groupingid], $allowedgroups)) {
        // active group does not exist anymore or is 0
        if ($SESSION->activegroup[$courseid][$groupmode][$groupingid] > 0 or $groupmode == SEPARATEGROUPS) {
            // do not do this if all groups selected and groupmode is not separate
            unset($SESSION->activegroup[$courseid][$groupmode][$groupingid]);
        }
    }

    // set up defaults if necessary
    if (!array_key_exists($groupingid, $SESSION->activegroup[$courseid][$groupmode])) {
        if ($groupmode == 'aag') {
            $SESSION->activegroup[$courseid][$groupmode][$groupingid] = 0; // all groups by default if user has accessallgroups

        } else if ($allowedgroups) {
            if ($groupmode != SEPARATEGROUPS and $mygroups = groups_get_all_groups($courseid, $USER->id, $groupingid)) {
                $firstgroup = reset($mygroups);
            } else {
                $firstgroup = reset($allowedgroups);
            }
            $SESSION->activegroup[$courseid][$groupmode][$groupingid] = $firstgroup->id;

        } else {
            // this happen when user not assigned into group in SEPARATEGROUPS mode or groups do not exist yet
            // mod authors must add extra checks for this when SEPARATEGROUPS mode used (such as when posting to forum)
            $SESSION->activegroup[$courseid][$groupmode][$groupingid] = 0;
        }
    }
}

/**
 * Caches group data for a particular course to speed up subsequent requests.
 *
 * @param int $courseid The course id to cache data for.
 * @param cache $cache The cache if it has already been initialised. If not a new one will be created.
 * @return stdClass A data object containing groups, groupings, and mappings.
 */
function groups_cache_groupdata($courseid, cache $cache = null) {
    global $DB;

    if ($cache === null) {
        // Initialise a cache if we wern't given one.
        $cache = cache::make('core', 'groupdata');
    }

    // Get the groups that belong to the course.
    $groups = $DB->get_records('groups', array('courseid' => $courseid), 'name ASC');
    // Get the groupings that belong to the course.
    $groupings = $DB->get_records('groupings', array('courseid' => $courseid), 'name ASC');

    if (!is_array($groups)) {
        $groups = array();
    }

    if (!is_array($groupings)) {
        $groupings = array();
    }

    if (!empty($groupings)) {
        // Finally get the mappings between the two.
        list($insql, $params) = $DB->get_in_or_equal(array_keys($groupings));
        $mappings = $DB->get_records_sql("
                SELECT gg.id, gg.groupingid, gg.groupid
                  FROM {groupings_groups} gg
                  JOIN {groups} g ON g.id = gg.groupid
                 WHERE gg.groupingid $insql
              ORDER BY g.name ASC", $params);
    } else {
        $mappings = array();
    }

    // Prepare the data array.
    $data = new stdClass;
    $data->groups = $groups;
    $data->groupings = $groupings;
    $data->mappings = $mappings;
    // Cache the data.
    $cache->set($courseid, $data);
    // Finally return it so it can be used if desired.
    return $data;
}

/**
 * Gets group data for a course.
 *
 * This returns an object with the following properties:
 *   - groups : An array of all the groups in the course.
 *   - groupings : An array of all the groupings within the course.
 *   - mappings : An array of group to grouping mappings.
 *
 * @param int $courseid The course id to get data for.
 * @param cache $cache The cache if it has already been initialised. If not a new one will be created.
 * @return stdClass
 */
function groups_get_course_data($courseid, cache $cache = null) {
    if ($cache === null) {
        // Initialise a cache if we wern't given one.
        $cache = cache::make('core', 'groupdata');
    }
    // Try to retrieve it from the cache.
    $data = $cache->get($courseid);
    if ($data === false) {
        $data = groups_cache_groupdata($courseid, $cache);
    }
    return $data;
}

/**
 * Determine if the current user can see at least one of the groups of the specified user.
 *
 * @param stdClass $course  Course object.
 * @param int $userid  user id to check against.
 * @param stdClass $cm Course module object. Optional, just for checking at activity level instead course one.
 * @return boolean true if visible, false otherwise
 * @since Moodle 2.9
 */
function groups_user_groups_visible($course, $userid, $cm = null) {
    global $USER;

    $groupmode = empty($cm) ? groups_get_course_groupmode($course) : groups_get_activity_groupmode($cm, $course);
    if ($groupmode == NOGROUPS || $groupmode == VISIBLEGROUPS) {
        // Groups are not used, or everything is visible, no need to go any further.
        return true;
    }

    $context = empty($cm) ? context_course::instance($course->id) : context_module::instance($cm->id);
    if (has_capability('moodle/site:accessallgroups', $context)) {
        // User can see everything.
        return true;
    } else {
        // Group mode is separate, and user doesn't have access all groups capability.
        if (empty($cm)) {
            $usergroups = groups_get_all_groups($course->id, $userid);
            $currentusergroups = groups_get_all_groups($course->id, $USER->id);
        } else {
            $usergroups = groups_get_activity_allowed_groups($cm, $userid);
            $currentusergroups = groups_get_activity_allowed_groups($cm, $USER->id);
        }

        $samegroups = array_intersect_key($currentusergroups, $usergroups);
        if (!empty($samegroups)) {
            // We share groups!
            return true;
        }
    }
    return false;
}

/**
 * Returns the users in the specified groups.
 *
 * This function does not return complete user objects by default. It returns the user_picture basic fields.
 *
 * @param array $groupsids The list of groups ids to check
 * @param array $extrafields extra fields to be included in result
 * @param int $sort optional sorting of returned users
 * @return array|bool Returns an array of the users for the specified group or false if no users or an error returned.
 * @since  Moodle 3.3
 */
function groups_get_groups_members($groupsids, $extrafields=null, $sort='lastname ASC') {
    global $DB;

    $userfields = user_picture::fields('u', $extrafields);
    list($insql, $params) = $DB->get_in_or_equal($groupsids);

    return $DB->get_records_sql("SELECT $userfields
                                   FROM {user} u, {groups_members} gm
                                  WHERE u.id = gm.userid AND gm.groupid $insql
                               GROUP BY $userfields
                               ORDER BY $sort", $params);
}

/**
 * Returns users who share group membership with the specified user in the given actiivty.
 *
 * @param stdClass|cm_info $cm course module
 * @param int $userid user id (empty for current user)
 * @return array a list of user
 * @since  Moodle 3.3
 */
function groups_get_activity_shared_group_members($cm, $userid = null) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER;
    }

    $groupsids = array_keys(groups_get_activity_allowed_groups($cm, $userid));
    // No groups no users.
    if (empty($groupsids)) {
        return [];
    }
    return groups_get_groups_members($groupsids);
}
