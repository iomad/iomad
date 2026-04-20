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

namespace core_badges;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use context_course;
use context_system;

require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * Abstract base class for badge award selectors.
 *
 * @package    core_badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Dai Nguyen Trong <ngtrdai@hotmail.com> based on Yuliya Bozhko <yuliya.bozhko@totaralms.com> code
 */
abstract class award_selector_base extends \user_selector_base {
    /**
     * @var int|null The id of the badge this selector is being used for.
     */
    protected ?int $badgeid = null;

    /**
     * @var \context|null The context of the badge this selector is being used for.
     */
    protected ?\context $context = null;

    /**
     * @var int|null The id of the role of badge issuer in current context.
     */
    protected ?int $issuerrole = null;

    /**
     * @var int|null The id of badge issuer.
     */
    protected ?int $issuerid = null;

    /**
     * @var string|null The return address. Accepts either a string or a moodle_url.
     */
    public ?string $url;

    /**
     * @var int|null The current group being displayed.
     */
    public ?int $currentgroup;

    /**
     * Constructor method.
     *
     * @param string $name The name of the selector.
     * @param array $options Configuration options for the selector.
     */
    public function __construct(string $name, array $options) {
        global $COURSE;
        $options['accesscontext'] = $options['context'];
        parent::__construct($name, $options);

        if (isset($options['context'])) {
            if ($options['context'] instanceof context_system) {
                // If it is a site badge, we need to get context of frontpage.
                $this->context = context_course::instance(SITEID);
            } else {
                $this->context = $options['context'];
            }
        }

        $this->badgeid = $options['badgeid'] ?? null;
        $this->issuerid = $options['issuerid'] ?? null;
        $this->issuerrole = $options['issuerrole'] ?? null;
        $this->url = $options['url'] ?? null;
        $this->currentgroup = $options['currentgroup'] ?? groups_get_course_group($COURSE, true);
    }

    /**
     * Returns an array of options to serialize and store for searches.
     *
     * @return array Array of options for serialization.
     */
    protected function get_options(): array {
        $options = parent::get_options();
        $options['file'] = 'badges/classes/award_selector_base.php';
        $options['context'] = $this->context;
        $options['badgeid'] = $this->badgeid;
        $options['issuerid'] = $this->issuerid;
        $options['issuerrole'] = $this->issuerrole;

        // These will be used to filter potential badge recipients when searching.
        $options['currentgroup'] = $this->currentgroup;

        return $options;
    }

    /**
     * Restricts the selection of users to display, according to the groups they belong.
     *
     * @return array Array containing group SQL, where clause, and parameters.
     */
    protected function get_groups_sql(): array {
        $groupsql = '';
        $groupwheresql = '';
        $groupwheresqlparams = [];

        if ($this->currentgroup) {
            $groupsql = ' JOIN {groups_members} gm ON gm.userid = u.id ';
            $groupwheresql = ' AND gm.groupid = :gr_grpid ';
            $groupwheresqlparams = ['gr_grpid' => $this->currentgroup];
        }

        return [$groupsql, $groupwheresql, $groupwheresqlparams];
    }

    /**
     * Find users matching the search criteria.
     * This method must be implemented by child classes.
     *
     * @param string $search The search string.
     * @return array Array of users matching the search criteria.
     */
    abstract public function find_users($search): array;
}
