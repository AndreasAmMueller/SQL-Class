<?php

/**
 * sql.class.php
 *
 * @author Andreas Mueller <webmaster@am-wd.de>
 * @version 1.1-20150716
 *
 * @description
 * This class tries to provide (full) support for
 * - MySQL
 * - SQLite3
 * using only one function-name
 *
 * Correct syntax (no slangs) required!
 **/

@error_reporting(0);

class SQL {
	private $version = "1.1";

	// MySQL
	private $host, $port, $user, $database, $encoding, $locales;
	// SQLite
	private $file;
	// Both
	private $password, $type, $con;

	// constructor => use static functions to create object!
	public function __construct($type) {
		$this->type = $type;
	}

	// static function to create mysql-instance
	public static function MySQL($user, $password, $database, $port = 3306, $host = '127.0.0.1') {
		if (!class_exists('mysqli')) {
			if (php_sapi_name() == 'cli') {
				throw new Exception('Error: MySQLi Class not installed! Details at http://php.net/manual/de/book.mysqli.php');
			} else {
				throw new Exception('<b>Error:</b> MySQLi Class not installed! Details <a href="http://php.net/manual/de/book.mysqli.php">here</a>');
			}
		}

		$self = new self('mysql');
		$self->setHost($host);
		$self->setUser($user);
		$self->setPassword($password);
		$self->setDatabase($database);
		$self->setPort($port);
		$self->setEncoding('utf8');
		$self->setLocales('en_US');

		return $self;
	}

	// static function to create sqlite-instance
	public static function SQLite($file, $password = '') {
		if (!class_exists('SQLite3')) {
			if (php_sapi_name() == 'cli') {
				throw new Exception('Error: SQLite3 Class not installed! Details at http://php.net/manual/de/book.sqlite3.php');
			} else {
				throw new Exception('<b>Error:</b> SQLite3 Class not installed! Details <a href="http://php.net/manual/de/book.sqlite3.php">here</a>');
			}
		}

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
		if ($this->type == 'mysql') {
			$this->con->set_charset($enc);
		}
		$this->encoding = $enc;
	}
	public function getEncoding() {
		return $this->encoding;
	}

	public function setLocales($locale) {
		$this->locales = $locale;
	}
	public function getLocales() {
		return $this->locales;
	}

	public function setFile($file) {
		if (!is_writable(dirname($file)))
				throw new Exception('<b>Error:</b> SQLite file directory not writable');
		$this->file = $file;
	}
	public function getFile() {
		return $this->file;
	}

	public function getVersion() {
		if ($this->type == 'sqlite') {
			$v = SQLite3::version();
			return $v['versionString'];
		} else {
			return $this->con->server_info;
		}
	}

	/* --- Open connection ---
	----------------------- */
	public function open() {
		return ($this->type == 'sqlite') ? $this->openSQLite() : $this->openMySQL();
	}
	// wide used alias
	public function connect() {
		return $this->open();
	}

	// private helper to manage errors in SQLite
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

	// private helper to manage conn errors in MySQL and encoding of transfer
	private function openMySQL() {
		$c = new mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
		if ($c->connect_errno) {
			throw new Exception('Failed to connect to MySQL: ('.$c->connect_errno.') '.$c->connect_error);
			return false;
		}
		$this->con = $c;
		// set encoding correct
		$query = "character_set_client = '$this->encoding',";
		$query.= "character_set_server = '$this->encoding',";
		$query.= "character_set_connection = '$this->encoding',";
		$query.= "character_set_database = '$this->encoding',";
		$query.= "character_set_results = '$this->encoding',";
		$query.= "lc_time_names = '$this->locales'";
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
	// commonly used alias
	public function disconnect() {
		return $this->close();
	}

	/* --- Query ---
	------------- */
	public function beginTransaction() {
		$this->query("BEGIN");
	}

	public function commit() {
		$this->query("COMMIT");
	}

	public function rollback() {
		$this->query("ROLLBACK");
	}

	public function query($query) {
		return $this->con->query($query);
	}

	// returns error message of the last executed query
	public function error() {
		return ($this->type == 'sqlite') ? $this->con->lastErrorMsg() : $this->con->error;
	}

	/* --- Results ---
		--------------- */
	// fetch queried results as array
	// this will the wohle array at once
	public function fetch_array($result) {
		return ($this->type == 'sqlite') ? $result->fetchArray() : $result->fetch_array();
	}

	// fetch queried results as objects
	// this will return each time a new object, or NULL if there are no more results
	public function fetch_object($result) {
		if ($result == null || $result == false) {
			return null;
		}

		if ($this->type == 'sqlite') {
			// SQLite we need to built ourselves
			$array = $result->fetchArray();
			if (empty($array))
					return NULL;

			$res = new stdClass();
			foreach ($array as $key => $val)
					$res->$key = $val;

			return $res;
		} else {
			return $result->fetch_object();
		}
	}

	// tell me the number of results
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

	// tell me the id (value from the field with AUTO_INCREMENT which is commonly the unique primary key)
	public function insert_id() {
		return ($this->type == 'sqlite') ? $this->con->lastInsertRowID() : $this->con->insert_id;
	}

	// tell the number of rows affected by the last query
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

	// get me the structure of $table
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

	// get me the data of $table
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

	// create a SQL Dump for Backup (SQLite and MySQL are not compatible!!!)
	// first tests for a converter in progress
	// $part can be either 'structure', 'data' or both seperated by comma
	// $tables can be a comma seperated list or empty for all tables
	// return value is a valid dumpfile (loooong string)
	// just write it down to a file!
	public function getDump($part = 'structure,data', $tables = '') {
		$todo = explode(',', $part);

		$file = array();
		$this->open();
		$file[] = "-- SQL-Class Dump by AM.WD";
		$file[] = "-- Version ".$this->version;
		$file[] = "-- http://am-wd.de";
		$file[] = "--";
		$file[] = "-- https://bitbucket.org/BlackyPanther/sql-class";
		$file[] = "--";
		$file[] = "-- PHP: ".phpversion();
		if ($this->type == 'sqlite') {
			$file[] = "-- SQL: SQLite ".$this->getVersion();
			$file[] = "-- File: ".basename($this->file);
		} else {
			$file[] = "-- SQL: MySQL ".$this->getVersion();
			$file[] = "-- Host: ".$this->host.":".$this->port;
		}
		$file[] = "--";
		$file[] = "-- Time: ".date('d. F Y H:i:s');
		$file[] = '';
		$file[] = 'SET FOREIGN_KEY_CHECKS = 0;'; // important for realtions on ImmoDB

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

		$file[] = '';
		$file[] = 'SET FOREIGN_KEY_CHECKS = 1;'; // important for relations on ImmoDB
		return implode(PHP_EOL, $file);
	}

	// reads a Dumpfile and executes it
	// if $returnError is TRUE the error message will be returned
	// else return value is TRUE or FALSE to show success
	// $dump can be a dump file read into a string or already split into an array (each line an enty)
	public function restoreDump($dump, $returnError = false) {
		$file = is_array($dump) ? $dump : (file_exists($dump) ? preg_split("/(\r\n|\n|\r)/", file_get_contents($dump)) : preg_split("/(\r\n|\n|\r)/", $dump));

		$line = 0; $lines = count($file);

		$error = '';
		$query = "";
		$queries = 0;
		$querylines = 0;
		$inParents = false;
		$inComment = false;

		$this->open();
		$this->beginTransaction();

		try {
			while ($line < $lines) {
				$dumpline = $file[$line++];

				// remove newline chars
				$dumpline = str_replace(array("\r", "\n"), array("", ""), $dumpline);

				// recognize multiline comment-block ending
				if ($inComment && $this->endsWith($dumpline, "*/")) {
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
							|| $this->startsWith($dumpline, "--")
							|| $this->startsWith($dumpline, "#")) {
						continue;
					}
					// start of multiline comment-block
					if ($this->startsWith($dumpline, "/*")) {
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

				if (!$inParents && $this->endsWith(trim($dumpline), ";")) {
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
		} catch (Exception $e) {
			$this->rollback();

			$error .= 'Exception caught: '.$e->getMessage();
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

	// try to convert SQLite3 => MySQL
	public function convertToMySQL($dump) {
		//TODO:
	}

	// try to convert MySQL => SQLite3
	public function convertToSQLite($dump) {
		//TODO:
	}

	private function startsWith($haystack, $needle) {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	private function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

}
