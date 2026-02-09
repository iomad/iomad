<?php

namespace local_companymanager_events;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        $roleid = $event->objectid;
        $role = $DB->get_record('role', ['id' => $roleid]);

        if ($role && $role->shortname === 'companymanager') {
            $customevent = \local_companymanager_events\event\companymanager_assigned::create([
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
            $customevent = \local_companymanager_events\event\companymanager_unassigned::create([
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
}
