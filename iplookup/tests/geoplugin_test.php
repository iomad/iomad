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
 * GeoIP tests
 *
 * @package    core_iplookup
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * GeoIp data file parsing test.
 */
class core_iplookup_geoplugin_testcase extends advanced_testcase {

    public function setUp() {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");
        require_once("$CFG->dirroot/iplookup/lib.php");

        if (!PHPUNIT_LONGTEST) {
            // we do not want to DDOS their server, right?
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->resetAfterTest();

        $CFG->geoipfile = '';
    }

    public function test_ipv4() {
        $result = iplookup_find_location('192.30.255.112');

        $this->assertEquals('array', gettype($result));
        $this->assertEquals('San Francisco', $result['city']);
        $this->assertEquals(-122.3933, $result['longitude'], 'Coordinates are out of accepted tolerance', 0.01);
        $this->assertEquals(37.7697, $result['latitude'], 'Coordinates are out of accepted tolerance', 0.01);
        $this->assertNull($result['error']);
        $this->assertEquals('array', gettype($result['title']));
        $this->assertEquals('San Francisco', $result['title'][0]);
        $this->assertEquals('United States', $result['title'][1]);
    }

    public function test_geoip_ipv6() {
        $result = iplookup_find_location('2a01:8900:2:3:8c6c:c0db:3d33:9ce6');

        $this->assertNotNull($result['error']);
        $this->assertEquals($result['error'], get_string('invalidipformat', 'error'));
    }
}

