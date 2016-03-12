<?php

/**
 * MySQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

// load base class with infos
if (!class_exists('AMWD\SQL\SQL'))
{
	define('SQLOPT_CLASS_LOADED', true);
	require_once __DIR__.'/SQL.class.php';
}

/**
 * Representing specific implementation for MySQL
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015-2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160309 | stable
 */
class MySQL extends SQL
{
	/**
	 * Initializes a new instance of a MySQL connection.
	 *
	 * @param string $user
	 *   The database user.
	 * @param string $password
	 *   The database users password.
	 * @param string $database (optional)
	 *   The name of the database. On null all statements need a `db`.`table`.
	 * @param int $port (optional)
	 *   The portnumber for the tcp connection. (Sockets currently not supported)
	 * @param string $hostname (optional)
	 *   The hostname or ip address of the MySQL server.
	 *
	 * @throws \RuntimeException
	 *   If the driver is not available.
	 */
	function __construct($user, $password, $database = null, $port = 3306, $hostname = '127.0.0.1')
	{
		if (!in_array('mysql', \PDO::getAvailableDrivers()))
			throw new \RuntimeException("MySQL driver not found. Please install MySQL support for PHP (e.g. php-mysql)");

		parent::__construct();

		$this->user     = $user;
		$this->password = $password;
		$this->database = $database;
		$this->port     = $port;
		$this->hostname = $hostname;
		$this->encoding = 'utf8';
		$this->locales  = 'en_US';
		$this->persistent = true;
	}

	/**
	 * Gets the version of the database client (driver).
	 *
	 * @return string
	 *   The driver and its version number.
	 */
	public function driver_version()
	{
		return 'MySQL '.$this->conn->getAttribute(\PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Opens the connection to the database.
	 *
	 * @return bool
	 *   true on a successful connection otherwise false and the error message is set.
	 */
	public function open()
	{
		$conString = 'mysql';
		$conString.= ':host='.$this->hostname;
		$conString.= ';port='.$this->port;
		$conString.= ';charset='.$this->encoding;

		if ($this->database != null)
			$conString.= ';dbname='.$this->database;

		try
		{
			$conn = new \PDO(
				$conString,
				$this->user,
				$this->password
			);
			$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$conn->setAttribute(\PDO::ATTR_PERSISTENT, $this->persistent);

			$conn->query("SET lc_time_names = ".$this->locales.";");

			$this->conn = $conn;
			$this->status = 'open';

			return true;
		}
		catch (\PDOException $ex)
		{
			$this->error = $ex->getMessage();
			$this->status = 'broken';

			return false;
		}

	}

	/**
	 * Gets the correct function with all elements concatenated.
	 *
	 * @param mixed[] $elements
	 *   An array list of elements to concatenate.
	 *
	 * @return string
	 */
	public function concat($elements)
	{
		if (count($elements) == 0)
			return '';

		return 'CONCAT('.implode(', ', $elements).')';
	}

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
	public function get_dump($part = SQLOPT_DB_STRUCTUREANDDATA, $tables = '')
	{
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
		$file[] = "-- Timestamp: ".date('Y-m-d H:i:s');
		$file[] = "";
		$file[] = "SET FOREIGN_KEY_CHECKS = 0;";

		foreach ($tables as $t) {
			if (in_array(SQLOPT_DB_STRUCTURE, $part))
					$file[] = $this->get_structure($t);

			if (in_array(SQLOPT_DB_DATA, $part))
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
	 * Gets the structure of a table.
	 *
	 * @param string $table
	 *   The name of the table.
	 *
	 * @return string
	 *   The table's structure.
	 */
	protected function get_structure($table)
	{
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
