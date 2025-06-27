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

namespace local_iomadcustompage\local\helpers;

use cache;
use coding_exception;
use context;
use context_system;
use core_collator;
use core_component;
use core_plugin_manager;
use core_reportbuilder\local\helpers\database;
use dml_exception;
use local_iomadcustompage\local\audiences\base;
use local_iomadcustompage\local\models\audience as audience_model;
use iomad;

/**
 * Class containing page audience helper methods
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience {
  /**
   * Return audience instances for a given page. Note that any records pointing to invalid audience types will be excluded
   *
   * @param int $pageid
   * @return base[]
   * @throws coding_exception
   */
    public static function get_base_records(int $pageid): array {
        $records = audience_model::get_records(['pageid' => $pageid], 'id');

        $instances = array_map(static function (audience_model $audience): ?base {
            return base::instance(0, $audience->to_record());
        }, $records);

        // Filter and remove null elements (invalid audience types).
        return array_filter($instances);
    }

  /**
   * Returns list of iomadcustompages IDs that the specified user can access, based on audience configuration. This can be expensive if the
   * site has lots of pages, with lots of audiences, so we cache the result for the duration of the users session
   *
   * @param int|null $userid User ID to check, or the current user if omitted
   * @return int[]
   * @throws \core\exception\coding_exception
   * @throws dml_exception
   * @throws coding_exception
   */
    public static function get_allowed_pages(?int $userid = null): array {
        global $USER, $DB;

        $userid = $userid ?: (int) $USER->id;

        // Prepare cache, if we previously stored the users allowed pages then return that.
        $cache = cache::make('local_iomadcustompage', 'iomadcustompage_allowed_pages');
        $cachedpages = $cache->get($userid);
        if ($cachedpages !== false) {
            return $cachedpages;
        }

        $allowedpages = [];
        $pageaudiences = [];

        // Retrieve all audiences and group them by page for convenience.
        $audiences = audience_model::get_records();
        foreach ($audiences as $audience) {
            $pageaudiences[$audience->get('pageid')][] = $audience;
        }

        foreach ($pageaudiences as $pageid => $audiences) {
            // Generate audience SQL based on those for the current page.
            [$wheres, $params] = self::user_audience_sql($audiences);
            if (count($wheres) === 0) {
                continue;
            }

            $paramuserid = database::generate_param_name();
            $params[$paramuserid] = $userid;

            $sql = "SELECT DISTINCT(u.id)
                      FROM {user} u
                     WHERE (" . implode(' OR ', $wheres) . ")
                       AND u.deleted = 0
                       AND u.id = :{$paramuserid}";

            // If we have a matching record, user can view the page.
            if ($DB->record_exists_sql($sql, $params)) {
                $allowedpages[] = $pageid;
            }
        }

        // Store users allowed pages in cache.
        $cache->set($userid, $allowedpages);

        return $allowedpages;
    }

    /**
     * Purge the audience cache of allowed pages
     */
    public static function purge_caches(): void {
        cache::make('local_iomadcustompage', 'iomadcustompage_allowed_pages')->purge();
    }

  /**
   * Generate SQL select clause and params for selecting pages specified user can access, based on audience configuration
   *
   * @param string $pagetablealias
   * @param int|null $userid User ID to check, or the current user if omitted
   * @return array
   * @throws \core\exception\coding_exception
   * @throws coding_exception
   * @throws dml_exception
   */
    public static function user_pages_list_sql(string $pagetablealias, ?int $userid = null): array {
        global $DB;

        $allowedpages = self::get_allowed_pages($userid);

        if (empty($allowedpages)) {
            return ['1=0', []];
        }

        // Get all sql audiences.
        $prefix = database::generate_param_name() . '_';
        [$select, $params] = $DB->get_in_or_equal($allowedpages, SQL_PARAMS_NAMED, $prefix);
        $sql = "{$pagetablealias}.id {$select}";

        return [$sql, $params];
    }

  /**
   * Return list of page ID's specified user can access, based on audience configuration
   *
   * @param int|null $userid User ID to check, or the current user if omitted
   * @return int[]
   * @throws \core\exception\coding_exception
   * @throws coding_exception
   * @throws dml_exception
   */
    public static function user_pages_list(?int $userid = null): array {
        global $DB;
        $pagetablealias = database::generate_alias();
        [$select, $params] = self::user_pages_list_sql($pagetablealias, $userid);
        $sql = "SELECT {$pagetablealias}.id
                  FROM {local_iomadcustompages} $pagetablealias
                 WHERE {$select}";

        return $DB->get_fieldset_sql($sql, $params);
    }

  /**
   * Returns SQL to limit the list of pages to those that the given user has access to
   *
   * - A user with 'editall' capability will have access to all pages
   * - A user with 'edit' capability will have access to:
   *      - Those pages this user has created
   *      - Those pages this user is in audience of
   * - A user with 'view' capability will have access to:
   *      - Those pages this user is in audience of
   *
   * @param string $pagetablealias
   * @param int|null $userid User ID to check, or the current user if omitted
   * @param context|null $context
   * @return array
   * @throws \core\exception\coding_exception
   * @throws coding_exception
   * @throws dml_exception
   */
    public static function user_pages_list_access_sql(
        string $pagetablealias,
        ?int $userid = null,
        ?context $context = null
    ): array {
        global $DB, $USER;

        if ($context === null) {
            $context = context_system::instance();
            // IOMAD
            $companyid = iomad::get_my_companyid($context);
            if ($companyid > 0) {
                $context = \core\context\company::instance($companyid);
            }
        }

        // If user can't view all pages, limit the returned list to those pages they can see.
        if (!has_capability('local/iomadcustompage:editall', $context, $userid)) {
            $pages = self::user_pages_list($userid);

            [$paramprefix, $paramuserid] = database::generate_param_names(2);
            [$pageselect, $params] = $DB->get_in_or_equal($pages, SQL_PARAMS_NAMED, "{$paramprefix}_", true, null);

            $where = "{$pagetablealias}.id {$pageselect}";

            // User can also see any pages that they can edit.
            if (has_capability('local/iomadcustompage:edit', $context, $userid)) {
                $where = "({$pagetablealias}.usercreated = :{$paramuserid} OR {$where})";
                $params[$paramuserid] = $userid ?? $USER->id;
            }

            return [$where, $params];
        }

        return ['1=1', []];
    }

  /**
   * Return appropriate list of where clauses and params for given audiences
   *
   * @param audience_model[] $audiences
   * @param string $usertablealias
   * @return array[] [$wheres, $params]
   * @throws coding_exception
   */
    public static function user_audience_sql(array $audiences, string $usertablealias = 'u'): array {
        $wheres = $params = [];

        foreach ($audiences as $audience) {
            if ($instance = base::instance(0, $audience->to_record())) {
                $instancetablealias = database::generate_alias();
                [$instancejoin, $instancewhere, $instanceparams] = $instance->get_sql($instancetablealias);

                $wheres[] = "{$usertablealias}.id IN (
                    SELECT {$instancetablealias}.id
                      FROM {user} {$instancetablealias}
                           {$instancejoin}
                     WHERE {$instancewhere}
                     )";
                $params += $instanceparams;
            }
        }

        return [$wheres, $params];
    }

  /**
   * Returns the list of audiences types in the system.
   *
   * @return array
   * @throws coding_exception
   */
    private static function get_audience_types(): array {
        $sources = [];

        $audiences = core_component::get_component_classes_in_namespace('local_iomadcustompage', 'iomadcustompage\\audience');
        foreach ($audiences as $class => $path) {
            $audienceclass = $class::instance();
            if (is_subclass_of($class, base::class) && $audienceclass->user_can_add()) {
                [$component] = explode('\\', $class);

                if ($plugininfo = core_plugin_manager::instance()->get_plugin_info($component)) {
                    $componentname = $plugininfo->displayname;
                } else {
                    $componentname = get_string('site');
                }

                $sources[$componentname][$class] = $audienceclass->get_name();
            }
        }

        return $sources;
    }

  /**
   * Get all the audiences types the current user can add to, organised by categories.
   *
   * @return array
   *
   * @throws coding_exception
   * @deprecated since Moodle 4.1 - please do not use this function any more, {@see custom_page_audience_cards_exporter}
   */
    public static function get_all_audiences_menu_types(): array {
        debugging('The function ' . __FUNCTION__ . '() is deprecated, please do not use it any more. ' .
            'See \'custom_page_audience_cards_exporter\' class for replacement', DEBUG_DEVELOPER);

        $menucardsarray = [];
        $notavailablestr = get_string('notavailable', 'moodle');

        $audiencetypes = self::get_audience_types();
        $audiencetypeindex = 0;
        foreach ($audiencetypes as $categoryname => $audience) {
            $menucards = [
                'name' => $categoryname,
                'key' => 'index' . ++$audiencetypeindex,
            ];

            foreach ($audience as $classname => $name) {
                $class = $classname::instance();
                $title = $class->is_available() ? get_string('addaudience', 'core_reportbuilder', $class->get_name()) :
                    $notavailablestr;
                $menucard['title'] = $title;
                $menucard['name'] = $class->get_name();
                $menucard['disabled'] = !$class->is_available();
                $menucard['identifier'] = get_class($class);
                $menucard['action'] = 'add-audience';
                $menucards['items'][] = $menucard;
            }

            // Order audience types on each category alphabetically.
            core_collator::asort_array_of_arrays_by_key($menucards['items'], 'name');
            $menucards['items'] = array_values($menucards['items']);

            $menucardsarray[] = $menucards;
        }

        return $menucardsarray;
    }
}
