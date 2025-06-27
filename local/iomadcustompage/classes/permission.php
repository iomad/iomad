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

use context;
use context_system;
use core\exception\coding_exception;
use dml_exception;
use local_iomadcustompage\local\helpers\audience;
use local_iomadcustompage\local\models\page;
use iomad;

/**
 * Page permission class
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {
    /**
     * Require given user can view pages list
     *
     * @param int|null $userid User ID to check, or the current user if omitted
     * @param context|null $context
     * @throws page_access_exception
     */
    public static function require_can_view_pages_list(?int $userid = null, ?context $context = null): void {
        if (!static::can_view_pages_list($userid, $context)) {
            throw new page_access_exception();
        }
    }

  /**
   * Whether given user can view pages list
   *
   * @param int|null $userid User ID to check, or the current user if omitted
   * @param context|null $context
   * @return bool
   * @throws dml_exception
   */
    public static function can_view_pages_list(?int $userid = null, ?context $context = null): bool {
        global $CFG;

        if ($context === null) {
            $context = context_system::instance();
            // IOMAD
            $companyid = iomad::get_my_companyid($context);
            if ($companyid > 0) {
                $context = \core\context\company::instance($companyid);
            }
        }

        return has_any_capability([
            'local/iomadcustompage:editall',
            'local/iomadcustompage:edit',
            'local/iomadcustompage:view',
        ], $context, $userid);
    }

    /**
     * Require given user can view page
     *
     * @param page $page
     * @param int|null $userid User ID to check, or the current user if omitted
     * @throws page_access_exception
     */
    public static function require_can_view_page(page $page, ?int $userid = null): void {
        if (!static::can_view_page($page, $userid)) {
            throw new page_access_exception('errorpageview');
        }
    }

  /**
   * Whether given user can view page
   *
   * @param page $page
   * @param int|null $userid User ID to check, or the current user if omitted
   * @return bool
   * @throws \coding_exception
   * @throws coding_exception
   * @throws dml_exception
   */
    public static function can_view_page(page $page, ?int $userid = null): bool {
        if (static::can_view_pages_list($userid, $page->get_context())) {
            return true;
        }

        //if (self::can_edit_page($page, $userid)) {
            //return true;
        //}

        $pages = audience::user_pages_list($userid);
        if (in_array($page->get('id'), $pages)) {
            return true;
        }

        return false;
    }

    /**
     * Require given user can edit page
     *
     * @param page $page
     * @param int|null $userid User ID to check, or the current user if omitted
     * @throws page_access_exception
     */
    public static function require_can_edit_page(page $page, ?int $userid = null): void {
        if (!static::can_edit_page($page, $userid)) {
            throw new page_access_exception('errorpageedit');
        }
    }

  /**
   * Whether given user can edit page
   *
   * @param page $page
   * @param int|null $userid User ID to check, or the current user if omitted
   * @return bool
   * @throws \coding_exception
   */
    public static function can_edit_page(page $page, ?int $userid = null): bool {
        global $CFG, $USER;

        // To edit their own pages, users must have either of the 'edit' or 'editall' capabilities. For pages
        // belonging
        // to other users, they must have the specific 'editall' capability.
        $userid = $userid ?: (int) $USER->id;

        // IOMAD
        $companyid = iomad::get_my_companyid(context_system::instance());
        if ($companyid > 0) {
            $context = \core\context\company::instance($companyid);
        }

        if ($page->get('usercreated') === $userid) {
            return has_any_capability([
                'local/iomadcustompage:edit',
                'local/iomadcustompage:editall',
            ], $page->get_context(), $userid);
        } else if (static::can_view_page($page, $userid)) {
            return has_any_capability([
                'local/iomadcustompage:edit',
                'local/iomadcustompage:editall',
            ], $context, $userid);
        } else {
            return has_capability('local/iomadcustompage:editall', $page->get_context(), $userid);
        }
    }

    /**
     * Whether given user can create a new page
     *
     * @param int|null $userid User ID to check, or the current user if omitted
     * @param context|null $context
     * @return bool
     */
    public static function can_create_page(?int $userid = null, ?context $context = null): bool {
        return is_siteadmin($userid);
    }

    /**
     * Require given user can create a new page
     *
     * @param int|null $userid User ID to check, or the current user if omitted
     * @param context|null $context
     * @throws page_access_exception
     */
    public static function require_can_create_page(?int $userid = null, ?context $context = null): void {
        if (!static::can_create_page($userid, $context)) {
            throw new page_access_exception('errorpagecreate');
        }
    }
}
