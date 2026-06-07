<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List all of the email templates and controls.
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, email, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);
$lang         = optional_param('lang', '', PARAM_LANG);
$ajaxtemplate = optional_param('ajaxtemplate', '', PARAM_CLEAN);
$ajaxvalue = optional_param('ajaxvalue', '', PARAM_CLEAN);
$save = optional_param('savetemplateset', 0, PARAM_CLEAN);
$manage = optional_param('manage', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUM);
$finished = optional_param('finished', 0, PARAM_BOOL);
$templatesetid = optional_param('templatesetid', 0, PARAM_INT);
$templateid = optional_param('templateid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);

if (!empty($templatesetid)) {
    $SESSION->currenttemplatesetid = $templatesetid;
}
if (!empty($SESSION->currenttemplatesetid) && !$finished) {
     $templatesetid = $SESSION->currenttemplatesetid;
}
if ($finished) {
    unset($SESSION->currenttemplatesetid);
    $templatesetid = 0;
}

// Deal with the default language.
if (empty($lang)) {
    if (isset($SESSION->lang)) {
        $lang = $SESSION->lang;
    } else {
        $lang = $USER->lang;
    }
}

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Check we can actually do anything on this page.
if (empty($templatesetid)) {
    iomad::require_capability('local/iomad:email_list', $companycontext);
} else {
    iomad::require_capability('local/iomad:email_templateset_list', $companycontext);
}

$email = email::get_templates();

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('template_list_title', 'local_iomad');
// Set the url.
$linkurl = new moodle_url('/local/iomad/template_list.php');
$manageurl = new moodle_url('/local/iomad/template_list.php', ['manage' => 1]);
$finishedurl = new moodle_url('/local/iomad/template_list.php', ['manage' => 1, 'finished' => 1]);

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->requires->js_call_amd('local_iomad/template_sliders', 'init', [$companyid, $page, $perpage, $lang]);

// Get output renderer.
$output = $PAGE->get_renderer('local_iomad');

// Set the page heading.
if (empty($templatesetid)) {
    if (empty($manage)) {
        $linktext = get_string('email_templates_for', 'local_iomad', $company->get_name());
    } else {
        $linktext = get_string('emailtemplatesets', 'local_iomad');
    }
} else {
    if (empty($action)) {
        if ($templatesetinfo = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            $linktext = get_string('email_templates_for', 'local_iomad', $templatesetinfo->templatesetname);
        } else {
            $linktext = get_string('email_templates_for', 'local_iomad', $company->get_name());
        }
    } else {
        if ($templatesetinfo = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            if ($action == 'edit') {
                $linktext = format_string(get_string('edittemplateset', 'local_iomad'). " " . $templatesetinfo->templatesetname);
            } else {
                $linktext = format_string(get_string('deletetemplateset', 'local_iomad'). " " . $templatesetinfo->templatesetname);
            }
        }
    }
}
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

$baseurl = new moodle_url(basename(__FILE__), ['sort' => $sort, 'dir' => $dir,
                                                    'perpage' => $perpage,
                                                    'lang' => $lang]);
$returnurl = $baseurl;

// Are the templates being migrated?
if (!empty($CFG->local_iomad_email_templates_migrating)) {
    notice(get_string('templatesnoaccessigble', 'local_iomad'), new moodle_url('/blocks/iomad_company_admin/index.php'));
    die;
}

// Check if ajax callback.
if ($ajaxtemplate) {
    $parts = explode('.', $ajaxtemplate);
    list($type, $id, $managertype, $senttemplatename) = $parts;

    // Get the new value.
    $newvalue = 0;
    if ($ajaxvalue == 'false') {
        $newvalue = 1;
    }

    // What are we dealing with?
    if ($type == 'c') {
        $tablename = "local_iomad_email_templates";
        $tablenamestrings = "local_iomad_email_template_strings";
        $tablekey = "companyid";
        $stringkey = "templateid";
    } else if ($type == 't') {
        $tablename = "local_iomad_email_templateset_templates";
        $tablenamestrings = "local_iomad_email_templateset_template_strings";
        $tablekey = "templateset";
        $stringkey = "templatesetid";
    }
    // What are we disabling?
    if ($managertype == 'e') {
        $tablefield = "disabled";
    }
    if ($managertype == 'em') {
        $tablefield = "disabledmanager";
    }
    if ($managertype == 'es') {
        $tablefield = "disabledsupervisor";
    }

    if (!is_numeric($senttemplatename)) {
        // Do the work.
        $DB->set_field($tablename, $tablefield, $newvalue, ['name' => $senttemplatename, $tablekey => $id]);
    } else {
        // Get all the records.
        $findsql = "SELECT et.id, et.name
                    FROM {" . $tablename . "} et
                    JOIN {" . $tablenamestrings ."} ets
                    ON et.id = ets.$stringkey
                    JOIN {tool_customlang} cl ON (
                        ets.lang=cl.lang
                        AND cl.stringid = CONCAT(et.name, '_name')
                    )
                    JOIN {tool_customlang_components} tcc ON (cl.componentid = tcc.id)
                    WHERE et.$tablekey = :id
                    AND ets.lang = :lang
                    AND tcc.name = :component
                    ORDER BY cl.master";
        $sqlparams = ['id' => $id,
                      'lang' => $lang,
                      'component' => 'local_email'];

        // Set up the headings.
        $templatenames = $DB->get_records_sql_menu($findsql,
                                                   $sqlparams,
                                                   $senttemplatename * $perpage,
                                                   $perpage);

        foreach ($templatenames as $templatename) {
            $DB->set_field($tablename, $tablefield, $newvalue, ['name' => $templatename, $tablekey => $id]);
        }
    }

    // Don't process any more.
    die;
}

// Deal with any deletes.
if ($action == 'delete' && confirm_sesskey()) {
    if ($confirm != md5($templatesetid)) {
        echo $output->header();

        if (!$templatesetinfo = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            throw new moodle_exception('templatesetnotfound', 'local_iomad');
        }

        $optionsyes = [
            'templatesetid' => $templatesetid,
            'confirm' => md5($templatesetid),
            'sesskey' => sesskey(),
            'action' => 'delete',
            ];
        echo $OUTPUT->confirm(get_string('deletetemplatesetfull', 'local_iomad', "'" . $templatesetinfo->templatesetname ."'"),
                              new moodle_url('/local/iomad/template_list.php', $optionsyes),
                                             '/local/iomad/template_list.php');
        echo $OUTPUT->footer();
        die;
    } else {
        // Delete the template.
        $templatesetstrings = $DB->get_records('local_iomad_email_templateset_templates', ['templateset' => $templatesetid]);
        foreach ($templatesetstrings as $templatesetstring) {
            $DB->delete_records('local_iomad_email_templateset_template_strings', ['templatesetid' => $templatesetstring->id]);
        }
        $DB->delete_records('local_iomad_email_templateset_templates', ['templateset' => $templatesetid]);
        $DB->delete_records('local_iomad_email_templatesets', ['id' => $templatesetid]);
        if ($SESSION->currenttemplatesetid == $templatesetid) {
            unset($SESSION->currenttemplatesetid);
        }
        redirect($manageurl, get_string('templatesetdeleted', 'local_iomad'), null, \core\output\notification::NOTIFY_SUCCESS);
        die;
    }
} else if ($action == 'setdefault' && confirm_sesskey()) {
    if ($confirm != md5($templatesetid)) {
        echo $output->header();

        if (!$templatesetinfo = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            throw new moodle_exception('templatesetnotfound', 'local_iomad');
        }

        $optionsyes = [
            'templatesetid' => $templatesetid,
            'confirm' => md5($templatesetid),
            'sesskey' => sesskey(),
            'action' => 'setdefault',
            ];
        echo $OUTPUT->confirm(get_string('setdefaulttemplatesetfull',
                                         'local_iomad',
                                         "'" . $templatesetinfo->templatesetname ."'"),
                              new moodle_url('/local/iomad/template_list.php', $optionsyes),
                              '/local/iomad/template_list.php');
        echo $OUTPUT->footer();
        die;
    } else {
        // Set the template set as default.
        $DB->set_field('local_iomad_email_templatesets', 'isdefault', 0, []);
        $DB->set_field('local_iomad_email_templatesets', 'isdefault', 1, ['id' => $templatesetid]);
        redirect($finishedurl, get_string('templatesetsetdefault', 'local_iomad'), null, \core\output\notification::NOTIFY_SUCCESS);
        die;
    }
} else if ($action == 'unsetdefault' && confirm_sesskey()) {
    if ($confirm != md5($templatesetid)) {
        echo $output->header();

        if (!$templatesetinfo = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            throw new moodle_exception('templatesetnotfound', 'local_iomad');
        }

        $optionsyes = [
            'templatesetid' => $templatesetid,
            'confirm' => md5($templatesetid),
            'sesskey' => sesskey(),
            'action' => 'unsetdefault',
            ];
        echo $OUTPUT->confirm(get_string('unsetdefaulttemplatesetfull',
                                         'local_iomad',
                                         "'" . $templatesetinfo->templatesetname ."'"),
                              new moodle_url('/local/iomad/template_list.php', $optionsyes),
                              '/local/iomad/template_list.php');
        echo $OUTPUT->footer();
        die;
    } else {
        // Set the template set as default.
        $DB->set_field('local_iomad_email_templatesets', 'isdefault', 0, []);
        redirect($finishedurl, get_string('templatesetsetdefault', 'local_iomad'), null, \core\output\notification::NOTIFY_SUCCESS);
        die;
    }
}

// Set up the form.
$mform = new local_iomad\forms\company_templateset_save_form($linkurl, $companyid, $templatesetid);

if ($data = $mform->get_data()) {
    // Save the template.
    $templatesetid = $DB->insert_record('local_iomad_email_templatesets', ['templatesetname' => $data->templatesetname]);
    $emailtemplates = $DB->get_records('local_iomad_email_templates', ['companyid' => $companyid]);
    foreach ($emailtemplates as $emailtemplate) {
        $emailtemplate->templateset = $templatesetid;
        $templateid = $DB->insert_record('local_iomad_email_templateset_templates', $emailtemplate);
        $stringtemplates = $DB->get_records('local_iomad_email_template_strings', ['templateid' => $emailtemplate->id]);
        foreach ($stringtemplates as $stringtemplate) {
            $stringtemplate->templatesetid = $templateid;
            unset($stringtemplate->id);
            $DB->insert_record('local_iomad_email_templateset_template_strings', $stringtemplate);
        }
    }
    redirect($linkurl, get_string('emailtemplatesetsaved', 'local_iomad'), null, \core\output\notification::NOTIFY_SUCCESS);
    die;
}

$buttons = "";

// Deal with the page buttons.
if (empty($manage)) {
    $saveurl = new moodle_url('/local/iomad/template_list.php',
                              ['savetemplateset' => 1,
                                    'templatesetid' => $templatesetid]);
    $manageurl = new moodle_url('/local/iomad/template_list.php',
                                ['manage' => 1]);
    $backbutton = '';
    if (!empty($templatesetid)) {
        if ($DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid])) {
            $backurl = new moodle_url('/local/iomad/template_list.php', ['finished' => true, 'manage' => 1]);
            $backbutton = $output->single_button($backurl, get_string('backtocompanytemplates', 'local_iomad'), 'get');
            $buttons .= $backbutton;
        }
    } else {
        $buttons .= $output->single_button($saveurl, get_string('savetemplateset', 'local_iomad'), 'get');
        $buttons .= $output->single_button($manageurl, get_string('managetemplatesets', 'local_iomad'), 'get');
    }
} else {
    $buttons .= $output->single_button($linkurl, get_string('back'), 'get');
}
$PAGE->set_button($buttons);

// Start the page.
echo $output->header();

if (!empty($save)) {
    if (!empty($templatesetid)) {
        $templateset = $DB->get_record('local_iomad_email_templatesets', ['id' => $templatesetid]);
        $mform->set_data($templateset);
    }

    // Display the form.
    $mform->display();
    echo $OUTPUT->footer();
    die;
}

// Output the search form.
$searchform = new local_iomad\forms\template_search_form();
$searchform->set_data(['search' => $search, 'manage' => $manage]);
$searchform->display();

// Sort the keys of the global $email object, the make sure we have that and the
// recordset we'll get next in the same order.
$configtemplates = array_keys($email);
sort($configtemplates);
$ntemplates = count($configtemplates);

if ($manage) {
    if (empty($templatesetid)) {
        if (!empty($search)) {
            $searchsql = $DB->sql_like('templatesetname', ':templatesetname', false);
            $sqlparams = ['templatesetname' => "%" . $search . "%"];
        } else {
            $searchsql = "1=1";
            $sqlparams = [];
        }

        // Display the list of templates.
        $table = new local_iomad\tables\templatesets_table('email_templatessets_table');
        $table->set_sql('*', '{local_iomad_email_templatesets}', $searchsql, $sqlparams);
        $table->define_baseurl($baseurl);
        $table->define_columns(['templatesetname', 'actions']);
        $table->define_headers([get_string('name'), '']);
        $table->no_sorting('actions');

        $table->out(30, true);

    }
} else {
    // Set up the prefix value for controls.
    if (empty($templatesetid)) {
        $prefix = "c." . $companyid;
    } else {
        $prefix = "t." . $templatesetid;
    }

    if (!empty($search)) {
        $searchsql = " AND " . $DB->sql_like('cl.master', ':templatename', false);
    } else {
        $searchsql = "";
    }

    // Set up the table.
    $sqlparams = [
        'companyid' => $companyid,
        'templatesetid' => $templatesetid,
        'lang' => $lang,
        'prefix' => $prefix,
        'component' => 'local_email',
        'templatename' => "%" . $search . "%",
    ];
    $selectsql = "concat(et.id, concat('_', ets.id)) AS junk,
                  et.*,
                  ets.lang,
                  ets.subject,
                  ets.body,
                  ets.signature,
                  cl.master AS templatename,
                  ets.id as templatestringid,
                  :prefix AS prefix";
    if (empty($templatesetid)) {
        $fromsql = "{local_iomad_email_templates} et
                    JOIN {local_iomad_email_template_strings} ets ON (et.id = ets.templateid)
                    JOIN {tool_customlang} cl ON (
                        ets.lang=cl.lang
                        AND cl.stringid = CONCAT(et.name, '_name')
                    )
                    JOIN {tool_customlang_components} tcc ON (cl.componentid = tcc.id)";
        $wheresql = "et.companyid = :companyid
                     AND ets.lang = :lang
                     AND tcc.name = :component
                     $searchsql";
    } else {
        $fromsql = "{local_iomad_email_templateset_templates} et
                    JOIN {local_iomad_email_templateset_template_strings} ets ON (et.id = ets.templatesetid)
                    JOIN {tool_customlang} cl ON (
                        ets.lang=cl.lang
                        AND cl.stringid = CONCAT(et.name, '_name')
                    )
                    JOIN {tool_customlang_components} tcc ON (cl.componentid = tcc.id)";
        $wheresql = "et.templateset = :templatesetid
                     AND ets.lang = :lang
                     AND tcc.name = :component
                     $searchsql";
    }

    // Set up the headings -- All this for the checkbox.
    $enabledrecs = $DB->get_records_sql_menu("SELECT DISTINCT et.id, et.disabled, cl.master
                                              FROM $fromsql
                                              WHERE $wheresql
                                              ORDER BY cl.master",
                                             $sqlparams,
                                             $page * $perpage,
                                             $perpage);

    $manenabledrecs = $DB->get_records_sql_menu("SELECT DISTINCT et.id, et.disabledmanager, cl.master
                                                 FROM $fromsql
                                                 WHERE $wheresql
                                                 ORDER BY cl.master",
                                                $sqlparams,
                                                $page * $perpage,
                                                $perpage);
    $supenabledrecs = $DB->get_records_sql_menu("SELECT DISTINCT et.id, et.disabledsupervisor, cl.master
                                                 FROM $fromsql
                                                 WHERE $wheresql
                                                 ORDER BY cl.master",
                                                $sqlparams,
                                                $page * $perpage,
                                                $perpage);

    // We have to process these as $array[0] and $array["0"] are not being handled properly.
    foreach ($enabledrecs as $i => $enabledvalue) {
        if ($enabledvalue) {
            $enabledrecs[$i] = "e";
        } else {
            $enabledrecs[$i] = "d";
        }
    }
    foreach ($manenabledrecs as $i => $enabledvalue) {
        if ($enabledvalue) {
            $manenabledrecs[$i] = "e";
        } else {
            $manenabledrecs[$i] = "d";
        }
    }
    foreach ($supenabledrecs as $i => $enabledvalue) {
        if ($enabledvalue) {
            $supenabledrecs[$i] = "e";
        } else {
            $supenabledrecs[$i] = "d";
        }
    }
    $enabledcounts = array_count_values($enabledrecs);
    if (empty($enabledcounts["d"])) {
        $enabledcounts["d"] = 0;
    }
    if (empty($enabledcounts["e"])) {
        $enabledcounts["e"] = 0;
    }
    $manenabledcounts = array_count_values($manenabledrecs);
    if (empty($manenabledcounts["d"])) {
        $manenabledcounts["d"] = 0;
    }
    if (empty($manenabledcounts["e"])) {
        $manenabledcounts["e"] = 0;
    }
    $supenabledcounts = array_count_values($supenabledrecs);
    if (empty($supenabledcounts["d"])) {
        $supenabledcounts["d"] = 0;
    }
    if (empty($supenabledcounts["e"])) {
        $supenabledcounts["e"] = 0;
    }
    $echecked = '';
    $emchecked = '';
    $eschecked = '';
    if ($enabledcounts["d"] >= $enabledcounts["d"] + $enabledcounts["e"]) {
        $echecked = 'checked';
    }
    if ($manenabledcounts["d"] >= $manenabledcounts["d"] + $manenabledcounts["e"]) {
        $emchecked = 'checked';
    }
    if ($supenabledcounts["d"] >= $supenabledcounts["d"] + $supenabledcounts["e"]) {
        $eschecked = 'checked';
    }

    $headers = [get_string('emailtemplatename', 'local_iomad'),
                get_string('enable') . "<br>" .
                html_writer::start_tag('label', ['class' => 'switch']) .
                html_writer::empty_tag('input', ['class' => 'checkbox enableallall',
                                                 'type' => 'checkbox',
                                                 $echecked => true,
                                                 'value' => "{$prefix}.e.{$page}"]) .
                html_writer::empty_tag('span', ['class' => 'slider round']) .
                html_writer::end_tag('label'),
                get_string('enable_manager', 'local_iomad') . "<br>" .
                html_writer::start_tag('label', ['class' => 'switch']) .
                html_writer::empty_tag('input', ['class' => 'checkbox enableallmanager',
                                                 'type' => 'checkbox',
                                                 $emchecked => true,
                                                 'value' => "{$prefix}.em.{$page}"]) .
                html_writer::empty_tag('span', ['class' => 'slider round']) .
                html_writer::end_tag('label'),
                get_string('enable_supervisor', 'local_iomad') . "<br>" .
                html_writer::start_tag('label', ['class' => 'switch']) .
                html_writer::empty_tag('input', ['class' => 'checkbox enableallsupervisor',
                                                 'type' => 'checkbox',
                                                 $eschecked => true,
                                                 'value' => "{$prefix}.es.{$page}"]) .
                html_writer::empty_tag('span', ['class' => 'slider round']) .
                html_writer::end_tag('label'),
                ''];

    $columns = ['templatename',
                'enableuser',
                'enablemanager',
                'enablesupervisor',
                'actions'];

    // Display the list of templates.
    $usertemplates = email::get_user_templates(false);
    $table = new local_iomad\tables\templates_table('email_templatess_table');
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->define_baseurl($baseurl);
    $table->define_columns($columns);
    $table->define_headers($headers);
    $table->no_sorting('actions');
    $table->no_sorting('templatename');
    $table->no_sorting('enableuser');
    $table->no_sorting('enablemanager');
    $table->no_sorting('enablesupervisor');
    $table->sort_default_column = 'templatename';
    $table->column_style('enableuser', 'text-align', 'right');
    $table->column_style('enablemanager', 'text-align', 'right');
    $table->column_style('enablesupervisor', 'text-align', 'right');
    $table->out($perpage, true);

}

echo $output->footer();
