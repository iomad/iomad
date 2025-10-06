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
 * @package   local_email
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * As of the implementation of this block and the general navigation code
 * in Moodle 2.0 the body of immediate upgrade work for this block and
 * settings is done in core upgrade {@see lib/db/upgrade.php}
 *
 * There were several reasons that they were put there and not here, both becuase
 * the process for the two blocks was very similar and because the upgrade process
 * was complex due to us wanting to remvoe the outmoded blocks that this
 * block was going to replace.
 *
 * @global moodle_database $DB
 * @param int $oldversion
 * @param object $block
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_email_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011111400) {

        // Define table email to be created.
        $table = new xmldb_table('email');

        // Adding fields to table email.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           XMLDB_SEQUENCE, null);
        $table->add_field('templatename', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('modifiedtime', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, null);
        $table->add_field('sent', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('varsreplaced', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                           null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, null);
        $table->add_field('invoiceid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                           null, null, null);
        $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                           null, null, null);

        // Adding keys to table email.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for email.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2011111400, 'local', 'email');
    }

    if ($oldversion < 2012011300) {

        // Define field senderid to be added to email.
        $table = new xmldb_table('email');
        $field = new xmldb_field('senderid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                                  null, null, null, 'classroomid');

        // Conditionally launch add field senderid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2012011300, 'local', 'email');
    }

    if ($oldversion < 2012092600) {

        // Define field headers to be added to email.
        $table = new xmldb_table('email');
        $field = new xmldb_field('headers', XMLDB_TYPE_TEXT, 'big',
                                  null, null, null, null, 'senderid');

        // Conditionally launch add field headers.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2012092600, 'local', 'email');
    }

    if ($oldversion < 2016051601) {

        // Define field due to be added to email.
        $table = new xmldb_table('email');
        $field = new xmldb_field('due', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'headers');

        // Conditionally launch add field due.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2016051601, 'local', 'email');
    }

    if ($oldversion < 2017080700) {

        // Define field lang to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('lang', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'en', 'name');

        // Conditionally launch add field lang.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080700, 'local', 'email');
    }

    if ($oldversion < 2017080701) {

        // Define field disabled to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'body');

        // Conditionally launch add field disabled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field disabledmanager to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('disabledmanager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'disabled');

        // Conditionally launch add field disabledmanager.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field disabledsupervisor to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('disabledsupervisor', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'disabledmanager');

        // Conditionally launch add field disabledsupervisor.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field repeatperiod to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('repeatperiod', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'disabledsupervisor');

        // Conditionally launch add field repeatperiod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080701, 'local', 'email');
    }

    if ($oldversion < 2017080702) {

        // Define field repeatvalue to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('repeatvalue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'repeatperiod');

        // Conditionally launch add field repeatvalue.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field repeateday to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('repeateday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'repeatvalue');

        // Conditionally launch add field repeateday.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table email_templateset to be created.
        $table = new xmldb_table('email_templateset');

        // Adding fields to table email_templateset.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table email_templateset.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for email_templateset.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table email_templateset_templates to be created.
        $table = new xmldb_table('email_templateset_templates');

        // Adding fields to table email_templateset_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templateset', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('disabledmanager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('disabledsupervisor', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('repeatperiod', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('repeatvalue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('repeateday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table email_templateset_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for email_templateset_templates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080702, 'local', 'email');
    }

    if ($oldversion < 2017080703) {

        // Rename field name on table email_templateset to templatesetname.
        $table = new xmldb_table('email_templateset');
        $field = new xmldb_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field templatesetname.
        $dbman->rename_field($table, $field, 'templatesetname');

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080703, 'local', 'email');
    }

    if ($oldversion < 2017080704) {

        // Define field companyid to be added to email.
        $table = new xmldb_table('email');
        $field = new xmldb_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'varsreplaced');

        // Conditionally launch add field companyid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // get all emails
        $emails = $DB->get_recordset('email', [], '', 'id, userid');

        foreach ($emails as $email) {
            $company = company::by_userid($email->userid);
            if (!empty($company->id)) {
                $DB->set_field('email','companyid', $company->id, array('id' => $email->id));
            }
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080704, 'local', 'email');
    }

    if ($oldversion < 2017080705) {

        // Define field signature to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('signature', XMLDB_TYPE_TEXT, null, null, null, null, null, 'body');

        // Conditionally launch add field signature.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailto to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailto', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'disabledsupervisor');

        // Conditionally launch add field emailto.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailtoother to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailtoother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailto');

        // Conditionally launch add field emailtoother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailcc to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailcc', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailtoother');

        // Conditionally launch add field emailcc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailccother to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailccother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailcc');

        // Conditionally launch add field emailccother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailfrom to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailfrom', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'emailccother');

        // Conditionally launch add field emailfrom.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailfromother to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailfromother', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'emailfrom');

        // Conditionally launch add field emailfromother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailreplyto to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailreplyto', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'emailfromother');

        // Conditionally launch add field emailreplyto.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailreplytoother to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailreplytoother', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'emailreplyto');

        // Conditionally launch add field emailreplytoother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080705, 'local', 'email');
    }

    if ($oldversion < 2017080707) {

        // Rename field repeateday on table email_template to repeatday.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('repeateday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'repeatvalue');

        // Launch rename field repeatday.
        $dbman->rename_field($table, $field, 'repeatday');

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017080707, 'local', 'email');
    }

    if ($oldversion < 2018112400) {

        // Define field emailfromothername to be added to email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('emailfromothername', XMLDB_TYPE_TEXT, null, null, null, null, null, 'repeatday');

        // Conditionally launch add field emailfromothername.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2018112400, 'local', 'email');
    }

    if ($oldversion < 2018112401) {

        // Changing type of field subject on table email_templateset_templates to char.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'lang');

        // Launch change of type for field subject.
        $dbman->change_field_type($table, $field);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2018112401, 'local', 'email');
    }

    if ($oldversion < 2018112402) {

        // Changing precision of field subject on table email_templateset_templates to (255).
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'lang');

        // Launch change of precision for field subject.
        $dbman->change_field_precision($table, $field);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2018112402, 'local', 'email');
    }

    if ($oldversion < 2023022400) {

        // Define field default to be added to email_templateset.
        $table = new xmldb_table('email_templateset');
        $field = new xmldb_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'templatesetname');

        // Conditionally launch add field default.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2023022400, 'local', 'email');
    }

    if ($oldversion < 2023022500) {

        // Changing nullability of field subject on table email_template to null.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'lang');

        // Launch change of nullability for field subject.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field body on table email_template to null.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('body', XMLDB_TYPE_TEXT, null, null, null, null, null, 'subject');

        // Launch change of nullability for field body.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field subject on table email_templateset_templates to null.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'lang');

        // Launch change of nullability for field subject.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field body on table email_templateset_templates to null.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('body', XMLDB_TYPE_TEXT, null, null, null, null, null, 'subject');

        // Launch change of nullability for field body.
        $dbman->change_field_notnull($table, $field);

        // Define index compidnamelang (not unique) to be added to email_template.
        $table = new xmldb_table('email_template');
        $index = new xmldb_index('compidnamelang', XMLDB_INDEX_NOTUNIQUE, ['companyid', 'name', 'lang']);

        // Conditionally launch add index compidnamelang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index compidlang (not unique) to be added to email_template.
        $table = new xmldb_table('email_template');
        $index = new xmldb_index('compidlang', XMLDB_INDEX_NOTUNIQUE, ['companyid', 'lang']);

        // Conditionally launch add index compidlang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field signature to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('signature', XMLDB_TYPE_TEXT, null, null, null, null, null, 'body');

        // Conditionally launch add field signature.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailto to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailto', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'disabledsupervisor');

        // Conditionally launch add field emailto.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailtoother to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailtoother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailto');

        // Conditionally launch add field emailtoother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailcc to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailcc', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailtoother');

        // Conditionally launch add field emailcc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailccother to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailccother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailcc');

        // Conditionally launch add field emailccother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailfrom to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailfrom', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailccother');

        // Conditionally launch add field emailfrom.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailfromother to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailfromother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailfrom');

        // Conditionally launch add field emailfromother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailreplyto to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailreplyto', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailfromother');

        // Conditionally launch add field emailreplyto.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailreplytoother to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailreplytoother', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'emailreplyto');

        // Conditionally launch add field emailreplytoother.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailfromothername to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('emailfromothername', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'repeateday');

        // Conditionally launch add field emailfromothername.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //// Rename field repeateday on table email_templateset_templates to repeatday.
        //$table = new xmldb_table('email_templateset_templates');
        //$field = new xmldb_field('repeateday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'repeatvalue');

        // Launch rename field repeateday.
        //$dbman->rename_field($table, $field, 'repeatday');

        // Define key templateset (foreign) to be added to email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $key = new xmldb_key('templateset', XMLDB_KEY_FOREIGN, ['templateset'], 'email_templateset', ['id']);

        // Launch add key templateset.
        $dbman->add_key($table, $key);

        // Mark that something is happening.
        set_config('local_email_templates_migrating', 1);

        // Set up an AdHoc task to migrate all of the email templates.
        $migratetask = new \local_email\task\migratetemplates();

        // Queue the task.
        \core\task\manager::queue_adhoc_task($migratetask);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2023022500, 'local', 'email');
    }

    if ($oldversion < 2023030900) {

        require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/customlang/locallib.php');

        $progressbar = new progress_bar();
        $progressbar->create();         // prints the HTML code of the progress bar

        // we may need a bit of extra execution time and memory here
        core_php_time_limit::raise(HOURSECS);
        raise_memory_limit(MEMORY_EXTRA);
        tool_customlang_utils::checkout($CFG->lang, $progressbar);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2023030900, 'local', 'email');
    }

    if ($oldversion < 2024030700) {

        // Rename field repeatday on table email_templateset_templates to NEWNAMEGOESHERE.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('repeateday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'repeatvalue');

        // Launch rename field repeatday.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'repeatday');
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2024030700, 'local', 'email');
    }

    if ($oldversion < 2024032600) {

        // Set up an AdHoc task to add the new email templates.
        $addtask = new \local_email\task\addtemplate();
        $addtask->set_custom_data(['templatename' => 'user_signed_up_to_waitlist']);

        // Queue the task.
        \core\task\manager::queue_adhoc_task($addtask);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2024032600, 'local', 'email');
    }

    if ($oldversion < 2024070100) {

        // Set up an AdHoc task to add the new email templates.
        $addtask = new \local_email\task\addtemplate();
        $addtask->set_custom_data(['templatename' => 'user_signed_up_for_event_reminder']);

        // Queue the task.
        \core\task\manager::queue_adhoc_task($addtask);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2024070100, 'local', 'email');
    }

    if ($oldversion < 2024111900) {

        // Set up an AdHoc task to add the new email templates.
        $addtask = new \local_email\task\addtemplate();
        $addtask->set_custom_data(['templatename' => 'expiring_digest_manager', 'disabled' => 1]);

        // Queue the task.
        \core\task\manager::queue_adhoc_task($addtask);

        // Set up an AdHoc task to add the new email templates.
        $addtask = new \local_email\task\addtemplate();
        $addtask->set_custom_data(['templatename' => 'warning_digest_manager', 'disabled' => 1]);

        // Queue the task.
        \core\task\manager::queue_adhoc_task($addtask);

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2024111900, 'local', 'email');
    }

    if ($oldversion < 2025011800) {

        // Define table email_template_strings to be created.
        $table = new xmldb_table('email_template_strings');

        // Adding fields to table email_template_strings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('signature', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table email_template_strings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for email_template_strings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table email_templateset_template_strings to be created.
        $table = new xmldb_table('email_templateset_template_strings');

        // Adding fields to table email_templateset_template_strings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templatesetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('signature', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table email_templateset_template_strings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for email_templateset_template_strings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Get all of the companies.
        $companies = $DB->get_records('company');
        $templates = $DB->get_records_sql("SELECT DISTINCT name FROM {email_template}");

        // Sset up progressbar.
        $total = count($companies);
        $progressbar = new progress_bar('migratingcompanytemplates', 500, true);
        $count = 0;

        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        core_php_time_limit::raise(HOURSECS);
        raise_memory_limit(MEMORY_EXTRA);

        foreach ($companies as $company) {
            // Set the default lang we will be using.
            $lang = $CFG->lang;
            if (!empty($company->lang)) {
                $lang = $company->lang;
            }

            // Get the set of company default templates.
            $default = $DB->get_records('email_template', ['companyid' => $company->id, 'lang' => $lang], 'name', 'name,id');

            // Add all of the the entries to the new tables.
            foreach ($templates as $template) {
                if (!empty($default[$template->name])) {
                    $DB->execute("INSERT INTO {email_template_strings} (templateid,lang,subject,body,signature)
                                  SELECT :id, lang, subject, body, signature
                                  FROM {email_template}
                                  WHERE companyid = :companyid
                                  AND name = :name",
                                 ['id' => $default[$template->name]->id,
                                  'companyid' => $company->id,
                                  'name' => $template->name]);
                } else {
                    $missingid = $DB->insert_record('email_template', ['companyid' => $company->id,
                                                                       'name' => $template->name,
                                                                       'lang' => $CFG->lang]);

                    foreach ($langs as $missinglang) {
                        $DB->insert_record('email_template_strings', ['templateid' => $missingid,
                                                                  'lang' => $missinglang]);
                    }
                }
            }

            // Delete all of the records apart from the ones for this lang.
            $DB->delete_records_select('email_template',
                                        "companyid = :companyid AND lang != :lang",
                                        ['companyid' => $company->id, 'lang' => $lang]);
            $count++;
            $progressbar->update($count, $total, "Converting company email templates $count/$total");
        }

        // Get all of the templatesets.
        $templatesets = $DB->get_records('email_templateset', [], '', 'id');
        $lang = $CFG->lang;

        // Set up progressbar.
        $total = count($templatesets);
        $progressbar = new progress_bar('migratingtemplatessettemplates', 500, true);
        $count = 0;

        foreach ($templatesets as $templateset) {

            // Get the set of company default templates.
            $default = $DB->get_records('email_templateset_templates', ['templateset' => $templateset->id, 'lang' => $lang], 'name', 'name,id');

            // Add all of the the entries to the new tables.
            foreach ($templates as $template) {
                if (!empty($default[$template->name])) {
                    $DB->execute("INSERT INTO {email_templateset_template_strings} (templatesetid,lang,subject,body,signature)
                                  SELECT :id, lang, subject, body, signature
                                  FROM {email_templateset_templates}
                                  WHERE templateset = :templateset
                                  AND name = :name",
                                 ['id' => $default[$template->name]->id,
                                  'templateset' => $templateset->id,
                                  'name' => $template->name]);
                } else {
                    $missingid = $DB->insert_record('email_templateset_templates', ['templateset' => $templateset->id,
                                                                                    'name' => $template->name,
                                                                                    'lang' => $CFG->lang]);
                    foreach ($langs as $missinglang) {
                        $DB->insert_record('email_templateset_template_strings', ['templatesetid' => $missingid,
                                                                                  'lang' => $missinglang]);
                    }
                }
            }
            // Delete all of the records apart from the ones for this lang.
            $DB->delete_records_select('email_templateset_templates',
                                       "templateset = :templatesetid AND lang != :lang",
                                       ['templatesetid' => $templateset->id, 'lang' => $CFG->lang]);
            $count++;
            $progressbar->update($count, $total, "Converting templateset email templates $count/$total");
        }

        // Define index compidnamelang (not unique) to be dropped form email_template.
        $table = new xmldb_table('email_template');
        $index = new xmldb_index('compidnamelang', XMLDB_INDEX_NOTUNIQUE, ['companyid', 'name', 'lang']);

        // Conditionally launch drop index compidnamelang.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index compidlang (not unique) to be dropped form email_template.
        $table = new xmldb_table('email_template');
        $index = new xmldb_index('compidlang', XMLDB_INDEX_NOTUNIQUE, ['companyid', 'lang']);

        // Conditionally launch drop index compidlang.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field lang to be dropped from email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('lang');

        // Conditionally launch drop field lang.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field subject to be dropped from email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('subject');

        // Conditionally launch drop field subject.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field body to be dropped from email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('body');

        // Conditionally launch drop field body.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field signature to be dropped from email_template.
        $table = new xmldb_table('email_template');
        $field = new xmldb_field('signature');

        // Conditionally launch drop field signature.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field lang to be dropped from email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('lang');

        // Conditionally launch drop field lang.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field subject to be dropped from email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('subject');

        // Conditionally launch drop field subject.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field body to be dropped from email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('body');

        // Conditionally launch drop field body.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field signature to be dropped from email_templateset_templates.
        $table = new xmldb_table('email_templateset_templates');
        $field = new xmldb_field('signature');

        // Conditionally launch drop field signature.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define key email_templ_strings_tempid (foreign) to be added to email_template_strings.
        $table = new xmldb_table('email_template_strings');
        $key = new xmldb_key('email_templ_strings_tempid', XMLDB_KEY_FOREIGN, ['templateid'], 'email_template', ['id']);

        // Launch add key email_templ_strings_tempid.
        $dbman->add_key($table, $key);

        // Define index email_templ_strings_tempidlang (not unique) to be added to email_template_strings.
        $table = new xmldb_table('email_template_strings');
        $index = new xmldb_index('email_templ_strings_tempidlang', XMLDB_INDEX_NOTUNIQUE, ['templateid', 'lang']);

        // Conditionally launch add index email_templ_strings_tempidlang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key email_templset_templ_str_tempid_fk (foreign) to be added to email_templateset_template_strings.
        $table = new xmldb_table('email_templateset_template_strings');
        $key = new xmldb_key('email_templset_templ_str_tempid_fk', XMLDB_KEY_FOREIGN, ['templatesetid'], 'email_templateset_templates', ['id']);

        // Launch add key email_templset_templ_str_tempid_fk.
        $dbman->add_key($table, $key);

        // Define index email_templset_templ_str_tempidlang (not unique) to be added to email_templateset_template_strings.
        $table = new xmldb_table('email_templateset_template_strings');
        $index = new xmldb_index('email_templset_templ_str_tempidlang', XMLDB_INDEX_NOTUNIQUE, ['templatesetid', 'lang']);

        // Conditionally launch add index email_templset_templ_str_tempidlang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2025011800, 'local', 'email');
    }

    if ($oldversion < 2025012000) {

        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');
        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        // Reload the custom lang table.
        foreach ($langs as $lang) {
            tool_customlang_utils::checkout($lang);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2025012000, 'local', 'email');
    }

    if ($oldversion < 2025012002) {

        // Need to delete any strings for unused templates.
        $deletetemplates = ['completion_warn_manager', 'course_completed_manager', 'expire_manager', 'expiry_warn_manager', 'license_reminder'];
        foreach ($deletetemplates as $deletename) {
            $DB->delete_records('email_template', ['name' => $deletename]);
            $DB->delete_records('email_templateset_templates', ['name' => $deletename]);
            $DB->execute("DELETE FROM {email_template_strings}
                          WHERE templateid NOT IN
                          (SELECT id FROM {email_template})");
            $DB->execute("DELETE FROM {email_templateset_template_strings}
                          WHERE templatesetid NOT IN
                          (SELECT id FROM {email_templateset_templates})");
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2025012002, 'local', 'email');
    }

    return $result;

}
