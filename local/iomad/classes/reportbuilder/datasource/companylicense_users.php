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

namespace local_iomad\reportbuilder\datasource;

use lang_string;
use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\{course, user};
use local_iomad\reportbuilder\local\entities\{company, department, companyusers, companylicense, companylicenseusers};

/**
 * Local IOMAD datasource
 *
 * @package     local_iomad
 * @copyright   2024 Derick Turner e-Learn Design
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class companylicense_users extends datasource {

    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('companylicenseusers', 'block_iomad_company_admin');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {

        // Get the tables and aliases.
        $companylicenseusersentity = new companylicenseusers();
        $companylicenseusersalias = $companylicenseusersentity->get_table_alias('local_iomad_company_license_users');
        $companyusersentity = new companyusers();
        $companyusersalias = $companyusersentity->get_table_alias('local_iomad_company_users');
        $departmententity = new department();
        $departmentalias = $departmententity->get_table_alias('local_iomad_company_departments');
        $companylicenseentity = new companylicense();
        $companylicensealias = $companylicenseentity->get_table_alias('local_iomad_company_licenses');
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $companyentity = new company();
        $companyalias = $companyentity->get_table_alias('local_iomad_companies');

        $this->set_main_table('local_iomad_company_license_users', $companylicenseusersalias);

        $this->add_entity($companylicenseusersentity);

        // Join the companylicense entity to the companylicense users entity.
        $companylicenseentity->add_join("JOIN {local_iomad_company_licenses} {$companylicensealias}
                ON {$companylicensealias}.id = {$companylicenseusersalias}.licenseid");
        $this->add_entity($companylicenseentity);

        // Join in the company entity.
        $companyentity->add_joins($companylicenseentity->get_joins());
        $companyentity->add_join("JOIN {local_iomad_companies} {$companyalias}
                ON {$companylicensealias}.companyid = {$companyalias}.id");
        $this->add_entity($companyentity);

        // Join in the company user entity.
        $companyusersentity->add_joins($companyentity->get_joins());
        $companyusersentity->add_join("JOIN {local_iomad_company_users} {$companyusersalias}
                ON ({$companyusersalias}.userid = {$companylicenseusersalias}.userid
                    AND {$companyusersalias}.companyid = {$companylicensealias}.companyid
                    AND {$companyusersalias}.companyid = {$companyalias}.id)");
        $this->add_entity($companyusersentity);

        // Join the department entity to the company entity.
        $departmententity->add_joins($companyusersentity->get_joins());
        $departmententity->add_join("JOIN {local_iomad_company_departments} {$departmentalias}
                ON ({$departmentalias}.companyid = {$companyalias}.id
                    AND {$departmentalias}.id = {$companyusersalias}.departmentid)");
        $this->add_entity($departmententity);

        // Join the course entity to the company issued entity.
        $courseentity->add_joins($departmententity->get_joins());
        $courseentity->add_join("JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$companylicenseusersalias}.courseid");
        $this->add_entity($courseentity);

        // Finally add the join for the user entity.
        $this->add_entity($userentity
            ->add_joins($courseentity->get_joins())
            ->add_join("JOIN {user} {$useralias}
                ON {$useralias}.id = {$companyusersalias}.userid")
            ->set_entity_title(new lang_string('user'))
        );

        // Add report elements from each of the entities we added to the report.
        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'company:name',
            'user:fullname',
            'department:name',
            'companylicense:name',
            'course:fullname',
            'companylicenseusers:issuedate',
            'companylicenseusers:isusing',
            'companylicenseusers:timecompleted',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'company:name',
            'user:fullname',
            'department:name',
            'companylicense:name',
            'course:fullname',
            'companylicenseusers:issuedate',
            'companylicenseusers:isusing',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'company:name',
            'user:fullname',
            'department:name',
            'companylicense:name',
            'course:fullname',
            'companylicenseusers:issuedate',
            'companylicenseusers:isusing',
        ];
    }

    /**
     * Return the default sorting that will be added to the report once it is created
     *
     * @return array|int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'company:name' => SORT_ASC,
            'user:fullname' => SORT_ASC,
            'department:name' => SORT_ASC,
            'companylicense:name' => SORT_ASC,
            'course:fullname' => SORT_ASC,
            'companylicenseusers:issuedate' => SORT_ASC,
            'companylicenseusers:isusing' => SORT_ASC,
        ];
    }
}
