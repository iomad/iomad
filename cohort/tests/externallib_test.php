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
 * External cohort API
 *
 * @package    core_cohort
 * @category   external
 * @copyright  MediaTouch 2000 srl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/cohort/externallib.php');

class core_cohort_externallib_testcase extends externallib_advanced_testcase {

    /**
     * Test create_cohorts
     *
     * @expectedException required_capability_exception
     */
    public function test_create_cohorts() {
        global $USER, $CFG, $DB;

        $this->resetAfterTest(true);

        $contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_category();

        $cohort1 = array(
            'categorytype' => array('type' => 'id', 'value' => $category->id),
            'name' => 'cohort test 1',
            'idnumber' => 'cohorttest1',
            'description' => 'This is a description for cohorttest1'
            );

        $cohort2 = array(
            'categorytype' => array('type' => 'system', 'value' => ''),
            'name' => 'cohort test 2',
            'idnumber' => 'cohorttest2',
            'description' => 'This is a description for cohorttest2',
            'visible' => 0
            );

        $cohort3 = array(
            'categorytype' => array('type' => 'id', 'value' => $category->id),
            'name' => 'cohort test 3',
            'idnumber' => 'cohorttest3',
            'description' => 'This is a description for cohorttest3'
            );
        $roleid = $this->assignUserCapability('moodle/cohort:manage', $contextid);

        // Call the external function.
        $this->setCurrentTimeStart();
        $createdcohorts = core_cohort_external::create_cohorts(array($cohort1, $cohort2));
        $createdcohorts = external_api::clean_returnvalue(core_cohort_external::create_cohorts_returns(), $createdcohorts);

        // Check we retrieve the good total number of created cohorts + no error on capability.
        $this->assertEquals(2, count($createdcohorts));

        foreach ($createdcohorts as $createdcohort) {
            $dbcohort = $DB->get_record('cohort', array('id' => $createdcohort['id']));
            if ($createdcohort['idnumber'] == $cohort1['idnumber']) {
                $conid = $DB->get_field('context', 'id', array('instanceid' => $cohort1['categorytype']['value'],
                        'contextlevel' => CONTEXT_COURSECAT));
                $this->assertEquals($dbcohort->contextid, $conid);
                $this->assertEquals($dbcohort->name, $cohort1['name']);
                $this->assertEquals($dbcohort->description, $cohort1['description']);
                $this->assertEquals($dbcohort->visible, 1); // Field was not specified, ensure it is visible by default.
            } else if ($createdcohort['idnumber'] == $cohort2['idnumber']) {
                $this->assertEquals($dbcohort->contextid, context_system::instance()->id);
                $this->assertEquals($dbcohort->name, $cohort2['name']);
                $this->assertEquals($dbcohort->description, $cohort2['description']);
                $this->assertEquals($dbcohort->visible, $cohort2['visible']);
            } else {
                $this->fail('Unrecognised cohort found');
            }
            $this->assertTimeCurrent($dbcohort->timecreated);
            $this->assertTimeCurrent($dbcohort->timemodified);
        }

        // Call without required capability.
        $this->unassignUserCapability('moodle/cohort:manage', $contextid, $roleid);
        $createdcohorts = core_cohort_external::create_cohorts(array($cohort3));
    }

    /**
     * Test delete_cohorts
     *
     * @expectedException required_capability_exception
     */
    public function test_delete_cohorts() {
        global $USER, $CFG, $DB;

        $this->resetAfterTest(true);

        $cohort1 = self::getDataGenerator()->create_cohort();
        $cohort2 = self::getDataGenerator()->create_cohort();
        // Check the cohorts were correctly created.
        $this->assertEquals(2, $DB->count_records_select('cohort', ' (id = :cohortid1 OR id = :cohortid2)',
                array('cohortid1' => $cohort1->id, 'cohortid2' => $cohort2->id)));

        $contextid = $cohort1->contextid;
        $roleid = $this->assignUserCapability('moodle/cohort:manage', $contextid);

        // Call the external function.
        core_cohort_external::delete_cohorts(array($cohort1->id, $cohort2->id));

        // Check we retrieve no cohorts + no error on capability.
        $this->assertEquals(0, $DB->count_records_select('cohort', ' (id = :cohortid1 OR id = :cohortid2)',
                array('cohortid1' => $cohort1->id, 'cohortid2' => $cohort2->id)));

        // Call without required capability.
        $cohort1 = self::getDataGenerator()->create_cohort();
        $cohort2 = self::getDataGenerator()->create_cohort();
        $this->unassignUserCapability('moodle/cohort:manage', $contextid, $roleid);
        core_cohort_external::delete_cohorts(array($cohort1->id, $cohort2->id));
    }

    /**
     * Test get_cohorts
     */
    public function test_get_cohorts() {
        global $USER, $CFG;

        $this->resetAfterTest(true);

        $cohort1 = array(
            'contextid' => 1,
            'name' => 'cohortnametest1',
            'idnumber' => 'idnumbertest1',
            'description' => 'This is a description for cohort 1'
            );
        $cohort1 = self::getDataGenerator()->create_cohort($cohort1);
        $cohort2 = self::getDataGenerator()->create_cohort();

        $context = context_system::instance();
        $roleid = $this->assignUserCapability('moodle/cohort:view', $context->id);

        // Call the external function.
        $returnedcohorts = core_cohort_external::get_cohorts(array(
            $cohort1->id, $cohort2->id));
        $returnedcohorts = external_api::clean_returnvalue(core_cohort_external::get_cohorts_returns(), $returnedcohorts);

        // Check we retrieve the good total number of enrolled cohorts + no error on capability.
        $this->assertEquals(2, count($returnedcohorts));

        foreach ($returnedcohorts as $enrolledcohort) {
            if ($enrolledcohort['idnumber'] == $cohort1->idnumber) {
                $this->assertEquals($cohort1->name, $enrolledcohort['name']);
                $this->assertEquals($cohort1->description, $enrolledcohort['description']);
                $this->assertEquals($cohort1->visible, $enrolledcohort['visible']);
            }
        }

        // Check that a user with cohort:manage can see the cohort.
        $this->unassignUserCapability('moodle/cohort:view', $context->id, $roleid);
        $roleid = $this->assignUserCapability('moodle/cohort:manage', $context->id, $roleid);
        // Call the external function.
        $returnedcohorts = core_cohort_external::get_cohorts(array(
            $cohort1->id, $cohort2->id));
        $returnedcohorts = external_api::clean_returnvalue(core_cohort_external::get_cohorts_returns(), $returnedcohorts);

        // Check we retrieve the good total number of enrolled cohorts + no error on capability.
        $this->assertEquals(2, count($returnedcohorts));
    }

    /**
     * Test update_cohorts
     *
     * @expectedException required_capability_exception
     */
    public function test_update_cohorts() {
        global $USER, $CFG, $DB;

        $this->resetAfterTest(true);

        $cohort1 = self::getDataGenerator()->create_cohort(array('visible' => 0));

        $cohort1 = array(
            'id' => $cohort1->id,
            'categorytype' => array('type' => 'id', 'value' => '1'),
            'name' => 'cohortnametest1',
            'idnumber' => 'idnumbertest1',
            'description' => 'This is a description for cohort 1'
            );

        $context = context_system::instance();
        $roleid = $this->assignUserCapability('moodle/cohort:manage', $context->id);

        // Call the external function.
        core_cohort_external::update_cohorts(array($cohort1));

        $dbcohort = $DB->get_record('cohort', array('id' => $cohort1['id']));
        $contextid = $DB->get_field('context', 'id', array('instanceid' => $cohort1['categorytype']['value'],
        'contextlevel' => CONTEXT_COURSECAT));
        $this->assertEquals($dbcohort->contextid, $contextid);
        $this->assertEquals($dbcohort->name, $cohort1['name']);
        $this->assertEquals($dbcohort->idnumber, $cohort1['idnumber']);
        $this->assertEquals($dbcohort->description, $cohort1['description']);
        $this->assertEquals($dbcohort->visible, 0);

        // Since field 'visible' was added in 2.8, make sure that update works correctly with and without this parameter.
        core_cohort_external::update_cohorts(array($cohort1 + array('visible' => 1)));
        $dbcohort = $DB->get_record('cohort', array('id' => $cohort1['id']));
        $this->assertEquals(1, $dbcohort->visible);
        core_cohort_external::update_cohorts(array($cohort1));
        $dbcohort = $DB->get_record('cohort', array('id' => $cohort1['id']));
        $this->assertEquals(1, $dbcohort->visible);

        // Call without required capability.
        $this->unassignUserCapability('moodle/cohort:manage', $context->id, $roleid);
        core_cohort_external::update_cohorts(array($cohort1));
    }

    /**
     * Verify handling of 'id' param.
     */
    public function test_update_cohorts_invalid_id_param() {
        $this->resetAfterTest(true);
        $cohort = self::getDataGenerator()->create_cohort();

        $cohort1 = array(
            'id' => 'THIS IS NOT AN ID',
            'name' => 'Changed cohort name',
            'categorytype' => array('type' => 'id', 'value' => '1'),
            'idnumber' => $cohort->idnumber,
        );

        try {
            core_cohort_external::update_cohorts(array($cohort1));
            $this->fail('Expecting invalid_parameter_exception exception, none occured');
        } catch (invalid_parameter_exception $e1) {
            $this->assertContains('Invalid external api parameter: the value is "THIS IS NOT AN ID"', $e1->debuginfo);
        }

        $cohort1['id'] = 9.999; // Also not a valid id of a cohort.
        try {
            core_cohort_external::update_cohorts(array($cohort1));
            $this->fail('Expecting invalid_parameter_exception exception, none occured');
        } catch (invalid_parameter_exception $e2) {
            $this->assertContains('Invalid external api parameter: the value is "9.999"', $e2->debuginfo);
        }
    }

    /**
     * Test update_cohorts without permission on the dest category.
     *
     * @expectedException required_capability_exception
     */
    public function test_update_cohorts_missing_dest() {
        global $USER, $CFG, $DB;

        $this->resetAfterTest(true);

        $category1 = self::getDataGenerator()->create_category(array(
            'name' => 'Test category 1'
        ));
        $category2 = self::getDataGenerator()->create_category(array(
            'name' => 'Test category 2'
        ));
        $context1 = context_coursecat::instance($category1->id);
        $context2 = context_coursecat::instance($category2->id);

        $cohort = array(
            'contextid' => $context1->id,
            'name' => 'cohortnametest1',
            'idnumber' => 'idnumbertest1',
            'description' => 'This is a description for cohort 1'
            );
        $cohort1 = self::getDataGenerator()->create_cohort($cohort);

        $roleid = $this->assignUserCapability('moodle/cohort:manage', $context1->id);

        $cohortupdate = array(
            'id' => $cohort1->id,
            'categorytype' => array('type' => 'id', 'value' => $category2->id),
            'name' => 'cohort update',
            'idnumber' => 'idnumber update',
            'description' => 'This is a description update'
            );

        // Call the external function.
        // Should fail because we don't have permission on the dest category
        core_cohort_external::update_cohorts(array($cohortupdate));
    }

    /**
     * Test update_cohorts without permission on the src category.
     *
     * @expectedException required_capability_exception
     */
    public function test_update_cohorts_missing_src() {
        global $USER, $CFG, $DB;

        $this->resetAfterTest(true);

        $category1 = self::getDataGenerator()->create_category(array(
            'name' => 'Test category 1'
        ));
        $category2 = self::getDataGenerator()->create_category(array(
            'name' => 'Test category 2'
        ));
        $context1 = context_coursecat::instance($category1->id);
        $context2 = context_coursecat::instance($category2->id);

        $cohort = array(
            'contextid' => $context1->id,
            'name' => 'cohortnametest1',
            'idnumber' => 'idnumbertest1',
            'description' => 'This is a description for cohort 1'
            );
        $cohort1 = self::getDataGenerator()->create_cohort($cohort);

        $roleid = $this->assignUserCapability('moodle/cohort:manage', $context2->id);

        $cohortupdate = array(
            'id' => $cohort1->id,
            'categorytype' => array('type' => 'id', 'value' => $category2->id),
            'name' => 'cohort update',
            'idnumber' => 'idnumber update',
            'description' => 'This is a description update'
            );

        // Call the external function.
        // Should fail because we don't have permission on the src category
        core_cohort_external::update_cohorts(array($cohortupdate));
    }

    /**
     * Test add_cohort_members
     *
     * @expectedException required_capability_exception
     */
    public function test_add_cohort_members() {
        global $DB;

        $this->resetAfterTest(true); // Reset all changes automatically after this test.

        $contextid = context_system::instance()->id;

        $cohort = array(
            'contextid' => $contextid,
            'name' => 'cohortnametest1',
            'idnumber' => 'idnumbertest1',
            'description' => 'This is a description for cohort 1'
            );
        $cohort0 = self::getDataGenerator()->create_cohort($cohort);
        // Check the cohorts were correctly created.
        $this->assertEquals(1, $DB->count_records_select('cohort', ' (id = :cohortid0)',
            array('cohortid0' => $cohort0->id)));

        $cohort1 = array(
            'cohorttype' => array('type' => 'id', 'value' => $cohort0->id),
            'usertype' => array('type' => 'id', 'value' => '1')
            );

        $roleid = $this->assignUserCapability('moodle/cohort:assign', $contextid);

        // Call the external function.
        $addcohortmembers = core_cohort_external::add_cohort_members(array($cohort1));
        $addcohortmembers = external_api::clean_returnvalue(core_cohort_external::add_cohort_members_returns(), $addcohortmembers);

        // Check we retrieve the good total number of created cohorts + no error on capability.
        $this->assertEquals(1, count($addcohortmembers));

        foreach ($addcohortmembers as $addcohortmember) {
            $dbcohort = $DB->get_record('cohort_members', array('cohortid' => $cohort0->id));
            $this->assertEquals($dbcohort->cohortid, $cohort1['cohorttype']['value']);
            $this->assertEquals($dbcohort->userid, $cohort1['usertype']['value']);
        }

        // Call without required capability.
        $cohort2 = array(
            'cohorttype' => array('type' => 'id', 'value' => $cohort0->id),
            'usertype' => array('type' => 'id', 'value' => '2')
            );
        $this->unassignUserCapability('moodle/cohort:assign', $contextid, $roleid);
        $addcohortmembers = core_cohort_external::add_cohort_members(array($cohort2));
    }

    /**
     * Test delete_cohort_members
     *
     * @expectedException required_capability_exception
     */
    public function test_delete_cohort_members() {
        global $DB;

        $this->resetAfterTest(true); // Reset all changes automatically after this test.

        $cohort1 = self::getDataGenerator()->create_cohort();
        $user1 = self::getDataGenerator()->create_user();
        $cohort2 = self::getDataGenerator()->create_cohort();
        $user2 = self::getDataGenerator()->create_user();

        $context = context_system::instance();
        $roleid = $this->assignUserCapability('moodle/cohort:assign', $context->id);

        $cohortaddmember1 = array(
            'cohorttype' => array('type' => 'id', 'value' => $cohort1->id),
            'usertype' => array('type' => 'id', 'value' => $user1->id)
            );
        $cohortmembers1 = core_cohort_external::add_cohort_members(array($cohortaddmember1));
        $cohortmembers1 = external_api::clean_returnvalue(core_cohort_external::add_cohort_members_returns(), $cohortmembers1);

        $cohortaddmember2 = array(
            'cohorttype' => array('type' => 'id', 'value' => $cohort2->id),
            'usertype' => array('type' => 'id', 'value' => $user2->id)
            );
        $cohortmembers2 = core_cohort_external::add_cohort_members(array($cohortaddmember2));
        $cohortmembers2 = external_api::clean_returnvalue(core_cohort_external::add_cohort_members_returns(), $cohortmembers2);

        // Check we retrieve no cohorts + no error on capability.
        $this->assertEquals(2, $DB->count_records_select('cohort_members', ' ((cohortid = :idcohort1 AND userid = :iduser1)
            OR (cohortid = :idcohort2 AND userid = :iduser2))',
            array('idcohort1' => $cohort1->id, 'iduser1' => $user1->id, 'idcohort2' => $cohort2->id, 'iduser2' => $user2->id)));

        // Call the external function.
         $cohortdel1 = array(
            'cohortid' => $cohort1->id,
            'userid' => $user1->id
            );
         $cohortdel2 = array(
            'cohortid' => $cohort2->id,
            'userid' => $user2->id
            );
        core_cohort_external::delete_cohort_members(array($cohortdel1, $cohortdel2));

        // Check we retrieve no cohorts + no error on capability.
        $this->assertEquals(0, $DB->count_records_select('cohort_members', ' ((cohortid = :idcohort1 AND userid = :iduser1)
            OR (cohortid = :idcohort2 AND userid = :iduser2))',
            array('idcohort1' => $cohort1->id, 'iduser1' => $user1->id, 'idcohort2' => $cohort2->id, 'iduser2' => $user2->id)));

        // Call without required capability.
        $this->unassignUserCapability('moodle/cohort:assign', $context->id, $roleid);
        core_cohort_external::delete_cohort_members(array($cohortdel1, $cohortdel2));
    }

    /**
     * Search cohorts.
     */
    public function test_search_cohorts() {
        $this->resetAfterTest(true);

        $creator = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();
        $catuser = $this->getDataGenerator()->create_user();
        $catcreator = $this->getDataGenerator()->create_user();
        $courseuser = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();
        $othercategory = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course();
        $syscontext = context_system::instance();
        $catcontext = context_coursecat::instance($category->id);
        $coursecontext = context_course::instance($course->id);

        // Fetching default authenticated user role.
        $userroles = get_archetype_roles('user');
        $this->assertCount(1, $userroles);
        $authrole = array_pop($userroles);

        // Reset all default authenticated users permissions.
        unassign_capability('moodle/cohort:manage', $authrole->id);

        // Creating specific roles.
        $creatorrole = create_role('Creator role', 'creatorrole', 'creator role description');
        $userrole = create_role('User role', 'userrole', 'user role description');
        $courserole = create_role('Course user role', 'courserole', 'course user role description');

        assign_capability('moodle/cohort:manage', CAP_ALLOW, $creatorrole, $syscontext->id);
        assign_capability('moodle/cohort:view', CAP_ALLOW, $courserole, $syscontext->id);

        // Check for parameter $includes = 'parents'.
        role_assign($creatorrole, $creator->id, $syscontext->id);
        role_assign($creatorrole, $catcreator->id, $catcontext->id);
        role_assign($userrole, $user->id, $syscontext->id);
        role_assign($userrole, $catuser->id, $catcontext->id);

        // Enrol user in the course.
        $this->getDataGenerator()->enrol_user($courseuser->id, $course->id, 'courserole');

        $syscontext = array('contextid' => context_system::instance()->id);
        $catcontext = array('contextid' => context_coursecat::instance($category->id)->id);
        $othercatcontext = array('contextid' => context_coursecat::instance($othercategory->id)->id);
        $coursecontext = array('contextid' => context_course::instance($course->id)->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(array_merge($syscontext, array('name' => 'Cohortsearch 1')));
        $cohort2 = $this->getDataGenerator()->create_cohort(array_merge($catcontext, array('name' => 'Cohortsearch 2')));
        $cohort3 = $this->getDataGenerator()->create_cohort(array_merge($othercatcontext, array('name' => 'Cohortsearch 3')));

        // A user without permission in the system.
        $this->setUser($user);
        try {
            $result = core_cohort_external::search_cohorts("Cohortsearch", $syscontext, 'parents');
            $this->fail('Invalid permissions in system');
        } catch (required_capability_exception $e) {
            // All good.
        }

        // A user without permission in a category.
        $this->setUser($catuser);
        try {
            $result = core_cohort_external::search_cohorts("Cohortsearch", $catcontext, 'parents');
            $this->fail('Invalid permissions in category');
        } catch (required_capability_exception $e) {
            // All good.
        }

        // A user with permissions in the system.
        $this->setUser($creator);
        $result = core_cohort_external::search_cohorts("Cohortsearch", $syscontext, 'parents');
        $this->assertEquals(1, count($result['cohorts']));
        $this->assertEquals('Cohortsearch 1', $result['cohorts'][$cohort1->id]->name);

        // A user with permissions in the category.
        $this->setUser($catcreator);
        $result = core_cohort_external::search_cohorts("Cohortsearch", $catcontext, 'parents');
        $this->assertEquals(2, count($result['cohorts']));
        $cohorts = array();
        foreach ($result['cohorts'] as $cohort) {
            $cohorts[] = $cohort->name;
        }
        $this->assertTrue(in_array('Cohortsearch 1', $cohorts));

        // Check for parameter $includes = 'self'.
        $this->setUser($creator);
        $result = core_cohort_external::search_cohorts("Cohortsearch", $othercatcontext, 'self');
        $this->assertEquals(1, count($result['cohorts']));
        $this->assertEquals('Cohortsearch 3', $result['cohorts'][$cohort3->id]->name);

        // Check for parameter $includes = 'all'.
        $this->setUser($creator);
        $result = core_cohort_external::search_cohorts("Cohortsearch", $syscontext, 'all');
        $this->assertEquals(3, count($result['cohorts']));

        // A user in the course context with the system cohort:view capability. Check that all the system cohorts are returned.
        $this->setUser($courseuser);
        $result = core_cohort_external::search_cohorts("Cohortsearch", $coursecontext, 'all');
        $this->assertEquals(1, count($result['cohorts']));
        $this->assertEquals('Cohortsearch 1', $result['cohorts'][$cohort1->id]->name);

        // Detect invalid parameter $includes.
        $this->setUser($creator);
        try {
            $result = core_cohort_external::search_cohorts("Cohortsearch", $syscontext, 'invalid');
            $this->fail('Invalid parameter includes');
        } catch (coding_exception $e) {
            // All good.
        }
    }
}
