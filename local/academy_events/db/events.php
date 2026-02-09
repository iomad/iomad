<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_academy_events\observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\local_academy_events\observer::role_unassigned',
    ],
];
