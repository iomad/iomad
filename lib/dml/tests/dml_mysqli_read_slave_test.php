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
 * DML read/read-write database handle tests for mysqli_native_moodle_database
 *
 * @package    core
 * @category   dml
 * @copyright  2018 Srdjan Janković, Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/fixtures/read_slave_moodle_database_mock_mysqli.php');

/**
 * DML mysqli_native_moodle_database read slave specific tests
 *
 * @package    core
 * @category   dml
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_dml_mysqli_read_slave_testcase extends base_testcase {
    /**
     * Test readonly handle is not used for reading from special pg_*() call queries,
     * pg_try_advisory_lock and pg_advisory_unlock.
     *
     * @return void
     */
    public function test_lock() : void {
        $DB = new read_slave_moodle_database_mock_mysqli();

        $this->assertEquals(0, $DB->perf_get_reads_slave());

        $DB->query_start("SELECT GET_LOCK('lock',1)", null, SQL_QUERY_SELECT);
        $this->assertEquals('test_rw', $DB->get_db_handle());
        $DB->query_end(null);
        $this->assertEquals(0, $DB->perf_get_reads_slave());

        $DB->query_start("SELECT RELEASE_LOCK('lock',1)", null, SQL_QUERY_SELECT);
        $this->assertEquals('test_rw', $DB->get_db_handle());
        $DB->query_end(null);
        $this->assertEquals(0, $DB->perf_get_reads_slave());
    }

    /**
     * Test readonly connection failure with real mysqli connection
     *
     * @return void
     */
    public function test_real_readslave_connect_fail() : void {
        global $DB;

        if ($DB->get_dbfamily() != 'mysql') {
            $this->markTestSkipped("Not mysql");
        }

        // Open second connection.
        $cfg = $DB->export_dbconfig();
        if (!isset($cfg->dboptions)) {
            $cfg->dboptions = array();
        }
        $cfg->dboptions['readonly'] = [
            'instance' => ['host.that.is.not'],
            'connecttimeout' => 1
        ];

        $db2 = moodle_database::get_driver_instance($cfg->dbtype, $cfg->dblibrary);
        $db2->connect($cfg->dbhost, $cfg->dbuser, $cfg->dbpass, $cfg->dbname, $cfg->prefix, $cfg->dboptions);
        $this->assertTrue(count($db2->get_records('user')) > 0);
    }
}
