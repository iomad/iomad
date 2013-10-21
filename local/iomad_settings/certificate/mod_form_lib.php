<?php

require_once(dirname(__FILE__) . '/lib.php');

function add_iomad_settings_elements($mform) {

    $strrequired = get_string('required');

    $mform->addElement('textarea', 'customtext2', get_string('customtext2', 'local_iomad_settings'), array('cols'=>'40', 'rows'=>'4', 'wrap'=>'virtual'));
    $mform->setType('customtext2', PARAM_RAW);

    $mform->addElement('textarea', 'customtext3', get_string('customtext3', 'local_iomad_settings'), array('cols'=>'40', 'rows'=>'4', 'wrap'=>'virtual'));
    $mform->setType('customtext3', PARAM_RAW);

    $mform->addElement('text', 'serialnumberformat', get_string('serialnumberformat', 'local_iomad_settings'), ' SIZE="50"');
    $mform->setType('serialnumberformat', PARAM_NOTAGS);
    $mform->setDefault('serialnumberformat', '{EC}{CC}{CD:DDMMYY}{SEQNO:3}');
    $mform->addRule('serialnumberformat', $strrequired, 'required', null, 'client');
    
    $mform->addElement('html', "<div class='fitem'><div class='fitemtitle'></div><div class='felement'>");
    $mform->addElement('html', get_string('serialnumberformat_help', 'local_iomad_settings'));
    $mform->addElement('html', "</div></div>");

    $choices = array();
    foreach(array(RESET_SEQUENCE_NEVER, RESET_SEQUENCE_DAILY, RESET_SEQUENCE_ANNUALLY) as $reset) {
        $choices[$reset] = get_string('reset_' . $reset, 'local_iomad_settings');
    }
    $mform->addElement('select', 'reset_sequence', get_string('reset_sequence','local_iomad_settings'), $choices);
    $mform->addRule('reset_sequence', $strrequired, 'required', null, 'client');

}