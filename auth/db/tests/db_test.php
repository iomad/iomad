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
 * External database auth sync tests, this also tests adodb drivers
 * that are matching our four supported Moodle database drivers.
 *
 * @package    auth_db
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class auth_db_testcase extends advanced_testcase {
    /** @var string Original error log */
    protected $oldlog;

    /** @var int The amount of users to create for the large user set deletion test  */
    protected $largedeletionsetsize = 128;

    protected function init_auth_database() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/auth/db/auth.php");

        // Discard error logs from AdoDB.
        $this->oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log");

        $dbman = $DB->get_manager();

        set_config('extencoding', 'utf-8', 'auth_db');

        set_config('host', $CFG->dbhost, 'auth_db');
        set_config('user', $CFG->dbuser, 'auth_db');
        set_config('pass', $CFG->dbpass, 'auth_db');
        set_config('name', $CFG->dbname, 'auth_db');

        if (!empty($CFG->dboptions['dbport'])) {
            set_config('host', $CFG->dbhost.':'.$CFG->dboptions['dbport'], 'auth_db');
        }

        switch ($DB->get_dbfamily()) {

            case 'mysql':
                set_config('type', 'mysqli', 'auth_db');
                set_config('setupsql', "SET NAMES 'UTF-8'", 'auth_db');
                set_config('sybasequoting', '0', 'auth_db');
                if (!empty($CFG->dboptions['dbsocket'])) {
                    $dbsocket = $CFG->dboptions['dbsocket'];
                    if ((strpos($dbsocket, '/') === false and strpos($dbsocket, '\\') === false)) {
                        $dbsocket = ini_get('mysqli.default_socket');
                    }
                    set_config('type', 'mysqli://'.rawurlencode($CFG->dbuser).':'.rawurlencode($CFG->dbpass).'@'.rawurlencode($CFG->dbhost).'/'.rawurlencode($CFG->dbname).'?socket='.rawurlencode($dbsocket), 'auth_db');
                }
                break;

            case 'oracle':
                set_config('type', 'oci8po', 'auth_db');
                set_config('sybasequoting', '1', 'auth_db');
                break;

            case 'postgres':
                set_config('type', 'postgres7', 'auth_db');
                $setupsql = "SET NAMES 'UTF-8'";
                if (!empty($CFG->dboptions['dbschema'])) {
                    $setupsql .= "; SET search_path = '".$CFG->dboptions['dbschema']."'";
                }
                set_config('setupsql', $setupsql, 'auth_db');
                set_config('sybasequoting', '0', 'auth_db');
                if (!empty($CFG->dboptions['dbsocket']) and ($CFG->dbhost === 'localhost' or $CFG->dbhost === '127.0.0.1')) {
                    if (strpos($CFG->dboptions['dbsocket'], '/') !== false) {
                        $socket = $CFG->dboptions['dbsocket'];
                        if (!empty($CFG->dboptions['dbport'])) {
                            $socket .= ':' . $CFG->dboptions['dbport'];
                        }
                        set_config('host', $socket, 'auth_db');
                    } else {
                        set_config('host', '', 'auth_db');
                    }
                }
                break;

            case 'mssql':
                if (get_class($DB) == 'mssql_native_moodle_database') {
                    set_config('type', 'mssql_n', 'auth_db');
                } else {
                    set_config('type', 'mssqlnative', 'auth_db');
                }
                set_config('sybasequoting', '1', 'auth_db');
                break;

            default:
                throw new exception('Unknown database family ' . $DB->get_dbfamily());
        }

        $table = new xmldb_table('auth_db_users');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('pass', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $dbman->create_table($table);
        set_config('table', $CFG->prefix.'auth_db_users', 'auth_db');
        set_config('fielduser', 'name', 'auth_db');
        set_config('fieldpass', 'pass', 'auth_db');
        set_config('field_map_lastname', 'lastname', 'auth_db');
        set_config('field_updatelocal_lastname', 'oncreate', 'auth_db');
        set_config('field_lock_lastname', 'unlocked', 'auth_db');
        // Setu up field mappings.

        set_config('field_map_email', 'email', 'auth_db');
        set_config('field_updatelocal_email', 'oncreate', 'auth_db');
        set_config('field_updateremote_email', '0', 'auth_db');
        set_config('field_lock_email', 'unlocked', 'auth_db');

        // Init the rest of settings.
        set_config('passtype', 'plaintext', 'auth_db');
        set_config('changepasswordurl', '', 'auth_db');
        set_config('debugauthdb', 0, 'auth_db');
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_db');
    }

    protected function cleanup_auth_database() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('auth_db_users');
        $dbman->drop_table($table);

        ini_set('error_log', $this->oldlog);
    }

    public function test_plugin() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // NOTE: It is strongly discouraged to create new tables in advanced_testcase classes,
        //       but there is no other simple way to test ext database enrol sync, so let's
        //       disable transactions are try to cleanup after the tests.

        $this->preventResetByRollback();

        $this->init_auth_database();

        /** @var auth_plugin_db $auth */
        $auth = get_auth_plugin('db');

        $authdb = $auth->db_init();


        // Test adodb may access the table.

        $user1 = (object)array('name'=>'u1', 'pass'=>'heslo', 'email'=>'u1@example.com');
        $user1->id = $DB->insert_record('auth_db_users', $user1);


        $sql = "SELECT * FROM {$auth->config->table}";
        $rs = $authdb->Execute($sql);
        $this->assertInstanceOf('ADORecordSet', $rs);
        $this->assertFalse($rs->EOF);
        $fields = $rs->FetchRow();
        $this->assertTrue(is_array($fields));
        $this->assertTrue($rs->EOF);
        $rs->Close();

        $authdb->Close();


        // Test bulk user account creation.

        $user2 = (object)array('name'=>'u2', 'pass'=>'heslo', 'email'=>'u2@example.com');
        $user2->id = $DB->insert_record('auth_db_users', $user2);

        $user3 = (object)array('name'=>'admin', 'pass'=>'heslo', 'email'=>'admin@example.com'); // Should be skipped.
        $user3->id = $DB->insert_record('auth_db_users', $user3);

        $this->assertCount(2, $DB->get_records('user'));

        $trace = new null_progress_trace();
        $auth->sync_users($trace, false);

        $this->assertEquals(4, $DB->count_records('user'));
        $u1 = $DB->get_record('user', array('username'=>$user1->name, 'auth'=>'db'));
        $this->assertSame($user1->email, $u1->email);
        $u2 = $DB->get_record('user', array('username'=>$user2->name, 'auth'=>'db'));
        $this->assertSame($user2->email, $u2->email);
        $admin = $DB->get_record('user', array('username'=>'admin', 'auth'=>'manual'));
        $this->assertNotEmpty($admin);


        // Test sync updates.

        $user2b = clone($user2);
        $user2b->email = 'u2b@example.com';
        $DB->update_record('auth_db_users', $user2b);

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $u2 = $DB->get_record('user', array('username'=>$user2->name));
        $this->assertSame($user2->email, $u2->email);

        $auth->sync_users($trace, true);
        $this->assertEquals(4, $DB->count_records('user'));
        $u2 = $DB->get_record('user', array('username'=>$user2->name));
        $this->assertSame($user2->email, $u2->email);

        set_config('field_updatelocal_email', 'onlogin', 'auth_db');
        $auth->config->field_updatelocal_email = 'onlogin';

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $u2 = $DB->get_record('user', array('username'=>$user2->name));
        $this->assertSame($user2->email, $u2->email);

        $auth->sync_users($trace, true);
        $this->assertEquals(4, $DB->count_records('user'));
        $u2 = $DB->get_record('user', array('username'=>$user2->name));
        $this->assertSame($user2b->email, $u2->email);


        // Test sync deletes and suspends.

        $DB->delete_records('auth_db_users', array('id'=>$user2->id));
        $this->assertCount(2, $DB->get_records('auth_db_users'));
        unset($user2);
        unset($user2b);

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $this->assertEquals(0, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(0, $DB->count_records('user', array('suspended'=>1)));

        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_SUSPEND;

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $this->assertEquals(0, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(1, $DB->count_records('user', array('suspended'=>1)));

        $user2 = (object)array('name'=>'u2', 'pass'=>'heslo', 'email'=>'u2@example.com');
        $user2->id = $DB->insert_record('auth_db_users', $user2);

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $this->assertEquals(0, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(0, $DB->count_records('user', array('suspended'=>1)));

        $DB->delete_records('auth_db_users', array('id'=>$user2->id));

        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_FULLDELETE;

        $auth->sync_users($trace, false);
        $this->assertEquals(4, $DB->count_records('user'));
        $this->assertEquals(1, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(0, $DB->count_records('user', array('suspended'=>1)));

        $user2 = (object)array('name'=>'u2', 'pass'=>'heslo', 'email'=>'u2@example.com');
        $user2->id = $DB->insert_record('auth_db_users', $user2);

        $auth->sync_users($trace, false);
        $this->assertEquals(5, $DB->count_records('user'));
        $this->assertEquals(1, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(0, $DB->count_records('user', array('suspended'=>1)));


        // Test user_login().

        $user3 = (object)array('name'=>'u3', 'pass'=>'heslo', 'email'=>'u3@example.com');
        $user3->id = $DB->insert_record('auth_db_users', $user3);

        $this->assertFalse($auth->user_login('u4', 'heslo'));
        $this->assertTrue($auth->user_login('u1', 'heslo'));

        $this->assertFalse($DB->record_exists('user', array('username'=>'u3', 'auth'=>'db')));
        $this->assertTrue($auth->user_login('u3', 'heslo'));
        $this->assertFalse($DB->record_exists('user', array('username'=>'u3', 'auth'=>'db')));

        set_config('passtype', 'md5', 'auth_db');
        $auth->config->passtype = 'md5';
        $user3->pass = md5('heslo');
        $DB->update_record('auth_db_users', $user3);
        $this->assertTrue($auth->user_login('u3', 'heslo'));

        set_config('passtype', 'sh1', 'auth_db');
        $auth->config->passtype = 'sha1';
        $user3->pass = sha1('heslo');
        $DB->update_record('auth_db_users', $user3);
        $this->assertTrue($auth->user_login('u3', 'heslo'));

        set_config('passtype', 'saltedcrypt', 'auth_db');
        $auth->config->passtype = 'saltedcrypt';
        $user3->pass = password_hash('heslo', PASSWORD_BCRYPT);
        $DB->update_record('auth_db_users', $user3);
        $this->assertTrue($auth->user_login('u3', 'heslo'));

        set_config('passtype', 'internal', 'auth_db');
        $auth->config->passtype = 'internal';
        create_user_record('u3', 'heslo', 'db');
        $this->assertTrue($auth->user_login('u3', 'heslo'));


        $DB->delete_records('auth_db_users', array('id'=>$user3->id));

        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_KEEP;
        $this->assertTrue($auth->user_login('u3', 'heslo'));

        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_SUSPEND;
        $this->assertFalse($auth->user_login('u3', 'heslo'));

        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_FULLDELETE;
        $this->assertFalse($auth->user_login('u3', 'heslo'));

        set_config('passtype', 'sh1', 'auth_db');
        $auth->config->passtype = 'sha1';
        $this->assertFalse($auth->user_login('u3', 'heslo'));


        // Test login create and update.

        $user4 = (object)array('name'=>'u4', 'pass'=>'heslo', 'email'=>'u4@example.com');
        $user4->id = $DB->insert_record('auth_db_users', $user4);

        set_config('passtype', 'plaintext', 'auth_db');
        $auth->config->passtype = 'plaintext';

        $iuser4 = create_user_record('u4', 'heslo', 'db');
        $this->assertNotEmpty($iuser4);
        $this->assertSame($user4->name, $iuser4->username);
        $this->assertSame($user4->email, $iuser4->email);
        $this->assertSame('db', $iuser4->auth);
        $this->assertSame($CFG->mnet_localhost_id, $iuser4->mnethostid);

        $user4b = clone($user4);
        $user4b->email = 'u4b@example.com';
        $DB->update_record('auth_db_users', $user4b);

        set_config('field_updatelocal_email', 'oncreate', 'auth_db');
        $auth->config->field_updatelocal_email = 'oncreate';

        update_user_record('u4');
        $iuser4 = $DB->get_record('user', array('id'=>$iuser4->id));
        $this->assertSame($user4->email, $iuser4->email);

        set_config('field_updatelocal_email', 'onlogin', 'auth_db');
        $auth->config->field_updatelocal_email = 'onlogin';

        update_user_record('u4');
        $iuser4 = $DB->get_record('user', array('id'=>$iuser4->id));
        $this->assertSame($user4b->email, $iuser4->email);


        // Test user_exists()

        $this->assertTrue($auth->user_exists('u1'));
        $this->assertTrue($auth->user_exists('admin'));
        $this->assertFalse($auth->user_exists('u3'));
        $this->assertTrue($auth->user_exists('u4'));

        $this->cleanup_auth_database();
    }

    /**
     * Testing the function _colonscope() from ADOdb.
     */
    public function test_adodb_colonscope() {
        global $CFG;
        require_once($CFG->libdir.'/adodb/adodb.inc.php');
        require_once($CFG->libdir.'/adodb/drivers/adodb-odbc.inc.php');
        require_once($CFG->libdir.'/adodb/drivers/adodb-db2ora.inc.php');

        $this->resetAfterTest(false);

        $sql = "select * from table WHERE column=:1 AND anothercolumn > :0";
        $arr = array('b', 1);
        list($sqlout, $arrout) = _colonscope($sql,$arr);
        $this->assertEquals("select * from table WHERE column=? AND anothercolumn > ?", $sqlout);
        $this->assertEquals(array(1, 'b'), $arrout);
    }

    /**
     * Testing the clean_data() method.
     */
    public function test_clean_data() {
        global $DB;

        $this->resetAfterTest(false);
        $this->preventResetByRollback();
        $this->init_auth_database();
        $auth = get_auth_plugin('db');
        $auth->db_init();

        // Create users on external table.
        $extdbuser1 = (object)array('name'=>'u1', 'pass'=>'heslo', 'email'=>'u1@example.com');
        $extdbuser1->id = $DB->insert_record('auth_db_users', $extdbuser1);

        // User with malicious data on the name (won't be imported).
        $extdbuser2 = (object)array('name'=>'user<script>alert(1);</script>xss', 'pass'=>'heslo', 'email'=>'xssuser@example.com');
        $extdbuser2->id = $DB->insert_record('auth_db_users', $extdbuser2);

        $extdbuser3 = (object)array('name'=>'u3', 'pass'=>'heslo', 'email'=>'u3@example.com',
                'lastname' => 'user<script>alert(1);</script>xss');
        $extdbuser3->id = $DB->insert_record('auth_db_users', $extdbuser3);
        $trace = new null_progress_trace();

        // Let's test user sync make sure still works as expected..
        $auth->sync_users($trace, true);
        $this->assertDebuggingCalled("The property 'lastname' has invalid data and has been cleaned.");
        // User with correct data, should be equal to external db.
        $user1 = $DB->get_record('user', array('email'=> $extdbuser1->email, 'auth'=>'db'));
        $this->assertEquals($extdbuser1->name, $user1->username);
        $this->assertEquals($extdbuser1->email, $user1->email);

        // Get the user on moodle user table.
        $user2 = $DB->get_record('user', array('email'=> $extdbuser2->email, 'auth'=>'db'));
        $user3 = $DB->get_record('user', array('email'=> $extdbuser3->email, 'auth'=>'db'));

        $this->assertEmpty($user2);
        $this->assertEquals($extdbuser3->name, $user3->username);
        $this->assertEquals('useralert(1);xss', $user3->lastname);

        $this->cleanup_auth_database();
    }

    /**
     * Testing the deletion of a user when there are many users in the external DB.
     */
    public function test_deleting_with_many_users() {
        global $DB;

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->init_auth_database();
        $auth = get_auth_plugin('db');
        $auth->db_init();

        // Set to delete from moodle when missing from DB.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_db');
        $auth->config->removeuser = AUTH_REMOVEUSER_FULLDELETE;

        // Create users.
        $users = [];
        for ($i = 0; $i < $this->largedeletionsetsize; $i++) {
            $user = (object)array('username' => "u$i", 'name' => "u$i", 'pass' => 'heslo', 'email' => "u$i@example.com");
            $user->id  = $DB->insert_record('auth_db_users', $user);
            $users[] = $user;
        }

        // Sync to moodle.
        $trace = new null_progress_trace();
        $auth->sync_users($trace, true);

        // Check user is there.
        $user = array_shift($users);
        $moodleuser = $DB->get_record('user', array('email' => $user->email, 'auth' => 'db'));
        $this->assertNotNull($moodleuser);
        $this->assertEquals($user->username, $moodleuser->username);

        // Delete a user.
        $DB->delete_records('auth_db_users', array('id' => $user->id));

        // Sync again.
        $auth->sync_users($trace, true);

        // Check user is no longer there.
        $moodleuser = $DB->get_record('user', array('id' => $moodleuser->id));
        $this->assertFalse($auth->user_login($user->username, 'heslo'));
        $this->assertEquals(1, $moodleuser->deleted);

        // Make sure it was the only user deleted.
        $numberdeleted = $DB->count_records('user', array('deleted' => 1, 'auth' => 'db'));
        $this->assertEquals(1, $numberdeleted);

        $this->cleanup_auth_database();
    }
}
