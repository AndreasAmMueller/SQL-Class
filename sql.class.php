<?php

/**
 * sql.class.php
 *
 * @author Andreas Mueller <webmaster@am-wd.de>
 * @version 1.0-20140606
 *
 * @description
 * This class tries to provide (full) support for
 * - MySQL
 * - SQLite
 * using only one function-name
 *
 * Correct syntax (no slangs) required!
 **/

@error_reporting(0);

class SQL {
	// MySQL
	private $host, $port, $user, $database, $encoding;
	// SQLite
	private $file;
	// Both
	private $password, $type, $con;

	// constructor => use static functions to create object!
	public function __construct($type) {
		$this->type = $type;
	}

	// static function to create mysql-instance
	public static function MySQL($user, $password, $database, $host = 'localhost', $port = 3306) {
		if (!class_exists('mysqli'))
				throw new Exception('<b>Error:</b> MySQLi Class not installed! Details <a href="http://php.net/manual/de/book.mysqli.php">here</a>');

		$self = new self('mysql');
		$self->setHost($host);
		$self->setUser($user);
		$self->setPassword($password);
		$self->setDatabase($database);
		$self->setPort($port);
		$self->setEncoding('utf8');

		return $self;
	}

	// static function to create sqlite-instance
	public static function SQLite($file, $password = '') {
		if (!class_exists('SQLite3'))
				throw new Exception('<b>Error:</b> SQLite3 Class not installed! Details <a href="http://php.net/manual/de/book.sqlite3.php">here</a>');

		$self = new self('sqlite');
		$self->setFile($file);
		$self->setPassword($password);

		return $self;
	}

	// destructor
	public function __destruct() {
		$this->close();
	}

	/* --- GETter and SETer ---
	------------------------ */
	public function setHost($host) {
		$this->host = $host;
	}
	public function getHost() {
		return $this->host;
	}

	public function setUser($user) {
		$this->user = $user;
	}
	public function getUser() {
		return $this->user;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}

	public function setDatabase($database) {
		$this->database = $database;
	}
	public function getDatabase() {
		return $this->database;
	}

	public function setPort($port) {
		$this->port = $port;
	}
	public function getPort() {
		return $this->port;
	}

	public function setEncoding($enc) {
		$this->encoding = $enc;
	}
	public function getEncoding() {
		return $this->encoding;
	}

	public function setFile($file) {
		if (!is_writable(dirname($file)))
				throw new Exception('<b>Error:</b> SQLite file directory not writable');
		$this->file = $file;
	}
	public function getFile() {
		return $this->file;
	}

	/* --- Open connection ---
	----------------------- */
	public function open() {
		return ($this->type == 'sqlite') ? $this->openSQLite() : $this->openMySQL();
	}

	public function connect() {
		return $this->open();
	}

	private function openSQLite() {
		$switch = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
		$c = new SQLite3($this->file, $switch, $this->password);
		if ($c->lastErrorCode()) {
			throw new Exception('Failed to open SQLite: ('.$c->lastErrorCode().') '.$c->lastErrorMsg());
			return false;
		}
		$this->con = $c;
		return true;
	}

	private function openMySQL() {
		$c = new mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
		if ($c->connect_errno) {
			throw new Exception('Failed to connect to MySQL: ('.$c->connect_errno.') '.$c->connect_error);
			return false;
		}
		$this->con = $c;
		$query = "character_set_client = '$this->encoding',";
		$query.= "character_set_server = '$this->encoding',";
		$query.= "character_set_connection = '$this->encoding',";
		$query.= "character_set_database = '$this->encoding',";
		$query.= "character_set_results = '$this->encoding'";
		$this->query("SET ".$query);

		return true;
	}

	/* --- Close connection ---
	------------------------ */
	public function close() {
		if ($this->con != null || $this->con != false) {
			$res = $this->con->close();
			$this->con = null;     // set to default value
			return $res;
		}
		return true;
	}

	public function disconnect() {
		return $this->close();
	}

	/* --- Query ---
	------------- */
	public function query($query) {
		return $this->con->query($query);
	}

	public function error() {
		return ($this->type == 'sqlite') ? $this->con->lastErrorMsg() : $this->con->error;
	}

	/* --- Results ---
	--------------- */
	public function fetch_array($result) {
		return ($this->type == 'sqlite') ? $result->fetchArray() : $result->fetch_array();
	}

	public function fetch_object($result) {
		if ($this->type == 'sqlite') {
			$array = $result->fetchArray();
			if (empty($array))
					return;

			$res = new stdClass();
			foreach ($res as $key => $val)
					$res->$key = $val;

			return $res;
		} else {
			return $result->fetch_object();
		}
	}

	public function num_rows($result) {
		if ($this->type == 'sqlite') {
			$count = 0;
			while ($row = $this->fetch_object($result))
					$count++;

			return $count;
		} else {
			return $result->num_rows;
		}
	}

	public function insert_id() {
		return ($this->type == 'sqlite') ? $this->con->lastInsertRowID() : $this->con->insert_id;
	}

	public function affected_rows() {
		return ($this->type == 'sqlite') ? $this->con->changes() : $this->con->affected_rows;
	}

	/* --- SQL Dump ---
	---------------- */
	public function getTables() {
		return ($this->type == 'sqlite') ? $this->sqTables() : $this->myTables();
	}

	private function sqTables() {
		$tables = array();
		$res = $this->query("SELECT name FROM sqlite_master WHERE type = 'table'");
		while ($row = $this->fetch_array($res)) {
			if ($row['name'] != 'sqlite_sequence')
					$tables[] = $row['name'];
		}
		return $tables;
	}

	private function myTables() {
		$tables = array();
		$res = $this->query("SHOW TABLES FROM `".$this->database."`");
		while ($row = $this->fetch_array($res)) {
			$tables[] = $row['Tables_in_'.$this->database];
		}
		return $tables;
	}

	private function getStructure($table) {
		$file = array();
		$file[] = '';
		$file[] = '-- ----------------------------';
		$file[] = '-- Table structure for `'.$table.'`';
		$file[] = '-- ----------------------------';
		$file[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
		if ($this->type == 'sqlite') {
			$res = $this->query("SELECT sql FROM sqlite_master WHERE name = '".$table."'");
			while ($row = $this->fetch_object($res)) {
				$file[] = $row->sql.';';
			}
		} else {
			$res = $this->query("SHOW CREATE TABLE `".$table."`");
			while ($row = $this->fetch_array($res)) {
				$file[] = str_replace(strstr($row['Create Table'], ') ENGINE'), ');', $row['Create Table']);
			}
		}

		return implode(PHP_EOL, $file);
	}

	private function getData($table) {
		$file = array();
		$file[] = '';
		$file[] = '-- ----------------------------';
		$file[] = '-- Table content for `'.$table.'`';
		$file[] = '-- ----------------------------';

		$res = $this->query("SELECT * FROM `".$table."`");
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

	public function getDump($part = 'structure,data', $tables = '') {
		$todo = explode(',', $part);

		$file = array();
		$this->open();
		$file[] = "-- SQL-Class Dump by AM.WD";
		$file[] = "-- Version 1.0";
		$file[] = "-- http://am-wd.de";
		$file[] = "--";
		$file[] = "-- https://bitbucket.org/BlackyPanther/sql-class";
		$file[] = "--";
		$file[] = "-- PHP: ".phpversion();
		if ($this->type == 'sqlite') {
			$version = SQLite3::version();
			$file[] = "-- SQL: SQLite ".$version['versionString'];
			$file[] = "-- File: ".basename($this->file);
		} else {
			$file[] = "-- SQL: MySQL ".$this->con->server_info;
			$file[] = "-- Host: ".$this->host.":".$this->port;
		}
		$file[] = "--";
		$file[] = "-- Time: ".date('d. F Y H:i');
		$file[] = '';
		$file[] = 'SET FOREIGN_KEY_CHECKS = 0;';

		if (empty($tables)) {
			$tbls = $this->getTables();
		} else {
			$tbls = explode(',', $tables);
		}

		foreach ($tbls as $tbl) {
			if (in_array('structure', $todo))
					$file[] = $this->getStructure($tbl);

			if (in_array('data', $todo))
					$file[] = $this->getData($tbl);
		}

		$this->close();

		$file[] = 'SET FOREIGN_KEY_CHECKS = 1;';
		return implode(PHP_EOL, $file);
	}

	public function restoreDump($dump, $returnError = false) {
		$file = (is_array($dump)) ? $dump : preg_split("/(\r\n|\n|\r)/", $dump);
		$line = 0; $lines = count($file);

		$error = '';
		$comment = array();
		$query = "";
		$queries = 0;
		$querylines = 0;
		$inparents = false;

		$comment[] = '#';
		$comment[] = '--';

		$this->open();

		while ($line < $lines) {
			$dumpline = $file[$line++];

			$dumpline = str_replace("\r", "", $dumpline);
			$dumpline = str_replace("\n", "", $dumpline);

			if (!$inparents) {
				$skipline = false;
				reset($comment);

				foreach ($comment as $c) {
					if (!$inparents && (trim($dumpline) == '' || strpos($dumpline, $c) === 0)) {
						$skipline = true;
						break;
					}
				}

				if ($skipline)
						continue;
			} else {
				$dumpline .= PHP_EOL;
			}

			$dumpline_deslashed = str_replace("\\\\", "", $dumpline);
			$parents = substr_count($dumpline_deslashed, "'") - substr_count($dumpline_deslashed, "\\'");
			if ($parents % 2 != 0)
					$inparents = !$inparents;

			$query .= $dumpline;

			if (!$inparents)
					$querylines++;

			if (preg_match("/;$/", trim($dumpline)) && !$inparents) {
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

		$this->close();

		if (empty($error)) {
			return true;
		} else if ($returnError) {
				return $error;
		} else {
			//die(nl2br($error));
			return false;
		}
	}

}
