<?php

/**
 * MySQL.class.php
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
 * Representing specific implementation for MySQL
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160123 | stable
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
		$this->locales  = 'en_US';
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
  , lc_time_names            = '".$this->locales."'
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
	 * returns the properly concatenated elements as the DB supports it.
	 * @return string
	 */
	public function concat($elements = array()) {
		if (count($elements) == 0)
			return '';

		return 'CONCAT('.implode(', ', $elements).')';
	}

	/**
	 * returns the properly escaped string for this DB type.
	 * @return string
	 */
	public function escape($string) {
		$close = $this->status == 'closed';
		$this->open();

		$escaped = $this->conn->real_escape_string($string);

		if ($close)
			$this->close();

		return $escaped;
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

?>
