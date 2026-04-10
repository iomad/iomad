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

use core\exception\moodle_exception;
use local_iomad\company;

/**
 * Local IOMAD company creation test
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_iomad_generator extends component_generator_base {
    /** @var int number of companies created since last reset */
    protected $companycounter = 0;
    /** @var int number of departments created since last reset */
    protected $departmentcounter = 0;

    /** @var array list of company names */
    public $companynames = [
        'Test Company A', 'NewTest Corp.',
    ];

    /** @var array list of common first names */
    public $companyshortnames = [
        'test_A', 'NewTest_Corp',
    ];

    /** @var array list of major cities */
    public $companycities = [
        'Andorra la Vella', 'Escaldes-Engordany', 'Encamp', 'La Massana', 'Sant Julià de Lòria',
        'Sarajevo', 'Banja Luka', 'Tuzla', 'Zenica', 'Ilidža',
        'Montréal', 'Toronto', 'Calgary', 'Ottawa', 'Vancouver',
        '上海市', '北京', '深圳市', '广州', '成都市',
        'Berlin', 'Hamburg', 'München', 'Köln', 'Weißenfels',
        'Guayaquil', 'Quito', 'Cuenca', 'Santo Domingo', 'Durán',
        'Helsinki', 'Alajärvi', 'Espoo', 'Tampere', 'Vantaa',
        'Libreville', 'Mandji', 'Masuku', 'Owendo', 'Oyem',
        'Bath', 'Belfast', 'Birmingham', 'Cardiff', 'Glasgow',
        // '', '', '', '', '',
    ];

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->companycounter = 0;
        $this->departmentcounter = 0;
    }

    /** @var array list of country codes */
    public $companycountries = [
        'AD', // 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', // 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
        'CA', // 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM',
        'CN', // 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DE', // 'DJ', 'DK', 'DM', 'DO', 'DZ',
        'EC', // 'EE', 'EG', 'EH', 'ER', 'ES', 'ET',
        'FI', // 'FJ', 'FK', 'FM', 'FO', 'FR',
        'GA',
        'GB',
        // 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
        // 'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
        // 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
        // 'JE', 'JM', 'JO', 'JP',
        // 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
        // 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
        // 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        // 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
        // 'OM',
        // 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
        // 'QA',
        // 'RE', 'RO', 'RS', 'RU', 'RW',
        // 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
        // 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
        // 'UA', 'UG', 'UM', 'US', 'UY',
        // 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
        // 'WF', 'WS',
        // 'YE', 'YT',
        // 'ZA', 'ZM', 'ZW',
    ];

    /**
     * Create a test company
     * @param stdClass $record
     * @return stdClass company record
     */
    public function create_company($record = []): company {
        $record = (object) $record;

        $this->companycounter++;
        $i = $this->companycounter;

        // Required fields: name, shortname, city, country.
        if (!isset($record->name) && !isset($record->shortname)) {
            $name = rand(0, count($this->companynames) - 1);
            $record->name = $this->companynames[$name];
            $record->shortname = $this->companyshortnames[$name];
        } else if (!isset($record->name)) {
            $record->name = 'Test Company ' . $i;
        } else if (!isset($record->shortname)) {
            $record->shortname = 'testcorp_' . $i;
        }

        if (!isset($record->city)) {
            $city = rand(0, count($this->companycities) - 1);
            $record->city = $this->companycities[$city];
        }

        if (!isset($record->country)) {
            $record->country = $this->companycountries[floor(array_search($record->city, $this->companycities) / 5)];
        }

        // Optional fields.

        // Create the company.
        $company = company::create_company($record);

        return $company;
    }

    /**
     * Create a test department
     * @param stdClass $record
     * @return stdClass department record
     */
    public function create_department($record = []): bool|int {
        $record = (object) $record;

        $this->departmentcounter++;
        $i = $this->departmentcounter;

        // Required fields: companyid, fullname, shortname.
        if (!isset($companyid)) {
            // If no company ID is specified, create a company for the department.
            $company = self::create_company();
            $record->companyid = $company->id;
        }
        if (!isset($record->fullname)) {
            $record->fullname = 'Test Department ' . $i;
        }
        if (!isset($record->shortname)) {
            $record->shortname = 'testdep_' . $i;
        }

        // Optional fields: parentid.

        // Create the department.
        $departmentid = company::create_department(null,
                                                $record->companyid,
                                                $record->fullname,
                                                $record->shortname,
                                                returnid: true);

        return $departmentid;
    }

}
