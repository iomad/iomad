<?php

namespace local_academy_events;

defined('MOODLE_INTERNAL') || die();

class hook_callbacks {

    public static function before_user_deleted(\core_user\hook\before_user_deleted $hook): void {
        global $DB;

        try {
            $user = $hook->user;

            $companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
            if (!$companymanagerrole) {
                return;
            }

            $hadcompanymanagerrole = $DB->record_exists('role_assignments', [
                'userid' => $user->id,
                'roleid' => $companymanagerrole->id
            ]);

            if ($hadcompanymanagerrole) {
                $cache = \cache::make('local_academy_events', 'userdata');
                $cache->set('user_' . $user->id, [
                    'username' => $user->username,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'timestamp' => time(),
                ]);
            }
        } catch (\Exception $e) {
            debugging('Error in academy_events hook: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
