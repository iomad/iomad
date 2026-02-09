<?php

namespace local_academy_events;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        $roleid = $event->objectid;
        $role = $DB->get_record('role', ['id' => $roleid]);

        if ($role && $role->shortname === 'companymanager') {
            $customevent = \local_academy_events\event\companymanager_assigned::create([
                'objectid' => $event->objectid,
                'context' => $event->get_context(),
                'relateduserid' => $event->relateduserid,
                'other' => [
                    'roleid' => $roleid,
                    'rolename' => $role->shortname,
                ]
            ]);
            $customevent->trigger();
        }
    }

    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;

        $roleid = $event->objectid;
        $role = $DB->get_record('role', ['id' => $roleid]);

        if ($role && $role->shortname === 'companymanager') {
            $userid = $event->relateduserid;
            $other = [
                'roleid' => $roleid,
                'rolename' => $role->shortname,
            ];

            $cache = \cache::make('local_academy_events', 'userdata');
            $userdata = $cache->get('user_' . $userid);

            if ($userdata && (time() - $userdata['timestamp']) < 60) {
                $other['username'] = $userdata['username'];
                $other['email'] = $userdata['email'];
                $other['firstname'] = $userdata['firstname'];
                $other['lastname'] = $userdata['lastname'];
                $other['is_deletion'] = true;

                $cache->delete('user_' . $userid);
            } else {
                $user = $DB->get_record('user', ['id' => $userid], 'username, email, firstname, lastname');
                if ($user) {
                    $other['username'] = $user->username;
                    $other['email'] = $user->email;
                    $other['firstname'] = $user->firstname;
                    $other['lastname'] = $user->lastname;
                }
                $other['is_deletion'] = false;
            }

            $customevent = \local_academy_events\event\companymanager_unassigned::create([
                'objectid' => $event->objectid,
                'context' => $event->get_context(),
                'relateduserid' => $event->relateduserid,
                'other' => $other
            ]);
            $customevent->trigger();
        }
    }
}
