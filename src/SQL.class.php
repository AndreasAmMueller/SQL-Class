<?php

/**
 * SQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

/**
 * Constant for Sql dump creation. Dump only the structure of the db.
 * @var string
 */
define('SQLOPT_DB_STRUCTURE',        'structure');

/**
 * Constant for Sql dump creation. Dump only the data of the db.
 * @var string
 */
define('SQLOPT_DB_DATA',             'data');

/**
 * Constant for Sql dump creation. Dump both, structure and data of the db.
 * @var string
 */
define('SQLOPT_DB_STRUCTUREANDDATA', 'structure,data');

// Load all SQL classes if this class was included
// Ohterwise load only this class to have all definitions.
if (!defined('SQLOPT_CLASS_LOADED'))
{
	require_once __DIR__.'/MySQL.class.php';
	require_once __DIR__.'/SQLite.class.php';
	//require_once __DIR__.'/PostgreSQL.class.php';
}

/**
 * Base-class with all functionality (or at least stubs) needed to access a database.
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015-2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160309 | stable
 */
abstract class SQL
{

	// --- class-member
	// ===========================================================================

	/**
	 * Represents the version of these classes.
	 * @var string
	 */
	private $version = "1.3";

	/**
	 * An array for all common data slinked to this class.
	 * @var mixed[]
	 */
	protected $data;

	/**
	 * The database connection itself.
	 * @var \PDO
	 */
	protected $conn;

	/**
	 * The status of the database connection.n
	 * @var string
	 */
	protected $status;

	/**
	 * The last raised error message.
	 * @var string
	 */
	protected $error;

	// --- 'magic' methods
	// ===========================================================================

	/**
	 * Initializes the properties.
	 */
	function __construct()
	{
		$this->data = array();
		$this->status = 'closed';
		$this->error = '';
	}

	/**
	 * Finalizes an instance of SQL class.
	 */
	function __destruct()
	{
		$this->close();
	}

	/**
	 * Gets a value to a key.
	 *
	 * @param  string  $name
	 *   Name of the key.
	 *
	 * @return  mixed
	 *   Value to the key.
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->data))
			return $this->data[$name];

		$trace = debug_backtrace();
		trigger_error('Undefined key for __get(): '
		              .$name.' in '
		              .$trace[0]['file'].' at row '
		              .$trace[0]['line']
			, E_USER_NOTICE);

		return null;
	}

	/**
	 * Sets a value for a key.
	 *
	 * @param string $name
	 *   The name of the key.
	 * @param mixed $value
	 *   The value of the key.
	 *
	 * return void
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * Gets a value indicating whether the key exists.
	 *
	 * @param string $name
	 *   The name of the key.
	 *
	 * @return bool
	 *   true if the key exists, otherwise false.
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * Deletes (unsets) a key.
	 *
	 * @param string $name
	 *   The name of the key.
	 *
	 * @return void
	 */
	public function __unset($name)
	{
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
		}
	}

	// --- version strings
	// ===========================================================================

	/**
	 * Gets the version number of the classes.
	 *
	 * @return string
	 *   The version number.
	 */
	public function version()
	{
		return $this->version;
	}

	/**
	 * Gets the version of the database client (driver).
	 *
	 * @return string
	 *   The driver and its version number.
	 */
	abstract public function driver_version();

	// --- data-connection
	// ===========================================================================

	/**
	 * Opens the connection to the database.
	 *
	 * @return bool
	 *   true on success otherwise false.
	 */
	public function connect()
	{
		return $this->open();
	}

	/**
	 * Closes the connection to the database.
	 *
	 * @param bool $force (optional)
	 *   A value indicating whether the close should be enforced.
	 *
	 * @return void
	 */
	public function disconnect($force = false)
	{
		$this->close($force);
	}

	/**
	 * Opens the connection to the database.
	 *
	 * @return bool
	 *   true on a successful connection otherwise false and the error message is set.
	 */
	abstract public function open();

	/**
	 * Closes the connection to the database.
	 *
	 * @param bool $force (optional)
	 *   A value indicating whether the close should be enforced.
	 *
	 * @return void
	 */
	public function close($force = false)
	{
		if ($this->conn == null || $this->conn == false)
			return;

		$persistent = $this->conn->getAttribute(\PDO::ATTR_PERSISTENT);

		if ($persistent && !$force)
			return;

		$this->conn = null;
		$this->status = 'closed';
	}

	/**
	 * Gets the state of the connection.
	 *
	 * @return string
	 */
	public function status()
	{
	 	return $this->status;
	 }

	// --- transaction-handling
	// ===========================================================================

	/**
	 * Starts a transaction.
	 *
	 * @return void
	 */
	public function begin_transaction()
	{
		$this->conn->beginTransaction();
	}

	/**
	 * Commits all changes.
	 *
	 * @return void
	 */
	public function commit()
	{
		$this->conn->commit();
	}

	/**
	 * Performs a rollback of all changes since transaction began.
	 *
	 * @return void
	 */
	public function rollback()
	{
		$this->conn->rollBack();
	}

	// --- queries
	// ===========================================================================

	/**
	 * Executes a given query statement.
	 *
	 * @param string $query
	 *   The statement to execute.
	 *
	 * @return \PDOStatement|bool
	 *   On error false is returned and the error message set, otherwise the PDOStatement
	 */
	public function query($query)
	{
		$this->error = '';

		try
		{
			return $this->conn->query($query);
		}
		catch(\PDOException $ex)
		{
			$this->error = $ex->getMessage();
			return false;
		}
	}

	/**
	 * Gets the last raised error message.
	 *
	 * @return string
	 */
	public function error()
	{
		return $this->error;
	}

	/**
	 * Prepares a query statement for execution.
	 *
	 * @param string $query
	 *   The query statement.
	 *
	 * @return \PDOStatement
	 *   The statement for handling.
	 */
	public function prepare($query)
	{
		$close = $this->status == 'close';

		$this->open();
		$stmt = $this->conn->prepare($query);

		if ($close)
			$this->close();

		return $stmt;
	}

	/**
	 * Gets the correct function with all elements concatenated.
	 *
	 * @param mixed[] $elements
	 *   An array list of elements to concatenate.
	 *
	 * @return string
	 */
	abstract public function concat($elements);

	/**
	 * Gets the escaped string for an Sql statement.
	 *
	 * The function may not be used while binding parameters via SQLCommand!
	 *
	 * @param string $string
	 *   The string to escape.
	 *
	 * @return string
	 */
	public function escape($string)
	{
		$close = $this->status == 'close';

		$this->open();
		$escaped = $this->conn->quote($string);

		if ($close)
			$this->close();

		return $escaped;
	}

	// --- data-results
	// ===========================================================================

	/**
	 * Gets an result row as assoc. array.
	 *
	 * @param \PDOStatement $result
	 *   The result of an queried statement.
	 *
	 * @return mixed[]
	 */
	public function fetch_array($result)
	{
		return $this->fetch_assoc($result);
	}

	/**
	 * Gets an result row as object.
	 *
	 * @param \PDOStatement $result
	 *   The result of an queried statement.
	 *
	 * @return object
	 */
	public function fetch_object($result)
	{
		return $result->fetch(\PDO::FETCH_OBJ);
	}

	/**
	 * Gets an result row as assoc. array.
	 *
	 * @param \PDOStatement $result
	 *   The result of an queried statement.
	 *
	 * @return mixed[]
	 */
	function fetch_assoc($result)
	{
		return $result->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Gets the number of rows returned by the executed statement.
	 *
	 * @param \PDOStatement $result
	 *   The result of an queried statement.
	 *
	 * @return int
	 *   The number of rows.
	 */
	public function num_rows($result)
	{
		return $result->rowCount();
	}

	/**
	 * Gets the unique id of the last inserted row.
	 *
	 * @return int
	 *   The id of the last inserted row.
	 */
	public function insert_id()
	{
		return $this->conn->lastInsertId();
	}

	/**
	 * Gets the number of rows affected by the last statement.
	 *
	 * @param \PDOStatement $result
	 *   The result of an queried statement.
	 *
	 * @return int
	 *   The number of rows affected by the statement.
	 */
	public function affected_rows($result)
	{
		return $result->rowCount();
	}

	// --- db-dumps
	// ===========================================================================

	/**
	 * Creates a dump to reimport.
	 *
	 * @param string|string[] $part (optional)
	 *   String or string array with structure, data or structure,data.
	 * @param string|string[] $tables
	 *   String or string array with tablenames to dump.
	 *
	 * @return string
	 */
	abstract public function get_dump($part = SQLOPT_DB_STRUCTUREANDDATA, $tables = '');

	/**
	 * Restores a given dump.
	 *
	 * @param string|string[] $dump
	 *   Dump as line separated array, filepath or string.
	 * @param bool $exitOnError (optional)
	 *   A value indicating whether the execution should be stopped on error.
	 * @param bool $returnError ()
	 *   A value indicating whether the error should be returned.
	 *
	 * @return bool|string
	 */
	public function restore_dump($dump, $exitOnError = false, $returnError = false)
	{
		$file = is_array($dump) ? $dump
			: (file_exists($dump) ? preg_split("/(\r\n|\n|\r)/", file_get_contents($dump))
				: preg_split("/(\r\n|\n|\r)/", $dump));

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
	 * Gets a converted Sql dump for a MySQL database.
	 *
	 * @param string|string[] $dump
	 *   Filepath, string or string-array containing a dump.
	 *
	 * @return string
	 *   The converted dump.
	 */
	public static function convertToMySQL($dump)
	{
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Gets a converted Sql dump for a SQLite database.to
	 *
	 * @param string|string[] $dump
	 *   Filepath, string or string-array containing a dump.
	 *
	 * @return string
	 *   The converted dump.
	 */
	public static function convertToSQLite($dump)
	{
		throw new \Exception('Not implemented yet');
	}

	// --- helper for dump-create
	// ===========================================================================

	/**
	 * Gets the structure of a table.
	 *
	 * @param string $table
	 *   The name of the table.
	 *
	 * @return string
	 *   The table's structure.
	 */
	abstract protected function get_structure($table);

	/**
	 * Gets the data of a table. Each row as own insert statement.
	 *
	 * @param string $table
	 *   The name of the table.
	 *
	 * @return string
	 *   All insert statements.
	 */
	protected function get_data($table)
	{
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
	 * Gets a value indicating whether the string starts with a sequence.
	 *
	 * @param string $haystack
	 *   The string where the sequence is searched in.
	 * @param string $needle
	 *   The sequence to search within the haystack.
	 *
	 * @return bool
	 *   true if needle was found at the start otherwise false.
	 */
	protected static function starts_with($haystack, $needle)
	{
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
	}

	/**
	 * Gets a value indicating whether the string ends with a sequence.
	 *
	 * @param string $haystack
	 *   The string where the sequence is searched in.
	 * @param string $needle
	 *   The sequence to search within the haystack.
	 *
	 * @return bool
	 *   true if needle was found at the end otherwise false.
	 */
	protected static function ends_with($haystack, $needle)
	{
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}
}

?>