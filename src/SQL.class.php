<?php

/**
 * SQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

// Options
define('SQLOPT_DB_STRUCTURE',        SQL::SQL_DB_STRUCTURE);
define('SQLOPT_DB_DATA',             SQL::SQL_DB_DATA);
define('SQLOPT_DB_STRUCTUREANDDATA', SQL::SQL_DB_STRUCTUREANDDATA);
define('SQLOPT_RETURN_ERROR',        SQL::SQL_RETURN_ERROR);
define('SQLOPT_RETURN_NO_ERROR',     SQL::SQL_RETURN_NO_ERROR);

// Load all SQL classes if this class was included
// Ohterwise load only this class to have all definitions.
if (!defined(SQLOPT_CLASS_LOADED)) {
	require_once __DIR__.'/MySQL.class.php';
	require_once __DIR__.'/SQLite.class.php';
	require_once __DIR__.'/PostgreSQL.class.php';
}

/**
 * Base-class with all functionality (or at least stubs) needed to access a database
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160123 | stable
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
	private $version = "1.3";

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
	 * Initializes new base object with property array.
	 *
	 * @return SQL
	 */
	function __construct() {
		$this->data = array();
		$this->status = 'closed';
	}

	/**
	 * Do your rest before object is destroyed.
	 * @return void
	 */
	function __destruct() {
		$this->close();
	}

	/**
	 * 'magic' get method for all properties
	 *
	 * @param  string $name name of the property
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
	 * @param  string  $name   name of the property
	 * @param  mixed   $value  value of the property
	 * @return void
	 */
	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

	/**
	 * 'magic' check if property exists
	 * @param  string  $name  name of the property
	 * @return bool
	 */
	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 * 'magic' property delete
	 * @param  string  $name  name of the property
	 * @return void
	 */
	public function __unset($name) {
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
		}
	}

	// --- version strings
	// ===========================================================================

	/**
	 * return version number of these classes
	 * @return string
	 */
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
	 * alias for SQL::open()
	 *
	 * @return bool true on success else Exception is thrown
	 */
	public function connect() {
		return $this->open();
	}

	/**
	 * alias for SQL::close()
	 *
	 * @return bool true on success else false
	 */
	public function disconnect() {
		$this->close();
	}

	/**
	 * function stub to establish a database connection
	 *
	 * @return bool true on successful connection
	 * @throws \RuntimeException if something went wrong establishing the connection
	 */
	abstract public function open();

	/**
	 * function to close previously established connection
	 * @return bool true on successful close else false
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
	 * starting an SQL transaction
	 * @return void
	 */
	public function begin_transaction() {
		$this->query("BEGIN;");
	}

	/**
	 * commiting all SQL changes
	 * @return void
	 */
	public function commit() {
		$this->query("COMMIT;");
	}

	/**
	 * roll unsuccessful SQL transaction back
	 * @return void
	 */
	public function rollback() {
		$this->query("ROLLBACK;");
	}

	// --- queries
	// ===========================================================================

	/**
	 * execute a simple querry directly
	 * @param  string  $query  Statement for request
	 * @return ressource
	 */
	public function query($query) {
		return $this->conn->query($query);
	}

	/**
	 * returns message of last raised error
	 * @return string
	 */
	abstract public function error();

	/**
	 * returns the properly concatenated elements as the DB supports it.
	 * @return string
	 */
	abstract public function concat($elements = array());

	/**
	 * returns the properly escaped string for this DB type.
	 * @return string
	 */
	abstract public function escape($string);

	// --- data-results
	// ===========================================================================

	/**
	 * returns all results as associative array
	 * @param  \mysqli_result $result result of last executed query
	 * @return mixed[]
	 */
	abstract public function fetch_array($result);

	/**
	 * returns all results as object
	 * @param  \mysqli_result $result result of last executed query
	 * @return mixed
	 */
	abstract public function fetch_object($result);
	
	/**
	 * returns all results as associative array as fetch_array.
	 * @param  \mysqli_result $result result of last executed query
	 * @return mixed[]
	*/
	public function fetch_assoc($result) {
		return $this->fetch_array($result);
	}

	/**
	 * returns number of rows in current result
	 * @param  \mysqli_result $result result of last executed query
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
	 * @param mixed $part   string or string-array with structure, data or structure,data
	 * @param mixed $tables string or string-array with tablenames for dump
	 *
	 * @return string with complete dump
	 */
	abstract public function get_dump($part = self::SQL_DB_STRUCTUREANDDATA, $tables = '');

	/**
	 * restore an SQL dump
	 *
	 * @param mixed $dump        dump as line-separated array, filepath or string
	 * @param bool  $exitOnError flag if restore should proceed on error or abort
	 * @param bool  $returnError flag if error should be returned
	 *
	 * @return mixed
	 */
	public function restore_dump($dump, $exitOnError = false, $returnError = self::SQL_RETURN_NO_ERROR) {
		$file = is_array($dump) ? $dump : (file_exists($dump) ? preg_split("/(\r\n|\n|\r)/", file_get_contents($dump)) : preg_split("/(\r\n|\n|\r)/", $dump));
		$close = false;

		if ($this->status == 'closed') {
			$this->open();
			$close = true;
		}

		$line       = 0;
		$lines      = count($file);

		$error      = '';
		$query      = "";
		$queries    = 0;
		$querylines = 0;
		$inParents  = false;
		$inComment  = false;

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
						
						if ($exitOnError)
							break;
					}

					$queries++;
					$querylines = 0;
					$query      = '';
				}
			}

			$this->commit();
		} catch (\Exception $ex) {
			$this->rollback();

			$error .= 'Exception caught: '.$ex->getMessage();
		}

		if ($close)
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
			
			$keys = array();
			$vals = array();
			
			foreach ($row as $key => $val) {
				if (!is_numeric($key)) {
					$keys[] = '`'.$key.'`';
					
					if (is_null($val)) {
						$vals[] = 'NULL';
					} else if (is_numeric($val)) {
						$vals[] = is_int($val) ? intval($val) : floatval($val);
					} else {
						$val = str_replace("\r", "", $val);
						$val = str_replace("\n", PHP_EOL, $val);
						$val = str_replace("'", "\'", $val);
						$vals[] = "'".strval($val)."'";
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

?>
