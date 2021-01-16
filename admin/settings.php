<?php

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');

$section = required_param('section', PARAM_SAFEDIR);
$return = optional_param('return','', PARAM_ALPHA);
$adminediting = optional_param('adminedit', -1, PARAM_BOOL);

/// no guest autologin
require_login(0, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/admin/settings.php', array('section' => $section));
$PAGE->set_pagetype('admin-setting-' . $section);
$PAGE->set_pagelayout('admin');
$PAGE->navigation->clear_cache();
navigation_node::require_admin_tree();

$adminroot = admin_get_root(); // need all settings
$settingspage = $adminroot->locate($section, true);

if (empty($settingspage) or !($settingspage instanceof admin_settingpage)) {
    if (moodle_needs_upgrading()) {
        redirect(new moodle_url('/admin/index.php'));
    } else {
        print_error('sectionerror', 'admin', "$CFG->wwwroot/$CFG->admin/");
    }
    die;
}

if (!($settingspage->check_access())) {
    print_error('accessdenied', 'admin');
    die;
}

/// WRITING SUBMITTED DATA (IF ANY) -------------------------------------------------------------------------------

$statusmsg = '';
$errormsg  = '';

if ($data = data_submitted() and confirm_sesskey()) {

    $count = admin_write_settings($data);
    // Regardless of whether any setting change was written (a positive count), check validation errors for those that didn't.
    if (empty($adminroot->errors)) {
        // No errors. Did we change any setting? If so, then redirect with success.
        if ($count) {
            redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        // We didn't change a setting.
        switch ($return) {
            case 'site': redirect("$CFG->wwwroot/");
            case 'admin': redirect("$CFG->wwwroot/$CFG->admin/");
        }
        redirect($PAGE->url);
    } else {
        $errormsg = get_string('errorwithsettings', 'admin');
        $firsterror = reset($adminroot->errors);
    }
    $settingspage = $adminroot->locate($section, true);
}

if ($PAGE->user_allowed_editing() && $adminediting != -1) {
    $USER->editing = $adminediting;
}

/// print header stuff ------------------------------------------------------------
if (empty($SITE->fullname)) {
    $PAGE->set_title($settingspage->visiblename);
    $PAGE->set_heading($settingspage->visiblename);

    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('configintrosite', 'admin'));

    if ($errormsg !== '') {
        echo $OUTPUT->notification($errormsg);

    } else if ($statusmsg !== '') {
        echo $OUTPUT->notification($statusmsg, 'notifysuccess');
    }

    // ---------------------------------------------------------------------------------------------------------------

    $pageparams = $PAGE->url->params();
    $context = [
        'actionurl' => $PAGE->url->out(false),
        'params' => array_map(function($param) use ($pageparams) {
            return [
                'name' => $param,
                'value' => $pageparams[$param]
            ];
        }, array_keys($pageparams)),
        'sesskey' => sesskey(),
        'return' => $return,
        'title' => null,
        'settings' => $settingspage->output_html(),
        'showsave' => true
    ];

    echo $OUTPUT->render_from_template('core_admin/settings', $context);

} else {
    if ($PAGE->user_allowed_editing()) {
        $url = clone($PAGE->url);
        if ($PAGE->user_is_editing()) {
            $caption = get_string('blockseditoff');
            $url->param('adminedit', 'off');
        } else {
            $caption = get_string('blocksediton');
            $url->param('adminedit', 'on');
        }
        $buttons = $OUTPUT->single_button($url, $caption, 'get');
        $PAGE->set_button($buttons);
    }

    $visiblepathtosection = array_reverse($settingspage->visiblepath);

    $PAGE->set_title("$SITE->shortname: " . implode(": ",$visiblepathtosection));
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();

    if ($errormsg !== '') {
        echo $OUTPUT->notification($errormsg);

    } else if ($statusmsg !== '') {
        echo $OUTPUT->notification($statusmsg, 'notifysuccess');
    }

    // ---------------------------------------------------------------------------------------------------------------

    $pageparams = $PAGE->url->params();
    $context = [
        'actionurl' => $PAGE->url->out(false),
        'params' => array_map(function($param) use ($pageparams) {
            return [
                'name' => $param,
                'value' => $pageparams[$param]
            ];
        }, array_keys($pageparams)),
        'sesskey' => sesskey(),
        'return' => $return,
        'title' => $settingspage->visiblename,
        'settings' => $settingspage->output_html(),
        'showsave' => $settingspage->show_save()
    ];

    echo $OUTPUT->render_from_template('core_admin/settings', $context);
}

$PAGE->requires->yui_module('moodle-core-formchangechecker',
        'M.core_formchangechecker.init',
        array(array(
            'formid' => 'adminsettings'
        ))
);
$PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');

echo $OUTPUT->footer();
