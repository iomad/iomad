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
 * IOMAD microlearning block class definition
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning;

use block_iomad_microlearning\event\{
    nugget_created,
    nugget_deleted,
    nugget_moved,
    nugget_updated,
    thread_deleted,
    thread_created,
    thread_schedule_updated,
    thread_updated,
};
use context_system;
use html_writer;
use local_iomad\{company, emailtemplate};

/**
 * IOMAD microlearning block class definition
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class microlearning {

    /**
     * Get the html output for any nuggets/threads a user has
     *
     * @return string
     */
    public static function get_my_nuggets() {
        global $DB, $USER;

        // Get any nuggets assigned and not completed.
        if ($mynuggets = $DB->get_records_sql(
            "SELECT mtu.*,
                    mn.name AS nuggetname,
                    mn.cmid,
                    mn.sectionid,
                    mn.url AS url,
                    mt.name AS threadname
             FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_nuggets} mn ON (mtu.nuggetid = mn.id)
             JOIN {block_iomad_microlearning_threads} mt ON (mtu.threadid = mt.id)
             WHERE mtu.userid = :userid
             AND mtu.timecompleted IS NULL
             ORDER BY mn.name,mtu.schedule_date",
            ['userid' => $USER->id])) {

            // Display the nuggets.
            $threadid = 0;
            $nuggetout = html_writer::start_tag('div', ['class' => 'microlearningthreads']);

            // Process all we found.
            foreach ($mynuggets as $mynugget) {
                // Conditionally display the thread name.
                if ($threadid != $mynugget->threadid) {
                    $nuggetout .= html_writer::start_tag('div', ['class' => 'microlearningthreadhead']);
                    $nuggetout .= format_text($mynugget->threadname);
                    $nuggetout .= html_writer::end_tag('a');
                    $threadid = $mynugget->threadid;
                }

                // Display the nugget information.
                $linkurl = self::get_nugget_url($mynugget);
                $nuggetout .= html_writer::start_tag('div', ['class' => 'microlearningnugget']);
                $nuggetout .= html_writer::start_tag('a', ['class' => 'microlearningnugget_link', 'href' => $linkurl]);
                $nuggetout .= format_string($mynugget->nuggetname);
                $nuggetout .= html_writer::end_tag('a');
                $nuggetout .= html_writer::end_tag('div');
            }

            $nuggetout .= html_writer::end_tag('div');
        } else {
            $nuggetout = get_string('nolearningthreads', 'block_microlearning');
        }

        return $nuggetout;
    }

    /**
     * Check if the thread is valid for the company
     *
     * @param int $companyid
     * @param int $threadid
     * @return bool
     */
    public static function check_valid_thread($companyid, $threadid) {
        global $DB;
        if ($DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid, 'companyid' => $companyid])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete a microlearning thread
     *
     * @param int $threadid
     * @return bool
     */
    public static function delete_thread($threadid) {
        global $DB, $USER;

        if (!$threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
            return false;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();
        $errors = false;

        // Delete users.
        if (!$DB->delete_records('block_iomad_microlearning_thread_users', ['threadid' => $threadid])) {
            $errors = true;
        }

        // Delete nuggets.
        $nuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $threadid]);
        foreach ($nuggets as $nugget) {

            // Delete nugget schedules.
            if (!$DB->delete_records('block_iomad_microlearning_nugget_schedules', ['nuggetid' => $nugget->id])) {
                $errors = true;
            }
        }

        // Finally delete the nugget.
        if (!$DB->delete_records('block_iomad_microlearning_nuggets', ['threadid' => $threadid])) {
            $errors = true;
        }

        // Delete thread.
        if (!$DB->delete_records('block_iomad_microlearning_threads', ['id' => $threadid])) {
            $errors = true;
        }

        // End transaction.
        if (!$errors) {
            $transaction->allow_commit();

            // Fire an Event for this.
            $eventother = ['companyid' => $threadrec->companyid];

            $event = thread_deleted::create([
                'context' => context_system::instance(),
                'userid' => $USER->id,
                'objectid' => $threadid,
                'other' => $eventother,
            ]);
            $event->trigger();

            return true;
        } else {
            try {
                throw new Exception('Could not delete thread');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }

            return false;
        }
    }

    /**
     * Clone a microlearning thread
     *
     * @param int $threadid
     * @return bool
     */
    public static function clone_thread($threadid) {
        global $DB, $USER;

        if (!$threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
            return false;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();
        $errors = false;

        // Create thread copy.
        $originalthreadid = $threadrec->id;
        unset($threadrec->id);
        $threadrec->name = $threadrec->name . get_string('copy', 'block_iomad_microlearning');
        if (!$threadrec->id = $DB->insert_record('block_iomad_microlearning_threads', $threadrec)) {
            $errors = true;
        }

        // Clone nuggets.
        $nuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $originalthreadid]);
        foreach ($nuggets as $nugget) {

            // Copy the nugget.
            $originalnuggetid = $nugget->id;
            unset($nugget->id);
            $nugget->threadid = $threadrec->id;
            if (!$nugget->id = $DB->insert_record('block_iomad_microlearning_nuggets', $nugget)) {
                $errors = true;
            }

            // Deal with the schedules.
            if ($nuggetschedule = $DB->get_record(
                'block_iomad_microlearning_nugget_schedules',
                ['nuggetid' => $originalnuggetid])) {
                // Copy nugget schedules.
                $nuggetschedule->nuggetid = $nugget->id;
                if (!$DB->insert_record('block_iomad_microlearning_nugget_schedules', $nuggetschedule)) {
                    $errors = true;
                }
            }
        }

        // End transaction.
        if (!$errors) {
            $transaction->allow_commit();

            // Fire an Event for this.
            $eventother = ['companyid' => $threadrec->companyid];

            $event = thread_created::create([
                'context' => context_system::instance(),
                'userid' => $USER->id,
                'objectid' => $threadrec->id,
                'other' => $eventother,
            ]);
            $event->trigger();
            return true;
        } else {
            try {
                throw new Exception('Could not clone thread');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }
        }
    }

    /**
     * Import a thread from another tenant
     *
     * @param int $threadid
     * @param int $companyid
     * @return void
     */
    public static function import_thread($threadid, $companyid) {
        global $DB, $USER;

        if (!$threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
            return false;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();
        $errors = false;

        // Create thread copy.
        $originalthreadid = $threadrec->id;
        unset($threadrec->id);
        $threadrec->companyid = $companyid;
        $threadrec->name = $threadrec->name . get_string('copy', 'block_iomad_microlearning');
        if (!$threadrec->id = $DB->insert_record('block_iomad_microlearning_threads', $threadrec)) {
            $errors = true;
        }

        // Clone nuggets.
        $nuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $originalthreadid]);
        foreach ($nuggets as $nugget) {

            // Copy the nugget.
            $originalnuggetid = $nugget->id;
            unset($nugget->id);
            $nugget->threadid = $threadrec->id;
            if (!$nugget->id = $DB->insert_record('block_iomad_microlearning_nuggets', $nugget)) {
                $errors = true;
            }

            // Deal with the schedules.
            if ($nuggetschedule = $DB->get_record(
                'block_iomad_microlearning_nugget_schedules',
                ['nuggetid' => $originalnuggetid])) {

                // Copy nugget schedules.
                $nuggetschedule->nuggetid = $nugget->id;
                if (!$DB->insert_record('block_iomad_microlearning_nugget_schedules', $nuggetschedule)) {
                    $errors = true;
                }
            }
        }

        // End transaction.
        if (!$errors) {
            $transaction->allow_commit();

            // Fire an Event for this.
            $eventother = ['companyid' => $threadrec->companyid];

            $event = thread_created::create([
                'context' => context_system::instance(),
                'userid' => $USER->id,
                'objectid' => $threadrec->id,
                'other' => $eventother,
            ]);
            $event->trigger();
            return true;
        } else {
            try {
                throw new Exception('Could not clone thread');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }
        }
    }

    /**
     * Delete a nugget
     *
     * @param int $nuggetid
     * @return bool
     */
    public static function delete_nugget($nuggetid) {
        global $DB, $USER;

        // Does the nugget exist.
        if (!$nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid])) {
            return false;
        }

        // Start a transaction.
        $errors = false;
        $transaction = $DB->start_delegated_transaction();

        // Get any nuggets after this one.
        if ($afters = $DB->get_records_sql("SELECT * FROM {block_iomad_microlearning_nuggets}
                                            WHERE threadid = :threadid
                                            AND nuggetorder > :current",
                                           ['threadid' => $nugget->threadid,
                                            'current' => $nugget->nuggetorder])) {
            // Move them up.
            foreach ($afters as $after) {
                $after->nuggetorder--;
                if ($after->nuggetorder < 0) {
                    $after->nuggetorder = 0;
                }
                $DB->update_record('block_iomad_microlearning_nuggets', $after);
            }
        }

        // Delete the nugget.
        if (!$DB->delete_records('block_iomad_microlearning_nuggets', ['id' => $nugget->id])) {
            try {
                throw new Exception('Could not delete nugget');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }

            return false;
        } else {
            $transaction->allow_commit();

            // Fire an Event for this.
            $eventother = ['threadid' => $nugget->threadid];

            $event = nugget_deleted::create([
                'context' => context_system::instance(),
                'userid' => $USER->id,
                'objectid' => $nuggetid,
                'other' => $eventother,
            ]);
            $event->trigger();

            return true;
        }

    }

    /**
     * Move a nugget up in the order
     *
     * @param int $nuggetid
     * @return void
     */
    public static function up_nugget($nuggetid) {
        global $DB, $USER;

        // Does the nugget exist.
        if (!$nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid])) {
            return false;
        }

        // Is it already the first one?
        if ($nugget->nuggetorder == 0) {
            return true;
        }

        // Get any nuggets after this one.
        if ($above = $DB->get_record_sql("SELECT * FROM {block_iomad_microlearning_nuggets}
                                          WHERE threadid = :threadid
                                          AND nuggetorder = :above",
                                         ['threadid' => $nugget->threadid,
                                          'above' => $nugget->nuggetorder - 1])) {
            $above->nuggetorder++;
            $DB->update_record('block_iomad_microlearning_nuggets', $above);
            $nugget->nuggetorder--;
            $DB->update_record('block_iomad_microlearning_nuggets', $nugget);
            if ($nugget->nuggetorder < 0) {
                // We need to re-order all of the nuggets as something went wrong....
                $threadnuggets = $DB->get_records('block_iomad_microlearning_nuggets',
                                                  ['threadid' => $nugget->threadid],
                                                  'nuggetorder',
                                                  'id');
                $newcount = 0;
                foreach ($threadnuggets as $threadnugget) {
                    $DB->set_field('block_iomad_microlearning_nuggets', 'nuggetorder', $newcount, ['id' => $threadnugget->id]);
                    $newcount++;
                }
            }
        }

        // Fire an Event for this.
        $eventother = ['threadid' => $nugget->threadid];

        $event = nugget_moved::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'objectid' => $nuggetid,
            'other' => $eventother,
        ]);
        $event->trigger();

    }

    /**
     * Move a nugget down in the order
     *
     * @param [type] $nuggetid
     * @return void
     */
    public static function down_nugget($nuggetid) {
        global $DB, $USER;

        // Does the nugget exist.
        if (!$nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid])) {
            return false;
        }

        // Get any nuggets after this one.
        if ($below = $DB->get_record_sql("SELECT * FROM {block_iomad_microlearning_nuggets}
                                          WHERE threadid = :threadid
                                          AND nuggetorder = :below",
                                         ['threadid' => $nugget->threadid,
                                          'below' => $nugget->nuggetorder + 1])) {
            $below->nuggetorder--;
            $DB->update_record('block_iomad_microlearning_nuggets', $below);
            $nugget->nuggetorder++;
            $DB->update_record('block_iomad_microlearning_nuggets', $nugget);
        }

        // Fire an Event for this.
        $eventother = ['threadid' => $nugget->threadid];

        $event = nugget_moved::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'objectid' => $nuggetid,
            'other' => $eventother,
        ]);
        $event->trigger();

    }

    /**
     * Get the schedules for a thread
     *
     * @param object $threadinfo
     * @param array $nuggets
     * @param int $startdate
     * @param int $fromnuggetid
     * @param bool $fromnext
     * @return void
     */
    public static function get_schedules($threadinfo,
                                         $nuggets,
                                         $startdate = null,
                                         $fromnuggetid = 0,
                                         $fromnext = false) {
        global $DB, $CFG;

        $returndata = (object) [];
        $returndata->threadid = $threadinfo->id;
        if (empty($startdate)) {
            $passedtime = false;
            $startdate = $threadinfo->startdate;
        } else {
            $passedtime = true;
        }
        $schedulearray = [];
        $duedatearray = [];
        $reminder1array = [];
        $reminder2array = [];
        $found = false;
        if (empty($threadinfo->defaultdue)) {
            $threadinfo->defaultdue = 0;
        }

        foreach ($nuggets as $nugget) {
            // If we are passed a nugget ID we need to go from that one only.
            if (!empty($fromnuggetid) && $nugget->id == $fromnuggetid) {
                $found = true;
            }
            if (!empty($fromnuggetid) && $nugget->id != $fromnuggetid && !$found) {
                continue;
            }
            // Check if we already have a schedule.
            if ($schedule = $DB->get_record('block_iomad_microlearning_nugget_schedules', ['nuggetid' => $nugget->id])) {
                if (!$passedtime || $startdate < $schedule->scheduledate) {
                    $startdate = $schedule->scheduledate;
                    $schedulearray[$nugget->id] = $schedule->scheduledate;
                    $duedatearray[$nugget->id] = $schedule->due_date;
                    $reminder1array[$nugget->id] = $schedule->reminder1_date;
                    $reminder2array[$nugget->id] = $schedule->reminder2_date;
                } else {
                    $schedulearray[$nugget->id] = $startdate;
                    $duedatearray[$nugget->id] = $startdate + $schedule->due_date - $schedule->scheduledate;
                    $reminder1array[$nugget->id] = $startdate + $schedule->reminder1_date - $schedule->scheduledate;
                    $reminder2array[$nugget->id] = $startdate + $schedule->reminder2_date - $schedule->scheduledate;
                    $startdate = $startdate + $schedule->due_date - $schedule->scheduledate;
                }
            } else {
                $schedulearray[$nugget->id] = $startdate +
                                              $threadinfo->message_preset +
                                              $threadinfo->message_time;
                $duedatearray[$nugget->id] = $startdate +
                                              $threadinfo->message_preset +
                                              $threadinfo->defaultdue +
                                              $threadinfo->message_time;
                if (!empty($threadinfo->reminder1)) {
                    $reminder1array[$nugget->id] = $startdate +
                                                   $threadinfo->message_preset +
                                                   $threadinfo->reminder1 +
                                                   $threadinfo->message_time;
                } else {
                    $reminder1array[$nugget->id] = null;
                }
                if (!empty($threadinfo->reminder2)) {
                    $reminder2array[$nugget->id] = $startdate +
                                                   $threadinfo->message_preset +
                                                   $threadinfo->reminder2 +
                                                   $threadinfo->message_time;
                } else {
                    $reminder2array[$nugget->id] = null;
                }
                $startdate = $startdate + $threadinfo->releaseinterval;
            }
        }
        $returndata->threadinfo = $threadinfo;
        $returndata->schedulearray = $schedulearray;
        $returndata->duedatearray = $duedatearray;
        $returndata->reminder1array = $reminder1array;
        $returndata->reminder2array = $reminder2array;

        return $returndata;
    }

    /**
     * Reset a microlearning thread schedule
     *
     * @param int $threadinfo
     * @return void
     */
    public static function reset_thread_schedule($threadinfo) {
        global $DB, $USER;

        // Delete the current schedules for any nuggets.
        if ($nuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $threadinfo->id])) {
            foreach ($nuggets as $nugget) {
                $DB->delete_records('block_iomad_microlearning_nugget_schedules', ['nuggetid' => $nugget->id]);
            }

            // Get the new schedule info.
            $scheduledata = self::get_schedules($threadinfo, $nuggets);
            self::update_thread_schedule($scheduledata);
        }
    }

    /**
     * Update thread schedule data
     *
     * @param object $scheduledata
     * @return void
     */
    public static function update_thread_schedule($scheduledata) {
        global $DB, $USER;

        // Process the scheduledata.
        foreach (array_keys($scheduledata->schedulearray) as $nuggetid) {
            // Does it exist already?
            if ($DB->record_exists('block_iomad_microlearning_nugget_schedules', ['nuggetid' => $nuggetid])) {
                // Update the stored nugget schedules.
                $DB->set_field('block_iomad_microlearning_nugget_schedules',
                               'scheduledate',
                               $scheduledata->schedulearray[$nuggetid],
                               ['nuggetid' => $nuggetid]);
                $DB->set_field('block_iomad_microlearning_nugget_schedules',
                               'due_date',
                               $scheduledata->duedatearray[$nuggetid],
                               ['nuggetid' => $nuggetid]);
                if (empty($scheduledata->reminder1array[$nuggetid])) {
                    $scheduledata->reminder1array[$nuggetid] = 0;
                }
                $DB->set_field('block_iomad_microlearning_nugget_schedules',
                               'reminder1_date',
                               $scheduledata->reminder1array[$nuggetid],
                               ['nuggetid' => $nuggetid]);
                if (empty($scheduledata->reminder2array[$nuggetid])) {
                    $scheduledata->reminder2array[$nuggetid] = 0;
                }
                $DB->set_field('block_iomad_microlearning_nugget_schedules',
                               'reminder2_date',
                               $scheduledata->reminder2array[$nuggetid],
                               ['nuggetid' => $nuggetid]);
            } else {
                // Make sure we have all the defaults.
                if (empty($scheduledata->duedatearray[$nuggetid])) {
                    $scheduledata->duedatearray[$nuggetid] = 0;
                }
                if (empty($scheduledata->reminder1array[$nuggetid])) {
                    $scheduledata->reminder1array[$nuggetid] = 0;
                }
                if (empty($scheduledata->reminder2array[$nuggetid])) {
                    $scheduledata->reminder2array[$nuggetid] = 0;
                }
                if (empty($scheduledata->threadinfo->send_message)) {
                    $scheduledata->threadinfo->send_message = 0;
                }
                if (empty($scheduledata->threadinfo->send_reminder)) {
                    $scheduledata->threadinfo->send_reminder = 0;
                }
                $DB->insert_record(
                    'block_iomad_microlearning_nugget_schedules',
                    [
                        'scheduledate' => $scheduledata->schedulearray[$nuggetid],
                        'nuggetid' => $nuggetid,
                        'timecreated' => time(),
                        'send_message' => $scheduledata->threadinfo->send_message,
                        'send_reminder' => $scheduledata->threadinfo->send_reminder,
                        'reminder1_date' => $scheduledata->reminder1array[$nuggetid],
                        'reminder2_date' => $scheduledata->reminder2array[$nuggetid],
                        'due_date' => $scheduledata->duedatearray[$nuggetid],
                    ]
                );
            }

            // Update the user nugget schedules.
            $DB->set_field(
                'block_iomad_microlearning_thread_users',
                'schedule_date',
                $scheduledata->schedulearray[$nuggetid],
                [
                    'threadid' => $scheduledata->threadid,
                    'nuggetid' => $nuggetid,
                ]
            );
            $DB->set_field(
                'block_iomad_microlearning_thread_users',
                'due_date',
                $scheduledata->duedatearray[$nuggetid],
                [
                    'threadid' => $scheduledata->threadid,
                    'nuggetid' => $nuggetid,
                ]
            );
            $DB->set_field(
                'block_iomad_microlearning_thread_users',
                'reminder1_date',
                $scheduledata->reminder1array[$nuggetid],
                [
                    'threadid' => $scheduledata->threadid,
                    'nuggetid' => $nuggetid,
                ]
            );
            $DB->set_field(
                'block_iomad_microlearning_thread_users',
                'reminder2_date',
                $scheduledata->reminder2array[$nuggetid],
                [
                    'threadid' => $scheduledata->threadid,
                    'nuggetid' => $nuggetid,
                ]
            );
        }

        // Fire an event for this.
        $eventother = ['threadid' => $scheduledata->threadid];

        $event = thread_schedule_updated::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'objectid' => $scheduledata->threadid,
            'other' => $eventother,
        ]);
        $event->trigger();

    }

    /**
     * Add a user to a microlearning thread
     *
     * @param int $threadid
     * @param int $userid
     * @param int $groupid
     * @param int $scheduletype
     * @return void
     */
    public static function add_user_to_thread($threadid, $userid, $groupid = 0, $scheduletype = 0) {
        global $DB, $USER;

        // Check the user is valid.
        if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) {
            return false;
        }

        // Check the thread is valid.
        if (!$threadinfo = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
            return false;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();
        $errors = false;
        $starttime = null;

        // Do we have a schedule type?
        if (!empty($scheduletype)) {
            if ($scheduletype == 1) {
                // We want midnight last night.
                $starttime = strtotime("today midnight") +
                             $threadinfo->message_preset +
                             $threadinfo->message_time;
            } else {
                // We want the next scheduled time.
                $starttime = self::get_next_scheduled($threadid) +
                             $threadinfo->message_preset +
                             $threadinfo->message_time;
            }
        }

        // Get the thread nuggets.
        $nuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $threadid]);
        if (empty($threadinfo->halt_until_fulfilled)) {
            $scheduleinfo = self::get_schedules($threadinfo, $nuggets, $starttime);
        } else {
            // We want midnight last night.
            $starttime = time() - (time() % 86400);
            $scheduleinfo = self::get_schedules($threadinfo, $nuggets, $starttime);
        }

        // Create the user schedule info.
        $stop = false;
        $completed = false;
        foreach ($nuggets as $nugget) {
            $schedulerec = new stdclass();
            $schedulerec->userid = $userid;
            $schedulerec->threadid = $threadid;
            $schedulerec->groupid = $groupid;
            $schedulerec->nuggetid = $nugget->id;
            $schedulerec->schedule_date = $scheduleinfo->schedulearray[$nugget->id];
            $schedulerec->due_date = $scheduleinfo->duedatearray[$nugget->id];
            $schedulerec->message_time = $threadinfo->message_time;
            if (!empty($scheduleinfo->reminder2array[$nugget->id])) {
                $schedulerec->reminder1_date = $scheduleinfo->reminder1array[$nugget->id];
            } else {
                $schedulerec->reminder1_date = 0;
            }
            if (!empty($scheduleinfo->reminder2array[$nugget->id])) {
                $schedulerec->reminder2_date = $scheduleinfo->reminder2array[$nugget->id];
            } else {
                $schedulerec->reminder2_date = 0;
            }
            $schedulerec->message_delivered = false;
            $schedulerec->reminder1_delivered = false;
            $schedulerec->reminder2_delivered = false;
            $schedulerec->timecreated = time();
            if (!empty($nugget->cmid)) {
                if ($modcompletion = $DB->get_record_sql(
                    "SELECT * FROM {course_modules_completion}
                     WHERE userid = :userid
                     AND coursemoduleid = :cmid
                     AND completionstate > 0",
                    ['userid' => $userid,
                     'cmid' => $nugget->cmid])) {
                    $schedulerec->timecompleted = $modcompletion->timemodified;
                    $completed = true;
                } else {
                    $completed = false;
                }
            } else if (!empty($nugget->sectionid)) {
                // Get all of the course modules in that section which have completion set up.
                $requiredcount = $DB->count_records_sql(
                    "SELECT COUNT(id) FROM {course_modules}
                     WHERE section = :section
                     AND completion > 0",
                    ['section' => $nugget->sectionid]);

                // Get all of the course modules in that section which have completion set up.
                $actualcount = $DB->get_records_sql(
                    "SELECT * FROM {course_modules_completion}
                     WHERE userid = :userid
                     AND completionstate > 0
                     AND coursemoduleid IN (
                         SELECT id FROM {course_modules}
                         WHERE section = :section
                     )
                     ORDER BY timemodified DESC",
                    ['userid' => $userid,
                     'section' => $nugget->sectionid]);

                if (!empty($actualcount) && $requiredcount >= count($actualcount)) {
                    // Get the maximum time modified.
                    $last = array_shift($actualcount);
                    $schedulerec->timecompleted = $last->timemodified;
                    $completed = true;
                } else {
                    $completed = false;
                }
            } else {
                $schedulerec->timecompleted = null;
            }
            $schedulerec->accesskey = self::generate_accesskey();
            if (!$DB->insert_record('block_iomad_microlearning_thread_users', $schedulerec)) {
                $errors = true;
            }

            // Is this a halt until completed?
            if (!empty($threadinfo->halt_until_fulfilled) && !$completed) {
                break;
            }
        }
        if ($errors) {
            try {
                throw new Exception('Could not add user to thread');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }

            return false;
        } else {
            $transaction->allow_commit();
            return true;
        }
    }

    /**
     * Remove a user from a microlearning thread
     *
     * @param int $threadid
     * @param int $userid
     * @return void
     */
    public static function remove_user_from_thread($threadid, $userid) {
        global $DB, $USER;

        // Check the user is valid.
        if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) {
            return false;
        }

        // Check the thread is valid.
        if (!$threadinfo = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
            return false;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();
        $errors = false;

        if (!$DB->delete_records('block_iomad_microlearning_thread_users', ['userid' => $userid, 'threadid' => $threadid])) {
            try {
                throw new Exception('Could not remove user from thread');
            } catch (\Exception $e) {
                $transaction->rollback($e);
            }

            return false;
        } else {
            $transaction->allow_commit();
            return true;
        }
    }

    /**
     * Generate a random access key
     *
     * @return void
     */
    public static function generate_accesskey() {
        return bin2hex(random_bytes(64));
    }

    /**
     * Get the url for a nugget
     *
     * @param object $nugget
     * @return string
     */
    public static function get_nugget_url($nugget) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');

        // Get the nugget url.
        if (!empty($nugget->url)) {
            $linkurl = $nugget->url;
        } else if (!empty($nugget->sectionid)) {
            $sectioninfo = $DB->get_record('course_sections', ['id' => $nugget->sectionid]);
            $linkurl = course_get_url($sectioninfo->course, $sectioninfo->section);
        } else if (!empty($nugget->cmid)) {
            $moduleinfo = $DB->get_record('course_modules', ['id' => $nugget->cmid]);
            $course = $DB->get_record('course', ['id' => $moduleinfo->course]);
            $modinfo = get_fast_modinfo($course);
            $cm = $modinfo->cms[$nugget->cmid];
            $linkurl = $cm->url;
        }

        return $linkurl;
    }

    /**
     * Get a list of threads a a id => name menu
     *
     * @param int $companyid
     * @return array
     */
    public static function get_menu_threads($companyid) {
        global $DB, $USER;

        $threads = $DB->get_records_menu('block_iomad_microlearning_threads', ['companyid' => $companyid], 'name', 'id,name');
        $menuthreads = [0 => get_string('selectthread', 'block_iomad_microlearning')];

        // Deal with any language formatting.
        foreach ($threads as $id => $name) {
            $menuthreads[$id] = format_string($name);
        }

        return $menuthreads;
    }

    /**
     * Assign a microlearning thread to a user
     *
     * @param object $user
     * @param int $threadid
     * @param int $companyid
     * @param int $groupid
     * @param int $scheduletype
     * @return void
     */
    public static function assign_thread_to_user($user,
                                                 $threadid,
                                                 $companyid,
                                                 $groupid = 0,
                                                 $scheduletype = 0) {
        global $DB, $USER;

        // Is the user valid.
        if (!$userrec = $DB->get_record('user', ['id' => $user->id, 'deleted' => 0, 'suspended' => 0])) {
            return false;
        }

        // The thread?
        if (!$threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid, 'companyid' => $companyid])) {
            return false;
        }

        // The company?
        if (!$companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid, 'suspended' => 0])) {
            return false;
        }

        // All OK so do the work.
        self::add_user_to_thread($threadid, $user->id, $groupid, $scheduletype);

        // Fire an event for this.
        $eventother = ['companyid' => $companyid];

        $event = nugget_created::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'relateduserid' => $user->id,
            'objectid' => $threadid,
            'other' => $eventother,
        ]);
        $event->trigger();
    }

    /**
     * Remove a microlearning thread from a user
     *
     * @param object $user
     * @param int $threadid
     * @param int $companyid
     * @return void
     */
    public static function remove_thread_from_user($user, $threadid, $companyid) {
        global $DB, $USER;

        // Is the user valid.
        if (!$userrec = $DB->get_record('user', ['id' => $user->id, 'deleted' => 0, 'suspended' => 0])) {
            return false;
        }

        // The thread?
        if (!$threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid, 'companyid' => $companyid])) {
            return false;
        }

        // The company?
        if (!$companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid, 'suspended' => 0])) {
            return false;
        }

        // All OK so do the work.
        self::remove_user_from_thread($threadid, $user->id);

        // Fire an event for this.
        $eventother = ['companyid' => $companyid];

        $event = nugget_created::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'relateduserid' => $user->id,
            'objectid' => $threadid,
            'other' => $eventother,
        ]);
        $event->trigger();

    }

    // Event handlers.
    /**
     * Empty event handler
     *
     * @param thread_created $event
     * @return void
     */
    public static function event_thread_created(thread_created $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param thread_deleted $event
     * @return void
     */
    public static function event_thread_deleted(thread_deleted $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param thread_updated $event
     * @return void
     */
    public static function event_thread_updated(thread_updated $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param thread_schedule_updated $event
     * @return void
     */
    public static function event_thread_schedule_updated(thread_schedule_updated $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param nugget_created $event
     * @return void
     */
    public static function event_nugget_created(nugget_created $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param nugget_deleted $event
     * @return void
     */
    public static function event_nugget_deleted(nugget_deleted $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param nugget_updated $event
     * @return void
     */
    public static function event_nugget_updated(nugget_updated $event) {
        global $DB, $USER;
    }

    /**
     * Empty event handler
     *
     * @param nugget_moved $event
     * @return void
     */
    public static function event_nugget_moved(nugget_moved $event) {
        global $DB, $USER;
    }

    /**
     * User deleted event handler
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function event_user_deleted(\core\event\user_deleted $event) {
        global $DB, $USER;

        // Delete all of the schedules for this user.
        $DB->delete_records('block_iomad_microlearning_thread_users', ['userid' => $event->objectid]);
    }

    /**
     * Course module updated event handler
     *
     * @param \core\event\course_module_completion_updated $event
     * @return void
     */
    public static function event_course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        global $DB, $USER;
        $cmid = $event->contextinstanceid;
        $userid = $event->relateduserid;
        $found = false;
        $threads = [];
        if ($nuggets = $DB->get_records_sql(
            "SELECT mtu.* FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_nuggets} mn ON (mtu.nuggetid = mn.id)
             WHERE mtu.userid = :userid
             AND mn.cmid = :cmid",
            ['userid' => $userid, 'cmid' => $cmid])) {
            foreach ($nuggets as $nugget) {
                if ($nugget->cmid == $cmid) {
                    $found = true;
                    if (empty($threads[$nugget->threadid])) {
                        $threads[$nugget->threadid] = [];
                    }
                    $threads[$nugget->threadid][$nugget->nuggetid] = $nugget->nuggetid;
                }
                $DB->set_field('block_iomad_microlearning_thread_users',
                               'timecompleted',
                               $event->timecreated,
                               ['id' => $nugget->id, 'userid' => $userid]);
            }
        }

        // Check if there is a section set instead.
        $cmidrec = $DB->get_record('course_modules', ['id' => $cmid]);
        if ($nuggets = $DB->get_records_sql(
            "SELECT mtu.* FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_nuggets} mn ON (mtu.nuggetid = mn.id)
             WHERE mtu.userid = :userid
             AND mn.sectionid = :sectionid",
            ['userid' => $userid, 'sectionid' => $cmidrec->section])) {

            // Get all of the course modules in that section which have completion set up.
            $requiredcount = $DB->count_records_sql(
                "SELECT COUNT(id) FROM {course_modules}
                 WHERE section = :section
                 AND completion > 0",
                ['section' => $cmidrec->section]);

            // Get all of the course modules in that section which have completion set up.
            $actualcount = $DB->count_records_sql(
                "SELECT COUNT(id) FROM {course_modules_completion}
                 WHERE userid = :userid
                 AND completionstate > 0
                 AND coursemoduleid IN (
                     SELECT id FROM {course_modules}
                     WHERE section = :section
                 )",
                ['userid' => $userid,
                 'section' => $cmidrec->section]);

            // If we have everything we need, mark it as completed.
            if ($requiredcount == $actualcount) {
                foreach ($nuggets as $nugget) {
                    $found = true;
                    if (empty($threads[$nugget->threadid])) {
                        $threads[$nugget->threadid] = [];
                    }
                    $threads[$nugget->threadid][$nugget->nuggetid] = $nugget->nuggetid;
                    $DB->set_field('block_iomad_microlearning_thread_users',
                                   'timecompleted',
                                   $event->timecreated,
                                   ['id' => $nugget->id]);
                }
            }
        }

        // Did we find anything?  Check if we need to anything else if it's a halted thread.
        if ($found) {
            foreach ($threads as $threadid => $threadnuggets) {
                if (!$threadrec = $DB->get_record(
                    'block_iomad_microlearning_threads',
                    ['id' => $threadid, 'halt_until_fulfilled' => 1])) {
                    continue;
                }
                // Get the nuggets from the thread.
                $mynuggets = $DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $threadid], 'nuggetorder', '*');
                $found = false;
                foreach ($mynuggets as $nugget) {
                    if (!empty($threadnuggets[$nugget->id])) {
                        $found = true;
                        break;
                    } else {
                        unset($mynuggets[$nugget->id]);
                    }
                }
                if ($found && count($mynuggets) > 1) {
                    unset($mynuggets[$nugget->id]);
                    $wantednuggets = $mynuggets;
                    $nextnugget = array_shift($wantednuggets);
                    $threadscheds = self::get_schedules($threadrec, $mynuggets, $event->timecreated, $nextnugget->id);
                    $completed = false;
                    $stop = false;
                    foreach ($mynuggets as $mynugget) {
                        $schedulerec = (object) [];
                        $schedulerec->userid = $userid;
                        $schedulerec->threadid = $threadid;
                        $schedulerec->nuggetid = $mynugget->id;
                        $schedulerec->schedule_date = $threadscheds->schedulearray[$mynugget->id];
                        $schedulerec->due_date = $threadscheds->duedatearray[$mynugget->id];
                        $schedulerec->message_time = $threadrec->message_time;
                        $schedulerec->reminder1_date = $schedulerec->schedule_date + $threadrec->reminder1;
                        $schedulerec->reminder2_date = $schedulerec->schedule_date + $threadrec->reminder2;
                        $schedulerec->message_delivered = false;
                        $schedulerec->reminder1_delivered = false;
                        $schedulerec->reminder2_delivered = false;
                        $schedulerec->timecreated = time();
                        if (!empty($mynugget->cmid)) {
                            if ($modcompletion = $DB->get_record_sql(
                                "SELECT * FROM {course_modules_completion}
                                 WHERE userid = :userid
                                 AND coursemoduleid = :cmid
                                 AND completionstate > 0",
                                ['userid' => $userid,
                                 'cmid' => $mynugget->cmid])) {
                                $schedulerec->timecompleted = $modcompletion->timemodified;
                                $completed = true;
                            } else {
                                $completed = false;
                            }
                        } else if (!empty($mynugget->sectionid)) {
                            // Get all of the course modules in that section which have completion set up.
                            $requiredcount = $DB->count_records_sql(
                                "SELECT COUNT(id) FROM {course_modules}
                                 WHERE section = :section
                                 AND completion > 0",
                                ['section' => $mynugget->sectionid]);

                            // Get all of the course modules in that section which have completion set up.
                            $actualcount = $DB->get_records_sql(
                                "SELECT * FROM {course_modules_completion}
                                 WHERE userid = :userid
                                 AND completionstate > 0
                                 AND coursemoduleid IN (
                                     SELECT id FROM {course_modules}
                                     WHERE section = :section
                                 )
                                 ORDER BY timemodified DESC",
                                ['userid' => $userid,
                                 'section' => $mynugget->sectionid]);

                            if (!empty($actualcount) && $requiredcount >= count($actualcount)) {
                                // Get the maximum time modified.
                                $last = array_shift($actualcount);
                                $schedulerec->timecompleted = $last->timemodified;
                                $completed = true;
                            } else {
                                $completed = false;
                            }
                        } else {
                            $schedulerec->timecompleted = null;
                        }
                        $schedulerec->accesskey = self::generate_accesskey();
                        if (!$DB->insert_record('block_iomad_microlearning_thread_users', $schedulerec)) {
                            $errors = true;
                        }

                        // Is this a halt until completed?
                        if (!empty($threadrec->halt_until_fulfilled) && !$completed) {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Calculate the next nugget schedule time
     *
     * @param int $threadid
     * @return void
     */
    private static function get_next_scheduled($threadid) {
        global $DB;

        // Get the thread info.
        $thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid]);

        $now = time();
        $send = $thread->startdate;

        // We need to have a positive value or we get stuck in a loop.
        if (empty($thread->releaseinterval)) {
            $thread->releaseinterval = 86400;
        }

        while ($send < $now) {
            $send = $send + $thread->releaseinterval;
        }

        return $send;
    }

    /**
     * IOMAD microlearning cron task
     *
     * @return void
     */
    public static function cron() {
        global $DB, $USER;

        // Get the current timestamp.
        $runtime = time();

        mtrace("starting block_microlearning cron at $runtime");

        // Get users who need to be sent a new link email.
        mtrace("getting list of users who have a new nugget");
        if ($scheduleusers = $DB->get_records_sql(
            "SELECT mtu.*, mt.companyid
             FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_threads} mt ON (
                 mtu.threadid = mt.id
                 AND mt.send_message = 1
                 AND mtu.message_delivered = 0
             )
             WHERE mtu.timecompleted IS NULL
             AND mt.active = 1
             AND mtu.schedule_date < :runtime",
            ['runtime' => $runtime])) {
            foreach ($scheduleusers as $scheduleuser) {
                $scheduleuser->message_delivered = 1;

                // Is the user valid?
                if ($user = $DB->get_record('user', ['id' => $scheduleuser->userid,
                                                     'suspended' => 0,
                                                     'deleted' => 0])) {
                    // Get the email payload.
                    if ($nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $scheduleuser->nuggetid])) {
                        $company = new company($scheduleuser->companyid);

                        // Get the nugget link.
                        $nugget->url = new moodle_url(
                            $company->get_wwwroot() . '/blocks/iomad_microlearning/land.php',
                            [
                                'nuggetid' => $nugget->id,
                                'userid' => $user->id,
                                'accesskey' => $scheduleuser->accesskey,
                            ]);

                        // Fire the email.
                        emailtemplate::send('microlearning_nugget_scheduled', ['user' => $user,
                                                                               'company' => $company,
                                                                               'nugget' => $nugget]);
                        $DB->set_field('block_iomad_microlearning_thread_users',
                                       'message_delivered',
                                       true,
                                       ['id' => $scheduleuser->id]);
                    }
                }

                // Update the record.
                $DB->update_record('block_iomad_microlearning_thread_users', $scheduleuser);
            }
        }
        // Remove the working array.
        unset($scheduleusers);

        // Get users who need to be sent a reminder email.
        mtrace("getting list of users for first reminder");
        if ($reminder1users = $DB->get_records_sql(
            "SELECT mtu.*,mt.companyid
             FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_threads} mt ON (mtu.threadid = mt.id)
             WHERE mt.send_reminder = 1
             AND mt.active = 1
             AND mtu.timecompleted IS NULL
             AND mtu.reminder1_delivered = 0
             AND mtu.reminder1_date IS NOT NULL
             AND (
                 mtu.reminder1_date < mtu.due_date
                 OR mtu.due_date = 0
             )
             AND mtu.reminder1_date < :runtime",
            ['runtime' => $runtime])) {
            foreach ($reminder1users as $reminder1user) {
                $reminder1user->reminder1_delivered = true;

                // Check the user is valid.
                if ($user = $DB->get_record('user', ['id' => $reminder1user->userid,
                                                     'suspended' => 0,
                                                     'deleted' => 0])) {
                    // Get the email payload.
                    if ($nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $reminder1user->nuggetid])) {
                        $company = new company($reminder1user->companyid);
                        // Fix the payload.
                        $nugget->name = format_text($nugget->name);
                        $nugget->url = new moodle_url
                        ($company->get_wwwroot() . '/blocks/iomad_microlearning/land.php',
                        [
                            'nuggetid' => $nugget->id,
                            'userid' => $user->id,
                            'accesskey' => $reminder1user->accesskey,
                        ]);

                        // Fire the email.
                        emailtemplate::send('microlearning_nugget_reminder1', ['user' => $user,
                                                                               'company' => $company,
                                                                               'nugget' => $nugget]);
                    }
                }

                // Update the record.
                $DB->update_record('block_iomad_microlearning_thread_users', $reminder1user);
            }
        }

        // Remove the working array.
        unset($reminder1users);

        // Get users who need to be sent a second reminder email.
        mtrace("getting list of users for second reminder");
        if ($reminder2users = $DB->get_records_sql(
            "SELECT mtu.*,mt.companyid
             FROM {block_iomad_microlearning_thread_users} mtu
             JOIN {block_iomad_microlearning_threads} mt
             ON (mtu.threadid = mt.id)
             WHERE mt.send_reminder = 1
             AND mt.active = 1
             AND mtu.timecompleted IS NULL
             AND mtu.reminder2_delivered = 0
             AND mtu.reminder2_date IS NOT NULL
             AND (
                 mtu.reminder2_date < mtu.due_date
                 OR mtu.due_date = 0
             )
             AND mtu.reminder2_date < :runtime",
            ['runtime' => $runtime])) {
            foreach ($reminder2users as $reminder2user) {
                $reminder2user->reminder2_delivered = true;
                $reminder2user->reminder1_delivered = true;

                // Check the user is valid.
                if ($user = $DB->get_record('user', ['id' => $reminder2user->userid,
                                                     'suspended' => 0,
                                                     'deleted' => 0])) {
                    // Get the email payload.
                    if ($nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $reminder2user->nuggetid])) {
                        $company = new company($reminder2user->companyid);

                        // Fix the payload.
                        $nugget->name = format_text($nugget->name);
                        $nugget->url = new moodle_url(
                            $company->get_wwwroot() . '/blocks/iomad_microlearning/land.php',
                            [
                                'nuggetid' => $nugget->id,
                                'userid' => $user->id,
                                'accesskey' => $reminder2user->accesskey,
                            ]
                        );

                        // Fire the email.
                        emailtemplate::send('microlearning_nugget_reminder2', ['user' => $user,
                                                                               'company' => $company,
                                                                               'nugget' => $nugget]);
                    }
                }
                $DB->update_record('block_iomad_microlearning_thread_users', $reminder2user);
            }
        }
        unset($reminder2users);
        mtrace("microlearning cron finished - " . time());
    }
}
