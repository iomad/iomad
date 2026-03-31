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
 * This file keeps track of upgrades to the iomad_learningpath block
 *
 * @package block_iomad_learningpath
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/my/lib.php");
require_once("{$CFG->libdir}/db/upgradelib.php");

/**
 * Upgrade code for the IOMAD Learningpath block.
 *
 * @param int $oldversion
 */
function xmldb_block_iomad_learningpath_upgrade($oldversion) {
    global $DB, $CFG, $OUTPUT;

    // IOMAD 4.1+ upgrade steps.

    $dbman = $DB->get_manager();

    if ($oldversion < 2024082700) {
        $DB->delete_records('block_instances', ['blockname' => 'iomad_learningpath']);

        // Add new instance to the /my/courses.php page.
        $subpagepattern = $DB->get_record('my_pages', [
            'userid' => null,
            'name' => MY_PAGE_COURSES,
            'private' => MY_PAGE_PUBLIC,
        ], 'id', IGNORE_MULTIPLE)->id;

        $blockname = 'iomad_learningpath';
        $pagetypepattern = 'my-index';

        $blockparams = [
            'blockname' => $blockname,
            'pagetypepattern' => $pagetypepattern,
            'subpagepattern' => $subpagepattern,
        ];

        // See if this block already somehow exists, it should not but who knows.
        if (!$DB->record_exists('block_instances', $blockparams)) {
            $page = new moodle_page();
            $page->set_context(context_system::instance());
            // Add the block to the default /my/courses.
            $page->blocks->add_region('content');
            $page->blocks->add_block($blockname, 'content', 0, false, $pagetypepattern, $subpagepattern);
        }

        upgrade_block_set_my_user_parent_context('iomad_learningpath', '__default', 'my-index');

        upgrade_block_savepoint(true, 2024082700, 'iomad_learningpath', false);
    }

    if ($oldversion < 2026010500) {
        mtrace("");
        mtrace("Moving local/iomad_learningpath plugin code to blocks/iomad_learningpath");

        // Set the list of capabilities we are changing from and to.
        $capabilites = [
            'local/iomad_learningpath:manage' => 'block/iomad_learningpath:manage',
            'local/iomad_learningpath:view' => 'block/iomad_learningpath:view_admin',
            'local/iomad_learningpath:assign' => 'block/iomad_learningpath:assign',
        ];

        // Update all of the capabilities for local/iomad_learningpaths to block/iomad_learningpaths.
        foreach ($capabilites as $old => $new) {
            $DB->set_field('role_capabilities', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_restriction', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_templates_caps', 'capability', $new, ['capability' => $old]);
        }

        mtrace("");
        mtrace("Uninstalling the old plugins.");
        $oldplugins = [
            'local_iomad_learningpath',
        ];
        $pluginman = core_plugin_manager::instance();

        foreach ($oldplugins as $plugin) {
            if ($pluginman->can_uninstall_plugin($plugin)) {
                mtrace('Uninstalling: ' . $plugin);
                $progress = new progress_trace_buffer(new text_progress_trace(), true);
                $pluginman->uninstall_plugin($plugin, $progress);
                $progress->finished();
                mtrace($progress->get_buffer());
            } else {
                mtrace('Can not be uninstalled: ' . $plugin);
            }
        }
        upgrade_block_savepoint(true, 2026010500, 'iomad_learningpath', false);
    }

    if ($oldversion < 2026022700) {

        // Competency_templatelearnpath table restructure.
        $table = new xmldb_table('competency_templatelearnpath');

        // Define index comptemp_temlp_uix (unique) to be dropped form competency_templatelearnpath.
        $index = new xmldb_index('comptemp_temlp_uix', XMLDB_INDEX_UNIQUE, ['templateid', 'learningpathid']);

        // Conditionally launch drop index comptemp_temlp_uix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index comptemp_temlp2 (not unique) to be dropped form competency_templatelearnpath.
        $index = new xmldb_index('comptemp_temlp2', XMLDB_INDEX_NOTUNIQUE, ['templateid']);

        // Conditionally launch drop index comptemp_temlp2.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index comptemp_uselp2 (not unique) to be dropped form competency_templatelearnpath.
        $index = new xmldb_index('comptemp_uselp2', XMLDB_INDEX_NOTUNIQUE, ['usermodified']);

        // Conditionally launch drop index comptemp_uselp2.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field learningpathid on table block_iomad_learningpath_competency_templates to pathid.
        $field = new xmldb_field('learningpathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'templateid');

        // Launch rename field learningpathid.
        $dbman->rename_field($table, $field, 'pathid');

        // Define index templateid-learningpathid (unique) to be added to competency_templatelearnpath.
        $index = new xmldb_index('templateid-pathid', XMLDB_INDEX_UNIQUE, ['templateid', 'pathid']);

        // Conditionally launch add index templateid-learningpathid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index templateid (not unique) to be added to competency_templatelearnpath.
        $index = new xmldb_index('templateid', XMLDB_INDEX_NOTUNIQUE, ['templateid']);

        // Conditionally launch add index templateid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index usermodified (not unique) to be added to competency_templatelearnpath.
        $index = new xmldb_index('usermodified', XMLDB_INDEX_NOTUNIQUE, ['usermodified']);

        // Conditionally launch add index usermodified.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to block_iomad_learningpath_competency_templates.
        $dbman->rename_table($table, 'block_iomad_learningpath_competency_templates');

        // Iomad_learningpathuser table restructure.
        $table = new xmldb_table('iomad_learningpathuser');

        // Define key userid (foreign) to be dropped form iomad_learningpathuser.
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define index ix_pa (not unique) to be dropped form iomad_learningpathuser.
        $index = new xmldb_index('ix_pa', XMLDB_INDEX_NOTUNIQUE, ['pathid']);

        // Conditionally launch drop index ix_pa.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index pathid (not unique) to be added to iomad_learningpathuser.
        $index = new xmldb_index('pathid', XMLDB_INDEX_NOTUNIQUE, ['pathid']);

        // Conditionally launch add index pathid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key fk_userid (foreign) to be added to iomad_learningpathuser.
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Launch rename table to block_iomad_learningpath_users.
        $dbman->rename_table($table, 'block_iomad_learningpath_users');

        // Iomad_learningpathcourse table restructure.
        $table = new xmldb_table('iomad_learningpathcourse');

        // Define index ix_pa (not unique) to be dropped form iomad_learningpathcourse.
        $index = new xmldb_index('ix_pa', XMLDB_INDEX_NOTUNIQUE, ['path']);

        // Conditionally launch drop index ix_pa.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define key course (foreign) to be dropped form iomad_learningpathcourse.
        $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);

        // Launch drop key course.
        $dbman->drop_key($table, $key);

        // Define key group (foreign) to be dropped form iomad_learningpathcourse.
        $key = new xmldb_key('group', XMLDB_KEY_FOREIGN, ['groupid'], 'iomad_learningpathgroup', ['id']);

        // Launch drop key group.
        $dbman->drop_key($table, $key);

        // Rename field course on table iomad_learningpathcourse to courseid.
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field courseid.
        $dbman->rename_field($table, $field, 'courseid');

        // Rename field path on table iomad_learningpathcourse to pathid.
        $field = new xmldb_field('path', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch rename field pathid.
        $dbman->rename_field($table, $field, 'pathid');

        // Define key fk_courseid (foreign) to be added to iomad_learningpathcourse.
        $key = new xmldb_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to block_iomad_learningpath_courses.
        $dbman->rename_table($table, 'block_iomad_learningpath_courses');

        // Iomad_learningpathgroup table restructure.
        $table = new xmldb_table('iomad_learningpathgroup');

        // Define index ix_lp (not unique) to be dropped form iomad_learningpathgroup.
        $index = new xmldb_index('ix_lp', XMLDB_INDEX_NOTUNIQUE, ['learningpath']);

        // Conditionally launch drop index ix_lp.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field learningpath on table block_iomad_learningpath_groups to pathid.
        $field = new xmldb_field('learningpath', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field to pathid.
        $dbman->rename_field($table, $field, 'pathid');

        // Launch rename table to block_iomad_learningpath_groups.
        $dbman->rename_table($table, 'block_iomad_learningpath_groups');

        // Iomad_learningpath table restructure.
        $table = new xmldb_table('iomad_learningpath');

        // Define index ix_com (not unique) to be dropped form iomad_learningpath.
        $index = new xmldb_index('ix_com', XMLDB_INDEX_NOTUNIQUE, ['company']);

        // Conditionally launch drop index ix_com.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index uix_comnam (unique) to be dropped form iomad_learningpath.
        $index = new xmldb_index('uix_comnam', XMLDB_INDEX_UNIQUE, ['company', 'name']);

        // Conditionally launch drop index uix_comnam.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field company on table iomad_learningpath to companyid.
        $field = new xmldb_field('company', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field companyid.
        $dbman->rename_field($table, $field, 'companyid');

        // Define index companyid (not unique) to be added to iomad_learningpath.
        $index = new xmldb_index('companyid', XMLDB_INDEX_NOTUNIQUE, ['companyid']);

        // Conditionally launch add index companyid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index companyid-name (unique) to be added to iomad_learningpath.
        $index = new xmldb_index('companyid-name', XMLDB_INDEX_UNIQUE, ['companyid', 'name']);

        // Conditionally launch add index companyid-name.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to block_iomad_learningpath.
        $dbman->rename_table($table, 'block_iomad_learningpath');

        // Add the foreign keys back in.

        // Define key fk_groupid (foreign) to be added to iomad_learningpathcourse.
        $table = new xmldb_table('block_iomad_learningpath_courses');
        $key = new xmldb_key('fk_groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'block_iomad_learningpath_groups', ['id']);

        // Launch add key fk_groupid.
        $dbman->add_key($table, $key);

        // Define key fk_pathid (foreign) to be added to iomad_learningpathcourse.
        $table = new xmldb_table('block_iomad_learningpath_courses');
        $key = new xmldb_key('fk_pathid', XMLDB_KEY_FOREIGN, ['pathid'], 'block_iomad_learningpath', ['id']);

        // Launch add key fk_pathid.
        $dbman->add_key($table, $key);

        // Define key fk_pathid (foreign) to be added to block_iomad_learningpath_groups.
        $table = new xmldb_table('block_iomad_learningpath_groups');
        $key = new xmldb_key('fk_pathid', XMLDB_KEY_FOREIGN, ['pathid'], 'block_iomad_learningpath', ['id']);

        // Launch add key fk_pathid.
        $dbman->add_key($table, $key);

        // Define key fk_pathid (foreign) to be added to block_iomad_learningpath_users.
        $table = new xmldb_table('block_iomad_learningpath_users');
        $key = new xmldb_key('fk_pathid', XMLDB_KEY_FOREIGN, ['pathid'], 'block_iomad_learningpath', ['id']);

        // Launch add key fk_pathid.
        $dbman->add_key($table, $key);

        // Define key fk_pathid (foreign) to be added to block_iomad_learningpath_competency_templates.
        $table = new xmldb_table('block_iomad_learningpath_competency_templates');
        $key = new xmldb_key('fk_pathid', XMLDB_KEY_FOREIGN, ['pathid'], 'block_iomad_learningpath', ['id']);

        // Launch add key fk_pathid.
        $dbman->add_key($table, $key);

        // Define key fk_templateid (foreign) to be added to block_iomad_learningpath_competency_templates.
        $table = new xmldb_table('block_iomad_learningpath_competency_templates');
        $key = new xmldb_key('fk_templateid', XMLDB_KEY_FOREIGN, ['templateid'], 'competency_template', ['id']);

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Iomad_learningpath savepoint reached.
        upgrade_block_savepoint(true, 2026022700, 'iomad_learningpath');
    }

    if ($oldversion < 2026033100) {

        // Define field dependent to be added to block_iomad_learningpath_groups.
        $table = new xmldb_table('block_iomad_learningpath_groups');
        $field = new xmldb_field('dependent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sequence');

        // Conditionally launch add field dependent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad_learningpath savepoint reached.
        upgrade_block_savepoint(true, 2026033100, 'iomad_learningpath');
    }

    return true;
}
