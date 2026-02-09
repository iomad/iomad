<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_heartbeat', get_string('pluginname', 'local_heartbeat'));

    $settings->add(new admin_setting_configtext(
        'local_heartbeat/heartbeat_url',
        get_string('heartbeat_url', 'local_heartbeat'),
        get_string('heartbeat_url_desc', 'local_heartbeat'),
        '',
        PARAM_URL
    ));

    $ADMIN->add('localplugins', $settings);
}
