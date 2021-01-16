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
 * Unit tests for user/profile/lib.php.
 *
 * @package core_user
 * @copyright 2014 The Open University
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for user/profile/lib.php.
 *
 * @package core_user
 * @copyright 2014 The Open University
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_user_profilelib_testcase extends advanced_testcase {
    /**
     * Tests profile_get_custom_fields function and checks it is consistent
     * with profile_user_record.
     */
    public function test_get_custom_fields() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        // Add a custom field of textarea type.
        $id1 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogdesc', 'name' => 'Description of frog', 'categoryid' => 1,
                'datatype' => 'textarea'));

        // Check the field is returned.
        $result = profile_get_custom_fields();
        $this->assertArrayHasKey($id1, $result);
        $this->assertEquals('frogdesc', $result[$id1]->shortname);

        // Textarea types are not included in user data though, so if we
        // use the 'only in user data' parameter, there is still nothing.
        $this->assertArrayNotHasKey($id1, profile_get_custom_fields(true));

        // Check that profile_user_record returns same (no) fields.
        $this->assertObjectNotHasAttribute('frogdesc', profile_user_record($user->id));

        // Check that profile_user_record returns all the fields when requested.
        $this->assertObjectHasAttribute('frogdesc', profile_user_record($user->id, false));

        // Add another custom field, this time of normal text type.
        $id2 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogname', 'name' => 'Name of frog', 'categoryid' => 1,
                'datatype' => 'text'));

        // Check both are returned using normal option.
        $result = profile_get_custom_fields();
        $this->assertArrayHasKey($id2, $result);
        $this->assertEquals('frogname', $result[$id2]->shortname);

        // And check that only the one is returned the other way.
        $this->assertArrayHasKey($id2, profile_get_custom_fields(true));

        // Check profile_user_record returns same field.
        $this->assertObjectHasAttribute('frogname', profile_user_record($user->id));

        // Check that profile_user_record returns all the fields when requested.
        $this->assertObjectHasAttribute('frogname', profile_user_record($user->id, false));
    }

    /**
     * Make sure that all profile fields can be initialised without arguments.
     */
    public function test_default_constructor() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/definelib.php');
        $datatypes = profile_list_datatypes();
        foreach ($datatypes as $datatype => $datatypename) {
            require_once($CFG->dirroot . '/user/profile/field/' .
                $datatype . '/field.class.php');
            $newfield = 'profile_field_' . $datatype;
            $formfield = new $newfield();
            $this->assertNotNull($formfield);
        }
    }

    /**
     * Test profile_view function
     */
    public function test_profile_view() {
        global $USER;

        $this->resetAfterTest();

        // Course without sections.
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $this->setUser($user);

        // Redirect events to the sink, so we can recover them later.
        $sink = $this->redirectEvents();

        profile_view($user, $context, $course);
        $events = $sink->get_events();
        $event = reset($events);

        // Check the event details are correct.
        $this->assertInstanceOf('\core\event\user_profile_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals($user->id, $event->relateduserid);
        $this->assertEquals($course->id, $event->other['courseid']);
        $this->assertEquals($course->shortname, $event->other['courseshortname']);
        $this->assertEquals($course->fullname, $event->other['coursefullname']);

        profile_view($user, $usercontext);
        $events = $sink->get_events();
        $event = array_pop($events);
        $sink->close();

        $this->assertInstanceOf('\core\event\user_profile_viewed', $event);
        $this->assertEquals($usercontext, $event->get_context());
        $this->assertEquals($user->id, $event->relateduserid);

    }

    /**
     * Test that {@link user_not_fully_set_up()} takes required custom fields into account.
     */
    public function test_profile_has_required_custom_fields_set() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/mnet/lib.php');

        $this->resetAfterTest();

        // Add a required, visible, unlocked custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'house', 'name' => 'House', 'required' => 1,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        // Add an optional, visible, unlocked custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'pet', 'name' => 'Pet', 'required' => 0,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        // Add required but invisible custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'secretid', 'name' => 'Secret ID', 'required' => 1,
            'visible' => 0, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        // Add required but locked custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'muggleborn', 'name' => 'Muggle-born', 'required' => 1,
            'visible' => 1, 'locked' => 1, 'categoryid' => 1, 'datatype' => 'checkbox']);

        // Create some student accounts.
        $hermione = $this->getDataGenerator()->create_user();
        $harry = $this->getDataGenerator()->create_user();
        $ron = $this->getDataGenerator()->create_user();
        $draco = $this->getDataGenerator()->create_user();

        // Hermione has all available custom fields filled (of course she has).
        profile_save_data((object)['id' => $hermione->id, 'profile_field_house' => 'Gryffindor']);
        profile_save_data((object)['id' => $hermione->id, 'profile_field_pet' => 'Crookshanks']);

        // Harry has only the optional field filled.
        profile_save_data((object)['id' => $harry->id, 'profile_field_pet' => 'Hedwig']);

        // Draco has only the required field filled.
        profile_save_data((object)['id' => $draco->id, 'profile_field_house' => 'Slytherin']);

        // Only students with required fields filled should be considered as fully set up in the default (strict) mode.
        $this->assertFalse(user_not_fully_set_up($hermione));
        $this->assertFalse(user_not_fully_set_up($draco));
        $this->assertTrue(user_not_fully_set_up($harry));
        $this->assertTrue(user_not_fully_set_up($ron));

        // In the lax mode, students do not need to have required fields filled.
        $this->assertFalse(user_not_fully_set_up($hermione, false));
        $this->assertFalse(user_not_fully_set_up($draco, false));
        $this->assertFalse(user_not_fully_set_up($harry, false));
        $this->assertFalse(user_not_fully_set_up($ron, false));

        // Lack of required core field is seen as a problem in either mode.
        unset($hermione->email);
        $this->assertTrue(user_not_fully_set_up($hermione, true));
        $this->assertTrue(user_not_fully_set_up($hermione, false));

        // When confirming remote MNet users, we do not have custom fields available.
        $roamingharry = mnet_strip_user($harry, ['firstname', 'lastname', 'email']);
        $roaminghermione = mnet_strip_user($hermione, ['firstname', 'lastname', 'email']);

        $this->assertTrue(user_not_fully_set_up($roamingharry, true));
        $this->assertFalse(user_not_fully_set_up($roamingharry, false));
        $this->assertTrue(user_not_fully_set_up($roaminghermione, true));
        $this->assertTrue(user_not_fully_set_up($roaminghermione, false));
    }
}
