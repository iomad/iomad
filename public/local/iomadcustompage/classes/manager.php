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

declare(strict_types=1);

namespace local_iomadcustompage;

use coding_exception;
use core\invalid_persistent_exception;
use dml_exception;
use dml_missing_record_exception;
use local_iomadcustompage\local\models\page;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * IOMAD Custom page manager class
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var int Maximum number of breadcrumb items to prevent infinite loops */
    private const MAX_BREADCRUMB_DEPTH = 10;

    /**
     * Return an instance of a page class from the given page ID
     *
     * @param int $pageid The page ID
     * @return page The page instance
     * @throws coding_exception If page ID is invalid
     * @throws dml_missing_record_exception If page not found
     */
    public static function get_page_from_id(int $pageid): page {
        if ($pageid <= 0) {
            throw new coding_exception('Invalid page ID provided: ' . $pageid);
        }

        try {
            return new page($pageid);
        } catch (dml_missing_record_exception $e) {
            throw new dml_missing_record_exception('local_iomadcustompages', 'id = ' . $pageid);
        }
    }

    /**
     * Create new page persistent
     *
     * @param stdClass $pagedata The page data
     * @return page The created page
     * @throws coding_exception If page data is invalid
     * @throws dml_exception|invalid_persistent_exception If database operation fails
     */
    public static function create_page_persistent(stdClass $pagedata): page {
        self::validate_page_data($pagedata);

        try {
            return (new page(0, $pagedata))->create();
        } catch (dml_exception $e) {
            throw new dml_exception('Error creating page: ' . $e->getMessage());
        }
    }

    /**
     * Validate page data before creation or update
     *
     * @param stdClass $pagedata The page data to validate
     * @throws coding_exception If validation fails
     */
    private static function validate_page_data(stdClass $pagedata): void {
        $requiredfields = ['name'];

        foreach ($requiredfields as $field) {
            if (!isset($pagedata->$field) || empty(trim($pagedata->$field))) {
                throw new coding_exception("Required field '$field' is missing or empty");
            }
        }
    }

    /**
     * Clean multilang text by removing empty paragraphs and trailing whitespace
     *
     * @param string|null $text The text to clean
     * @return string The cleaned text
     */
    public static function clean_multilang_text(?string $text): string {
        if (empty($text)) {
            return '';
        }

        // Remove empty paragraphs with only non-breaking spaces.
        $text = preg_replace('/<p[^>]*>(?:\s*<span[^>]*>(?:&nbsp;|\xC2\xA0)+<\/span>\s*)*<\/p>/i', '', $text);

        // Remove empty paragraphs.
        $text = preg_replace('/<p[^>]*>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $text);

        // Remove trailing non-breaking spaces and line breaks.
        $text = preg_replace('/(?:&nbsp;|\xC2\xA0)*(?:<br\s*\/?>)*\s*$/', '', $text);
        $text = preg_replace('/(?:<br\s*\/?>\s*|&nbsp;)+$/', '', $text);

        return trim($text, " \t\n\r\0\x0B\xC2\xA0");
    }

    /**
     * Setup page breadcrumb navigation
     *
     * @param array $breadcrumbItems Array of page objects to build breadcrumb from
     * @throws coding_exception If a breadcrumb item is not a page object
     * @throws moodle_exception If URL construction fails
     */
    public static function setup_page_breadcrumb(array $breadcrumbitems): void {
        global $PAGE;

        if (empty($breadcrumbitems)) {
            return;
        }

        // Prevent infinite loops.
        if (count($breadcrumbitems) > self::MAX_BREADCRUMB_DEPTH) {
            debugging('Breadcrumb depth exceeds maximum allowed', DEBUG_DEVELOPER);
            $breadcrumbitems = array_slice($breadcrumbitems, 0, self::MAX_BREADCRUMB_DEPTH);
        }

        $PAGE->navbar->ignore_active();
        self::add_user_homepage_breadcrumb_node();

        foreach ($breadcrumbitems as $breadcrumbitem) {
            if (!$breadcrumbitem instanceof page) {
                throw new coding_exception('Invalid breadcrumb item: must be a page object');
            }

            $breadcrumbtitle = $breadcrumbitem->get_formatted_name();
            $breadcrumburl = $breadcrumbitem->is_container() ?
                null :
                new moodle_url('/local/iomadcustompage/view.php', ['id' => $breadcrumbitem->get('id')]);

            $navbarnode = $PAGE->navbar->add($breadcrumbtitle, $breadcrumburl);
            if ($breadcrumbitem->is_container() && $navbarnode) {
                $navbarnode->make_inactive();
            }
        }
    }

    /**
     * Add user homepage breadcrumb node
     *
     * @throws moodle_exception If URL construction fails
     */
    private static function add_user_homepage_breadcrumb_node(): void {
        global $PAGE;
        $homepage = get_home_page();
        switch ($homepage) {
            case HOMEPAGE_MY:
                $homepageurl = new moodle_url('/my/index.php');
                $homepagetitle = get_string('myhome');
                break;
            case HOMEPAGE_MYCOURSES:
                $homepageurl = new moodle_url('/my/courses.php');
                $homepagetitle = get_string('mycourses');
                break;
            default:
                $homepageurl = new moodle_url('/index.php');
                $homepagetitle = get_string('home');
        }
        $PAGE->navbar->add($homepagetitle, $homepageurl);
    }

    /**
     * Check if user can access page
     *
     * @param page $page The page to check access for
     * @param int|null $userid The user ID, or null for current user
     * @return bool True if user can access page
     */
    public static function can_user_access_page(page $page, ?int $userid = null): bool {
        try {
            permission::require_can_view_page($page, $userid);
            return true;
        } catch (page_access_exception $e) {
            return false;
        }
    }
}
