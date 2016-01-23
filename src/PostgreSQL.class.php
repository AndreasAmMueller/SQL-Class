<?php

/**
 * MySQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;
require_once __DIR__.'/SQL.class.php';

die('Not implemented yet');

/**
 * Representing specific implementation for PostgreSQL
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160116 | stable
 */
class PostgreSQL extends SQL {
	/**
	 * Initializes a new instance of PostgreSQL Connection
	 *
	 * @return PostgreSQL
	 */
	function __construct($conn_params) {
		if (!function_exists('pg_connect'))
			throw new \RuntimeException('PostgreSQL extension not found. Details at http://php.net/manual/de/book.pgsql.php');

		if (!is_array($conn_params) || count($conn_params) == 0 || !isset($conn_params['dbname']))
			throw new \ArgumentException('$conn_params at least need the parameter \'dbname\' to be valid');

		parent::__construct();

		$this->connection_params = $conn_params;
		$this->encoding = 'UTF8';
	}

	/**
	 * return version info of connection driver
	 * @return string
	 */
	public function driver_version() {
		$v = pg_version($this->conn);
		return $v['client'];
	}

	/**
	 * function to establish a database connection
	 *
	 * @return bool
	 * @throws \RuntimeException if something went wrong establishing the connection
	 */
	public function open() {
		$allowed_params = array('host', 'hostaddr', 'port', 'dbname', 'user', 'password', 'connect_timeout', 'sslmode');

		$params = array();

		foreach ($this->connection_params as $param => $value) {
			if (in_array($param, $allowed_params))
				$params[] = $param.'='.$value;
		}

		$connection_string = implode(' ', $params);

		$conn = pg_connect($connection_string." options='--client_encoding=".$this->encoding."'");

		if ($conn === false)
			throw new \RuntimeException('Failed to connect: '.pg_last_error());

		$this->conn = $conn;
		$this->status = 'open';

		return true;
	}

	/**
	 * function to close previously established connection
	 * @return bool true on successful close else false
	 */
	public function close() {
		if ($this->conn != null || $this->conn != false) {
			$ret = pg_close($this->conn);
			$this->conn = null;
			$this->status = 'closed';
			return $ret;
		}

		return true;
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
		return pg_escape_string($this->conn, $string);
	}

	/**
	 * execute a simple querry directly
	 * @param  string  $query  Statement for request
	 * @return ressource
	 */
	public function query($query) {
		return pg_query($this->conn, $query);
	}

	/**
	 * returns message of last raised error
	 * @return string
	 */
	public function error() {
		return pg_last_error($this->conn);
	}

	/**
	 * returns all results as associative array
	 * @param  resource $result result of last executed query
	 * @return mixed[]
	 */
	public function fetch_array($result) {
		return pg_fetch_array($result);
	}

	/**
	 * returns all results as object
	 * @param  resource $result result of last executed query
	 * @return mixed
	 */
	public function fetch_object($result) {
		return pg_fetch_object($result);
	}

	/**
	 * returns number of rows in current result
	 * @param  resource $result result of last executed query
	 * @return int
	 */
	public function num_rows($result) {
		return pg_num_rows($this->conn, $result);
	}

	/**
	 * returns unique id of last inserted row
	 * @param  resource $result result of last executed query
	 * @return int
	 */
	public function insert_id($result) {
		return pg_last_oid($result);
	}

	/**
	 * returns number of rows affected by last statement
	 * @param  resource $result result of last executed query
	 * @return int
	 */
	public function affected_rows($result) {
		return pg_affected_rows($result);
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
		throw new \Exception('Not Implemented yet');



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
		throw new \Exception('Not Implemented yet');


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
