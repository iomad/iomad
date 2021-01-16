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
 * This file keeps track of upgrades to Moodle.
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   core_install
 * @category  upgrade
 * @copyright 2006 onwards Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main upgrade tasks to be executed on Moodle version bump
 *
 * This function is automatically executed after one bump in the Moodle core
 * version is detected. It's in charge of performing the required tasks
 * to raise core from the previous version to the next one.
 *
 * It's a collection of ordered blocks of code, named "upgrade steps",
 * each one performing one isolated (from the rest of steps) task. Usually
 * tasks involve creating new DB objects or performing manipulation of the
 * information for cleanup/fixup purposes.
 *
 * Each upgrade step has a fixed structure, that can be summarised as follows:
 *
 * if ($oldversion < XXXXXXXXXX.XX) {
 *     // Explanation of the update step, linking to issue in the Tracker if necessary
 *     upgrade_set_timeout(XX); // Optional for big tasks
 *     // Code to execute goes here, usually the XMLDB Editor will
 *     // help you here. See {@link http://docs.moodle.org/dev/XMLDB_editor}.
 *     upgrade_main_savepoint(true, XXXXXXXXXX.XX);
 * }
 *
 * All plugins within Moodle (modules, blocks, reports...) support the existence of
 * their own upgrade.php file, using the "Frankenstyle" component name as
 * defined at {@link http://docs.moodle.org/dev/Frankenstyle}, for example:
 *     - {@link xmldb_page_upgrade($oldversion)}. (modules don't require the plugintype ("mod_") to be used.
 *     - {@link xmldb_auth_manual_upgrade($oldversion)}.
 *     - {@link xmldb_workshopform_accumulative_upgrade($oldversion)}.
 *     - ....
 *
 * In order to keep the contents of this file reduced, it's allowed to create some helper
 * functions to be used here in the {@link upgradelib.php} file at the same directory. Note
 * that such a file must be manually included from upgrade.php, and there are some restrictions
 * about what can be used within it.
 *
 * For more information, take a look to the documentation available:
 *     - Data definition API: {@link http://docs.moodle.org/dev/Data_definition_API}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_main_upgrade($oldversion) {
    global $CFG, $DB;

    require_once($CFG->libdir.'/db/upgradelib.php'); // Core Upgrade-related functions.

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Always keep this upgrade step with version being the minimum
    // allowed version to upgrade from (v3.0.0 right now).
    if ($oldversion < 2015111600) {
        // Just in case somebody hacks upgrade scripts or env, we really can not continue.
        echo("You need to upgrade to 3.0.x or higher first!\n");
        exit(1);
        // Note this savepoint is 100% unreachable, but needed to pass the upgrade checks.
        upgrade_main_savepoint(true, 2015111600);
    }

    if ($oldversion < 2016011300.01) {

        // This is a big upgrade script. We create new table tag_coll and the field
        // tag.tagcollid pointing to it.

        // Define table tag_coll to be created.
        $table = new xmldb_table('tag_coll');

        // Adding fields to table tagcloud.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('searchable', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('customurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table tagcloud.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for tagcloud.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Table {tag}.
        // Define index name (unique) to be dropped form tag - we will replace it with index on (tagcollid,name) later.
        $table = new xmldb_table('tag');
        $index = new xmldb_index('name', XMLDB_INDEX_UNIQUE, array('name'));

        // Conditionally launch drop index name.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field tagcollid to be added to tag, we create it as null first and will change to notnull later.
        $table = new xmldb_table('tag');
        $field = new xmldb_field('tagcollid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

        // Conditionally launch add field tagcloudid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.01);
    }

    if ($oldversion < 2016011300.02) {
        // Create a default tag collection if not exists and update the field tag.tagcollid to point to it.
        if (!$tcid = $DB->get_field_sql('SELECT id FROM {tag_coll} ORDER BY isdefault DESC, sortorder, id', null,
                IGNORE_MULTIPLE)) {
            $tcid = $DB->insert_record('tag_coll', array('isdefault' => 1, 'sortorder' => 0));
        }
        $DB->execute('UPDATE {tag} SET tagcollid = ? WHERE tagcollid IS NULL', array($tcid));

        // Define index tagcollname (unique) to be added to tag.
        $table = new xmldb_table('tag');
        $index = new xmldb_index('tagcollname', XMLDB_INDEX_UNIQUE, array('tagcollid', 'name'));
        $field = new xmldb_field('tagcollid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'userid');

        // Conditionally launch add index tagcollname.
        if (!$dbman->index_exists($table, $index)) {
            // Launch change of nullability for field tagcollid.
            $dbman->change_field_notnull($table, $field);
            $dbman->add_index($table, $index);
        }

        // Define key tagcollid (foreign) to be added to tag.
        $table = new xmldb_table('tag');
        $key = new xmldb_key('tagcollid', XMLDB_KEY_FOREIGN, array('tagcollid'), 'tag_coll', array('id'));

        // Launch add key tagcloudid.
        $dbman->add_key($table, $key);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.02);
    }

    if ($oldversion < 2016011300.03) {

        // Define table tag_area to be created.
        $table = new xmldb_table('tag_area');

        // Adding fields to table tag_area.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemtype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('tagcollid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('callback', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('callbackfile', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table tag_area.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('tagcollid', XMLDB_KEY_FOREIGN, array('tagcollid'), 'tag_coll', array('id'));

        // Adding indexes to table tag_area.
        $table->add_index('compitemtype', XMLDB_INDEX_UNIQUE, array('component', 'itemtype'));

        // Conditionally launch create table for tag_area.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.03);
    }

    if ($oldversion < 2016011300.04) {

        // Define index itemtype-itemid-tagid-tiuserid (unique) to be dropped form tag_instance.
        $table = new xmldb_table('tag_instance');
        $index = new xmldb_index('itemtype-itemid-tagid-tiuserid', XMLDB_INDEX_UNIQUE,
                array('itemtype', 'itemid', 'tagid', 'tiuserid'));

        // Conditionally launch drop index itemtype-itemid-tagid-tiuserid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.04);
    }

    if ($oldversion < 2016011300.05) {

        $DB->execute("UPDATE {tag_instance} SET component = ? WHERE component IS NULL", array(''));

        // Changing nullability of field component on table tag_instance to not null.
        $table = new xmldb_table('tag_instance');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'tagid');

        // Launch change of nullability for field component.
        $dbman->change_field_notnull($table, $field);

        // Changing type of field itemtype on table tag_instance to char.
        $table = new xmldb_table('tag_instance');
        $field = new xmldb_field('itemtype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'component');

        // Launch change of type for field itemtype.
        $dbman->change_field_type($table, $field);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.05);
    }

    if ($oldversion < 2016011300.06) {

        // Define index taggeditem (unique) to be added to tag_instance.
        $table = new xmldb_table('tag_instance');
        $index = new xmldb_index('taggeditem', XMLDB_INDEX_UNIQUE, array('component', 'itemtype', 'itemid', 'tiuserid', 'tagid'));

        // Conditionally launch add index taggeditem.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.06);
    }

    if ($oldversion < 2016011300.07) {

        // Define index taglookup (not unique) to be added to tag_instance.
        $table = new xmldb_table('tag_instance');
        $index = new xmldb_index('taglookup', XMLDB_INDEX_NOTUNIQUE, array('itemtype', 'component', 'tagid', 'contextid'));

        // Conditionally launch add index taglookup.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016011300.07);
    }

    if ($oldversion < 2016011301.00) {

        // Force uninstall of deleted tool.
        if (!file_exists("$CFG->dirroot/webservice/amf")) {
            // Remove capabilities.
            capabilities_cleanup('webservice_amf');
            // Remove all other associated config.
            unset_all_config_for_plugin('webservice_amf');
        }
        upgrade_main_savepoint(true, 2016011301.00);
    }

    if ($oldversion < 2016011901.00) {

        // Convert calendar_lookahead to nearest new value.
        $transaction = $DB->start_delegated_transaction();

        // Count all users who curretly have that preference set (for progress bar).
        $total = $DB->count_records_select('user_preferences', "name = 'calendar_lookahead' AND value != '0'");
        $pbar = new progress_bar('upgradecalendarlookahead', 500, true);

        // Get all these users, one at a time.
        $rs = $DB->get_recordset_select('user_preferences', "name = 'calendar_lookahead' AND value != '0'");
        $i = 0;
        foreach ($rs as $userpref) {

            // Calculate and set new lookahead value.
            if ($userpref->value > 90) {
                $newvalue = 120;
            } else if ($userpref->value > 60 and $userpref->value < 90) {
                $newvalue = 90;
            } else if ($userpref->value > 30 and $userpref->value < 60) {
                $newvalue = 60;
            } else if ($userpref->value > 21 and $userpref->value < 30) {
                $newvalue = 30;
            } else if ($userpref->value > 14 and $userpref->value < 21) {
                $newvalue = 21;
            } else if ($userpref->value > 7 and $userpref->value < 14) {
                $newvalue = 14;
            } else {
                $newvalue = $userpref->value;
            }

            $DB->set_field('user_preferences', 'value', $newvalue, array('id' => $userpref->id));

            // Update progress.
            $i++;
            $pbar->update($i, $total, "Upgrading user preference settings - $i/$total.");
        }
        $rs->close();
        $transaction->allow_commit();

        upgrade_main_savepoint(true, 2016011901.00);
    }

    if ($oldversion < 2016020200.00) {

        // Define field isstandard to be added to tag.
        $table = new xmldb_table('tag');
        $field = new xmldb_field('isstandard', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'rawname');

        // Conditionally launch add field isstandard.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index tagcolltype (not unique) to be dropped form tag.
        // This index is no longer created however it was present at some point and it's better to be safe and try to drop it.
        $index = new xmldb_index('tagcolltype', XMLDB_INDEX_NOTUNIQUE, array('tagcollid', 'tagtype'));

        // Conditionally launch drop index tagcolltype.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index tagcolltype (not unique) to be added to tag.
        $index = new xmldb_index('tagcolltype', XMLDB_INDEX_NOTUNIQUE, array('tagcollid', 'isstandard'));

        // Conditionally launch add index tagcolltype.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field tagtype to be dropped from tag.
        $field = new xmldb_field('tagtype');

        // Conditionally launch drop field tagtype and update isstandard.
        if ($dbman->field_exists($table, $field)) {
            $DB->execute("UPDATE {tag} SET isstandard=(CASE WHEN (tagtype = ?) THEN 1 ELSE 0 END)", array('official'));
            $dbman->drop_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016020200.00);
    }

    if ($oldversion < 2016020201.00) {

        // Define field showstandard to be added to tag_area.
        $table = new xmldb_table('tag_area');
        $field = new xmldb_field('showstandard', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'callbackfile');

        // Conditionally launch add field showstandard.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // By default set user area to hide standard tags. 2 = core_tag_tag::HIDE_STANDARD (can not use constant here).
        $DB->execute("UPDATE {tag_area} SET showstandard = ? WHERE itemtype = ? AND component = ?",
            array(2, 'user', 'core'));

        // Changing precision of field enabled on table tag_area to (1).
        $table = new xmldb_table('tag_area');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'itemtype');

        // Launch change of precision for field enabled.
        $dbman->change_field_precision($table, $field);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016020201.00);
    }

    if ($oldversion < 2016021500.00) {
        $root = $CFG->tempdir . '/download';
        if (is_dir($root)) {
            // Fetch each repository type - include all repos, not just enabled.
            $repositories = $DB->get_records('repository', array(), '', 'type');

            foreach ($repositories as $id => $repository) {
                $directory = $root . '/repository_' . $repository->type;
                if (is_dir($directory)) {
                    fulldelete($directory);
                }
            }
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016021500.00);
    }

    if ($oldversion < 2016021501.00) {
        // This could take a long time. Unfortunately, no way to know how long, and no way to do progress, so setting for 1 hour.
        upgrade_set_timeout(3600);

        // Define index userid-itemid (not unique) to be added to grade_grades_history.
        $table = new xmldb_table('grade_grades_history');
        $index = new xmldb_index('userid-itemid-timemodified', XMLDB_INDEX_NOTUNIQUE, array('userid', 'itemid', 'timemodified'));

        // Conditionally launch add index userid-itemid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016021501.00);
    }

    if ($oldversion < 2016030103.00) {

        // MDL-50887. Implement plugins infrastructure for antivirus and create ClamAV plugin.
        // This routine moves core ClamAV configuration to plugin level.

        // If clamav was configured and enabled, enable the plugin.
        if (!empty($CFG->runclamonupload) && !empty($CFG->pathtoclam)) {
            set_config('antiviruses', 'clamav');
        } else {
            set_config('antiviruses', '');
        }

        if (isset($CFG->runclamonupload)) {
            // Just unset global configuration, we have already enabled the plugin
            // which implies that ClamAV will be used for scanning uploaded files.
            unset_config('runclamonupload');
        }
        // Move core ClamAV configuration settings to plugin.
        if (isset($CFG->pathtoclam)) {
            set_config('pathtoclam', $CFG->pathtoclam, 'antivirus_clamav');
            unset_config('pathtoclam');
        }
        if (isset($CFG->quarantinedir)) {
            set_config('quarantinedir', $CFG->quarantinedir, 'antivirus_clamav');
            unset_config('quarantinedir');
        }
        if (isset($CFG->clamfailureonupload)) {
            set_config('clamfailureonupload', $CFG->clamfailureonupload, 'antivirus_clamav');
            unset_config('clamfailureonupload');
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016030103.00);
    }

    if ($oldversion < 2016030400.01) {
        // Add the new services field.
        $table = new xmldb_table('external_functions');
        $field = new xmldb_field('services', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'capabilities');

        // Conditionally launch add field services.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016030400.01);
    }

    if ($oldversion < 2016041500.50) {

        // Define table competency to be created.
        $table = new xmldb_table('competency');

        // Adding fields to table competency.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('competencyframeworkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruletype', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('ruleoutcome', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ruleconfig', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('scaleconfiguration', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table competency.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency.
        $table->add_index('idnumberframework', XMLDB_INDEX_UNIQUE, array('competencyframeworkid', 'idnumber'));
        $table->add_index('ruleoutcome', XMLDB_INDEX_NOTUNIQUE, array('ruleoutcome'));

        // Conditionally launch create table for competency.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.50);
    }

    if ($oldversion < 2016041500.51) {

        // Define table competency_coursecompsetting to be created.
        $table = new xmldb_table('competency_coursecompsetting');

        // Adding fields to table competency_coursecompsetting.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pushratingstouserplans', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table competency_coursecompsetting.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseidlink', XMLDB_KEY_FOREIGN_UNIQUE, array('courseid'), 'course', array('id'));

        // Conditionally launch create table for competency_coursecompsetting.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.51);
    }

    if ($oldversion < 2016041500.52) {

        // Define table competency_framework to be created.
        $table = new xmldb_table('competency_framework');

        // Adding fields to table competency_framework.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('scaleconfiguration', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('taxonomies', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table competency_framework.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_framework.
        $table->add_index('idnumber', XMLDB_INDEX_UNIQUE, array('idnumber'));

        // Conditionally launch create table for competency_framework.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.52);
    }

    if ($oldversion < 2016041500.53) {

        // Define table competency_coursecomp to be created.
        $table = new xmldb_table('competency_coursecomp');

        // Adding fields to table competency_coursecomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleoutcome', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_coursecomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseidlink', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('competencyid', XMLDB_KEY_FOREIGN, array('competencyid'), 'competency_competency', array('id'));

        // Adding indexes to table competency_coursecomp.
        $table->add_index('courseidruleoutcome', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'ruleoutcome'));
        $table->add_index('courseidcompetencyid', XMLDB_INDEX_UNIQUE, array('courseid', 'competencyid'));

        // Conditionally launch create table for competency_coursecomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.53);
    }

    if ($oldversion < 2016041500.54) {

        // Define table competency_plan to be created.
        $table = new xmldb_table('competency_plan');

        // Adding fields to table competency_plan.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('origtemplateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duedate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('reviewerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_plan.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_plan.
        $table->add_index('useridstatus', XMLDB_INDEX_NOTUNIQUE, array('userid', 'status'));
        $table->add_index('templateid', XMLDB_INDEX_NOTUNIQUE, array('templateid'));
        $table->add_index('statusduedate', XMLDB_INDEX_NOTUNIQUE, array('status', 'duedate'));

        // Conditionally launch create table for competency_plan.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.54);
    }

    if ($oldversion < 2016041500.55) {

        // Define table competency_template to be created.
        $table = new xmldb_table('competency_template');

        // Adding fields to table competency_template.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('duedate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table competency_template.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for competency_template.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.55);
    }

    if ($oldversion < 2016041500.56) {

        // Define table competency_templatecomp to be created.
        $table = new xmldb_table('competency_templatecomp');

        // Adding fields to table competency_templatecomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table competency_templatecomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('templateidlink', XMLDB_KEY_FOREIGN, array('templateid'), 'competency_template', array('id'));
        $table->add_key('competencyid', XMLDB_KEY_FOREIGN, array('competencyid'), 'competency_competency', array('id'));

        // Conditionally launch create table for competency_templatecomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.56);
    }

    if ($oldversion < 2016041500.57) {

        // Define table competency_templatecohort to be created.
        $table = new xmldb_table('competency_templatecohort');

        // Adding fields to table competency_templatecohort.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_templatecohort.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_templatecohort.
        $table->add_index('templateid', XMLDB_INDEX_NOTUNIQUE, array('templateid'));
        $table->add_index('templatecohortids', XMLDB_INDEX_UNIQUE, array('templateid', 'cohortid'));

        // Conditionally launch create table for competency_templatecohort.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.57);
    }

    if ($oldversion < 2016041500.58) {

        // Define table competency_relatedcomp to be created.
        $table = new xmldb_table('competency_relatedcomp');

        // Adding fields to table competency_relatedcomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('relatedcompetencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_relatedcomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for competency_relatedcomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.58);
    }

    if ($oldversion < 2016041500.59) {

        // Define table competency_usercomp to be created.
        $table = new xmldb_table('competency_usercomp');

        // Adding fields to table competency_usercomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reviewerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('proficiency', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_usercomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_usercomp.
        $table->add_index('useridcompetency', XMLDB_INDEX_UNIQUE, array('userid', 'competencyid'));

        // Conditionally launch create table for competency_usercomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.59);
    }

    if ($oldversion < 2016041500.60) {

        // Define table competency_usercompcourse to be created.
        $table = new xmldb_table('competency_usercompcourse');

        // Adding fields to table competency_usercompcourse.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('proficiency', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_usercompcourse.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_usercompcourse.
        $table->add_index('useridcoursecomp', XMLDB_INDEX_UNIQUE, array('userid', 'courseid', 'competencyid'));

        // Conditionally launch create table for competency_usercompcourse.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.60);
    }

    if ($oldversion < 2016041500.61) {

        // Define table competency_usercompplan to be created.
        $table = new xmldb_table('competency_usercompplan');

        // Adding fields to table competency_usercompplan.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('proficiency', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_usercompplan.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_usercompplan.
        $table->add_index('usercompetencyplan', XMLDB_INDEX_UNIQUE, array('userid', 'competencyid', 'planid'));

        // Conditionally launch create table for competency_usercompplan.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.61);
    }

    if ($oldversion < 2016041500.62) {

        // Define table competency_plancomp to be created.
        $table = new xmldb_table('competency_plancomp');

        // Adding fields to table competency_plancomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_plancomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_plancomp.
        $table->add_index('planidcompetencyid', XMLDB_INDEX_UNIQUE, array('planid', 'competencyid'));

        // Conditionally launch create table for competency_plancomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.62);
    }

    if ($oldversion < 2016041500.63) {

        // Define table competency_evidence to be created.
        $table = new xmldb_table('competency_evidence');

        // Adding fields to table competency_evidence.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('usercompetencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('actionuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('descidentifier', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('desccomponent', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('desca', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_evidence.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_evidence.
        $table->add_index('usercompetencyid', XMLDB_INDEX_NOTUNIQUE, array('usercompetencyid'));

        // Conditionally launch create table for competency_evidence.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.63);
    }

    if ($oldversion < 2016041500.64) {

        // Define table competency_userevidence to be created.
        $table = new xmldb_table('competency_userevidence');

        // Adding fields to table competency_userevidence.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_userevidence.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_userevidence.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for competency_userevidence.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.64);
    }

    if ($oldversion < 2016041500.65) {

        // Define table competency_userevidencecomp to be created.
        $table = new xmldb_table('competency_userevidencecomp');

        // Adding fields to table competency_userevidencecomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userevidenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_userevidencecomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table competency_userevidencecomp.
        $table->add_index('userevidenceid', XMLDB_INDEX_NOTUNIQUE, array('userevidenceid'));
        $table->add_index('userevidencecompids', XMLDB_INDEX_UNIQUE, array('userevidenceid', 'competencyid'));

        // Conditionally launch create table for competency_userevidencecomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.65);
    }

    if ($oldversion < 2016041500.66) {

        // Define table competency_modulecomp to be created.
        $table = new xmldb_table('competency_modulecomp');

        // Adding fields to table competency_modulecomp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleoutcome', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table competency_modulecomp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cmidkey', XMLDB_KEY_FOREIGN, array('cmid'), 'course_modules', array('id'));
        $table->add_key('competencyidkey', XMLDB_KEY_FOREIGN, array('competencyid'), 'competency_competency', array('id'));

        // Adding indexes to table competency_modulecomp.
        $table->add_index('cmidruleoutcome', XMLDB_INDEX_NOTUNIQUE, array('cmid', 'ruleoutcome'));
        $table->add_index('cmidcompetencyid', XMLDB_INDEX_UNIQUE, array('cmid', 'competencyid'));

        // Conditionally launch create table for competency_modulecomp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016041500.66);
    }

    if ($oldversion < 2016042100.00) {
        // Update all countries to upper case.
        $DB->execute("UPDATE {user} SET country = UPPER(country)");
        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016042100.00);
    }

    if ($oldversion < 2016042600.01) {
        $deprecatedwebservices = [
            'moodle_course_create_courses',
            'moodle_course_get_courses',
            'moodle_enrol_get_enrolled_users',
            'moodle_enrol_get_users_courses',
            'moodle_enrol_manual_enrol_users',
            'moodle_file_get_files',
            'moodle_file_upload',
            'moodle_group_add_groupmembers',
            'moodle_group_create_groups',
            'moodle_group_delete_groupmembers',
            'moodle_group_delete_groups',
            'moodle_group_get_course_groups',
            'moodle_group_get_groupmembers',
            'moodle_group_get_groups',
            'moodle_message_send_instantmessages',
            'moodle_notes_create_notes',
            'moodle_role_assign',
            'moodle_role_unassign',
            'moodle_user_create_users',
            'moodle_user_delete_users',
            'moodle_user_get_course_participants_by_id',
            'moodle_user_get_users_by_courseid',
            'moodle_user_get_users_by_id',
            'moodle_user_update_users',
            'core_grade_get_definitions',
            'core_user_get_users_by_id',
            'moodle_webservice_get_siteinfo',
            'mod_forum_get_forum_discussions'
        ];

        list($insql, $params) = $DB->get_in_or_equal($deprecatedwebservices);
        $DB->delete_records_select('external_functions', "name $insql", $params);
        $DB->delete_records_select('external_services_functions', "functionname $insql", $params);
        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016042600.01);
    }

    if ($oldversion < 2016051300.00) {
        // Add a default competency rating scale.
        make_competence_scale();

        // Savepoint reached.
        upgrade_main_savepoint(true, 2016051300.00);
    }

    if ($oldversion < 2016051700.01) {
        // This script is included in each major version upgrade process (3.0, 3.1) so make sure we don't run it twice.
        if (empty($CFG->upgrade_letterboundarycourses)) {
            // MDL-45390. If a grade is being displayed with letters and the grade boundaries are not being adhered to properly
            // then this course will also be frozen.
            // If the changes are accepted then the display of some grades may change.
            // This is here to freeze the gradebook in affected courses.
            upgrade_course_letter_boundary();

            // To skip running the same script on the upgrade to the next major version release.
            set_config('upgrade_letterboundarycourses', 1);
        }
        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016051700.01);
    }

    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2016081700.00) {

        // If someone is emotionally attached to it let's leave the config (basically the version) there.
        if (!file_exists($CFG->dirroot . '/report/search/classes/output/form.php')) {
            unset_all_config_for_plugin('report_search');
        }

        // Savepoint reached.
        upgrade_main_savepoint(true, 2016081700.00);
    }

    if ($oldversion < 2016081700.02) {
        // Default schedule values.
        $hour = 0;
        $minute = 0;

        // Get the old settings.
        if (isset($CFG->statsruntimestarthour)) {
            $hour = $CFG->statsruntimestarthour;
        }
        if (isset($CFG->statsruntimestartminute)) {
            $minute = $CFG->statsruntimestartminute;
        }

        // Retrieve the scheduled task record first.
        $stattask = $DB->get_record('task_scheduled', array('component' => 'moodle', 'classname' => '\core\task\stats_cron_task'));

        // Don't touch customised scheduling.
        if ($stattask && !$stattask->customised) {

            $nextruntime = mktime($hour, $minute, 0, date('m'), date('d'), date('Y'));
            if ($nextruntime < $stattask->lastruntime) {
                // Add 24 hours to the next run time.
                $newtime = new DateTime();
                $newtime->setTimestamp($nextruntime);
                $newtime->add(new DateInterval('P1D'));
                $nextruntime = $newtime->getTimestamp();
            }
            $stattask->nextruntime = $nextruntime;
            $stattask->minute = $minute;
            $stattask->hour = $hour;
            $stattask->customised = 1;
            $DB->update_record('task_scheduled', $stattask);
        }
        // These settings are no longer used.
        unset_config('statsruntimestarthour');
        unset_config('statsruntimestartminute');
        unset_config('statslastexecution');

        upgrade_main_savepoint(true, 2016081700.02);
    }

    if ($oldversion < 2016082200.00) {
        // An upgrade step to remove any duplicate stamps, within the same context, in the question_categories table, and to
        // add a unique index to (contextid, stamp) to avoid future stamp duplication. See MDL-54864.

        // Extend the execution time limit of the script to 2 hours.
        upgrade_set_timeout(7200);

        // This SQL fetches the id of those records which have duplicate stamps within the same context.
        // This doesn't return the original record within the context, from which the duplicate stamps were likely created.
        $fromclause = "FROM (
                        SELECT min(id) AS minid, contextid, stamp
                            FROM {question_categories}
                            GROUP BY contextid, stamp
                        ) minid
                        JOIN {question_categories} qc
                            ON qc.contextid = minid.contextid AND qc.stamp = minid.stamp AND qc.id > minid.minid";

        // Get the total record count - used for the progress bar.
        $countduplicatessql = "SELECT count(qc.id) $fromclause";
        $total = $DB->count_records_sql($countduplicatessql);

        // Get the records themselves.
        $getduplicatessql = "SELECT qc.id $fromclause ORDER BY minid";
        $rs = $DB->get_recordset_sql($getduplicatessql);

        // For each duplicate, update the stamp to a new random value.
        $i = 0;
        $pbar = new progress_bar('updatequestioncategorystamp', 500, true);
        foreach ($rs as $record) {
            // Generate a new, unique stamp and update the record.
            do {
                $newstamp = make_unique_id_code();
            } while (isset($usedstamps[$newstamp]));
            $usedstamps[$newstamp] = 1;
            $DB->set_field('question_categories', 'stamp', $newstamp, array('id' => $record->id));

            // Update progress.
            $i++;
            $pbar->update($i, $total, "Updating duplicate question category stamp - $i/$total.");
        }
        unset($usedstamps);

        // The uniqueness of each (contextid, stamp) pair is now guaranteed, so add the unique index to stop future duplicates.
        $table = new xmldb_table('question_categories');
        $index = new xmldb_index('contextidstamp', XMLDB_INDEX_UNIQUE, array('contextid', 'stamp'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Savepoint reached.
        upgrade_main_savepoint(true, 2016082200.00);
    }

    if ($oldversion < 2016091900.00) {

        // Removing the themes from core.
        $themes = array('base', 'canvas');

        foreach ($themes as $key => $theme) {
            if (check_dir_exists($CFG->dirroot . '/theme/' . $theme, false)) {
                // Ignore the themes that have been re-downloaded.
                unset($themes[$key]);
            }
        }

        if (!empty($themes)) {
            // Hacky emulation of plugin uninstallation.
            foreach ($themes as $theme) {
                unset_all_config_for_plugin('theme_' . $theme);
            }
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016091900.00);
    }

    if ($oldversion < 2016091900.02) {

        // Define index attemptstepid-name (unique) to be dropped from question_attempt_step_data.
        $table = new xmldb_table('question_attempt_step_data');
        $index = new xmldb_index('attemptstepid-name', XMLDB_INDEX_UNIQUE, array('attemptstepid', 'name'));

        // Conditionally launch drop index attemptstepid-name.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016091900.02);
    }

    if ($oldversion < 2016100300.00) {
        unset_config('enablecssoptimiser');

        upgrade_main_savepoint(true, 2016100300.00);
    }

    if ($oldversion < 2016100501.00) {

        // Define field enddate to be added to course.
        $table = new xmldb_table('course');
        $field = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'startdate');

        // Conditionally launch add field enddate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016100501.00);
    }

    if ($oldversion < 2016101100.00) {
        // Define field component to be added to message.
        $table = new xmldb_table('message');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'timeusertodeleted');

        // Conditionally launch add field component.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field eventtype to be added to message.
        $field = new xmldb_field('eventtype', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'component');

        // Conditionally launch add field eventtype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016101100.00);
    }


    if ($oldversion < 2016101101.00) {
        // Define field component to be added to message_read.
        $table = new xmldb_table('message_read');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'timeusertodeleted');

        // Conditionally launch add field component.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field eventtype to be added to message_read.
        $field = new xmldb_field('eventtype', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'component');

        // Conditionally launch add field eventtype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016101101.00);
    }

    if ($oldversion < 2016101401.00) {
        // Clean up repository_alfresco config unless plugin has been manually installed.
        if (!file_exists($CFG->dirroot . '/repository/alfresco/lib.php')) {
            // Remove capabilities.
            capabilities_cleanup('repository_alfresco');
            // Clean config.
            unset_all_config_for_plugin('repository_alfresco');
        }

        // Savepoint reached.
        upgrade_main_savepoint(true, 2016101401.00);
    }

    if ($oldversion < 2016101401.02) {
        $table = new xmldb_table('external_tokens');
        $field = new xmldb_field('privatetoken', XMLDB_TYPE_CHAR, '64', null, null, null, null);

        // Conditionally add privatetoken field to the external_tokens table.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016101401.02);
    }

    if ($oldversion < 2016110202.00) {

        // Force uninstall of deleted authentication plugin.
        if (!file_exists("$CFG->dirroot/auth/radius")) {
            // Leave settings inplace if there are radius users.
            if (!$DB->record_exists('user', array('auth' => 'radius', 'deleted' => 0))) {
                // Remove all other associated config.
                unset_all_config_for_plugin('auth/radius');
                // The version number for radius is in this format.
                unset_all_config_for_plugin('auth_radius');
            }
        }
        upgrade_main_savepoint(true, 2016110202.00);
    }

    if ($oldversion < 2016110300.00) {
        // Remove unused admin email setting.
        unset_config('emailonlyfromreplyaddress');

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016110300.00);
    }

    if ($oldversion < 2016110500.00) {

        $oldplayers = [
            'vimeo' => null,
            'mp3' => ['.mp3'],
            'html5video' => ['.mov', '.mp4', '.m4v', '.mpeg', '.mpe', '.mpg', '.ogv', '.webm'],
            'flv' => ['.flv', '.f4v'],
            'html5audio' => ['.aac', '.flac', '.mp3', '.m4a', '.oga', '.ogg', '.wav'],
            'youtube' => null,
            'swf' => null,
        ];

        // Convert hardcoded media players to the settings of the new media player plugin type.
        if (get_config('core', 'media_plugins_sortorder') === false) {
            $enabledplugins = [];
            $videoextensions = [];
            $audioextensions = [];
            foreach ($oldplayers as $oldplayer => $extensions) {
                $settingname = 'core_media_enable_'.$oldplayer;
                if (!empty($CFG->$settingname)) {
                    if ($extensions) {
                        // VideoJS will be used for all media files players that were used previously.
                        $enabledplugins['videojs'] = 'videojs';
                        if ($oldplayer === 'mp3' || $oldplayer === 'html5audio') {
                            $audioextensions += array_combine($extensions, $extensions);
                        } else {
                            $videoextensions += array_combine($extensions, $extensions);
                        }
                    } else {
                        // Enable youtube, vimeo and swf.
                        $enabledplugins[$oldplayer] = $oldplayer;
                    }
                }
            }

            set_config('media_plugins_sortorder', join(',', $enabledplugins));

            // Configure VideoJS to match the existing players set up.
            if ($enabledplugins['videojs']) {
                $enabledplugins[] = 'videojs';
                set_config('audioextensions', join(',', $audioextensions), 'media_videojs');
                set_config('videoextensions', join(',', $videoextensions), 'media_videojs');
                $useflash = !empty($CFG->core_media_enable_flv) || !empty($CFG->core_media_enable_mp3);
                set_config('useflash', $useflash, 'media_videojs');
                if (empty($CFG->core_media_enable_youtube)) {
                    // Normally YouTube is enabled in videojs, but if youtube converter was disabled before upgrade
                    // disable it in videojs as well.
                    set_config('youtube', false, 'media_videojs');
                }
            }
        }

        // Unset old settings.
        foreach ($oldplayers as $oldplayer => $extensions) {
            unset_config('core_media_enable_' . $oldplayer);
        }

        // Preset defaults if CORE_MEDIA_VIDEO_WIDTH and CORE_MEDIA_VIDEO_HEIGHT are specified in config.php .
        // After this upgrade step these constants will not be used any more.
        if (defined('CORE_MEDIA_VIDEO_WIDTH')) {
            set_config('media_default_width', CORE_MEDIA_VIDEO_WIDTH);
        }
        if (defined('CORE_MEDIA_VIDEO_HEIGHT')) {
            set_config('media_default_height', CORE_MEDIA_VIDEO_HEIGHT);
        }

        // Savepoint reached.
        upgrade_main_savepoint(true, 2016110500.00);
    }

    if ($oldversion < 2016110600.00) {
        // Define a field 'deletioninprogress' in the 'course_modules' table, to background deletion tasks.
        $table = new xmldb_table('course_modules');
        $field = new xmldb_field('deletioninprogress', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'availability');

        // Conditionally launch add field 'deletioninprogress'.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016110600.00);
    }

    if ($oldversion < 2016112200.01) {

        // Define field requiredbytheme to be added to block_instances.
        $table = new xmldb_table('block_instances');
        $field = new xmldb_field('requiredbytheme', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showinsubcontexts');

        // Conditionally launch add field requiredbytheme.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016112200.01);
    }
    if ($oldversion < 2016112200.02) {

        // Change the existing site level admin and settings blocks to be requiredbytheme which means they won't show in boost.
        $context = context_system::instance();
        $params = array('blockname' => 'settings', 'parentcontextid' => $context->id);
        $DB->set_field('block_instances', 'requiredbytheme', 1, $params);

        $params = array('blockname' => 'navigation', 'parentcontextid' => $context->id);
        $DB->set_field('block_instances', 'requiredbytheme', 1, $params);
        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016112200.02);
    }

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2016122800.00) {
        // Find all roles with the coursecreator archetype.
        $coursecreatorroleids = $DB->get_records('role', array('archetype' => 'coursecreator'), '', 'id');

        $context = context_system::instance();
        $capability = 'moodle/site:configview';

        foreach ($coursecreatorroleids as $roleid => $notused) {

            // Check that the capability has not already been assigned. If it has then it's either already set
            // to allow or specifically set to prohibit or prevent.
            if (!$DB->record_exists('role_capabilities', array('roleid' => $roleid, 'capability' => $capability))) {
                // Assign the capability.
                $cap = new stdClass();
                $cap->contextid    = $context->id;
                $cap->roleid       = $roleid;
                $cap->capability   = $capability;
                $cap->permission   = CAP_ALLOW;
                $cap->timemodified = time();
                $cap->modifierid   = 0;

                $DB->insert_record('role_capabilities', $cap);
            }
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2016122800.00);
    }

    if ($oldversion < 2017020200.01) {

        // Define index useridfrom_timeuserfromdeleted_notification (not unique) to be added to message.
        $table = new xmldb_table('message');
        $index = new xmldb_index('useridfrom_timeuserfromdeleted_notification', XMLDB_INDEX_NOTUNIQUE, array('useridfrom', 'timeuserfromdeleted', 'notification'));

        // Conditionally launch add index useridfrom_timeuserfromdeleted_notification.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index useridto_timeusertodeleted_notification (not unique) to be added to message.
        $index = new xmldb_index('useridto_timeusertodeleted_notification', XMLDB_INDEX_NOTUNIQUE, array('useridto', 'timeusertodeleted', 'notification'));

        // Conditionally launch add index useridto_timeusertodeleted_notification.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('useridto', XMLDB_INDEX_NOTUNIQUE, array('useridto'));

        // Conditionally launch drop index useridto.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017020200.01);
    }

    if ($oldversion < 2017020200.02) {

        // Define index useridfrom_timeuserfromdeleted_notification (not unique) to be added to message_read.
        $table = new xmldb_table('message_read');
        $index = new xmldb_index('useridfrom_timeuserfromdeleted_notification', XMLDB_INDEX_NOTUNIQUE, array('useridfrom', 'timeuserfromdeleted', 'notification'));

        // Conditionally launch add index useridfrom_timeuserfromdeleted_notification.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index useridto_timeusertodeleted_notification (not unique) to be added to message_read.
        $index = new xmldb_index('useridto_timeusertodeleted_notification', XMLDB_INDEX_NOTUNIQUE, array('useridto', 'timeusertodeleted', 'notification'));

        // Conditionally launch add index useridto_timeusertodeleted_notification.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('useridto', XMLDB_INDEX_NOTUNIQUE, array('useridto'));

        // Conditionally launch drop index useridto.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017020200.02);
    }

    if ($oldversion < 2017020901.00) {

        // Delete "orphaned" block positions. Note, the query does not use indexes (because there are none),
        // if it runs too long during upgrade you can comment this line - it will leave orphaned records
        // in the database but they won't bother you.
        upgrade_block_positions();

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017020901.00);
    }

    if ($oldversion < 2017021300.00) {
        unset_config('loginpasswordautocomplete');
        upgrade_main_savepoint(true, 2017021300.00);
    }

    if ($oldversion < 2017021400.00) {
        // Define field visibleoncoursepage to be added to course_modules.
        $table = new xmldb_table('course_modules');
        $field = new xmldb_field('visibleoncoursepage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'visible');

        // Conditionally launch add field visibleoncoursepage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017021400.00);
    }

    if ($oldversion < 2017030700.00) {

        // Define field priority to be added to event.
        $table = new xmldb_table('event');
        $field = new xmldb_field('priority', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'subscriptionid');

        // Conditionally launch add field priority.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017030700.00);
    }

    if ($oldversion < 2017031400.00) {

        // Define table file_conversion to be created.
        $table = new xmldb_table('file_conversion');

        // Adding fields to table file_conversion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sourcefileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetformat', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('statusmessage', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('converter', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('destfileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table file_conversion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sourcefileid', XMLDB_KEY_FOREIGN, array('sourcefileid'), 'files', array('id'));
        $table->add_key('destfileid', XMLDB_KEY_FOREIGN, array('destfileid'), 'files', array('id'));

        // Conditionally launch create table for file_conversion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017031400.00);
    }

    if ($oldversion < 2017040400.00) {

        // If block_course_overview is no longer present, replace with block_myoverview.
        if (!file_exists($CFG->dirroot . '/blocks/course_overview/block_course_overview.php')) {
            $DB->set_field('block_instances', 'blockname', 'myoverview', array('blockname' => 'course_overview'));
        }

        upgrade_main_savepoint(true, 2017040400.00);
    }

    if ($oldversion < 2017040401.00) {

        // If block_course_overview is no longer present, remove it.
        // Note - we do not need to completely remove the block context etc because we
        // have replaced all occurrences of block_course_overview with block_myoverview
        // in the upgrade step above.
        if (!file_exists($CFG->dirroot . '/blocks/course_overview/block_course_overview.php')) {
            // Delete the block from the block table.
            $DB->delete_records('block', array('name' => 'course_overview'));
            // Remove capabilities.
            capabilities_cleanup('block_course_overview');
            // Clean config.
            unset_all_config_for_plugin('block_course_overview');
        }

        upgrade_main_savepoint(true, 2017040401.00);
    }

    if ($oldversion < 2017040402.00) {

        // Define fields to be added to the 'event' table.
        $table = new xmldb_table('event');
        $fieldtype = new xmldb_field('type', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 0, 'instance');
        $fieldtimesort = new xmldb_field('timesort', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'timeduration');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $fieldtype)) {
            $dbman->add_field($table, $fieldtype);
        }

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $fieldtimesort)) {
            $dbman->add_field($table, $fieldtimesort);
        }

        // Now, define the index we will be adding.
        $index = new xmldb_index('type-timesort', XMLDB_INDEX_NOTUNIQUE, array('type', 'timesort'));

        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_main_savepoint(true, 2017040402.00);
    }

    if ($oldversion < 2017040700.01) {

        // Define table oauth2_issuer to be created.
        $table = new xmldb_table('oauth2_issuer');

        // Adding fields to table oauth2_issuer.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('image', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('baseurl', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('clientid', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('clientsecret', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('loginscopes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('loginscopesoffline', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('loginparams', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('loginparamsoffline', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('alloweddomains', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('scopessupported', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('showonloginpage', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table oauth2_issuer.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for oauth2_issuer.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017040700.01);
    }

    if ($oldversion < 2017040700.02) {

        // Define table oauth2_endpoint to be created.
        $table = new xmldb_table('oauth2_endpoint');

        // Adding fields to table oauth2_endpoint.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('issuerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table oauth2_endpoint.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('issuer_id_key', XMLDB_KEY_FOREIGN, array('issuerid'), 'oauth2_issuer', array('id'));

        // Conditionally launch create table for oauth2_endpoint.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017040700.02);
    }

    if ($oldversion < 2017040700.03) {

        // Define table oauth2_system_account to be created.
        $table = new xmldb_table('oauth2_system_account');

        // Adding fields to table oauth2_system_account.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('issuerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('refreshtoken', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('grantedscopes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table oauth2_system_account.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('issueridkey', XMLDB_KEY_FOREIGN_UNIQUE, array('issuerid'), 'oauth2_issuer', array('id'));

        // Conditionally launch create table for oauth2_system_account.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017040700.03);
    }

    if ($oldversion < 2017040700.04) {

        // Define table oauth2_user_field_mapping to be created.
        $table = new xmldb_table('oauth2_user_field_mapping');

        // Adding fields to table oauth2_user_field_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('issuerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('externalfield', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('internalfield', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table oauth2_user_field_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('issuerkey', XMLDB_KEY_FOREIGN, array('issuerid'), 'oauth2_issuer', array('id'));
        $table->add_key('uniqinternal', XMLDB_KEY_UNIQUE, array('issuerid', 'internalfield'));

        // Conditionally launch create table for oauth2_user_field_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017040700.04);
    }

    if ($oldversion < 2017041801.00) {

        // Define table course_completion_defaults to be created.
        $table = new xmldb_table('course_completion_defaults');

        // Adding fields to table course_completion_defaults.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('completion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('completionview', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('completionusegrade', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('completionexpected', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('customrules', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table course_completion_defaults.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('module', XMLDB_KEY_FOREIGN, array('module'), 'modules', array('id'));
        $table->add_key('course', XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

        // Adding indexes to table course_completion_defaults.
        $table->add_index('coursemodule', XMLDB_INDEX_UNIQUE, array('course', 'module'));

        // Conditionally launch create table for course_completion_defaults.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_main_savepoint(true, 2017041801.00);
    }

    if ($oldversion < 2017050500.01) {
        // Get the list of parent event IDs.
        $sql = "SELECT DISTINCT repeatid
                           FROM {event}
                          WHERE repeatid <> 0";
        $parentids = array_keys($DB->get_records_sql($sql));
        // Check if there are repeating events we need to process.
        if (!empty($parentids)) {
            // The repeat IDs of parent events should match their own ID.
            // So we need to update parent events that have non-matching IDs and repeat IDs.
            list($insql, $params) = $DB->get_in_or_equal($parentids);
            $updatesql = "UPDATE {event}
                             SET repeatid = id
                           WHERE id <> repeatid
                                 AND id $insql";
            $DB->execute($updatesql, $params);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017050500.01);
    }

    if ($oldversion < 2017050500.02) {
        // MDL-58684:
        // Remove all portfolio_tempdata records as these may contain serialized \file_system type objects, which are now unable to
        // be unserialized because of changes to the file storage API made in MDL-46375. Portfolio now stores an id reference to
        // files instead of the object.
        // These records are normally removed after a successful export, however, can be left behind if the user abandons the
        // export attempt (a stale record). Additionally, each stale record cannot be reused and is normally cleaned up by the cron
        // task core\task\portfolio_cron_task. Since the cron task tries to unserialize them, and generates a warning, we'll remove
        // all records here.
        $DB->delete_records_select('portfolio_tempdata', 'id > ?', [0]);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017050500.02);
    }

    if ($oldversion < 2017050900.01) {
        // Create adhoc task for upgrading of existing calendar events.
        $record = new \stdClass();
        $record->classname = '\core\task\refresh_mod_calendar_events_task';
        $record->component = 'core';

        // Next run time based from nextruntime computation in \core\task\manager::queue_adhoc_task().
        $nextruntime = time() - 1;
        $record->nextruntime = $nextruntime;
        $DB->insert_record('task_adhoc', $record);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017050900.01);
    }

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017061201.00) {
        $table = new xmldb_table('course_sections');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'availability');

        // Define a field 'timemodified' in the 'course_sections' table.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_main_savepoint(true, 2017061201.00);
    }

    if ($oldversion < 2017061301.00) {
        // Check if the value of 'navcourselimit' is set to the old default value, if so, change it to the new default.
        if ($CFG->navcourselimit == 20) {
            set_config('navcourselimit', 10);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017061301.00);
    }

    if ($oldversion < 2017071000.00) {

        // Define field requireconfirmation to be added to oauth2_issuer.
        $table = new xmldb_table('oauth2_issuer');
        $field = new xmldb_field('requireconfirmation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'sortorder');

        // Conditionally launch add field requireconfirmation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017071000.00);
    }

    if ($oldversion < 2017071001.00) {

        // Define field timemodified to be added to block_instances.
        $table = new xmldb_table('block_instances');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null,
                null, null, 'configdata');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Set field to current time.
            $DB->set_field('block_instances', 'timemodified', time());

            // Changing nullability of field timemodified on table block_instances to not null.
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                    null, null, 'configdata');

            // Launch change of nullability for field timemodified.
            $dbman->change_field_notnull($table, $field);

            // Define index timemodified (not unique) to be added to block_instances.
            $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

            // Conditionally launch add index timemodified.
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        // Define field timecreated to be added to block_instances.
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null,
                null, null, 'configdata');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Set field to current time.
            $DB->set_field('block_instances', 'timecreated', time());

            // Changing nullability of field timecreated on table block_instances to not null.
            $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                    null, null, 'configdata');

            // Launch change of nullability for field timecreated.
            $dbman->change_field_notnull($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017071001.00);
    }

    if ($oldversion < 2017071100.00 ) {
        // Clean old upgrade setting not used anymore.
        unset_config('upgrade_minmaxgradestepignored');
        upgrade_main_savepoint(true, 2017071100.00);
    }

    if ($oldversion < 2017072000.02) {

        // Define table analytics_models to be created.
        $table = new xmldb_table('analytics_models');

        // Adding fields to table analytics_models.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('trained', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('target', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('indicators', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesplitting', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table analytics_models.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_models.
        $table->add_index('enabledandtrained', XMLDB_INDEX_NOTUNIQUE, array('enabled', 'trained'));

        // Conditionally launch create table for analytics_models.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table analytics_models_log to be created.
        $table = new xmldb_table('analytics_models_log');

        // Adding fields to table analytics_models_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('target', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('indicators', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesplitting', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('score', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('info', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('dir', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table analytics_models_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_models_log.
        $table->add_index('modelid', XMLDB_INDEX_NOTUNIQUE, array('modelid'));

        // Conditionally launch create table for analytics_models_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table analytics_predictions to be created.
        $table = new xmldb_table('analytics_predictions');

        // Adding fields to table analytics_predictions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sampleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rangeindex', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('prediction', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('predictionscore', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('calculations', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table analytics_predictions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_predictions.
        $table->add_index('modelidandcontextid', XMLDB_INDEX_NOTUNIQUE, array('modelid', 'contextid'));

        // Conditionally launch create table for analytics_predictions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table analytics_train_samples to be created.
        $table = new xmldb_table('analytics_train_samples');

        // Adding fields to table analytics_train_samples.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('analysableid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesplitting', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sampleids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table analytics_train_samples.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_train_samples.
        $table->add_index('modelidandanalysableidandtimesplitting', XMLDB_INDEX_NOTUNIQUE,
            array('modelid', 'analysableid', 'timesplitting'));

        // Conditionally launch create table for analytics_train_samples.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table analytics_predict_ranges to be created.
        $table = new xmldb_table('analytics_predict_ranges');

        // Adding fields to table analytics_predict_ranges.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('analysableid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesplitting', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rangeindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table analytics_predict_ranges.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_predict_ranges.
        $table->add_index('modelidandanalysableidandtimesplitting', XMLDB_INDEX_NOTUNIQUE,
            array('modelid', 'analysableid', 'timesplitting'));

        // Conditionally launch create table for analytics_predict_ranges.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table analytics_used_files to be created.
        $table = new xmldb_table('analytics_used_files');

        // Adding fields to table analytics_used_files.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table analytics_used_files.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_used_files.
        $table->add_index('modelidandfileidandaction', XMLDB_INDEX_NOTUNIQUE, array('modelid', 'fileid', 'action'));

        // Conditionally launch create table for analytics_used_files.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $now = time();
        $admin = get_admin();

        $targetname = '\core\analytics\target\course_dropout';
        if (!$DB->record_exists('analytics_models', array('target' => $targetname))) {
            // We can not use API calls to create the built-in models.
            $modelobj = new stdClass();
            $modelobj->target = $targetname;
            $modelobj->indicators = json_encode(array(
                '\mod_assign\analytics\indicator\cognitive_depth',
                '\mod_assign\analytics\indicator\social_breadth',
                '\mod_book\analytics\indicator\cognitive_depth',
                '\mod_book\analytics\indicator\social_breadth',
                '\mod_chat\analytics\indicator\cognitive_depth',
                '\mod_chat\analytics\indicator\social_breadth',
                '\mod_choice\analytics\indicator\cognitive_depth',
                '\mod_choice\analytics\indicator\social_breadth',
                '\mod_data\analytics\indicator\cognitive_depth',
                '\mod_data\analytics\indicator\social_breadth',
                '\mod_feedback\analytics\indicator\cognitive_depth',
                '\mod_feedback\analytics\indicator\social_breadth',
                '\mod_folder\analytics\indicator\cognitive_depth',
                '\mod_folder\analytics\indicator\social_breadth',
                '\mod_forum\analytics\indicator\cognitive_depth',
                '\mod_forum\analytics\indicator\social_breadth',
                '\mod_glossary\analytics\indicator\cognitive_depth',
                '\mod_glossary\analytics\indicator\social_breadth',
                '\mod_imscp\analytics\indicator\cognitive_depth',
                '\mod_imscp\analytics\indicator\social_breadth',
                '\mod_label\analytics\indicator\cognitive_depth',
                '\mod_label\analytics\indicator\social_breadth',
                '\mod_lesson\analytics\indicator\cognitive_depth',
                '\mod_lesson\analytics\indicator\social_breadth',
                '\mod_lti\analytics\indicator\cognitive_depth',
                '\mod_lti\analytics\indicator\social_breadth',
                '\mod_page\analytics\indicator\cognitive_depth',
                '\mod_page\analytics\indicator\social_breadth',
                '\mod_quiz\analytics\indicator\cognitive_depth',
                '\mod_quiz\analytics\indicator\social_breadth',
                '\mod_resource\analytics\indicator\cognitive_depth',
                '\mod_resource\analytics\indicator\social_breadth',
                '\mod_scorm\analytics\indicator\cognitive_depth',
                '\mod_scorm\analytics\indicator\social_breadth',
                '\mod_survey\analytics\indicator\cognitive_depth',
                '\mod_survey\analytics\indicator\social_breadth',
                '\mod_url\analytics\indicator\cognitive_depth',
                '\mod_url\analytics\indicator\social_breadth',
                '\mod_wiki\analytics\indicator\cognitive_depth',
                '\mod_wiki\analytics\indicator\social_breadth',
                '\mod_workshop\analytics\indicator\cognitive_depth',
                '\mod_workshop\analytics\indicator\social_breadth',
            ));
            $modelobj->version = $now;
            $modelobj->timecreated = $now;
            $modelobj->timemodified = $now;
            $modelobj->usermodified = $admin->id;
            $DB->insert_record('analytics_models', $modelobj);
        }

        $targetname = '\core\analytics\target\no_teaching';
        if (!$DB->record_exists('analytics_models', array('target' => $targetname))) {
            $modelobj = new stdClass();
            $modelobj->enabled = 1;
            $modelobj->trained = 1;
            $modelobj->target = $targetname;
            $modelobj->indicators = json_encode(array('\core_course\analytics\indicator\no_teacher'));
            $modelobj->timesplitting = '\core\analytics\time_splitting\single_range';
            $modelobj->version = $now;
            $modelobj->timecreated = $now;
            $modelobj->timemodified = $now;
            $modelobj->usermodified = $admin->id;
            $DB->insert_record('analytics_models', $modelobj);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017072000.02);
    }

    if ($oldversion < 2017072700.01) {
        // Changing nullability of field email on table oauth2_system_account to null.
        $table = new xmldb_table('oauth2_system_account');
        $field = new xmldb_field('email', XMLDB_TYPE_TEXT, null, null, null, null, null, 'grantedscopes');

        // Launch change of nullability for field email.
        $dbman->change_field_notnull($table, $field);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017072700.01);
    }

    if ($oldversion < 2017072700.02) {

        // If the site was previously registered with http://hub.moodle.org change the registration to
        // point to https://moodle.net - this is the correct hub address using https protocol.
        $oldhuburl = "http://hub.moodle.org";
        $newhuburl = "https://moodle.net";
        $cleanoldhuburl = preg_replace('/[^A-Za-z0-9_-]/i', '', $oldhuburl);
        $cleannewhuburl = preg_replace('/[^A-Za-z0-9_-]/i', '', $newhuburl);

        // Update existing registration.
        $DB->execute("UPDATE {registration_hubs} SET hubname = ?, huburl = ? WHERE huburl = ?",
            ['Moodle.net', $newhuburl, $oldhuburl]);

        // Update settings of existing registration.
        $sqlnamelike = $DB->sql_like('name', '?');
        $entries = $DB->get_records_sql("SELECT * FROM {config_plugins} where plugin=? and " . $sqlnamelike,
            ['hub', '%' . $DB->sql_like_escape('_' . $cleanoldhuburl)]);
        foreach ($entries as $entry) {
            $newname = substr($entry->name, 0, -strlen($cleanoldhuburl)) . $cleannewhuburl;
            try {
                $DB->update_record('config_plugins', ['id' => $entry->id, 'name' => $newname]);
            } catch (dml_exception $e) {
                // Entry with new name already exists, remove the one with an old name.
                $DB->delete_records('config_plugins', ['id' => $entry->id]);
            }
        }

        // Update published courses.
        $DB->execute('UPDATE {course_published} SET huburl = ? WHERE huburl = ?', [$newhuburl, $oldhuburl]);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017072700.02);
    }

    if ($oldversion < 2017080700.01) {

        // Get the table by its previous name.
        $table = new xmldb_table('analytics_predict_ranges');
        if ($dbman->table_exists($table)) {

            // We can only accept this because we are in master.
            $DB->delete_records('analytics_predictions');
            $DB->delete_records('analytics_used_files', array('action' => 'predicted'));
            $DB->delete_records('analytics_predict_ranges');

            // Define field sampleids to be added to analytics_predict_ranges (renamed below to analytics_predict_samples).
            $field = new xmldb_field('sampleids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'rangeindex');

            // Conditionally launch add field sampleids.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Define field timemodified to be added to analytics_predict_ranges (renamed below to analytics_predict_samples).
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');

            // Conditionally launch add field timemodified.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Rename the table to its new name.
            $dbman->rename_table($table, 'analytics_predict_samples');
        }

        $table = new xmldb_table('analytics_predict_samples');

        $index = new xmldb_index('modelidandanalysableidandtimesplitting', XMLDB_INDEX_NOTUNIQUE,
            array('modelid', 'analysableid', 'timesplitting'));

        // Conditionally launch drop index.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('modelidandanalysableidandtimesplittingandrangeindex', XMLDB_INDEX_NOTUNIQUE,
            array('modelid', 'analysableid', 'timesplitting', 'rangeindex'));

        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017080700.01);
    }

    if ($oldversion < 2017082200.00) {
        $plugins = ['radius', 'fc', 'nntp', 'pam', 'pop3', 'imap'];

        foreach ($plugins as $plugin) {
            // Check to see if the plugin exists on disk.
            // If it does not, remove the config for it.
            if (!file_exists($CFG->dirroot . "/auth/{$plugin}/auth.php")) {
                // Clean config.
                unset_all_config_for_plugin("auth_{$plugin}");
            }
        }
        upgrade_main_savepoint(true, 2017082200.00);
    }

    if ($oldversion < 2017082200.01) {

        // Define table analytics_indicator_calc to be created.
        $table = new xmldb_table('analytics_indicator_calc');

        // Adding fields to table analytics_indicator_calc.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sampleorigin', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sampleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('indicator', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table analytics_indicator_calc.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table analytics_indicator_calc.
        $table->add_index('starttime-endtime-contextid', XMLDB_INDEX_NOTUNIQUE, array('starttime', 'endtime', 'contextid'));

        // Conditionally launch create table for analytics_indicator_calc.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017082200.01);
    }

    if ($oldversion < 2017082300.01) {

        // This script in included in each major version upgrade process so make sure we don't run it twice.
        if (empty($CFG->linkcoursesectionsupgradescriptwasrun)) {
            // Check if the site is using a boost-based theme.
            // If value of 'linkcoursesections' is set to the old default value, change it to the new default.
            if (upgrade_theme_is_from_family('boost', $CFG->theme)) {
                set_config('linkcoursesections', 1);
            }
            set_config('linkcoursesectionsupgradescriptwasrun', 1);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017082300.01);
    }

    if ($oldversion < 2017082500.00) {
        // Handle FKs for the table 'analytics_models_log'.
        $table = new xmldb_table('analytics_models_log');

        // Remove the existing index before adding FK (which creates an index).
        $index = new xmldb_index('modelid', XMLDB_INDEX_NOTUNIQUE, array('modelid'));

        // Conditionally launch drop index.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Now, add the FK.
        $key = new xmldb_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));
        $dbman->add_key($table, $key);

        // Handle FKs for the table 'analytics_predictions'.
        $table = new xmldb_table('analytics_predictions');
        $key = new xmldb_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));
        $dbman->add_key($table, $key);

        $key = new xmldb_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));
        $dbman->add_key($table, $key);

        // Handle FKs for the table 'analytics_train_samples'.
        $table = new xmldb_table('analytics_train_samples');
        $key = new xmldb_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));
        $dbman->add_key($table, $key);

        $key = new xmldb_key('fileid', XMLDB_KEY_FOREIGN, array('fileid'), 'files', array('id'));
        $dbman->add_key($table, $key);

        // Handle FKs for the table 'analytics_predict_samples'.
        $table = new xmldb_table('analytics_predict_samples');
        $key = new xmldb_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));
        $dbman->add_key($table, $key);

        // Handle FKs for the table 'analytics_used_files'.
        $table = new xmldb_table('analytics_used_files');
        $key = new xmldb_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));
        $dbman->add_key($table, $key);

        $key = new xmldb_key('fileid', XMLDB_KEY_FOREIGN, array('fileid'), 'files', array('id'));
        $dbman->add_key($table, $key);

        // Handle FKs for the table 'analytics_indicator_calc'.
        $table = new xmldb_table('analytics_indicator_calc');
        $key = new xmldb_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));
        $dbman->add_key($table, $key);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017082500.00);
    }

    if ($oldversion < 2017082800.00) {

        // Changing type of field prediction on table analytics_predictions to number.
        $table = new xmldb_table('analytics_predictions');
        $field = new xmldb_field('prediction', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null, 'rangeindex');

        // Launch change of type for field prediction.
        $dbman->change_field_type($table, $field);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017082800.00);
    }

    if ($oldversion < 2017090700.01) {

        // Define table analytics_prediction_actions to be created.
        $table = new xmldb_table('analytics_prediction_actions');

        // Adding fields to table analytics_prediction_actions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('predictionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('actionname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table analytics_prediction_actions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('predictionid', XMLDB_KEY_FOREIGN, array('predictionid'), 'analytics_predictions', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Adding indexes to table analytics_prediction_actions.
        $table->add_index('predictionidanduseridandactionname', XMLDB_INDEX_NOTUNIQUE,
            array('predictionid', 'userid', 'actionname'));

        // Conditionally launch create table for analytics_prediction_actions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017090700.01);
    }

    if ($oldversion < 2017091200.00) {
        // Force all messages to be reindexed.
        set_config('core_message_message_sent_lastindexrun', '0', 'core_search');
        set_config('core_message_message_received_lastindexrun', '0', 'core_search');

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017091200.00);
    }

    if ($oldversion < 2017091201.00) {
        // Define field userid to be added to task_adhoc.
        $table = new xmldb_table('task_adhoc');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'customdata');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('useriduser', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Launch add key userid_user.
        $dbman->add_key($table, $key);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017091201.00);
    }

    if ($oldversion < 2017092201.00) {

        // Remove duplicate registrations.
        $newhuburl = "https://moodle.net";
        $registrations = $DB->get_records('registration_hubs', ['huburl' => $newhuburl], 'confirmed DESC, id ASC');
        if (count($registrations) > 1) {
            $reg = array_shift($registrations);
            $DB->delete_records_select('registration_hubs', 'huburl = ? AND id <> ?', [$newhuburl, $reg->id]);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017092201.00);
    }

    if ($oldversion < 2017092202.00) {

        if (!file_exists($CFG->dirroot . '/blocks/messages/block_messages.php')) {

            // Delete instances.
            $instances = $DB->get_records_list('block_instances', 'blockname', ['messages']);
            $instanceids = array_keys($instances);

            if (!empty($instanceids)) {
                $DB->delete_records_list('block_positions', 'blockinstanceid', $instanceids);
                $DB->delete_records_list('block_instances', 'id', $instanceids);
                list($sql, $params) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
                $params['contextlevel'] = CONTEXT_BLOCK;
                $DB->delete_records_select('context', "contextlevel=:contextlevel AND instanceid " . $sql, $params);

                $preferences = array();
                foreach ($instances as $instanceid => $instance) {
                    $preferences[] = 'block' . $instanceid . 'hidden';
                    $preferences[] = 'docked_block_instance_' . $instanceid;
                }
                $DB->delete_records_list('user_preferences', 'name', $preferences);
            }

            // Delete the block from the block table.
            $DB->delete_records('block', array('name' => 'messages'));

            // Remove capabilities.
            capabilities_cleanup('block_messages');

            // Clean config.
            unset_all_config_for_plugin('block_messages');
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017092202.00);
    }

    if ($oldversion < 2017092700.00) {

        // Rename several fields in registration data to match the names of the properties that are sent to moodle.net.
        $renames = [
            'site_address_httpsmoodlenet' => 'site_street_httpsmoodlenet',
            'site_region_httpsmoodlenet' => 'site_regioncode_httpsmoodlenet',
            'site_country_httpsmoodlenet' => 'site_countrycode_httpsmoodlenet'];
        foreach ($renames as $oldparamname => $newparamname) {
            try {
                $DB->execute("UPDATE {config_plugins} SET name = ? WHERE name = ? AND plugin = ?",
                    [$newparamname, $oldparamname, 'hub']);
            } catch (dml_exception $e) {
                // Exception can happen if the config value with the new name already exists, ignore it and move on.
            }
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017092700.00);
    }

    if ($oldversion < 2017092900.00) {
        // Define field categoryid to be added to event.
        $table = new xmldb_table('event');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'format');

        // Conditionally launch add field categoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the categoryid key.
        $key = new xmldb_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'course_categories', array('id'));
        $dbman->add_key($table, $key);

        // Add a new index for groupid/courseid/categoryid/visible/userid.
        // Do this before we remove the old index.
        $index = new xmldb_index('groupid-courseid-categoryid-visible-userid', XMLDB_INDEX_NOTUNIQUE, array('groupid', 'courseid', 'categoryid', 'visible', 'userid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Drop the old index.
        $index = new xmldb_index('groupid-courseid-visible-userid', XMLDB_INDEX_NOTUNIQUE, array('groupid', 'courseid', 'visible', 'userid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017092900.00);
    }

    if ($oldversion < 2017100900.00) {
        // Add index on time modified to grade_outcomes_history, grade_categories_history,
        // grade_items_history, and scale_history.
        $table = new xmldb_table('grade_outcomes_history');
        $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('grade_items_history');
        $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('grade_categories_history');
        $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('scale_history');
        $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017100900.00);
    }

    if ($oldversion < 2017101000.00) {

        // Define table analytics_used_analysables to be created.
        $table = new xmldb_table('analytics_used_analysables');

        // Adding fields to table analytics_used_analysables.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('analysableid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeanalysed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table analytics_used_analysables.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('modelid', XMLDB_KEY_FOREIGN, array('modelid'), 'analytics_models', array('id'));

        // Adding indexes to table analytics_used_analysables.
        $table->add_index('modelid-action', XMLDB_INDEX_NOTUNIQUE, array('modelid', 'action'));

        // Conditionally launch create table for analytics_used_analysables.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101000.00);
    }

    if ($oldversion < 2017101000.01) {
        // Define field override to be added to course_modules_completion.
        $table = new xmldb_table('course_modules_completion');
        $field = new xmldb_field('overrideby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'viewed');

        // Conditionally launch add field override.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101000.01);
    }

    if ($oldversion < 2017101000.02) {
        // Define field 'timestart' to be added to 'analytics_predictions'.
        $table = new xmldb_table('analytics_predictions');
        $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field 'timestart'.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field 'timeend' to be added to 'analytics_predictions'.
        $field = new xmldb_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timestart');

        // Conditionally launch add field 'timeend'.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101000.02);
    }

    if ($oldversion < 2017101200.00) {
        // Define table search_index_requests to be created.
        $table = new xmldb_table('search_index_requests');

        // Adding fields to table search_index_requests.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('searcharea', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timerequested', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('partialarea', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('partialtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table search_index_requests.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));

        // Conditionally launch create table for search_index_requests.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101200.00);
    }

    // Index modification upgrade step.
    if ($oldversion < 2017101300.01) {

        $table = new xmldb_table('analytics_used_files');

        // Define index modelidandfileidandaction (not unique) to be dropped form analytics_used_files.
        $index = new xmldb_index('modelidandfileidandaction', XMLDB_INDEX_NOTUNIQUE, array('modelid', 'fileid', 'action'));

        // Conditionally launch drop index modelidandfileidandaction.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index modelidandactionandfileid (not unique) to be dropped form analytics_used_files.
        $index = new xmldb_index('modelidandactionandfileid', XMLDB_INDEX_NOTUNIQUE, array('modelid', 'action', 'fileid'));

        // Conditionally launch add index modelidandactionandfileid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101300.01);
    }

    if ($oldversion < 2017101900.01) {

        $fs = get_file_storage();
        $models = $DB->get_records('analytics_models');
        foreach ($models as $model) {
            $files = $fs->get_directory_files(\context_system::instance()->id, 'analytics', 'unlabelled', $model->id,
                '/analysable/', true, true);
            foreach ($files as $file) {
                $file->delete();
            }
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101900.01);
    }

    if ($oldversion < 2017101900.02) {
        // Create adhoc task for upgrading of existing calendar events.
        $record = new \stdClass();
        $record->classname = '\core\task\refresh_mod_calendar_events_task';
        $record->component = 'core';

        // Next run time based from nextruntime computation in \core\task\manager::queue_adhoc_task().
        $nextruntime = time() - 1;
        $record->nextruntime = $nextruntime;
        $DB->insert_record('task_adhoc', $record);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017101900.02);
    }

    if ($oldversion < 2017102100.01) {
        // We will need to force them onto ssl if loginhttps is set.
        if (!empty($CFG->loginhttps)) {
            set_config('overridetossl', 1);
        }
        // Loginhttps should no longer be set.
        unset_config('loginhttps');

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017102100.01);
    }

    if ($oldversion < 2017110300.01) {

        // Define field categoryid to be added to event_subscriptions.
        $table = new xmldb_table('event_subscriptions');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'url');

        // Conditionally launch add field categoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017110300.01);
    }

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017111300.02) {

        // Define field basicauth to be added to oauth2_issuer.
        $table = new xmldb_table('oauth2_issuer');
        $field = new xmldb_field('basicauth', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'showonloginpage');

        // Conditionally launch add field basicauth.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017111300.02);
    }

    if ($oldversion < 2017111301.08) {

        // Fix old block configurations that use the deprecated (and now removed) object class.
        upgrade_fix_block_instance_configuration();

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017111301.08);
    }

    if ($oldversion < 2017111302.12) {
        // Update default digital age consent map according to the current legislation on each country.
        $ageofdigitalconsentmap = implode(PHP_EOL, [
            '*, 16',
            'AT, 14',
            'ES, 14',
            'US, 13'
        ]);
        set_config('agedigitalconsentmap', $ageofdigitalconsentmap);

        // Main savepoint reached.
        upgrade_main_savepoint(true, 2017111302.12);
    }

    return true;
}
