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
 * Native pgsql class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/moodle_database.php');
require_once(__DIR__.'/pgsql_native_moodle_recordset.php');
require_once(__DIR__.'/pgsql_native_moodle_temptables.php');

/**
 * Native pgsql class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pgsql_native_moodle_database extends moodle_database {

    /** @var resource $pgsql database resource */
    protected $pgsql     = null;

    protected $last_error_reporting; // To handle pgsql driver default verbosity

    /** @var bool savepoint hack for MDL-35506 - workaround for automatic transaction rollback on error */
    protected $savepointpresent = false;

    /**
     * Detects if all needed PHP stuff installed.
     * Note: can be used before connect()
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('pgsql')) {
            return get_string('pgsqlextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    /**
     * Returns database family type - describes SQL dialect
     * Note: can be used before connect()
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'postgres';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysqli, pgsql, oci, mssql, sqlsrv
     */
    protected function get_dbtype() {
        return 'pgsql';
    }

    /**
     * Returns general database library name
     * Note: can be used before connect()
     * @return string db type pdo, native
     */
    protected function get_dblibrary() {
        return 'native';
    }

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('nativepgsql', 'install');
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('nativepgsqlhelp', 'install');
    }

    /**
     * Connect to db
     * Must be called before other methods.
     * @param string $dbhost The database host.
     * @param string $dbuser The database username.
     * @param string $dbpass The database username's password.
     * @param string $dbname The name of the database being connected to.
     * @param mixed $prefix string means moodle db prefix, false used for external databases where prefix not used
     * @param array $dboptions driver specific options
     * @return bool true
     * @throws dml_connection_exception if error
     */
    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        if ($prefix == '' and !$this->external) {
            //Enforce prefixes for everybody but mysql
            throw new dml_exception('prefixcannotbeempty', $this->get_dbfamily());
        }

        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        $this->store_settings($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);

        $pass = addcslashes($this->dbpass, "'\\");

        // Unix socket connections should have lower overhead
        if (!empty($this->dboptions['dbsocket']) and ($this->dbhost === 'localhost' or $this->dbhost === '127.0.0.1')) {
            $connection = "user='$this->dbuser' password='$pass' dbname='$this->dbname'";
            if (strpos($this->dboptions['dbsocket'], '/') !== false) {
                // A directory was specified as the socket location.
                $connection .= " host='".$this->dboptions['dbsocket']."'";
            }
            if (!empty($this->dboptions['dbport'])) {
                // A port as specified, add it to the connection as it's used as part of the socket path.
                $connection .= " port ='".$this->dboptions['dbport']."'";
            }
        } else {
            $this->dboptions['dbsocket'] = '';
            if (empty($this->dbname)) {
                // probably old style socket connection - do not add port
                $port = "";
            } else if (empty($this->dboptions['dbport'])) {
                $port = "port ='5432'";
            } else {
                $port = "port ='".$this->dboptions['dbport']."'";
            }
            $connection = "host='$this->dbhost' $port user='$this->dbuser' password='$pass' dbname='$this->dbname'";
        }

        if (empty($this->dboptions['dbhandlesoptions'])) {
            // ALTER USER and ALTER DATABASE are overridden by these settings.
            $options = array('--client_encoding=utf8', '--standard_conforming_strings=on');
            // Select schema if specified, otherwise the first one wins.
            if (!empty($this->dboptions['dbschema'])) {
                $options[] = "-c search_path=" . addcslashes($this->dboptions['dbschema'], "'\\");
            }

            $connection .= " options='" . implode(' ', $options) . "'";
        }

        ob_start();
        if (empty($this->dboptions['dbpersist'])) {
            $this->pgsql = pg_connect($connection, PGSQL_CONNECT_FORCE_NEW);
        } else {
            $this->pgsql = pg_pconnect($connection, PGSQL_CONNECT_FORCE_NEW);
        }
        $dberr = ob_get_contents();
        ob_end_clean();

        $status = pg_connection_status($this->pgsql);

        if ($status === false or $status === PGSQL_CONNECTION_BAD) {
            $this->pgsql = null;
            throw new dml_connection_exception($dberr);
        }

        if (!empty($this->dboptions['dbhandlesoptions'])) {
            /* We don't trust people who just set the dbhandlesoptions, this code checks up on them.
             * These functions do not talk to the server, they use the client library knowledge to determine state.
             */
            if (!empty($this->dboptions['dbschema'])) {
                throw new dml_connection_exception('You cannot specify a schema with dbhandlesoptions, use the database to set it.');
            }
            if (pg_client_encoding($this->pgsql) != 'UTF8') {
                throw new dml_connection_exception('client_encoding = UTF8 not set, it is: ' . pg_client_encoding($this->pgsql));
            }
            if (pg_escape_string($this->pgsql, '\\') != '\\') {
                throw new dml_connection_exception('standard_conforming_strings = on, must be set at the database.');
            }
        }

        // Connection stabilised and configured, going to instantiate the temptables controller
        $this->temptables = new pgsql_native_moodle_temptables($this);

        return true;
    }

    /**
     * Close database connection and release all resources
     * and memory (especially circular memory references).
     * Do NOT use connect() again, create a new instance if needed.
     */
    public function dispose() {
        parent::dispose(); // Call parent dispose to write/close session and other common stuff before closing connection
        if ($this->pgsql) {
            pg_close($this->pgsql);
            $this->pgsql = null;
        }
    }


    /**
     * Called before each db query.
     * @param string $sql
     * @param array array of parameters
     * @param int $type type of query
     * @param mixed $extrainfo driver specific extra information
     * @return void
     */
    protected function query_start($sql, array $params=null, $type, $extrainfo=null) {
        parent::query_start($sql, $params, $type, $extrainfo);
        // pgsql driver tents to send debug to output, we do not need that ;-)
        $this->last_error_reporting = error_reporting(0);
    }

    /**
     * Called immediately after each db query.
     * @param mixed db specific result
     * @return void
     */
    protected function query_end($result) {
        // reset original debug level
        error_reporting($this->last_error_reporting);
        try {
            parent::query_end($result);
            if ($this->savepointpresent and $this->last_type != SQL_QUERY_AUX and $this->last_type != SQL_QUERY_SELECT) {
                $res = @pg_query($this->pgsql, "RELEASE SAVEPOINT moodle_pg_savepoint; SAVEPOINT moodle_pg_savepoint");
                if ($res) {
                    pg_free_result($res);
                }
            }
        } catch (Exception $e) {
            if ($this->savepointpresent) {
                $res = @pg_query($this->pgsql, "ROLLBACK TO SAVEPOINT moodle_pg_savepoint; SAVEPOINT moodle_pg_savepoint");
                if ($res) {
                    pg_free_result($res);
                }
            }
            throw $e;
        }
    }

    /**
     * Returns database server info array
     * @return array Array containing 'description' and 'version' info
     */
    public function get_server_info() {
        static $info;
        if (!$info) {
            $this->query_start("--pg_version()", null, SQL_QUERY_AUX);
            $info = pg_version($this->pgsql);
            $this->query_end(true);
        }
        return array('description'=>$info['server'], 'version'=>$info['server']);
    }

    /**
     * Returns supported query parameter types
     * @return int bitmask of accepted SQL_PARAMS_*
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_DOLLAR;
    }

    /**
     * Returns last error reported by database engine.
     * @return string error message
     */
    public function get_last_error() {
        return pg_last_error($this->pgsql);
    }

    /**
     * Return tables in database WITHOUT current prefix.
     * @param bool $usecache if true, returns list of cached tables.
     * @return array of table names in lowercase and without prefix
     */
    public function get_tables($usecache=true) {
        if ($usecache and $this->tables !== null) {
            return $this->tables;
        }
        $this->tables = array();
        $prefix = str_replace('_', '|_', $this->prefix);
        $sql = "SELECT c.relname
                  FROM pg_catalog.pg_class c
                  JOIN pg_catalog.pg_namespace as ns ON ns.oid = c.relnamespace
                 WHERE c.relname LIKE '$prefix%' ESCAPE '|'
                       AND c.relkind = 'r'
                       AND (ns.nspname = current_schema() OR ns.oid = pg_my_temp_schema())";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            while ($row = pg_fetch_row($result)) {
                $tablename = reset($row);
                if ($this->prefix !== false && $this->prefix !== '') {
                    if (strpos($tablename, $this->prefix) !== 0) {
                        continue;
                    }
                    $tablename = substr($tablename, strlen($this->prefix));
                }
                $this->tables[$tablename] = $tablename;
            }
            pg_free_result($result);
        }
        return $this->tables;
    }

    /**
     * Return table indexes - everything lowercased.
     * @param string $table The table we want to get indexes from.
     * @return array of arrays
     */
    public function get_indexes($table) {
        $indexes = array();
        $tablename = $this->prefix.$table;

        $sql = "SELECT i.*
                  FROM pg_catalog.pg_indexes i
                  JOIN pg_catalog.pg_namespace as ns ON ns.nspname = i.schemaname
                 WHERE i.tablename = '$tablename'
                       AND (i.schemaname = current_schema() OR ns.oid = pg_my_temp_schema())";

        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                // The index definition could be generated schema-qualifying the target table name
                // for safety, depending on the pgsql version (CVE-2018-1058).
                if (!preg_match('/CREATE (|UNIQUE )INDEX ([^\s]+) ON (|'.$row['schemaname'].'\.)'.$tablename.' USING ([^\s]+) \(([^\)]+)\)/i', $row['indexdef'], $matches)) {
                    continue;
                }
                if ($matches[5] === 'id') {
                    continue;
                }
                $columns = explode(',', $matches[5]);
                foreach ($columns as $k=>$column) {
                    $column = trim($column);
                    if ($pos = strpos($column, ' ')) {
                        // index type is separated by space
                        $column = substr($column, 0, $pos);
                    }
                    $columns[$k] = $this->trim_quotes($column);
                }
                $indexes[$row['indexname']] = array('unique'=>!empty($matches[1]),
                                              'columns'=>$columns);
            }
            pg_free_result($result);
        }
        return $indexes;
    }

    /**
     * Returns detailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return database_column_info[] array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        if ($usecache) {
            if ($this->temptables->is_temptable($table)) {
                if ($data = $this->get_temp_tables_cache()->get($table)) {
                    return $data;
                }
            } else {
                if ($data = $this->get_metacache()->get($table)) {
                    return $data;
                }
            }
        }

        $structure = array();

        $tablename = $this->prefix.$table;

        $sql = "SELECT a.attnum, a.attname AS field, t.typname AS type, a.attlen, a.atttypmod, a.attnotnull, a.atthasdef, d.adsrc
                  FROM pg_catalog.pg_class c
                  JOIN pg_catalog.pg_namespace as ns ON ns.oid = c.relnamespace
                  JOIN pg_catalog.pg_attribute a ON a.attrelid = c.oid
                  JOIN pg_catalog.pg_type t ON t.oid = a.atttypid
             LEFT JOIN pg_catalog.pg_attrdef d ON (d.adrelid = c.oid AND d.adnum = a.attnum)
                 WHERE relkind = 'r' AND c.relname = '$tablename' AND c.reltype > 0 AND a.attnum > 0
                       AND (ns.nspname = current_schema() OR ns.oid = pg_my_temp_schema())
              ORDER BY a.attnum";

        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if (!$result) {
            return array();
        }
        while ($rawcolumn = pg_fetch_object($result)) {

            $info = new stdClass();
            $info->name = $rawcolumn->field;
            $matches = null;

            if ($rawcolumn->type === 'varchar') {
                $info->type          = 'varchar';
                $info->meta_type     = 'C';
                $info->max_length    = $rawcolumn->atttypmod - 4;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                        $info->default_value = trim($info->default_value, "'");
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                } else {
                    $info->default_value = null;
                }
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/int(\d)/i', $rawcolumn->type, $matches)) {
                $info->type = 'int';
                if (strpos($rawcolumn->adsrc, 'nextval') === 0) {
                    $info->primary_key   = true;
                    $info->meta_type     = 'R';
                    $info->unique        = true;
                    $info->auto_increment= true;
                    $info->has_default   = false;
                } else {
                    $info->primary_key   = false;
                    $info->meta_type     = 'I';
                    $info->unique        = null;
                    $info->auto_increment= false;
                    $info->has_default   = ($rawcolumn->atthasdef === 't');
                }
                // Return number of decimals, not bytes here.
                if ($matches[1] >= 8) {
                    $info->max_length = 18;
                } else if ($matches[1] >= 4) {
                    $info->max_length = 9;
                } else if ($matches[1] >= 2) {
                    $info->max_length = 4;
                } else if ($matches[1] >= 1) {
                    $info->max_length = 2;
                } else {
                    $info->max_length = 0;
                }
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                if ($info->has_default) {
                    // PG 9.5+ uses ::<TYPE> syntax for some defaults.
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                    $info->default_value = trim($info->default_value, "()'");
                } else {
                    $info->default_value = null;
                }
                $info->binary        = false;
                $info->unsigned      = false;

            } else if ($rawcolumn->type === 'numeric') {
                $info->type = $rawcolumn->type;
                $info->meta_type     = 'N';
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    // PG 9.5+ uses ::<TYPE> syntax for some defaults.
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                    $info->default_value = trim($info->default_value, "()'");
                } else {
                    $info->default_value = null;
                }
                $info->max_length    = $rawcolumn->atttypmod >> 16;
                $info->scale         = ($rawcolumn->atttypmod & 0xFFFF) - 4;

            } else if (preg_match('/float(\d)/i', $rawcolumn->type, $matches)) {
                $info->type = 'float';
                $info->meta_type     = 'N';
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    // PG 9.5+ uses ::<TYPE> syntax for some defaults.
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                    $info->default_value = trim($info->default_value, "()'");
                } else {
                    $info->default_value = null;
                }
                // just guess expected number of deciaml places :-(
                if ($matches[1] == 8) {
                    // total 15 digits
                    $info->max_length = 8;
                    $info->scale      = 7;
                } else {
                    // total 6 digits
                    $info->max_length = 4;
                    $info->scale      = 2;
                }

            } else if ($rawcolumn->type === 'text') {
                $info->type          = $rawcolumn->type;
                $info->meta_type     = 'X';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                        $info->default_value = trim($info->default_value, "'");
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                } else {
                    $info->default_value = null;
                }
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if ($rawcolumn->type === 'bytea') {
                $info->type          = $rawcolumn->type;
                $info->meta_type     = 'B';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = false;
                $info->default_value = null;
                $info->primary_key   = false;
                $info->binary        = true;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            }

            $structure[$info->name] = new database_column_info($info);
        }

        pg_free_result($result);

        if ($usecache) {
            if ($this->temptables->is_temptable($table)) {
                $this->get_temp_tables_cache()->set($table, $structure);
            } else {
                $this->get_metacache()->set($table, $structure);
            }
        }

        return $structure;
    }

    /**
     * Normalise values based in RDBMS dependencies (booleans, LOBs...)
     *
     * @param database_column_info $column column metadata corresponding with the value we are going to normalise
     * @param mixed $value value we are going to normalise
     * @return mixed the normalised value
     */
    protected function normalise_value($column, $value) {
        $this->detect_objects($value);

        if (is_bool($value)) { // Always, convert boolean to int
            $value = (int)$value;

        } else if ($column->meta_type === 'B') {
            if (!is_null($value)) {
                // standard_conforming_strings must be enabled, otherwise pg_escape_bytea() will double escape
                // \ and produce data errors.  This is set on the connection.
                $value = pg_escape_bytea($this->pgsql, $value);
            }

        } else if ($value === '') {
            if ($column->meta_type === 'I' or $column->meta_type === 'F' or $column->meta_type === 'N') {
                $value = 0; // prevent '' problems in numeric fields
            }
        }
        return $value;
    }

    /**
     * Is db in unicode mode?
     * @return bool
     */
    public function setup_is_unicodedb() {
        // Get PostgreSQL server_encoding value
        $sql = "SHOW server_encoding";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if (!$result) {
            return false;
        }
        $rawcolumn = pg_fetch_object($result);
        $encoding = $rawcolumn->server_encoding;
        pg_free_result($result);

        return (strtoupper($encoding) == 'UNICODE' || strtoupper($encoding) == 'UTF8');
    }

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string|array $sql query
     * @param array|null $tablenames an array of xmldb table names affected by this request.
     * @return bool true
     * @throws ddl_change_structure_exception A DDL specific exception is thrown for any errors.
     */
    public function change_database_structure($sql, $tablenames = null) {
        $this->get_manager(); // Includes DDL exceptions classes ;-)
        if (is_array($sql)) {
            $sql = implode("\n;\n", $sql);
        }
        if (!$this->is_transaction_started()) {
            // It is better to do all or nothing, this helps with recovery...
            $sql = "BEGIN ISOLATION LEVEL SERIALIZABLE;\n$sql\n; COMMIT";
        }

        try {
            $this->query_start($sql, null, SQL_QUERY_STRUCTURE);
            $result = pg_query($this->pgsql, $sql);
            $this->query_end($result);
            pg_free_result($result);
        } catch (ddl_change_structure_exception $e) {
            if (!$this->is_transaction_started()) {
                $result = @pg_query($this->pgsql, "ROLLBACK");
                @pg_free_result($result);
            }
            $this->reset_caches($tablenames);
            throw $e;
        }

        $this->reset_caches($tablenames);
        return true;
    }

    /**
     * Execute general sql query. Should be used only when no other method suitable.
     * Do NOT use this to make changes in db structure, use database_manager methods instead!
     * @param string $sql query
     * @param array $params query parameters
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function execute($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        if (strpos($sql, ';') !== false) {
            throw new coding_exception('moodle_database::execute() Multiple sql statements found or bound parameters not used properly in query!');
        }

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Get a number of records as a moodle_recordset using a SQL statement.
     *
     * Since this method is a little less readable, use of it should be restricted to
     * code where it's possible there might be large datasets being returned.  For known
     * small datasets use get_records_sql - it leads to simpler code.
     *
     * The return type is like:
     * @see function get_recordset.
     *
     * @param string $sql the SQL select query to execute.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return moodle_recordset instance
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {

        list($limitfrom, $limitnum) = $this->normalise_limit_from_num($limitfrom, $limitnum);

        if ($limitnum) {
            $sql .= " LIMIT $limitnum";
        }
        if ($limitfrom) {
            $sql .= " OFFSET $limitfrom";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        return $this->create_recordset($result);
    }

    protected function create_recordset($result) {
        return new pgsql_native_moodle_recordset($result);
    }

    /**
     * Get a number of records as an array of objects using a SQL statement.
     *
     * Return value is like:
     * @see function get_records.
     *
     * @param string $sql the SQL select query to execute. The first column of this SELECT statement
     *   must be a unique value (usually the 'id' field), as it will be used as the key of the
     *   returned array.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return array of objects, or empty array if no records were found
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {

        list($limitfrom, $limitnum) = $this->normalise_limit_from_num($limitfrom, $limitnum);

        if ($limitnum) {
            $sql .= " LIMIT $limitnum";
        }
        if ($limitfrom) {
            $sql .= " OFFSET $limitfrom";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        // find out if there are any blobs
        $numfields = pg_num_fields($result);
        $blobs = array();
        for ($i = 0; $i < $numfields; $i++) {
            $type = pg_field_type($result, $i);
            if ($type == 'bytea') {
                $blobs[] = pg_field_name($result, $i);
            }
        }

        $rows = pg_fetch_all($result);
        pg_free_result($result);

        $return = array();
        if ($rows) {
            foreach ($rows as $row) {
                $id = reset($row);
                if ($blobs) {
                    foreach ($blobs as $blob) {
                        $row[$blob] = ($row[$blob] !== null ? pg_unescape_bytea($row[$blob]) : null);
                    }
                }
                if (isset($return[$id])) {
                    $colname = key($row);
                    debugging("Did you remember to make the first column something unique in your call to get_records? Duplicate value '$id' found in column '$colname'.", DEBUG_DEVELOPER);
                }
                $return[$id] = (object)$row;
            }
        }

        return $return;
    }

    /**
     * Selects records and return values (first field) as an array using a SQL statement.
     *
     * @param string $sql The SQL query
     * @param array $params array of sql parameters
     * @return array of values
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_fieldset_sql($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        $return = pg_fetch_all_columns($result, 0);

        if (pg_field_type($result, 0) == 'bytea') {
            foreach ($return as $key => $value) {
                $return[$key] = ($value === null ? $value : pg_unescape_bytea($value));
            }
        }

        pg_free_result($result);

        return $return;
    }

    /**
     * Insert new record into database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool $returnit return it of inserted record
     * @param bool $bulk true means repeated inserts expected
     * @param bool $customsequence true if 'id' included in $params, disables $returnid
     * @return bool|int true or new id
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }

        $returning = "";

        if ($customsequence) {
            if (!isset($params['id'])) {
                throw new coding_exception('moodle_database::insert_record_raw() id field must be specified if custom sequences used.');
            }
            $returnid = false;
        } else {
            if ($returnid) {
                $returning = "RETURNING id";
                unset($params['id']);
            } else {
                unset($params['id']);
            }
        }

        if (empty($params)) {
            throw new coding_exception('moodle_database::insert_record_raw() no fields found.');
        }

        $fields = implode(',', array_keys($params));
        $values = array();
        $i = 1;
        foreach ($params as $value) {
            $this->detect_objects($value);
            $values[] = "\$".$i++;
        }
        $values = implode(',', $values);

        $sql = "INSERT INTO {$this->prefix}$table ($fields) VALUES($values) $returning";
        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        if ($returning !== "") {
            $row = pg_fetch_assoc($result);
            $params['id'] = reset($row);
        }
        pg_free_result($result);

        if (!$returnid) {
            return true;
        }

        return (int)$params['id'];
    }

    /**
     * Insert a record into a table and return the "id" field if required.
     *
     * Some conversions and safety checks are carried out. Lobs are supported.
     * If the return ID isn't required, then this just reports success as true/false.
     * $data is an object containing needed data
     * @param string $table The database table to be inserted into
     * @param object $data A data object with values for one or more fields in the record
     * @param bool $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
     * @return bool|int true or new id
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        $dataobject = (array)$dataobject;

        $columns = $this->get_columns($table);
        if (empty($columns)) {
            throw new dml_exception('ddltablenotexist', $table);
        }

        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if ($field === 'id') {
                continue;
            }
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            $cleaned[$field] = $this->normalise_value($column, $value);
        }

        return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);

    }

    /**
     * Insert multiple records into database as fast as possible.
     *
     * Order of inserts is maintained, but the operation is not atomic,
     * use transactions if necessary.
     *
     * This method is intended for inserting of large number of small objects,
     * do not use for huge objects with text or binary fields.
     *
     * @since Moodle 2.7
     *
     * @param string $table  The database table to be inserted into
     * @param array|Traversable $dataobjects list of objects to be inserted, must be compatible with foreach
     * @return void does not return new record ids
     *
     * @throws coding_exception if data objects have different structure
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function insert_records($table, $dataobjects) {
        if (!is_array($dataobjects) and !($dataobjects instanceof Traversable)) {
            throw new coding_exception('insert_records() passed non-traversable object');
        }

        // PostgreSQL does not seem to have problems with huge queries.
        $chunksize = 500;
        if (!empty($this->dboptions['bulkinsertsize'])) {
            $chunksize = (int)$this->dboptions['bulkinsertsize'];
        }

        $columns = $this->get_columns($table, true);

        $fields = null;
        $count = 0;
        $chunk = array();
        foreach ($dataobjects as $dataobject) {
            if (!is_array($dataobject) and !is_object($dataobject)) {
                throw new coding_exception('insert_records() passed invalid record object');
            }
            $dataobject = (array)$dataobject;
            if ($fields === null) {
                $fields = array_keys($dataobject);
                $columns = array_intersect_key($columns, $dataobject);
                unset($columns['id']);
            } else if ($fields !== array_keys($dataobject)) {
                throw new coding_exception('All dataobjects in insert_records() must have the same structure!');
            }

            $count++;
            $chunk[] = $dataobject;

            if ($count === $chunksize) {
                $this->insert_chunk($table, $chunk, $columns);
                $chunk = array();
                $count = 0;
            }
        }

        if ($count) {
            $this->insert_chunk($table, $chunk, $columns);
        }
    }

    /**
     * Insert records in chunks, strict param types...
     *
     * Note: can be used only from insert_records().
     *
     * @param string $table
     * @param array $chunk
     * @param database_column_info[] $columns
     */
    protected function insert_chunk($table, array $chunk, array $columns) {
        $i = 1;
        $params = array();
        $values = array();
        foreach ($chunk as $dataobject) {
            $vals = array();
            foreach ($columns as $field => $column) {
                $params[] = $this->normalise_value($column, $dataobject[$field]);
                $vals[] = "\$".$i++;
            }
            $values[] = '('.implode(',', $vals).')';
        }

        $fieldssql = '('.implode(',', array_keys($columns)).')';
        $valuessql = implode(',', $values);

        $sql = "INSERT INTO {$this->prefix}$table $fieldssql VALUES $valuessql";
        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);
        pg_free_result($result);
    }

    /**
     * Import a record into a table, id field is required.
     * Safety checks are NOT carried out. Lobs are supported.
     *
     * @param string $table name of database table to be inserted into
     * @param object $dataobject A data object with values for one or more fields in the record
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function import_record($table, $dataobject) {
        $dataobject = (array)$dataobject;

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            $this->detect_objects($value);
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            $cleaned[$field] = $this->normalise_value($column, $value);
        }

        return $this->insert_record_raw($table, $cleaned, false, true, true);
    }

    /**
     * Update record in database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function update_record_raw($table, $params, $bulk=false) {
        $params = (array)$params;

        if (!isset($params['id'])) {
            throw new coding_exception('moodle_database::update_record_raw() id field must be specified.');
        }
        $id = $params['id'];
        unset($params['id']);

        if (empty($params)) {
            throw new coding_exception('moodle_database::update_record_raw() no fields found.');
        }

        $i = 1;

        $sets = array();
        foreach ($params as $field=>$value) {
            $this->detect_objects($value);
            $sets[] = "$field = \$".$i++;
        }

        $params[] = $id; // last ? in WHERE condition

        $sets = implode(',', $sets);
        $sql = "UPDATE {$this->prefix}$table SET $sets WHERE id=\$".$i;

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Update a record in a table
     *
     * $dataobject is an object containing needed data
     * Relies on $dataobject having a variable "id" to
     * specify the record to update
     *
     * @param string $table The database table to be checked against.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified.
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function update_record($table, $dataobject, $bulk=false) {
        $dataobject = (array)$dataobject;

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            $cleaned[$field] = $this->normalise_value($column, $value);
        }

        $this->update_record_raw($table, $cleaned, $bulk);

        return true;
    }

    /**
     * Set a single field in every table record which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $newfield the field to set.
     * @param string $newvalue the value to set the field to.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {

        if ($select) {
            $select = "WHERE $select";
        }
        if (is_null($params)) {
            $params = array();
        }
        list($select, $params, $type) = $this->fix_sql_params($select, $params);
        $i = count($params)+1;

        // Get column metadata
        $columns = $this->get_columns($table);
        $column = $columns[$newfield];

        $normalisedvalue = $this->normalise_value($column, $newvalue);

        $newfield = "$newfield = \$" . $i;
        $params[] = $normalisedvalue;
        $sql = "UPDATE {$this->prefix}$table SET $newfield $select";

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);

        return true;
    }

    /**
     * Delete one or more records from a table which match a particular WHERE clause, lobs not supported.
     *
     * @param string $table The database table to be checked against.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call (used to define the selection criteria).
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function delete_records_select($table, $select, array $params=null) {
        if ($select) {
            $select = "WHERE $select";
        }
        $sql = "DELETE FROM {$this->prefix}$table $select";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);

        return true;
    }

    /**
     * Returns 'LIKE' part of a query.
     *
     * @param string $fieldname usually name of the table column
     * @param string $param usually bound query parameter (?, :named)
     * @param bool $casesensitive use case sensitive search
     * @param bool $accensensitive use accent sensitive search (not all databases support accent insensitive)
     * @param bool $notlike true means "NOT LIKE"
     * @param string $escapechar escape char for '%' and '_'
     * @return string SQL code fragment
     */
    public function sql_like($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notlike = false, $escapechar = '\\') {
        if (strpos($param, '%') !== false) {
            debugging('Potential SQL injection detected, sql_like() expects bound parameters (? or :named)');
        }

        // postgresql does not support accent insensitive text comparisons, sorry
        if ($casesensitive) {
            $LIKE = $notlike ? 'NOT LIKE' : 'LIKE';
        } else {
            $LIKE = $notlike ? 'NOT ILIKE' : 'ILIKE';
        }
        return "$fieldname $LIKE $param ESCAPE '$escapechar'";
    }

    public function sql_bitxor($int1, $int2) {
        return '((' . $int1 . ') # (' . $int2 . '))';
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS INT) ';
    }

    public function sql_cast_char2real($fieldname, $text=false) {
        return " $fieldname::real ";
    }

    public function sql_concat() {
        $arr = func_get_args();
        $s = implode(' || ', $arr);
        if ($s === '') {
            return " '' ";
        }
        // Add always empty string element so integer-exclusive concats
        // will work without needing to cast each element explicitly
        return " '' || $s ";
    }

    public function sql_concat_join($separator="' '", $elements=array()) {
        for ($n=count($elements)-1; $n > 0 ; $n--) {
            array_splice($elements, $n, 0, $separator);
        }
        $s = implode(' || ', $elements);
        if ($s === '') {
            return " '' ";
        }
        return " $s ";
    }

    public function sql_regex_supported() {
        return true;
    }

    public function sql_regex($positivematch = true, $casesensitive = false) {
        if ($casesensitive) {
            return $positivematch ? '~' : '!~';
        } else {
            return $positivematch ? '~*' : '!~*';
        }
    }

    /**
     * Does this driver support tool_replace?
     *
     * @since Moodle 2.6.1
     * @return bool
     */
    public function replace_all_text_supported() {
        return true;
    }

    public function session_lock_supported() {
        return true;
    }

    /**
     * Obtain session lock
     * @param int $rowid id of the row with session record
     * @param int $timeout max allowed time to wait for the lock in seconds
     * @return bool success
     */
    public function get_session_lock($rowid, $timeout) {
        // NOTE: there is a potential locking problem for database running
        //       multiple instances of moodle, we could try to use pg_advisory_lock(int, int),
        //       luckily there is not a big chance that they would collide
        if (!$this->session_lock_supported()) {
            return;
        }

        parent::get_session_lock($rowid, $timeout);

        $timeoutmilli = $timeout * 1000;

        $sql = "SET statement_timeout TO $timeoutmilli";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            pg_free_result($result);
        }

        $sql = "SELECT pg_advisory_lock($rowid)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $start = time();
        $result = pg_query($this->pgsql, $sql);
        $end = time();
        try {
            $this->query_end($result);
        } catch (dml_exception $ex) {
            if ($end - $start >= $timeout) {
                throw new dml_sessionwait_exception();
            } else {
                throw $ex;
            }
        }

        if ($result) {
            pg_free_result($result);
        }

        $sql = "SET statement_timeout TO DEFAULT";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            pg_free_result($result);
        }
    }

    public function release_session_lock($rowid) {
        if (!$this->session_lock_supported()) {
            return;
        }
        if (!$this->used_for_db_sessions) {
            return;
        }

        parent::release_session_lock($rowid);

        $sql = "SELECT pg_advisory_unlock($rowid)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            pg_free_result($result);
        }
    }

    /**
     * Driver specific start of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function begin_transaction() {
        $this->savepointpresent = true;
        $sql = "BEGIN ISOLATION LEVEL READ COMMITTED; SAVEPOINT moodle_pg_savepoint";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
    }

    /**
     * Driver specific commit of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function commit_transaction() {
        $this->savepointpresent = false;
        $sql = "RELEASE SAVEPOINT moodle_pg_savepoint; COMMIT";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
    }

    /**
     * Driver specific abort of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function rollback_transaction() {
        $this->savepointpresent = false;
        $sql = "RELEASE SAVEPOINT moodle_pg_savepoint; ROLLBACK";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
    }

    /**
     * Helper function trimming (whitespace + quotes) any string
     * needed because PG uses to enclose with double quotes some
     * fields in indexes definition and others
     *
     * @param string $str string to apply whitespace + quotes trim
     * @return string trimmed string
     */
    private function trim_quotes($str) {
        return trim(trim($str), "'\"");
    }
}
