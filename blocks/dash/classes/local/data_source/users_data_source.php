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
 * Class users_data_source.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\block_builder;
use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\dash_framework\query_builder\join;
use block_dash\local\dash_framework\structure\table;
use block_dash\local\dash_framework\structure\user_table;
use block_dash\local\data_grid\filter\current_course_participants_condition;
use block_dash\local\data_grid\filter\date_filter;
use block_dash\local\data_grid\filter\filter;
use block_dash\local\data_grid\filter\group_filter;
use block_dash\local\data_grid\filter\logged_in_user_condition;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\data_grid\filter\filter_collection_interface;
use block_dash\local\data_grid\filter\my_groups_condition;
use block_dash\local\data_grid\filter\participants_condition;
use block_dash\local\data_grid\filter\user_field_filter;
use block_dash\local\data_grid\filter\user_profile_field_filter;
use block_dash\local\data_grid\filter\current_course_condition;
use block_dash\local\data_grid\filter\bool_filter;
use coding_exception;
use context;
/**
 * Class users_data_source.
 *
 * @package block_dash
 */
class users_data_source extends abstract_data_source {

    /**
     * Constructor.
     *
     * @param context $context
     */
    public function __construct(context $context) {
        $this->add_table(new user_table());

        parent::__construct($context);
    }

    /**
     * Get human readable name of data source.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('users');
    }

    /**
     * Return query template for retrieving user info.
     *
     * @return builder
     * @throws coding_exception
     */
    public function get_query_template(): builder {
        global $CFG, $DB;

        require_once("$CFG->dirroot/user/profile/lib.php");

        $builder = new builder();
        $builder
            ->select('u.id', 'u_id')
            ->from('user', 'u')
            ->join('user_enrolments', 'ue', 'userid', 'u.id', join::TYPE_LEFT_JOIN)
            ->join('enrol', 'e', 'id', 'ue.enrolid', join::TYPE_LEFT_JOIN)
            ->join('course', 'c', 'id', 'e.courseid', join::TYPE_LEFT_JOIN)
            ->join('groups_members', 'gm', 'userid', 'u.id', join::TYPE_LEFT_JOIN)
            ->join('groups', 'g', 'id', 'gm.groupid', join::TYPE_LEFT_JOIN);

        foreach (profile_get_custom_fields() as $field) {
            $alias = 'u_pf_' . strtolower($field->shortname);

            $builder
                ->join('user_info_data', $alias, 'userid', 'u.id', join::TYPE_LEFT_JOIN)
                ->join_condition($alias, "$alias.fieldid = $field->id");
        }

        $builder->where('u.deleted', [0]);

        return $builder;
    }

    /**
     * Group by columns.
     *
     * @return string
     */
    public function get_groupby() {
        return false;
    }

    /**
     * Build and return filter collection.
     *
     * @return filter_collection_interface
     * @throws coding_exception
     */
    public function build_filter_collection() {
        global $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");

        $filtercollection = new filter_collection(get_class($this), $this->get_context());

        $filtercollection->add_filter(new group_filter('group', 'gm100.groupid'));

        $filtercollection->add_filter(new user_field_filter('u_department', 'u.department', 'department',
            get_string('department')));
        $filtercollection->add_filter(new user_field_filter('u_institution', 'u.institution', 'institution',
            get_string('institution')));

        $filter = new date_filter('u_lastlogin', 'u.lastlogin', date_filter::DATE_FUNCTION_FLOOR,
            get_string('lastlogin'));
        $filter->set_operation(filter::OPERATION_GREATER_THAN_EQUAL);
        $filtercollection->add_filter($filter);

        $filter = new date_filter('u_firstaccess', 'u.firstaccess', date_filter::DATE_FUNCTION_FLOOR,
            get_string('firstaccess'));
        $filter->set_operation(filter::OPERATION_GREATER_THAN_EQUAL);
        $filtercollection->add_filter($filter);

        $filtercollection->add_filter(new logged_in_user_condition('current_user', 'u.id'));
        $filtercollection->add_filter(new participants_condition('participants', 'u.id'));
        $filtercollection->add_filter(new my_groups_condition('my_groups', 'gm300.groupid'));
        $filtercollection->add_filter(new current_course_condition('current_course', 'c.id'));

        if (block_dash_has_pro()) {
            $filtercollection->add_filter(new \local_dash\data_grid\filter\relations_role_condition('parentrole', 'u.id'));
            $filtercollection->add_filter(new \local_dash\data_grid\filter\cohort_condition('cohort', 'u.id'));
            $filtercollection->add_filter(new \local_dash\data_grid\filter\users_mycohort_condition('users_mycohort', 'u.id'));
        }

        foreach (profile_get_custom_fields() as $field) {
            $alias = 'u_pf_' . strtolower($field->shortname);
            $select = $alias . '.data';
            switch ($field->datatype) {
                case 'checkbox':
                    $definitions[] = new bool_filter($alias, $select, $field->name);
                    break;
                case 'datetime':
                    $filtercollection->add_filter(new date_filter($alias, $select, date_filter::DATE_FUNCTION_FLOOR,
                            $field->name));
                    break;
                case 'textarea':
                    break;
                default:
                    $filter = new user_profile_field_filter($alias, $alias . '.data', $field->id, $field->name);
                    $filter->set_label(format_string($field->name));
                    $filtercollection->add_filter($filter);
                    break;
            }
        }

        return $filtercollection;
    }

    /**
     * Set the default preferences of the User datasource, force the set the default settings.
     *
     * @param array $data
     * @return array
     */
    public function set_default_preferences(&$data) {
        $configpreferences = $data['config_preferences'];
        $configpreferences['available_fields']['u_firstname']['visible'] = true;
        $configpreferences['available_fields']['u_lastname']['visible'] = true;
        $configpreferences['available_fields']['u_email']['visible'] = true;
        $configpreferences['available_fields']['u_lastlogin']['visible'] = true;
        $data['config_preferences'] = $configpreferences;
    }
}
