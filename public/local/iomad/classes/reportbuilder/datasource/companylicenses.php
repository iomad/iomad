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
use local_iomad\reportbuilder\local\entities\{company, companylicense};

/**
 * Local IOMAD datasource
 *
 * @package     local_iomad
 * @copyright   2024 Derick Turner e-Learn Design
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class companylicenses extends datasource {

    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('companylicense', 'block_iomad_company_admin');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $companyentity = new company();
        $companyalias = $companyentity->get_table_alias('local_iomad_companies');

        $this->set_main_table('local_iomad_companies', $companyalias);

        $this->add_entity($companyentity);

        // Get the tables and aliases.
        $companylicenseentity = new companylicense();
        $companylicensealias = $companylicenseentity->get_table_alias('local_iomad_company_licenses');

        $this->add_entity($companylicenseentity
            ->add_join("JOIN {local_iomad_company_licenses} {$companylicensealias}
                ON {$companylicensealias}.companyid = {$companyalias}.id")
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
            'local_iomad_companies:name',
            'local_iomad_company_licenses:name',
            'local_iomad_company_licenses:reference',
            'local_iomad_company_licenses:startdate',
            'local_iomad_company_licenses:expirydate',
            'local_iomad_company_licenses:humanallocation',
            'local_iomad_company_licenses:used',
            'local_iomad_company_licenses:program',
            'local_iomad_company_licenses:type',
            'local_iomad_company_licenses:cutoffdate',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'local_iomad_companies:name',
            'local_iomad_company_licenses:name',
            'local_iomad_company_licenses:reference',
            'local_iomad_company_licenses:startdate',
            'local_iomad_company_licenses:expirydate',
            'local_iomad_company_licenses:humanallocation',
            'local_iomad_company_licenses:used',
            'local_iomad_company_licenses:program',
            'local_iomad_company_licenses:type',
            'local_iomad_company_licenses:cutoffdate',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'local_iomad_companies:name',
            'local_iomad_company_licenses:name',
            'local_iomad_company_licenses:reference',
            'local_iomad_company_licenses:startdate',
            'local_iomad_company_licenses:expirydate',
            'local_iomad_company_licenses:humanallocation',
            'local_iomad_company_licenses:used',
            'local_iomad_company_licenses:program',
            'local_iomad_company_licenses:type',
            'local_iomad_company_licenses:cutoffdate',
        ];
    }

    /**
     * Return the default sorting that will be added to the report once it is created
     *
     * @return array|int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'local_iomad_companies:name' => SORT_ASC,
            'local_iomad_company_licenses:name' => SORT_ASC,
            'local_iomad_company_licenses:reference' => SORT_ASC,
            'local_iomad_company_licenses:startdate' => SORT_ASC,
            'local_iomad_company_licenses:expirydate' => SORT_ASC,
            'local_iomad_company_licenses:humanallocation' => SORT_ASC,
            'local_iomad_company_licenses:used' => SORT_ASC,
            'local_iomad_company_licenses:program' => SORT_ASC,
            'local_iomad_company_licenses:type' => SORT_ASC,
            'local_iomad_company_licenses:cutoffdate' => SORT_ASC,
        ];
    }
}
