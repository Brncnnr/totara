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
 * Native mysqli class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\dml\sql;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/moodle_database.php');
require_once(__DIR__.'/mysqli_native_moodle_recordset.php');
require_once(__DIR__.'/mysqli_native_moodle_temptables.php');

/**
 * Native mysqli class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mysqli_native_moodle_database extends moodle_database {

    /** @var mysqli $mysqli */
    protected $mysqli = null;

    /** @var array cached server information */
    protected $serverinfo = null;

    /** @var bool flag indicating that transaction was auto-committed after DDL change */
    protected $autocommitted = false;

    /**
     * Attempt to create the database
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @return bool success
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function create_database($dbhost, $dbuser, $dbpass, $dbname, array $dboptions=null) {
        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        if (!empty($dboptions['dbsocket'])
                and (strpos($dboptions['dbsocket'], '/') !== false or strpos($dboptions['dbsocket'], '\\') !== false)) {
            $dbsocket = $dboptions['dbsocket'];
        } else {
            $dbsocket = ini_get('mysqli.default_socket');
        }
        if (empty($dboptions['dbport'])) {
            $dbport = (int)ini_get('mysqli.default_port');
        } else {
            $dbport = (int)$dboptions['dbport'];
        }
        // verify ini.get does not return nonsense
        if (empty($dbport)) {
            $dbport = 3306;
        }
        ob_start();
        $conn = new mysqli($dbhost, $dbuser, $dbpass, '', $dbport, $dbsocket); // Connect without db
        $dberr = ob_get_contents();
        ob_end_clean();
        $errorno = @$conn->connect_errno;

        if ($errorno !== 0) {
            throw new dml_connection_exception($dberr);
        }

        // Totara: always use utf8 by default, admins must set other collations in config.php file.
        $charset = 'utf8';
        $collation = 'utf8_unicode_ci'; // Developers need to use  _bin encoding to pass all phpunit tests.
        if (isset($dboptions['dbcollation'])) {
            if (strpos($dboptions['dbcollation'], 'utf8mb4_') === 0) {
                $charset = 'utf8mb4';
                $collation = $dboptions['dbcollation'];
            } else if (strpos($dboptions['dbcollation'], 'utf8mb3_') === 0) {
                $charset = 'utf8mb3';
                $collation = $dboptions['dbcollation'];
            } else if (strpos($dboptions['dbcollation'], 'utf8_') === 0) {
                $collation = $dboptions['dbcollation'];
            }
        }

        if (!$this->is_valid_db_name($dbname)) {
            throw new dml_exception('cannotcreatedbnamemysql');
        }

        $result = $conn->query("CREATE DATABASE $dbname DEFAULT CHARACTER SET $charset DEFAULT COLLATE ".$collation);

        $conn->close();

        if (!$result) {
            throw new dml_exception('cannotcreatedb');
        }

        return true;
    }

    /**
     * We validate the database name according to the MySQL rules for unquoted identifiers.
     * This is not generally strictly enforced, as admins can just put any name in config.php directly.
     * It's done here anyway to avoid potential problems when the name is used unquoted elsewhere.
     *
     * @param string $db_name
     * @return bool
     */
    public function is_valid_db_name(string $db_name): bool {
        return (bool)preg_match('/^[\w$\x{0080}-\x{FFFF}]{1,64}$/u', $db_name);
    }

    /**
     * Detects if all needed PHP stuff installed.
     * Note: can be used before connect()
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('mysqli')) {
            return get_string('mysqliextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    /**
     * Returns database family type - describes SQL dialect
     * Note: can be used before connect()
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'mysql';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysqli, pgsql, oci, mssql, sqlsrv
     */
    protected function get_dbtype() {
        return 'mysqli';
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
     * Returns the current MySQL db engine.
     *
     * This is an ugly workaround for MySQL default engine problems,
     * Moodle is designed to work best on ACID compliant databases
     * with full transaction support. Do not use MyISAM.
     *
     * @return string or null MySQL engine name
     */
    public function get_dbengine() {
        if (isset($this->dboptions['dbengine'])) {
            return $this->dboptions['dbengine'];
        }

        if ($this->external) {
            return null;
        }

        $engine = null;

        // Look for current engine of our config table (the first table that gets created),
        // so that we create all tables with the same engine.
        $sql = "SHOW TABLE STATUS WHERE name = '{$this->prefix}config'";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $engine = $rec['Engine'];
        }
        $result->close();

        if ($engine) {
            // Cache the result to improve performance.
            $this->dboptions['dbengine'] = $engine;
            return $engine;
        }

        // Get the default database engine.
        $sql = "SELECT @@default_storage_engine engine";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $engine = $rec['engine'];
        }
        $result->close();

        if ($engine === 'MyISAM') {
            // we really do not want MyISAM for Moodle, InnoDB or XtraDB is a reasonable defaults if supported
            $sql = "SHOW STORAGE ENGINES";
            $this->query_start($sql, NULL, SQL_QUERY_AUX);
            $result = $this->mysqli->query($sql);
            $this->query_end($result);
            $engines = array();
            while ($res = $result->fetch_assoc()) {
                if ($res['Support'] === 'YES' or $res['Support'] === 'DEFAULT') {
                    $engines[$res['Engine']] = true;
                }
            }
            $result->close();
            if (isset($engines['InnoDB'])) {
                $engine = 'InnoDB';
            }
            if (isset($engines['XtraDB'])) {
                $engine = 'XtraDB';
            }
        }

        // Cache the result to improve performance.
        $this->dboptions['dbengine'] = $engine;
        return $engine;
    }

    /**
     * Get expected database charset for current db collation.
     *
     * NOTE: only utf8 and utf8mb4 charsets are supported,
     *       so watch out if used for external database connections.
     *
     * @return string
     */
    public function get_charset() {
        $dbcollation = $this->get_dbcollation();
        if (strpos($dbcollation, 'utf8mb4_') === 0) {
            return 'utf8mb4';
        } else if (strpos($dbcollation, 'utf8mb3_') === 0) {
            // MariaDB 10.6 changed utf8mb3 from being an alias to utf8 to actual encoding see https://jira.mariadb.org/browse/MDEV-8334
            return 'utf8mb3';
        } else {
            return 'utf8';
        }
    }

    /**
     * Returns the current MySQL db collation.
     *
     * The order of detection is:
     *  1/ $CFG->dboptions['dbcollation'] value
     *  2/ collation of the 'config' table
     *  3/ default collation of current database
     *  4/ default server collation
     *
     * NOTE: the results are cached in $this->dboptions['dbcollation']
     *
     * @return string MySQL collation name
     */
    public function get_dbcollation() {
        if (isset($this->dboptions['dbcollation'])) {
            return $this->dboptions['dbcollation'];
        }

        $collation = null;

        if ($this->external) {
            // Totara: Get the default database collation,
            // if it is not utf8 compatible things may fail pretty badly - bad luck.
            $sql = "SELECT @@collation_database";
            $this->query_start($sql, NULL, SQL_QUERY_AUX);
            $result = $this->mysqli->query($sql);
            $this->query_end($result);
            if ($rec = $result->fetch_assoc()) {
                $collation = $rec['@@collation_database'];
            }
            $result->close();
            $this->dboptions['dbcollation'] = $collation;
            return $collation;
        }

        // Look for current collation of our config table (the first table that gets created),
        // so that we create all tables with the same collation.
        $sql = "SHOW TABLE STATUS WHERE Name = '{$this->prefix}config'";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $collation = $rec['Collation'];
            if (strpos($collation, 'utf8') !== 0) {
                // We cannot continue, admin needs to fix the database!
                throw new moodle_exception("Unsupported collation '{$collation}' detected in the config table!");
            }
        }
        $result->close();

        if (!$collation && $this->get_dbvendor() === 'mysql' && version_compare($this->get_server_info()['version'], '8.0', '>')) {
            // Only accent and case sensitive collations are fully supported,
            // admins do need to upgrade to MySQL 8. Really!
            $collation = 'utf8mb4_0900_as_cs';
        }

        if (!$collation && ((defined('PHPUNIT_UTIL') && PHPUNIT_UTIL) || (defined('BEHAT_UTIL') && BEHAT_UTIL))) {
            // Tests expect accent and case sensitive collations.
            $collation = 'utf8mb4_bin';
        }

        if (!$collation) {
            // Get the default database collation, but only if using utf8 or utf8mb4 compatible collations.
            $sql = "SELECT @@collation_database";
            $this->query_start($sql, NULL, SQL_QUERY_AUX);
            $result = $this->mysqli->query($sql);
            $this->query_end($result);
            if ($rec = $result->fetch_assoc()) {
                if (strpos($rec['@@collation_database'], 'utf8') === 0) {
                    $collation = $rec['@@collation_database'];
                }
            }
            $result->close();
        }

        if (!$collation) {
            // We want only utf8 or utf8mb4 compatible collations.
            $collation = null;
            $sql = "SHOW COLLATION WHERE Collation LIKE 'utf8%'";
            $this->query_start($sql, NULL, SQL_QUERY_AUX);
            $result = $this->mysqli->query($sql);
            $this->query_end($result);
            while ($res = $result->fetch_assoc()) {
                $collation = $res['Collation'];
                if (strtoupper($res['Default']) === 'YES') {
                    $collation = $res['Collation'];
                    break;
                }
            }
            $result->close();
        }

        if (!$collation) {
            // Totara: we need to always return something valid so that we can perform installation.
            $collation = 'utf8mb4_unicode_ci';
        }

        // Cache the result to improve performance.
        $this->dboptions['dbcollation'] = $collation;
        return $collation;
    }

    /**
     * Get the row format from the database schema.
     *
     * @param string $table
     * @return string row_format name or null if not known or table does not exist.
     */
    public function get_row_format($table) {
        $rowformat = null;
        $table = $this->mysqli->real_escape_string($table);
        $sql = "SHOW TABLE STATUS WHERE Name = '{$this->prefix}$table'";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $rowformat = $rec['Row_format'];
        }
        $result->close();

        return $rowformat;
    }

    /**
     * Get the InnoDB file format used in database.
     *
     * @return string returns innodb_file_format
     */
    public function get_file_format() {
        $info = $this->get_server_info();
        if ($this->get_dbvendor() === 'mysql' and version_compare($info['version'], '8.0', '>')) {
            // Totara: MySQL 8 supports only new file formats.
            return 'Barracuda';
        }
        if ($this->get_dbvendor() === 'mariadb' and version_compare($info['version'], '10.3', '>')) {
            // Totara: MariaDB 10.3 supports only new file formats.
            return 'Barracuda';
        }

        $fileformat = null;
        $sql = "SHOW VARIABLES LIKE 'innodb_file_format'";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $fileformat = $rec['Value'];
        }
        $result->close();

        return $fileformat;
    }

    /**
     * Check the database to see if innodb_file_per_table is on.
     *
     * @return bool True if on otherwise false.
     */
    public function is_file_per_table_enabled() {
        $info = $this->get_server_info();
        if ($this->get_dbvendor() === 'mysql' and version_compare($info['version'], '8.0' ,'>')) {
            // Totara: MySQL 8 supports only new file formats.
            return true;
        }

        // NOTE: MariaDB 10.3.1dev did not remove this setting yet, so keep checking it for now.

        if ($filepertable = $this->get_record_sql("SHOW VARIABLES LIKE 'innodb_file_per_table'")) {
            if ($filepertable->value == 'ON') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check the database to see if innodb_large_prefix is on.
     *
     * @return bool True if on otherwise false.
     */
    public function is_large_prefix_enabled() {
        $info = $this->get_server_info();
        if ($this->get_dbvendor() === 'mysql' and version_compare($info['version'], '8.0', '>')) {
            // Totara: MySQL 8 supports only new file formats.
            return true;
        }

        if ($this->get_dbvendor() === 'mariadb' and version_compare($info['version'], '10.3', '>')) {
            // Totara: MariaDB 10.3 supports only new file formats.
            return true;
        }

        if ($largeprefix = $this->get_record_sql("SHOW VARIABLES LIKE 'innodb_large_prefix'")) {
            if ($largeprefix->value == 'ON') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('nativemysqli', 'install');
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('nativemysqlihelp', 'install');
    }

    /**
     * Returns the language used for full text search.
     *
     * NOTE: admin must run admin/cli/fts_rebuild_indexes.php after change of lang!
     *
     * @since Totara 12
     *
     * @return string
     */
    public function get_ftslanguage() {
        $dbcollation = $this->get_dbcollation();
        if (!empty($this->dboptions['ftslanguage'])) {
            $sc = $this->dboptions['ftslanguage'];
            // Make sure that the charset matches!
            if (strpos($sc, 'utf8mb4_') === 0 and strpos($dbcollation, 'utf8mb4_') === 0) {
                return $sc;
            }
            if (strpos($sc, 'utf8mb3_') === 0 and strpos($dbcollation, 'utf8mb3_') === 0) {
                return $sc;
            }
            if (strpos($sc, 'utf8_') === 0 and strpos($dbcollation, 'utf8_') === 0) {
                return $sc;
            }
        }
        // Guess the right value, we expect they are using a variant of _cs_as.
        if ($dbcollation === 'utf8_bin') {
            return 'utf8_unicode_ci';
        }
        if ($dbcollation === 'utf8mb4_bin') {
            return 'utf8mb4_unicode_ci';
        }
        if (substr($dbcollation, -3) === '_ci') {
            // This is not a supported collation, but anyway.
            return $dbcollation;
        }
        if (substr($dbcollation, -6) === '_as_cs') {
            return substr($dbcollation, 0, -6) . '_ai_ci';
        }
        if (substr($dbcollation, -3) === '_cs') {
            return substr($dbcollation, 0, -3) . '_ci';
        }
        // No more guessing, use the same collation.
        return $dbcollation;
    }

    /**
     * Diagnose database and tables, this function is used
     * to verify database and driver settings, db engine types, etc.
     *
     * @return string null means everything ok, string means problem found.
     */
    public function diagnose() {
        $sloppymyisamfound = false;
        $prefix = str_replace('_', '\\_', $this->prefix);
        $sql = "SHOW TABLE STATUS WHERE Name LIKE BINARY '$prefix%' AND Engine = 'MyISAM'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($result) {
            if ($result->num_rows) {
                $sloppymyisamfound = true;
            }
            $result->close();
        }

        if ($sloppymyisamfound) {
            return get_string('myisamproblem', 'error');
        } else {
            return null;
        }
    }

    /**
     * Connect to db
     * Must be called before other methods.
     * @param string $dbhost The database host.
     * @param string $dbuser The database username.
     * @param string $dbpass The database username's password.
     * @param string $dbname The name of the database being connected to.e
     * @param mixed $prefix string means moodle db prefix, false used for external databases where prefix not used
     * @param array $dboptions driver specific options
     * @return bool success
     */
    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        if (!$this->external and strlen($prefix) > 16) {
            // Max prefix length is 64 - 48 = 16 characters,
            // see https://dev.mysql.com/doc/refman/5.7/en/identifier-length.html
            $a = (object)array('dbfamily' => 'mysql', 'maxlength' => 16);
            throw new dml_exception('prefixtoolong', $a);
        }

        // Forcing error reporting mode to OFF, which is no longer the default on PHP 8.1
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->store_settings($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);

        // dbsocket is used ONLY if host is NULL or 'localhost',
        // you can not disable it because it is always tried if dbhost is 'localhost'
        if (!empty($this->dboptions['dbsocket'])
                and (strpos($this->dboptions['dbsocket'], '/') !== false or strpos($this->dboptions['dbsocket'], '\\') !== false)) {
            $dbsocket = $this->dboptions['dbsocket'];
        } else {
            $dbsocket = ini_get('mysqli.default_socket');
        }
        if (empty($this->dboptions['dbport'])) {
            $dbport = (int)ini_get('mysqli.default_port');
        } else {
            $dbport = (int)$this->dboptions['dbport'];
        }
        // verify ini.get does not return nonsense
        if (empty($dbport)) {
            $dbport = 3306;
        }
        if ($dbhost and !empty($this->dboptions['dbpersist'])) {
            $dbhost = "p:$dbhost";
        }

        // Totara: use real_connect() so that we can specify all options.
        $this->mysqli = mysqli_init();
        if (!$this->mysqli) {
            throw new dml_connection_exception('cannot init mysqli');
        }

        // Totara: add database communication encryption support
        if (!empty($this->dboptions['ssl_key'])
            or !empty($this->dboptions['ssl_cert'])
            or !empty($this->dboptions['ssl_ca'])
            or !empty($this->dboptions['ssl_capath'])
            or !empty($this->dboptions['ssl_cipher'])
        ) {
            $this->mysqli->ssl_set(
                $this->dboptions['ssl_key'] ?? null,
                $this->dboptions['ssl_cert'] ?? null,
                $this->dboptions['ssl_ca'] ?? null,
                $this->dboptions['ssl_capath'] ?? null,
                $this->dboptions['ssl_cipher'] ?? null
            );
        }
        if (!empty($this->dboptions['ssl_verify_server_cert'])) {
            $this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        }
        $flags = 0;
        if (!empty($this->dboptions['client_ssl'])) {
            $flags = $flags | MYSQLI_CLIENT_SSL;
        }
        if (!empty($this->dboptions['client_dont_verify_server_cert'])) {
            $flags = $flags | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
        }
        if ($flags === 0) {
            $flags = null;
        }

        // Always log errors instead of showing them to users during MySQL connection.
        $orig_display_errors = null;
        if (ini_get_bool('display_errors')) {
            $orig_display_errors = '1';
            ini_set('display_errors', '0');
        }
        $orig_log_errors = null;
        if (!ini_get_bool('log_errors')) {
            $orig_log_errors = '0';
            ini_set('log_errors', '1');
        }

        if (is_null($flags)) {
            $this->mysqli->real_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsocket);
        } else {
            $this->mysqli->real_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsocket, $flags);
        }
        // Set original error handling values if changed.
        if ($orig_display_errors !== null) {
            ini_set('display_errors', $orig_display_errors);
        }
        if ($orig_log_errors !== null) {
            ini_set('log_errors', $orig_log_errors);
        }

        if ($this->mysqli->connect_errno !== 0) {
            $dberr = $this->mysqli->connect_error;
            $this->mysqli = null;
            throw new dml_connection_exception($dberr);
        }

        // Disable logging until we are fully setup.
        $this->query_log_prevent();

        $charset = $this->get_charset();
        $this->query_start("--set_charset()", null, SQL_QUERY_AUX);
        $this->mysqli->set_charset($charset);
        $this->query_end(true);

        // mysqli::set_charset doesn't properly handle connection collation see https://www.php.net/manual/en/mysqli.set-charset.php#121067
        $collation = $this->get_dbcollation();
        $sql = "SET NAMES $charset COLLATE $collation";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $this->mysqli->query($sql);
        $this->query_end(true);

        // Make sure correct driver was selected
        // because MariaDB is NOT a drop-in replacement for recent MySQL!
        if (!$this->external) {
            $si = $this->get_server_info();
            if ($this->get_dbvendor() === 'mysql') {
                if (version_compare($si['version'], '10.0', '>')) {
                    $this->dispose();
                    throw new dml_exception('dbusemariadb');
                }
            } else if ($this->get_dbvendor() === 'mariadb') {
                if (substr($si['version'], 0, 2) === '8.' || substr($si['version'], 0, 4) === '5.7.' ) {
                    $this->dispose();
                    throw new dml_exception('dbusemysql');
                }
            }
        }

        // Totara: Configuration related to specific MySQL versions.
        $this->version_specific_support();

        // Enforce strict mode for the session and column quoting. That guaranties
        // standard behaviour under some situations, avoiding some MySQL nasty
        // habits like truncating data or performing some transparent cast losses.
        // With strict mode enforced, Moodle DB layer will be consistently throwing
        // the corresponding exceptions as expected.
        $sql = "SET SESSION sql_mode = 'STRICT_ALL_TABLES,ANSI_QUOTES'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        // Totara: make sure the group_concat can work with large strings.
        $sql = "SELECT @@group_concat_max_len";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        if ($rec = $result->fetch_assoc()) {
            if ($rec['@@group_concat_max_len'] < 131072) {
                $this->query_end(true);
                $sql = "SET SESSION group_concat_max_len = 131072";
                $this->query_start($sql, null, SQL_QUERY_AUX);
                $this->mysqli->query($sql);
            }
        }
        $this->query_end(true);

        // We can enable logging now.
        $this->query_log_allow();

        // Connection stabilised and configured, going to instantiate the temptables controller
        $this->temptables = new mysqli_native_moodle_temptables($this);

        return true;
    }

    /**
     * Add configuration for specific versions of MySQL
     */
    protected function version_specific_support() {
        $version = $this->get_server_info()['version'];

        // MySQL Bug https://bugs.mysql.com/bug.php?id=84812
        if (version_compare($version, '5.7.21') < 0
            || version_compare($version, '8.0.0') >= 0 && version_compare($version, '8.0.4') < 0) {
            $sql = "SET SESSION optimizer_switch='derived_merge=off'";
            $this->query_start($sql, null, SQL_QUERY_AUX);
            $this->mysqli->query($sql);
            $this->query_end(true);
        }
    }

    /**
     * Close database connection and release all resources
     * and memory (especially circular memory references).
     * Do NOT use connect() again, create a new instance if needed.
     */
    public function dispose() {
        parent::dispose(); // Call parent dispose to write/close session and other common stuff before closing connection
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }

    /**
     * Returns database server info array
     * @return array Array containing 'description' and 'version' info
     */
    public function get_server_info() {
        if (!$this->mysqli) {
            return null;
        }

        if (isset($this->serverinfo)) {
            return $this->serverinfo;
        }

        $version = $this->mysqli->server_info;
        // Sometimes the version reported by client is not correct, such as on Azure MariaDB,
        // other setups with MySQL routers might be affected too.
        $sql = "SELECT VERSION()";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($rec = $result->fetch_assoc()) {
            $version = reset($rec);
        }
        if (preg_match('/^5\.5\.5-(10\..+)-MariaDB/i', $version, $matches)) {
            // Legacy MariaDB backwards compatibility hack.
            $version = $matches[1];
        }
        // Normalise the version, no fancy suffixes here.
        if (preg_match('/^\d+(\.\d+)+/i', $version, $matches)) {
            $version = $matches[0];
        }

        $this->serverinfo = array(
            'description' => $this->mysqli->server_info,
            'version' => $version,
        );

        return $this->serverinfo;
    }

    /**
     * Returns supported query parameter types
     * @return int bitmask of accepted SQL_PARAMS_*
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_QM;
    }

    /**
     * Returns last error reported by database engine.
     * @return string error message
     */
    public function get_last_error() {
        return $this->mysqli->error;
    }

    /**
     * Return tables in database WITHOUT current prefix
     * @param bool $usecache if true, returns list of cached tables.
     * @return array of table names in lowercase and without prefix
     */
    public function get_tables($usecache=true) {
        if ($usecache and $this->tables !== null) {
            return $this->tables;
        }
        $this->tables = array();
        $prefix = str_replace('_', '\\_', $this->prefix);
        $sql = "SHOW TABLES LIKE '$prefix%'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        $len = strlen($this->prefix);
        if ($result) {
            while ($arr = $result->fetch_assoc()) {
                $tablename = reset($arr);
                $tablename = substr($tablename, $len);
                $this->tables[$tablename] = $tablename;
            }
            $result->close();
        }

        // Add the currently available temptables
        $this->tables = array_merge($this->tables, $this->temptables->get_temptables());
        return $this->tables;
    }

    /**
     * @inheritDoc
     */
    public function get_primary_keys(string $table): array {
        $keys = [];

        $sql = "SHOW KEYS FROM {{$table}} WHERE Key_name = 'PRIMARY'";

        $rows = $this->get_records_sql_unkeyed($sql);

        foreach ($rows as $row) {
            $keys[$row->column_name] = [
                'column_name' => $row->column_name
            ];
        }

        return $keys;
    }

    /**
     * Return table indexes - everything lowercased.
     * @param string $table The table we want to get indexes from.
     * @return array An associative array of indexes containing 'unique' flag and 'columns' being indexed
     */
    public function get_indexes($table) {
        $indexes = array();
        $sql = "SHOW INDEXES FROM {$this->prefix}$table";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        try {
            $this->query_end($result);
        } catch (dml_read_exception $e) {
            return $indexes; // table does not exist - no indexes...
        }
        if ($result) {
            while ($res = $result->fetch_object()) {
                if ($res->Key_name === 'PRIMARY') {
                    continue;
                }
                if (!isset($indexes[$res->Key_name])) {
                    $indexes[$res->Key_name] = array('unique'=>empty($res->Non_unique), 'columns'=>array(), 'fulltextsearch'=>($res->Index_type==='FULLTEXT'));
                }
                $indexes[$res->Key_name]['columns'][$res->Seq_in_index-1] = $res->Column_name;
            }
            $result->close();
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

        $sql = "SHOW COLUMNS FROM {$this->prefix}{$table}";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end(true); // Don't want to throw anything here ever. MDL-30147

        if ($result === false) {
            return array();
        }

        while ($rawcolumn = $result->fetch_assoc()) {
            $info = $this->get_column_info((object)$rawcolumn);
            $structure[$info->name] = new database_column_info($info);
        }
        $result->close();

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
     * Returns moodle column info for raw column from information schema.
     * @param stdClass $rawcolumn
     * @return stdClass standardised colum info
     */
    private function get_column_info(stdClass $rawcolumn) {
        preg_match('/^([a-z]+)(\((.+)\))?( unsigned)?/', $rawcolumn->Type, $matches);
        $type = strtolower($matches[1]);
        $precision = isset($matches[3]) ? $matches[3] : '';
        $unsigned = isset($matches[4]);

        $info = new stdClass();
        $info->name           = $rawcolumn->Field;
        $info->type           = $type;
        $info->meta_type      = $this->mysqltype2moodletype($type);
        // Totara: MariaDB 10.2.7 stared to add quotes around strings the same way as PG, but unfortunately it uses NULL string incorrectly there.
        if ($rawcolumn->Default === 'NULL' or $rawcolumn->Default === null) {
            $info->default_value  = null;
            $info->has_default = false;
        } else {
            $info->default_value = trim($rawcolumn->Default, "'");
            $info->has_default = true;
        }
        $info->not_null       = ($rawcolumn->Null === 'NO');
        $info->primary_key    = ($rawcolumn->Key === 'PRI');
        $info->binary         = false;
        $info->unsigned       = null;
        $info->auto_increment = false;
        $info->unique         = null;
        $info->scale          = null;

        if ($info->meta_type === 'C') {
            $info->max_length = $precision;
            $info->max_length = $precision;

        } else if ($info->meta_type === 'I') {
            if ($info->primary_key) {
                $info->meta_type = 'R';
                $info->unique    = true;
            }
            // Return number of decimals, not bytes here.
            if ($type === 'bigint') {
                $maxlength = 18;
            } else if ($type === 'int' or $type === 'integer') {
                $maxlength = 9;
            } else if ($type === 'mediumint') {
                $maxlength = 6;
            } else if ($type === 'smallint') {
                $maxlength = 4;
            } else if ($type === 'tinyint') {
                $maxlength = 2;
            } else {
                // This should not happen.
                $maxlength = 2;
            }
            // It is possible that display precision is different from storage type length,
            // always use the smaller value to make sure our data fits.
            if ($precision > 0 && $precision < $maxlength) {
                $info->max_length = $precision;
            } else {
                $info->max_length = $maxlength;
            }

            $info->unsigned      = $unsigned;
            $info->auto_increment= (strpos($rawcolumn->Extra, 'auto_increment') !== false);

        } else if ($info->meta_type === 'N') {
            $parts = explode(',', $precision);
            $info->max_length    = (int)$parts[0];
            $info->scale         = isset($parts[1]) ? (int)$parts[1] : 0;
            $info->unsigned      = $unsigned;

        } else if ($info->meta_type === 'X') {
            // We do not really know what are the limits.
            $info->max_length    = -1;
            $info->primary_key   = false;

        } else if ($info->meta_type === 'B') {
            $info->max_length    = -1;
            $info->primary_key   = false;
            $info->binary        = true;
        }

        return $info;
    }

    /**
     * Normalise column type.
     * @param string $mysql_type
     * @return string one character
     * @throws dml_exception
     */
    private function mysqltype2moodletype($mysql_type) {
        $type = null;

        switch (mb_strtoupper($mysql_type, 'UTF-8')) {
            case 'BIT':
                $type = 'L';
                break;

            case 'TINYINT':
            case 'SMALLINT':
            case 'MEDIUMINT':
            case 'INT':
            case 'INTEGER':
            case 'BIGINT':
                $type = 'I';
                break;

            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                $type = 'N';
                break;

            case 'CHAR':
            case 'ENUM':
            case 'SET':
            case 'VARCHAR':
                $type = 'C';
                break;

            case 'TINYTEXT':
            case 'TEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                $type = 'X';
                break;

            case 'BINARY':
            case 'VARBINARY':
            case 'BLOB':
            case 'TINYBLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
                $type = 'B';
                break;

            case 'DATE':
            case 'TIME':
            case 'DATETIME':
            case 'TIMESTAMP':
            case 'YEAR':
                $type = 'D';
                break;
        }

        if (!$type) {
            throw new dml_exception('invalidmysqlnativetype', $mysql_type);
        }
        return $type;
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

        } else if ($value === '') {
            if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                $value = 0; // prevent '' problems in numeric fields
            }
        // Any float value being stored in varchar or text field is converted to string to avoid
        // any implicit conversion by MySQL
        } else if (is_float($value) and ($column->meta_type == 'C' or $column->meta_type == 'X')) {
            $value = core_text::float2str($value);
        }
        return $value;
    }

    /**
     * Is this database compatible with utf8?
     * @return bool
     */
    public function setup_is_unicodedb() {
        // All new tables are created with this collation, we just have to make sure it is utf8 compatible,
        // if config table already exists it has this collation too.
        $collation = $this->get_dbcollation();
        $charset = $this->get_charset();

        $sql = "SHOW COLLATION WHERE Collation ='$collation' AND Charset = '$charset'";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($result->fetch_assoc()) {
            $return = true;
        } else {
            $return = false;
        }
        $result->close();

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function setup_supports_four_byte_character_set() {
        $charset = strtolower($this->get_charset());

        // All the following character sets support four byte characters
        if ($charset == 'utf8mb4'
            || $charset == 'utf16'
            || $charset == 'utf16le'
            || $charset == 'utf32'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string|array $sql query
     * @param array|null $tablenames an array of xmldb table names affected by this request.
     * @return bool true
     * @throws ddl_change_structure_exception A DDL specific exception is thrown for any errors.
     */
    public function change_database_structure($sql, $tablenames = null) {
        if ($this->is_transaction_started()) {
            debugging('Transactions are not compatible with DDL operations in MySQL and MS SQL Server', DEBUG_DEVELOPER);
            $this->autocommitted = true;
        }
        $this->get_manager(); // Includes DDL exceptions classes ;-)
        if (is_array($sql)) {
            $sql = implode("\n;\n", $sql);
        }

        try {
            $this->query_start($sql, null, SQL_QUERY_STRUCTURE);
            $result = $this->mysqli->multi_query($sql);
            if ($result === false) {
                $this->query_end(false);
            }
            while ($this->mysqli->more_results()) {
                $result = $this->mysqli->next_result();
                if ($result === false) {
                    $this->query_end(false);
                }
            }
            $this->query_end(true);
        } catch (ddl_change_structure_exception $e) {
            while (@$this->mysqli->more_results()) {
                @$this->mysqli->next_result();
            }
            $this->reset_caches($tablenames);
            throw $e;
        }

        $this->reset_caches($tablenames);
        return true;
    }

    /**
     * Very ugly hack which emulates bound parameters in queries
     * because prepared statements do not use query cache.
     */
    protected function emulate_bound_params($sql, array $params=null) {
        if (empty($params)) {
            return $sql;
        }
        // ok, we have verified sql statement with ? and correct number of params
        $parts = array_reverse(explode('?', $sql));
        $return = array_pop($parts);
        foreach ($params as $param) {
            if (is_bool($param)) {
                $return .= (int)$param;
            } else if (is_null($param)) {
                $return .= 'NULL';
            } else if (is_number($param)) {
                $return .= "'".$param."'"; // we have to always use strings because mysql is using weird automatic int casting
            } else if (is_float($param)) {
                $return .= core_text::float2str($param);
            } else {
                $param = $this->mysqli->real_escape_string($param);
                $return .= "'$param'";
            }
            $return .= array_pop($parts);
        }
        return $return;
    }

    /**
     * Execute general sql query. Should be used only when no other method suitable.
     * Do NOT use this to make changes in db structure, use database_manager methods instead!
     * @param string|sql $sql query
     * @param array $params query parameters
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function execute($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        if (strpos($sql, ';') !== false) {
            throw new coding_exception('moodle_database::execute() Multiple sql statements found or bound parameters not used properly in query!');
        }

        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        if ($result === true) {
            return true;

        } else {
            $result->close();
            return true;
        }
    }

    /**
     * Get a number of records as a moodle_recordset using a SQL statement.
     *
     * This method is intended for queries with reasonable result size only,
     * @see moodle_database::get_huge_recordset_sql() if the results might not fit into memory.
     *
     * The result may be used as iterator in foreach(), if you want to obtain
     * an array with incremental numeric keys @see moodle_recordset::to_array()
     *
     * @param string|sql $sql the SQL select query to execute.
     * @param array|null $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return moodle_recordset A moodle_recordset instance.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        list($limitfrom, $limitnum) = $this->normalise_limit_from_num($limitfrom, $limitnum);

        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitfrom, $limitnum";
        }

        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        // no MYSQLI_USE_RESULT here, it would block write ops on affected tables
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        return $this->create_recordset($result);
    }

    /**
     * Get all records from a table.
     *
     * This method works around potential memory problems and may improve performance,
     * this method may block access to table until the recordset is closed.
     *
     * @param string $table Name of database table.
     * @return moodle_recordset A moodle_recordset instance {@link function get_recordset}.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function export_table_recordset($table) {
        $sql = $this->fix_table_names("SELECT * FROM {{$table}}");

        $this->query_start($sql, array(), SQL_QUERY_SELECT);
        // MYSQLI_STORE_RESULT may eat all memory for large tables, unfortunately MYSQLI_USE_RESULT blocks other queries.
        $result = $this->mysqli->query($sql, MYSQLI_USE_RESULT);
        $this->query_end($result);

        return $this->create_recordset($result);
    }

    protected function create_recordset($result) {
        return new mysqli_native_moodle_recordset($result);
    }

    /**
     * Get a number of records as an array of objects using a SQL statement.
     *
     * Return value is like:
     * @see function get_records.
     *
     * @param string|sql $sql the SQL select query to execute. The first column of this SELECT statement
     *   must be a unique value (usually the 'id' field), as it will be used as the key of the
     *   returned array.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @param bool $unique_id Require the first column to be unique and key the array by it, otherwise return an array with sequential keys
     * @return array of objects, or empty array if no records were found
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    protected function get_records_sql_raw($sql, array $params=null, $limitfrom=0, $limitnum=0, bool $unique_id = true): array {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        list($limitfrom, $limitnum) = $this->normalise_limit_from_num($limitfrom, $limitnum);

        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitfrom, $limitnum";
        }

        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        $return = array();

        while($row = $result->fetch_assoc()) {
            $row = array_change_key_case($row, CASE_LOWER);
            $this->add_row_to_result($row, $return, $unique_id);
        }
        $result->close();

        return $return;
    }

    /**
     * Selects records and return values (first field) as an array using a SQL statement.
     *
     * @param string|sql $sql The SQL query
     * @param array $params array of sql parameters
     * @return array of values
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_fieldset_sql($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        $return = array();

        while($row = $result->fetch_assoc()) {
            $return[] = reset($row);
        }
        $result->close();

        return $return;
    }

    /**
     * Test whether a SQL SELECT statement returns any records.
     *
     * This function returns true if the SQL statement executes
     * without any errors and returns at least one record.
     *
     * @param string|sql $sql The SQL statement to execute.
     * @param array $params array of sql parameters
     * @return bool true if the SQL executes without errors and returns at least one record.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function record_exists_sql($sql, array $params=null) {
        // MySQL 5.7 and MariaDB 10.3 require the FROM clause.
        if ($sql instanceof sql) {
            $sql = $sql->prepend('SELECT 1 FROM DUAL WHERE EXISTS (')->append(')');
        } else {
            $sql = "SELECT 1 FROM DUAL WHERE EXISTS ($sql)";
        }
        return (bool)$this->get_field_sql($sql, $params);
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

        if ($customsequence) {
            if (!isset($params['id'])) {
                throw new coding_exception('moodle_database::insert_record_raw() id field must be specified if custom sequences used.');
            }
            $returnid = false;
        } else {
            unset($params['id']);
        }

        if (empty($params)) {
            throw new coding_exception('moodle_database::insert_record_raw() no fields found.');
        }

        $fields = array();
        foreach ($params as $field => $value) {
            $fields[] = '"' . $field . '"'; // Totara: always quote column names to allow reserved words.
        }
        $fields = implode(',', $fields);
        $qms    = array_fill(0, count($params), '?');
        $qms    = implode(',', $qms);

        $sql = "INSERT INTO {$this->prefix}$table ($fields) VALUES($qms)";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = $this->mysqli->query($rawsql);
        $id = @$this->mysqli->insert_id; // must be called before query_end() which may insert log into db
        $this->query_end($result);

        if (!$customsequence and !$id) {
            throw new dml_write_exception('unknown error fetching inserted id');
        }

        if (!$returnid) {
            return true;
        } else {
            return (int)$id;
        }
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
     * NOTE: order of record ids is not guaranteed to match the order of $dataobjects
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
        if (!is_array($dataobjects) and !$dataobjects instanceof Traversable) {
            throw new coding_exception('insert_records() passed non-traversable object');
        }

        // MySQL has a relatively small query length limit by default,
        // make sure 'max_allowed_packet' in my.cnf is high enough
        // if you change the following default...
        static $chunksize = null;
        if ($chunksize === null) {
            if (!empty($this->dboptions['bulkinsertsize'])) {
                $chunksize = (int)$this->dboptions['bulkinsertsize'];

            } else {
                if (PHP_INT_SIZE === 4) {
                    // Bad luck for Windows, we cannot do any maths with large numbers.
                    $chunksize = 5;
                } else {
                    $sql = "SHOW VARIABLES LIKE 'max_allowed_packet'";
                    $this->query_start($sql, null, SQL_QUERY_AUX);
                    $result = $this->mysqli->query($sql);
                    $this->query_end($result);
                    $size = 0;
                    if ($rec = $result->fetch_assoc()) {
                        $size = $rec['Value'];
                    }
                    $result->close();
                    // Hopefully 200kb per object are enough.
                    $chunksize = (int)($size / 200000);
                    if ($chunksize > 50) {
                        $chunksize = 50;
                    }
                }
            }
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
     * Insert records in chunks.
     *
     * Note: can be used only from insert_records().
     *
     * @param string $table
     * @param array $chunk
     * @param database_column_info[] $columns
     */
    protected function insert_chunk($table, array $chunk, array $columns) {
        $fieldssql = '('.implode(',', array_keys($columns)).')';

        $valuessql = '('.implode(',', array_fill(0, count($columns), '?')).')';
        $valuessql = implode(',', array_fill(0, count($chunk), $valuessql));

        $params = array();
        foreach ($chunk as $dataobject) {
            foreach ($columns as $field => $column) {
                $params[] = $this->normalise_value($column, $dataobject[$field]);
            }
        }

        $sql = "INSERT INTO {$this->prefix}$table $fieldssql VALUES $valuessql";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);
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
            if (!isset($columns[$field])) {
                continue;
            }
            $cleaned[$field] = $value;
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

        $sets = array();
        foreach ($params as $field=>$value) {
            $sets[] = '"' . $field .'" = ?'; // Totara: always quote column names to allow reserved words.
        }

        $params[] = $id; // last ? in WHERE condition

        $sets = implode(',', $sets);
        $sql = "UPDATE {$this->prefix}$table SET $sets WHERE id=?";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

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

        return $this->update_record_raw($table, $cleaned, $bulk);
    }

    /**
     * Delete one or more records from a table which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string|sql $select A fragment of SQL to be used in a where clause in the SQL call (used to define the selection criteria).
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function delete_records_select($table, $select, array $params=null) {
        if ($select instanceof sql) {
            $sql = self::sql("DELETE FROM {{$table}}");
            $sql = $sql->append($select->prepend("WHERE"));
        } else {
            if ($select) {
                $select = "WHERE $select";
            }
            $sql = "DELETE FROM {{$table}} $select";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        return true;
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS SIGNED) ';
    }

    public function sql_cast_char2real($fieldname, $text=false) {
        // Set to 65 (max mysql 5.5 precision) with 7 as scale
        // because we must ensure at least 6 decimal positions
        // per casting given that postgres is casting to that scale (::real::).
        // Can be raised easily but that must be done in all DBs and tests.
        return ' CAST(' . $fieldname . ' AS DECIMAL(65,7)) ';
    }

    public function sql_equal($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notequal = false) {
        $equalop = $notequal ? '<>' : '=';

        // Totara: Future MySQL versions will have case and accent sensitive collations, for now just look for the _bin versions.
        $bincollate = $this->get_charset() . '_bin';

        $dbcollation = $this->get_dbcollation();
        if (strpos($dbcollation, '_as_cs') !== false) {
            // Totara: admin configured MySQL 8 properly!
            if ($casesensitive) {
                return "$fieldname $equalop $param";
            }
            $col = ($accentsensitive ? '_as' : '_ai') . '_ci';
            $collation = str_replace('_as_cs', $col, $dbcollation);
            return "$fieldname COLLATE $collation $equalop $param";
        }

        if ($casesensitive) {
            // Current MySQL versions do not support case sensitive and accent insensitive.
            return "$fieldname COLLATE $bincollate $equalop $param";
        } else if ($accentsensitive) {
            // Case insensitive and accent sensitive, we can force a binary comparison once all texts are using the same case.
            return "LOWER($fieldname) COLLATE $bincollate $equalop LOWER($param)";
        } else {
            // Case insensitive and accent insensitive. All collations are that way, but utf8_bin.
            $collation = '';
            if ($this->get_dbcollation() == 'utf8_bin') {
                $collation = 'COLLATE utf8_unicode_ci';
            } else if ($this->get_dbcollation() == 'utf8mb4_bin') {
                $collation = 'COLLATE utf8mb4_unicode_ci';
            }
            return "$fieldname $collation $equalop $param";
        }
    }

    public function sql_cast_char2float($fieldname) {
        return ' CAST(' . $fieldname . ' AS DECIMAL(20,2)) ';
    }

    public function sql_cast_2char($fieldname) {
        $charset = $this->get_charset();
        return ' CAST(' . $fieldname . ' AS CHAR) COLLATE ' . $charset . '_bin';
    }

    /**
     * Returns 'LIKE' part of a query.
     *
     * Note that mysql does not support $casesensitive = true and $accentsensitive = false.
     * More information in http://bugs.mysql.com/bug.php?id=19567.
     *
     * @param string $fieldname usually name of the table column
     * @param string $param usually bound query parameter (?, :named)
     * @param bool $casesensitive use case sensitive search
     * @param bool $accensensitive use accent sensitive search (ignored if $casesensitive is true)
     * @param bool $notlike true means "NOT LIKE"
     * @param string $escapechar escape char for '%' and '_'
     * @return string SQL code fragment
     */
    public function sql_like($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notlike = false, $escapechar = '\\') {
        if (strpos($param, '%') !== false) {
            debugging('Potential SQL injection detected, sql_like() expects bound parameters (? or :named)');
        }
        $escapechar = $this->mysqli->real_escape_string($escapechar); // prevents problems with C-style escapes of enclosing '\'

        // Totara: Future MySQL versions will have case and accent sensitive collations, for now just look for the _bin versions.
        $bincollate = $this->get_charset() . '_bin';

        $LIKE = $notlike ? 'NOT LIKE' : 'LIKE';

        $dbcollation = $this->get_dbcollation();
        if (strpos($dbcollation, '_as_cs') !== false) {
            // Totara: admin configured MySQL 8 properly!
            if ($casesensitive) {
                return "$fieldname $LIKE $param ESCAPE '$escapechar'";
            }
            $col = ($accentsensitive ? '_as' : '_ai') . '_ci';
            $collation = str_replace('_as_cs', $col, $dbcollation);
            return "$fieldname $LIKE $param COLLATE $collation ESCAPE '$escapechar'";
        }

        if ($casesensitive) {
            // Current MySQL versions do not support case sensitive and accent insensitive.
            return "$fieldname $LIKE $param COLLATE $bincollate ESCAPE '$escapechar'";

        } else if ($accentsensitive) {
            // Case insensitive and accent sensitive, we can force a binary comparison once all texts are using the same case.
            return "LOWER($fieldname) $LIKE LOWER($param) COLLATE $bincollate ESCAPE '$escapechar'";

        } else {
            // Case insensitive and accent insensitive.
            $collation = '';
            if ($this->get_dbcollation() == 'utf8_bin') {
                // Force a case insensitive comparison if using utf8_bin.
                $collation = 'COLLATE utf8_unicode_ci';
            } else if ($this->get_dbcollation() == 'utf8mb4_bin') {
                // Force a case insensitive comparison if using utf8mb4_bin.
                $collation = 'COLLATE utf8mb4_unicode_ci';
            }

            return "$fieldname $LIKE $param $collation ESCAPE '$escapechar'";
        }
    }

    /**
     * Returns the proper SQL to do CONCAT between the elements passed
     * Can take many parameters
     *
     * @param string $str,... 1 or more fields/strings to concat
     *
     * @return string The concat sql
     */
    public function sql_concat() {
        $arr = func_get_args();
        $s = implode(', ', $arr);
        if ($s === '') {
            return "''";
        }
        return "CONCAT($s)";
    }

    /**
     * Returns database specific SQL code similar to GROUP_CONCAT() behaviour from MySQL.
     *
     * NOTE: NULL values are skipped, use COALESCE if you want to include a replacement.
     *
     * @since Totara 2.6.34, 2.7.17, 2.9.9
     *
     * @param string $expr      Expression to get individual values
     * @param string $separator The delimiter to separate the values, a simple string value only
     * @param string $orderby   ORDER BY clause that determines order of rows with values,
     *                          optional since Totara 2.6.44, 2.7.27, 2.9.19, 9.7
     * @return string SQL fragment equivalent to GROUP_CONCAT()
     */
    public function sql_group_concat($expr, $separator, $orderby = '') {
        if ($orderby) {
            $orderby = "ORDER BY $orderby";
        } else {
            $orderby = "";
        }
        // See: http://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_group-concat
        $separator = $this->get_manager()->generator->addslashes($separator);
        return " GROUP_CONCAT({$expr} {$orderby} SEPARATOR '{$separator}') ";
    }

    /**
     * Returns database specific SQL code similar to GROUP_CONCAT() behaviour from MySQL
     * where duplicates are removed.
     *
     * NOTE: NULL values are skipped, use COALESCE if you want to include a replacement,
     *       the ordering of results cannot be defined.
     *
     * @since Totara 2.6.44, 2.7.27, 2.9.19, 9.7
     *
     * @param string $expr      Expression to get individual values
     * @param string $separator The delimiter to separate the values, a simple string value only
     * @return string SQL fragment equivalent to GROUP_CONCAT()
     */
    public function sql_group_concat_unique($expr, $separator) {
        // See: http://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_group-concat
        $separator = $this->get_manager()->generator->addslashes($separator);
        return " GROUP_CONCAT(DISTINCT {$expr} SEPARATOR '{$separator}') ";
    }

    /**
     * Returns the proper SQL to do CONCAT between the elements passed
     * with a given separator
     *
     * @param string $separator The string to use as the separator
     * @param array $elements An array of items to concatenate
     * @return string The concat SQL
     */
    public function sql_concat_join($separator="' '", $elements=array()) {
        $s = implode(', ', $elements);

        if ($s === '') {
            return "''";
        }
        return "CONCAT_WS($separator, $s)";
    }

    /**
     * Returns the SQL text to be used to calculate the length in characters of one expression.
     * @param string fieldname or expression to calculate its length in characters.
     * @return string the piece of SQL code to be used in the statement.
     */
    public function sql_length($fieldname) {
        return ' CHAR_LENGTH(' . $fieldname . ')';
    }

    /**
     * Does this driver support regex syntax when searching
     */
    public function sql_regex_supported() {
        return true;
    }

    /**
     * Return regex positive or negative match sql
     * @param bool $positivematch
     * @param bool $casesensitive
     * @return string or empty if not supported
     */
    public function sql_regex($positivematch = true, $casesensitive = false) {
        $collation = '';
        if ($casesensitive) {
            if (substr($this->get_dbcollation(), -4) !== '_bin' && substr($this->get_dbcollation(), -3) !== '_cs') {
                $collationinfo = explode('_', $this->get_dbcollation());
                $collation = 'COLLATE ' . $collationinfo[0] . '_bin ';
            }
        } else {
            if (substr($this->get_dbcollation(), -3) === '_cs') {
                $collation = 'COLLATE ' . substr($this->get_dbcollation(), 0, -3) . '_ci ';
            } else if ($this->get_dbcollation() == 'utf8_bin') {
                $collation = 'COLLATE utf8_unicode_ci ';
            } else if ($this->get_dbcollation() == 'utf8mb4_bin') {
                $collation = 'COLLATE utf8mb4_unicode_ci ';
            }
        }

        return $collation . ($positivematch ? 'REGEXP' : 'NOT REGEXP');
    }

    /**
     * Returns the driver specific syntax for the beginning of a word boundary.
     *
     * @since Totara 13.0
     * @return string or empty if not supported
     */
    public function sql_regex_word_boundary_start(): string {
        $version = $this->get_server_info()['version'];
        // ICU expression library in MySQL 8 has new word boundary markers.
        if (version_compare($version, '8.0.4', '>=')) {
            return '\\b';
        }
        return '[[:<:]]';
    }

    /**
     * Returns the driver specific syntax for the end of a word boundary.
     *
     * @since Totara 13.0
     * @return string or empty if not supported
     */
    public function sql_regex_word_boundary_end(): string {
        $version = $this->get_server_info()['version'];
        // ICU expression library in MySQL 8 has new word boundary markers.
        if (version_compare($version, '8.0.4', '>=')) {
            return '\\b';
        }
        return '[[:>:]]';
    }

    /**
     * Returns the SQL to be used in order to an UNSIGNED INTEGER column to SIGNED.
     *
     * @deprecated since 2.3
     * @param string $fieldname The name of the field to be cast
     * @return string The piece of SQL code to be used in your statement.
     */
    public function sql_cast_2signed($fieldname) {
        return ' CAST(' . $fieldname . ' AS SIGNED) ';
    }

    /**
     * Returns the SQL that allows to find intersection of two or more queries
     *
     * @since Moodle 2.8
     *
     * @param array $selects array of SQL select queries, each of them only returns fields with the names from $fields
     * @param string $fields comma-separated list of fields
     * @return string SQL query that will return only values that are present in each of selects
     */
    public function sql_intersect($selects, $fields) {
        if (count($selects) <= 1) {
            return parent::sql_intersect($selects, $fields);
        }
        $fields = preg_replace('/\s/', '', $fields);
        static $aliascnt = 0;
        $falias = 'intsctal'.($aliascnt++);
        $rv = "SELECT $falias.".
            preg_replace('/,/', ','.$falias.'.', $fields).
            " FROM ($selects[0]) $falias";
        $fields = preg_split('/,/', $fields);
        for ($i = 1; $i < count($selects); $i++) {
            $alias = 'intsctal'.($aliascnt++);
            $ons = array();
            foreach ($fields as $f) {
                $ons[] = "$falias.$f = $alias.$f";
            }
            $rv .= " JOIN (".$selects[$i].") $alias ON ". join(' AND ', $ons);
        }
        return $rv;
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
     * @return void
     */
    public function get_session_lock($rowid, $timeout) {
        parent::get_session_lock($rowid, $timeout);

        $fullname = $this->dbname.'-'.$this->prefix.'-session-'.$rowid;
        $sql = "SELECT GET_LOCK('$fullname', $timeout)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result) {
            $arr = $result->fetch_assoc();
            $result->close();

            if (reset($arr) == 1) {
                return;
            } else {
                throw new dml_sessionwait_exception();
            }
        }
    }

    public function release_session_lock($rowid) {
        if (!$this->used_for_db_sessions) {
            return;
        }

        parent::release_session_lock($rowid);
        $fullname = $this->dbname.'-'.$this->prefix.'-session-'.$rowid;
        $sql = "SELECT RELEASE_LOCK('$fullname')";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result) {
            $result->close();
        }
    }

    /**
     * Are transactions supported?
     *
     * Totara: transaction support is required!
     *
     * @return bool
     */
    protected function transactions_supported() {
        return true;
    }

    /**
     * Driver specific start of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function begin_transaction() {
        $sql = "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        $sql = "START TRANSACTION";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
    }

    /**
     * Driver specific commit of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function commit_transaction() {
        $sql = "COMMIT";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        $this->autocommitted = false;
    }

    /**
     * Driver specific abort of real database transaction,
     * this can not be used directly in code.
     * @return void
     */
    protected function rollback_transaction() {
        $sql = "ROLLBACK";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        // If this was true then we are in trouble, but clear the flag anyway.
        $this->autocommitted = false;
    }

    /**
     * Creates new database savepoint.
     * @param string $name
     */
    protected function create_savepoint(string $name) {
        $sql = "SAVEPOINT {$name}";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
    }

    /**
     * Release savepoint, rollback will not be possible any more.
     * @param string $name
     */
    protected function release_savepoint(string $name) {
        if ($this->autocommitted) {
            // We cannot rollback, so let's pretend we can at least commit.
            return;
        }
        $sql = "RELEASE SAVEPOINT {$name}";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
    }

    /**
     * Rolls back current transaction back to given savepoint.
     * @param string $name
     */
    protected function rollback_savepoint(string $name) {
        $sql = "ROLLBACK TO SAVEPOINT {$name}";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
    }

    /**
     * Do not use.
     *
     * @deprecated
     *
     * @param string|sql $sql the SQL select query to execute.
     * @param array $params array of sql parameters (optional)
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @param int &$count this variable will be filled with count of rows returned by select without limit statement
     * @return counted_recordset A moodle_recordset instance.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    public function get_counted_recordset_sql($sql, array $params=null, $limitfrom = 0, $limitnum = 0, &$count = 0) {
        global $CFG;
        require_once($CFG->libdir.'/dml/counted_recordset.php');

        debugging('Counted recordsets are deprecated, use two separate queries instead.', DEBUG_DEVELOPER);

        if ($sql instanceof sql) {
            if (!empty($params)) {
                debugging('$params parameter is ignored when sql instance supplied', DEBUG_DEVELOPER);
            }
            $params = $sql->get_params();
            $sql = $sql->get_sql();
        }

        if (!preg_match('/^\s*SELECT\s/is', $sql)) {
            throw new dml_exception('dmlcountedrecordseterror', null, "Counted recordset query must start with SELECT");
        }

        $sqlcnt = preg_replace('/^\s*SELECT\s/is', 'SELECT SQL_CALC_FOUND_ROWS ', $sql);

        $recordset = $this->get_recordset_sql($sqlcnt, $params, $limitfrom, $limitnum);

        // Get count.
        $mysqlcount = $this->get_field_sql("SELECT FOUND_ROWS()");
        $recordset = new counted_recordset($recordset, $mysqlcount);
        $count = $recordset->get_count_without_limits();

        return $recordset;
    }

    /**
     * Build a natural language search subquery using database specific search functions.
     *
     * @since Totara 12
     *
     * @param string $table        database table name
     * @param array  $searchfields ['field_name'=>weight, ...] eg: ['high'=>3, 'medium'=>2, 'low'=>1]
     * @param string $searchtext   natural language search text
     * @return array [sql, params[]]
     */
    protected function build_fts_subquery(string $table, array $searchfields, string $searchtext): array {
        $mode = 'NATURAL LANGUAGE MODE';
        if ($this->get_fts_mode($searchtext) === self::SEARCH_MODE_BOOLEAN) {
            $mode = 'BOOLEAN MODE';
        }

        $params = array();
        $score = array();

        foreach ($searchfields as $field => $weight) {
            $paramname = $this->get_unique_param('fts');
            $params[$paramname] = $searchtext;
            $score[] = "(MATCH ({$field}) AGAINST (:{$paramname} IN {$mode}))*{$weight}";
        }

        $scoresum = implode(' + ', $score);
        $sql = "SELECT id, {$scoresum} AS score
                  FROM {{$table}}
                HAVING score > 0";

        return array("({$sql})", $params);
    }
}
