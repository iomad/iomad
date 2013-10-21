<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('establishment_code',
                                                get_string('establishment_code','local_iomad_settings'),
                                                get_string('establishment_code_help','local_iomad_settings'),
                                                '',
                                                PARAM_INT));
}


