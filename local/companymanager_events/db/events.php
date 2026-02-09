<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_companymanager_events\observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\local_companymanager_events\observer::role_unassigned',
    ],
];
