<?php

/**
 * SQLite.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

// load base class with infos
if (!class_exists('SQL')) {
	define('SQLOPT_CLASS_LOADED', true);
	require_once __DIR__.'/SQL.class.php';
}

/**
 * Representing specific implementation for SQLite
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160123 | stable
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
	 * returns the properly concatenated elements as the DB supports it.
	 * @return string
	 */
	public function concat($elements = array()) {
		if (count($elements) == 0)
			return '';

		return implode(' || ', $elements);
	}

	/**
	 * returns the properly escaped string for this DB type.
	 * @return string
	 */
	public function escape($string) {
		reuturn \SQLite3::escapeString($string);
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

		if (!is_array($tables)) {
			$tables = trim($tables);

			$tmp = empty($tables) ? array() : explode(',', $tables);
			$tables = array();

			foreach ($tmp as $tbl) {
				$tbl = trim($tbl);
				if (!empty($tbl))
					$tables[] = $tbl;
			}
		}

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
