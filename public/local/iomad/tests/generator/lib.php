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

use local_iomad\{company, company_user,};

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
    /** @var int number of iomad users created since last reset */
    protected $usercounter = 0;
    /** @var int number of departments created since last reset */
    protected $departmentcounter = 0;
    /** @var int number of courses created since last reset */
    protected $coursecounter = 0;

    /** @var array list of company names */
    public $companynames = [
        'Test Company', 'NewTest Corp.', 'Company 4 Testing',
    ];

    /** @var array list of common first names */
    public $companyshortnames = [
        'test_comp', 'NewTest_Corp', 'c4t'
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
        '香港', '維多利亞市', '九龍', '荃灣', '沙田新市鎮',
        'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Palembang',
        'Grouville', 'Saint Brélade', 'Saint Clément', 'Saint Helier', 'Saint John',
        'Nairobi', 'Mombasa', 'Nakuru', 'Ruiru', 'Eldoret',
        'ວຽງຈັນ', 'ໄກສອນ ພົມວິຫານ', 'ປາກເຊ', 'ຫລວງພະບາງ', 'ເມືອງຊຳເໜືອ',
        'الدار البيضاء', 'فاس', 'طنجة', 'مراكش', 'سلا',
        'Windhoek', 'Walvis Bay', 'Swakopmund', 'Henties Bay', 'Omaruru',
        'مَسْقَط', 'أدم', 'السِّيْب', 'هيماء', 'نِزْوَى',
        'Ciudad de Panamá', 'San Miguelito', 'La Chorrera', 'Colón', 'Penonomé',
        'الدوحة', 'الوكرة', 'الشحانية', 'مسيعيد', 'نعيجة',
        'L\'Étang-Salé', 'Petite-Île', 'Bras-Panon', 'Sainte-Suzanne', 'La Plaine-des-Palmistes',
        'الرياض', 'جِدَّة', 'الدمام', 'مَكَّة', 'ٱلْمَدِيْنَة',
        'South Caicos Lighthouse', 'Cockburn Town', 'Five Cays', 'Wheeland', 'Kew',
        'Київ', 'Харків', 'Одеса', 'Дніпро', 'Донецьк',
        'Mata Utu', 'Vaitupu', 'Mala\'efo\'ou', 'Leava', 'Ono',
        'صنعاء', 'تعز', 'الحديدة', 'عدن', 'إب',
        'Johannesburg-iGoli', 'Kaapstad-eKapa', 'Durban-eThekwini', 'Germiston-kwaDukathole', 'Gqeberha-iBhayi',
        // '', '', '', '', '',
    ];

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->companycounter = 0;
        $this->usercounter = 0;
        $this->departmentcounter = 0;
        $this->coursecounter = 0;
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
        'GB', // 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
        'HK', // 'HM', 'HN', 'HR', 'HT', 'HU',
        'ID', // 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
        'JE', // 'JM', 'JO', 'JP',
        'KE', // 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
        'LA', // 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
        'MA', // 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NA', // 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
        'OM',
        'PA', // 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
        'QA',
        'RE', // 'RO', 'RS', 'RU', 'RW',
        'SA', // 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
        'TC', // 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
        'UA', // 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
        'WF', // 'WS',
        'YE', // 'YT',
        'ZA', // 'ZM', 'ZW',
    ];

    /**
     * Create a test company
     * @param stdClass|array $record
     * @return stdClass $company
     */
    public function create_company($record = []): company {
        $record = is_array($record) ? (object) $record : $record;

        $this->companycounter++;
        $i = $this->companycounter;

        // Required fields: name, shortname, city, country.
        if (!isset($record->city)) {
            $city = rand(0, count($this->companycities) - 1);
            $record->city = $this->companycities[$city];
        }

        if (!isset($record->country)) {
            $record->country = $this->companycountries[floor(array_search($record->city, $this->companycities) / 5)];
        }

        if (!isset($record->name) && !isset($record->shortname)) {
            $name = rand(0, count($this->companynames) - 1);
            $record->name = $record->city . ' ' . $this->companynames[$name];
            $record->shortname = $this->companyshortnames[$name] . '_' . $i;
        } else if (!isset($record->name)) {
            $record->name = $record->city . ' Test Company ' . $i;
        } else if (!isset($record->shortname)) {
            $record->shortname = 'testcorp_' . $i;
        }

        // TODO: Generate data for optional fields.

        // Create the company.
        $company = company::create_company($record);

        return $company;
    }

    /**
     * Create a test user via IOMAD
     * @param stdClass|array $record
     * @param ?int $companyid
     * @return int $userid
     */
    public function create_iomad_user($record = [], ?int $companyid = null): int {
        $record = is_array($record) ? (object) $record : $record;

        $this->usercounter++;
        $i = $this->usercounter;

        // Required fields: companyid, username, firstname, lastname, email.
        if (empty($companyid)) {
            // Create companies.
            $company = self::create_company();
            $companyid = $company->id;
        }

        if (!isset($record->username)) {
            $record->username = 'testuser' . $i;
        }
        if (!isset($record->firstname)) {
            $record->firstname = 'Test';
        }
        if (!isset($record->lastname)) {
            $record->lastname = 'User' . $i;
        }
        if (!isset($record->email)) {
            $record->email = 'testuser@gmailland.com' . $i;
        }

        // Really depends on the setting of $record->auth: sendnewpasswordemails, preference_auth_forcepasswordchange
        if (!isset($record->sendnewpasswordemails)) {
            $record->sendnewpasswordemails = false;
        }
        if (!isset($record->preference_auth_forcepasswordchange)) {
            $record->preference_auth_forcepasswordchange = false;
        }

        // TODO: Generate data for optional fields.

        // Create the user.
        $userid = company_user::create($record, $companyid);

        return $userid;
    }

    /**
     * Create a test department
     * @param stdClass|array $record
     * @return bool|int $departmentid
     */
    public function create_department($record = []): bool|int {
        global $DB;

        $record = is_array($record) ? (object) $record : $record;

        $this->departmentcounter++;
        $i = $this->departmentcounter;

        // Required fields: companyid, fullname, shortname.
        if (empty($record->id)) {
            if (!isset($record->companyid)) {
                // If no company ID is specified, create a company for the department.
                $company = self::create_company();
                $record->companyid = $company->id;
            }
            if (!isset($record->name)) {
                $record->name = 'Test Department ' . $i;
            }
            if (!isset($record->shortname)) {
                $record->shortname = 'testdep_' . $i;
            }
        } else {
            $department = $DB->get_record('local_iomad_company_departments', ['id' => $record->id]);

            if (!isset($record->companyid)) {
                $record->companyid = (int) $department->companyid;
            }
            if (!isset($record->name)) {
                $record->name = (string) $department->name;
            }
            if (!isset($record->shortname)) {
                $record->shortname = (string) $department->shortname;
            }
        }

        // TODO: Generate data for optional fields.

        // Create the department.
        $departmentid = company::create_department($record->id ?? null,
                                                $record->companyid,
                                                $record->name,
                                                $record->shortname,
                                                returnid: true);

        return $departmentid;
    }

    /**
     * Create a test course
     * @param stdClass|array $record
     * @return ?stdClass $courseid
     */
    public function create_course($record = []): ?stdClass {
        global $DB, $CFG;

        $record = is_array($record) ? (object) $record : $record;

        $this->coursecounter++;
        $i = $this->coursecounter;

        // Required fields: category, fullname, shortname, selfenrol.
        if (empty($record->companyid)) {
            // Create company.
            $company = self::create_company();
        } else {
            $company = new company($record->companyid);
        }
        $companyrec = $DB->get_record('local_iomad_companies', ['id' => $company->id]);

        if (!isset($categoryid)) {
            if (!empty($company->coursecategoryid)) {
                $categoryid = $companyrec->coursecategoryid;
            } else {
                $categoryid = $CFG->defaultrequestcategory;
            }
        }

        $selfenrol = !empty($record->selfenrol) ? $record->selfenrol : 0; // By default, users can enrol themselves onto a course

        // TODO: Generate data for optional fields.

        // Create the course.
        $coursedata = (object) [];
        $coursedata->category = $categoryid;
        $coursedata->selfenrol = $selfenrol;
        $course = company::create_course($coursedata, $company);

        return $course;
    }

}
