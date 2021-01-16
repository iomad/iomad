<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once('user_message_form.php');

$msg     = optional_param('msg', '', PARAM_CLEANHTML);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();
admin_externalpage_setup('userbulk');
require_capability('moodle/site:manageallmessaging', context_system::instance());

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if (empty($CFG->messaging)) {
    print_error('messagingdisable', 'error');
}

//TODO: add support for large number of users

if ($confirm and !empty($msg) and confirm_sesskey()) {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $rs = $DB->get_recordset_select('user', "id $in", $params);
    foreach ($rs as $user) {
        //TODO we should probably support all text formats here or only FORMAT_MOODLE
        //For now bulk messaging is still using the html editor and its supplying html
        //so we have to use html format for it to be displayed correctly
        message_post_message($USER, $user, $msg, FORMAT_HTML);
    }
    $rs->close();
    redirect($return);
}

$msgform = new user_message_form('user_bulk_message.php');

if ($msgform->is_cancelled()) {
    redirect($return);

} else if ($formdata = $msgform->get_data()) {
    $options = new stdClass();
    $options->para     = false;
    $options->newlines = true;
    $options->smiley   = false;

    $msg = format_text($formdata->messagebody['text'], $formdata->messagebody['format'], $options);

    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    echo $OUTPUT->box($msg, 'boxwidthnarrow boxaligncenter generalbox', 'preview'); //TODO: clean once we start using proper text formats here

    $formcontinue = new single_button(new moodle_url('user_bulk_message.php', array('confirm' => 1, 'msg' => $msg)), get_string('yes')); //TODO: clean once we start using proper text formats here
    $formcancel = new single_button(new moodle_url('user_bulk.php'), get_string('no'), 'get');
    echo $OUTPUT->confirm(get_string('confirmmessage', 'bulkusers', $usernames), $formcontinue, $formcancel);
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
$msgform->display();
echo $OUTPUT->footer();
