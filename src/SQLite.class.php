<?php

/**
 * SQLite.class.php
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
 * Representing specific implementation for SQLite
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015-2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160309 | stable
 */
class SQLite extends SQL
{
	/**
	 * Initializes a new instance of a SQLite connection.
	 *
	 * @param string $path
	 *   The path to db file (absolute path prefered).
	 *
	 * @throws \RuntimeException
	 *   If the driver is not available.
	 */
	function __construct($path)
	{
		if (!in_array('sqlite', \PDO::getAvailableDrivers()))
			throw new \RuntimeException("SQLite3 driver not found. Please install SQLite support for PHP (e.g. php-sqlite)");

		parent::__construct();

		$this->path       = $path;
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
		return 'SQLite '.$this->conn->getAttribute(\PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Opens the connection to the database.
	 *
	 * @return bool
	 *   true on a successful connection otherwise false and the error message is set.
	 */
	public function open()
	{
		try
		{
			$conn = new \PDO('sqlite:'.$this->path);
			$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$conn->setAttribute(\PDO::ATTR_PERSISTENT, $this->persistent);

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
	public static function concat($elements)
	{
		if (count($elements) == 0)
			return '';

		return implode(' || ', $elements);
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

		if ($this->status == 'closed')
		{
			$this->open();
			$close = true;
		}

		if (!is_array($part))
			$part = explode(',', $part);

		if (!is_array($tables))
		{
			$tables = trim($tables);

			$tmp = empty($tables) ? array() : explode(',', $tables);
			$tables = array();

			foreach ($tmp as $tbl)
			{
				$tbl = trim($tbl);
				if (!empty($tbl))
					$tables[] = $tbl;
			}
		}

		if (count($tables) == 0)
		{
			$tables = array();
			$res = $this->query("SELECT name FROM sqlite_master WHERE type = 'table'");
			while ($row = $this->fetch_array($res))
			{
				if ($row['name'] != 'sqlite_sequence')
					$tables[] = $row['name'];
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

		foreach ($tables as $t)
		{
			if (in_array(SQLOPT_DB_STRUCTURE, $part))
					$file[] = $this->get_structure($t);

			if (in_array(SQLOPT_DB_DATA, $part))
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

		$res = $this->query("SELECT sql FROM sqlite_master WHERE name = '".$table."'");
		while ($row = $this->fetch_object($res))
		{
			$file[] = $row->sql.';';
		}

		return implode(PHP_EOL, $file);
	}
}

?>