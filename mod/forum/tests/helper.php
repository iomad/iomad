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
 * Helper functions used by several tests.
 *
 * @package    mod_forum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Helper functions used by several tests.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait helper {

    /**
     * Helper to create the required number of users in the specified
     * course.
     * Users are enrolled as students.
     *
     * @param stdClass $course The course object
     * @param integer $count The number of users to create
     * @return array The users created
     */
    protected function helper_create_users($course, $count) {
        $users = array();

        for ($i = 0; $i < $count; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Create a new discussion and post within the specified forum, as the
     * specified author.
     *
     * @param stdClass $forum The forum to post in
     * @param stdClass $author The author to post as
     * @return array An array containing the discussion object, and the post object
     */
    protected function helper_post_to_forum($forum, $author) {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        // Create a discussion in the forum, and then add a post to that discussion.
        $record = new stdClass();
        $record->course = $forum->course;
        $record->userid = $author->id;
        $record->forum = $forum->id;
        $discussion = $generator->create_discussion($record);

        // Retrieve the post which was created by create_discussion.
        $post = $DB->get_record('forum_posts', array('discussion' => $discussion->id));

        return array($discussion, $post);
    }

    /**
     * Update the post time for the specified post by $factor.
     *
     * @param stdClass $post The post to update
     * @param int $factor The amount to update by
     */
    protected function helper_update_post_time($post, $factor) {
        global $DB;

        // Update the post to have a created in the past.
        $DB->set_field('forum_posts', 'created', $post->created + $factor, array('id' => $post->id));
    }

    /**
     * Update the subscription time for the specified user/discussion by $factor.
     *
     * @param stdClass $user The user to update
     * @param stdClass $discussion The discussion to update for this user
     * @param int $factor The amount to update by
     */
    protected function helper_update_subscription_time($user, $discussion, $factor) {
        global $DB;

        $sub = $DB->get_record('forum_discussion_subs', array('userid' => $user->id, 'discussion' => $discussion->id));

        // Update the subscription to have a preference in the past.
        $DB->set_field('forum_discussion_subs', 'preference', $sub->preference + $factor, array('id' => $sub->id));
    }

    /**
     * Create a new post within an existing discussion, as the specified author.
     *
     * @param stdClass $forum The forum to post in
     * @param stdClass $discussion The discussion to post in
     * @param stdClass $author The author to post as
     * @return stdClass The forum post
     */
    protected function helper_post_to_discussion($forum, $discussion, $author) {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        // Add a post to the discussion.
        $record = new stdClass();
        $record->course = $forum->course;
        $strre = get_string('re', 'forum');
        $record->subject = $strre . ' ' . $discussion->subject;
        $record->userid = $author->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $record->mailnow = 1;

        $post = $generator->create_post($record);

        return $post;
    }

    /**
     * Create a new post within an existing discussion, as the specified author.
     *
     * @param stdClass $parent The post being replied to
     * @param stdClass $author The author to post as
     * @return stdClass The forum post
     */
    protected function helper_reply_to_post($parent, $author) {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        // Add a post to the discussion.
        $strre = get_string('re', 'forum');
        $record = (object) [
            'discussion' => $parent->discussion,
            'parent' => $parent->id,
            'userid' => $author->id,
            'mailnow' => 1,
            'subject' => $strre . ' ' . $parent->subject,
        ];

        $post = $generator->create_post($record);

        return $post;
    }
}
