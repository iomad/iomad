<?php

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_user\hook\before_user_deleted::class,
        'callback' => [\local_academy_events\hook_callbacks::class, 'before_user_deleted'],
        'priority' => 0,
    ],
];
