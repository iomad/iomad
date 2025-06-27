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

declare(strict_types=1);

namespace local_iomadcustompage\privacy;

use coding_exception;
use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_iomadcustompage\local\models\audience;
use local_iomadcustompage\local\models\page;
use stdClass;

/**
 * Privacy Subsystem for local_iomadcustompage
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Returns metadata about the component
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(page::TABLE, [
            'name' => 'privacy:metadata:page:name',
            'title' => 'privacy:metadata:page:title',
            'usercreated' => 'privacy:metadata:page:usercreated',
            'usermodified' => 'privacy:metadata:page:usermodified',
            'timecreated' => 'privacy:metadata:page:timecreated',
            'timemodified' => 'privacy:metadata:page:timemodified',
        ], 'privacy:metadata:page');

        $collection->add_database_table(audience::TABLE, [
            'classname' => 'privacy:metadata:audience:classname',
            'configdata' => 'privacy:metadata:audience:configdata',
            'heading' => 'privacy:metadata:audience:heading',
            'usercreated' => 'privacy:metadata:audience:usercreated',
            'usermodified' => 'privacy:metadata:audience:usermodified',
            'timecreated' => 'privacy:metadata:audience:timecreated',
            'timemodified' => 'privacy:metadata:audience:timemodified',
        ], 'privacy:metadata:audience');

        return $collection;
    }

  /**
   * Get export sub context for a page
   *
   * @param page $page
   * @return array
   * @throws coding_exception
   */
    public static function get_export_subcontext(page $page): array {
        $pagenode = implode('-', [
            $page->get('id'),
            clean_filename($page->get_formatted_name()),
        ]);

        return [get_string('iomadcustompage', 'local_iomadcustompage'), $pagenode];
    }

    /**
     * Get the list of contexts that contain user information for the specified user
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Locate all contexts for page the user has created, or pages they have created audience for.
        $sql = '
            SELECT p.contextid
              FROM {' . page::TABLE . '} p
             WHERE (p.usercreated = ?
                 OR p.usermodified = ?
                 OR p.id IN (
                    SELECT a.pageid
                      FROM {' . audience::TABLE . '} a
                     WHERE a.usercreated = ? OR a.usermodified = ?
                    )
                   )';

        return $contextlist->add_from_sql($sql, array_fill(0, 4, $userid));
    }

    /**
     * Get users in context
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $select = 'p.contextid = :contextid';
        $params = ['contextid' => $userlist->get_context()->id];

        // Users who have created pages.
        $sql = 'SELECT p.usercreated, p.usermodified
                 FROM {' . page::TABLE . '} p
                WHERE ' . $select;
        $userlist->add_from_sql('usercreated', $sql, $params);
        $userlist->add_from_sql('usermodified', $sql, $params);

        // Users who have created audiences.
        $sql = 'SELECT a.usercreated, a.usermodified
                  FROM {' . audience::TABLE . '} a
                  JOIN {' . page::TABLE . '} p ON p.id = a.pageid
                WHERE ' . $select;
        $userlist->add_from_sql('usercreated', $sql, $params);
        $userlist->add_from_sql('usermodified', $sql, $params);
    }

  /**
   * Export all user data for the specified user in the specified contexts
   *
   * @param approved_contextlist $contextlist
   * @throws coding_exception
   */
    public static function export_user_data(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        // We need to get all pages that the user has created, or pages they have created audience for.
        $select = '(usercreated = ? OR usermodified = ? OR id IN (
            SELECT a.pageid
              FROM {' . audience::TABLE . '} a
             WHERE a.usercreated = ? OR a.usermodified = ?
        ))';
        $params = array_fill(0, 4, $user->id);

        foreach (page::get_records_select($select, $params) as $page) {
            $subcontext = static::get_export_subcontext($page);

            self::export_page($subcontext, $page);

            $select = 'pageid = ? AND (usercreated = ? OR usermodified = ?)';
            $params = [$page->get('id'), $user->id, $user->id];

            // Audiences.
            if ($audiences = audience::get_records_select($select, $params)) {
                static::export_audiences($page->get_context(), $subcontext, $audiences);
            }
        }
    }

    /**
     * Delete data for all users in context
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        // We don't perform any deletion of user data.
    }

    /**
     * Delete data for user
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // We don't perform any deletion of user data.
    }

    /**
     * Delete data for users
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // We don't perform any deletion of user data.
    }

  /**
   * Export given page in context
   *
   * @param array $subcontext
   * @param page $page
   * @throws coding_exception
   */
    protected static function export_page(array $subcontext, page $page): void {

        $pagedata = (object) [
            'name' => $page->get_formatted_name(),
            'usercreated' => transform::user($page->get('usercreated')),
            'usermodified' => transform::user($page->get('usermodified')),
            'timecreated' => transform::datetime($page->get('timecreated')),
            'timemodified' => transform::datetime($page->get('timemodified')),
        ];

        writer::with_context($page->get_context())->export_data($subcontext, $pagedata);
    }

  /**
   * Export given audiences in context
   *
   * @param context $context
   * @param array $subcontext
   * @param audience[] $audiences
   * @throws coding_exception
   */
    protected static function export_audiences(context $context, array $subcontext, array $audiences): void {
        $audiencedata = array_map(static function(audience $audience) use ($context): stdClass {
            // Show the audience name, if it exists.
            $classname = $audience->get('classname');
            if (class_exists($classname)) {
                $classname = $classname::instance()->get_name();
            }

            return (object) [
                'classname' => $classname,
                'heading' => $audience->get_formatted_heading($context),
                'configdata' => $audience->get('configdata'),
                'usercreated' => transform::user($audience->get('usercreated')),
                'usermodified' => transform::user($audience->get('usermodified')),
                'timecreated' => transform::datetime($audience->get('timecreated')),
                'timemodified' => transform::datetime($audience->get('timemodified')),
            ];
        }, $audiences);

        writer::with_context($context)->export_related_data($subcontext, 'audiences', (object) ['data' => $audiencedata]);
    }
}
