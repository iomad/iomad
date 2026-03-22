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
 * Privacy Subsystem implementation for local_iomad.
 *
 * @package    local_iomad
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\privacy;

use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use context_system;
use context_user;
use core_user;

/**
 * Privacy Subsystem implementation for local_iomad.
 *
 * @package    local_iomad
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_iomad_company_users',
            [
                'companyid' => 'privacy:metadata:company_users:companyid',
                'userid' => 'privacy:metadata:company_users:userid',
                'managertype' => 'privacy:metadata:company_users:managertype',
                'departmentid' => 'privacy:metadata:company_users:departmentid',
                'suspended' => 'privacy:metadata:company_users:suspended',
            ],
            'privacy:metadata:local_iomad_company_users'
        );

        $collection->add_database_table(
            'local_iomad_company_license_users',
            [
                'licenseid' => 'privacy:metadata:companylicense_users:licenseid',
                'userid' => 'privacy:metadata:companylicense_users:userid',
                'isusing' => 'privacy:metadata:companylicense_users:isusing',
                'timecompleted' => 'privacy:metadata:companylicense_users:timecompleted',
                'score' => 'privacy:metadata:companylicense_users:score',
                'result' => 'privacy:metadata:companylicense_users:result',
                'courseid' => 'privacy:metadata:companylicense_users:courseid',
                'issuedate' => 'privacy:metadata:companylicense_users:issuedate',
                'groupid' => 'privacy:metadata:companylicense_users:groupid',
            ],
            'privacy:metadata:local_iomad_company_license_users'
        );

        $collection->add_database_table(
            'local_iomad_tracks',
            [
                'id' => 'privacy:metadata:local_iomad_track:id',
                'courseid' => 'privacy:metadata:local_iomad_track:courseid',
                'coursename' => 'privacy:metadata:local_iomad_track:coursename',
                'userid' => 'privacy:metadata:local_iomad_track:userid',
                'timecompleted' => 'privacy:metadata:local_iomad_track:timecompleted',
                'timeenrolled' => 'privacy:metadata:local_iomad_track:timeenrolled',
                'timestarted' => 'privacy:metadata:local_iomad_track:timestarted',
                'finalscore' => 'privacy:metadata:local_iomad_track:finalscore',
                'companyid' => 'privacy:metadata:local_iomad_track:companyid',
                'licenseid' => 'privacy:metadata:local_iomad_track:licenseid',
                'licensename' => 'privacy:metadata:local_iomad_track:licensename',
                'licenseallocated' => 'privacy:metadata:local_iomad_track:licenseallocated',
            ],
            'privacy:metadata:local_iomad_tracks'
        );

        $collection->add_database_table(
            'local_iomad_track_certs',
            [
                'id' => 'privacy:metadata:local_iomad_track_certs:id',
                'trackid' => 'privacy:metadata:local_iomad_track_certs:trackid',
                'filename' => 'privacy:metadata:local_iomad_track_certs:filename',
            ],
            'privacy:metadata:local_iomad_track_certs'
        );

        $collection->add_database_table(
            'local_iomad_emails',
            [
                'id' => 'privacy:metadata:local_email:id',
                'templatename' => 'privacy:metadata:local_email:templatename',
                'sent' => 'privacy:metadata:local_email:sent',
                'subject' => 'privacy:metadata:local_email:subject',
                'body' => 'privacy:metadata:local_email:body',
                'courseid' => 'privacy:metadata:local_email:courseid',
                'userid' => 'privacy:metadata:local_email:userid',
                'invoiceid' => 'privacy:metadata:local_email:invoiceid',
                'classroomid' => 'privacy:metadata:local_email:senderid',
                'headers' => 'privacy:metadata:local_email:headers',
            ],
            'privacy:metadata:local_iomad_emails'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // System context only.
        $sql = "SELECT c.id
                  FROM {context} c
                WHERE contextlevel = :contextlevel";

        $params = [
            'userid'  => $userid,
            'contextlevel'  => CONTEXT_SYSTEM,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        $context = context_system::instance();

        // Get the company information.
        if ($companies = $DB->get_records('local_iomad_company_users', ['userid' => $user->id])) {
            $companiesout = (object) [];
            $companiesout->companies = $companies;
            writer::with_context($context)->export_data([get_string('companyusers', 'block_iomad_company_admin')], $companiesout);
        }

        // Get the license allocation information.
        if ($licenses = $DB->get_records('local_iomad_company_license_users', ['userid' => $user->id])) {
            $licensesout = (object) [];
            foreach ($licenses as $id => $license) {
                if (!empty($license->issuedate)) {
                    $licenses[$id]->issuedate = transform::datetime($license->issuedate);
                }
                if (!empty($license->timecompleted)) {
                    $licenses[$id]->timecompleted = transform::datetime($license->timecompleted);
                }
            }
            $licensesout->licenses = $licenses;
            writer::with_context($context)->export_data([get_string('licenseusers', 'block_iomad_company_admin')], $licensesout);
        }

        // Get the tracking table entreies
        if ($tracks = $DB->get_records('local_iomad_tracks', ['userid' => $user->id])) {
            $trackout = (object) [];
            $trackout->tracks = [];
            $trackout->certs = [];
            foreach ($tracks as $track) {
                if (!empty($track->timeenrolled)) {
                    $track->timeenrolled = transform::datetime($track->timeenrolled);
                }
                if (!empty($track->timestarted)) {
                    $track->timestarted = transform::datetime($track->timestarted);
                }
                if (!empty($track->timecompleted)) {
                    $track->timecompleted = transform::datetime($track->timecompleted);
                }
                if (!empty($track->timeexpires)) {
                    $track->timeexpires = transform::datetime($track->timeexpires);
                }
                if (!empty($track->licenseallocated)) {
                    $track->licenseallocated = transform::datetime($track->licenseallocated);
                }
                if (!empty($track->modifiedtime)) {
                    $track->modifiedtime = transform::datetime($track->modifiedtime);
                }
                $trackout->tracks[$track->id] = $track;
                if ($certinfos = $DB->get_records('local_iomad_track_certs', ['trackid' => $track->id])) {
                    foreach ($certinfos as $certinfo) {
                        // Export the track info
                        $trackout->certs[$cert->id] = $certinfo;
                        //writer::with_context($context)->export_data([], $certinfo);
                    }
                }
            }
            writer::with_context($context)->export_data([get_string('coursecompletions', 'moodle')], $trackout);
        }

        // Get the emails information.
        $emailsql = "SELECT * FROM {local_iomad_emails}
                     WHERE userid = :userid
                     OR senderid = :senderid
                     OR " . $DB->sql_like('headers', ':email');
        $params = ['userid' => $user->id,
                   'senderid' => $user->id,
                   'email' => $user->email];
        if ($emails = $DB->get_records_sql($emailsql, $params)) {
            $emailsout = (object) [];
            foreach ($emails as $id => $email) {
                if (!empty($email->modifiedtime)) {
                    $emails[$id]->modifiedtime = transform::datetime($email->modifiedtime);
                }
                if (!empty($email->sent)) {
                    $emails[$id]->sent = transform::datetime($email->sent);
                }
                if (!empty($email->due)) {
                    $emails[$id]->due = transform::datetime($email->due);
                }
            }
            $emailsout->emails = $emails;
            writer::with_context($context)->export_data([get_string('email', 'local_email')], $emailsout);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $CFG, $DB;

        if (empty($context)) {
            return;
        }

        if (!$context instanceof context_user) {
            return;
        }

        $DB->delete_records('local_iomad_company_users', ['userid' => $context->instanceid]);
        $DB->set_field('local_iomad_company_license_users', 'userid', '-1', ['userid' => $context->instanceid]);

        // Get the track records.
        $trackrecs = $DB->get_records('local_iomad_track', ['userid' => $context->instanceid]);
        foreach ($trackrecs as $trackrec) {
            // Get the certs.
            if ($certs = $DB->get_records('local_iomad_track_certs', ['trackid' => $trackrec->id])) {
                // Delete the files.
                require_once($CFG->libdir . '/filelib.php');
                foreach ($certs as $cert) {
                    continue;
                    if ($file = $DB->get_record(
                        'files',
                        [
                            'component' => 'local_iomad_track',
                            'itemid' => $cert->trackid,
                            'filename' => $cert->filename,
                        ])) {
                        $filedir1 = substr($file->contenthash,0,2);
                        $filedir2 = substr($file->contenthash,2,2);
                        $filepath = $CFG->dataroot . '/filedir/' .
                                    $filedir1 . '/' . $filedir2 .
                                    '/' . $file->contenthash;
                        fulldelete($filepath);
                    }
                }
                $DB->delete_records('local_iomad_track_certs', ['id' => $cert->id]);
                $DB->delete_records(
                    'files',
                    [
                        'component' => 'local_iomad',
                        'area' => 'certificate_issue',
                        'contextid' => $context->instanceid,
                    ]
                );
            }

            // Delete the track record.
            $DB->delete_records('local_iomad_tracks', ['id' => $trackrec->id]);
        }

        // Get the user from the context.
        $user = core_user::get_user($context->instanceid);

        // Get any received, sent or cc'd emails.
        $emailsql = "SELECT * FROM {local_iomad_emails}
                     WHERE userid = :userid
                     OR senderid = :senderid
                     OR " . $DB->sql_like('headers', ':email');
        $params = ['userid' => $user->id,
                   'senderid' => $user->id,
                   'email' => '%' . $user->email . '%'];
        if ($emails = $DB->get_records_sql($emailsql, $params)) {
            foreach ($emails as $email){
                $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $CFG, $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $usercontext = context_user::instance($user->id);
        $DB->delete_records('local_iomad_company_users', ['userid' => $userid]);
        $DB->set_field('local_iomad_company_license_users', 'userid', '-1', ['userid' => $userid]);

        // Get the track records.
        $trackrecs = $DB->get_records('local_iomad_track', ['userid' => $userid]);
        foreach ($trackrecs as $trackrec) {
            // Get the certs.
            if ($certs = $DB->get_records('local_iomad_track_certs', ['trackid' => $trackrec->id])) {
                // Delete the files.
                require_once($CFG->libdir . '/filelib.php');
                foreach ($certs as $cert) {
                    continue;
                    if ($file = $DB->get_record(
                        'files',
                        [
                            'component' => 'local_iomad_track',
                            'itemid' => $cert->trackid,
                            'filename' => $cert->filename,
                        ])) {
                        $filedir1 = substr($file->contenthash,0,2);
                        $filedir2 = substr($file->contenthash,2,2);
                        $filepath = $CFG->dataroot . '/filedir/' .
                                    $filedir1 . '/' . $filedir2 .
                                    '/' . $file->contenthash;
                        fulldelete($filepath);
                    }
                }
                $DB->delete_records('local_iomad_track_certs', ['id' => $cert->id]);
                $DB->delete_records(
                    'files',
                    [
                        'component' => 'local_iomad',
                        'area' => 'certificate_issue',
                        'contextid' => $usercontext->id,
                    ]
                );
            }

            // Delete the track record.
            $DB->delete_records('local_iomad_tracks', ['id' => $trackrec->id]);
        }

        // Get the user from the context.
        $user = core_user::get_user($userid);

        // Get any received, sent or cc'd emails.
        $emailsql = "SELECT * FROM {local_iomad_emails}
                     WHERE userid = :userid
                     OR senderid = :senderid
                     OR " . $DB->sql_like('headers', ':email');
        $params = ['userid' => $user->id,
                   'senderid' => $user->id,
                   'email' => '%' . $user->email . '%'];
        if ($emails = $DB->get_records_sql($emailsql, $params)) {
            foreach ($emails as $email){
                $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $params = [
            'userid' => $context->id,
            'contextuser' => CONTEXT_USER,
        ];

        $sql = "SELECT cu.userid as userid
                  FROM {local_iomad_company_users} cu
                  JOIN {context} ctx
                       ON ctx.instanceid = cu.userid
                       AND ctx.contextlevel = :contextuser
                 WHERE ctx.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT lit.userid as userid
                  FROM {local_iomad_tracks} lit
                  JOIN {context} ctx
                       ON ctx.instanceid = lit.userid
                       AND ctx.contextlevel = :contextuser
                 WHERE ctx.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT e.userid as userid
                  FROM {local_iomad_emails} e
                  JOIN {context} ctx
                       ON ctx.instanceid = e.userid
                       AND ctx.contextlevel = :contextuser
                 WHERE ctx.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $select = "userid {$usersql}";

        $DB->delete_records_select('local_report_user_license_allocations', $select, $params);
        $DB->delete_records_select('local_iomad_company_users', "userid {$usersql}", $params);
        $DB->set_field_select(
            'local_iomad_company_license_users',
            'userid',
            '-1',
            "userid {$usersql}",
            $params
        );

        // Get the track records.
        $trackrecs = $DB->get_records_select('local_iomad_track', "users {$usersql}", $params);
        foreach ($trackrecs as $trackrec) {
            $usercontext = context_user::instance($trackrec->userid);
            // Get the certs.
            if ($certs = $DB->get_records('local_iomad_track_certs', ['trackid' => $trackrec->id])) {
                // Delete the files.
                require_once($CFG->libdir . '/filelib.php');
                foreach ($certs as $cert) {
                    continue;
                    if ($file = $DB->get_record(
                        'files',
                        [
                            'component' => 'local_iomad_track',
                            'itemid' => $cert->trackid,
                            'filename' => $cert->filename,
                        ])) {
                        $filedir1 = substr($file->contenthash,0,2);
                        $filedir2 = substr($file->contenthash,2,2);
                        $filepath = $CFG->dataroot . '/filedir/' .
                                    $filedir1 . '/' . $filedir2 .
                                    '/' . $file->contenthash;
                        fulldelete($filepath);
                    }
                }
                $DB->delete_records('local_iomad_track_certs', ['id' => $cert->id]);
                $DB->delete_records(
                    'files',
                    [
                        'component' => 'local_iomad',
                        'area' => 'certificate_issue',
                        'contextid' => $usercontext->id,
                    ]
                );
            }

            // Delete the track record.
            $DB->delete_records('local_iomad_tracks', ['id' => $trackrec->id]);
        }

        // Deal with emails.
        foreach ($userids as $userid) {
            // Get the user from the context.
            $user = core_user::get_user($userid);

            // Get any received, sent or cc'd emails.
            $emailsql = "SELECT * FROM {local_iomad_emails}
                        WHERE userid = :userid
                        OR senderid = :senderid
                        OR " . $DB->sql_like('headers', ':email');
            $params = ['userid' => $user->id,
                    'senderid' => $user->id,
                    'email' => '%' . $user->email . '%'];
            if ($emails = $DB->get_records_sql($emailsql, $params)) {
                foreach ($emails as $email){
                    $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                }
            }
        }
    }
}
