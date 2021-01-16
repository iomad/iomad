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
 * Privacy Subsystem implementation for tool_policy.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\moodle_content_writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the policy tool.
 *
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This tool stores user data.
        \core_privacy\local\metadata\provider,

        // This tool may provide access to and deletion of user data.
        \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'tool_policy_acceptances',
            [
                'policyversionid' => 'privacy:metadata:acceptances:policyversionid',
                'userid' => 'privacy:metadata:acceptances:userid',
                'status' => 'privacy:metadata:acceptances:status',
                'lang' => 'privacy:metadata:acceptances:lang',
                'usermodified' => 'privacy:metadata:acceptances:usermodified',
                'timecreated' => 'privacy:metadata:acceptances:timecreated',
                'timemodified' => 'privacy:metadata:acceptances:timemodified',
                'note' => 'privacy:metadata:acceptances:note',
            ],
            'privacy:metadata:acceptances'
        );

        $collection->add_database_table(
            'tool_policy_versions',
            [
                'name' => 'privacy:metadata:versions:name',
                'type' => 'privacy:metadata:versions:type',
                'audience' => 'privacy:metadata:versions:audience',
                'archived' => 'privacy:metadata:versions:archived',
                'usermodified' => 'privacy:metadata:versions:usermodified',
                'timecreated' => 'privacy:metadata:versions:timecreated',
                'timemodified' => 'privacy:metadata:versions:timemodified',
                'policyid' => 'privacy:metadata:versions:policyid',
                'revision' => 'privacy:metadata:versions:revision',
                'summary' => 'privacy:metadata:versions:summary',
                'summaryformat' => 'privacy:metadata:versions:summaryformat',
                'content' => 'privacy:metadata:versions:content',
                'contentformat' => 'privacy:metadata:versions:contentformat',
            ],
            'privacy:metadata:versions'
        );

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:subsystem:corefiles');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The userid.
     * @return contextlist The list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Policies a user has modified.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {tool_policy_versions} v ON v.usermodified = :userid
                 WHERE c.contextlevel = :contextlevel";
        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        // Policies a user has accepted.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {tool_policy_acceptances} a ON c.instanceid = a.userid
                 WHERE
                    c.contextlevel = :contextlevel
                   AND (
                    a.userid = :userid OR a.usermodified = :usermodified
                   )";
        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
            'usermodified' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // Export user agreements.
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                static::export_policy_agreements_for_context($context);
            } else if ($context->contextlevel == CONTEXT_SYSTEM) {
                static::export_authored_policies($contextlist->get_user());
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * We never delete user agreements to the policies because they are part of privacy data.
     * We never delete policy versions because they are part of privacy data.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * We never delete user agreements to the policies because they are part of privacy data.
     * We never delete policy versions because they are part of privacy data.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }

    /**
     * Export all policy agreements relating to the specified user context.
     *
     * @param \context_user $context The context to export
     */
    protected static function export_policy_agreements_for_context(\context_user $context) {
        global $DB;

        $sysctx = \context_system::instance();
        $fs = get_file_storage();
        $agreementsql = "
            SELECT
                a.id AS agreementid, a.userid, a.timemodified, a.note, a.status,
                a.policyversionid AS versionid, a.usermodified, a.timecreated,
                v.id, v.archived, v.name, v.revision,
                v.summary, v.summaryformat,
                v.content, v.contentformat,
                p.currentversionid
             FROM {tool_policy_acceptances} a
             JOIN {tool_policy_versions} v ON v.id = a.policyversionid
             JOIN {tool_policy} p ON v.policyid = p.id
            WHERE a.userid = :userid OR a.usermodified = :usermodified";

        // Fetch all agreements related to this user.
        $agreements = $DB->get_recordset_sql($agreementsql, [
            'userid' => $context->instanceid,
            'usermodified' => $context->instanceid,
        ]);

        $basecontext = [
            get_string('privacyandpolicies', 'admin'),
            get_string('useracceptances', 'tool_policy'),
        ];

        foreach ($agreements as $agreement) {
            $subcontext = array_merge($basecontext, [get_string('policynamedversion', 'tool_policy', $agreement)]);

            $summary = writer::with_context($context)->rewrite_pluginfile_urls(
                $subcontext,
                'tool_policy',
                'policydocumentsummary',
                $agreement->versionid,
                $agreement->summary
            );
            $content = writer::with_context($context)->rewrite_pluginfile_urls(
                $subcontext,
                'tool_policy',
                'policydocumentcontent',
                $agreement->versionid,
                $agreement->content
            );
            $agreementcontent = (object) [
                'name' => $agreement->name,
                'revision' => $agreement->revision,
                'isactive' => transform::yesno($agreement->versionid == $agreement->currentversionid),
                'isagreed' => transform::yesno($agreement->status),
                'agreedby' => transform::user($agreement->usermodified),
                'timecreated' => transform::datetime($agreement->timecreated),
                'timemodified' => transform::datetime($agreement->timemodified),
                'note' => $agreement->note,
                'summary' => format_text($summary, $agreement->summaryformat),
                'content' => format_text($content, $agreement->contentformat),
            ];

            writer::with_context($context)->export_data($subcontext, $agreementcontent);
            // Manually export the files as they reside in the system context so we can't use
            // the write's helper methods.
            foreach ($fs->get_area_files($sysctx->id, 'tool_policy', 'policydocumentsummary', $agreement->versionid) as $file) {
                writer::with_context($context)->export_file($subcontext, $file);
            }
            foreach ($fs->get_area_files($sysctx->id, 'tool_policy', 'policydocumentcontent', $agreement->versionid) as $file) {
                writer::with_context($context)->export_file($subcontext, $file);
            }
        }
        $agreements->close();
    }

    /**
     * Export all policy agreements that the user authored.
     *
     * @param stdClass $user The user who has created the policies to export.
     */
    protected static function export_authored_policies(\stdClass $user) {
        global $DB;

        // Authored policies are exported against the system.
        $context = \context_system::instance();
        $basecontext = [
            get_string('policydocuments', 'tool_policy'),
        ];

        $sql = "SELECT v.id,
                       v.name,
                       v.revision,
                       v.summary,
                       v.content,
                       v.archived,
                       v.usermodified,
                       v.timecreated,
                       v.timemodified,
                       p.currentversionid
                  FROM {tool_policy_versions} v
                  JOIN {tool_policy} p ON p.id = v.policyid
                 WHERE v.usermodified = :userid";
        $versions = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($versions as $version) {
            $subcontext = array_merge($basecontext, [get_string('policynamedversion', 'tool_policy', $version)]);

            $versioncontent = (object) [
                'name' => $version->name,
                'revision' => $version->revision,
                'summary' => writer::with_context($context)->rewrite_pluginfile_urls(
                    $subcontext,
                    'tool_policy',
                    'policydocumentsummary',
                    $version->id,
                    $version->summary
                ),
                'content' => writer::with_context($context)->rewrite_pluginfile_urls(
                    $subcontext,
                    'tool_policy',
                    'policydocumentcontent',
                    $version->id,
                    $version->content
                ),
                'isactive' => transform::yesno($version->id == $version->currentversionid),
                'isarchived' => transform::yesno($version->archived),
                'createdbyme' => transform::yesno($version->usermodified == $user->id),
                'timecreated' => transform::datetime($version->timecreated),
                'timemodified' => transform::datetime($version->timemodified),
            ];
            writer::with_context($context)
                ->export_data($subcontext, $versioncontent)
                ->export_area_files($subcontext, 'tool_policy', 'policydocumentsummary', $version->id)
                ->export_area_files($subcontext, 'tool_policy', 'policydocumentcontent', $version->id);
        }
        $versions->close();
    }
}
