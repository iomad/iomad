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
 * file index.php
 * index page to view blogs. if no blog is specified then site wide entries are shown
 * if a blog id is specified then the latest entries from that blog are shown
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/locallib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/comment/lib.php');

$id       = optional_param('id', null, PARAM_INT);
$start    = optional_param('formstart', 0, PARAM_INT);
$tag      = optional_param('tag', '', PARAM_NOTAGS);
$userid   = optional_param('userid', null, PARAM_INT);
$tagid    = optional_param('tagid', null, PARAM_INT);
$modid    = optional_param('modid', null, PARAM_INT);
$entryid  = optional_param('entryid', null, PARAM_INT);
$groupid  = optional_param('groupid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$search   = optional_param('search', null, PARAM_RAW);

comment::init();

$urlparams = compact('id', 'start', 'tag', 'userid', 'tagid', 'modid', 'entryid', 'groupid', 'courseid', 'search');
foreach ($urlparams as $var => $val) {
    if (empty($val)) {
        unset($urlparams[$var]);
    }
}
$PAGE->set_url('/blog/index.php', $urlparams);

// Correct tagid if a text tag is provided as a param.
if (!empty($tag)) {
    if ($tagrec = $DB->get_record('tag', array('name' => $tag))) {
        $tagid = $tagrec->id;
    } else {
        unset($tagid);
    }
}

// Set the userid to the entry author if we have the entry ID.
if ($entryid and !isset($userid)) {
    $entry = new blog_entry($entryid);
    $userid = $entry->userid;
}

if (isset($userid) && empty($courseid) && empty($modid)) {
    $context = context_user::instance($userid);
} else if (!empty($courseid) && $courseid != SITEID) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}
$PAGE->set_context($context);

$sitecontext = context_system::instance();

if (isset($userid) && $USER->id == $userid) {
    $blognode = $PAGE->navigation->find('siteblog', null);
    if ($blognode) {
        $blognode->make_inactive();
    }
}

// Check basic permissions.
if ($CFG->bloglevel == BLOG_GLOBAL_LEVEL) {
    // Everybody can see anything - no login required unless site is locked down using forcelogin.
    if ($CFG->forcelogin) {
        require_login();
    }

} else if ($CFG->bloglevel == BLOG_SITE_LEVEL) {
    // Users must log in and can not be guests.
    require_login();
    if (isguestuser()) {
        // They must have entered the url manually.
        print_error('noguest');
    }

} else if ($CFG->bloglevel == BLOG_USER_LEVEL) {
    // Users can see own blogs only! with the exception of people with special cap.
    require_login();

} else {
    // Weird!
    print_error('blogdisable', 'blog');
}

if (empty($CFG->enableblogs)) {
    print_error('blogdisable', 'blog');
}

// Add courseid if modid or groupid is specified: This is used for navigation and title.
if (!empty($modid) && empty($courseid)) {
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $modid));
}

if (!empty($groupid) && empty($courseid)) {
    $courseid = $DB->get_field('groups', 'courseid', array('id' => $groupid));
}


if (!$userid && has_capability('moodle/blog:view', $sitecontext) && $CFG->bloglevel > BLOG_USER_LEVEL) {
    if ($entryid) {
        if (!$entryobject = $DB->get_record('post', array('id' => $entryid))) {
            print_error('nosuchentry', 'blog');
        }
        $userid = $entryobject->userid;
    }
} else if (!$userid) {
    $userid = $USER->id;
}

if (!empty($modid)) {
    if ($CFG->bloglevel < BLOG_SITE_LEVEL) {
        print_error(get_string('nocourseblogs', 'blog'));
    }
    if (!$mod = $DB->get_record('course_modules', array('id' => $modid))) {
        print_error(get_string('invalidmodid', 'blog'));
    }
    $courseid = $mod->course;
}

if ((empty($courseid) ? true : $courseid == SITEID) && empty($userid)) {
    if ($CFG->bloglevel < BLOG_SITE_LEVEL) {
        print_error('siteblogdisable', 'blog');
    }
    if (!has_capability('moodle/blog:view', $sitecontext)) {
        print_error('cannotviewsiteblog', 'blog');
    }

    $COURSE = $DB->get_record('course', array('format' => 'site'));
    $courseid = $COURSE->id;
}

if (!empty($courseid)) {
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourseid');
    }

    $courseid = $course->id;
    require_login($course);

    if (!has_capability('moodle/blog:view', $sitecontext)) {
        print_error('cannotviewcourseblog', 'blog');
    }
} else {
    $coursecontext = context_course::instance(SITEID);
}

if (!empty($groupid)) {
    if ($CFG->bloglevel < BLOG_SITE_LEVEL) {
        print_error('groupblogdisable', 'blog');
    }

    if (! $group = groups_get_group($groupid)) {
        print_error(get_string('invalidgroupid', 'blog'));
    }

    if (!$course = $DB->get_record('course', array('id' => $group->courseid))) {
        print_error('invalidcourseid');
    }

    $coursecontext = context_course::instance($course->id);
    $courseid = $course->id;
    require_login($course);

    if (!has_capability('moodle/blog:view', $sitecontext)) {
        print_error(get_string('cannotviewcourseorgroupblog', 'blog'));
    }

    if (groups_get_course_groupmode($course) == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $coursecontext)) {
        if (!groups_is_member($groupid)) {
            print_error('notmemberofgroup');
        }
    }
}

if (!empty($userid)) {
    if ($CFG->bloglevel < BLOG_USER_LEVEL) {
        print_error('blogdisable', 'blog');
    }

    if (!$user = $DB->get_record('user', array('id' => $userid))) {
        print_error('invaliduserid');
    }

    if ($user->deleted) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('userdeleted'));
        echo $OUTPUT->footer();
        die;
    }

    if ($USER->id == $userid) {
        if (!has_capability('moodle/blog:create', $sitecontext)
          && !has_capability('moodle/blog:view', $sitecontext)) {
            print_error('donothaveblog', 'blog');
        }
    } else {
        if (!has_capability('moodle/blog:view', $sitecontext) || !blog_user_can_view_user_entry($userid)) {
            print_error('cannotviewcourseblog', 'blog');
        }

        $PAGE->navigation->extend_for_user($user);
    }
}

$courseid = (empty($courseid)) ? SITEID : $courseid;


$blogheaders = blog_get_headers();

$rsscontext = null;
$filtertype = null;
$thingid = null;
$rsstitle = '';
if ($CFG->enablerssfeeds) {
    list($thingid, $rsscontext, $filtertype) = blog_rss_get_params($blogheaders['filters']);
    if (empty($rsscontext)) {
        $rsscontext = context_system::instance();
    }
    $rsstitle = $blogheaders['heading'];

    // Check we haven't started output by outputting an error message.
    if ($PAGE->state == moodle_page::STATE_BEFORE_HEADER) {
        blog_rss_add_http_header($rsscontext, $rsstitle, $filtertype, $thingid, $tagid);
    }
}

$usernode = $PAGE->navigation->find('user'.$userid, null);
if ($usernode && $courseid != SITEID) {
    $courseblogsnode = $PAGE->navigation->find('courseblogs', null);
    if ($courseblogsnode) {
        $courseblogsnode->remove();
    }
    $blogurl = new moodle_url($PAGE->url);
    $blognode = $usernode->add(get_string('blogscourse', 'blog'), $blogurl);
    $blognode->make_active();
}

if ($courseid != SITEID) {
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    if (!empty($user)) {
        $headerinfo = array('heading' => fullname($user), 'user' => $user);
        echo $OUTPUT->context_header($headerinfo, 2);
    }
} else if (isset($userid)) {
    $PAGE->set_heading(fullname($user));
    echo $OUTPUT->header();
} else if ($courseid == SITEID) {
    echo $OUTPUT->header();
}

echo $OUTPUT->heading($blogheaders['heading'], 2);

$bloglisting = new blog_listing($blogheaders['filters']);
$bloglisting->print_entries();

if ($CFG->enablerssfeeds) {
    blog_rss_print_link($rsscontext, $filtertype, $thingid, $tagid, get_string('rssfeed', 'blog'));
}

echo $OUTPUT->footer();
$eventparams = array(
    'other' => array('entryid' => $entryid, 'tagid' => $tagid, 'userid' => $userid, 'modid' => $modid, 'groupid' => $groupid,
                     'search' => $search, 'fromstart' => $start)
);
if (!empty($userid)) {
    $eventparams['relateduserid'] = $userid;
}
$eventparams['other']['courseid'] = ($courseid === SITEID) ? 0 : $courseid;
$event = \core\event\blog_entries_viewed::create($eventparams);
$event->trigger();
