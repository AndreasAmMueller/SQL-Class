<?php

/**
 * SQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

/**
 * Base-class with all functionality (or at least stubs) needed to access a database
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.2-20151109 | stable
 */
abstract class SQL {

	// --- constants
	// ===========================================================================

	/**
	 * Flag for an SQL dump if only structure should be dumped
	 * @var string
	 */
	const SQL_DB_STRUCTURE = 'structure';

	/**
	 * Flag for an SQL dump if only data should be dumped
	 * @var string
	 */
	const SQL_DB_DATA = 'data';

	/**
	 * Flag for an SQL dump if structure and data should be dumped
	 * @var string
	 */
	const SQL_DB_STRUCTUREANDDATA = 'structure,data';

	/**
	 * Flag for an SQL restore if errors should be returned
	 * @var bool
	 */
	const SQL_RETURN_ERROR = true;

	/**
	 * Flag for an SQL restore if errors should not be returned
	 * @var bool
	 */
	const SQL_RETURN_NO_ERROR = false;

	// --- class-member
	// ===========================================================================

	/**
	 * version of these classes
	 * @var string
	 */
	private $version = "1.2";

	/**
	 * data array for all properties
	 * @var array
	 */
	protected $data;

	/**
	 * connection object
	 * @var mixed
	 */
	protected $conn;
	
	/**
	 * status of Connection
	 * @var string
	 */
	protected $status;

	// --- 'magic' methods
	// ===========================================================================

	/**
	 * initialize new base object with property array
	 *
	 * @return SQL
	 */
	function __construct() {
		$this->data = array();
		$this->status = 'closed';
	}

	/**
	 * do your rest before object is destroyed
	 * @return void
	 */
	function __destruct() {
		$this->close();
	}

	/**
	 * 'magic' get method for all properties
	 *
	 * @param string $name name of the property
	 * @return mixed
	 */
	public function __get($name) {
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		$trace = debug_backtrace();
		trigger_error('Undefined key for __get(): '
									.$name.' in '
									.$trace[0]['file'].' at row '
									.$trace[0]['line']
				, E_USER_NOTICE);

		return null;
	}

	/**
	 * 'magic' set method for all properties
	 *
	 * @param string $name name of the property
	 * @param mixed $value value of the property
	 * @return void
	 */
	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

	/**
	 * 'magic' check if property exists
	 * @param string $name name of the property
	 * @return bool
	 */
	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 * 'magic' property delete
	 * @param string $name name of the property
	 * @return void
	 */
	public function __unset($name) {
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
		}
	}

	// --- static methods
	// ===========================================================================

	/**
	 * static function initializing a new instance of MySQL and keep compatibility with v1.1
	 *
	 * @param string $user database user
	 * @param string $password database users password
	 * @param string $database name of the database
	 * @param int $port portnumber for tcp connection
	 * @param string $host hostname or ip address of mysql server
	 *
	 * @return MySQL
	 */
	public static function MySQL($user, $password, $database, $port = 3306, $host = '127.0.0.1') {
		return new MySQL($user, $password, $database, $port, $host);
	}

	/**
	 * static function initializing a new instance of SQLite and keep compatibility with v1.1
	 *
	 * @param string $path path to databasefile
	 * @param string $password password for database; default: empty
	 *
	 * @return SQLite
	 */
	public static function SQLite($file, $password = '') {
		return new SQLite($file, $password);
	}

	// --- version strings
	// ===========================================================================

	/**
	 * return version number of these classes
	 * @return string
	 **/
	public function version() {
		return $this->version;
	}

	/**
	 * return version of the connection driver
	 * @return string
	 */
	abstract public function driver_version();

	// --- data-connection
	// ===========================================================================

	/**
	 * alias for open()
	 */
	public function connect() {
		return $this->open();
	}

	/**
	 * alias for close()
	 */
	public function disconnect() {
		$this->close();
	}

	/**
	 * function stub to establish a database connection
	 *
	 * @return bool
	 * @throws \RuntimeException if something went wrong establishing the connection
	 */
	abstract public function open();

	/**
	 * function to close previously established connection
	 * @return bool
	 */
	public function close() {
		if ($this->conn != null || $this->conn != false) {
			$ret = $this->conn->close();
			$this->conn = null;
			$this->status = 'closed';
			return $ret;
		}

		return true;
	}
	
	/**
	 * returns current connection status
	 * @return string
	 */
	public function status() {
	 	return $this->status;
	 }

	// --- transaction-handling
	// ===========================================================================

	/**
	 * starting an transaction
	 * @return void
	 */
	public function begin_transaction() {
		$this->query("BEGIN;");
	}

	/**
	 * commiting all made changes
	 * @return void
	 */
	public function commit() {
		$this->query("COMMIT;");
	}

	/**
	 * roll unsuccessful transaction back
	 * @return void
	 */
	public function rollback() {
		$this->query("ROLLBACK;");
	}

	// --- queries
	// ===========================================================================

	/**
	 * execute a simple querry directly
	 * @param string $query Statement for request
	 * @return \msqli_result
	 */
	public function query($query) {
		return $this->conn->query($query);
	}

	/**
	 * returns message of last raised error
	 * @return string
	 */
	abstract public function error();

	// --- data-results
	// ===========================================================================

	/**
	 * returns all results as associative array
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed[]
	 */
	abstract public function fetch_array($result);

	/**
	 * returns all results as object
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed
	 */
	abstract public function fetch_object($result);

	/**
	 * returns number of rows in current result
	 * @param \mysqli_result $result result of last executed query
	 * @return int
	 */
	abstract public function num_rows($result);

	/**
	 * returns unique id of last inserted row
	 * @return int
	 */
	abstract public function insert_id();

	/**
	 * returns number of rows affected by last statement
	 * @return int
	 */
	abstract public function affected_rows();

	// --- db-dumps
	// ===========================================================================

	/**
	 * create an complete SQL dump from (selected) tables
	 *
	 * @param mixed $part string or string-array with structure, data or structure,data
	 * @param mixed $tables string or string-array with tablenames for dump
	 *
	 * @return string with complete dump
	 */
	abstract public function get_dump($part = self::SQL_DB_STRUCTUREANDDATA, $tables = '');

	/**
	 * restore an SQL dump
	 *
	 * @param mixed $dump dump as line-separated array, filepath or string
	 * @param bool $returnError flag if error should be returned
	 *
	 * @return mixed
	 */
	public function restore_dump($dump, $returnError = self::SQL_RETURN_NO_ERROR) {
		$file = is_array($dump) ? $dump : (file_exists($dump) ? preg_split("/(\r\n|\n|\r)/", file_get_contents($dump)) : preg_split("/(\r\n|\n|\r)/", $dump));

		$line = 0;
		$lines = count($file);

		$error = '';
		$query = "";
		$queries = 0;
		$querylines = 0;
		$inParents = false;
		$inComment = false;

		$this->open();
		$this->begin_transaction();

		try {
			while ($line < $lines) {
				$dumpline = $file[$line++];

				// remove newline chars
				$dumpline = str_replace(array("\r", "\n"), array("", ""), $dumpline);

				// recognize multiline comment-block ending
				if ($inComment && self::ends_with($dumpline, "*/")) {
					$inComment = false;
					continue;
				}

				// we're in multiline comment-block
				if ($inComment)
						continue;

				// we're not in multiline comment and not in parents
				if (!$inParents) {
					// empty or single line comment
					if (trim($dumpline) == ''
							|| self::starts_with($dumpline, "--")
							|| self::starts_with($dumpline, "#")) {
						continue;
					}
					// start of multiline comment-block
					if (self::starts_with($dumpline, "/*")) {
						$inComment = true;
						continue;
					}
				} else {
					$dumpline .= PHP_EOL;
				}

				$dumpline_deslashed = str_replace("\\\\", "", $dumpline);
				$parents = substr_count($dumpline_deslashed, "'") - substr_count($dumpline_deslashed, "\\'");
				if ($parents % 2 != 0)
						$inParents = !$inParents;

				$query .= $dumpline;

				if (!$inParents)
						$querylines++;

				if (!$inParents && self::ends_with(trim($dumpline), ";")) {
					if (!$this->query($query)) {
						$error .= 'Error in line '.$line.': '.$this->error().PHP_EOL;
						$error .= 'Code: '.$dumpline.PHP_EOL;
						$error .= 'Executed: '.$queries.' Queries'.PHP_EOL;
					}

					$queries++;
					$querylines = 0;
					$query = '';
				}
			}

			$this->commit();
		} catch (Exception $ex) {
			$this->rollback();

			$error .= 'Exception caught: '.$ex->getMessage();
		}

		$this->close();

		if (empty($error)) {
			return true;
		} else if ($returnError) {
				return $error;
		} else {
			return false;
		}
	}

	// --- dump conversion
	// ===========================================================================

/**
	 * try to convert a SQLite dump into an MySQL dump
	 *
	 * @param mixed $dump filepath, string or string-array with dump
	 * @return mixed
	 */
	public static function convertToMySQL($dump) {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * try to convert a MySQL dump into an SQLite dump
	 *
	 * @param mixed $dump filepath, string or string-array with dump
	 * @return mixed
	 */
	public static function convertToSQLite($dump) {
		throw new \Exception('Not implemented yet');
	}

	// --- helper for dump-create
	// ===========================================================================

	/**
	 * function stub; returning SQL structure of table
	 *
	 * @param string $table name of table to get structure for
	 * @return string
	 */
	abstract protected function get_structure($table);

	/**
	 * function returning SQL insertions for table content
	 *
	 * @param string $table name of table to get contents
	 * @return string
	 */
	protected function get_data($table) {
		$file = array();
		$file[] = '';
		$file[] = '--';
		$file[] = '-- Table content for `'.$table.'`';
		$file[] = '--';

		$res = $this->query("SELECT * FROM `".$table."`;");
		while ($row = $this->fetch_array($res)) {
			$line = 'INSERT INTO `'.$table.'` VALUES (';
			$vals = array();
			foreach ($row as $key => $val) {
				if (!is_numeric($key)) {
					if (is_null($val)) {
						$vals[] = 'NULL';
					} else {
						$val = str_replace("\r", "", $val);
						$val = str_replace("\n", PHP_EOL, $val);
						$val = str_replace("'", "\'", $val);
						$vals[] = "'$val'";
					}
				}
			}
			$line.= implode(',', $vals);
			$line.= ');';
			$file[] = $line;
		}

		return implode(PHP_EOL, $file);
	}

	// --- helper for dump-restore
	// ===========================================================================

	/**
	 * check if $haystack starts with $needle
	 *
	 * @param string $haystack part to be checked
	 * @param string $needle sequence to search at start
	 * @return bool
	 */
	protected static function starts_with($haystack, $needle) {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
	}

	/**
	 * check if $haystack ends with $needle
	 *
	 * @param string $haystack part to be checked
	 * @param string $needle sequence to search at end
	 * @return bool
	 */
	protected static function ends_with($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}
}

/**
 * Representing specific implementation for MySQL
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.2-20150811 | stable
 */
class MySQL extends SQL {
	/**
	 * Initialize a new instance of MySQL Connection
	 *
	 * @param string $user database user
	 * @param string $password database users password
	 * @param string $database name of the database
	 * @param int $port portnumber for tcp connection
	 * @param string $hostname hostname or ip address of mysql server
	 *
	 * @return MySQL
	 */
	function __construct($user, $password, $database, $port = 3306, $hostname = '127.0.0.1') {
		if (!class_exists('mysqli'))
			throw new \RuntimeException('MySQLi Class not found. Details at http://php.net/manual/de/book.mysqli.php');

		parent::__construct();

		$this->user     = $user;
		$this->password = $password;
		$this->database = $database;
		$this->port     = $port;
		$this->hostname = $hostname;
		$this->encoding = 'utf8';
		$this->locale   = 'en_US';
	}

	/**
	 * return version info of connection driver
	 * @return string
	 */
	public function driver_version() {
		return $this->conn->server_info;
	}

	/**
	 * function to establish a database connection
	 *
	 * @return bool
	 * @throws \RuntimeException if something went wrong establishing the connection
	 */
	public function open() {
		$conn = new \mysqli(
			$this->hostname,
			$this->user,
			$this->password,
			$this->database,
			$this->port
		);

		if ($conn->connect_errno)
			throw new \RuntimeException('Failed to connect: #'.$conn->connect_errno.' | '.$conn->connect_error);

		$this->conn = $conn;

		// enforce correct encoding
		$query = "SET
    character_set_client     = '".$this->encoding."'
  , character_set_server     = '".$this->encoding."'
  , character_set_connection = '".$this->encoding."'
  , character_set_database   = '".$this->encoding."'
  , character_set_results    = '".$this->encoding."'
  , lc_time_names            = '".$this->locale."'
;";

		$this->query($query);
		$this->status = 'open';

		return true;
	}

	/**
	 * returns message of last raised error
	 * @return string
	 */
	public function error() {
		return $this->conn->error;
	}

	/**
	 * returns all results as associative array
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed[]
	 */
	public function fetch_array($result) {
		return $result->fetch_array();
	}

	/**
	 * returns all results as object
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed
	 */
	public function fetch_object($result) {
		return $result->fetch_object();
	}

	/**
	 * returns number of rows in current result
	 * @param \mysqli_result $result result of last executed query
	 * @return int
	 */
	public function num_rows($result) {
		return $result->num_rows;
	}

	/**
	 * returns unique id of last inserted row
	 * @return int
	 */
	public function insert_id() {
		return $this->conn->insert_id;
	}

	/**
	 * returns number of rows affected by last statement
	 * @return int
	 */
	public function affected_rows() {
		return $this->conn->affected_rows;
	}

	/**
	 * create an complete SQL dump from (selected) tables
	 *
	 * @param mixed $part string or string-array with structure, data or structure,data
	 * @param mixed $tables string or string-array with tablenames for dump
	 *
	 * @return string with complete dump
	 */
	public function get_dump($part = self::SQL_DB_STRUCTUREANDDATA, $tables = '') {
		$close = false;

		if ($this->status == 'closed') {
			$this->open();
			$close = true;
		}

		if (!is_array($part))
			$part = explode(',', $part);

		if (!is_array($tables))
			$tables = explode(',', $tables);

		if (count($tables) == 0) {
			$tables = array();
			$res = $this->query("SHOW TABLES FROM `".$this->database."`");
			while ($row = $this->fetch_array($res)) {
				$tables[] = $row['Tables_in_'.$this->database];
			}
		}

		$file = array();

		$file[] = "-- SQL Dump v".$this->version()." by AM.WD";
		$file[] = "-- http://am-wd.de";
		$file[] = "--";
		$file[] = "-- https://bitbucket.org/BlackyPanther/sql-class";
		$file[] = "--";
		$file[] = "-- PHP:       ".phpversion();
		$file[] = "-- SQL Type:  MySQL";
		$file[] = "-- Version:   ".$this->driver_version();
		$file[] = "--";
		$file[] = "-- Host:      ".$this->hostname.":".$this->port;
		$file[] = "--";
		$file[] = "-- Timestamp: ".date('d. F Y H:i:s');
		$file[] = "";
		$file[] = "SET FOREIGN_KEY_CHECKS = 0;";

		foreach ($tables as $t) {
			if (in_array(self::SQL_DB_STRUCTURE, $part))
					$file[] = $this->get_structure($t);

			if (in_array(self::SQL_DB_DATA, $part))
					$file[] = $this->get_data($t);
		}

		$file[] = "";
		$file[] = "SET FOREIGN_KEY_CHECKS = 1;";
		$file[] = "";

		if ($close)
			$this->close();

		return implode(PHP_EOL, $file);
	}

	/**
	 * function returning SQL structure of table
	 *
	 * @param string $table name of table to get structure for
	 * @return string
	 */
	protected function get_structure($table) {
		$file = array();
		$file[] = '';
		$file[] = '--';
		$file[] = '-- Table structure for `'.$table.'`';
		$file[] = '--';
		$file[] = 'DROP TABLE IF EXISTS `'.$table.'`;';

		$res = $this->query("SHOW CREATE TABLE `".$table."`");
		while ($row = $this->fetch_array($res)) {
			$file[] = preg_replace("/AUTO_INCREMENT=(.*) DEFAULT/", "AUTO_INCREMENT=1 DEFAULT", $row['Create Table'].";");
		}

		return implode(PHP_EOL, $file);
	}
}

/**
 * Representing specific implementation for SQLite
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.2-20150811 | stable
 */
class SQLite extends SQL {
	/**
	 * Initialize a new instance of SQLite Connection
	 *
	 * @param string $path path to db file
	 * @param string $password database users password
	 *
	 * @return SQLite
	 */
	function __construct($path, $password = '') {
		if (!class_exists('SQLite3'))
			throw new \RuntimeException('SQLite3 Class not found. Details at http://php.net/manual/de/book.sqlite3.php');

		parent::__construct();

		$this->path     = $path;
		$this->password = $password;
	}

	/**
	 * return version info of connection driver
	 * @return string
	 */
	public function driver_version() {
		$v = \SQLite3::version();
		return $v['versionString'];
	}

	/**
	 * function to establish a database connection
	 *
	 * @return bool
	 * @throws \RuntimeException if something went wrong establishing the connection
	 */
	public function open() {
		$conn = new \SQLite3(
			$this->path,
			SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
			$this->password
		);

		if ($conn->lastErrorCode())
			throw new \RuntimeException('Failed to connect: #'.$conn->lastErrorCode().' | '.$conn->lastErrorMsg());

		$this->conn = $conn;
		$this->status = 'open';

		return true;
	}

	/**
	 * returns message of last raised error
	 * @return string
	 */
	public function error() {
		return $this->conn->lastErrorMsg();
	}

	/**
	 * returns all results as associative array
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed[]
	 */
	public function fetch_array($result) {
		return $result->fetchArray();
	}

	/**
	 * returns all results as object
	 * @param \mysqli_result $result result of last executed query
	 * @return mixed
	 */
	public function fetch_object($result) {
		$array = $result->fetchArray();
		if (empty($array))
				return NULL;

		$res = new \stdClass();
		foreach ($array as $key => $val)
				$res->$key = $val;

		return $res;
	}

	/**
	 * returns number of rows in current result
	 * @param \mysqli_result $result result of last executed query
	 * @return int
	 */
	public function num_rows($result) {
		$count = 0;
		while ($row = $this->fetch_object($result))
			$count++;

		return $count;
	}

	/**
	 * returns unique id of last inserted row
	 * @return int
	 */
	public function insert_id() {
		return $this->conn->lastInsertRowID();
	}

	/**
	 * returns number of rows affected by last statement
	 * @return int
	 */
	public function affected_rows() {
		return $this->conn->changes();
	}

	/**
	 * create an complete SQL dump from (selected) tables
	 *
	 * @param mixed $part string or string-array with structure, data or structure,data
	 * @param mixed $tables string or string-array with tablenames for dump
	 *
	 * @return string with complete dump
	 */
	public function get_dump($part = self::SQL_DB_STRUCTUREANDDATA, $tables = '') {
		$close = false;
		
		if ($this->status == 'closed') {
			$this->open();
			$close = true;
		}

		if (!is_array($part))
			$part = explode(',', $part);

		if (!is_array($tables))
			$tables = explode(',', $tables);

		if (count($tables) == 0) {
			$tables = array();
			$res = $this->query("SELECT name FROM sqlite_master WHERE type = 'table'");
			while ($row = $this->fetch_array($res)) {
				if ($row['name'] != 'sqlite_sequence') {
					$tables[] = $row['name'];
				}
			}
		}

		$file = array();

		$file[] = "-- SQL Dump v".$this->version()." by AM.WD";
		$file[] = "-- http://am-wd.de";
		$file[] = "--";
		$file[] = "-- https://bitbucket.org/BlackyPanther/sql-class";
		$file[] = "--";
		$file[] = "-- PHP:       ".phpversion();
		$file[] = "-- SQL Type:  SQLite";
		$file[] = "-- Version:   ".$this->driver_version();
		$file[] = "--";
		$file[] = "-- File:      ".$this->path;
		$file[] = "--";
		$file[] = "-- Timestamp: ".date('d. F Y H:i:s');
		$file[] = "";
		$file[] = "PRAGMA foreign_keys = false;";

		foreach ($tables as $t) {
			if (in_array(self::SQL_DB_STRUCTURE, $part))
					$file[] = $this->get_structure($t);

			if (in_array(self::SQL_DB_DATA, $part))
					$file[] = $this->get_data($t);
		}

		$file[] = "";
		$file[] = "PRAGMA foreign_keys = true;";
		$file[] = "";

		if ($close)
			$this->close();

		return implode(PHP_EOL, $file);
	}

	/**
	 * function returning SQL structure of table
	 *
	 * @param string $table name of table to get structure for
	 * @return string
	 */
	protected function get_structure($table) {
		$file = array();
		$file[] = '';
		$file[] = '--';
		$file[] = '-- Table structure for `'.$table.'`';
		$file[] = '--';
		$file[] = 'DROP TABLE IF EXISTS `'.$table.'`;';

		$res = $this->query("SELECT sql FROM sqlite_master WHERE name = '".$table."'");
		while ($row = $this->fetch_object($res)) {
			$file[] = $row->sql.';';
		}

		return implode(PHP_EOL, $file);
	}
}

?>
